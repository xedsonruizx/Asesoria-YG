<template>
  <div>
    <!-- Placeholder -->
    <div class="mb-4">
      <Label for="placeholder">Texto de Ayuda</Label>
      <Input 
        id="placeholder"
        v-model="placeholder"
        placeholder="Texto que aparecerá como ayuda al usuario"
      />
      <InputError :message="placeholderError" />
    </div>

    <!-- Valores mínimo y máximo -->
    <div class="grid grid-cols-2 gap-4">
      <div>
        <Label for="min_value">Valor Mínimo</Label>
        <Input 
          id="min_value"
          v-model.number="minValue"
          type="number"
          placeholder="0"
        />
        <InputError :message="minValueError" />
      </div>
      <div>
        <Label for="max_value">Valor Máximo</Label>
        <Input 
          id="max_value"
          v-model.number="maxValue"
          type="number"
          placeholder="100"
        />
        <InputError :message="maxValueError" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';

interface Props {
  placeholder?: string;
  minValue?: number | null;
  maxValue?: number | null;
  placeholderError?: string;
  minValueError?: string;
  maxValueError?: string;
}

interface Emits {
  (e: 'update:placeholder', value: string): void;
  (e: 'update:minValue', value: number | null): void;
  (e: 'update:maxValue', value: number | null): void;
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: '',
  minValue: null,
  maxValue: null,
  placeholderError: '',
  minValueError: '',
  maxValueError: ''
});

const emit = defineEmits<Emits>();

const placeholder = computed({
  get: () => props.placeholder,
  set: (value: string) => emit('update:placeholder', value)
});

const minValue = computed({
  get: () => props.minValue,
  set: (value: number | null) => emit('update:minValue', value)
});

const maxValue = computed({
  get: () => props.maxValue,
  set: (value: number | null) => emit('update:maxValue', value)
});
</script>