<script setup lang="ts">
import { computed } from 'vue';

interface Question {
    id: number;
    question_text: string;
    question_type: 'text' | 'textarea' | 'select' | 'number' | 'checkbox' | 'radio';
    options?: string[] | Array<{text: string, points: number}>;
    placeholder?: string;
    min_value?: number;
    max_value?: number;
    points: number;
    show_condition?: any;
    order: number;
    is_active: boolean;
    is_required: boolean;
}

interface Props {
    question: Question;
    modelValue: any;
}

interface Emits {
    (e: 'update:modelValue', value: any): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const value = computed({
    get: () => props.modelValue,
    set: (newValue) => emit('update:modelValue', newValue)
});

// Función para obtener las opciones en formato uniforme
const getOptionsArray = computed(() => {
    if (!props.question.options) return [];
    
    let options = props.question.options;
    
    // Si las opciones vienen como string JSON, parsearlas
    if (typeof options === 'string') {
        try {
            options = JSON.parse(options);
        } catch (e) {
            console.error('Error parsing options JSON:', e, 'Original options:', props.question.options);
            // Si falla el parsing, intentar como array de strings separadas por coma
            if (typeof props.question.options === 'string') {
                const stringOptions = props.question.options.split(',').map(opt => opt.trim());
                return stringOptions.map(option => ({
                    text: option,
                    points: 0
                }));
            }
            return [];
        }
    }
    
    // Si es el nuevo formato con objetos {text, points}
    if (Array.isArray(options) && 
        options.length > 0 && 
        typeof options[0] === 'object' && 
        'text' in options[0]) {
        const result = options as Array<{text: string, points: number}>;
        console.log('Parsed options for question', props.question.id, ':', result);
        return result;
    }
    
    // Si es el formato antiguo (array de strings)
    if (Array.isArray(options)) {
        const result = options.map(option => ({
            text: typeof option === 'string' ? option : String(option),
            points: 0
        }));
        console.log('Converted string options for question', props.question.id, ':', result);
        return result;
    }
    
    return [];
});

// Función para obtener el texto de una opción
const getOptionText = (option: any): string => {
    return typeof option === 'object' ? option.text : String(option);
};

// Función para obtener el valor de una opción (usar un ID único)
const getOptionValue = (option: any): string => {
    if (typeof option === 'object') {
        // Usar el texto como valor para mantener consistencia
        return option.text;
    }
    return String(option);
};

// Computed para determinar si el campo está vacío
const isFieldEmpty = computed(() => {
    const currentValue = value.value;
    
    // Para checkboxes, considerar vacío si no es array o está vacío
    if (props.question.question_type === 'checkbox') {
        return !Array.isArray(currentValue) || currentValue.length === 0;
    }
    
    // Para otros tipos, considerar vacío si es undefined, null o string vacío
    return currentValue === undefined || currentValue === null || currentValue === '';
});

// Computed para mostrar el indicador de campo requerido
const shouldShowRequiredIndicator = computed(() => {
    return props.question.is_required && isFieldEmpty.value;
});

const handleCheckboxChange = (option: string, checked: boolean) => {
    let currentValue = Array.isArray(value.value) ? [...value.value] : [];
    
    if (checked) {
        if (!currentValue.includes(option)) {
            currentValue.push(option);
        }
    } else {
        currentValue = currentValue.filter(item => item !== option);
    }
    
    value.value = currentValue;
};
</script>

<template>
    <div class="space-y-3">
        <label class="block text-sm font-medium text-gray-700 dark:text-white">
            {{ question.question_text }}
            <span v-if="question.points" class="text-xs text-gray-500 ml-1">
                ({{ question.points }} puntos)
            </span>
        </label>

        <!-- Campo de texto -->
        <input
            v-if="question.question_type === 'text'"
            v-model="value"
            type="text"
            :placeholder="question.placeholder || 'Ingrese su respuesta'"
            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
        />

        <!-- Campo de textarea -->
        <textarea
            v-else-if="question.question_type === 'textarea'"
            v-model="value"
            :placeholder="question.placeholder || 'Ingrese su respuesta detallada'"
            rows="4"
            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
        ></textarea>

        <!-- Campo select -->
        <div v-else-if="question.question_type === 'select'" class="relative">
            <select
                v-model="value"
                class="mt-1 block w-full px-3 py-2 pr-10 border border-gray-300 rounded-md shadow-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white appearance-none cursor-pointer"
            >
                <option value="" disabled>Seleccione una opción</option>
                <option 
                    v-for="option in getOptionsArray" 
                    :key="getOptionValue(option)" 
                    :value="getOptionValue(option)"
                >
                    {{ getOptionText(option) }}
                    <span v-if="option.points !== undefined" class="text-xs text-gray-500">
                        ({{ option.points }} puntos)
                    </span>
                </option>
            </select>
            <!-- Flecha personalizada -->
            <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>
        </div>

        <!-- Radio buttons -->
        <div v-else-if="question.question_type === 'radio'" class="space-y-2">
            <label 
                v-for="option in getOptionsArray" 
                :key="getOptionValue(option)" 
                class="flex items-center cursor-pointer"
            >
                <input
                    type="radio"
                    :name="`question_${question.id}`"
                    :value="getOptionValue(option)"
                    v-model="value"
                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus:outline-none dark:bg-gray-700 dark:border-gray-600"
                />
                <span class="ml-2 text-sm text-gray-700 dark:text-white">
                    {{ getOptionText(option) }}
                    <span v-if="option.points !== undefined" class="text-xs text-gray-500 ml-1">
                        ({{ option.points }} puntos)
                    </span>
                </span>
            </label>
        </div>

        <!-- Campo numérico -->
        <input
            v-else-if="question.question_type === 'number'"
            v-model.number="value"
            type="number"
            :placeholder="question.placeholder || 'Ingrese un número'"
            :min="question.min_value"
            :max="question.max_value"
            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
        />

        <!-- Checkboxes múltiples -->
        <div v-else-if="question.question_type === 'checkbox'" class="space-y-2">
            <label 
                v-for="option in getOptionsArray" 
                :key="getOptionValue(option)" 
                class="flex items-center cursor-pointer"
            >
                <input
                    type="checkbox"
                    :value="getOptionValue(option)"
                    :checked="Array.isArray(value) && value.includes(getOptionValue(option))"
                    @change="handleCheckboxChange(getOptionValue(option), ($event.target as HTMLInputElement).checked)"
                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:outline-none dark:bg-gray-700 dark:border-gray-600"
                />
                <span class="ml-2 text-sm text-gray-700 dark:text-white">
                    {{ getOptionText(option) }}
                    <span v-if="option.points !== undefined" class="text-xs text-gray-500 ml-1">
                        ({{ option.points }} puntos)
                    </span>
                </span>
            </label>
        </div>

        <!-- Indicador de respuesta requerida -->
        <div v-if="shouldShowRequiredIndicator" class="text-xs text-gray-400">
            * Campo requerido
        </div>
    </div>
</template>