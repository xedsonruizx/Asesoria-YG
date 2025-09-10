<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';

interface Question {
  id: number;
  question_text: string;
}

interface ShowCondition {
  parent_question_id?: number | null;
  operator?: string;
  value?: string;
}

interface Props {
  modelValue: boolean;
  showCondition: ShowCondition;
  categoryId: number | null;
  errors?: Record<string, string>;
  excludeQuestionId?: number; // Para Edit.vue, excluir la pregunta actual
}

interface Emits {
  'update:modelValue': [value: boolean];
  'update:showCondition': [value: ShowCondition];
}

const props = withDefaults(defineProps<Props>(), {
  errors: () => ({}),
  excludeQuestionId: undefined
});

const emit = defineEmits<Emits>();

const availableQuestions = ref<Question[]>([]);
const loading = ref(false);

// Computed para el valor del checkbox
const hasDependency = computed({
  get: () => props.modelValue,
  set: (value: boolean) => emit('update:modelValue', value)
});

// Computed para show_condition
const condition = computed({
  get: () => props.showCondition,
  set: (value: ShowCondition) => emit('update:showCondition', value)
});

// Función para cargar preguntas por categoría
const loadQuestionsByCategory = async (categoryId: number) => {
  if (!categoryId) {
    availableQuestions.value = [];
    return;
  }
  
  loading.value = true;
  try {
    const url = props.excludeQuestionId 
      ? `/admin/questions/by-category?category_id=${categoryId}&exclude_id=${props.excludeQuestionId}`
      : `/admin/questions/by-category?category_id=${categoryId}`;
    
    const response = await fetch(url);
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const contentType = response.headers.get('content-type');
    if (!contentType || !contentType.includes('application/json')) {
      throw new Error('Response is not JSON');
    }
    
    const questions = await response.json();
    availableQuestions.value = questions;
  } catch (error) {
    console.error('Error loading questions:', error);
    availableQuestions.value = [];
  } finally {
    loading.value = false;
  }
};

// Función para actualizar parent_question_id
const updateParentQuestionId = (value: number | null) => {

  
  const newCondition = {
    ...props.showCondition,
    parent_question_id: value
  };
  
  emit('update:showCondition', newCondition);
};

// Función para actualizar operator
const updateOperator = (value: string) => {
  emit('update:showCondition', {
    ...props.showCondition,
    operator: value
  });
};

// Función para actualizar value
const updateValue = (value: string) => {
  emit('update:showCondition', {
    ...props.showCondition,
    value: value
  });
};

// Watcher para cargar preguntas cuando cambie la categoría
watch(() => props.categoryId, (newCategoryId, oldCategoryId) => {
  
  if (newCategoryId && hasDependency.value) {
    loadQuestionsByCategory(newCategoryId);
  } else {
    availableQuestions.value = [];
  }
  
  // Limpiar la pregunta padre seleccionada al cambiar categoría
  updateParentQuestionId(null);
});

// También cargar cuando se active la dependencia
watch(hasDependency, (newValue) => {
  if (newValue && props.categoryId) {
    loadQuestionsByCategory(props.categoryId);
  } else {
    availableQuestions.value = [];
  }
});

// Cargar preguntas al montar el componente si ya hay dependencia
onMounted(() => {
  if (hasDependency.value && props.categoryId) {
    loadQuestionsByCategory(props.categoryId);
  }
});
</script>

<template>
  <div class="space-y-4 border-t border-border pt-4">
    <h4 class="font-medium text-foreground">Dependencias</h4>
    
    <div class="flex items-center space-x-2">
      <input 
        id="has_dependency"
        v-model="hasDependency"
        type="checkbox"
        class="rounded border-input text-primary focus:ring-ring"
      />
      <Label for="has_dependency">Esta pregunta depende de otra</Label>
    </div>
    
    <div v-if="hasDependency" class="space-y-4 ml-6">
      <div>
        <Label for="parent_question">Pregunta padre</Label>
        <!-- DEBUG INFO -->
        <!-- <div class="text-xs text-gray-500 mb-2">
          Debug: parent_question_id = {{ showCondition.parent_question_id }} | 
          categoryId = {{ categoryId }} | 
          availableQuestions.length = {{ availableQuestions.length }}
        </div> -->
        <select 
          id="parent_question"
          :value="condition.parent_question_id"
          @change="updateParentQuestionId($event.target.value ? parseInt($event.target.value) : null)"
          class="w-full px-3 py-2 border border-input rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
        >
          <option :value="null">Seleccionar pregunta</option>
          <option v-for="question in availableQuestions" :key="question.id" :value="question.id">
            Orden {{ question.order }} - {{ question.question_text }}
          </option>
        </select>
        <InputError :message="errors['show_condition.parent_question_id']" />
      </div>
      
      <div>
        <Label for="condition_operator">Condición</Label>
        <select 
          id="condition_operator"
          :value="showCondition.operator || 'equals'"
          @input="updateOperator(($event.target as HTMLSelectElement).value)"
          class="w-full px-3 py-2 border border-input rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
        >
          <option value="equals">Es igual a</option>
          <option value="not_equals">No es igual a</option>
          <option value="contains">Contiene</option>
          <option value="greater_than">Mayor que</option>
          <option value="less_than">Menor que</option>
          <option value="is_empty">Está vacío</option>
          <option value="is_not_empty">Está rellenado</option>
        </select>
        <InputError :message="errors['show_condition.operator']" />
      </div>
      
      <div v-if="!['is_empty', 'is_not_empty'].includes(showCondition.operator || 'equals')">
        <Label for="condition_value">Valor esperado</Label>
        <Input 
          id="condition_value"
          :model-value="showCondition.value || ''"
          @update:model-value="updateValue"
          placeholder="Valor que debe tener la pregunta padre"
        />
        <InputError :message="errors['show_condition.value']" />
      </div>
    </div>
  </div>
</template>