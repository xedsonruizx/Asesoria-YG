<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import CreateModal from './Create.vue';
import EditModal from './Edit.vue';
import DeleteModal from './Delete.vue';
import { Plus, Edit, Trash2, Eye, ChevronLeft, ChevronRight, Tag, FileText, Calendar, ArrowLeft } from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';

// Props del backend
interface PostCategory {
  id: number;
  name: string;
  slug: string;
  description?: string;
  color?: string;
  is_active: boolean;
  posts_count: number;  // Campo crítico para validación de eliminación
  created_at: string;
  updated_at: string;
  posts?: Post[];
}

interface Post {
  id: number;
  title: string;
  status: string;
  created_at: string;
}

interface CategoriesData {
  data: PostCategory[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
}

const props = withDefaults(defineProps<{
  categories: CategoriesData;
}>(), {
  categories: () => ({ data: [], current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 })
});

// Estados de los modales
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const selectedCategory = ref<PostCategory | null>(null);

// Obtener la página actual de Inertia
const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Categorías de Posts',
        href: '/post-categories',
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

// Funciones de modales
const openCreateModal = () => {
  showCreateModal.value = true;
};

const openEditModal = (category: PostCategory) => {
  selectedCategory.value = category;
  showEditModal.value = true;
};



const openDeleteModal = (category: PostCategory) => {
  selectedCategory.value = category;
  showDeleteModal.value = true;
};

const closeModals = () => {
  showCreateModal.value = false;
  showEditModal.value = false;
  showDeleteModal.value = false;
  selectedCategory.value = null;
};

// Handlers para los eventos de los modales
const handleCreated = () => {
  router.reload();
};

const handleUpdated = () => {
  router.reload();
};


const deleteCategory = (category: PostCategory) => {
  openDeleteModal(category);  // Abre modal con categoría seleccionada
};

const handleDeleted = () => {
  router.reload();  // Recarga datos después de eliminación
};

// Funciones de paginación
const goToPage = (page: number) => {
  if (page >= 1 && page <= (props.categories?.last_page || 1)) {
    router.visit('/post-categories', {
      data: { page },
      preserveState: true,
      preserveScroll: true,
    });
  }
};

const goToPreviousPage = () => {
  const currentPage = props.categories?.current_page || 1;
  if (currentPage > 1) {
    goToPage(currentPage - 1);
  }
};

const goToNextPage = () => {
  const currentPage = props.categories?.current_page || 1;
  const lastPage = props.categories?.last_page || 1;
  if (currentPage < lastPage) {
    goToPage(currentPage + 1);
  }
};

// Generar números de página para mostrar
const getPageNumbers = () => {
  const currentPage = props.categories?.current_page || 1;
  const lastPage = props.categories?.last_page || 1;
  const pages: number[] = [];
  
  // Verificar que tenemos datos válidos
  if (!props.categories || !currentPage || !lastPage) {
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
const getCategoryColor = (color?: string) => {
  return color || '#6B7280';
};

const truncateText = (text: string, maxLength: number) => {
  if (text.length <= maxLength) return text;
  return text.substring(0, maxLength) + '...';
};

const getPostStatusBadge = (status: string) => {
  const statusMap: Record<string, { text: string; class: string }> = {
    'published': { text: 'Publicado', class: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300' },
    'draft': { text: 'Borrador', class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300' },
    'archived': { text: 'Archivado', class: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300' },
  };
  return statusMap[status] || { text: status, class: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300' };
};
</script>

<template>
    <Head title="Categorías de Posts" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header con información de categorías -->
            <div class="bg-card rounded-lg p-6 shadow-sm border border-border">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4 mb-4">
                    <div class="flex-1">
                        <h1 class="text-xl sm:text-2xl font-bold mb-2 text-foreground">Gestión de Categorías</h1>
                        <p class="text-muted-foreground text-sm sm:text-base">Administra las categorías para organizar tus publicaciones</p>
                    </div>
                    <button 
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground hover:bg-primary/90 rounded-md transition-colors font-medium"
                    >
                        <Plus class="h-4 w-4" />
                        Nueva Categoría
                    </button>
                </div>
                
                <!-- Estadísticas básicas -->
                <div class="flex gap-2 sm:gap-4 flex-wrap">
                    <div class="bg-muted px-3 sm:px-4 py-2 sm:py-3 rounded-md flex-1 sm:flex-none">
                        <span class="font-semibold text-foreground text-xs sm:text-sm">Total: {{ props.categories?.total || 0 }}</span>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/20 px-3 sm:px-4 py-2 sm:py-3 rounded-md flex-1 sm:flex-none">
                        <span class="font-semibold text-green-700 dark:text-green-300 text-xs sm:text-sm">Página {{ props.categories?.current_page || 1 }} de {{ props.categories?.last_page || 1 }}</span>
                    </div>
                </div>
            </div>

            <!-- Información de paginación con controles -->
            <div v-if="props.categories?.data && props.categories.data.length > 0" class="bg-card rounded-lg p-4 shadow-sm border border-border">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <!-- Información de registros -->
                    <div class="text-muted-foreground text-sm">
                        <span>Mostrando {{ props.categories.from }} a {{ props.categories.to }} de {{ props.categories.total }} categorías</span>
                    </div>
                    
                    <!-- Controles de paginación -->
                    <div v-if="props.categories.last_page > 1" class="flex items-center gap-2">
                        <!-- Botón anterior -->
                        <button 
                            @click="goToPreviousPage"
                            :disabled="props.categories.current_page <= 1"
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
                                    page === props.categories?.current_page 
                                        ? 'bg-primary text-primary-foreground' 
                                        : 'text-muted-foreground hover:text-foreground hover:bg-muted'
                                ]"
                            >
                                {{ page }}
                            </button>
                            
                            <!-- Última página si no está visible -->
                            <template v-if="getPageNumbers()?.length && getPageNumbers()[getPageNumbers().length - 1] < props.categories.last_page">
                                <span v-if="getPageNumbers()[getPageNumbers().length - 1] < props.categories.last_page - 1" class="text-muted-foreground px-1">...</span>
                                <button 
                                    @click="goToPage(props.categories.last_page)"
                                    class="inline-flex items-center justify-center w-8 h-8 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors"
                                >
                                    {{ props.categories.last_page }}
                                </button>
                            </template>
                        </div>
                        
                        <!-- Botón siguiente -->
                        <button 
                            @click="goToNextPage"
                            :disabled="props.categories.current_page >= props.categories.last_page"
                            class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Siguiente
                            <ChevronRight class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabla de categorías -->
            <div class="bg-card rounded-lg overflow-hidden shadow-sm border border-border">
                <div v-if="props.categories?.data && props.categories.data.length > 0">
                    <!-- Encabezados -->
                    <div class="bg-muted/30 p-4 border-b border-border">
                        <div class="grid grid-cols-1 md:grid-cols-7 gap-4 font-semibold text-foreground">
                            <div class="md:col-span-2">Categoría</div>
                            <div class="md:col-span-2">Descripción</div>
                            <div class="hidden md:block">Posts</div>
                            <div class="hidden md:block">Estado</div>
                            <div class="hidden md:block">Acciones</div>
                        </div>
                    </div>
                    
                    <!-- Filas de datos -->
                    <div>
                        <div 
                            v-for="category in props.categories.data" 
                            :key="category.id"
                            class="border-b border-border p-4 hover:bg-muted/50 transition-colors group"
                        >
                            <div class="grid grid-cols-1 md:grid-cols-7 gap-4 items-start md:items-center">
                                <!-- Categoría -->
                                <div class="md:col-span-2 cursor-pointer">
                                    <div class="flex items-center gap-3 mb-1">
                                        <div 
                                            class="w-4 h-4 rounded-full flex-shrink-0" 
                                            :style="{ backgroundColor: getCategoryColor(category.color) }"
                                        ></div>
                                        <div class="font-semibold text-foreground group-hover:text-primary transition-colors">
                                            {{ category.name }}
                                        </div>
                                    </div>
                                    <div class="text-sm text-muted-foreground ml-7">{{ category.slug }}</div>
                                    
                                    <!-- Información adicional en móvil -->
                                    <div class="md:hidden mt-2 space-y-1">
                                        <div class="text-sm text-muted-foreground">
                                            <strong>Descripción:</strong> {{ truncateText(category.description || 'Sin descripción', 60) }}
                                        </div>
                                        <div class="text-sm text-muted-foreground flex items-center gap-2">
                                            <strong>Posts:</strong> 
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                                                <FileText class="h-3 w-3 mr-1" />
                                                {{ category.posts_count }}
                                            </span>
                                        </div>
                                        <div class="text-sm text-muted-foreground flex items-center gap-2">
                                            <strong>Estado:</strong> 
                                            <span :class="[
                                                'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                                                category.is_active 
                                                    ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300' 
                                                    : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300'
                                            ]">
                                                {{ category.is_active ? 'Activo' : 'Inactivo' }}
                                            </span>
                                        </div>
                                        <div class="text-sm text-muted-foreground">
                                            <strong>Creado:</strong> {{ formatDate(category.created_at) }}
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Descripción (solo desktop) -->
                                <div class="hidden md:block md:col-span-2">
                                    <div class="text-sm text-foreground" :title="category.description">
                                        {{ truncateText(category.description || 'Sin descripción', 80) }}
                                    </div>
                                </div>
                                
                                <!-- Posts Count (solo desktop) -->
                                <div class="hidden md:block">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                                        <FileText class="h-3 w-3 mr-1" />
                                        {{ category.posts_count }}
                                    </span>
                                </div>
                                
                                <!-- Estado (solo desktop) -->
                                <div class="hidden md:block">
                                    <span :class="[
                                        'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                                        category.is_active 
                                            ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300' 
                                            : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-300'
                                    ]">
                                        {{ category.is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </div>
                                
                                <!-- Acciones (solo desktop) -->
                                <div class="hidden md:flex md:gap-2">
                                    <button 
                                        @click="openEditModal(category)" 
                                        class="inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors border border-input"
                                    >
                                        <Edit class="w-4 h-4" />
                                    </button>
                                    <button 
                                        @click="deleteCategory(category)" 
                                        class="inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors border border-input"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                                
                                <!-- Acciones móvil -->
                                <div class="md:hidden flex gap-2 mt-2">
                                    <button 
                                        @click="openEditModal(category)" 
                                        class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors border border-input"
                                    >
                                        <Edit class="w-4 h-4" />
                                        Editar
                                    </button>
                                    <button 
                                        @click="deleteCategory(category)" 
                                        class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-md transition-colors border border-input"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                        Eliminar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Estado vacío -->
                <div v-else class="p-8 text-center">
                    <div class="flex flex-col items-center gap-4">
                        <div class="w-16 h-16 bg-muted rounded-full flex items-center justify-center">
                            <Tag class="w-8 h-8 text-muted-foreground" />
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-foreground mb-2">No hay categorías</h3>
                            <p class="text-muted-foreground mb-4">Comienza creando tu primera categoría para organizar tus posts.</p>
                            <button 
                                @click="openCreateModal"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground hover:bg-primary/90 rounded-md transition-colors font-medium"
                            >
                                <Plus class="h-4 w-4" />
                                Crear Primera Categoría
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Componentes modulares -->
        <CreateModal 
            :show="showCreateModal" 
            @close="closeModals" 
            @created="handleCreated" 
        />
        
        <EditModal 
            :show="showEditModal" 
            :category="selectedCategory" 
            @close="closeModals" 
            @updated="handleUpdated" 
        />
        
        <DeleteModal 
            :is-open="showDeleteModal" 
            :category="selectedCategory" 
            @update:is-open="(value) => showDeleteModal = value" 
            @confirm="handleDeleted" 
        />
    </AppLayout>
</template>