<template>
  <div>
    <Label>Opciones con Puntos *</Label>
    <div class="space-y-3">
      <RadioOptionForm
        v-for="(option, index) in options"
        :key="index"
        :option="option"
        :index="index"
        @update="(field, value) => updateOption(index, field, value)"
        @remove="removeOption(index)"
      />
      <Button @click="addOption" type="button" variant="outline" size="sm">
        <Plus class="h-4 w-4 mr-2" />
        Agregar Opción
      </Button>
    </div>
    <InputError :message="error" />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Plus } from 'lucide-vue-next';
import RadioOptionForm from './RadioOptionForm.vue';

interface QuestionOption {
  text: string;
  points: number;
}

interface Props {
  modelValue?: QuestionOption[];
  error?: string;
}

interface Emits {
  (e: 'update:modelValue', value: QuestionOption[]): void;
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: () => [],
  error: ''
});

const emit = defineEmits<Emits>();

const options = computed({
  get: () => props.modelValue,
  set: (value: QuestionOption[]) => emit('update:modelValue', value)
});

const addOption = () => {
  const newOptions = [...options.value, { text: '', points: 0 }];
  emit('update:modelValue', newOptions);
};

const removeOption = (index: number) => {
  const newOptions = [...options.value];
  newOptions.splice(index, 1);
  emit('update:modelValue', newOptions);
};

const updateOption = (index: number, field: keyof QuestionOption, value: any) => {
  const newOptions = [...options.value];
  newOptions[index] = { ...newOptions[index], [field]: value };
  emit('update:modelValue', newOptions);
};
</script>