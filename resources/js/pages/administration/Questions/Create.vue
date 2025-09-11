<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Save, Plus, Minus, ArrowLeft } from 'lucide-vue-next';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import QuestionDependency from '@/components/QuestionDependency.vue';
import MultaAssignment from '@/components/MultaAssignment.vue';
import { type BreadcrumbItem } from '@/types';
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
}

const props = withDefaults(defineProps<Props>(), {
  categories: () => [],
  availableQuestions: () => [],
});

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

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Preguntas', href: '/admin/questions' },
  { title: 'Crear Nueva Pregunta', href: '/admin/questions/create' },
];

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

// Remover las funciones addOption y removeOption ya que están en los componentes

const submitForm = () => {
  form.post('/admin/questions', {
    onSuccess: () => {
      router.visit('/admin/questions');
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
watch(() => form.question_type, (newType) => {
  if (['select', 'radio', 'checkbox'].includes(newType)) {
    // Para tipos con opciones, los puntos se calcularán automáticamente
    form.points = 0;
  } else {
    // Para otros tipos, establecer puntos por defecto
    form.points = 1;
  }
});
const goBack = () => {
  router.visit('/admin/questions');
};

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
  <Head title="Crear Nueva Pregunta" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="space-y-6 p-6">
      <!-- Header -->
      <div class="bg-card rounded-lg p-6 shadow-sm border border-border">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">
          <div class="flex-1">
            <h1 class="text-xl sm:text-2xl font-bold mb-2 text-foreground">Crear Nueva Pregunta</h1>
            <p class="text-muted-foreground text-sm sm:text-base">Completa los campos para crear una nueva pregunta de evaluación</p>
          </div>
          <Button @click="goBack" variant="outline" class="inline-flex items-center gap-2">
            <ArrowLeft class="h-4 w-4" />
            Volver
          </Button>
        </div>
      </div>

      <!-- Formulario -->
      <div class="bg-card rounded-lg shadow-sm border border-border">
        <div class="p-6">
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Formulario principal -->
            <div class="lg:col-span-2">
              <form @submit.prevent="submitForm" class="space-y-6">
                <!-- Categoría -->
                <div>
                  <Label for="category_id">Categoría *</Label>
                  <select 
                    id="category_id"
                    v-model="form.category_id"
                    class="w-full px-3 py-2 border border-input rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                    required
                  >
                    <option value="null" disabled>Seleccionar categoría</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">
                      {{ category.name }}
                    </option>
                  </select>
                  <InputError :message="form.errors.category_id" />
                </div>

                <!-- Texto de la pregunta -->
                <div>
                  <Label for="question_text">Texto de la Pregunta *</Label>
                  <textarea 
                    id="question_text"
                    v-model="form.question_text"
                    rows="3"
                    class="w-full px-3 py-2 border border-input rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                    placeholder="Escribe el texto de la pregunta..."
                    required
                  ></textarea>
                  <InputError :message="form.errors.question_text" />
                </div>

                <!-- Tipo de pregunta -->
                <div>
                  <Label for="question_type">Tipo de Pregunta *</Label>
                  <select 
                    id="question_type"
                    v-model="form.question_type"
                    class="w-full px-3 py-2 border border-input rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                    required
                  >
                    <option value="text">Texto</option>
                    <option value="textarea">Texto largo</option>
                    <option value="select">Selección (dropdown)</option>
                    <option value="radio">Selección única (radio buttons)</option>
                    <option value="number">Número</option>
                    <option value="checkbox">Selección múltiple (checkboxes)</option>
                  </select>
                  <InputError :message="form.errors.question_type" />
                </div>



                <!-- Campos específicos del tipo de pregunta -->
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

                <!-- Puntos -->
                <div v-if="showPoints">
                  <Label for="points">Puntos *</Label>
                  <Input 
                    id="points"
                    v-model.number="form.points"
                    type="number"
                    min="0"
                    max="100"
                    required
                  />
                  <InputError :message="form.errors.points" />
                </div>

                <!-- Orden -->
                <div>
                  <Label for="order">Orden *</Label>
                  <Input 
                    id="order"
                    v-model.number="form.order"
                    type="number"
                    min="1"
                    required
                  />
                  <InputError :message="form.errors.order" />
                  <p class="text-sm text-muted-foreground mt-1">
                    El orden debe ser único dentro de la categoría seleccionada
                  </p>
                </div>



                <!-- Dependencias de pregunta -->
                <QuestionDependency
                  v-model="hasDependency"
                  v-model:show-condition="form.show_condition"
                  :category-id="form.category_id"
                  :errors="form.errors"
                />

                <!-- Asignación de multas -->
                <MultaAssignment
                  v-model="hasMultaAssignment"
                  v-model:multa-condition="form.multa_condition"
                  :errors="form.errors"
                />




                <!-- Botones -->
                <div class="flex justify-end gap-3 pt-6">
                  <Button @click="goBack" type="button" variant="outline">
                    Cancelar
                  </Button>
                  <Button type="submit" :disabled="form.processing">
                    <Save class="h-4 w-4 mr-2" />
                    {{ form.processing ? 'Guardando...' : 'Guardar Pregunta' }}
                  </Button>
                </div>
              </form>
            </div>

            <!-- Sidebar con información -->
            <div class="lg:col-span-1">
              <div class="bg-muted rounded-lg p-4">
                <h3 class="font-medium text-foreground mb-4">Tipos de Pregunta</h3>
                <div class="space-y-3 text-sm">
                  <div>
                    <strong>Texto:</strong> Campo de texto simple
                  </div>
                  <div>
                    <strong>Texto largo:</strong> Área de texto para respuestas extensas
                  </div>
                  <div>
                    <strong>Selección:</strong> Lista desplegable con opciones
                  </div>
                  <div>
                    <strong>Radio:</strong> Selección única con opciones personalizables
                  </div>
                  <div>
                    <strong>Número:</strong> Campo numérico con validación
                  </div>
                  <div>
                    <strong>Casillas:</strong> Múltiples opciones seleccionables
                  </div>
                  <!-- <div>
                    <strong>Sí/No:</strong> Pregunta de respuesta binaria
                  </div> -->
                </div>
                
                <div class="mt-6">
                  <!-- <h4 class="font-medium text-foreground mb-2">Campo Orden</h4>
                  <p class="text-sm text-muted-foreground">
                    Define la secuencia en que aparecen las preguntas. Número menor = aparece primero.
                  </p> -->
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

   
    </div>
  </AppLayout>
</template>
