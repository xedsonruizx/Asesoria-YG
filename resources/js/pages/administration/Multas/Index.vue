<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import route from 'ziggy-js';
import AppLayout from '@/layouts/AppLayout.vue';
import CreateModal from './Create.vue';
import EditModal from './Edit.vue';
import DeleteModal from './Delete.vue';
import { Plus, Edit, Trash2, Eye, ChevronLeft, ChevronRight, FileText, Calendar, Download, AlertTriangle, RotateCcw, Power, PowerOff } from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';

// Props del backend
interface Multa {
  id: number;
  name: string;
  description: string;
  path_file?: string;
  file_url?: string;
  is_active: boolean;
  status_text: string;
  questions_count?: number;
  created_at: string;
  updated_at: string;
  deleted_at?: string;
}

interface MultasData {
  data: Multa[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
}

const props = withDefaults(defineProps<{
  multas: MultasData;
  filters?: {
    search?: string;
    status?: string;
    show_deleted?: string;
  };
}>(), {
  multas: () => ({ data: [], current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 }),
  filters: () => ({})
});

// Estados de los modales
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const selectedMulta = ref<Multa | null>(null);

// Estados de filtros
const searchQuery = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const showDeleted = ref(props.filters?.show_deleted === 'true');

// Obtener la página actual de Inertia
const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Multas',
        href: '/multas',
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

// Funciones de filtrado
const applyFilters = () => {
  const params: any = {};
  
  if (searchQuery.value) params.search = searchQuery.value;
  if (statusFilter.value) params.status = statusFilter.value;
  if (showDeleted.value) params.show_deleted = 'true';
  
  router.get('/multas', params, {
    preserveState: true,
    replace: true
  });
};

const clearFilters = () => {
  searchQuery.value = '';
  statusFilter.value = '';
  showDeleted.value = false;
  router.get('/multas');
};

// Funciones de modales
const openCreateModal = () => {
  showCreateModal.value = true;
};

const openEditModal = (multa: Multa) => {
  selectedMulta.value = multa;
  showEditModal.value = true;
};

const openDeleteModal = (multa: Multa) => {
  selectedMulta.value = multa;
  showDeleteModal.value = true;
};

const closeModals = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  showDeleteModal.value = false;
  selectedMulta.value = null;
};

// Funciones de acciones
const toggleStatus = (multa: Multa) => {
  router.patch(`/multas/${multa.id}/toggle-status`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      // La página se recargará automáticamente
    }
  });
};

const restoreMulta = (multa: Multa) => {
  router.patch(`/multas/${multa.id}/restore`, {}, {
    preserveScroll: true,
    onSuccess: () => {
      // La página se recargará automáticamente
    }
  });
};

const forceDeleteMulta = (multa: Multa) => {
  if (confirm('¿Estás seguro de que quieres eliminar permanentemente esta multa? Esta acción no se puede deshacer.')) {
    router.delete(`/multas/${multa.id}/force-delete`, {
      preserveScroll: true,
      onSuccess: () => {
        // La página se recargará automáticamente
      }
    });
  }
};

// Handlers para los eventos de los modales
const handleCreated = () => {
  router.reload();
  closeModals();
};

const handleUpdated = () => {
  router.reload();
  closeModals();
};

const handleDeleted = () => {
  router.reload();
  closeModals();
};

// Funciones de paginación
const goToPage = (page: number) => {
  if (page >= 1 && page <= (props.multas?.last_page || 1)) {
    const params: any = { page };
    if (searchQuery.value) params.search = searchQuery.value;
    if (statusFilter.value) params.status = statusFilter.value;
    if (showDeleted.value) params.show_deleted = 'true';
    
    router.visit('/multas', {
      data: params,
      preserveState: true,
      preserveScroll: true,
    });
  }
};

const goToPreviousPage = () => {
  const currentPage = props.multas?.current_page || 1;
  if (currentPage > 1) {
    goToPage(currentPage - 1);
  }
};

const goToNextPage = () => {
  const currentPage = props.multas?.current_page || 1;
  const lastPage = props.multas?.last_page || 1;
  if (currentPage < lastPage) {
    goToPage(currentPage + 1);
  }
};

// Generar números de página para mostrar
const getPageNumbers = () => {
  const currentPage = props.multas?.current_page || 1;
  const lastPage = props.multas?.last_page || 1;
  const pages: number[] = [];
  
  // Verificar que tenemos datos válidos
  if (!props.multas || !currentPage || !lastPage) {
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
</script>

<template>
  <Head title="Multas" />
  
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <!-- Header con información de multas -->
      <div class="bg-card rounded-lg p-6 shadow-sm border border-border">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4 mb-4">
          <div class="flex-1">
            <h1 class="text-xl sm:text-2xl font-bold mb-2 text-foreground">Multas</h1>
            <p class="text-muted-foreground text-sm sm:text-base">Gestiona las multas del sistema</p>
          </div>
          <button 
            @click="openCreateModal"
            class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground hover:bg-primary/90 rounded-md transition-colors font-medium"
          >
            <Plus class="h-4 w-4" />
            Nueva Multa
          </button>
        </div>
        
        <!-- Estadísticas básicas -->
        <div class="flex gap-2 sm:gap-4 flex-wrap">
          <div class="bg-muted px-3 sm:px-4 py-2 sm:py-3 rounded-md flex-1 sm:flex-none">
            <span class="font-semibold text-foreground text-xs sm:text-sm">Total: {{ props.multas?.total || 0 }}</span>
          </div>
          <div class="bg-green-50 dark:bg-green-900/20 px-3 sm:px-4 py-2 sm:py-3 rounded-md flex-1 sm:flex-none">
            <span class="font-semibold text-green-700 dark:text-green-300 text-xs sm:text-sm">Activas: {{ multas.data.filter(m => m.is_active && !m.deleted_at).length }}</span>
          </div>
          <div class="bg-red-50 dark:bg-red-900/20 px-3 sm:px-4 py-2 sm:py-3 rounded-md flex-1 sm:flex-none">
            <span class="font-semibold text-red-700 dark:text-red-300 text-xs sm:text-sm">Inactivas: {{ multas.data.filter(m => !m.is_active && !m.deleted_at).length }}</span>
          </div>
          <div class="bg-orange-50 dark:bg-orange-900/20 px-3 sm:px-4 py-2 sm:py-3 rounded-md flex-1 sm:flex-none">
            <span class="font-semibold text-orange-700 dark:text-orange-300 text-xs sm:text-sm">Eliminadas: {{ multas.data.filter(m => m.deleted_at).length }}</span>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-card rounded-lg p-4 shadow-sm border border-border">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
          <div>
            <label class="block text-sm font-medium text-foreground mb-1">Buscar</label>
            <input
              v-model="searchQuery"
              @keyup.enter="applyFilters"
              type="text"
              placeholder="Buscar por nombre..."
              class="w-full px-3 py-2 border border-input rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-foreground mb-1">Estado</label>
            <select
              v-model="statusFilter"
              @change="applyFilters"
              class="w-full px-3 py-2 border border-input rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
            >
              <option value="">Todos</option>
              <option value="active">Activos</option>
              <option value="inactive">Inactivos</option>
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
              <span class="ml-2 text-sm text-foreground">Mostrar eliminados</span>
            </label>
          </div>
          <div class="flex items-end space-x-2">
            <button
              @click="applyFilters"
              class="px-4 py-2 bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors"
            >
              Filtrar
            </button>
            <button
              @click="clearFilters"
              class="px-4 py-2 bg-muted text-muted-foreground rounded-md hover:bg-muted/80 transition-colors"
            >
              Limpiar
            </button>
          </div>
        </div>
      </div>

      <!-- Información de paginación con controles -->
      <div v-if="props.multas?.data && props.multas.data.length > 0" class="bg-card rounded-lg p-4 shadow-sm border border-border">
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
          <!-- Información de registros -->
          <div class="text-muted-foreground text-sm">
            <span>Mostrando {{ props.multas.from }} a {{ props.multas.to }} de {{ props.multas.total }} multas</span>
          </div>
          
          <!-- Controles de paginación -->
          <div v-if="props.multas.last_page > 1" class="flex items-center gap-2">
            <!-- Botón anterior -->
            <button 
              @click="goToPreviousPage"
              :disabled="props.multas.current_page <= 1"
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
                  page === props.multas?.current_page 
                    ? 'bg-primary text-primary-foreground' 
                    : 'text-muted-foreground hover:text-foreground hover:bg-muted'
                ]"
              >
                {{ page }}
              </button>
              
              <!-- Última página si no está visible -->
              <template v-if="getPageNumbers()?.length && getPageNumbers()[getPageNumbers().length - 1] < props.multas.last_page">
                <span v-if="getPageNumbers()[getPageNumbers().length - 1] < props.multas.last_page - 1" class="text-muted-foreground px-1">...</span>
                <button 
                  @click="goToPage(props.multas.last_page)"
                  class="inline-flex items-center justify-center w-8 h-8 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors"
                >
                  {{ props.multas.last_page }}
                </button>
              </template>
            </div>
            
            <!-- Botón siguiente -->
            <button 
              @click="goToNextPage"
              :disabled="props.multas.current_page >= props.multas.last_page"
              class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Siguiente
              <ChevronRight class="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- Lista de multas -->
      <div class="bg-card rounded-lg overflow-hidden shadow-sm border border-border">
        <div v-if="props.multas?.data && props.multas.data.length > 0">
          <!-- Encabezados -->
          <div class="bg-muted/30 p-4 border-b border-border">
            <div class="grid grid-cols-1 md:grid-cols-6 gap-4 font-semibold text-foreground">
              <div class="md:col-span-2">Multa</div>
              <div class="md:col-span-2">Descripción</div>
              <div class="hidden md:block">Estado</div>
              <div class="hidden md:block text-right">Acciones</div>
            </div>
          </div>
          
          <!-- Filas de datos -->
          <div>
            <div 
              v-for="multa in props.multas.data" 
              :key="multa.id"
              :class="[
                'border-b border-border p-4 hover:bg-muted/50 transition-colors group',
                { 'bg-red-50 dark:bg-red-900/10': multa.deleted_at }
              ]"
            >
              <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-start md:items-center">
                <!-- Multa -->
                <div class="md:col-span-2">
                  <div class="font-semibold text-foreground group-hover:text-primary transition-colors mb-1">
                    {{ multa.name }}
                  </div>
                  <div class="text-sm text-muted-foreground flex items-center gap-2">
                    <Calendar class="h-3 w-3" />
                    {{ formatDate(multa.created_at) }}
                  </div>
                  
                  <!-- Archivo -->
                  <div class="mt-1">
                    <div v-if="multa.file_url" class="flex items-center gap-1">
                      <FileText class="w-3 h-3 text-muted-foreground" />
                      <a 
                        :href="multa.file_url" 
                        target="_blank"
                        class="text-xs text-primary hover:text-primary/80 transition-colors"
                      >
                        Ver archivo
                      </a>
                    </div>
                    <span v-else class="text-xs text-muted-foreground">Sin archivo</span>
                  </div>
                  
                  <!-- Información adicional en móvil -->
                  <div class="md:hidden mt-2 space-y-1">
                    <div class="text-sm text-muted-foreground">
                      <strong>Descripción:</strong> {{ truncateText(multa.description || 'Sin descripción', 60) }}
                    </div>
                    <div class="text-sm text-muted-foreground flex items-center gap-2">
                      <strong>Estado:</strong> 
                      <span :class="[
                        'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                        multa.deleted_at 
                          ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300'
                          : multa.is_active 
                            ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300' 
                            : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300'
                      ]">
                        {{ multa.deleted_at ? 'Eliminado' : multa.status_text }}
                      </span>
                    </div>
                  </div>
                </div>
                
                <!-- Descripción (solo desktop) -->
                <div class="hidden md:block md:col-span-2">
                  <div class="text-sm text-foreground" :title="multa.description">
                    {{ truncateText(multa.description || 'Sin descripción', 80) }}
                  </div>
                </div>
                
                <!-- Estado (solo desktop) -->
                <div class="hidden md:block">
                  <span :class="[
                    'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                    multa.deleted_at 
                      ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300'
                      : multa.is_active 
                        ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300' 
                        : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300'
                  ]">
                    {{ multa.deleted_at ? 'Eliminado' : multa.status_text }}
                  </span>
                </div>
                
                <!-- Acciones (solo desktop) -->
                <div class="hidden md:flex md:gap-2 md:justify-end">
                  <template v-if="!multa.deleted_at">
                    <!-- Toggle Status -->
                    <button
                      @click="toggleStatus(multa)"
                      :class="[
                        'inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium rounded-md transition-colors border border-input',
                        multa.is_active 
                          ? 'text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20'
                          : 'text-green-600 hover:text-green-700 hover:bg-green-50 dark:hover:bg-green-900/20'
                      ]"
                      :title="multa.is_active ? 'Desactivar' : 'Activar'"
                    >
                      <Power v-if="!multa.is_active" class="w-4 h-4" />
                      <PowerOff v-else class="w-4 h-4" />
                    </button>
                    
                    <!-- Edit -->
                    <button 
                      @click="openEditModal(multa)" 
                      class="inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors border border-input"
                    >
                      <Edit class="w-4 h-4" />
                    </button>
                    
                    <!-- Delete -->
                    <button 
                      @click="openDeleteModal(multa)" 
                      class="inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors border border-input"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </template>
                  
                  <template v-else>
                    <!-- Restore -->
                    <button
                      @click="restoreMulta(multa)"
                      class="inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium text-green-600 hover:text-green-700 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-md transition-colors border border-input"
                      title="Restaurar"
                    >
                      <RotateCcw class="w-4 h-4" />
                    </button>
                    
                    <!-- Force Delete -->
                    <button
                      @click="forceDeleteMulta(multa)"
                      class="inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors border border-input"
                      title="Eliminar permanentemente"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </template>
                </div>
                
                <!-- Acciones móvil -->
                <div class="md:hidden flex gap-2 mt-2">
                  <template v-if="!multa.deleted_at">
                    <!-- Toggle Status -->
                    <button
                      @click="toggleStatus(multa)"
                      :class="[
                        'flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium rounded-md transition-colors border border-input',
                        multa.is_active 
                          ? 'text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20'
                          : 'text-green-600 hover:text-green-700 hover:bg-green-50 dark:hover:bg-green-900/20'
                      ]"
                    >
                      <Power v-if="!multa.is_active" class="w-4 h-4" />
                      <PowerOff v-else class="w-4 h-4" />
                      {{ multa.is_active ? 'Desactivar' : 'Activar' }}
                    </button>
                    
                    <!-- Edit -->
                    <button 
                      @click="openEditModal(multa)" 
                      class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors border border-input"
                    >
                      <Edit class="w-4 h-4" />
                      Editar
                    </button>
                    
                    <!-- Delete -->
                    <button 
                      @click="openDeleteModal(multa)" 
                      class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors border border-input"
                    >
                      <Trash2 class="w-4 h-4" />
                      Eliminar
                    </button>
                  </template>
                  
                  <template v-else>
                    <!-- Restore -->
                    <button
                      @click="restoreMulta(multa)"
                      class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-green-600 hover:text-green-700 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-md transition-colors border border-input"
                    >
                      <RotateCcw class="w-4 h-4" />
                      Restaurar
                    </button>
                    
                    <!-- Force Delete -->
                    <button
                      @click="forceDeleteMulta(multa)"
                      class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors border border-input"
                    >
                      <Trash2 class="w-4 h-4" />
                      Eliminar Permanente
                    </button>
                  </template>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Estado vacío -->
        <div v-else class="p-8 text-center">
          <div class="flex flex-col items-center gap-4">
            <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center">
              <FileText class="w-8 h-8 text-muted-foreground" />
            </div>
            <div>
              <h3 class="text-lg font-semibold text-foreground mb-2">No hay multas disponibles</h3>
              <p class="text-muted-foreground mb-4">Comienza creando tu primera multa para gestionar el sistema.</p>
              <button 
                @click="openCreateModal"
                class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground hover:bg-primary/90 rounded-md transition-colors font-medium"
              >
                <Plus class="h-4 w-4" />
                Crear Primera Multa
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modales -->
    <CreateModal
      :show="showCreateModal"
      @close="closeModals"
      @created="handleCreated"
    />
    
    <EditModal
      :show="showEditModal"
      :multa="selectedMulta"
      @close="closeModals"
      @updated="handleUpdated"
    />
    
    <DeleteModal
      v-if="showDeleteModal && selectedMulta"
      :multa="selectedMulta"
      @close="closeModals"
      @deleted="handleDeleted"
    />
  </AppLayout>
</template>