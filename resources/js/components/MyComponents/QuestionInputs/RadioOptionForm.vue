<template>
  <div class="flex gap-2 items-end">
    <div class="flex-1">
      <Label :for="`radio-option-text-${index}`" class="text-sm">Texto de la opción</Label>
      <Input 
        :id="`radio-option-text-${index}`"
        :model-value="option.text"
        @update:model-value="updateField('text', $event)"
        :placeholder="`Opción ${index + 1}`"
      />
    </div>
    <div class="w-24">
      <Label :for="`radio-option-points-${index}`" class="text-sm">Puntos</Label>
      <Input 
        :id="`radio-option-points-${index}`"
        :model-value="option.points"
        @update:model-value="updateField('points', Number($event))"
        type="number"
        min="0"
        max="100"
        placeholder="0"
      />
    </div>
    <Button 
      @click="$emit('remove')" 
      type="button" 
      variant="outline" 
      size="sm"
      class="h-10 w-10 p-0"
    >
      <Minus class="h-4 w-4" />
    </Button>
  </div>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Minus } from 'lucide-vue-next';

interface QuestionOption {
  text: string;
  points: number;
}

interface Props {
  option: QuestionOption;
  index: number;
}

interface Emits {
  (e: 'update', field: keyof QuestionOption, value: any): void;
  (e: 'remove'): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const updateField = (field: keyof QuestionOption, value: any) => {
  emit('update', field, value);
};
</script>