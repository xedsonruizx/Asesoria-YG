<template>
  <div>
    <!-- Campos específicos según el tipo de pregunta -->
    <TextInput
      v-if="questionType === 'text'"
      :model-value="placeholder"
      @update:model-value="updatePlaceholder"
      :error="errors?.placeholder"
    />
    
    <TextareaInput
      v-else-if="questionType === 'textarea'"
      :model-value="placeholder"
      @update:model-value="updatePlaceholder"
      :error="errors?.placeholder"
    />
    
    <SelectInput
      v-else-if="questionType === 'select'"
      :model-value="options"
      @update:model-value="updateOptions"
      :error="errors?.options"
    />
    
    <NumberInput
      v-else-if="questionType === 'number'"
      :placeholder="placeholder"
      :min-value="minValue"
      :max-value="maxValue"
      @update:placeholder="updatePlaceholder"
      @update:min-value="updateMinValue"
      @update:max-value="updateMaxValue"
      :placeholder-error="errors?.placeholder"
      :min-value-error="errors?.min_value"
      :max-value-error="errors?.max_value"
    />
    
    <CheckboxInput
      v-else-if="questionType === 'checkbox'"
      :model-value="options"
      @update:model-value="updateOptions"
      :error="errors?.options"
    />
    
    <RadioInput
      v-else-if="questionType === 'radio'"
      :model-value="options"
      @update:model-value="updateOptions"
      :error="errors?.options"
    />
    
    <!-- <YesNoInput v-else-if="questionType === 'yes_no'"/> -->
  </div>
</template>

<script setup lang="ts">
import { computed, watch } from 'vue';
import TextInput from './TextInput.vue';
import TextareaInput from './TextareaInput.vue';
import SelectInput from './SelectInput.vue';
import NumberInput from './NumberInput.vue';
import CheckboxInput from './CheckboxInput.vue';
import RadioInput from './RadioInput.vue';
// Remover: import YesNoInput from './YesNoInput.vue';

type QuestionType = 'text' | 'textarea' | 'select' | 'number' | 'checkbox' | 'radio';

// Agregar la interfaz QuestionOption
interface QuestionOption {
  text: string;
  points: number;
}

interface Props {
  questionType: QuestionType;
  placeholder?: string;
  options?: QuestionOption[];
  minValue?: number | null;
  maxValue?: number | null;
  errors?: Record<string, string>;
}

interface Emits {
  (e: 'update:placeholder', value: string): void;
  (e: 'update:options', value: QuestionOption[]): void;
  (e: 'update:minValue', value: number | null): void;
  (e: 'update:maxValue', value: number | null): void;
  (e: 'update:totalPoints', value: number): void; // Nuevo emit para los puntos totales
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: '',
  options: () => [],
  minValue: null,
  maxValue: null,
  errors: () => ({})
});

const emit = defineEmits<Emits>();

// Computed para calcular el total de puntos de las opciones
const totalPoints = computed(() => {
  // Solo calcular puntos automáticamente para tipos con opciones
  if (['select', 'radio', 'checkbox'].includes(props.questionType)) {
    if (!props.options || props.options.length === 0) {
      // No retornar 0, sino undefined para indicar que no hay cálculo automático
      return undefined;
    }
    
    if (props.questionType === 'checkbox') {
      // Para checkboxes, sumar todos los puntos (máximo posible)
      return props.options.reduce((sum, option) => sum + (option.points || 0), 0);
    } else {
      // Para select y radio, tomar el máximo puntaje disponible
      return Math.max(...props.options.map(option => option.points || 0));
    }
  }
  
  // Para otros tipos, no calcular automáticamente
  return undefined;
});

// Watcher para emitir cambios en el total de puntos
watch(totalPoints, (newTotal) => {
  // Solo emitir si hay un valor calculado válido
  if (newTotal !== undefined) {
    emit('update:totalPoints', newTotal);
  }
}, { immediate: true });

const updatePlaceholder = (value: string) => {
  emit('update:placeholder', value);
};

const updateOptions = (value: QuestionOption[]) => {
  emit('update:options', value);
};

const updateMinValue = (value: number | null) => {
  emit('update:minValue', value);
};

const updateMaxValue = (value: number | null) => {
  emit('update:maxValue', value);
};
</script>