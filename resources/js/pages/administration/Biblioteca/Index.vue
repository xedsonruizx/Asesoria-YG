<script setup lang="ts">
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import CreateModal from './Create.vue';
import EditModal from './Edit.vue';
import DeleteModal from './Delete.vue';
import ShowModal from './Show.vue';
import HierarchyModal from './HierarchyModal.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Plus, Edit, Trash2, Eye, ChevronLeft, ChevronRight, FileText, Calendar, Download, AlertTriangle, RotateCcw, Power, PowerOff, Users, BookOpen, Lock, Unlock, TreePine } from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';

// Props del backend
interface Biblioteca {
  id: number;
  titulo: string;
  slug: string;
  descripcion: string;
  padre_id?: number;
  is_premium: boolean;
  orden: number;
  created_at: string;
  updated_at: string;
  deleted_at?: string;
  padre?: {
    id: number;
    titulo: string;
    slug: string;
  };
  hijos_count: number;
  nivel: number;
  ruta_completa: string;
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

interface Carpeta {
  id: number;
  nombre: string;
  slug: string;
  descripcion?: string;
  color: string;
  icono: string;
  orden: number;
  activa: boolean;
  padre_id?: number;
  nivel: number;
  ruta_completa: string;
  subcarpetas?: Carpeta[];
  bibliotecas?: Biblioteca[];
}

interface Props {
  biblioteca: PaginatedData<Biblioteca>;
  carpetas?: Carpeta[];
  stats?: {
    total: number;
    premium: number;
    gratuito: number;
    deleted: number;
  };
  filters?: {
    search?: string;
    is_premium?: string;
    show_deleted?: string;
  };
}

const props = defineProps<Props>();

// Estados de los modales
// Estados para modales
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const showShowModal = ref(false);
const showHierarchyModal = ref(false);
const selectedBiblioteca = ref<Biblioteca | null>(null);

// Estados de filtros
const searchQuery = ref(props.filters?.search || '');
const premiumFilter = ref(props.filters?.is_premium || '');
const showDeleted = ref(props.filters?.show_deleted === 'true');

// Obtener la página actual de Inertia
const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Biblioteca',
        href: '/admin/biblioteca',
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

const getTypeLabel = (is_premium: boolean) => {
  return is_premium ? 'Premium' : 'Gratuito';
};

// Funciones de filtrado
const applyFilters = () => {
  const params: any = {};
  
  if (searchQuery.value) params.search = searchQuery.value;
  if (premiumFilter.value) params.is_premium = premiumFilter.value;
  if (showDeleted.value) params.show_deleted = 'true';
  
  router.get('/admin/biblioteca', params, {
    preserveState: true,
    replace: true
  });
};

const clearFilters = () => {
  searchQuery.value = '';
  premiumFilter.value = '';
  showDeleted.value = false;
  router.get('/admin/biblioteca');
};

// Funciones de modales
const openCreateModal = () => {
  showCreateModal.value = true;
};

const openEditModal = (biblioteca: Biblioteca) => {
  selectedBiblioteca.value = biblioteca;
  showEditModal.value = true;
};

const openDeleteModal = (biblioteca: Biblioteca) => {
  selectedBiblioteca.value = biblioteca;
  showDeleteModal.value = true;
};

const openShowModal = (biblioteca: Biblioteca) => {
  selectedBiblioteca.value = biblioteca;
  showShowModal.value = true;
};

const openHierarchyModal = () => {
  showHierarchyModal.value = true;
};

// Función para cerrar todos los modales
const closeModals = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  showDeleteModal.value = false;
  showShowModal.value = false;
  showHierarchyModal.value = false;
  selectedBiblioteca.value = null;
};

// Funciones de acciones
// Función helper para obtener filtros actuales
const getCurrentFilters = () => {
  const params: any = {};
  if (searchQuery.value) params.search = searchQuery.value;
  if (premiumFilter.value) params.is_premium = premiumFilter.value;
  if (showDeleted.value) params.show_deleted = 'true';
  return params;
};

// Funciones de acciones modificadas
const toggleStatus = (biblioteca: Biblioteca) => {
  const filters = getCurrentFilters();
  router.patch(`/admin/biblioteca/${biblioteca.id}/toggle-status`, filters, {
    preserveScroll: true,
    onSuccess: () => {
      // La página se recargará automáticamente con filtros preservados
    }
  });
};

const restoreBiblioteca = (biblioteca: Biblioteca) => {
  const filters = getCurrentFilters();
  router.patch(`/admin/biblioteca/${biblioteca.id}/restore`, filters, {
    preserveScroll: true,
    onSuccess: () => {
      // La página se recargará automáticamente con filtros preservados
    }
  });
};

const forceDeleteBiblioteca = (biblioteca: Biblioteca) => {
  if (confirm('¿Estás seguro de que quieres eliminar permanentemente este elemento? Esta acción no se puede deshacer.')) {
    const filters = getCurrentFilters();
    router.delete(`/admin/biblioteca/${biblioteca.id}/force-delete`, {
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
  router.get('/admin/biblioteca', filters, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      closeModals();
    }
  });
};

const handleUpdated = () => {
  const filters = getCurrentFilters();
  router.get('/admin/biblioteca', filters, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      closeModals();
    }
  });
};

const handleDeleted = () => {
  const filters = getCurrentFilters();
  router.get('/admin/biblioteca', filters, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      closeModals();
    }
  });
};

// Funciones de paginación
const goToPage = (page: number) => {
  if (page >= 1 && page <= (props.biblioteca?.last_page || 1)) {
    const params: any = { page };
    if (searchQuery.value) params.search = searchQuery.value;
    if (premiumFilter.value) params.is_premium = premiumFilter.value;
    if (showDeleted.value) params.show_deleted = 'true';
    
    router.visit('/admin/biblioteca', {
      data: params,
      preserveState: true,
      preserveScroll: true,
    });
  }
};

const goToPreviousPage = () => {
  const currentPage = props.biblioteca?.current_page || 1;
  if (currentPage > 1) {
    goToPage(currentPage - 1);
  }
};

const goToNextPage = () => {
  const currentPage = props.biblioteca?.current_page || 1;
  const lastPage = props.biblioteca?.last_page || 1;
  if (currentPage < lastPage) {
    goToPage(currentPage + 1);
  }
};

// Generar números de página para mostrar
const getPageNumbers = () => {
  const currentPage = props.biblioteca?.current_page || 1;
  const lastPage = props.biblioteca?.last_page || 1;
  const pages: number[] = [];
  
  // Verificar que tenemos datos válidos
  if (!props.biblioteca || !currentPage || !lastPage) {
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
const getDependencyInfo = (biblioteca: Biblioteca) => {
  const dependencies = [];
  let canDelete = true;
  
  // Si tiene elementos hijos
  if (biblioteca.hijos_count > 0) {
    dependencies.push({
      text: `${biblioteca.hijos_count} elemento(s) hijo(s)`,
      class: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300'
    });
    canDelete = false;
  }
  
  if (dependencies.length === 0) {
    return {
      text: 'Sin dependencias',
      class: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
      canDelete: true
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
  <Head title="Biblioteca" />
  
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
      <!-- Header con información de biblioteca -->
      <div class="bg-card rounded-lg p-6 shadow-sm border border-border">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4 mb-4">
          <div class="flex-1">
            <h1 class="text-xl sm:text-2xl font-bold mb-2 text-foreground">Biblioteca</h1>
            <p class="text-muted-foreground text-sm sm:text-base">Gestiona los elementos de la biblioteca del sistema</p>
          </div>
          <div class="flex gap-2">
            <button 
              @click="openHierarchyModal"
              class="inline-flex items-center gap-2 px-4 py-2 bg-secondary text-secondary-foreground hover:bg-secondary/80 rounded-md transition-colors font-medium"
            >
              <TreePine class="h-4 w-4" />
              Ver Jerarquía
            </button>
            <button 
              @click="openCreateModal"
              class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground hover:bg-primary/90 rounded-md transition-colors font-medium"
            >
              <Plus class="h-4 w-4" />
              Nuevo Elemento
            </button>
          </div>
        </div>
        
        <!-- Estadísticas básicas -->
        <div class="flex gap-2 sm:gap-4 flex-wrap">
          <div class="bg-muted px-3 sm:px-4 py-2 sm:py-3 rounded-md flex-1 sm:flex-none">
            <span class="font-semibold text-foreground text-xs sm:text-sm">Total: {{ props.stats?.total || props.biblioteca.total }}</span>
          </div>
          <div class="bg-yellow-50 dark:bg-yellow-900/20 px-3 sm:px-4 py-2 sm:py-3 rounded-md flex-1 sm:flex-none">
            <span class="font-semibold text-yellow-700 dark:text-yellow-300 text-xs sm:text-sm">Premium: {{ props.biblioteca.data.filter(b => b.is_premium && !b.deleted_at).length }}</span>
          </div>
          <div class="bg-green-50 dark:bg-green-900/20 px-3 sm:px-4 py-2 sm:py-3 rounded-md flex-1 sm:flex-none">
            <span class="font-semibold text-green-700 dark:text-green-300 text-xs sm:text-sm">Gratuito: {{ props.biblioteca.data.filter(b => !b.is_premium && !b.deleted_at).length }}</span>
          </div>
          <div class="bg-red-50 dark:bg-red-900/20 px-3 sm:px-4 py-2 sm:py-3 rounded-md flex-1 sm:flex-none">
            <span class="font-semibold text-red-700 dark:text-red-300 text-xs sm:text-sm">Eliminados: {{ props.biblioteca.data.filter(b => b.deleted_at).length }}</span>
          </div>
          <div class="bg-blue-50 dark:bg-blue-900/20 px-3 sm:px-4 py-2 sm:py-3 rounded-md flex-1 sm:flex-none">
            <span class="font-semibold text-blue-700 dark:text-blue-300 text-xs sm:text-sm">Con Hijos: {{ props.biblioteca.data.filter(b => b.hijos_count > 0).length }}</span>
          </div>
        </div>
      </div>

      <!-- Filtros -->
      <div class="bg-card rounded-lg p-4 shadow-sm border border-border">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
          <div class="sm:col-span-2 lg:col-span-1">
            <label class="block text-sm font-medium text-foreground mb-1">Buscar</label>
            <input
              v-model="searchQuery"
              @keyup.enter="applyFilters"
              type="text"
              placeholder="Buscar por título..."
              class="w-full px-3 py-2 border border-input rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-foreground mb-1">Tipo</label>
            <select
              v-model="premiumFilter"
              @change="applyFilters"
              class="w-full px-3 py-2 border border-input rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
            >
              <option value="">Todos</option>
              <option value="1">Premium</option>
              <option value="0">Gratuito</option>
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
              <span class="ml-2 text-sm text-foreground whitespace-nowrap">Mostrar eliminados</span>
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
      <div v-if="props.biblioteca?.data && props.biblioteca.data.length > 0" class="bg-card rounded-lg p-4 shadow-sm border border-border">
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
          <!-- Información de registros -->
          <div class="text-muted-foreground text-sm">
            <span>Mostrando {{ props.biblioteca.from }} a {{ props.biblioteca.to }} de {{ props.biblioteca.total }} elementos</span>
          </div>
          
          <!-- Controles de paginación -->
          <div v-if="props.biblioteca.last_page > 1" class="flex items-center gap-2">
            <!-- Botón anterior -->
            <button 
              @click="goToPreviousPage"
              :disabled="props.biblioteca.current_page <= 1"
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
                  page === props.biblioteca?.current_page 
                    ? 'bg-primary text-primary-foreground' 
                    : 'text-muted-foreground hover:text-foreground hover:bg-muted'
                ]"
              >
                {{ page }}
              </button>
              
              <!-- Última página si no está visible -->
              <template v-if="getPageNumbers()?.length && getPageNumbers()[getPageNumbers().length - 1] < props.biblioteca.last_page">
                <span v-if="getPageNumbers()[getPageNumbers().length - 1] < props.biblioteca.last_page - 1" class="text-muted-foreground px-1">...</span>
                <button 
                  @click="goToPage(props.biblioteca.last_page)"
                  class="inline-flex items-center justify-center w-8 h-8 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors"
                >
                  {{ props.biblioteca.last_page }}
                </button>
              </template>
            </div>
            
            <!-- Botón siguiente -->
            <button 
              @click="goToNextPage"
              :disabled="props.biblioteca.current_page >= props.biblioteca.last_page"
              class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Siguiente
              <ChevronRight class="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>

      <!-- Tabla de elementos -->
      <div class="bg-card rounded-lg overflow-hidden shadow-sm border border-border">
        <div v-if="props.biblioteca?.data && props.biblioteca.data.length > 0">
          <!-- Encabezados -->
          <div class="bg-muted/30 p-4 border-b border-border">
            <div class="grid grid-cols-1 md:grid-cols-8 gap-4 font-semibold text-foreground">
              <div class="md:col-span-2">Elemento</div>
              <div class="md:col-span-2">Descripción</div>
              <div class="hidden md:block">Tipo</div>
              <div class="hidden md:block">Hijos</div>
              <div class="hidden md:block">Dependencias</div>
              <div class="hidden md:block text-center">Acciones</div>
            </div>
          </div>
          
          <!-- Filas de datos -->
          <div>
            <div 
              v-for="elemento in props.biblioteca.data" 
              :key="elemento.id"
              :class="[
                'border-b border-border p-4 hover:bg-muted/50 transition-colors group',
                elemento.deleted_at ? 'bg-red-50 dark:bg-red-900/10' : ''
              ]"
            >
              <div class="grid grid-cols-1 md:grid-cols-8 gap-4 items-start md:items-center">
                <!-- Elemento -->
                <div class="md:col-span-2 cursor-pointer">
                  <div class="flex items-center gap-3 mb-1">
                    <BookOpen class="w-4 h-4 text-muted-foreground flex-shrink-0" />
                    <div class="font-semibold text-foreground group-hover:text-primary transition-colors">
                      {{ elemento.titulo }}
                    </div>
                    <div v-if="elemento.deleted_at" class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300">
                      Eliminado
                    </div>
                  </div>
                  <div class="text-sm text-muted-foreground ml-7">{{ elemento.slug }}</div>
                  <div v-if="elemento.padre" class="text-xs text-muted-foreground ml-7 mt-1">
                    Padre: {{ elemento.padre.titulo }}
                  </div>
                  
                  <!-- Información adicional en móvil -->
                  <div class="md:hidden mt-2 space-y-1">
                    <div class="text-sm text-muted-foreground">
                      <strong>Descripción:</strong> {{ truncateText(elemento.descripcion || 'Sin descripción', 60) }}
                    </div>
                    <div class="text-sm text-muted-foreground flex items-center gap-2">
                      <strong>Tipo:</strong> 
                      <span :class="[
                        'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                        elemento.is_premium 
                          ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300' 
                          : 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300'
                      ]">
                        {{ getTypeLabel(elemento.is_premium) }}
                      </span>
                    </div>
                    <div class="text-sm text-muted-foreground flex items-center gap-2">
                      <strong>Hijos:</strong> 
                      <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                        <Users class="h-3 w-3 mr-1" />
                        {{ elemento.hijos_count }}
                      </span>
                    </div>
                    <div class="text-sm text-muted-foreground">
                      <strong>Creado:</strong> {{ formatDate(elemento.created_at) }}
                    </div>
                  </div>
                </div>
                
                <!-- Descripción (solo desktop) -->
                <div class="hidden md:block md:col-span-2">
                  <div class="text-sm text-foreground" :title="elemento.descripcion">
                    {{ truncateText(elemento.descripcion || 'Sin descripción', 80) }}
                  </div>
                </div>
                
                <!-- Tipo (solo desktop) -->
                <div class="hidden md:block">
                  <span :class="[
                    'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                    elemento.is_premium 
                      ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300' 
                      : 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300'
                  ]">
                    {{ getTypeLabel(elemento.is_premium) }}
                  </span>
                </div>
                
                <!-- Hijos Count (solo desktop) -->
                <div class="hidden md:block">
                  <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                    <Users class="h-3 w-3 mr-1" />
                    {{ elemento.hijos_count }}
                  </span>
                </div>
                
                <!-- Dependencias (solo desktop) -->
                <div class="hidden md:block">
                  <span :class="[
                    'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                    getDependencyInfo(elemento).class
                  ]">
                    {{ getDependencyInfo(elemento).text }}
                  </span>
                </div>
              
                <!-- Acciones (solo desktop) -->
                <div class="hidden md:flex md:gap-2 md:justify-center">
                
                  <template v-if="!elemento.deleted_at">
                    <button 
                      @click="openShowModal(elemento)" 
                      class="inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors border border-input"
                      title="Ver detalles"
                    >
                      <Eye class="w-4 h-4" />
                    </button>


                    <button 
                      @click="openEditModal(elemento)" 
                      class="inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors border border-input"
                    >
                      <Edit class="w-4 h-4" />
                    </button>
                    <button 
                      @click="openDeleteModal(elemento)" 
                      :disabled="!getDependencyInfo(elemento).canDelete"
                      :class="[
                        'inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium rounded-md transition-colors border border-input',
                        getDependencyInfo(elemento).canDelete
                          ? 'text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20'
                          : 'text-gray-400 cursor-not-allowed opacity-50'
                      ]"
                      :title="getDependencyInfo(elemento).canDelete ? 'Eliminar' : 'No se puede eliminar: tiene dependencias'"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </template>
                  
                  <template v-else>
                    <button 
                      @click="restoreBiblioteca(elemento)" 
                      class="inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium text-green-600 hover:text-green-700 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-md transition-colors border border-input"
                      title="Restaurar"
                    >
                      <RotateCcw class="w-4 h-4" />
                    </button>
                    <button 
                      @click="forceDeleteBiblioteca(elemento)" 
                      class="inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors border border-input"
                      title="Eliminar permanentemente"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </template>
                </div>
                
                <!-- Acciones móvil -->
                <div class="md:hidden flex gap-2 mt-2">
                  <template v-if="!elemento.deleted_at">
                    <button 
                      @click="openEditModal(elemento)" 
                      class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors border border-input"
                    >
                      <Edit class="w-4 h-4" />
                      Editar
                    </button>
                    <button 
                      @click="openDeleteModal(elemento)" 
                      :disabled="!getDependencyInfo(elemento).canDelete"
                      :class="[
                        'flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium rounded-md transition-colors border border-input',
                        getDependencyInfo(elemento).canDelete
                          ? 'text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20'
                          : 'text-gray-400 cursor-not-allowed opacity-50'
                      ]"
                    >
                      <Trash2 class="w-4 h-4" />
                      Eliminar
                    </button>
                  </template>
                  
                  <template v-else>
                    <button 
                      @click="restoreBiblioteca(elemento)" 
                      class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-green-600 hover:text-green-700 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-md transition-colors border border-input"
                    >
                      <RotateCcw class="w-4 h-4" />
                      Restaurar
                    </button>
                    <button 
                      @click="forceDeleteBiblioteca(elemento)" 
                      class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors border border-input"
                    >
                      <Trash2 class="w-4 h-4" />
                      Eliminar
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
              <BookOpen class="w-8 h-8 text-muted-foreground" />
            </div>
            <div>
              <h3 class="text-lg font-semibold text-foreground mb-2">No hay elementos en la biblioteca</h3>
              <p class="text-muted-foreground mb-4">Comienza creando tu primer elemento para organizar tu biblioteca.</p>
              <button 
                @click="openCreateModal"
                class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground hover:bg-primary/90 rounded-md transition-colors font-medium"
              >
                <Plus class="h-4 w-4" />
                Nuevo Elemento
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
      :biblioteca="selectedBiblioteca"
      @close="closeModals"
      @updated="handleUpdated"
    />
    
    <DeleteModal
      v-if="showDeleteModal && selectedBiblioteca"
      :biblioteca="selectedBiblioteca"
      @close="closeModals"
      @deleted="handleDeleted"
    />
    
    <ShowModal
      :biblioteca="selectedBiblioteca"
      :is-open="showShowModal"
      @close="closeModals"
    />
    <!-- HierarchyModal -->
    <HierarchyModal 
      :biblioteca="biblioteca.data" 
      :carpetas="carpetas || []"
      :show="showHierarchyModal" 
      @close="closeModals" 
    />
  </AppLayout>
</template>