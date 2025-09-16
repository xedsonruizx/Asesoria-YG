<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Plus, Edit, Trash2, Eye, RotateCcw, FileText, Users, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';
import { Download } from 'lucide-vue-next';


// Props del backend
interface User {
  id: number;
  name: string;
  email: string;
}

interface Evaluation {
  id: number;
  title: string;
  description?: string;
  status: 'draft' | 'in_progress' | 'completed';
  total_score: number;
  total_progress: number;
  completed_at?: string;
  created_at: string;
  updated_at: string;
  user: User;
}

interface EvaluationsData {
  data: Evaluation[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
}

interface Stats {
  total: number;
  completed: number;
  in_progress: number;
  draft: number;
}

const props = withDefaults(defineProps<{
  evaluations: EvaluationsData;
  stats: Stats;
  filters?: {
    user?: string;
  };
}>(), {
  evaluations: () => ({ data: [], current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 }),
  stats: () => ({ total: 0, completed: 0, in_progress: 0, draft: 0 }),
  filters: () => ({})
});

// Estado para el modal de eliminación
const showDeleteModal = ref(false);
const evaluationToDelete = ref<Evaluation | null>(null);

// Estado para filtros - inicializar con valores del backend
const filters = ref({
  user: props.filters?.user || ''
});
const showFilters = ref(false);

// Función para aplicar filtros
const applyFilters = () => {
  const params: any = { page: 1 }; // Resetear a página 1 cuando se aplican filtros
  
  if (filters.value.user.trim()) {
    params.user = filters.value.user.trim();
  }
  
  router.visit('/evaluations', {
    data: params,
    preserveState: true,
    preserveScroll: true,
  });
};

// Función para limpiar filtros
const clearFilters = () => {
  filters.value = {
    user: ''
  };
  
  router.visit('/evaluations', {
    data: { page: 1 },
    preserveState: true,
    preserveScroll: true,
  });
};

// Función para alternar la visibilidad de filtros
const toggleFilters = () => {
  showFilters.value = !showFilters.value;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Evaluaciones',
        href: '/evaluations',
    },
];

// Funciones
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const getStatusColor = (status: string) => {
  switch (status) {
    case 'completed':
      return 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300';
    case 'in_progress':
      return 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300';
    case 'draft':
      return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300';
    default:
      return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300';
  }
};

const getStatusText = (status: string) => {
  switch (status) {
    case 'completed':
      return 'Completada';
    case 'in_progress':
      return 'En Progreso';
    case 'draft':
      return 'Borrador';
    default:
      return status;
  }
};

const createEvaluation = () => {
  router.visit('/evaluations/create');
};

const editEvaluation = (id: number) => {
  router.visit(`/evaluations/${id}/edit`);
};

const viewEvaluation = (evaluation: Evaluation) => {
  router.visit(`/evaluations/${evaluation.id}`);
};

const resetEvaluation = (id: number) => {
  if (confirm('¿Estás seguro de que quieres reiniciar esta evaluación? Se perderán todas las respuestas.')) {
    router.post(`/evaluations/${id}/reset`);
  }
};

const confirmDelete = (evaluation: Evaluation) => {
  evaluationToDelete.value = evaluation;
  showDeleteModal.value = true;
};

const closeDeleteModal = () => {
  showDeleteModal.value = false;
  evaluationToDelete.value = null;
};

const handleDeleteConfirm = (evaluationId: number) => {
  router.delete(`/evaluations/${evaluationId}`, {
    onSuccess: () => {
      console.log('Evaluación eliminada exitosamente');
      closeDeleteModal();
    },
    onError: (errors) => {
      console.error('Error al eliminar la evaluación:', errors);
      alert('Error al eliminar la evaluación. Inténtalo de nuevo.');
    }
  });
};

// Funciones de paginación
const goToPage = (page: number) => {
  if (page >= 1 && page <= (props.evaluations?.last_page || 1)) {
    const params: any = { page };
    
    // Preservar filtro de usuario activo
    if (filters.value.user.trim()) {
      params.user = filters.value.user.trim();
    }
    
    router.visit('/evaluations', {
      data: params,
      preserveState: true,
      preserveScroll: true,
    });
  }
};

const goToPreviousPage = () => {
  const currentPage = props.evaluations?.current_page || 1;
  if (currentPage > 1) {
    goToPage(currentPage - 1);
  }
};

const goToNextPage = () => {
  const currentPage = props.evaluations?.current_page || 1;
  const lastPage = props.evaluations?.last_page || 1;
  if (currentPage < lastPage) {
    goToPage(currentPage + 1);
  }
};

// Generar números de página para mostrar
const getPageNumbers = () => {
  const currentPage = props.evaluations?.current_page || 1;
  const lastPage = props.evaluations?.last_page || 1;
  const pages: number[] = [];
  
  // Verificar que tenemos datos válidos
  if (!props.evaluations || lastPage <= 1) {
    return pages;
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

const truncateText = (text: string, maxLength: number = 30) => {
  // Verificar que text existe y es una cadena válida
  if (!text || typeof text !== 'string') {
    return '';
  }
  return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
};

const getCategoryDisplayName = (categorySlug: string): string => {
  const categoryNames: Record<string, string> = {
    'rrhh': 'RRHH',
    'legal': 'Legal',
    'financiero': 'Financiero',
    'operacional': 'Operacional'
  };
  return categoryNames[categorySlug] || categorySlug;
};

const getCategoryMaxScore = (categorySlug: string): number => {
  // Basado en el seeder, todas las categorías tienen max_score de 100
  return 100;
};

const getCategoryPercentage = (score: number, maxScore: number): number => {
  return maxScore > 0 ? Math.round((score / maxScore) * 100) : 0;
};

const getCategoryBarColor = (score: number, maxScore: number): string => {
  const percentage = getCategoryPercentage(score, maxScore);
  
  if (percentage >= 90) return 'bg-green-500'; // Excelente
  if (percentage >= 80) return 'bg-green-400'; // Bueno
  if (percentage >= 60) return 'bg-yellow-500'; // Regular
  if (percentage >= 40) return 'bg-orange-500'; // Deficiente
  return 'bg-red-500'; // Crítico
};


const downloadPdf = (evaluationId: number) => {
    window.open(`/evaluations/${evaluationId}/pdf`, '_blank');
}

</script>

<template>
    <Head title="Evaluaciones" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header con información de evaluaciones -->
            <div class="bg-card rounded-lg p-6 shadow-sm border border-border">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4 mb-4">
                    <div class="flex-1">
                        <h1 class="text-xl sm:text-2xl font-bold mb-2 text-foreground">Gestión de Evaluaciones</h1>
                        <p class="text-muted-foreground text-sm sm:text-base">Administra y visualiza todas las evaluaciones del sistema</p>
                    </div>
                    <!-- <button 
                        @click="createEvaluation"
                        class="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2 bg-primary text-primary-foreground hover:bg-primary/90 rounded-md transition-colors font-medium text-xs sm:text-sm w-full sm:w-auto"
                    >
                        <Plus class="h-4 w-4 flex-shrink-0" />
                        <span class="truncate">Nueva Evaluación</span>
                    </button> -->
                </div>
                
                <!-- Estadísticas -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-muted px-3 sm:px-4 py-2 sm:py-3 rounded-md">
                        <div class="flex items-center gap-2">
                            <FileText class="h-4 w-4 text-blue-600" />
                            <div>
                                <p class="text-xs text-muted-foreground">Total</p>
                                <p class="font-semibold text-foreground">{{ props.stats?.total || 0 }}</p>
                              
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección de Filtros -->
            <div class="bg-card rounded-lg p-4 shadow-sm border border-border">
                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
                    <h2 class="text-lg font-semibold text-foreground">Filtro de Usuario</h2>
                    <button 
                        @click="toggleFilters"
                        class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z" />
                        </svg>
                        {{ showFilters ? 'Ocultar Filtro' : 'Mostrar Filtro' }}
                    </button>
                </div>
                
                <div v-show="showFilters" class="space-y-4">
                    <div class="grid grid-cols-1 gap-4">
                        <!-- Filtro de usuario por texto -->
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-2">Buscar por Usuario</label>
                            <input 
                                v-model="filters.user"
                                type="text" 
                                placeholder="Escribe el nombre o email del usuario..."
                                class="w-full px-3 py-2 border border-border rounded-md bg-background text-foreground placeholder-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                                @keyup.enter="applyFilters"
                            >
                        </div>
                    </div>
                    
                    <!-- Botones de acción -->
                    <div class="flex flex-col sm:flex-row gap-2">
                        <button 
                            @click="applyFilters"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-primary text-primary-foreground hover:bg-primary/90 rounded-md transition-colors font-medium text-sm"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                            Buscar
                        </button>
                        <button 
                            @click="clearFilters"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-muted text-muted-foreground hover:bg-muted/80 hover:text-foreground rounded-md transition-colors font-medium text-sm"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Limpiar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Información de paginación con controles -->
            <div v-if="props.evaluations?.data && props.evaluations.data.length > 0" class="bg-card rounded-lg p-4 shadow-sm border border-border">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <!-- Información de registros -->
                    <div class="text-muted-foreground text-sm">
                        <span>Mostrando {{ props.evaluations.from }} a {{ props.evaluations.to }} de {{ props.evaluations.total }} evaluaciones</span>
                    </div>
                    
                    <!-- Controles de paginación -->
                    <div v-if="props.evaluations.last_page > 1" class="flex items-center gap-2">
                        <!-- Botón anterior -->
                        <button 
                            @click="goToPreviousPage"
                            :disabled="props.evaluations.current_page <= 1"
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
                                    page === props.evaluations?.current_page 
                                        ? 'bg-primary text-primary-foreground' 
                                        : 'text-muted-foreground hover:text-foreground hover:bg-muted'
                                ]"
                            >
                                {{ page }}
                            </button>
                            
                            <!-- Última página si no está visible -->
                            <template v-if="getPageNumbers().length > 0 && getPageNumbers()[getPageNumbers().length - 1] < props.evaluations.last_page">
                                <span v-if="getPageNumbers()[getPageNumbers().length - 1] < props.evaluations.last_page - 1" class="text-muted-foreground px-1">...</span>
                                <button 
                                    @click="goToPage(props.evaluations.last_page)"
                                    class="inline-flex items-center justify-center w-8 h-8 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors"
                                >
                                    {{ props.evaluations.last_page }}
                                </button>
                            </template>
                        </div>
                        
                        <!-- Botón siguiente -->
                        <button 
                            @click="goToNextPage"
                            :disabled="props.evaluations.current_page >= props.evaluations.last_page"
                            class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Siguiente
                            <ChevronRight class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabla de evaluaciones -->
            <div class="bg-card rounded-lg overflow-hidden shadow-sm border border-border">
                <div v-if="props.evaluations?.data && props.evaluations.data.length > 0">
                    <!-- Encabezados -->
                    <div class="bg-muted/30 p-4 border-b border-border">
                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 font-semibold text-foreground">
                            <div class="md:col-span-2">Usuario</div>
                            <div class="hidden md:block">Estado</div>
                            <div class="hidden md:block">Puntuación por Categoría</div>
                            <div class="hidden md:block">Acciones</div>
                        </div>
                    </div>
                    
                    <!-- Filas de datos -->
                    <div>
                        <div 
                            v-for="evaluation in props.evaluations.data.filter(e => e.status === 'completed')" 
                            :key="evaluation.id"
                            class="border-b border-border p-4 hover:bg-muted/50 transition-colors group"
                        >
                            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-start md:items-center">
                                <!-- Usuario -->
                                <div class="md:col-span-2 cursor-pointer">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-muted flex items-center justify-center">
                                                <Users class="h-5 w-5 text-muted-foreground" />
                                            </div>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="font-semibold text-foreground mb-1 group-hover:text-primary transition-colors truncate">
                                                {{ evaluation.user.name }}
                                            </div>
                                            <div class="text-sm text-muted-foreground truncate">
                                                {{ evaluation.user.email }}
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Información adicional en móvil -->
                                    <div class="md:hidden mt-3 space-y-3">
                                        <!-- Estado -->
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-muted-foreground">Estado:</span>
                                            <span :class="getStatusColor(evaluation.status)" class="px-2 py-1 rounded-full text-xs font-medium">
                                                {{ getStatusText(evaluation.status) }}
                                            </span>
                                        </div>
                                        
                                        <!-- Puntuación por categorías en móvil -->
                                        <div v-if="evaluation.category_details && evaluation.category_details.length > 0" class="space-y-2">
                                            <div class="text-xs text-muted-foreground font-medium">Puntuación por Categoría:</div>
                                            <div class="space-y-2">
                                                <div 
                                                    v-for="category in evaluation.category_details" 
                                                    :key="category.slug"
                                                    class="flex items-center justify-between text-xs"
                                                >
                                                    <div class="flex items-center gap-2 min-w-0 flex-1">
                                                        <span class="font-medium text-foreground truncate">
                                                            {{ getCategoryDisplayName(category.slug) }}
                                                        </span>
                                                        <div class="flex-1 bg-gray-200 rounded-full h-1.5 min-w-[30px]">
                                                            <div 
                                                                :class="getCategoryBarColor(category.obtained_points || 0, category.total_possible_points || 1)"
                                                                class="h-1.5 rounded-full transition-all duration-300"
                                                                :style="{ width: getCategoryPercentage(category.obtained_points || 0, category.total_possible_points || 1) + '%' }"
                                                            ></div>
                                                        </div>
                                                    </div>
                                                    <div class="text-right ml-2 flex-shrink-0">
                                                        <div class="font-semibold text-foreground">
                                                            {{ getCategoryPercentage(category.obtained_points || 0, category.total_possible_points || 1) }}%
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Total general en móvil -->
                                                <div class="pt-2 border-t border-border">
                                                    <div class="flex items-center justify-between text-xs">
                                                        <span class="font-bold text-foreground">Total:</span>
                                                        <div class="font-bold text-foreground">
                                                            {{ evaluation.total_percentage || 0 }}%
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Acciones en móvil -->
                                        <div class="flex flex-wrap gap-2 pt-2">
                                            <button 
                                                @click="viewEvaluation(evaluation)"
                                                class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded transition-colors"
                                            >
                                                <Eye class="h-3 w-3" />
                                                Ver
                                            </button>
                                            <button 
                                                @click="downloadPdf(evaluation.id)"
                                                class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-green-600 hover:text-green-800 hover:bg-green-50 rounded transition-colors"
                                            >
                                                <Download class="h-3 w-3" />
                                                PDF
                                            </button>
                                            <button 
                                                @click="resetEvaluation(evaluation.id)"
                                                class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-orange-600 hover:text-orange-800 hover:bg-orange-50 rounded transition-colors"
                                            >
                                                <RotateCcw class="h-3 w-3" />
                                                Reiniciar
                                            </button>
                                            <button 
                                                @click="confirmDelete(evaluation)"
                                                class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-red-600 hover:text-red-800 hover:bg-red-50 rounded transition-colors"
                                            >
                                                <Trash2 class="h-3 w-3" />
                                                Eliminar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Estado (solo desktop) -->
                                <div class="hidden md:block">
                                    <span :class="getStatusColor(evaluation.status)" class="inline-flex px-2 py-1 text-xs font-semibold rounded-full">
                                        {{ getStatusText(evaluation.status) }}
                                    </span>
                                </div>
                                
                                <!-- Puntuación por categorías (solo desktop) -->
                             <!-- Puntuación por Categoría -->
                                  <div class="hidden md:block">
                                      <div v-if="evaluation.category_details && evaluation.category_details.length > 0" class="space-y-2">
                                          <div 
                                              v-for="category in evaluation.category_details" 
                                              :key="category.slug"
                                              class="flex items-center justify-between text-xs"
                                          >
                                              <div class="flex items-center gap-2 min-w-0 flex-1">
                                                  <span class="font-medium text-foreground truncate">
                                                      {{ getCategoryDisplayName(category.slug) }}
                                                  </span>
                                                  <div class="flex-1 bg-gray-200 rounded-full h-1.5 min-w-[40px]">
                                                      <div 
                                                          :class="getCategoryBarColor(category.obtained_points || 0, category.total_possible_points || 1)"
                                                          class="h-1.5 rounded-full transition-all duration-300"
                                                          :style="{ width: getCategoryPercentage(category.obtained_points || 0, category.total_possible_points || 1) + '%' }"
                                                      ></div>
                                                  </div>
                                              </div>
                                              <div class="text-right ml-2 flex-shrink-0">
                                                  <div class="font-semibold text-foreground">
                                                      {{ category.obtained_points || 0 }}/{{ category.total_possible_points || 0 }}
                                                  </div>
                                                  <div class="text-muted-foreground">
                                                      {{ getCategoryPercentage(category.obtained_points || 0, category.total_possible_points || 1) }}%
                                                  </div>
                                              </div>
                                          </div>
                                          
                                          <!-- Total general -->
                                          <div class="pt-2 border-t border-border">
                                              <div class="flex items-center justify-between text-xs">
                                                  <div class="flex items-center gap-2 min-w-0 flex-1">
                                                      <span class="font-bold text-foreground">Total General</span>
                                                      <div class="flex-1 bg-gray-200 rounded-full h-2 min-w-[40px]">
                                                          <div 
                                                              :class="getCategoryBarColor(evaluation.total_score || 0, 100)"
                                                              class="h-2 rounded-full transition-all duration-300"
                                                              :style="{ width: (evaluation.total_percentage || 0) + '%' }"
                                                          ></div>
                                                      </div>
                                                  </div>
                                                  <div class="text-right ml-2 flex-shrink-0">
                                                      <div class="font-bold text-foreground">
                                                          {{ evaluation.total_percentage || 0 }}%
                                                      </div>
                                                  </div>
                                              </div>
                                          </div>
                                      </div>
                                      <div v-else class="text-xs text-muted-foreground">
                                          Sin datos de categorías
                                      </div>
                                  </div>
                                
                                <!-- Acciones (solo desktop) -->
                                <div class="hidden md:flex md:gap-1">
                                       <Button
                                            variant="outline"  size="sm" @click="downloadPdf(evaluation.id)" class="text-green-600 hover:text-green-700 hover:bg-green-50"
                                        >
                                            <Download class="h-4 w-4" />
                                        </Button>
                                    <button 
                                        @click="confirmDelete(evaluation)" 
                                        class="inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors border border-input"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                                
                                <!-- Acciones en móvil -->
                                <div class="md:hidden col-span-full flex justify-between items-center mt-3 pt-3 border-t border-border">
                                    <Button
                                            variant="outline"  size="sm" @click="downloadPdf(evaluation.id)" class="text-green-600 hover:text-green-700 hover:bg-green-50"
                                        >
                                            <Download class="h-4 w-4" />
                                    </Button>
                                    <div class="flex gap-2">
                                        <Button 
                                            @click="confirmDelete(evaluation)" 
                                            variant="destructive" 
                                            size="sm"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Estado vacío -->
                <div v-else-if="props.evaluations?.data && props.evaluations.data.filter(e => e.status === 'completed').length === 0" class="text-center py-12 px-4">
                    <div class="text-4xl mb-4">📋</div>
                    <h3 class="text-lg font-semibold text-foreground mb-2">No hay evaluaciones completadas</h3>
                    <p class="text-muted-foreground">Aún no se han completado evaluaciones en el sistema.</p>
                </div>
                
                <!-- Estado vacío general -->
                <div v-else class="text-center py-12 px-4">
                    <div class="text-4xl mb-4">📋</div>
                    <h3 class="text-lg font-semibold text-foreground mb-2">No hay evaluaciones</h3>
                    <p class="text-muted-foreground">Aún no se han creado evaluaciones en el sistema.</p>
                </div>
            </div>
        </div>
        
        <!-- Modal de eliminación -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-opacity duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-opacity duration-300"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div 
                    v-if="showDeleteModal" 
                    class="fixed inset-0 bg-black/50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4"
                    @click="closeDeleteModal"
                >
                    <Transition
                        enter-active-class="transition-all duration-300"
                        enter-from-class="opacity-0 scale-95 translate-y-4"
                        enter-to-class="opacity-100 scale-100 translate-y-0"
                        leave-active-class="transition-all duration-300"
                        leave-from-class="opacity-100 scale-100 translate-y-0"
                        leave-to-class="opacity-0 scale-95 translate-y-4"
                    >
                        <div 
                            v-if="showDeleteModal"
                            class="bg-card border border-border rounded-lg shadow-lg w-full max-w-md"
                            @click.stop
                        >
                            <div class="p-6">
                                <div class="flex items-center justify-center w-12 h-12 mx-auto bg-destructive/10 rounded-full mb-4">
                                    <Trash2 class="h-6 w-6 text-destructive" />
                                </div>
                                <h3 class="text-lg font-semibold text-foreground text-center mb-2">Eliminar Evaluación</h3>
                                <p class="text-sm text-muted-foreground text-center mb-6">
                                    ¿Estás seguro de que quieres eliminar la evaluación "{{ evaluationToDelete?.title }}"?
                                    Esta acción no se puede deshacer.
                                </p>
                                <div class="flex gap-3 justify-center">
                                    <Button @click="closeDeleteModal" variant="outline">
                                        Cancelar
                                    </Button>
                                    <Button
                                        @click="handleDeleteConfirm(evaluationToDelete!.id)"
                                        variant="destructive"
                                    >
                                        Eliminar
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>



