<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import Card from '@/components/ui/card/Card.vue';

interface Question {
    id: number;
    text: string;
    category: string;
    type: 'text' | 'textarea' | 'select' | 'number' | 'checkbox' | 'yes_no';
    options?: string[];
    placeholder?: string;
    min?: number;
    max?: number;
    points: number;
    showCondition?: { questionId: number; answer: any };
    nextQuestionYes?: number;
    nextQuestionNo?: number;
}

interface Props {
    answers: Record<number, any>;
}

interface Emits {
    (e: 'update:answers', value: Record<number, any>): void;
    (e: 'answer-change', questionId: number, value: any): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

// Preguntas específicas de LEGAL
const legalQuestions: Question[] = [
    {
        id: 8,
        text: "¿Conoce sus derechos laborales básicos?",
        category: "LEGAL",
        type: "yes_no",
        points: 15,
        nextQuestionYes: 9,
        nextQuestionNo: 10
    },
    {
        id: 9,
        text: "¿Ha tenido que hacer valer sus derechos ante su empleador?",
        category: "LEGAL",
        type: "yes_no",
        points: 10,
        showCondition: { questionId: 8, answer: true },
        nextQuestionYes: 11,
        nextQuestionNo: 12
    },
    {
        id: 10,
        text: "¿Considera que necesita asesoría sobre sus derechos laborales?",
        category: "LEGAL",
        type: "yes_no",
        points: 8,
        showCondition: { questionId: 8, answer: false },
        nextQuestionYes: 13,
        nextQuestionNo: 14
    },
    {
        id: 11,
        text: "¿Obtuvo una respuesta satisfactoria de su empleador?",
        category: "LEGAL",
        type: "yes_no",
        points: 18,
        showCondition: { questionId: 9, answer: true }
    },
    {
        id: 12,
        text: "Seleccione los temas legales que le interesan (puede marcar varios):",
        category: "LEGAL",
        type: "checkbox",
        points: 12,
        options: ["Despido injustificado", "Acoso laboral", "Horas extras", "Seguridad social", "Discriminación"],
        showCondition: { questionId: 9, answer: false }
    },
    {
        id: 13,
        text: "¿Está afiliado al seguro social?",
        category: "LEGAL",
        type: "yes_no",
        points: 20,
        showCondition: { questionId: 10, answer: true }
    },
    {
        id: 14,
        text: "¿Su empleador cumple con las normativas laborales básicas?",
        category: "LEGAL",
        type: "yes_no",
        points: 15,
        showCondition: { questionId: 10, answer: false }
    }
];

// Computed para preguntas visibles
const visibleQuestions = computed(() => {
    return legalQuestions.filter(question => {
        if (!question.showCondition) return true;
        const conditionAnswer = props.answers[question.showCondition.questionId];
        return conditionAnswer === question.showCondition.answer;
    });
});

// Computed para progreso
const progress = computed(() => {
    const totalQuestions = visibleQuestions.value.length;
    const answeredQuestions = visibleQuestions.value.filter(q => 
        props.answers[q.id] !== undefined && props.answers[q.id] !== ''
    ).length;
    return totalQuestions > 0 ? Math.round((answeredQuestions / totalQuestions) * 100) : 0;
});

// Computed para puntaje
const score = computed(() => {
    return visibleQuestions.value.reduce((total, question) => {
        const answer = props.answers[question.id];
        if (answer !== undefined && answer !== '') {
            if (question.type === 'yes_no') {
                return total + (answer === true ? question.points : 0);
            } else if (question.type === 'checkbox') {
                return total + (Array.isArray(answer) && answer.length > 0 ? question.points : 0);
            } else {
                return total + question.points;
            }
        }
        return total;
    }, 0);
});

// Función para manejar cambios en las respuestas
const handleAnswerChange = (questionId: number, value: any) => {
    const newAnswers = { ...props.answers };
    newAnswers[questionId] = value;
    
    // Limpiar respuestas dependientes
    clearDependentAnswers(questionId, newAnswers);
    
    emit('update:answers', newAnswers);
    emit('answer-change', questionId, value);
};

// Función para manejar checkboxes
const handleCheckboxChange = (questionId: number, option: string, checked: boolean) => {
    const currentAnswers = Array.isArray(props.answers[questionId]) ? [...props.answers[questionId]] : [];
    
    if (checked) {
        if (!currentAnswers.includes(option)) {
            currentAnswers.push(option);
        }
    } else {
        const index = currentAnswers.indexOf(option);
        if (index > -1) {
            currentAnswers.splice(index, 1);
        }
    }
    
    handleAnswerChange(questionId, currentAnswers);
};

// Función para limpiar respuestas dependientes
const clearDependentAnswers = (changedQuestionId: number, answers: Record<number, any>) => {
    legalQuestions.forEach(question => {
        if (question.showCondition?.questionId === changedQuestionId) {
            delete answers[question.id];
            clearDependentAnswers(question.id, answers);
        }
    });
};

// Computed para color del progreso
const progressColor = computed(() => {
    if (progress.value >= 80) return 'bg-green-500';
    if (progress.value >= 50) return 'bg-yellow-500';
    return 'bg-red-500';
});

// Computed para mensaje de resultado
const resultMessage = computed(() => {
    const percentage = progress.value;
    if (percentage >= 80) return 'Excelente conocimiento legal laboral';
    if (percentage >= 60) return 'Buen conocimiento legal laboral';
    if (percentage >= 40) return 'Conocimiento legal laboral regular';
    return 'Necesita mejorar su conocimiento legal laboral';
});
</script>

<template>
    <Card class="p-6">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-2 dark:text-white">Aspectos Legales</h2>
            <div class="w-full bg-gray-200 rounded-full h-2.5 mb-4">
                <div :class="progressColor" class="h-2.5 rounded-full transition-all duration-300" :style="{ width: progress + '%' }"></div>
            </div>
            <p class="text-sm text-gray-600">Progreso: {{ progress }}% ({{ score }} puntos)</p>
        </div>

        <div class="space-y-6">
            <div v-for="question in visibleQuestions" :key="question.id" class="space-y-3">
                <label class="block text-sm font-medium text-gray-700 dark:text-white">
                    {{ question.text }}
                </label>

                <!-- Radio buttons para yes_no -->
                <div v-if="question.type === 'yes_no'" class="flex space-x-4">
                    <label class="flex items-center">
                        <input
                            type="radio"
                            :name="`question_${question.id}`"
                            :value="true"
                            :checked="answers[question.id] === true"
                            @change="handleAnswerChange(question.id, true)"
                            class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 focus:outline-none"
                        />
                        <span class="ml-2 text-sm font-medium">Sí</span>
                    </label>
                    <label class="flex items-center">
                        <input
                            type="radio"
                            :name="`question_${question.id}`"
                            :value="false"
                            :checked="answers[question.id] === false"
                            @change="handleAnswerChange(question.id, false)"
                            class="w-4 h-4 text-red-600 bg-gray-100 border-gray-300 focus:outline-none"
                        />
                        <span class="ml-2 text-sm font-medium">No</span>
                    </label>
                </div>

                <!-- Campo de texto -->
                <input
                    v-else-if="question.type === 'text'"
                    type="text"
                    :placeholder="question.placeholder"
                    :value="answers[question.id] || ''"
                    @input="handleAnswerChange(question.id, ($event.target as HTMLInputElement).value)"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                />

                <!-- Área de texto -->
                <textarea
                    v-else-if="question.type === 'textarea'"
                    :placeholder="question.placeholder"
                    :value="answers[question.id] || ''"
                    @input="handleAnswerChange(question.id, ($event.target as HTMLTextAreaElement).value)"
                    rows="3"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                ></textarea>

                <!-- Select dropdown -->
                <select
                    v-else-if="question.type === 'select'"
                    :value="answers[question.id] || ''"
                    @change="handleAnswerChange(question.id, ($event.target as HTMLSelectElement).value)"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                >
                    <option value="">Seleccione una opción</option>
                    <option v-for="option in question.options" :key="option" :value="option">
                        {{ option }}
                    </option>
                </select>

                <!-- Campo numérico -->
                <input
                    v-else-if="question.type === 'number'"
                    type="number"
                    :placeholder="question.placeholder"
                    :min="question.min"
                    :max="question.max"
                    :value="answers[question.id] || ''"
                    @input="handleAnswerChange(question.id, parseInt(($event.target as HTMLInputElement).value) || '')"
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                />

                <!-- Checkboxes -->
                <div v-else-if="question.type === 'checkbox'" class="space-y-2">
                    <label v-for="option in question.options" :key="option" class="flex items-center">
                        <input
                            type="checkbox"
                            :value="option"
                            :checked="Array.isArray(answers[question.id]) && answers[question.id].includes(option)"
                            @change="handleCheckboxChange(question.id, option, ($event.target as HTMLInputElement).checked)"
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:outline-none"
                        />
                        <span class="ml-2 text-sm text-gray-700">{{ option }}</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Resultado de la sección -->
        <div v-if="progress > 0" class="mt-6 p-4 rounded-lg" :class="{
            'bg-green-50 border border-green-200': progress >= 80,
            'bg-yellow-50 border border-yellow-200': progress >= 60 && progress < 80,
            'bg-orange-50 border border-orange-200': progress >= 40 && progress < 60,
            'bg-red-50 border border-red-200': progress < 40
        }">
            <h3 class="font-semibold" :class="{
                'text-green-800': progress >= 80,
                'text-yellow-800': progress >= 60 && progress < 80,
                'text-orange-800': progress >= 40 && progress < 60,
                'text-red-800': progress < 40
            }">
                {{ resultMessage }}
            </h3>
            <p class="text-sm mt-1" :class="{
                'text-green-600': progress >= 80,
                'text-yellow-600': progress >= 60 && progress < 80,
                'text-orange-600': progress >= 40 && progress < 60,
                'text-red-600': progress < 40
            }">
                Puntaje obtenido: {{ score }} puntos
            </p>
        </div>
    </Card>
</template>