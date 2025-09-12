<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { Save, X } from 'lucide-vue-next';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Teleport } from 'vue';
import QuestionDependency from '@/components/QuestionDependency.vue';
import MultaAssignment from '@/components/MultaAssignment.vue';
import QuestionTypeInputs from '@/components/MyComponents/QuestionInputs/QuestionTypeInputs.vue';

interface QuestionOption {
    text: string;
    points: number;
}

interface QuestionForm {
    category_id: number;
    question_text: string;
    question_type: 'text' | 'textarea' | 'select' | 'number' | 'checkbox' | 'yes_no';
    options: QuestionOption[];
    placeholder?: string;
    min_value?: number;
    max_value?: number;
    points: number;
    order: number;
    show_condition?: {
        parent_question_id?: number;
        operator?: string;
        value?: string;
    };
    multa_condition?: {
        multa_id?: number;
        trigger_condition?: string;
        trigger_value?: string;
    };
    validation_rules?: any;
    is_required: boolean;
    is_active: boolean;
}

interface Question {
    id: number;
    category_id: number;
    question_text: string;
    question_type: string;
    options?: string[];
    placeholder?: string;
    min_value?: number;
    max_value?: number;
    points: number;
    order: number;
    show_condition?: any;
    validation_rules?: any;
    is_required: boolean;
    is_active: boolean;
    category?: {
        id: number;
        name: string;
    };
}

interface Category {
    id: number;
    name: string;
    slug: string;
}

interface Multa {
    id: number;
    name: string;
    description?: string;
}

interface Props {
    question: Question;
    categories: Category[];
    availableQuestions?: Question[];
    multas?: Multa[];
}

const props = withDefaults(defineProps<Props>(), {
    availableQuestions: () => [],
    multas: () => []
});

// Emits
const emit = defineEmits<{
  close: [];
  updated: [];
}>();

const isOpen = ref(true);
const hasDependency = ref(false);
const hasMultaAssignment = ref(false);

// Función para convertir opciones al formato correcto
const convertOptionsToCorrectFormat = (options: any): QuestionOption[] => {
    if (!options) return [];
    
    // Si ya es un array de objetos con text y points
    if (Array.isArray(options) && 
        options.length > 0 && 
        typeof options[0] === 'object' && 
        'text' in options[0] && 
        'points' in options[0]) {
        return options as QuestionOption[];
    }
    
    // Si es un array de strings (formato antiguo)
    if (Array.isArray(options)) {
        return options.map(option => ({
            text: typeof option === 'string' ? option : String(option),
            points: 0
        }));
    }
    
    // Si es un string JSON, parsearlo
    if (typeof options === 'string') {
        try {
            const parsed = JSON.parse(options);
            return convertOptionsToCorrectFormat(parsed);
        } catch (e) {
            console.error('Error parsing options JSON:', e);
            return [];
        }
    }
    
    return [];
};

const form = useForm<QuestionForm>({
    category_id: props.question.category_id,
    question_text: props.question.question_text,
    question_type: props.question.question_type as any,
    options: convertOptionsToCorrectFormat(props.question.options),
    placeholder: props.question.placeholder || '',
    min_value: props.question.min_value,
    max_value: props.question.max_value,
    points: props.question.points,
    order: props.question.order,
    show_condition: props.question.show_condition || {
        parent_question_id: null,
        operator: 'equals',
        value: ''
    },
    multa_condition: props.question.multa_condition || {
        multa_id: null,
        trigger_condition: 'always',
        trigger_value: null
    },
    validation_rules: props.question.validation_rules,
    is_required: props.question.is_required,
    is_active: props.question.is_active
});

// Establecer hasDependency basándose en el form inicializado
if (form.show_condition?.parent_question_id) {
    hasDependency.value = true;
    console.log('Dependency detected:', form.show_condition);
}

// Inicializar hasMultaAssignment basado en los datos existentes
if (props.question.multa_condition?.multa_id) {
    hasMultaAssignment.value = true;
}

// Computed properties
const showPoints = computed(() => {
    return !['select', 'radio', 'checkbox'].includes(form.question_type);
});

const close = () => {
    isOpen.value = false;
    emit('close');
};

const submitForm = () => {
    form.put(`/admin/questions/${props.question.id}`, {
        onSuccess: () => {
            emit('updated');
            close();
        }
    });
};

const availableQuestions = ref<Question[]>(props.availableQuestions || []);

// Función para cargar preguntas por categoría
const loadQuestionsByCategory = async (categoryId: number) => {
  if (!categoryId) {
    availableQuestions.value = [];
    return;
  }
  
  try {
    const response = await fetch(`/admin/questions/by-category?category_id=${categoryId}&exclude_id=${props.question.id}`);
    
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

// Watcher para cargar preguntas cuando cambie la categoría
watch(() => form.category_id, (newCategoryId) => {
  if (newCategoryId && hasDependency.value) {
    loadQuestionsByCategory(newCategoryId);
  } else {
    availableQuestions.value = [];
  }
  // Limpiar la pregunta padre seleccionada al cambiar categoría
  if (form.show_condition?.parent_question_id) {
    form.show_condition.parent_question_id = null;
  }
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

// Cargar preguntas al montar el componente si ya hay dependencia
onMounted(() => {
  if (hasDependency.value && form.category_id) {
    loadQuestionsByCategory(form.category_id);
  }
});

// Agregar función para actualizar puntos totales
const updateTotalPoints = (totalPoints: number) => {
  form.points = totalPoints;
};

// Watcher para resetear opciones cuando cambia el tipo de pregunta
watch(() => form.question_type, (newType, oldType) => {
  if (newType !== oldType) {
    // Resetear opciones para tipos que no las necesitan
    if (!['select', 'radio', 'checkbox'].includes(newType)) {
      form.options = [];
    }
    
    // Limpiar placeholder para tipos que no lo necesitan
    if (!['text', 'textarea', 'number'].includes(newType)) {
      form.placeholder = '';
    }
    
    // Resetear min_value y max_value para tipos que no son number
    if (newType !== 'number') {
      form.min_value = undefined;
      form.max_value = undefined;
    }
    
    // Ajustar puntos según el tipo
    if (['select', 'radio', 'checkbox'].includes(newType)) {
      // Para tipos con opciones, los puntos se calculan automáticamente
      form.points = 0;
    } else {
      // Para otros tipos, establecer puntos por defecto
      form.points = 1;
    }
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
          <h2 class="text-lg sm:text-xl font-semibold text-foreground">Editar Pregunta</h2>
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
                    :exclude-question-id="question.id"
                    :errors="form.errors"
                  />
                </div>
                
                <!-- Asignación de Multas -->
                <div class="space-y-4">
                  <MultaAssignment 
                    v-if="props.multas && props.multas.length > 0"
                    v-model="hasMultaAssignment"
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
                  {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
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



