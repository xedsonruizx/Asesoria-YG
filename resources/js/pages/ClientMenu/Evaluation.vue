<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import Button from '@/components/ui/button/Button.vue';
import Card from '@/components/ui/card/Card.vue';
import TopBar from '@/components/MyComponents/TopBar.vue';
import QuestionInput from '@/components/QuestionInput.vue';
import EvaluationResults from '@/components/EvaluationResults.vue';
import { useEvaluationPersistence } from '@/composables/useEvaluationPersistence';
import axios from 'axios';

// Interfaces
interface Question {
    id: number;
    question_text: string;
    category_id: number;
    question_type: 'text' | 'textarea' | 'select' | 'number' | 'checkbox' | 'radio';
    options?: string | string[] | Array<{text: string, points: number}>;
    placeholder?: string;
    min_value?: number;
    max_value?: number;
    points: number;
    show_condition?: {
        parent_question_id: number;
        operator: 'equals' | 'not_equals' | 'contains' | 'not_contains';
        value: any;
    };
    order: number;
    is_active: boolean;
    is_required: boolean;
}

interface Category {
    id: number;
    name: string;
    description?: string;
    slug: string;
    questions_count?: number;
    color?: string;
}

interface CategoryScore {
    category: string;
    score: number;
    maxScore: number;
    progress: number;
    percentage?: number;
    obtainedPoints?: number;
    totalPossiblePoints?: number;
    answeredQuestions?: number;
}

interface TriggeredMulta {
    id: number;
    title: string;
    description: string;
    fine_amount: number;
    severity: string;
}

interface Evaluation {
    id: number;
    user_id: number;
    total_score: number;
    total_progress: number;
    is_completed: boolean;
    completed_at?: string;
    category_scores: Record<string, CategoryScore>;
    status: 'draft' | 'in_progress' | 'completed';
}

// Props
const props = defineProps<{
    evaluation: Evaluation;
    categories: Category[];
    questions: Question[];
    answers: Record<number, any>;
    showResults: boolean;
    categoryScores: Record<string, CategoryScore>;
    triggeredMultas?: TriggeredMulta[];
}>();

// Composables
const { 
    saveAnswersToStorage, 
    loadAnswersFromStorage, 
    clearStoredAnswers, 
    setupAutoSave 
} = useEvaluationPersistence();

// Estado reactivo
const storedData = loadAnswersFromStorage();
const evaluation = ref<Evaluation>(props.evaluation);
const answers = ref<Record<number, any>>({
    ...props.answers,
    ...storedData.answers
});
const showResults = ref<boolean>(
    storedData.showResults || 
    props.showResults || 
    props.evaluation?.status === 'completed' || 
    false
);
const isSubmitting = ref<boolean>(false);
const notification = ref<{ type: string; message: string }>({ type: '', message: '' });
const currentCategoryIndex = ref<number>(0);
const unsavedChanges = new Set<number>();

// Agregar variable reactiva para las multas activadas
const triggeredMultas = ref<TriggeredMulta[]>(props.triggeredMultas || []);

// Función para verificar si una pregunta debe mostrarse
const shouldShowQuestion = (question: Question): boolean => {
    if (!question.show_condition) {
        return true;
    }
    
    if (!question.show_condition.parent_question_id) {
        return true;
    }
    
    const parentQuestion = props.questions.find(q => 
        q.id === question.show_condition.parent_question_id
    );
    
    if (!parentQuestion) return true;
    
    const parentAnswer = answers.value[parentQuestion.id];
    const expectedValue = question.show_condition.value;
    
    if (!['empty', 'is_empty', 'filled', 'is_not_empty'].includes(question.show_condition.operator)) {
        if (expectedValue === null || expectedValue === undefined) return true;
    }
    
    switch (question.show_condition.operator) {
        case 'equals':
            if (parentAnswer === undefined || parentAnswer === null || parentAnswer === '') {
                return false;
            }
            if (typeof expectedValue === 'string' && typeof parentAnswer === 'string') {
                return parentAnswer === expectedValue;
            }
            if (typeof expectedValue === 'boolean') {
                if (expectedValue === true) {
                    return parentAnswer === 'Sí' || parentAnswer === true;
                }
                if (expectedValue === false) {
                    return parentAnswer === 'No' || parentAnswer === false;
                }
            }
            return parentAnswer == expectedValue;
        case 'not_equals':
            if (parentAnswer === undefined || parentAnswer === null || parentAnswer === '') {
                return false;
            }
            return parentAnswer != expectedValue;
        case 'contains':
            if (parentAnswer === undefined || parentAnswer === null || parentAnswer === '') {
                return false;
            }
            return Array.isArray(parentAnswer) && parentAnswer.includes(expectedValue);
        case 'not_contains':
            if (parentAnswer === undefined || parentAnswer === null || parentAnswer === '') {
                return false;
            }
            return !Array.isArray(parentAnswer) || !parentAnswer.includes(expectedValue);
        case 'greater_than':
            if (parentAnswer === undefined || parentAnswer === null || parentAnswer === '') {
                return false;
            }
            const numParentAnswer = parseFloat(parentAnswer);
            const numExpectedValue = parseFloat(expectedValue);
            if (isNaN(numParentAnswer) || isNaN(numExpectedValue)) {
                console.warn('Comparación greater_than: uno de los valores no es numérico', { parentAnswer, expectedValue });
                return false;
            }
            return numParentAnswer > numExpectedValue;
        case 'less_than':
            if (parentAnswer === undefined || parentAnswer === null || parentAnswer === '') {
                return false;
            }
            const numParentAnswerLess = parseFloat(parentAnswer);
            const numExpectedValueLess = parseFloat(expectedValue);
            if (isNaN(numParentAnswerLess) || isNaN(numExpectedValueLess)) {
                console.warn('Comparación less_than: uno de los valores no es numérico', { parentAnswer, expectedValue });
                return false;
            }
            return numParentAnswerLess < numExpectedValueLess;
        case 'empty':
        case 'is_empty':
            const isEmpty = parentAnswer === undefined || parentAnswer === null || parentAnswer === '' || 
                   (Array.isArray(parentAnswer) && parentAnswer.length === 0);
            return isEmpty;
        case 'filled':
        case 'is_not_empty':
            const isFilled = parentAnswer !== undefined && parentAnswer !== null && parentAnswer !== '' && 
                   (!Array.isArray(parentAnswer) || parentAnswer.length > 0);
            return isFilled;
        default:
            return true;
    }
};

// Computed properties
const categoriesWithQuestions = computed(() => {
    return props.categories
        .filter(category => {
            if (category.questions_count !== undefined) {
                return category.questions_count > 0;
            }
            const categoryQuestions = props.questions.filter(q => 
                q.category_id === category.id && q.is_active
            );
            return categoryQuestions.length > 0;
        })
        .map(category => {
            const categoryQuestions = props.questions
                .filter(q => q.category_id === category.id && q.is_active);
            
            const filteredQuestions = categoryQuestions.filter(shouldShowQuestion);
            const sortedQuestions = filteredQuestions.sort((a, b) => a.order - b.order);
            
            return {
                ...category,
                questions: sortedQuestions,
                answeredCount: sortedQuestions.filter(q => 
                    answers.value[q.id] !== undefined && 
                    answers.value[q.id] !== null && 
                    answers.value[q.id] !== ''
                ).length
            };
        })
        .filter(category => {
            return category.questions.length > 0;
        });
});

const overallProgress = computed(() => {
    const totalQuestions = categoriesWithQuestions.value.reduce((sum, cat) => sum + cat.questions.length, 0);
    const answeredQuestions = categoriesWithQuestions.value.reduce((sum, cat) => sum + cat.answeredCount, 0);
    return totalQuestions > 0 ? Math.round((answeredQuestions / totalQuestions) * 100) : 0;
});

const currentCategoryProgress = computed(() => {
    const currentCategory = categoriesWithQuestions.value[currentCategoryIndex.value];
    if (!currentCategory) return 0;
    return currentCategory.questions.length > 0 
        ? Math.round((currentCategory.answeredCount / currentCategory.questions.length) * 100) 
        : 0;
});

const hasUnsavedChanges = computed(() => {
    return unsavedChanges.size > 0;
});

const allQuestionsAnswered = computed(() => {
    return categoriesWithQuestions.value.every(category => 
        category.questions.every(question => 
            !question.is_required || 
            (answers.value[question.id] !== undefined && 
             answers.value[question.id] !== null && 
             answers.value[question.id] !== '')
        )
    );
});

const totalQuestions = computed(() => {
    return categoriesWithQuestions.value.reduce((sum, cat) => sum + cat.questions.length, 0);
});

// Métodos de navegación
const goToCategory = (index: number) => {
    currentCategoryIndex.value = index;
};

const nextCategory = () => {
    if (currentCategoryIndex.value < categoriesWithQuestions.value.length - 1) {
        currentCategoryIndex.value++;
    }
};

const previousCategory = () => {
    if (currentCategoryIndex.value > 0) {
        currentCategoryIndex.value--;
    }
};

// Métodos de guardado
const saveAnswer = (questionId: number, value: any) => {
    if (value === null || value === '' || (Array.isArray(value) && value.length === 0)) {
        delete answers.value[questionId];
        unsavedChanges.delete(questionId);
    } else {
        answers.value[questionId] = value;
        unsavedChanges.add(questionId);
    }
    
    saveAnswersToStorage(answers.value, showResults.value);
};

const saveDraft = () => {
    saveAnswersToStorage(answers.value, showResults.value);
    notification.value = {
        type: 'success',
        message: 'Borrador guardado localmente'
    };
    setTimeout(clearNotification, 3000);
};

const completeEvaluation = async () => {
    isSubmitting.value = true;
    
    try {
        const visibleQuestionIds = new Set(
            categoriesWithQuestions.value
                .flatMap(category => category.questions)
                .map(question => question.id)
        );
        
        const visibleAnswers = Object.fromEntries(
            Object.entries(answers.value).filter(([questionId]) => 
                visibleQuestionIds.has(parseInt(questionId))
            )
        );
        
        
        const response = await axios.post('/evaluation/submit', {
            evaluation_id: evaluation.value.id,
            answers: visibleAnswers
        });
        
        if (response.data.success) {
            clearStoredAnswers();
            unsavedChanges.clear();
            
            evaluation.value = response.data.evaluation;
            evaluation.value.status = 'completed';
            evaluation.value.is_completed = true;
            evaluation.value.completed_at = new Date().toISOString();
            
            // Actualizar las multas activadas desde la respuesta del servidor
            triggeredMultas.value = response.data.triggered_multas || [];
            
            showResults.value = true;
            
            notification.value = {
                type: 'success',
                message: '¡Evaluación completada exitosamente! Refrescando página...'
            };
            
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        }
    } catch (error) {
        console.error('❌ Error completing evaluation:', error);
        notification.value = {
            type: 'error',
            message: 'Error al completar la evaluación. Las respuestas se mantienen guardadas localmente.'
        };
    } finally {
        isSubmitting.value = false;
    }
};

// Otros métodos
const clearNotification = () => {
    notification.value = { type: '', message: '' };
};

const handleRestart = async () => {
    try {
        await axios.post('/evaluation/restart', {
            evaluation_id: evaluation.value.id
        });
        
        answers.value = {};
        showResults.value = false;
        unsavedChanges.clear();
        clearStoredAnswers();
        
        notification.value = {
            type: 'success',
            message: 'Evaluación reiniciada correctamente.'
        };
    } catch (error) {
        console.error('Error al reiniciar evaluación:', error);
        notification.value = {
            type: 'error',
            message: 'Error al reiniciar la evaluación.'
        };
    }
};

const handleRequestConsultation = () => {
    router.visit('/contact', {
        data: {
            service: 'consultation',
            evaluation_completed: true
        }
    });
};

const handleBeforeUnload = (event: BeforeUnloadEvent) => {
    if (hasUnsavedChanges.value) {
        event.preventDefault();
        event.returnValue = 'Tienes respuestas sin enviar. ¿Estás seguro de que quieres salir?';
        return event.returnValue;
    }
};

const cleanupDeletedQuestions = () => {
    const validQuestionIds = new Set(
        categoriesWithQuestions.value
            .flatMap(category => category.questions)
            .map(question => question.id)
    );
    
    Object.keys(answers.value).forEach(questionId => {
        if (!validQuestionIds.has(parseInt(questionId))) {
            delete answers.value[parseInt(questionId)];
            console.warn(`Respuesta para pregunta eliminada ${questionId} removida`);
        }
    });
};

// Watchers
watch(notification, (newVal) => {
    if (newVal.message) {
        setTimeout(clearNotification, 5000);
    }
});

// Lifecycle hooks
onMounted(() => {
    cleanupDeletedQuestions();
    setupAutoSave(answers, showResults);
    window.addEventListener('beforeunload', handleBeforeUnload);
});

onUnmounted(() => {
    window.removeEventListener('beforeunload', handleBeforeUnload);
    
    if (hasUnsavedChanges.value) {
        saveAnswersToStorage(answers.value, showResults.value);
    }
});
</script>

<template>
    <div class="min-h-screen bg-[#FDFDFC] dark:bg-[#0a0a0a]">
        <Head title="Evaluación" />
        
        <TopBar />
        
        <!-- Indicador de cambios no guardados -->
        <div v-if="hasUnsavedChanges" 
             class="fixed top-16 right-4 z-40 bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-3 rounded shadow-lg">
            <div class="flex items-center">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-sm">Cambios guardados localmente</span>
                <button @click="saveDraft" class="ml-2 text-xs bg-yellow-500 text-white px-2 py-1 rounded hover:bg-yellow-600">
                    Guardar borrador
                </button>
            </div>
        </div>

        <!-- Mostrar resultados si la evaluación está completada -->
        <EvaluationResults
            v-if="showResults"
            :categories="categories"
            :category-scores="categoryScores"
            :total-questions="totalQuestions"
            :triggered-multas="triggeredMultas"
            @restart="handleRestart"
            @request-consultation="handleRequestConsultation"
        />

        <!-- Formulario de evaluación -->
        <div v-else class="container mx-auto px-4 py-8">
            <!-- Progreso general -->
            <div class="mb-8">
                <div class="flex items-center justify-between mb-2">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Evaluación</h1>
                    <span class="text-sm text-gray-600">{{ overallProgress }}% completado</span>
                </div>
                <div class="bg-gray-200 rounded-full h-2">
                    <div 
                        class="bg-blue-600 h-2 rounded-full transition-all duration-300"
                        :style="{ width: overallProgress + '%' }"
                    ></div>
                </div>
            </div>

            <!-- Navegación de categorías -->
            <div v-if="categoriesWithQuestions.length > 0" class="bg-[#FDFDFC] dark:bg-[#0a0a0a] rounded-lg shadow-sm border p-4 mb-8">
                <div class="flex flex-wrap gap-2 justify-center">
                    <button
                        v-for="(category, index) in categoriesWithQuestions"
                        :key="category.id"
                        @click="goToCategory(index)"
                        :class="[
                            'px-4 py-2 rounded-lg font-medium transition-all duration-200',
                            currentCategoryIndex === index
                                ? 'bg-blue-600 text-white shadow-md'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        ]"
                    >
                        {{ category.name }}
                        <span class="ml-2 text-xs opacity-75">
                            ({{ category.answeredCount }}/{{ category.questions.length }})
                        </span>
                    </button>
                </div>
            </div>

            <!-- Mensaje cuando no hay categorías ni preguntas -->
            <div v-if="categoriesWithQuestions.length === 0" class="text-center py-12">
                <div class="bg-gray-50 rounded-lg p-8 border border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Sin preguntas disponibles</h3>
                    <p class="text-gray-500">No hay categorías ni preguntas configuradas para esta evaluación en este momento.</p>
                </div>
            </div>

            <!-- Contenido de la categoría actual -->
            <div v-else-if="categoriesWithQuestions[currentCategoryIndex]" class="space-y-6">
                <Card class="p-6">
                    <div class="mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                            {{ categoriesWithQuestions[currentCategoryIndex].name }}
                        </h2>
                        <p v-if="categoriesWithQuestions[currentCategoryIndex].description" 
                           class="text-gray-600 mb-4">
                            {{ categoriesWithQuestions[currentCategoryIndex].description }}
                        </p>
                        
                        <!-- Progreso de la categoría -->
                        <div class="flex items-center gap-4">
                            <div class="flex-1">
                                <div class="bg-gray-200 rounded-full h-2">
                                    <div 
                                        class="bg-green-600 h-2 rounded-full transition-all duration-300"
                                        :style="{ width: currentCategoryProgress + '%' }"
                                    ></div>
                                </div>
                            </div>
                            <span class="text-sm text-gray-600 font-medium">
                                {{ currentCategoryProgress }}%
                            </span>
                        </div>
                    </div>

                    <!-- Preguntas de la categoría -->
                    <div class="space-y-6">
                        <div 
                            v-for="question in categoriesWithQuestions[currentCategoryIndex].questions" 
                            :key="question.id"
                            class="border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition-colors"
                        >
                            <QuestionInput
                                :question="question"
                                :model-value="answers[question.id]"
                                @update:model-value="(value) => saveAnswer(question.id, value)"
                            />
                        </div>
                    </div>
                </Card>

                <!-- Navegación entre categorías -->
                <div class="flex justify-between items-center">
                    <Button
                        v-if="currentCategoryIndex > 0"
                        @click="previousCategory"
                        variant="outline"
                        class="flex items-center gap-2"
                    >
                        ← Anterior
                    </Button>
                    <div v-else></div>

                    <div class="text-sm text-gray-600">
                        Categoría {{ currentCategoryIndex + 1 }} de {{ categoriesWithQuestions.length }}
                    </div>

                    <Button
                        v-if="currentCategoryIndex < categoriesWithQuestions.length - 1"
                        @click="nextCategory"
                        class="flex items-center gap-2"
                    >
                        Siguiente →
                    </Button>
                    <Button
                        v-else-if="allQuestionsAnswered"
                        @click="completeEvaluation"
                        :disabled="isSubmitting"
                        class="bg-green-600 hover:bg-green-700 flex items-center gap-2"
                    >
                        <span v-if="isSubmitting">Completando...</span>
                        <span v-else>Completar Evaluación ✓</span>
                    </Button>
                    <div v-else class="text-sm text-amber-600">
                        Complete todas las preguntas requeridas
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.container {
    max-width: 1200px;
}
</style>



