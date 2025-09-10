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
    
    <YesNoInput
      v-else-if="questionType === 'yes_no'"
    />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
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
  options?: QuestionOption[]; // Cambiar de string[] a QuestionOption[]
  minValue?: number | null;
  maxValue?: number | null;
  errors?: Record<string, string>;
}

interface Emits {
  (e: 'update:placeholder', value: string): void;
  (e: 'update:options', value: string[]): void;
  (e: 'update:minValue', value: number | null): void;
  (e: 'update:maxValue', value: number | null): void;
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: '',
  options: () => [], // Esto ya está correcto
  minValue: null,
  maxValue: null,
  errors: () => ({})
});

const emit = defineEmits<Emits>();

const updatePlaceholder = (value: string) => {
  emit('update:placeholder', value);
};

const updateOptions = (value: string[]) => {
  emit('update:options', value);
};

const updateMinValue = (value: number | null) => {
  emit('update:minValue', value);
};

const updateMaxValue = (value: number | null) => {
  emit('update:maxValue', value);
};
</script>