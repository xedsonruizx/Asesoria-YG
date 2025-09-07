<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { index as postsIndex, show as postShow, create as postCreate } from '@/routes/posts';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { Plus, ChevronLeft, ChevronRight } from 'lucide-vue-next';

// Definir la interfaz Post
interface Post {
  id: number;
  title: string;
  content: string;
  tags?: Array<{
    id: number;
    name: string;
    color: string;
    slug: string;
  }>;
  status: 'draft' | 'published' | 'Delete';
  image_path?: string;
  file_path?: string;
  Subscripcion: boolean;
  created_at: string;
  updated_at: string;
  image_url?: string;
  file_url?: string;
}

interface Props {
  posts?: {
    data: Post[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
  };
}

const props = withDefaults(defineProps<Props>(), {
  posts: () => ({ data: [], current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 })
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Publicaciones',
        href: postsIndex().url,
    },
];

// Funciones de utilidad
const getStatusLabel = (status: string) => {
  const labels = {
    'draft': 'Borrador',
    'published': 'Publicado',
    'Delete': 'Eliminado'
  };
  return labels[status as keyof typeof labels] || status;
};

const getStatusColor = (status: string) => {
   const colors = {
    'draft': 'bg-yellow-500 text-white',
    'published': 'bg-green-500 text-white',
    'Delete': 'bg-red-500 text-white'
  };
  return colors[status as keyof typeof colors] || 'bg-gray-500 text-white';
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const truncateContent = (content: string, maxLength: number = 100) => {
  return content.length > maxLength ? content.substring(0, maxLength) + '...' : content;
};

// Función para navegar a la vista detallada
const viewPost = (post: Post) => {
  window.location.href = postShow(post.id).url;
};

// Función para navegar a crear nueva publicación
const createNewPost = () => {
  window.location.href = postCreate().url;
};

// Funciones de paginación
const goToPage = (page: number) => {
  if (page >= 1 && page <= (props.posts?.last_page || 1)) {
    router.get(postsIndex().url, { page }, {
      preserveState: true,
      preserveScroll: true
    });
  }
};

const goToPreviousPage = () => {
  const currentPage = props.posts?.current_page || 1;
  if (currentPage > 1) {
    goToPage(currentPage - 1);
  }
};

const goToNextPage = () => {
  const currentPage = props.posts?.current_page || 1;
  const lastPage = props.posts?.last_page || 1;
  if (currentPage < lastPage) {
    goToPage(currentPage + 1);
  }
};

// Generar números de página para mostrar
const getPageNumbers = () => {
  const currentPage = props.posts?.current_page || 1;
  const lastPage = props.posts?.last_page || 1;
  const pages: number[] = [];
  
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
</script>

<template>
    <Head title="Publicaciones" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header con información de publicaciones -->
            <div class="bg-card rounded-lg p-6 shadow-sm border border-border">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4 mb-4">
                    <div class="flex-1">
                        <h1 class="text-xl sm:text-2xl font-bold mb-2 text-foreground">Gestión de Publicaciones</h1>
                        <p class="text-muted-foreground text-sm sm:text-base">Administra y visualiza todas las publicaciones del sistema</p>
                    </div>
                    <button 
                        @click="createNewPost"
                        class="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2 bg-primary text-primary-foreground hover:bg-primary/90 rounded-md transition-colors font-medium text-xs sm:text-sm w-full sm:w-auto"
                    >
                        <Plus class="h-4 w-4 flex-shrink-0" />
                        <span class="truncate">Crear Nueva Publicación</span>
                    </button>
                </div>
                
                <!-- Estadísticas básicas -->
                <div class="flex gap-2 sm:gap-4 flex-wrap">
                    <div class="bg-muted px-3 sm:px-4 py-2 sm:py-3 rounded-md flex-1 sm:flex-none">
                        <span class="font-semibold text-foreground text-xs sm:text-sm">Total: {{ props.posts?.total || 0 }}</span>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/20 px-3 sm:px-4 py-2 sm:py-3 rounded-md flex-1 sm:flex-none">
                        <span class="font-semibold text-green-700 dark:text-green-300 text-xs sm:text-sm">Página {{ props.posts?.current_page || 1 }} de {{ props.posts?.last_page || 1 }}</span>
                    </div>
                </div>
            </div>
   <!-- Información de paginación con controles -->
            <div v-if="props.posts?.data && props.posts.data.length > 0" class="bg-card rounded-lg p-4 shadow-sm border border-border">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <!-- Información de registros -->
                    <div class="text-muted-foreground text-sm">
                        <span>Mostrando {{ props.posts.from }} a {{ props.posts.to }} de {{ props.posts.total }} publicaciones</span>
                    </div>
                    
                    <!-- Controles de paginación -->
                    <div v-if="props.posts.last_page > 1" class="flex items-center gap-2">
                        <!-- Botón anterior -->
                        <button 
                            @click="goToPreviousPage"
                            :disabled="props.posts.current_page <= 1"
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
                                    page === props.posts?.current_page 
                                        ? 'bg-primary text-primary-foreground' 
                                        : 'text-muted-foreground hover:text-foreground hover:bg-muted'
                                ]"
                            >
                                {{ page }}
                            </button>
                            
                            <!-- Última página si no está visible -->
                            <template v-if="getPageNumbers()[getPageNumbers().length - 1] < props.posts.last_page">
                                <span v-if="getPageNumbers()[getPageNumbers().length - 1] < props.posts.last_page - 1" class="text-muted-foreground px-1">...</span>
                                <button 
                                    @click="goToPage(props.posts.last_page)"
                                    class="inline-flex items-center justify-center w-8 h-8 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors"
                                >
                                    {{ props.posts.last_page }}
                                </button>
                            </template>
                        </div>
                        
                        <!-- Botón siguiente -->
                        <button 
                            @click="goToNextPage"
                            :disabled="props.posts.current_page >= props.posts.last_page"
                            class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Siguiente
                            <ChevronRight class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>
            <!-- Tabla de publicaciones -->
            <div class="bg-card rounded-lg overflow-hidden shadow-sm border border-border">
                <div v-if="props.posts?.data && props.posts.data.length > 0">
                    <!-- Header de la tabla -->
                    <!-- Encabezados -->
                    <div class="bg-muted/30 p-4 border-b border-border">
                        <div class="grid grid-cols-1 md:grid-cols-6 gap-4 font-semibold text-foreground">
                            <div class="md:col-span-2">Título y Contenido</div>
                            <div class="hidden md:block">Tags</div>
                            <div class="hidden md:block">Estado</div>
                            <div class="hidden md:block">Archivos</div>
                            <div class="hidden md:block">Fecha de Creación</div>
                        </div>
                    </div>
                    
                    <!-- Filas de datos -->
                    <div>
                        <div 
                            v-for="post in props.posts.data" 
                            :key="post.id"
                            class="border-b border-border p-4 hover:bg-muted/50 transition-colors group"
                        >
                            <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-start md:items-center">
                                <!-- Título y contenido -->
                                <div class="md:col-span-2 cursor-pointer" @click="viewPost(post)">
                                    <div class="font-semibold text-foreground mb-1 group-hover:text-primary transition-colors">{{ post.title }}</div>
                                    <div class="text-sm text-muted-foreground leading-relaxed">
                                        {{ truncateContent(post.content) }}
                                    </div>
                                    
                                    <!-- Tags, Estado y Archivos en móvil -->
                                    <div class="md:hidden mt-3 space-y-2">
                                        <!-- Tags móvil -->
                                        <div>
                                            <span class="text-xs font-medium text-muted-foreground mr-2">Tags:</span>
                                            <div v-if="post.tags && post.tags.length > 0" class="inline-flex flex-wrap gap-1">
                                                <span 
                                                    v-for="tag in post.tags" 
                                                    :key="tag.id"
                                                    :style="{ backgroundColor: tag.color }"
                                                    class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium text-white"
                                                >
                                                    {{ tag.name }}
                                                </span>
                                            </div>
                                            <span v-else class="text-xs text-muted-foreground italic">
                                                Sin tags
                                            </span>
                                        </div>
                                        
                                        <!-- Estado móvil -->
                                        <div>
                                            <span class="text-xs font-medium text-muted-foreground mr-2">Estado:</span>
                                            <span :class="getStatusColor(post.status) + ' inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium'">
                                                {{ getStatusLabel(post.status) }}
                                            </span>
                                        </div>
                                        
                                        <!-- Archivos móvil -->
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-xs font-medium text-muted-foreground">Archivos:</span>
                                            <div class="flex flex-wrap gap-1">
                                                <span v-if="post.image_path" class="inline-flex items-center px-2 py-1 rounded text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                                                    📷 IMG
                                                </span>
                                                <span v-if="post.file_path" class="inline-flex items-center px-2 py-1 rounded text-xs bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300">
                                                    📄 FILE
                                                </span>
                                                <span v-if="post.is_premium" class="inline-flex items-center px-2 py-1 rounded text-xs bg-pink-100 text-pink-800 dark:bg-pink-900/20 dark:text-pink-300">
                                                    🔒 PREMIUM
                                                </span>
                                                <span v-if="!post.image_path && !post.file_path && !post.is_premium" class="text-xs text-muted-foreground italic">
                                                    Sin archivos
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Tags (solo desktop) -->
                                <div class="hidden md:block">
                                    <div v-if="post.tags && post.tags.length > 0" class="flex flex-wrap gap-1">
                                        <span 
                                            v-for="tag in post.tags" 
                                            :key="tag.id"
                                            :style="{ backgroundColor: tag.color }"
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium text-white"
                                        >
                                            {{ tag.name }}
                                        </span>
                                    </div>
                                    <span v-else class="text-xs text-muted-foreground italic">
                                        Sin tags
                                    </span>
                                </div>
                                
                                <!-- Estado (solo desktop) -->
                                <div class="hidden md:block">
                                    <span :class="getStatusColor(post.status) + ' inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium'">
                                        {{ getStatusLabel(post.status) }}
                                    </span>
                                </div>
                                
                                <!-- Archivos (solo desktop) -->
                                <div class="hidden md:flex md:flex-wrap md:gap-1">
                                    <span v-if="post.image_path" class="inline-flex items-center px-2 py-1 rounded text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                                        📷 IMG
                                    </span>
                                    <span v-if="post.file_path" class="inline-flex items-center px-2 py-1 rounded text-xs bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300">
                                        📄 FILE
                                    </span>
                                    <span v-if="post.is_premium" class="inline-flex items-center px-2 py-1 rounded text-xs bg-pink-100 text-pink-800 dark:bg-pink-900/20 dark:text-pink-300">
                                        🔒 PREMIUM
                                    </span>
                                    <span v-if="!post.image_path && !post.file_path && !post.is_premium" class="text-xs text-muted-foreground italic">
                                        Sin archivos
                                    </span>
                                </div>
                                
                                <!-- Fecha (solo desktop) -->
                                <div class="hidden md:block text-sm text-muted-foreground">
                                    {{ formatDate(post.created_at) }}
                                </div>
                                
                                <!-- Fecha y acciones en móvil -->
                                <div class="md:hidden col-span-full flex justify-between items-center mt-3 pt-3 border-t border-border">
                                    <div class="text-sm text-muted-foreground">
                                        <strong>Creado:</strong> {{ formatDate(post.created_at) }}
                                    </div>
                                    <button 
                                        @click="viewPost(post)"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-primary hover:text-primary-foreground hover:bg-primary rounded-md transition-colors border border-primary/20 hover:border-primary"
                                    >
                                        Ver detalles
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Estado vacío -->
                <div v-else class="text-center py-12 px-4">
                    <div class="text-4xl mb-4">📝</div>
                    <h3 class="text-lg font-semibold text-foreground mb-2">No hay publicaciones</h3>
                    <p class="text-muted-foreground">Aún no se han creado publicaciones en el sistema.</p>
                </div>
            </div>
            
         
        </div>
    </AppLayout>
</template>