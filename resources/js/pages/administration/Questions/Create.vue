<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Save, Plus, Minus, ArrowLeft, X } from 'lucide-vue-next';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Teleport } from 'vue';
import InputError from '@/components/InputError.vue';
import QuestionDependency from '@/components/QuestionDependency.vue';
import MultaAssignment from '@/components/MultaAssignment.vue';
import QuestionTypeInputs from '@/components/MyComponents/QuestionInputs/QuestionTypeInputs.vue';

interface Category {
  id: number;
  name: string;
  slug: string;
  color: string;
}

interface Question {
  id: number;
  question_text: string;
}

interface Multa {
  id: number;
  name: string;
  description?: string;
}

interface QuestionOption {
  text: string;
  points: number;
}

interface QuestionForm {
  category_id: number | null;
  question_text: string;
  question_type: 'text' | 'textarea' | 'select' | 'radio' | 'number' | 'checkbox' | 'yes_no';
  options: QuestionOption[];
  placeholder: string;
  min_value: number | null;
  max_value: number | null;
  points: number;
  order: number;
  show_condition: any;
  multa_condition: {
    multa_id: number | null;
    trigger_condition: string;
    trigger_value: string | null;
  };
  validation_rules: any;
  is_required: boolean;
  is_active: boolean;
}

interface Props {
  categories: Category[];
  availableQuestions?: Question[];
  questions?: Question[];  // Agregar esta propiedad
  multas?: Multa[];        // Agregar esta propiedad
}

const props = withDefaults(defineProps<Props>(), {
  categories: () => [],
  availableQuestions: () => [],
  questions: () => [],     // Valor por defecto
  multas: () => [],        // Valor por defecto
});

// Emits
const emit = defineEmits<{
  close: [];
  created: [];
}>();

const isOpen = ref(true);
const hasDependency = ref(false);
const hasMultaAssignment = ref(false);

const form = useForm<QuestionForm>({
  category_id: null,
  question_text: '',
  question_type: 'text',
  options: [],
  placeholder: '',
  min_value: null,
  max_value: null,
  points: 1,
  order: 1,
  show_condition: {
    parent_question_id: null,
    operator: 'equals',
    value: null
  },
  multa_condition: {
    multa_id: null,
    trigger_condition: 'always',
    trigger_value: null
  },
  validation_rules: null,
  is_required: true,
  is_active: true,
});

// Computed para mostrar campos específicos según el tipo
const showOptions = computed(() => {
  return ['select', 'checkbox'].includes(form.question_type);
});

const showMinMax = computed(() => {
  return form.question_type === 'number';
});

const showPlaceholder = computed(() => {
  return ['text', 'textarea', 'number'].includes(form.question_type);
});

// Agregar computed para mostrar campo de puntos
const showPoints = computed(() => {
  // No mostrar puntos para tipos que tienen puntos individuales por opción
  return !['select', 'radio', 'checkbox'].includes(form.question_type);
});

// Función para cerrar el modal
const close = () => {
  isOpen.value = false;
  emit('close');
};

const submitForm = () => {
  form.post('/admin/questions', {
    onSuccess: () => {
      emit('created');
      close();
    },
  });
};

// Agregar función para actualizar puntos totales
const updateTotalPoints = (totalPoints: number) => {
  // Solo actualizar puntos automáticamente para tipos con opciones
  if (['select', 'radio', 'checkbox'].includes(form.question_type)) {
    form.points = totalPoints;
  }
};

// Watcher para resetear puntos cuando cambie el tipo de pregunta
watch(() => form.question_type, (newType, oldType) => {
  if (['select', 'radio', 'checkbox'].includes(newType)) {
    // Para tipos con opciones, los puntos se calcularán automáticamente
    form.points = 0;
  } else {
    // Para otros tipos, establecer puntos por defecto
    form.points = 1;
    // Limpiar opciones si el nuevo tipo no las necesita
    form.options = [];
  }
  
  // Limpiar campos específicos según el tipo anterior
  if (oldType && !['text', 'textarea', 'number'].includes(newType)) {
    form.placeholder = '';
  }
  
  if (oldType && newType !== 'number') {
    form.min_value = null;
    form.max_value = null;
  }
});

const availableQuestions = ref<Question[]>([]);

// Función para obtener el próximo orden automáticamente
const getNextOrder = async (categoryId: number) => {
  if (!categoryId) {
    form.order = 1;
    return;
  }
  
  try {
    const response = await fetch(`/admin/questions/next-order?category_id=${categoryId}`);
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();
    form.order = data.next_order;
  } catch (error) {
    console.error('Error getting next order:', error);
    form.order = 1; // Valor por defecto en caso de error
  }
};

// Función para cargar preguntas por categoría
const loadQuestionsByCategory = async (categoryId: number) => {
  if (!categoryId) {
    availableQuestions.value = [];
    return;
  }
  
  try {
    const response = await fetch(`/admin/questions/by-category?category_id=${categoryId}`);
    
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
  }
};

// Watcher para cargar preguntas y obtener próximo orden cuando cambie la categoría
watch(() => form.category_id, (newCategoryId) => {
  if (newCategoryId) {
    // Obtener el próximo orden automáticamente
    getNextOrder(newCategoryId);
    
    if (hasDependency.value) {
      loadQuestionsByCategory(newCategoryId);
    }
  } else {
    availableQuestions.value = [];
    form.order = 1;
  }
  // Limpiar la pregunta padre seleccionada al cambiar categoría
  form.show_condition.parent_question_id = null;
});

// También cargar cuando se active la dependencia
watch(hasDependency, (newValue) => {
  if (newValue && form.category_id) {
    loadQuestionsByCategory(form.category_id);
  } else {
    availableQuestions.value = [];
    // Limpiar los datos de dependencia cuando se desmarca
    form.show_condition = {
      parent_question_id: null,
      operator: 'equals',
      value: null
    };
  }
});
</script>

<template>
  <Teleport to="body">
    <div 
      v-if="isOpen" 
      class="fixed inset-0 z-50 flex items-start sm:items-center justify-center p-2 sm:p-4 bg-black/50 backdrop-blur-sm overflow-y-auto"
      @click="close"
    >
      <!-- Modal Container - Responsive -->
      <div 
        class="relative w-full max-w-sm sm:max-w-2xl md:max-w-4xl lg:max-w-6xl xl:max-w-7xl min-h-[90vh] sm:min-h-0 sm:max-h-[95vh] bg-background rounded-none sm:rounded-lg shadow-2xl border-0 sm:border overflow-hidden mt-0 sm:mt-4"
        @click.stop
      >
        <!-- Header -->
        <div class="flex items-center justify-between p-4 sm:p-6 border-b bg-muted/30 sticky top-0 z-10">
          <h2 class="text-lg sm:text-xl font-semibold text-foreground">Nueva Pregunta</h2>
          <button 
            @click="close" 
            class="p-2 hover:bg-muted rounded-md transition-colors"
          >
            <X class="w-4 h-4" />
          </button>
        </div>
        
        <!-- Content - Responsive Layout -->
        <div class="flex flex-col lg:flex-row h-[calc(90vh-64px)] sm:h-[calc(95vh-80px)]">
          <!-- Main Form Area -->
          <div class="flex-1 p-4 sm:p-6 overflow-y-auto">
            <form @submit.prevent="submitForm" class="space-y-4 sm:space-y-6">
              <!-- Grid responsive -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                <!-- Categoría -->
                <div class="space-y-2 md:col-span-2 lg:col-span-1">
                  <Label for="category">Categoría *</Label>
                  <select
                    id="category"
                    v-model="form.category_id"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    required
                  >
                    <option value="null">Seleccionar categoría</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">
                      {{ category.name }}
                    </option>
                  </select>
                  <InputError :message="form.errors.category_id" />
                </div>

                <!-- Texto de la Pregunta -->
                <div class="space-y-2 md:col-span-2">
                  <Label for="question_text">Texto de la Pregunta *</Label>
                  <textarea
                    id="question_text"
                    v-model="form.question_text"
                    rows="3"
                    class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    placeholder="Escribe el texto de la pregunta..."
                    required
                  ></textarea>
                  <InputError :message="form.errors.question_text" />
                </div>

                <!-- Tipo de Pregunta -->
                <div class="space-y-2">
                  <Label for="question_type">Tipo de Pregunta *</Label>
                  <select
                    id="question_type"
                    v-model="form.question_type"
                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                    required
                  >
                    <option value="text">Texto</option>
                    <option value="textarea">Texto largo</option>
                    <option value="select">Selección</option>
                    <option value="radio">Radio</option>
                    <option value="number">Número</option>
                    <option value="checkbox">Casillas</option>
                  </select>
                  <InputError :message="form.errors.question_type" />
                </div>

                <!-- Puntos -->
                <div v-if="showPoints" class="space-y-2">
                  <Label for="points">Puntos *</Label>
                  <Input
                    id="points"
                    v-model.number="form.points"
                    type="number"
                    min="0"
                    required
                  />
                  <InputError :message="form.errors.points" />
                </div>

                <!-- Puntos automáticos para tipos con opciones -->
                <div v-else class="space-y-2">
                  <Label>Puntos (Calculados automáticamente)</Label>
                  <div class="text-sm text-muted-foreground bg-muted p-2 rounded">
                    Total de puntos: {{ form.points }}
                  </div>
                </div>

                <!-- Orden -->
                <div class="space-y-2">
                  <Label for="order">Orden *</Label>
                  <Input
                    id="order"
                    v-model.number="form.order"
                    type="number"
                    min="1"
                    required
                  />
                  <InputError :message="form.errors.order" />
                </div>
              </div>

              <!-- Componentes adicionales -->
              <div class="space-y-4 sm:space-y-6">
                <QuestionTypeInputs
                  :question-type="form.question_type"
                  :placeholder="form.placeholder"
                  :options="form.options"
                  :min-value="form.min_value"
                  :max-value="form.max_value"
                  @update:placeholder="form.placeholder = $event"
                  @update:options="form.options = $event"
                  @update:min-value="form.min_value = $event"
                  @update:max-value="form.max_value = $event"
                  @update:totalPoints="updateTotalPoints"
                  :errors="form.errors"
                />
                
                <!-- Dependencias de Pregunta -->
                <div class="space-y-4">
                  
                  <QuestionDependency 
                    v-model="hasDependency"
                    :show-condition="form.show_condition"
                    @update:show-condition="form.show_condition = $event"
                    :category-id="form.category_id"
                    :errors="form.errors"
                  />
                </div>
                
                                <!-- Asignación de Multas -->
                <div class="space-y-4">
                  
                  <MultaAssignment 
                    v-if="props.multas && props.multas.length > 0"
                    :multas="props.multas"
                    :multa-condition="form.multa_condition"
                    @update:multa-condition="form.multa_condition = $event"
                    :errors="form.errors"
                  />
                </div>
              </div>
              
              <!-- Botones - Sticky en móvil -->
              <div class="sticky bottom-0 bg-background border-t pt-4 mt-6 flex flex-col sm:flex-row justify-end gap-3">
                <Button type="button" variant="outline" @click="close" class="w-full sm:w-auto">
                  Cancelar
                </Button>
                <Button type="submit" :disabled="form.processing" class="w-full sm:w-auto">
                  <Save class="w-4 h-4 mr-2" />
                  Guardar Pregunta
                </Button>
              </div>
            </form>
          </div>
          
          <!-- Sidebar - Oculto en móvil, visible en desktop -->
          <div class="hidden lg:block w-80 border-l bg-muted/20 p-6 overflow-y-auto">
            <div class="space-y-4">
              <h3 class="font-semibold text-foreground">Tipos de Pregunta</h3>
              <div class="space-y-3 text-sm">
                <div class="p-3 bg-background rounded-md">
                  <strong>Texto:</strong> Campo de texto simple
                </div>
                <div class="p-3 bg-background rounded-md">
                  <strong>Texto largo:</strong> Área de texto para respuestas extensas
                </div>
                <div class="p-3 bg-background rounded-md">
                  <strong>Selección:</strong> Lista desplegable con opciones
                </div>
                <div class="p-3 bg-background rounded-md">
                  <strong>Radio:</strong> Selección única con opciones personalizables
                </div>
                <div class="p-3 bg-background rounded-md">
                  <strong>Número:</strong> Campo numérico con validación
                </div>
                <div class="p-3 bg-background rounded-md">
                  <strong>Casillas:</strong> Múltiples opciones seleccionables
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
