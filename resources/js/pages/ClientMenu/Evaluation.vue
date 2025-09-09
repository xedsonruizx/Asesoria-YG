<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
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
    question_type: 'text' | 'textarea' | 'select' | 'number' | 'checkbox' | 'yes_no';
    options?: string[];
    placeholder?: string;
    min_value?: number;
    max_value?: number;
    points: number;
    show_condition?: {
        questionId: number;
        answer: any;
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

// Estado reactivo
const evaluation = ref<Evaluation>(props.evaluation);
const answers = ref<Record<number, any>>(props.answers || {});
const showResults = ref<boolean>(props.showResults || props.evaluation?.is_completed || false);
const isSubmitting = ref<boolean>(false);
const notification = ref<{ type: string; message: string }>({ type: '', message: '' });

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

// Computed para puntajes por categoría
const categoryScores = computed(() => {
    // Si tenemos categoryScores desde el backend (resultados completados), usarlos
    if (props.categoryScores && Object.keys(props.categoryScores).length > 0) {
        return props.categoryScores;
    }
    
    // Si no, calcular basándose en las preguntas visibles (evaluación en progreso)
    const scores: Record<string, CategoryScore> = {};
    
    visibleQuestionsByCategory.value.forEach(category => {
        const categoryQuestions = category.questions;
        const maxScore = categoryQuestions.reduce((sum, q) => sum + q.points, 0);
        const currentScore = categoryQuestions.reduce((sum, q) => {
            const answer = answers.value[q.id];
            if (answer !== undefined && answer !== '' && answer !== null) {
                if (q.question_type === 'yes_no') {
                    return sum + (answer === true ? q.points : 0);
                } else if (q.question_type === 'checkbox' && Array.isArray(answer)) {
                    return sum + (answer.length > 0 ? q.points : 0);
                } else {
                    return sum + q.points;
                }
            }
            return sum;
        }, 0);
        
        const answeredQuestions = categoryQuestions.filter(q => {
            const answer = answers.value[q.id];
            return answer !== undefined && answer !== '' && answer !== null;
        }).length;
        
        const progress = categoryQuestions.length > 0 ? Math.round((answeredQuestions / categoryQuestions.length) * 100) : 0;
        
        scores[category.name] = {
            category: category.name,
            score: currentScore,
            maxScore,
            progress
        };
    });
    
    return scores;
});
const totalScore = computed(() => {
    return Object.values(categoryScores.value).reduce((sum, cat) => sum + cat.score, 0);
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

// Modificar la función submitEvaluation para no redirigir
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
            
            // Mostrar resultados
            showResults.value = true;
            showNotification('success', '¡Evaluación completada exitosamente!');
        }
    } catch (error) {
        console.error('Error al enviar evaluación:', error);
        showNotification('error', 'Error al enviar la evaluación. Inténtelo nuevamente.');
    } finally {
        isSubmitting.value = false;
    }
};

// Función para manejar el reinicio de la evaluación
const handleRestart = async () => {
    try {
        await axios.post('/evaluation/restart', {
            evaluation_id: evaluation.value.id
        });
        
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

const getProgressColor = (progress: number) => {
    if (progress >= 80) return 'text-green-600';
    if (progress >= 50) return 'text-yellow-600';
    return 'text-red-600';
};

const getScoreMessage = (score: number, category: string) => {
    const categoryData = categoryScores.value[category];
    if (!categoryData) return '';
    
    const percentage = categoryData.progress;
    
    if (percentage >= 80) return `Excelente conocimiento en ${category}`;
    if (percentage >= 60) return `Buen conocimiento en ${category}`;
    if (percentage >= 40) return `Conocimiento regular en ${category}`;
    return `Necesita mejorar en ${category}`;
};

const getCircularProgress = (progress: number) => {
    const circumference = 2 * Math.PI * 45;
    const strokeDasharray = circumference;
    const strokeDashoffset = circumference - (progress / 100) * circumference;
    return { strokeDasharray, strokeDashoffset };
};

// Función para manejar la solicitud de consulta
const handleRequestConsultation = () => {
    // Aquí puedes agregar la lógica para redirigir a la página de contacto
    // o abrir un modal de contacto
    console.log('Solicitar consulta profesional');
}



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
        :rh-score="Math.round((categoryScores['Recursos Humanos']?.progress || 0))"
        :legal-score="Math.round((categoryScores['Legal']?.progress || 0))"
        :total-questions="Object.values(answers).length"
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
                            v-for="question in category.questions" 
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