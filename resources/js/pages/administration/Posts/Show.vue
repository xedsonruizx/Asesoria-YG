<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { show as postShow, index as postsIndex } from '@/routes/posts';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { ArrowLeft, Calendar, Tag, User, FileText, Image, Video, Download, Lock } from 'lucide-vue-next';

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
  post: Post;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Publicaciones',
        href: postsIndex().url,
    },
    {
        title: props.post.title,
        href: postShow(props.post.id).url,
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
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

// Detectar tipo de archivo multimedia
const getFileType = (filePath: string) => {
  if (!filePath) return null;
  const extension = filePath.split('.').pop()?.toLowerCase();
  
  const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
  const videoExtensions = ['mp4', 'webm', 'ogg', 'avi', 'mov'];
  const audioExtensions = ['mp3', 'wav', 'ogg', 'aac'];
  
  if (imageExtensions.includes(extension || '')) return 'image';
  if (videoExtensions.includes(extension || '')) return 'video';
  if (audioExtensions.includes(extension || '')) return 'audio';
  return 'document';
};

const imageType = computed(() => props.post.image_path ? getFileType(props.post.image_path) : null);
const fileType = computed(() => props.post.file_path ? getFileType(props.post.file_path) : null);
// URL de imagen de respaldo (imagen base64 simple)
const fallbackImageUrl = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMTgiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIiBmaWxsPSIjOTk5Ij5JbWFnZW4gbm8gZGlzcG9uaWJsZTwvdGV4dD48L3N2Zz4=';

// Agregar funciones para las acciones
const editPost = () => {
    // Navegar a la página de edición sin refresco
    $inertia.visit(`/posts/${props.post.id}/edit`);
};

const duplicatePost = () => {
    // Lógica para duplicar post
    console.log('Duplicar post:', props.post.id);
};

const deletePost = () => {
    if (confirm('¿Estás seguro de que quieres eliminar esta publicación?')) {
        $inertia.delete(`/posts/${props.post.id}`);
    }
};
</script>

<template>
    <Head :title="post.title" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <!-- Header con botón de regreso -->
            <div class="flex items-center gap-4">
                <Link 
                    :href="postsIndex().url"
                    class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground transition-colors rounded-md hover:bg-muted"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Volver a publicaciones
                </Link>
            </div>

            <!-- Contenido principal -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Contenido del post -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Título y metadatos -->
                    <div class="bg-card rounded-lg p-6 shadow-sm border border-border">
                        <div class="flex items-start justify-between mb-4">
                            <h1 class="text-3xl font-bold text-foreground leading-tight">{{ post.title }}</h1>
                            <span :class="getStatusColor(post.status) + ' inline-flex items-center px-3 py-1 rounded-full text-sm font-medium'">
                                {{ getStatusLabel(post.status) }}
                            </span>
                        </div>
                        
                        <!-- Metadatos -->
                        <div class="flex flex-wrap gap-4 text-sm text-muted-foreground">
                            <div class="flex items-center gap-2">
                                <Calendar class="h-4 w-4" />
                                <span>{{ formatDate(post.created_at) }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <Tag class="h-4 w-4" />
                                <span>{{ post.Category }}</span>
                            </div>
                            <div v-if="post.Subscripcion" class="flex items-center gap-2 text-pink-600 dark:text-pink-400">
                                <Lock class="h-4 w-4" />
                                <span>Contenido Premium</span>
                            </div>
                        </div>
                    </div>

                    <!-- Imagen principal -->
                    <div v-if="post.image_url && imageType === 'image'" class="bg-card rounded-lg overflow-hidden shadow-sm border border-border">
                        <div class="relative">
                            <!-- Imagen real -->
                            <img 
                                v-if="!imageLoadError"
                                :src="post.image_url" 
                                :alt="post.title"
                                class="w-full h-auto max-h-96 object-cover"
                                loading="lazy"
                                @error="handleImageError"
                            />
                            
                            <!-- Placeholder cuando falla la carga -->
                            <div 
                                v-if="imageLoadError" 
                                class="w-full h-96 flex items-center justify-center bg-gray-100 dark:bg-gray-800 border-2 border-dashed border-gray-300 dark:border-gray-600"
                            >
                                <div class="text-center text-gray-500 dark:text-gray-400">
                                    <Image class="h-16 w-16 mx-auto mb-4 opacity-50" />
                                    <p class="text-lg font-medium mb-2">Imagen no disponible</p>
                                    <p class="text-sm opacity-75">No se pudo cargar la imagen principal</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Placeholder cuando no hay imagen -->
                    <div v-else-if="!post.image_url" class="bg-card rounded-lg overflow-hidden shadow-sm border border-border">
                        <div class="w-full h-64 flex items-center justify-center bg-gray-50 dark:bg-gray-900 border-2 border-dashed border-gray-300 dark:border-gray-600">
                            <div class="text-center text-gray-400 dark:text-gray-500">
                                <Image class="h-12 w-12 mx-auto mb-3 opacity-40" />
                                <p class="text-base font-medium mb-1">Sin imagen</p>
                                <p class="text-sm opacity-75">Esta publicación no tiene imagen</p>
                            </div>
                        </div>
                    </div>

                    <!-- Video principal -->
                    <div v-if="post.image_url && imageType === 'video'" class="bg-card rounded-lg overflow-hidden shadow-sm border border-border">
                        <video 
                            :src="post.image_url" 
                            controls
                            class="w-full h-auto max-h-96"
                            preload="metadata"
                        >
                            Tu navegador no soporta el elemento de video.
                        </video>
                    </div>

                    <!-- Contenido del post -->
                    <div class="bg-card rounded-lg p-6 shadow-sm border border-border">
                        <div class="flex items-center gap-2 mb-4">
                            <FileText class="h-5 w-5 text-muted-foreground" />
                            <h2 class="text-xl font-semibold text-foreground">Contenido</h2>
                        </div>
                        <div class="prose prose-gray dark:prose-invert max-w-none">
                            <div class="text-foreground leading-relaxed whitespace-pre-wrap">{{ post.content }}</div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar con información adicional -->
                <div class="space-y-6">
                    <!-- Información del post -->
                    <div class="bg-card rounded-lg p-6 shadow-sm border border-border">
                        <h3 class="text-lg font-semibold text-foreground mb-4">Información</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">ID</label>
                                <p class="text-foreground">#{{ post.id }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Categoría</label>
                                <p class="text-foreground">{{ post.Category }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Estado</label>
                                <p class="text-foreground">{{ getStatusLabel(post.status) }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Tipo de acceso</label>
                                <p class="text-foreground">{{ post.Subscripcion ? 'Premium' : 'Público' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Creado</label>
                                <p class="text-foreground">{{ formatDate(post.created_at) }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Actualizado</label>
                                <p class="text-foreground">{{ formatDate(post.updated_at) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Archivos adjuntos -->
                    <div v-if="post.file_url || (post.image_url && imageType !== 'image' && imageType !== 'video')" class="bg-card rounded-lg p-6 shadow-sm border border-border">
                        <h3 class="text-lg font-semibold text-foreground mb-4 flex items-center gap-2">
                            <Download class="h-5 w-5" />
                            Archivos adjuntos
                        </h3>
                        <div class="space-y-3">
                            <!-- Archivo principal si no es imagen/video -->
                            <div v-if="post.image_url && imageType !== 'image' && imageType !== 'video'" class="flex items-center gap-3 p-3 bg-muted rounded-lg">
                                <div class="flex-shrink-0">
                                    <Image v-if="imageType === 'image'" class="h-6 w-6 text-blue-500" />
                                    <Video v-else-if="imageType === 'video'" class="h-6 w-6 text-purple-500" />
                                    <FileText v-else class="h-6 w-6 text-gray-500" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-foreground truncate">Archivo principal</p>
                                    <p class="text-xs text-muted-foreground">{{ imageType || 'documento' }}</p>
                                </div>
                                <a 
                                    :href="post.image_url" 
                                    target="_blank"
                                    class="flex-shrink-0 inline-flex items-center px-3 py-1 text-xs font-medium bg-primary text-primary-foreground rounded hover:bg-primary/90 transition-colors"
                                >
                                    Ver
                                </a>
                            </div>

                            <!-- Archivo adicional -->
                            <div v-if="post.file_url" class="flex items-center gap-3 p-3 bg-muted rounded-lg">
                                <div class="flex-shrink-0">
                                    <Image v-if="fileType === 'image'" class="h-6 w-6 text-blue-500" />
                                    <Video v-else-if="fileType === 'video'" class="h-6 w-6 text-purple-500" />
                                    <FileText v-else class="h-6 w-6 text-gray-500" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-foreground truncate">Archivo adjunto</p>
                                    <p class="text-xs text-muted-foreground">{{ fileType || 'documento' }}</p>
                                </div>
                                <a 
                                    :href="post.file_url" 
                                    target="_blank"
                                    class="flex-shrink-0 inline-flex items-center px-3 py-1 text-xs font-medium bg-primary text-primary-foreground rounded hover:bg-primary/90 transition-colors"
                                >
                                    Ver
                                </a>
                            </div>

                            <!-- Galería de archivos adicionales si hay video en file_url -->
                            <div v-if="post.file_url && fileType === 'video'" class="mt-4">
                                <h4 class="text-sm font-medium text-foreground mb-2">Video adjunto</h4>
                                <div class="bg-muted rounded-lg overflow-hidden">
                                    <video 
                                        :src="post.file_url" 
                                        controls
                                        class="w-full h-auto max-h-48"
                                        preload="metadata"
                                    >
                                        Tu navegador no soporta el elemento de video.
                                    </video>
                                </div>
                            </div>

                            <!-- Imagen adicional si hay imagen en file_url -->
                            <div v-if="post.file_url && fileType === 'image'" class="mt-4">
                                <h4 class="text-sm font-medium text-foreground mb-2">Imagen adjunta</h4>
                                <div class="bg-muted rounded-lg overflow-hidden">
                                    <div class="relative">
                                        <!-- Imagen adjunta real -->
                                        <img 
                                            v-if="!fileImageLoadError"
                                            :src="post.file_url" 
                                            :alt="'Imagen adjunta de ' + post.title"
                                            class="w-full h-auto max-h-48 object-cover"
                                            loading="lazy"
                                            @error="handleFileImageError"
                                        />
                                        
                                        <!-- Placeholder para imagen adjunta -->
                                        <div 
                                            v-if="fileImageLoadError" 
                                            class="w-full h-48 flex items-center justify-center bg-gray-100 dark:bg-gray-800 border-2 border-dashed border-gray-300 dark:border-gray-600"
                                        >
                                            <div class="text-center text-gray-500 dark:text-gray-400">
                                                <Image class="h-12 w-12 mx-auto mb-2 opacity-50" />
                                                <p class="text-sm font-medium mb-1">Imagen no disponible</p>
                                                <p class="text-xs opacity-75">Error al cargar imagen adjunta</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="bg-card rounded-lg p-6 shadow-sm border border-border">
                        <h3 class="text-lg font-semibold text-foreground mb-4">Acciones</h3>
                        <div class="space-y-2">
                            <button 
                                @click="editPost"
                                class="w-full inline-flex items-center justify-center px-4 py-2 text-sm font-medium bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors"
                            >
                                Editar publicación
                            </button>
                            <button 
                                @click="duplicatePost"
                                class="w-full inline-flex items-center justify-center px-4 py-2 text-sm font-medium bg-secondary text-secondary-foreground rounded-md hover:bg-secondary/80 transition-colors"
                            >
                                Duplicar
                            </button>
                            <button 
                                @click="deletePost"
                                class="w-full inline-flex items-center justify-center px-4 py-2 text-sm font-medium bg-destructive text-destructive-foreground rounded-md hover:bg-destructive/90 transition-colors"
                            >
                                Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>