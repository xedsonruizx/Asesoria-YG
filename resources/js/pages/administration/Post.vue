<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { index as postsIndex, show as postShow } from '@/routes/posts';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';

// Definir la interfaz Post
interface Post {
  id: number;
  title: string;
  content: string;
  Category: string;
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
</script>

<template>
    <Head title="Publicaciones" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header con información de publicaciones -->
            <div class="bg-card rounded-lg p-6 shadow-sm border border-border">
                <h1 class="text-2xl font-bold mb-2 text-foreground">Gestión de Publicaciones</h1>
                <p class="text-muted-foreground mb-4">Administra y visualiza todas las publicaciones del sistema</p>
                
                <!-- Estadísticas básicas -->
                <div class="flex gap-4 flex-wrap">
                    <div class="bg-muted px-4 py-3 rounded-md">
                        <span class="font-semibold text-foreground">Total: {{ props.posts?.total || 0 }}</span>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/20 px-4 py-3 rounded-md">
                        <span class="font-semibold text-green-700 dark:text-green-300">Página {{ props.posts?.current_page || 1 }} de {{ props.posts?.last_page || 1 }}</span>
                    </div>
                </div>
            </div>

            <!-- Tabla de publicaciones -->
            <div class="bg-card rounded-lg overflow-hidden shadow-sm border border-border">
                <div v-if="props.posts?.data && props.posts.data.length > 0">
                    <!-- Header de la tabla -->
                    <div class="bg-muted border-b border-border px-4 py-3">
                        <div class="grid grid-cols-1 md:grid-cols-6 gap-4 font-semibold text-foreground">
                            <div class="md:col-span-2">Título y Contenido</div>
                            <div>Categoría</div>
                            <div>Estado</div>
                            <div>Archivos</div>
                            <div class="hidden md:block">Fecha de Creación</div>
                            <div class="hidden md:block">Acciones</div>
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
                                </div>
                                
                                <!-- Categoría -->
                                <div class="mt-2 md:mt-0">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-secondary text-secondary-foreground">
                                        {{ post.Category }}
                                    </span>
                                </div>
                                
                                <!-- Estado -->
                                <div class="mt-2 md:mt-0">
                                    <span :class="getStatusColor(post.status) + ' inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium'">
                                        {{ getStatusLabel(post.status) }}
                                    </span>
                                </div>
                                
                                <!-- Archivos -->
                                <div class="flex gap-2 mt-2 md:mt-0">
                                    <span v-if="post.image_path" class="inline-flex items-center px-2 py-1 rounded text-xs bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300">
                                        📷 IMG
                                    </span>
                                    <span v-if="post.file_path" class="inline-flex items-center px-2 py-1 rounded text-xs bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300">
                                        📄 FILE
                                    </span>
                                    <span v-if="post.Subscripcion" class="inline-flex items-center px-2 py-1 rounded text-xs bg-pink-100 text-pink-800 dark:bg-pink-900/20 dark:text-pink-300">
                                        🔒 PREMIUM
                                    </span>
                                </div>
                                
                                <!-- Fecha -->
                                <div class="text-sm text-muted-foreground mt-2 md:mt-0 md:block hidden">
                                    {{ formatDate(post.created_at) }}
                                </div>
                                
                                <!-- Acciones -->
                                <div class="mt-2 md:mt-0 md:block hidden">
                                    <button 
                                        @click="viewPost(post)"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-primary hover:text-primary-foreground hover:bg-primary rounded-md transition-colors border border-primary/20 hover:border-primary"
                                    >
                                        Ver detalles
                                    </button>
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
            
            <!-- Información de paginación -->
            <div v-if="props.posts?.data && props.posts.data.length > 0" class="bg-card rounded-lg p-4 shadow-sm border border-border">
                <div class="flex justify-between items-center text-muted-foreground text-sm">
                    <span>Mostrando {{ props.posts.from }} a {{ props.posts.to }} de {{ props.posts.total }} publicaciones</span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>