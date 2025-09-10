<script setup lang="ts">
import { ref, computed, onMounted, watch, onBeforeUnmount } from 'vue';
import { Head } from '@inertiajs/vue3';
import Button from '@/components/ui/button/Button.vue';
import Card from '@/components/ui/card/Card.vue';
import TopBar from '@/components/MyComponents/TopBar.vue';
import QuestionInput from '@/components/QuestionInput.vue';
import EvaluationResults from '@/components/EvaluationResults.vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

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
        expected_value: any;
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

interface Evaluation {
    id: number;
    user_id: number;
    total_score: number;
    total_progress: number;
    is_completed: boolean;
    completed_at?: string;
    category_scores: Record<string, CategoryScore>;
}

interface Props {
    evaluation: Evaluation;
    questions: Question[];
    answers: Record<number, any>;
    categories: Category[];
    showResults?: boolean;
    report?: any;
    categoryScores?: Record<string, CategoryScore>; // Agregar esta prop
}

const props = defineProps<Props>();

// Función para inicializar respuestas con valores por defecto
const initializeAnswersWithDefaults = () => {
    const initialAnswers = { ...props.answers };
    
    props.questions.forEach(question => {
        if (question.question_type === 'select' && question.is_active) {
            if (initialAnswers[question.id] === undefined || initialAnswers[question.id] === null) {
                initialAnswers[question.id] = '';
            }
        }
    });
    
    return initialAnswers;
};

// Estado reactivo - Priorizar datos del backend
const evaluation = ref<Evaluation>(props.evaluation);
const answers = ref<Record<number, any>>(props.answers || {});
const showResults = ref<boolean>(props.showResults || props.evaluation?.is_completed || false);
const isSubmitting = ref<boolean>(false);
const notification = ref<{ type: string; message: string }>({ type: '', message: '' });

const STORAGE_KEY = `evaluation_${props.evaluation.id}_answers`;

// Función para guardar respuestas en localStorage
const saveAnswersToStorage = () => {
    try {
        const dataToSave = {
            answers: answers.value,
            showResults: showResults.value,
            timestamp: Date.now()
        };
        localStorage.setItem(STORAGE_KEY, JSON.stringify(dataToSave));
    } catch (error) {
        console.error('Error al guardar respuestas:', error);
    }
};

// Función para cargar respuestas desde localStorage
const loadAnswersFromStorage = () => {
    // PRIORIDAD 1: Si la evaluación está completada, SIEMPRE usar datos del backend
    if (props.evaluation?.status === 'completed' || props.showResults) {
        answers.value = props.answers || {};
        showResults.value = true;
        
        // Limpiar localStorage si existe (ya no es necesario)
        if (localStorage.getItem(STORAGE_KEY)) {
            localStorage.removeItem(STORAGE_KEY);
        }
        
        return;
    }
    
    // PRIORIDAD 2: Solo usar localStorage para evaluaciones en progreso
    try {
        const savedData = localStorage.getItem(STORAGE_KEY);
        
        if (savedData) {
            const parsedData = JSON.parse(savedData);
            
            // Verificar si los datos no son muy antiguos (24 horas)
            const twentyFourHours = 24 * 60 * 60 * 1000;
            if (parsedData.timestamp && Date.now() - parsedData.timestamp > twentyFourHours) {
                localStorage.removeItem(STORAGE_KEY);
                answers.value = initializeAnswersWithDefaults();
                return;
            }
            
            // Combinar respuestas guardadas con las del backend
            if (parsedData.answers) {
                const backendAnswers = props.answers || {};
                const localStorageAnswers = parsedData.answers;
                
                // Backend tiene prioridad sobre localStorage
                answers.value = { ...localStorageAnswers, ...backendAnswers };
            } else {
                answers.value = initializeAnswersWithDefaults();
            }
        } else {
            answers.value = initializeAnswersWithDefaults();
        }
    } catch (error) {
        console.error('Error al cargar desde localStorage:', error);
        answers.value = initializeAnswersWithDefaults();
    }
};

// Función para limpiar localStorage cuando se completa la evaluación
const clearStoredAnswers = () => {
    try {
        localStorage.removeItem(STORAGE_KEY);
    } catch (error) {
        console.error('Error al limpiar localStorage:', error);
    }
};

// Función para guardar automáticamente cada cierto tiempo
let autoSaveTimeout: NodeJS.Timeout | null = null;
const scheduleAutoSave = () => {
    if (autoSaveTimeout) {
        clearTimeout(autoSaveTimeout);
    }
    autoSaveTimeout = setTimeout(() => {
        saveAnswersToStorage();
    }, 2000); // Guardar después de 2 segundos de inactividad
};

// Watcher para guardar automáticamente cuando cambien las respuestas o showResults
watch([answers, showResults], () => {
    // Solo guardar en localStorage si la evaluación NO está completada
    if (!props.evaluation?.is_completed && !showResults.value && Object.keys(answers.value).length > 0) {
        scheduleAutoSave();
    }
}, { deep: true });

// Agregar la computed property faltante para agrupar preguntas por categoría
const visibleQuestionsByCategory = computed(() => {
    const categoriesWithQuestions = props.categories.map(category => {
        // Filtrar preguntas de esta categoría
        const categoryQuestions = props.questions.filter(q => 
            q.category_id === category.id && q.is_active
        );
        
        // Filtrar preguntas visibles basándose en show_condition
        const visibleQuestions = categoryQuestions.filter(question => {
            if (!question.show_condition) return true;
            
            const conditionAnswer = answers.value[question.show_condition.questionId];
            return conditionAnswer === question.show_condition.answer;
        });
        
        return {
            ...category,
            questions: visibleQuestions.sort((a, b) => a.order - b.order)
        };
    });
    
    // Solo devolver categorías que tienen preguntas visibles
    return categoriesWithQuestions.filter(category => category.questions.length > 0);
});

// Función para obtener los puntos de una respuesta
const getAnswerPoints = (question: Question, answer: any): number => {
    if (!answer || answer === '') return 0;
    
    // Para tipos con puntos individuales por opción
    if (['select', 'radio', 'checkbox'].includes(question.question_type)) {
        if (!question.options) return 0;
        
        let options = question.options;
        
        // Si las opciones vienen como string JSON, parsearlas
        if (typeof options === 'string') {
            try {
                options = JSON.parse(options);
            } catch (e) {
                console.error('Error parsing options JSON in getAnswerPoints:', e);
                // Si falla el parsing, usar puntos base de la pregunta
                return question.points;
            }
        }
        
        // Si las opciones tienen el nuevo formato con puntos
        if (Array.isArray(options) && 
            options.length > 0 && 
            typeof options[0] === 'object' && 
            'points' in options[0]) {
            
            const optionsWithPoints = options as Array<{text: string, points: number}>;
            
            if (question.question_type === 'checkbox' && Array.isArray(answer)) {
                // Para checkboxes, sumar puntos de todas las opciones seleccionadas
                return answer.reduce((sum, selectedOption) => {
                    const option = optionsWithPoints.find(opt => opt.text === selectedOption);
                    return sum + (option?.points || 0);
                }, 0);
            } else {
                // Para select y radio, obtener puntos de la opción seleccionada
                const option = optionsWithPoints.find(opt => opt.text === answer);
                return option?.points || 0;
            }
        }
        
        // Si es formato antiguo (array de strings), usar puntos base
        if (Array.isArray(options)) {
            return question.points;
        }
    }
    
    // Para otros tipos o formato antiguo, usar los puntos base de la pregunta
    return question.points;
};

// Computed para puntajes por categoría
const categoryScores = computed(() => {
    // Si tenemos categoryScores desde el backend (resultados completados), convertirlos al formato esperado
    if (props.categoryScores && Object.keys(props.categoryScores).length > 0) {
        const convertedScores: Record<string, CategoryScore> = {};
        
        // Crear mapeo dinámico desde las categorías de la base de datos
        const categoryMap: Record<string, string> = {};
        props.categories.forEach(category => {
            categoryMap[category.slug] = category.name;
        });
        
        Object.entries(props.categoryScores).forEach(([slug, data]) => {
            const categoryName = categoryMap[slug] || slug;
            convertedScores[categoryName] = {
                category: categoryName,
                score: data.score || 0,
                maxScore: data.maxScore || data.max_score || 1,
                progress: data.progress || 0,
                percentage: data.percentage || 0,
                obtainedPoints: data.obtainedPoints || 0,
                totalPossiblePoints: data.totalPossiblePoints || 0,
                answeredQuestions: data.answeredQuestions || 0
            };
        });
        
        return convertedScores;
    }
    
    // Si no, calcular basándose en las preguntas visibles (evaluación en progreso)
    const scores: Record<string, CategoryScore> = {};
    
    visibleQuestionsByCategory.value.forEach(category => {
        const categoryQuestions = category.questions;
        
        // Calcular puntaje máximo considerando el nuevo formato
        const maxScore = categoryQuestions.reduce((sum, q) => {
            if (['select', 'radio', 'checkbox'].includes(q.question_type) && q.options) {
                // Si tiene opciones con puntos individuales
                if (Array.isArray(q.options) && 
                    q.options.length > 0 && 
                    typeof q.options[0] === 'object' && 
                    'points' in q.options[0]) {
                    
                    const optionsWithPoints = q.options as Array<{text: string, points: number}>;
                    
                    if (q.question_type === 'checkbox') {
                        // Para checkboxes, el máximo es la suma de todos los puntos
                        return sum + optionsWithPoints.reduce((optSum, opt) => optSum + opt.points, 0);
                    } else {
                        // Para select y radio, el máximo es el mayor puntaje disponible
                        return sum + Math.max(...optionsWithPoints.map(opt => opt.points));
                    }
                }
            }
            return sum + q.points;
        }, 0);
        
        // Calcular puntaje actual
        const currentScore = categoryQuestions.reduce((sum, q) => {
            const answer = answers.value[q.id];
            return sum + getAnswerPoints(q, answer);
        }, 0);
        
        const answeredQuestions = categoryQuestions.filter(q => {
            const answer = answers.value[q.id];
            return answer !== undefined && answer !== '' && answer !== null;
        }).length;
        
        const progress = categoryQuestions.length > 0 ? Math.round((answeredQuestions / categoryQuestions.length) * 100) : 0;
        const percentage = maxScore > 0 ? Math.round((currentScore / maxScore) * 100) : 0;
        
        scores[category.name] = {
            category: category.name,
            score: currentScore,
            maxScore,
            progress,
            percentage,
            obtainedPoints: currentScore,
            totalPossiblePoints: maxScore,
            answeredQuestions
        };
    });
    
    return scores;
});

const totalScore = computed(() => {
    return Object.values(categoryScores.value).reduce((sum, cat) => sum + cat.score, 0);
});

const totalPossibleScore = computed(() => {
    return Object.values(categoryScores.value).reduce((sum, cat) => sum + cat.maxScore, 0);
});

const totalAnsweredQuestions = computed(() => {
    return Object.values(categoryScores.value).reduce((sum, cat) => sum + (cat.answeredQuestions || 0), 0);
});

const overallPercentage = computed(() => {
    return totalPossibleScore.value > 0 ? Math.round((totalScore.value / totalPossibleScore.value) * 100) : 0;
});

const isValidForSubmission = computed(() => {
    return Object.values(categoryScores.value).every(cat => cat.progress >= 20);
});

// Funciones
const showNotification = (type: string, message: string) => {
    notification.value = { type, message };
    setTimeout(() => {
        notification.value = { type: '', message: '' };
    }, 3000);
};

// Modificar la función submitEvaluation
const submitEvaluation = async () => {
    if (!isValidForSubmission.value) {
        showNotification('error', 'Debe completar al menos el 80% de cada sección para enviar la evaluación.');
        return;
    }
    
    isSubmitting.value = true;
    
    try {
        // Enviar solo las respuestas, el controlador obtendrá la evaluación del usuario autenticado
        const response = await axios.post('/evaluation/submit', {
            answers: answers.value
        });
        
        if (response.data.success) {
            // Actualizar el estado local con los datos del servidor
            evaluation.value = response.data.evaluation;
            
            // Limpiar localStorage al completar exitosamente
            clearStoredAnswers();
            
            // Mostrar resultados
            showResults.value = true;
            showNotification('success', '¡Evaluación completada exitosamente!');
        }
    } catch (error) {
        console.error('Error al enviar evaluación:', error);
        showNotification('error', 'Error al enviar la evaluación. Las respuestas se han guardado automáticamente.');
    } finally {
        isSubmitting.value = false;
    }
};

// Modificar la función handleRestart
const handleRestart = async () => {
    try {
        await axios.post('/evaluation/restart', {
            evaluation_id: evaluation.value.id
        });
        
        // Limpiar localStorage al reiniciar
        clearStoredAnswers();
        
        // Resetear estado local
        answers.value = {};
        showResults.value = false;
        evaluation.value.is_completed = false;
        evaluation.value.completed_at = undefined;
        
        showNotification('success', 'Evaluación reiniciada correctamente. Refrescando página...');
        
        // Refrescar la página después de 2 segundos
        setTimeout(() => {
            window.location.reload();
        }, 2000);
    } catch (error) {
        console.log(error);
        showNotification('error', 'Error al reiniciar la evaluación.');
    }
};

// Función para manejar el evento beforeunload (cuando el usuario cierra la página)
const handleBeforeUnload = () => {
    if (!showResults.value && Object.keys(answers.value).length > 0) {
        saveAnswersToStorage();
    }
};

// Inicialización
onMounted(() => {
    // Cargar respuestas (prioridad backend)
    loadAnswersFromStorage();
    
    // Logs iniciales al cargar la página
    console.log('🔄 PÁGINA REFRESCADA - Datos iniciales:');
    console.log('📊 Evaluación:', evaluation.value);
    console.log('📝 Respuestas cargadas:', answers.value);
    console.log('🎯 Puntajes por categoría:', categoryScores.value);
    console.log('📈 Puntaje total:', totalScore.value, '/', totalPossibleScore.value);
    console.log('📊 Porcentaje general:', overallPercentage.value + '%');
    console.log('✅ Preguntas respondidas:', correctlyAnsweredQuestions.value);
    console.log('🏁 Mostrar resultados:', showResults.value);
    console.log('📋 Props recibidas:', {
        evaluation: props.evaluation,
        categoryScores: props.categoryScores,
        showResults: props.showResults,
        totalQuestions: props.questions.length
    });
    
    // Agregar listener para guardar antes de cerrar la página
    window.addEventListener('beforeunload', handleBeforeUnload);
});

// Limpiar listeners al desmontar
onBeforeUnmount(() => {
    if (autoSaveTimeout) {
        clearTimeout(autoSaveTimeout);
    }
    window.removeEventListener('beforeunload', handleBeforeUnload);
});

// Calcular preguntas respondidas correctamente
const correctlyAnsweredQuestions = computed(() => {
    const answeredCount = Object.entries(answers.value).filter(([questionId, answer]) => {
        // Encontrar la pregunta
        const question = visibleQuestionsByCategory.value
            .flatMap(cat => cat.questions)
            .find(q => q.id.toString() === questionId);
        
        if (!question || !answer || answer === '') return false;
        
        // Para preguntas con opciones, verificar si la respuesta es válida
        if (['select', 'radio', 'checkbox'].includes(question.question_type)) {
            if (question.question_type === 'checkbox') {
                return Array.isArray(answer) && answer.length > 0;
            }
            return answer !== '';
        }
        
        // Para otros tipos de preguntas
        return answer !== undefined && answer !== null && answer !== '';
    }).length;
    
    // Log de preguntas respondidas
    console.log('📊 Preguntas respondidas:', answeredCount);
    console.log('📝 Respuestas actuales:', answers.value);
    
    return answeredCount;
});

// Función para manejar solicitud de consultoría
const handleRequestConsultation = () => {
    // Redirigir a la página de contacto o abrir modal de consultoría
    router.visit('/contact', {
        data: {
            service: 'consultation',
            evaluation_completed: true
        }
    });
};

// Watcher para logs de puntajes y porcentajes
watch([categoryScores, totalScore, overallPercentage], ([newCategoryScores, newTotalScore, newPercentage]) => {
    console.log('🎯 Puntajes por categoría:', newCategoryScores);
    console.log('📈 Puntaje total:', newTotalScore, '/', totalPossibleScore.value);
    console.log('📊 Porcentaje general:', newPercentage + '%');
    console.log('✅ Preguntas respondidas totales:', totalAnsweredQuestions.value);
}, { deep: true });

// Función para obtener el color del progreso
const getProgressColor = (progress: number): string => {
    if (progress >= 80) return 'text-green-600';
    if (progress >= 60) return 'text-yellow-600';
    if (progress >= 40) return 'text-orange-600';
    return 'text-red-600';
};

// Función para obtener el mensaje del puntaje
const getScoreMessage = (score: number, categoryName: string): string => {
    if (score >= 70) return `Excelente situación en ${categoryName}`;
    if (score >= 50) return `Situación mejorable en ${categoryName}`;
    return `Requiere atención en ${categoryName}`;
};

// Función para verificar si una pregunta debe mostrarse
const shouldShowQuestion = (question: Question): boolean => {
    if (!question.show_condition) return true;
    
    const conditionAnswer = answers.value[question.show_condition.parent_question_id];
    const expectedValue = question.show_condition.expected_value;
    
    switch (question.show_condition.operator) {
        case 'equals':
            return conditionAnswer === expectedValue;
        case 'not_equals':
            return conditionAnswer !== expectedValue;
        case 'contains':
            return Array.isArray(conditionAnswer) && conditionAnswer.includes(expectedValue);
        case 'not_contains':
            return !Array.isArray(conditionAnswer) || !conditionAnswer.includes(expectedValue);
        default:
            return true;
    }
};
</script>

<template>
    <Head title="Evaluación Laboral" />
    
    <TopBar />
    
    <!-- Notificación nativa -->
    <div 
        v-if="notification.type" 
        class="fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300"
        :class="{
            'bg-green-500 text-white': notification.type === 'success',
            'bg-red-500 text-white': notification.type === 'error'
        }"
    >
        {{ notification.message }}
    </div>
    
    <!-- Mostrar resultados si la evaluación está completada -->
    <EvaluationResults
        v-if="showResults"
        :categories="props.categories"
        :category-scores="categoryScores"
        :total-questions="correctlyAnsweredQuestions"
        @restart="handleRestart"
        @request-consultation="handleRequestConsultation"
    />
    
    <!-- Formulario de evaluación si no está completada -->
    <div v-else class="min-h-screen bg-gray-50 py-8 dark:bg-gray-900">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Progreso general -->
            <Card class="mb-8 p-6">
                <div class="text-center">
                    <h1 class="text-3xl font-bold text-gray-900 mb-4 dark:text-white">Evaluación Laboral</h1>
                    <div class="w-full bg-gray-200 rounded-full h-4 mb-4 dark:bg-gray-700">
                        <div 
                            class="bg-blue-600 h-4 rounded-full transition-all duration-300" 
                            :style="{ width: progress + '%' }"
                        ></div>
                    </div>
                    <p class="text-lg font-medium" :class="getProgressColor(progress)">
                        Progreso: {{ progress }}%
                    </p>
                </div>
            </Card>

            <!-- Secciones de preguntas dinámicas -->
            <div class="space-y-8">
                <Card v-for="category in visibleQuestionsByCategory" :key="category.id" class="p-6">
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 mb-2 dark:text-white">{{ category.name }}</h2>
                        <div class="w-full bg-gray-200 rounded-full h-2.5 mb-4">
                            <div 
                                class="h-2.5 rounded-full transition-all duration-300" 
                                :class="{
                                    'bg-green-500': categoryScores[category.name]?.progress >= 80,
                                    'bg-yellow-500': categoryScores[category.name]?.progress >= 60 && categoryScores[category.name]?.progress < 80,
                                    'bg-orange-500': categoryScores[category.name]?.progress >= 40 && categoryScores[category.name]?.progress < 60,
                                    'bg-red-500': categoryScores[category.name]?.progress < 40
                                }"
                                :style="{ width: (categoryScores[category.name]?.progress || 0) + '%' }"
                            ></div>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Progreso: {{ categoryScores[category.name]?.progress || 0 }}% 
                            ({{ categoryScores[category.name]?.score || 0 }} puntos)
                            <span v-if="category.questions.length > 0" class="ml-2">
                                - {{ category.questions.filter(q => answers[q.id] !== undefined && answers[q.id] !== '').length }} de {{ category.questions.length }} preguntas respondidas
                            </span>
                        </p>
                    </div>

                    <div class="space-y-6">
                        <QuestionInput
                            v-for="question in category.questions.filter(q => shouldShowQuestion(q))" 
                            :key="question.id"
                            :question="question"
                            v-model="answers[question.id]"
                        />
                    </div>

                    <!-- Resultado de la sección -->
                    <div v-if="(categoryScores[category.name]?.progress || 0) > 0" class="mt-6 p-4 rounded-lg" :class="{
                        'bg-green-50 border border-green-200': (categoryScores[category.name]?.progress || 0) >= 80,
                        'bg-yellow-50 border border-yellow-200': (categoryScores[category.name]?.progress || 0) >= 60 && (categoryScores[category.name]?.progress || 0) < 80,
                        'bg-orange-50 border border-orange-200': (categoryScores[category.name]?.progress || 0) >= 40 && (categoryScores[category.name]?.progress || 0) < 60,
                        'bg-red-50 border border-red-200': (categoryScores[category.name]?.progress || 0) < 40
                    }">
                        <h3 class="font-semibold" :class="{
                            'text-green-800': (categoryScores[category.name]?.progress || 0) >= 80,
                            'text-yellow-800': (categoryScores[category.name]?.progress || 0) >= 60 && (categoryScores[category.name]?.progress || 0) < 80,
                            'text-orange-800': (categoryScores[category.name]?.progress || 0) >= 40 && (categoryScores[category.name]?.progress || 0) < 60,
                            'text-red-800': (categoryScores[category.name]?.progress || 0) < 40
                        }">
                            {{ getScoreMessage(categoryScores[category.name]?.score || 0, category.name) }}
                        </h3>
                        <p class="text-sm mt-1" :class="{
                            'text-green-600': (categoryScores[category.name]?.progress || 0) >= 80,
                            'text-yellow-600': (categoryScores[category.name]?.progress || 0) >= 60 && (categoryScores[category.name]?.progress || 0) < 80,
                            'text-orange-600': (categoryScores[category.name]?.progress || 0) >= 40 && (categoryScores[category.name]?.progress || 0) < 60,
                            'text-red-600': (categoryScores[category.name]?.progress || 0) < 40
                        }">
                            Puntaje obtenido: {{ categoryScores[category.name]?.score || 0 }} puntos
                        </p>
                    </div>
                </Card>

                <!-- Botones de acción -->
                <div class="flex justify-center space-x-4">
                    <Button 
                        @click="submitEvaluation"
                        :disabled="!isValidForSubmission || isSubmitting"
                        class="px-8 py-3"
                    >
                        {{ isSubmitting ? 'Enviando...' : 'Completar Evaluación' }}
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>