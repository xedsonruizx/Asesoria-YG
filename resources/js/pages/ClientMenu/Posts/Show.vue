<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import TopBar from '@/components/MyComponents/TopBar.vue';
import SubscriptionModal from '@/components/MyComponents/SubscriptionModal.vue';
import { computed, ref } from 'vue';
import { ArrowLeft, Calendar, Tag, User, FileText, Image, Video, Download, Lock, Eye, Share2 } from 'lucide-vue-next';

// Obtener el nombre de la empresa desde las variables de entorno
const companyName = import.meta.env.VITE_COMPANY_NAME || 'Asesorías YG';

// Definir la interfaz Post
interface Post {
  id: number;
  title: string;
  content: string;
  category: string;
  status: string;
  image_path?: string;
  file_path?: string;
  subscripcion: boolean;
  created_at: string;
  updated_at: string;
}

interface Props {
  post: Post;
}

const props = defineProps<Props>();

// Estado del modal y suscripción
const showSubscriptionModal = ref(false);
const userHasActiveSubscription = false; // Esto vendría de props o store

// Estados para manejo de errores de imágenes
const imageLoadError = ref(false);
const fileImageLoadError = ref(false);

// Funciones para manejar errores de carga de imágenes
const handleImageError = () => {
    imageLoadError.value = true;
};

const handleFileImageError = () => {
    fileImageLoadError.value = true;
};

// Funciones de utilidad
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

// Función para obtener el color de la categoría
const getCategoryColor = (category: string) => {
    const colors = {
        'Legal': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        'RRHH': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        'Finanzas': 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        'Tecnología': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200'
    };
    return colors[category] || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
};

// Detectar tipo de archivo multimedia
const getFileType = (filePath: string) => {
  if (!filePath) return null;
  const extension = filePath.split('.').pop()?.toLowerCase();
  
  const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
  const videoExtensions = ['mp4', 'webm', 'ogg', 'avi', 'mov'];
  
  if (imageExtensions.includes(extension || '')) return 'image';
  if (videoExtensions.includes(extension || '')) return 'video';
  return 'document';
};

const imageType = computed(() => props.post.image_path ? getFileType(props.post.image_path) : null);
const fileType = computed(() => props.post.file_path ? getFileType(props.post.file_path) : null);

// Verificar si el contenido está bloqueado
const isContentBlocked = computed(() => {
    return props.post.subscripcion && !userHasActiveSubscription;
});

// Función para manejar acceso a contenido premium
const handlePremiumAccess = () => {
    if (isContentBlocked.value) {
        showSubscriptionModal.value = true;
        return;
    }
};

// Función para cerrar el modal
const closeSubscriptionModal = () => {
    showSubscriptionModal.value = false;
};

// Función para compartir (placeholder)
const sharePost = () => {
    if (navigator.share) {
        navigator.share({
            title: props.post.title,
            text: props.post.content.substring(0, 100) + '...',
            url: window.location.href
        });
    } else {
        // Fallback: copiar URL al portapapeles
        navigator.clipboard.writeText(window.location.href);
        alert('URL copiada al portapapeles');
    }
};
</script>

<template>
    <Head :title="post.title + ' - ' + companyName">
        <meta name="description" :content="post.content.substring(0, 160) + '...'" />
        <meta property="og:title" :content="post.title" />
        <meta property="og:description" :content="post.content.substring(0, 160) + '...'" />
        <meta property="og:image" :content="post.image_path" v-if="post.image_path" />
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    
    <div class="min-h-screen bg-[#FDFDFC] dark:bg-[#0a0a0a]">
        <!-- TopBar -->
        <TopBar />
        
        <!-- Contenido principal -->
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header con botón de regreso -->
            <div class="flex items-center justify-between mb-8">
                <Link 
                    href="/publicaciones"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC] transition-colors rounded-lg hover:bg-[#f5f5f4] dark:hover:bg-[#161615]"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Volver a publicaciones
                </Link>
                
                <!-- Botón compartir -->
                <button 
                    @click="sharePost"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-[#706f6c] dark:text-[#A1A09A] hover:text-[#1b1b18] dark:hover:text-[#EDEDEC] transition-colors rounded-lg hover:bg-[#f5f5f4] dark:hover:bg-[#161615]"
                >
                    <Share2 class="h-4 w-4" />
                    Compartir
                </button>
            </div>

            <!-- Artículo principal -->
            <article class="bg-white dark:bg-[#161615] rounded-lg shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d] overflow-hidden">
                <!-- Header del artículo -->
                <div class="p-6 sm:p-8">
                    <!-- Metadatos -->
                    <div class="flex flex-wrap items-center gap-4 mb-6">
                        <span :class="getCategoryColor(post.category)" class="px-3 py-1 rounded-full text-sm font-medium">
                            {{ post.category }}
                        </span>
                        <div class="flex items-center gap-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            <Calendar class="h-4 w-4" />
                            <span>{{ formatDate(post.created_at) }}</span>
                        </div>
                        <div class="flex items-center gap-2 text-sm text-[#706f6c] dark:text-[#A1A09A]">
                            <Eye class="h-4 w-4" />
                            <span>{{ companyName }}</span>
                        </div>
                        <div v-if="post.subscripcion" class="flex items-center gap-2 text-sm text-yellow-600 dark:text-yellow-400">
                            <Lock class="h-4 w-4" />
                            <span>Premium</span>
                        </div>
                    </div>
                    
                    <!-- Título -->
                    <h1 class="text-3xl sm:text-4xl font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-6 leading-tight">
                        {{ post.title }}
                    </h1>
                </div>

                <!-- Imagen principal -->
                <div v-if="post.image_path && imageType === 'image'" class="relative">
                    <!-- Overlay para contenido premium -->
                    <div 
                        v-if="isContentBlocked"
                        class="absolute inset-0 bg-black bg-opacity-60 flex items-center justify-center z-10 cursor-pointer"
                        @click="handlePremiumAccess"
                    >
                        <div class="text-center text-white">
                            <Lock class="w-16 h-16 mx-auto mb-4 opacity-80" />
                            <h3 class="text-xl font-semibold mb-2">Contenido Premium</h3>
                            <p class="text-sm opacity-90 mb-4">Suscríbete para acceder a este contenido</p>
                            <button class="px-6 py-2 bg-yellow-500 text-white rounded-lg font-medium hover:bg-yellow-600 transition-colors">
                                Ver Planes
                            </button>
                        </div>
                    </div>
                    
                    <!-- Imagen real -->
                    <img 
                        v-if="!imageLoadError"
                        :src="post.image_path" 
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
                            <p class="text-sm opacity-75">No se pudo cargar la imagen</p>
                        </div>
                    </div>
                </div>

                <!-- Placeholder cuando no hay imagen -->
                <div v-else-if="!post.image_path" class="bg-white dark:bg-[#161615] rounded-lg overflow-hidden">
                    <div class="w-full h-64 flex items-center justify-center bg-gray-50 dark:bg-gray-900 border-2 border-dashed border-gray-300 dark:border-gray-600">
                        <div class="text-center text-gray-400 dark:text-gray-500">
                            <Image class="h-12 w-12 mx-auto mb-3 opacity-40" />
                            <p class="text-base font-medium mb-1">Sin imagen</p>
                            <p class="text-sm opacity-75">Esta publicación no tiene imagen</p>
                        </div>
                    </div>
                </div>

                <!-- Video principal -->
                <div v-if="post.image_path && imageType === 'video'" class="relative">
                    <!-- Overlay para contenido premium -->
                    <div 
                        v-if="isContentBlocked"
                        class="absolute inset-0 bg-black bg-opacity-60 flex items-center justify-center z-10 cursor-pointer"
                        @click="handlePremiumAccess"
                    >
                        <div class="text-center text-white">
                            <Lock class="w-16 h-16 mx-auto mb-4 opacity-80" />
                            <h3 class="text-xl font-semibold mb-2">Contenido Premium</h3>
                            <p class="text-sm opacity-90 mb-4">Suscríbete para acceder a este video</p>
                            <button class="px-6 py-2 bg-yellow-500 text-white rounded-lg font-medium hover:bg-yellow-600 transition-colors">
                                Ver Planes
                            </button>
                        </div>
                    </div>
                    
                    <video 
                        v-if="!isContentBlocked"
                        :src="post.image_path" 
                        controls
                        class="w-full h-auto max-h-96"
                        preload="metadata"
                    >
                        Tu navegador no soporta el elemento de video.
                    </video>
                </div>

                <!-- Contenido del post -->
                <div class="p-6 sm:p-8">
                    <div class="prose prose-lg prose-gray dark:prose-invert max-w-none">
                        <!-- Contenido completo para usuarios con suscripción -->
                        <div v-if="!isContentBlocked" class="text-[#1b1b18] dark:text-[#EDEDEC] leading-relaxed whitespace-pre-wrap">
                            {{ post.content }}
                        </div>
                        
                        <!-- Contenido truncado para usuarios sin suscripción -->
                        <div v-else>
                            <div class="text-[#1b1b18] dark:text-[#EDEDEC] leading-relaxed whitespace-pre-wrap mb-6">
                                {{ post.content.substring(0, 300) }}...
                            </div>
                            
                            <!-- Call to action para suscripción -->
                            <div class="bg-gradient-to-r from-yellow-50 to-orange-50 dark:from-yellow-900/20 dark:to-orange-900/20 rounded-lg p-6 text-center border border-yellow-200 dark:border-yellow-800">
                                <Lock class="w-12 h-12 mx-auto mb-4 text-yellow-600 dark:text-yellow-400" />
                                <h3 class="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-2">
                                    Contenido Premium
                                </h3>
                                <p class="text-[#706f6c] dark:text-[#A1A09A] mb-4">
                                    Suscríbete para leer el artículo completo y acceder a contenido exclusivo
                                </p>
                                <button 
                                    @click="handlePremiumAccess"
                                    class="px-6 py-3 bg-yellow-500 text-white rounded-lg font-medium hover:bg-yellow-600 transition-colors"
                                >
                                    Ver Planes de Suscripción
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Archivos adjuntos -->
                <div v-if="post.file_path && !isContentBlocked" class="border-t border-gray-200 dark:border-gray-700 p-6 sm:p-8">
                    <h3 class="text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-4 flex items-center gap-2">
                        <Download class="h-5 w-5" />
                        Archivos adjuntos
                    </h3>
                    
                    <!-- Archivo descargable -->
                    <div class="flex items-center gap-4 p-4 bg-[#f5f5f4] dark:bg-[#1a1a19] rounded-lg">
                        <div class="flex-shrink-0">
                            <Image v-if="fileType === 'image'" class="h-8 w-8 text-blue-500" />
                            <Video v-else-if="fileType === 'video'" class="h-8 w-8 text-purple-500" />
                            <FileText v-else class="h-8 w-8 text-gray-500" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-[#1b1b18] dark:text-[#EDEDEC] truncate">
                                Archivo adjunto
                            </p>
                            <p class="text-xs text-[#706f6c] dark:text-[#A1A09A]">
                                {{ fileType || 'documento' }}
                            </p>
                        </div>
                        <a 
                            :href="post.file_path" 
                            target="_blank"
                            class="flex-shrink-0 inline-flex items-center px-4 py-2 text-sm font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
                        >
                            <Download class="h-4 w-4 mr-2" />
                            Descargar
                        </a>
                    </div>
                    
                    <!-- Vista previa de imagen adjunta -->
                    <div v-if="fileType === 'image'" class="mt-4">
                        <img 
                            v-if="!fileImageLoadError"
                            :src="post.file_path" 
                            :alt="'Imagen adjunta de ' + post.title"
                            class="w-full h-auto max-h-64 object-cover rounded-lg"
                            loading="lazy"
                            @error="handleFileImageError"
                        />
                        
                        <!-- Placeholder para imagen adjunta -->
                        <div 
                            v-if="fileImageLoadError" 
                            class="w-full h-64 flex items-center justify-center bg-gray-100 dark:bg-gray-800 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg"
                        >
                            <div class="text-center text-gray-500 dark:text-gray-400">
                                <Image class="h-12 w-12 mx-auto mb-2 opacity-50" />
                                <p class="text-sm font-medium mb-1">Imagen no disponible</p>
                                <p class="text-xs opacity-75">Error al cargar imagen adjunta</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Vista previa de video adjunto -->
                    <div v-if="fileType === 'video'" class="mt-4">
                        <video 
                            :src="post.file_path" 
                            controls
                            class="w-full h-auto max-h-64 rounded-lg"
                            preload="metadata"
                        >
                            Tu navegador no soporta el elemento de video.
                        </video>
                    </div>
                </div>
            </article>
            
            <!-- Navegación adicional -->
            <div class="mt-8 text-center">
                <Link 
                    href="/publicaciones"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition-colors"
                >
                    Ver más publicaciones
                </Link>
            </div>
        </div>
    </div>

    <!-- Modal de suscripción -->
    <SubscriptionModal 
        :is-open="showSubscriptionModal"
        :post-title="post.title"
        @close="closeSubscriptionModal"
    />
</template>

<style scoped>
.prose {
    color: inherit;
}

.prose h1,
.prose h2,
.prose h3,
.prose h4,
.prose h5,
.prose h6 {
    color: inherit;
}

.prose p {
    margin-bottom: 1rem;
}

.prose ul,
.prose ol {
    margin: 1rem 0;
    padding-left: 1.5rem;
}

.prose li {
    margin-bottom: 0.5rem;
}
</style>