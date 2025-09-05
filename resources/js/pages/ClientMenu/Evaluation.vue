<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import TopBar from '@/components/MyComponents/TopBar.vue';
import Button from '@/components/ui/button/Button.vue';
import Card from '@/components/ui/card/Card.vue';
import RRHHSection from '@/components/MyComponents/Evaluacion/RRHHSection.vue';
import LegalSection from '@/components/MyComponents/Evaluacion/LegalSection.vue';
import { useEvaluationPersistence } from '@/composables/useEvaluationPersistence';

// Estado global de la evaluación
const answers = ref<Record<number, any>>({});
const showResults = ref(false);
const isSubmitting = ref(false);

// Composable de persistencia
const { loadAnswersFromStorage, clearStoredAnswers, setupAutoSave } = useEvaluationPersistence();

// Cargar datos guardados al inicializar
onMounted(() => {
    const stored = loadAnswersFromStorage();
    answers.value = stored.answers;
    showResults.value = stored.showResults;
    
    // Configurar guardado automático
    setupAutoSave(answers, showResults);
});

// IDs de preguntas por categoría para cálculos
const rrhhQuestionIds = [1, 2, 3, 4, 5, 6, 7, 15, 16];
const legalQuestionIds = [8, 9, 10, 11, 12, 13, 14];

// Computed para progreso total
const totalProgress = computed(() => {
    const totalQuestions = rrhhQuestionIds.length + legalQuestionIds.length;
    const answeredQuestions = [...rrhhQuestionIds, ...legalQuestionIds].filter(id => 
        answers.value[id] !== undefined && answers.value[id] !== ''
    ).length;
    return totalQuestions > 0 ? Math.round((answeredQuestions / totalQuestions) * 100) : 0;
});

// Computed para puntaje total
const totalScore = computed(() => {
    // Definición de puntos por pregunta
    const questionPoints: Record<number, number> = {
        // RRHH
        1: 25, 2: 15, 3: 5, 4: 20, 5: 8, 6: 12, 7: 6, 15: 10, 16: 8,
        // LEGAL
        8: 15, 9: 10, 10: 8, 11: 18, 12: 12, 13: 20, 14: 15
    };
    
    return Object.entries(answers.value).reduce((total, [questionId, answer]) => {
        const id = parseInt(questionId);
        const points = questionPoints[id] || 0;
        
        if (answer !== undefined && answer !== '') {
            // Para preguntas yes_no
            if ([1, 2, 3, 4, 6, 8, 9, 10, 11, 13, 14].includes(id)) {
                return total + (answer === true ? points : 0);
            }
            // Para checkboxes
            else if (id === 12) {
                return total + (Array.isArray(answer) && answer.length > 0 ? points : 0);
            }
            // Para otros tipos (text, textarea, select, number)
            else {
                return total + points;
            }
        }
        return total;
    }, 0);
});

// Computed para validación del formulario
const canSubmit = computed(() => {
    return totalProgress.value >= 10; // Al menos 80% completado
});

// Computed para color del progreso total
const totalProgressColor = computed(() => {
    if (totalProgress.value >= 80) return 'bg-green-500';
    if (totalProgress.value >= 50) return 'bg-yellow-500';
    return 'bg-red-500';
});

// Computed para mensaje de resultado total
const totalResultMessage = computed(() => {
    const percentage = totalProgress.value;
    if (percentage >= 90) return 'Excelente situación laboral general';
    if (percentage >= 70) return 'Buena situación laboral general';
    if (percentage >= 50) return 'Situación laboral regular';
    return 'Necesita mejorar significativamente su situación laboral';
});

// Función para manejar cambios en las respuestas
const handleAnswerChange = (questionId: number, value: any) => {
    // Los componentes hijos ya manejan la lógica de dependencias
    // Solo necesitamos actualizar el estado global
};

// Función para actualizar respuestas desde componentes hijos
const updateAnswers = (newAnswers: Record<number, any>) => {
    answers.value = newAnswers;
};

// Función para enviar evaluación
const submitEvaluation = async () => {
    if (!canSubmit.value) {
        alert('Por favor complete al menos el 80% de la evaluación antes de enviar.');
        return;
    }
    
    isSubmitting.value = true;
    
    try {
        // Simular envío al servidor
        await new Promise(resolve => setTimeout(resolve, 2000));
        
        showResults.value = true;
        
        // Limpiar localStorage después del envío exitoso
        clearStoredAnswers();
        
        alert('Evaluación enviada exitosamente');
    } catch (error) {
        console.error('Error al enviar evaluación:', error);
        alert('Error al enviar la evaluación. Por favor intente nuevamente.');
    } finally {
        isSubmitting.value = false;
    }
};

// Función para reiniciar evaluación
const restartEvaluation = () => {
    answers.value = {};
    showResults.value = false;
    isSubmitting.value = false;
    clearStoredAnswers();
};
// Computed para progreso de RRHH
const rrhhProgress = computed(() => {
    const rrhhQuestions = [1, 2, 3, 4, 5, 6, 7, 15, 16];
    const answeredRRHH = rrhhQuestions.filter(id => 
        answers.value[id] !== undefined && answers.value[id] !== ''
    ).length;
    return Math.round((answeredRRHH / rrhhQuestions.length) * 100);
});

// Computed para progreso de Legal
const legalProgress = computed(() => {
    const legalQuestions = [8, 9, 10, 11, 12, 13, 14];
    const answeredLegal = legalQuestions.filter(id => 
        answers.value[id] !== undefined && answers.value[id] !== ''
    ).length;
    return Math.round((answeredLegal / legalQuestions.length) * 100);
});

// Computed para total de preguntas respondidas
const totalAnsweredQuestions = computed(() => {
    return Object.keys(answers.value).filter(key => 
        answers.value[parseInt(key)] !== undefined && answers.value[parseInt(key)] !== ''
    ).length;
});
</script>

<template>
    <Head title="Evaluación Laboral" />
    
    <div class="min-h-screen bg-[#FDFDFC] text-[#1b1b18] dark:bg-[#0a0a0a] dark:text-[#EDEDEC]">
        <TopBar />
        
        <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <!-- Encabezado principal -->
            <div v-if="!showResults" class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                    Evaluación de Situación Laboral
                </h1>
                <p class="text-lg text-gray-600 dark:text-gray-300 mb-6">
                    Complete esta evaluación para conocer su situación laboral actual
                </p>
                
                <!-- Progreso total -->
                <Card class="p-6 mb-8">
                    <div class="mb-4">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Progreso General</h2>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 mb-4">
                            <div 
                                :class="totalProgressColor" 
                                class="h-3 rounded-full transition-all duration-300" 
                                :style="{ width: totalProgress + '%' }"
                            ></div>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            Progreso: {{ totalProgress }}% | Puntaje total: {{ totalScore }} puntos
                        </p>
                    </div>
                </Card>
            </div>
            
            <!-- Secciones modulares -->
            <div v-if="!showResults" class="space-y-8">
                <!-- Sección RRHH -->
                <RRHHSection 
                    :answers="answers"
                    @update:answers="updateAnswers"
                    @answer-change="handleAnswerChange"
                />
                
                <!-- Sección LEGAL -->
                <LegalSection 
                    :answers="answers"
                    @update:answers="updateAnswers"
                    @answer-change="handleAnswerChange"
                />
            </div>
            
            <!-- Botones de acción -->
            <div v-if="!showResults" class="flex justify-center space-x-4 mt-8">
                <Button 
                    @click="submitEvaluation"
                    :disabled="!canSubmit || isSubmitting"
                    class="px-8 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span v-if="isSubmitting">Enviando...</span>
                    <span v-else>Enviar Evaluación</span>
                </Button>
            </div>
            <!-- Resultados finales -->
            <div v-if="showResults" class="mt-8">
                <Card class="p-8 bg-gray-900 border-gray-700">
                    <div class="text-center mb-8">
                        <h2 class="text-2xl font-bold text-white mb-4">
                            Resultados de su Evaluación
                        </h2>
                        
                        <!-- Indicadores de estado -->
                        <div class="flex justify-center items-center space-x-8 mb-8">
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                <span class="text-sm text-gray-300">Requiere atención</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                <span class="text-sm text-gray-300">Mejorable</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                <span class="text-sm text-gray-300">Excelente</span>
                            </div>
                        </div>
                        
                        <!-- Gráficos circulares por categoría -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                            <!-- Recursos Humanos -->
                            <div class="text-center">
                                <h3 class="text-lg font-semibold text-blue-400 mb-4">Recursos Humanos</h3>
                                <div class="relative inline-flex items-center justify-center w-32 h-32 mb-4">
                                    <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 36 36">
                                        <!-- Círculo de fondo -->
                                        <path class="text-gray-700" stroke="currentColor" stroke-width="3" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                        <!-- Círculo de progreso RRHH -->
                                        <path 
                                            :class="{
                                                'text-green-500': rrhhProgress >= 80,
                                                'text-yellow-500': rrhhProgress >= 60 && rrhhProgress < 80,
                                                'text-red-500': rrhhProgress < 60
                                            }"
                                            stroke="currentColor" 
                                            stroke-width="3" 
                                            fill="none" 
                                            :stroke-dasharray="`${rrhhProgress}, 100`"
                                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                        />
                                    </svg>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="text-2xl font-bold text-white">{{ rrhhProgress }}%</span>
                                    </div>
                                </div>
                                <h4 class="font-semibold mb-2" :class="{
                                    'text-green-400': rrhhProgress >= 80,
                                    'text-yellow-400': rrhhProgress >= 60 && rrhhProgress < 80,
                                    'text-red-400': rrhhProgress < 60
                                }">
                                    {{ rrhhProgress >= 80 ? 'Excelente situación en RRHH' : rrhhProgress >= 60 ? 'Situación mejorable en RRHH' : 'Hay aspectos en RRHH que podrían mejorarse.' }}
                                </h4>
                                <p class="text-sm text-gray-400">
                                    {{ rrhhProgress >= 80 ? 'Su situación en RRHH está muy bien estructurada.' : 'Hay aspectos en RRHH que podrían mejorarse.' }}
                                </p>
                            </div>
                            
                            <!-- Legal -->
                            <div class="text-center">
                                <h3 class="text-lg font-semibold text-purple-400 mb-4">Legal</h3>
                                <div class="relative inline-flex items-center justify-center w-32 h-32 mb-4">
                                    <svg class="w-32 h-32 transform -rotate-90" viewBox="0 0 36 36">
                                        <!-- Círculo de fondo -->
                                        <path class="text-gray-700" stroke="currentColor" stroke-width="3" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                        <!-- Círculo de progreso Legal -->
                                        <path 
                                            :class="{
                                                'text-green-500': legalProgress >= 80,
                                                'text-yellow-500': legalProgress >= 60 && legalProgress < 80,
                                                'text-red-500': legalProgress < 60
                                            }"
                                            stroke="currentColor" 
                                            stroke-width="3" 
                                            fill="none" 
                                            :stroke-dasharray="`${legalProgress}, 100`"
                                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                        />
                                    </svg>
                                    <div class="absolute inset-0 flex items-center justify-center">
                                        <span class="text-2xl font-bold text-white">{{ legalProgress }}%</span>
                                    </div>
                                </div>
                                <h4 class="font-semibold mb-2" :class="{
                                    'text-green-400': legalProgress >= 80,
                                    'text-yellow-400': legalProgress >= 60 && legalProgress < 80,
                                    'text-red-400': legalProgress < 60
                                }">
                                    {{ legalProgress >= 80 ? 'Excelente situación en LEGAL' : legalProgress >= 60 ? 'Situación mejorable en LEGAL' : 'Su situación en LEGAL está muy bien estructurada.' }}
                                </h4>
                                <p class="text-sm text-gray-400">
                                    {{ legalProgress >= 80 ? 'Su situación en LEGAL está muy bien estructurada.' : 'Hay aspectos legales que podrían mejorarse.' }}
                                </p>
                            </div>
                        </div>
                        
                        <!-- Estadísticas resumidas -->
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
                            <div class="bg-gray-800 rounded-lg p-4">
                                <div class="text-2xl font-bold text-white mb-1">{{ totalAnsweredQuestions }}</div>
                                <div class="text-sm text-gray-400">Preguntas respondidas</div>
                            </div>
                            <div class="bg-gray-800 rounded-lg p-4">
                                <div class="text-2xl font-bold text-blue-400 mb-1">{{ rrhhProgress }}%</div>
                                <div class="text-sm text-gray-400">Puntaje RRHH</div>
                            </div>
                            <div class="bg-gray-800 rounded-lg p-4">
                                <div class="text-2xl font-bold text-purple-400 mb-1">{{ legalProgress }}%</div>
                                <div class="text-sm text-gray-400">Puntaje Legal</div>
                            </div>
                        </div>
                        
                        <!-- Botones de acción -->
                        <div class="flex flex-col sm:flex-row gap-4 justify-center">
                            <Button 
                                @click="restartEvaluation"
                                class="px-8 py-3 bg-gray-700 text-white rounded-lg hover:bg-gray-600 border border-gray-600"
                            >
                                Realizar nueva evaluación
                            </Button>
                            <Button 
                                class="px-8 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
                            >
                                Solicitar consulta profesional
                            </Button>
                        </div>
                        
                        <!-- Disclaimer -->
                        <div class="mt-8 text-center">
                            <p class="text-sm text-gray-400 mb-2">
                                Esta evaluación es solo orientativa y no constituye asesoría legal profesional.
                            </p>
                            <p class="text-sm text-gray-400">
                                Para obtener asesoría personalizada, contacte con nuestros especialistas.
                            </p>
                        </div>
                    </div>
                </Card>
            </div>
        </div>
    </div>
</template>