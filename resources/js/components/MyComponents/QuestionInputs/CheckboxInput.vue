<template>
  <div>
    <Label>Opciones con Puntos *</Label>
    <div class="space-y-3 mt-2">
      <div v-for="(option, index) in options" :key="index" class="flex gap-2 items-end">
        <div class="flex-1">
          <Label :for="`checkbox-option-text-${index}`" class="text-sm">Texto de la opción</Label>
          <Input 
            :id="`checkbox-option-text-${index}`"
            v-model="option.text"
            :placeholder="`Opción ${index + 1}`"
            @input="updateOption(index, 'text', $event.target.value)"
          />
        </div>
        <div class="w-24">
          <Label :for="`checkbox-option-points-${index}`" class="text-sm">Puntos</Label>
          <Input 
            :id="`checkbox-option-points-${index}`"
            v-model.number="option.points"
            type="number"
            min="0"
            max="100"
            placeholder="0"
            @input="updateOption(index, 'points', Number($event.target.value))"
          />
        </div>
        <Button 
          @click="removeOption(index)" 
          type="button" 
          variant="outline" 
          size="sm"
          class="h-10 w-10 p-0"
        >
          <Minus class="h-4 w-4" />
        </Button>
      </div>
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Plus, Minus } from 'lucide-vue-next';

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

const updateOption = (index: number, field: 'text' | 'points', value: string | number) => {
  const newOptions = [...options.value];
  newOptions[index] = { ...newOptions[index], [field]: value };
  emit('update:modelValue', newOptions);
};
</script>