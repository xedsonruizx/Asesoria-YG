<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import route from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import CreateModal from './Create.vue';
import EditModal from './Edit.vue';
import DeleteModal from './Delete.vue';
import { Plus, Edit, Trash2, Eye, ChevronLeft, ChevronRight, FileText, Calendar, Download, AlertTriangle, RotateCcw, Power, PowerOff, Users } from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';

// Props del backend
interface Category {
  id: number;
  name: string;
  slug: string;
  description?: string;
  color?: string;
  icon?: string;
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
  show_condition?: {
    parent_question_id: number | null;
    operator: string;
    expected_value: any;
    value?: any;
  };
  validation_rules?: any;
  is_required: boolean;
  is_active: boolean;
  created_at: string;
  updated_at: string;
  deleted_at?: string;
  category: Category;
  has_answers: boolean;
  answers_count: number;
  status_text: string;
  multas: {
    id: number;
    name: string;
    description?: string;
    file_path?: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    deleted_at?: string;
    pivot: {
      evaluation_question_id: number;
      multa_id: number;
      trigger_condition: string;
      trigger_value?: string;
      is_active: number;
      created_at: string;
      updated_at: string;
    };
  }[];
}

interface Multa {
  id: number;
  name: string;
  description?: string;
}

interface PaginatedData<T> {
  data: T[];
  current_page: number;
  from: number;
  last_page: number;
  per_page: number;
  to: number;
  total: number;
}

interface Props {
  questions: Question[];
  categories: Category[];
  multas?: Multa[];
  stats?: {
    total: number;
    active: number;
    inactive: number;
    deleted: number;
  };
  filters?: {
    search?: string;
    category_id?: string;
    question_type?: string;
    is_active?: string;
    show_deleted?: string;
  };
}

const props = defineProps<Props>();

// Estados de los modales
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const selectedQuestion = ref<Question | null>(null);

// Estados de filtros
const searchQuery = ref(props.filters?.search || '');
const categoryFilter = ref(props.filters?.category_id?.toString() || '');
const typeFilter = ref(props.filters?.question_type || '');
const statusFilter = ref(props.filters?.is_active || '');
const showDeleted = ref(props.filters?.show_deleted === 'true');

// Obtener la página actual de Inertia
const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Preguntas',
        href: '/admin/questions',
    },
];

// Tipos de preguntas
const questionTypes = [
  { value: 'text', label: 'Texto' },
  { value: 'textarea', label: 'Área de texto' },
  { value: 'select', label: 'Selección' },
  { value: 'number', label: 'Número' },
  { value: 'checkbox', label: 'Casillas' },
  { value: 'yes_no', label: 'Sí/No' },
];

// Funciones
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const getTypeLabel = (type: string) => {
  const typeObj = questionTypes.find(t => t.value === type);
  return typeObj ? typeObj.label : type;
};

// Funciones de filtrado
const applyFilters = () => {
  const params: any = {};
  
  if (searchQuery.value) params.search = searchQuery.value;
  if (categoryFilter.value) params.category_id = categoryFilter.value;
  if (typeFilter.value) params.question_type = typeFilter.value;
  if (statusFilter.value) params.is_active = statusFilter.value;
  if (showDeleted.value) params.show_deleted = 'true';
  
  router.get('/admin/questions', params, {
    preserveState: true,
    replace: true
  });
};

const clearFilters = () => {
  searchQuery.value = '';
  categoryFilter.value = '';
  typeFilter.value = '';
  statusFilter.value = '';
  showDeleted.value = false;
  router.get('/admin/questions');
};

// Funciones de modales
const openCreateModal = () => {
  showCreateModal.value = true;
};

const openEditModal = (question: Question) => {
  selectedQuestion.value = question;
  showEditModal.value = true;
};

const openDeleteModal = (question: Question) => {
  selectedQuestion.value = question;
  showDeleteModal.value = true;
};

const closeModals = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  showDeleteModal.value = false;
  selectedQuestion.value = null;
};

// Funciones de acciones
// Función helper para obtener filtros actuales
const getCurrentFilters = () => {
  const params: any = {};
  if (searchQuery.value) params.search = searchQuery.value;
  if (categoryFilter.value) params.category_id = categoryFilter.value;
  if (typeFilter.value) params.question_type = typeFilter.value;
  if (statusFilter.value) params.is_active = statusFilter.value;
  if (showDeleted.value) params.show_deleted = 'true';
  return params;
};

// Funciones de acciones modificadas
const toggleStatus = (question: Question) => {
  const filters = getCurrentFilters();
  router.patch(`/admin/questions/${question.id}/toggle-status`, filters, {
    preserveScroll: true,
    onSuccess: () => {
      // La página se recargará automáticamente con filtros preservados
    }
  });
};

const restoreQuestion = (question: Question) => {
  const filters = getCurrentFilters();
  router.patch(`/admin/questions/${question.id}/restore`, filters, {
    preserveScroll: true,
    onSuccess: () => {
      // La página se recargará automáticamente con filtros preservados
    }
  });
};

const forceDeleteQuestion = (question: Question) => {
  if (confirm('¿Estás seguro de que quieres eliminar permanentemente esta pregunta? Esta acción no se puede deshacer.')) {
    const filters = getCurrentFilters();
    router.delete(`/admin/questions/${question.id}/force-delete`, {
      data: filters,
      preserveScroll: true,
      onSuccess: () => {
        // La página se recargará automáticamente con filtros preservados
      }
    });
  }
};

// Handlers para los eventos de los modales modificados
const handleCreated = () => {
  const filters = getCurrentFilters();
  router.get('/admin/questions', filters, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      closeModals();
    }
  });
};


// Función para obtener información de multa de una pregunta
const getMultaInfo = (question: Question) => {
  // Verificar si la pregunta tiene multas asociadas
  if (!question.multas || question.multas.length === 0) {
    return {
      hasMulta: false,
      text: 'Sin multa',
      class: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
    };
  }
  
  // Obtener la primera multa activa
  const activeMulta = question.multas.find(multa => multa.pivot.is_active);
  
  if (!activeMulta) {
    return {
      hasMulta: false,
      text: 'Sin multa activa',
      class: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
    };
  }
  
  return {
    hasMulta: true,
    text: activeMulta.name,
    class: 'bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-300',
    multa: activeMulta,
    condition: {
      multa_id: activeMulta.id,
      trigger_condition: activeMulta.pivot.trigger_condition,
      trigger_value: activeMulta.pivot.trigger_value
    }
  };
};


const handleUpdated = () => {
  const filters = getCurrentFilters();
  router.get('/admin/questions', filters, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      closeModals();
    }
  });
};

const handleDeleted = () => {
  const filters = getCurrentFilters();
  router.get('/admin/questions', filters, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      closeModals();
    }
  });
};

// Eliminar todas las funciones de paginación:
// - goToPage()
// - goToPreviousPage() 
// - goToNextPage()
// - getPageNumbers()

// Y eliminar la sección de paginación del template
const goToPage = (page: number) => {
  if (page >= 1 && page <= (props.questions?.last_page || 1)) {
    const params: any = { page };
    if (searchQuery.value) params.search = searchQuery.value;
    if (categoryFilter.value) params.category_id = categoryFilter.value;
    if (typeFilter.value) params.question_type = typeFilter.value;
    if (statusFilter.value) params.is_active = statusFilter.value;
    if (showDeleted.value) params.show_deleted = 'true';
    
    router.visit('/admin/questions', {
      data: params,
      preserveState: true,
      preserveScroll: true,
    });
  }
};

const goToPreviousPage = () => {
  const currentPage = props.questions?.current_page || 1;
  if (currentPage > 1) {
    goToPage(currentPage - 1);
  }
};

const goToNextPage = () => {
  const currentPage = props.questions?.current_page || 1;
  const lastPage = props.questions?.last_page || 1;
  if (currentPage < lastPage) {
    goToPage(currentPage + 1);
  }
};

// Generar números de página para mostrar
const getPageNumbers = () => {
  const currentPage = props.questions?.current_page || 1;
  const lastPage = props.questions?.last_page || 1;
  const pages: number[] = [];
  
  // Verificar que tenemos datos válidos
  if (!props.questions || !currentPage || !lastPage) {
    return [];
  }
  
  // Mostrar máximo 5 páginas
  let startPage = Math.max(1, currentPage - 2);
  let endPage = Math.min(lastPage, startPage + 4);
  
  // Ajustar si estamos cerca del final
  if (endPage - startPage < 4) {
    startPage = Math.max(1, endPage - 4);
  }
  
  for (let i = startPage; i <= endPage; i++) {
    pages.push(i);
  }
  
  return pages;
};

// Funciones de utilidad
const truncateText = (text: string, maxLength: number) => {
  if (text.length <= maxLength) return text;
  return text.substring(0, maxLength) + '...';
};

// Función para obtener información de dependencias
const getDependencyInfo = (question: Question) => {
  const dependencies = [];
  let canDelete = true;
  
  // Si tiene respuestas asociadas
  if (question.has_answers) {
    dependencies.push({
      text: `${question.answers_count} respuestas`,
      class: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300'
    });
    canDelete = false;
  }
  
  // Si esta pregunta depende de otra
  if (question.show_condition) {
    const parentQuestionId = question.show_condition.parent_question_id;
    if (parentQuestionId !== null && parentQuestionId !== undefined) {
      const parentQuestion = props.questions.find(q => q.id === parentQuestionId);
      const parentOrder = parentQuestion ? parentQuestion.order : parentQuestionId;
      dependencies.push({
        text: `Depende de: Orden ${parentOrder}`,
        class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300'
      });
    }
  }
  
  // Verificar si otras preguntas dependen de esta
  const dependents = props.questions.filter(q =>
    q.show_condition &&
    q.show_condition.parent_question_id === question.id
  );
  
  if (dependents.length > 0) {
    const dependentOrders = dependents.map(q => q.order).sort((a, b) => a - b);
    dependencies.push({
      text: `Dependen: Órdenes ${dependentOrders.join(', ')}`,
      class: 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-300'
    });
    canDelete = false;
  }
  
  if (dependencies.length === 0) {
    return {
      text: 'Sin dependencias',
      class: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
      canDelete: true
    };
  } else if (dependencies.length > 1) {
    return {
      text: dependencies.map(dep => dep.text).join(' • '),
      class: 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-300',
      canDelete
    };
  } else {
    return {
      text: dependencies[0].text,
      class: dependencies[0].class,
      canDelete
    };
  }
};
</script>

<template>
  <Head title="Preguntas" />
  
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <!-- Header con información de preguntas -->
      <div class="bg-card rounded-lg p-6 shadow-sm border border-border">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4 mb-4">
          <div class="flex-1">
            <h1 class="text-xl sm:text-2xl font-bold mb-2 text-foreground">Preguntas</h1>
            <p class="text-muted-foreground text-sm sm:text-base">Gestiona las preguntas de evaluación del sistema</p>
          </div>
          <button 
            @click="openCreateModal"
            class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground hover:bg-primary/90 rounded-md transition-colors font-medium"
          >
            <Plus class="h-4 w-4" />
            Nueva Pregunta
          </button>
        </div>
        
        <!-- Estadísticas básicas -->
        <div class="flex gap-2 sm:gap-4 flex-wrap">
          <div class="bg-muted px-3 sm:px-4 py-2 sm:py-3 rounded-md flex-1 sm:flex-none">
            <span class="font-semibold text-foreground text-xs sm:text-sm">Total: {{ props.stats?.total || props.questions.length }}</span>
          </div>
          <div class="bg-green-50 dark:bg-green-900/20 px-3 sm:px-4 py-2 sm:py-3 rounded-md flex-1 sm:flex-none">
            <span class="font-semibold text-green-700 dark:text-green-300 text-xs sm:text-sm">Activas: {{ props.questions.filter(q => q.is_active && !q.deleted_at).length }}</span>
          </div>
          <div class="bg-red-50 dark:bg-red-900/20 px-3 sm:px-4 py-2 sm:py-3 rounded-md flex-1 sm:flex-none">
            <span class="font-semibold text-red-700 dark:text-red-300 text-xs sm:text-sm">Inactivas: {{ props.questions.filter(q => !q.is_active && !q.deleted_at).length }}</span>
          </div>
          <div class="bg-orange-50 dark:bg-orange-900/20 px-3 sm:px-4 py-2 sm:py-3 rounded-md flex-1 sm:flex-none">
            <span class="font-semibold text-orange-700 dark:text-orange-300 text-xs sm:text-sm">Eliminadas: {{ props.questions.filter(q => q.deleted_at).length }}</span>
          </div>
          <div class="bg-blue-50 dark:bg-blue-900/20 px-3 sm:px-4 py-2 sm:py-3 rounded-md flex-1 sm:flex-none">
            <span class="font-semibold text-blue-700 dark:text-blue-300 text-xs sm:text-sm">Con Respuestas: {{ props.questions.filter(q => q.has_answers).length }}</span>
          </div>
          <div class="bg-purple-50 dark:bg-purple-900/20 px-3 sm:px-4 py-2 sm:py-3 rounded-md flex-1 sm:flex-none">
            <span class="font-semibold text-purple-700 dark:text-purple-300 text-xs sm:text-sm">Con Multas: {{ props.questions.filter(q => q.multa_condition?.multa_id).length }}</span>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-card rounded-lg p-4 shadow-sm border border-border">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
          <div class="sm:col-span-2 lg:col-span-1">
            <label class="block text-sm font-medium text-foreground mb-1">Buscar</label>
            <input
              v-model="searchQuery"
              @keyup.enter="applyFilters"
              type="text"
              placeholder="Buscar por pregunta..."
              class="w-full px-3 py-2 border border-input rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-foreground mb-1">Categoría</label>
            <select
              v-model="categoryFilter"
              @change="applyFilters"
              class="w-full px-3 py-2 border border-input rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
            >
              <option value="">Todas</option>
              <option v-for="category in categories" :key="category.id" :value="category.id.toString()">
                {{ category.name }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-foreground mb-1">Tipo</label>
            <select
              v-model="typeFilter"
              @change="applyFilters"
              class="w-full px-3 py-2 border border-input rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
            >
              <option value="">Todos</option>
              <option v-for="type in questionTypes" :key="type.value" :value="type.value">
                {{ type.label }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-foreground mb-1">Estado</label>
            <select
              v-model="statusFilter"
              @change="applyFilters"
              class="w-full px-3 py-2 border border-input rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
            >
              <option value="">Todos</option>
              <option value="true">Activas</option>
              <option value="false">Inactivas</option>
            </select>
          </div>
          <div class="flex items-end">
            <label class="flex items-center">
              <input
                v-model="showDeleted"
                @change="applyFilters"
                type="checkbox"
                class="rounded border-input text-primary shadow-sm focus:border-ring focus:ring focus:ring-ring focus:ring-opacity-50"
              />
              <span class="ml-2 text-sm text-foreground whitespace-nowrap">Mostrar eliminadas</span>
            </label>
          </div>
          <div class="flex items-end space-x-2 sm:col-span-2 lg:col-span-1">
            <button
              @click="applyFilters"
              class="flex-1 sm:flex-none px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors whitespace-nowrap"
            >
              Filtrar
            </button>
            <button
              @click="clearFilters"
              class="flex-1 sm:flex-none px-4 py-2 bg-muted text-muted-foreground rounded-md hover:bg-muted/80 transition-colors whitespace-nowrap"
            >
              Limpiar
            </button>
          </div>
        </div>
      </div>

      <!-- Información de paginación con controles -->
      <div v-if="props.questions?.data && props.questions.data.length > 0" class="bg-card rounded-lg p-4 shadow-sm border border-border">
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
          <!-- Información de registros -->
          <div class="text-muted-foreground text-sm">
            <span>Mostrando {{ props.questions.from }} a {{ props.questions.to }} de {{ props.questions.total }} preguntas</span>
          </div>
          
          <!-- Controles de paginación -->
          <div v-if="props.questions.last_page > 1" class="flex items-center gap-2">
            <!-- Botón anterior -->
            <button 
              @click="goToPreviousPage"
              :disabled="props.questions.current_page <= 1"
              class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <ChevronLeft class="h-4 w-4" />
              Anterior
            </button>
            
            <!-- Números de página -->
            <div class="flex items-center gap-1">
              <!-- Primera página si no está visible -->
              <template v-if="getPageNumbers()[0] > 1">
                <button 
                  @click="goToPage(1)"
                  class="inline-flex items-center justify-center w-8 h-8 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors"
                >
                  1
                </button>
                <span v-if="getPageNumbers()[0] > 2" class="text-muted-foreground px-1">...</span>
              </template>
              
              <!-- Páginas visibles -->
              <button 
                v-for="page in getPageNumbers()" 
                :key="page"
                @click="goToPage(page)"
                :class="[
                  'inline-flex items-center justify-center w-8 h-8 text-sm font-medium rounded-md transition-colors',
                  page === props.questions?.current_page 
                    ? 'bg-primary text-primary-foreground' 
                    : 'text-muted-foreground hover:text-foreground hover:bg-muted'
                ]"
              >
                {{ page }}
              </button>
              
              <!-- Última página si no está visible -->
              <template v-if="getPageNumbers()?.length && getPageNumbers()[getPageNumbers().length - 1] < props.questions.last_page">
                <span v-if="getPageNumbers()[getPageNumbers().length - 1] < props.questions.last_page - 1" class="text-muted-foreground px-1">...</span>
                <button 
                  @click="goToPage(props.questions.last_page)"
                  class="inline-flex items-center justify-center w-8 h-8 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors"
                >
                  {{ props.questions.last_page }}
                </button>
              </template>
            </div>
            
            <!-- Botón siguiente -->
            <button 
              @click="goToNextPage"
              :disabled="props.questions.current_page >= props.questions.last_page"
              class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Siguiente
              <ChevronRight class="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- Información simple de total de preguntas -->
      <div v-if="props.questions && props.questions.length > 0" class="bg-card rounded-lg p-4 shadow-sm border border-border">
        <div class="text-muted-foreground text-sm text-center">
          <span>Total: {{ props.questions.length }} preguntas</span>
        </div>
      </div>


  

      <!-- Lista de preguntas -->
      <div class="bg-card rounded-lg shadow-sm border border-border">
        <div v-if="props.questions && props.questions.length > 0">
          <!-- Encabezados de tabla - Solo visible en desktop -->
          <div class="hidden lg:grid lg:grid-cols-12 gap-4 p-4 bg-muted/50 rounded-t-lg border-b border-border font-medium text-sm text-muted-foreground">
            <div class="col-span-3">Pregunta</div>
            <div class="col-span-2">Categoría</div>
            <div class="col-span-1">Tipo</div>
            <div class="col-span-1">Puntos</div>
            <div class="col-span-2">Dependencias</div>
            <div class="col-span-1">Multas</div>
            <div class="col-span-2">Acciones</div>
          </div>
          
          <!-- Filas de datos -->
          <div class="divide-y divide-border">
            <div 
              v-for="question in props.questions" 
              :key="question.id"
              :class="[
                'p-3 sm:p-4 hover:bg-muted/50 transition-colors group',
                { 'bg-red-50 dark:bg-red-900/10': question.deleted_at }
              ]"
            >
              <!-- Layout Desktop (lg y superior) -->
              <div class="hidden lg:grid lg:grid-cols-12 lg:gap-3 lg:items-center">
                <!-- Pregunta -->
                <div class="col-span-3">
                  <div class="space-y-1">
                    <div class="flex items-center gap-2">
                      <span class="text-xs text-muted-foreground font-mono bg-muted px-2 py-1 rounded">
                        #{{ question.order }}
                      </span>
                      <div class="flex items-center gap-2">
                        <span :class="[
                          'inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium',
                          question.is_active 
                            ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300'
                            : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300'
                        ]">
                          <div :class="[
                            'w-1.5 h-1.5 rounded-full',
                            question.is_active ? 'bg-green-500' : 'bg-red-500'
                          ]"></div>
                          {{ question.is_active ? 'Activa' : 'Inactiva' }}
                        </span>
                        <span v-if="question.deleted_at" class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-300">
                          <AlertTriangle class="h-3 w-3 mr-1" />
                          Eliminada
                        </span>
                      </div>
                    </div>
                    <p class="font-medium text-sm text-foreground leading-tight">
                      {{ truncateText(question.question_text, 80) }}
                    </p>
                  </div>
                </div>

                <!-- Categoría -->
                <div class="col-span-2">
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                    {{ question.category?.name || 'Sin categoría' }}
                  </span>
                </div>

                <!-- Tipo -->
                <div class="col-span-1">
                  <span class="text-xs text-muted-foreground">
                    {{ getTypeLabel(question.question_type) }}
                  </span>
                </div>

                <!-- Puntos -->
                <div class="col-span-1">
                  <span class="font-medium text-xs text-foreground">
                    {{ question.points }} pts
                  </span>
                </div>

                <!-- Dependencias -->
                <div class="col-span-2">
                  <span :class="[
                    'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                    getDependencyInfo(question).class
                  ]">
                    {{ getDependencyInfo(question).text }}
                  </span>
                </div>
                
                <!-- Multas -->
                <div class="col-span-1">
                  <span :class="[
                    'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                    getMultaInfo(question).class
                  ]">
                    {{ getMultaInfo(question).text }}
                  </span>
                </div>

                <!-- Acciones -->
                <div class="col-span-2">
                  <div class="flex items-center gap-1">
                 
                    
                    <button
                      v-if="!question.deleted_at"
                      @click="toggleStatus(question)"
                      :class="[
                        'inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium rounded-md transition-colors border border-input',
                        question.is_active 
                          ? 'text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20'
                          : 'text-green-600 hover:text-green-700 hover:bg-green-50 dark:hover:bg-green-900/20'
                      ]"
                      :title="question.is_active ? 'Desactivar' : 'Activar'"
                    >
                      <component :is="question.is_active ? PowerOff : Power" class="h-4 w-4" />
                    </button>
                       <button
                      v-if="!question.deleted_at"
                      @click="openEditModal(question)"
                      class="inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors border border-input"
                      title="Editar"
                    >
                      <Edit class="h-4 w-4" />
                    </button>

                    <button
                      v-if="!question.deleted_at && getDependencyInfo(question).canDelete"
                      @click="openDeleteModal(question)"
                      class="inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors border border-input"
                      title="Eliminar"
                    >
                      <Trash2 class="h-4 w-4" />
                    </button>
                    
                    <button
                      v-if="question.deleted_at"
                      @click="restoreQuestion(question)"
                      class="inline-flex items-center justify-center w-8 h-8 text-green-600 hover:text-green-700 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-md transition-colors"
                      title="Restaurar"
                    >
                      <RotateCcw class="h-4 w-4" />
                    </button>
                    
                    <button
                      v-if="question.deleted_at"
                      @click="forceDeleteQuestion(question)"
                      class="inline-flex items-center justify-center w-8 h-8 text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors"
                      title="Eliminar Permanente"
                    >
                      <Trash2 class="h-3 w-3" />
                    </button>
                  </div>
                </div>
              </div>

              <!-- Layout Móvil y Tablet (hasta lg) -->
              <div class="lg:hidden">
                <!-- Pregunta Principal -->
                <div class="flex items-start gap-3 mb-3">
                  <div class="flex-shrink-0 w-8 h-8 bg-primary/10 text-primary rounded-full flex items-center justify-center text-sm font-medium">
                    {{ question.order }}
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="font-medium text-foreground text-sm leading-tight mb-2">
                      {{ question.question_text }}
                    </p>
                    
                    <!-- Estados -->
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                      <span :class="[
                        'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                        question.is_active 
                          ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300'
                          : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300'
                      ]">
                        <component :is="question.is_active ? Power : PowerOff" class="h-3 w-3 mr-1" />
                        {{ question.is_active ? 'Activa' : 'Inactiva' }}
                      </span>
                      <span v-if="question.deleted_at" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800 dark:bg-orange-900/20 dark:text-orange-300">
                        <AlertTriangle class="h-3 w-3 mr-1" />
                        Eliminada
                      </span>
                    </div>
                  </div>
                </div>
                
                <!-- Información Adicional -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-4">
                  <div class="bg-muted/30 rounded-lg p-2">
                    <div class="text-xs text-muted-foreground mb-1">Categoría</div>
                    <div class="text-xs font-medium text-foreground truncate">
                      {{ question.category?.name || 'Sin categoría' }}
                    </div>
                  </div>
                  
                  <div class="bg-muted/30 rounded-lg p-2">
                    <div class="text-xs text-muted-foreground mb-1">Tipo</div>
                    <div class="text-xs font-medium text-foreground">
                      {{ getTypeLabel(question.question_type) }}
                    </div>
                  </div>
                  
                  <div class="bg-muted/30 rounded-lg p-2">
                    <div class="text-xs text-muted-foreground mb-1">Puntos</div>
                    <div class="text-xs font-medium text-foreground">
                      {{ question.points }} pts
                    </div>
                  </div>
                  
                  <div class="bg-muted/30 rounded-lg p-2">
                    <div class="text-xs text-muted-foreground mb-1">Dependencias</div>
                    <div class="text-xs font-medium" :class="getDependencyInfo(question).class.includes('green') ? 'text-green-600' : getDependencyInfo(question).class.includes('red') ? 'text-red-600' : 'text-yellow-600'">
                      {{ getDependencyInfo(question).text }}
                    </div>
                  </div>
                  
                  <div class="bg-muted/30 rounded-lg p-2 col-span-2 sm:col-span-4">
                    <div class="text-xs text-muted-foreground mb-1">Multas </div>
                    <div class="text-xs font-medium" :class="getMultaInfo(question).hasMulta ? 'text-orange-600' : 'text-gray-600'">
                      {{ getMultaInfo(question).text }}
                    </div>
                  </div>
                </div>
                
                <!-- Botones de Acción Móvil -->
                <div class="flex flex-col sm:flex-row gap-2">
                  <button
                    v-if="!question.deleted_at"
                    @click="toggleStatus(question)"
                    :class="[
                      'flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium rounded-md transition-colors border border-input',
                      question.is_active
                        ? 'text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20'
                        : 'text-green-600 hover:text-green-700 hover:bg-green-50 dark:hover:bg-green-900/20'
                    ]"
                  >
                    <component :is="question.is_active ? PowerOff : Power" class="h-4 w-4" />
                    {{ question.is_active ? 'Desactivar' : 'Activar' }}
                  </button>

                  <button
                    @click="openEditModal(question)"
                    class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors border border-input"
                  >
                    <Edit class="w-4 h-4" />
                    Editar
                  </button>

                  <button
                    v-if="!question.deleted_at && getDependencyInfo(question).canDelete"
                    @click="openDeleteModal(question)"
                    class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors border border-input"
                  >
                    <Trash2 class="h-4 w-4" />
                    Eliminar
                  </button>
                  
                  <button
                    v-if="question.deleted_at"
                    @click="restoreQuestion(question)"
                    class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-green-600 hover:text-green-700 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-md transition-colors border border-input"
                  >
                    <RotateCcw class="h-4 w-4" />
                    Restaurar
                  </button>
                  
                  <button
                    v-if="question.deleted_at"
                    @click="forceDeleteQuestion(question)"
                    class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors border border-input"
                  >
                    <Trash2 class="h-4 w-4" />
                    Eliminar Permanente
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Estado vacío -->
        <div v-else class="p-8 text-center">
          <div class="text-muted-foreground">
            <FileText class="h-12 w-12 mx-auto mb-4 opacity-50" />
            <p class="text-lg font-medium mb-2">No hay preguntas</p>
            <p class="text-sm">Comienza creando tu primera pregunta de evaluación</p>
          </div>
          <button 
            @click="openCreateModal"
            class="mt-4 inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground hover:bg-primary/90 rounded-md transition-colors font-medium"
          >
            <Plus class="h-4 w-4" />
            Nueva Pregunta
          </button>
        </div>
      </div>
    </div>


  </AppLayout>

    <!-- Modales -->
    <CreateModal 
      v-if="showCreateModal" 
      @close="closeModals" 
      @created="handleCreated"
      :categories="categories"
      :multas="multas"
    />
    
    <EditModal 
      v-if="showEditModal && selectedQuestion" 
      :question="selectedQuestion"
      @close="closeModals" 
      @updated="handleUpdated"
      :categories="categories"
      :multas="multas"
    />
    
    <DeleteModal 
      v-if="showDeleteModal && selectedQuestion" 
      :question="selectedQuestion"
      @close="closeModals" 
      @deleted="handleDeleted"
    />

</template>
