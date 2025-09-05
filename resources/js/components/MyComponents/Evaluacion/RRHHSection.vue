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

// Preguntas específicas de RRHH
const rrhhQuestions: Question[] = [
    {
        id: 1,
        text: "¿Tiene contrato de trabajo formal?",
        category: "RRHH",
        type: "yes_no",
        points: 25,
        nextQuestionYes: 2,
        nextQuestionNo: 3
    },
    {
        id: 2,
        text: "¿Su contrato especifica claramente sus funciones y salario?",
        category: "RRHH",
        type: "yes_no",
        points: 15,
        showCondition: { questionId: 1, answer: true },
        nextQuestionYes: 4,
        nextQuestionNo: 5
    },
    {
        id: 3,
        text: "¿Ha trabajado más de 3 meses sin contrato?",
        category: "RRHH",
        type: "yes_no",
        points: 5,
        showCondition: { questionId: 1, answer: false },
        nextQuestionYes: 6,
        nextQuestionNo: 7
    },
    {
        id: 4,
        text: "¿Recibe todos los beneficios laborales (vacaciones, aguinaldo, etc.)?",
        category: "RRHH",
        type: "yes_no",
        points: 20,
        showCondition: { questionId: 2, answer: true }
    },
    {
        id: 5,
        text: "Describa brevemente sus funciones principales en el trabajo:",
        category: "RRHH",
        type: "textarea",
        points: 8,
        placeholder: "Ej: Atención al cliente, ventas, administración...",
        showCondition: { questionId: 2, answer: false }
    },
    {
        id: 6,
        text: "¿Su empleador le ha prometido formalizar el contrato?",
        category: "RRHH",
        type: "yes_no",
        points: 12,
        showCondition: { questionId: 3, answer: true }
    },
    {
        id: 7,
        text: "Ingrese su nombre completo:",
        category: "RRHH",
        type: "text",
        points: 6,
        placeholder: "Nombre y apellidos",
        showCondition: { questionId: 3, answer: false }
    },
    {
        id: 15,
        text: "¿Cuántos años de experiencia laboral tiene?",
        category: "RRHH",
        type: "number",
        points: 10,
        placeholder: "Años de experiencia",
        min: 0,
        max: 50
    },
    {
        id: 16,
        text: "Seleccione su nivel educativo:",
        category: "RRHH",
        type: "select",
        points: 8,
        options: ["Primaria", "Secundaria", "Técnico", "Universitario", "Postgrado"]
    }
];

// Computed para preguntas visibles
const visibleQuestions = computed(() => {
    return rrhhQuestions.filter(question => {
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

// Función para limpiar respuestas dependientes
const clearDependentAnswers = (changedQuestionId: number, answers: Record<number, any>) => {
    rrhhQuestions.forEach(question => {
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
    if (percentage >= 80) return 'Excelente situación laboral en RRHH';
    if (percentage >= 60) return 'Buena situación laboral en RRHH';
    if (percentage >= 40) return 'Situación laboral regular en RRHH';
    return 'Necesita mejorar su situación laboral en RRHH';
});
</script>

<template>
    <Card class="p-6">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-2 dark:text-white">Recursos Humanos</h2>
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
                    class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-900 dark:bg-gray-800 dark:text-white dark:border-gray-600"
                >
                    <option value="" class="text-gray-500 dark:text-gray-400">Seleccione una opción</option>
                    <option v-for="option in question.options" :key="option" :value="option" class="text-gray-900 dark:text-white bg-white dark:bg-gray-800">
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