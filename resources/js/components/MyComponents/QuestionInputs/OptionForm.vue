<template>
  <div class="border rounded-lg p-4 space-y-4 bg-gray-50 dark:bg-gray-800">
    <div class="flex justify-between items-center">
      <h4 class="font-medium text-sm text-gray-700 dark:text-gray-300">
        Opción {{ index + 1 }}
      </h4>
      <Button 
        @click="$emit('remove')" 
        type="button" 
        variant="outline" 
        size="sm"
        class="h-8 w-8 p-0 text-red-600 hover:text-red-700"
      >
        <Minus class="h-4 w-4" />
      </Button>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <!-- Texto de la opción -->
      <div>
        <Label :for="`option-text-${index}`" class="text-sm font-medium">
          Texto de la opción *
        </Label>
        <Input 
          :id="`option-text-${index}`"
          :model-value="option.text"
          @update:model-value="updateField('text', $event)"
          :placeholder="`Opción ${index + 1}`"
          class="mt-1"
        />
        <InputError :message="errors?.text" class="mt-1" />
      </div>
      
      <!-- Puntos -->
      <div>
        <Label :for="`option-points-${index}`" class="text-sm font-medium">
          Puntos
        </Label>
        <Input 
          :id="`option-points-${index}`"
          :model-value="option.points"
          @update:model-value="updateField('points', Number($event))"
          type="number"
          min="0"
          max="100"
          placeholder="0"
          class="mt-1"
        />
        <InputError :message="errors?.points" class="mt-1" />
      </div>
    </div>
    
    <!-- Descripción adicional -->
    <div>
      <Label :for="`option-description-${index}`" class="text-sm font-medium">
        Descripción (opcional)
      </Label>
      <textarea 
        :id="`option-description-${index}`"
        :value="option.description || ''"
        @input="updateField('description', $event.target.value)"
        placeholder="Descripción adicional para esta opción..."
        rows="2"
        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
      ></textarea>
    </div>
    
    <!-- Configuraciones adicionales -->
    <div class="flex items-center space-x-4">
      <div class="flex items-center space-x-2">
        <input 
          :id="`option-default-${index}`"
          type="checkbox"
          :checked="option.isDefault || false"
          @change="updateField('isDefault', $event.target.checked)"
          class="rounded border-input text-primary focus:ring-ring"
        />
        <Label :for="`option-default-${index}`" class="text-sm">
          Opción por defecto
        </Label>
      </div>
      
      <div class="flex items-center space-x-2">
        <input 
          :id="`option-disabled-${index}`"
          type="checkbox"
          :checked="option.isDisabled || false"
          @change="updateField('isDisabled', $event.target.checked)"
          class="rounded border-input text-primary focus:ring-ring"
        />
        <Label :for="`option-disabled-${index}`" class="text-sm">
          Deshabilitada
        </Label>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Minus } from 'lucide-vue-next';

interface QuestionOption {
  text: string;
  points: number;
  description?: string;
  isDefault?: boolean;
  isDisabled?: boolean;
}

interface Props {
  option: QuestionOption;
  index: number;
  errors?: Record<string, string>;
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