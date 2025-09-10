<template>
  <div>
    <Label for="placeholder">Texto de Ayuda</Label>
    <Input 
      id="placeholder"
      v-model="placeholder"
      placeholder="Texto que aparecerá como ayuda al usuario"
    />
    <InputError :message="error" />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';

interface Props {
  modelValue?: string;
  error?: string;
}

interface Emits {
  (e: 'update:modelValue', value: string): void;
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  error: ''
});

const emit = defineEmits<Emits>();

const placeholder = computed({
  get: () => props.modelValue,
  set: (value: string) => emit('update:modelValue', value)
});
</script>