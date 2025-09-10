<script setup lang="ts">
import { ref, computed, watch, onMounted  } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Save, ArrowLeft, Plus, Minus } from 'lucide-vue-next';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem } from '@/types';
import QuestionDependency from '@/components/QuestionDependency.vue';
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

interface Props {
    question: Question;
    categories: Category[];
    availableQuestions?: Question[];
}

const props = withDefaults(defineProps<Props>(), {
    availableQuestions: () => []
});

// Variable reactiva para manejar dependencias - CORREGIR ESTA LÍNEA
const hasDependency = ref(false); // Inicializar como false temporalmente

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
    validation_rules: props.question.validation_rules,
    is_required: props.question.is_required,
    is_active: props.question.is_active
});

// Verificar y actualizar hasDependency después de inicializar el form
console.log('Debug - Question data:', {
    show_condition: props.question.show_condition,
    form_show_condition: form.show_condition,
    parent_question_id: form.show_condition?.parent_question_id
});

// DEBUG: Agregar console.log temporal para verificar los datos
console.log('Question data:', {
  show_condition: props.question.show_condition,
  parent_question_id: props.question.show_condition?.parent_question_id,
  hasDependency: hasDependency.value
});




// Establecer hasDependency basándose en el form inicializado
if (form.show_condition?.parent_question_id && 
    form.show_condition.parent_question_id !== null && 
    form.show_condition.parent_question_id !== undefined) {
    hasDependency.value = true;
    console.log('Debug - Setting hasDependency to true');
} else {
    console.log('Debug - hasDependency remains false');
}
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Preguntas', href: '/admin/questions' },
    { title: 'Editar Pregunta', current: true },
];

// Computed properties como en Create.vue
// const showOptions = computed(() => {
//     return ['select', 'radio' , 'checkbox'].includes(form.question_type);
// });

// const showMinMax = computed(() => {
//     return form.question_type === 'number';
// });

// const showPlaceholder = computed(() => {
//     return ['text', 'textarea', 'number'].includes(form.question_type);
// });

// Agregar computed para mostrar campo de puntos
const showPoints = computed(() => {
    // No mostrar puntos para tipos que tienen puntos individuales por opción
    return !['select', 'radio', 'checkbox'].includes(form.question_type);
});

// Remover las funciones addOption y removeOption ya que están en los componentes

const submitForm = () => {
    form.put(`/admin/questions/${props.question.id}`, {
        onSuccess: () => {
            router.visit('/admin/questions');
        }
    });
};

const goBack = () => {
    router.visit('/admin/questions');
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
    form.show_condition.parent_question_id = undefined;
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
  console.log('Mounting Edit component', {
    hasDependency: hasDependency.value,
    categoryId: form.category_id,
    showCondition: form.show_condition,
    originalQuestion: props.question
  });
  
  if (hasDependency.value && form.category_id) {
    loadQuestionsByCategory(form.category_id);
  }
});


// DEBUG: Agregar console.log temporal para verificar los datos
console.log('Question data:', {
  show_condition: props.question.show_condition,
  parent_question_id: props.question.show_condition?.parent_question_id,
  hasDependency: hasDependency.value
});


// Agregar función para actualizar puntos totales
const updateTotalPoints = (totalPoints: number) => {
  form.points = totalPoints;
};

// Watcher para resetear puntos cuando cambia el tipo de pregunta
watch(() => form.question_type, () => {
  if (!['select', 'radio', 'checkbox'].includes(form.question_type)) {
    form.points = 1;
  }
});
</script>

<template>
    <Head title="Editar Pregunta" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <!-- Header -->
            <div class="bg-card rounded-lg p-6 shadow-sm border border-border">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">
                    <div class="flex-1">
                        <h1 class="text-xl sm:text-2xl font-bold mb-2 text-foreground">Editar Pregunta</h1>
                        <p class="text-muted-foreground text-sm sm:text-base">Modifica los campos de la pregunta de evaluación</p>
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
                                        disabled
                                        id="category_id"
                                        v-model="form.category_id"
                                        class="w-full px-3 py-2 border border-input rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                                        required
                                    >
                                        <option value="">Seleccionar categoría</option>
                                        <option v-for="category in categories" :key="category.id" :value="category.id" >
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

                                <!-- Puntos y orden -->
                                <div class="grid grid-cols-2 gap-4">
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
                                    <div :class="showPoints ? '' : 'col-span-2'">
                                        <Label>Orden en la Categoría</Label>
                                        <div class="px-3 py-2 bg-muted border border-border rounded-md text-sm text-muted-foreground">
                                            Posición {{ form.order }} en {{ question.category?.name }}
                                        </div>
                                        <p class="text-xs text-muted-foreground mt-1">El orden se asigna automáticamente según la categoría</p>
                                    </div>
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
                                            <select 
                                                id="parent_question"
                                                v-model="form.show_condition.parent_question_id"
                                                class="w-full px-3 py-2 border border-input rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                                            >
                                                <option :value="null">Seleccionar pregunta</option>
                                                <option v-for="question in availableQuestions" :key="question.id" :value="question.id">
                                                    Orden {{ question.order }} - {{ question.question_text }}
                                                </option>
                                            </select>
                                            <InputError :message="form.errors['show_condition.parent_question_id']" />
                                        </div>
                                        
                                        <div>
                                            <Label for="condition_operator">Condición</Label>
                                            <select 
                                                id="condition_operator"
                                                v-model="form.show_condition.operator"
                                                class="w-full px-3 py-2 border border-input rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
                                            >
                                                <option value="equals">Es igual a</option>
                                                <option value="not_equals">No es igual a</option>
                                                <option value="contains">Contiene</option>
                                                <option value="greater_than">Mayor que</option>
                                                <option value="less_than">Menor que</option>
                                            </select>
                                            <InputError :message="form.errors['show_condition.operator']" />
                                        </div>
                                        
                                        <div>
                                            <Label for="condition_value">Valor esperado</Label>
                                            <Input 
                                                id="condition_value"
                                                v-model="form.show_condition.value"
                                                placeholder="Valor que debe tener la pregunta padre"
                                            />
                                            <InputError :message="form.errors['show_condition.value']" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Checkboxes -->
                                <div class="space-y-4">
                                    <div class="flex items-center space-x-2">
                                        <input 
                                            id="is_required"
                                            v-model="form.is_required"
                                            type="checkbox"
                                            class="rounded border-input text-primary focus:ring-ring"
                                        />
                                        <Label for="is_required">Pregunta obligatoria</Label>
                                    </div>
                                    
                                    <!-- <div class="flex items-center space-x-2">
                                        <input 
                                            id="is_active"
                                            v-model="form.is_active"
                                            type="checkbox"
                                            class="rounded border-input text-primary focus:ring-ring"
                                        />
                                        <Label for="is_active">Pregunta activa</Label>
                                    </div> -->
                                </div>

                                <!-- Botones -->
                                <div class="flex justify-end gap-3 pt-6">
                                    <Button @click="goBack" type="button" variant="outline">
                                        Cancelar
                                    </Button>
                                    <Button type="submit" :disabled="form.processing">
                                        <Save class="h-4 w-4 mr-2" />
                                        {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
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
                                        <strong>Número:</strong> Campo numérico con validación
                                    </div>
                                    <div>
                                        <strong>Casillas:</strong> Múltiples opciones seleccionables
                                    </div>
                                    <div>
                                        <strong>Sí/No:</strong> Pregunta de respuesta binaria
                                    </div>
                                </div>
                                
                                <div class="mt-6">
                                    <h4 class="font-medium text-foreground mb-2">Campo Orden</h4>
                                    <p class="text-sm text-muted-foreground">
                                        Define la secuencia en que aparecen las preguntas. Número menor = aparece primero.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>



