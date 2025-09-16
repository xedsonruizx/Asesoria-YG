<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { Image, Play } from 'lucide-vue-next';

// Interface para el Post
interface Post {
    id: number;
    title: string;
    content: string;
    excerpt?: string;
    slug: string;
    meta_description?: string;
    status: string;
    is_premium: boolean;
    image_path: string | null;
    file_path: string | null;
    author_id?: number;
    published_at?: string;
    created_at: string;
    updated_at: string;
    tags?: Array<{
        id: number;
        name: string;
        slug: string;
        color: string;
    }>;
}

// Props
interface Props {
    post: Post;
    companyName?: string;
    userIsPremium?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    companyName: 'Asesorías YG',
    userIsPremium: false
});

// Emits
const emit = defineEmits<{
    openSubscriptionModal: [title: string];
}>();

// Estado para manejar errores de imagen/video
const mediaErrors = ref<Record<number, boolean>>({});

// Computed para obtener la URL completa del archivo
const mediaUrl = computed(() => {
    if (!props.post.image_path) return null;
    // Si ya es una URL completa, devolverla tal como está
    if (props.post.image_path.startsWith('http')) {
        return props.post.image_path;
    }
    // Si es una ruta relativa, construir la URL completa
    return `/storage/${props.post.image_path}`;
});

// Computed para determinar si el archivo es un video
const isVideo = computed(() => {
    if (!props.post.image_path) return false;
    const videoExtensions = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm'];
    const extension = props.post.image_path.split('.').pop()?.toLowerCase();
    return extension ? videoExtensions.includes(extension) : false;
});

// Computed para determinar si el archivo es una imagen
const isImage = computed(() => {
    if (!props.post.image_path) return false;
    const imageExtensions = ['jpeg', 'jpg', 'png', 'webp', 'gif'];
    const extension = props.post.image_path.split('.').pop()?.toLowerCase();
    return extension ? imageExtensions.includes(extension) : false;
});

// Función para manejar errores de media
const handleMediaError = (postId: number) => {
    mediaErrors.value[postId] = true;
    console.error(`Error loading media for post ${postId}:`, mediaUrl.value);
};

// Función para obtener el color de la etiqueta
const getTagColor = (color: string) => {
    return color || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
};

// Función para formatear fecha
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

// Función para manejar clic en post
const handlePostClick = () => {
    console.log('🔍 Post clicked:', props.post.title);
    console.log('🔍 PostCard props:', props.post);
    console.log('🏷️ Tags received:', props.post.tags);
    console.log('🔢 Tags count:', props.post.tags?.length || 0);
    console.log('🔒 Premium required:', props.post.is_premium);
    console.log('👤 User is premium:', props.userIsPremium);
    console.log('🖼️ Media URL:', mediaUrl.value);

    
    if (props.post.is_premium && !props.userIsPremium) {
        // Emitir evento para mostrar modal de suscripción solo si no es premium
        console.log('✅ Opening subscription modal...');
        emit('openSubscriptionModal', props.post.title);
        return;
    }
    
    // Navegar al show del post usando slug
    const url = props.post.slug ? `/publicacion/${props.post.slug}` : `/publicacion/${props.post.id}`;
    router.visit(url);
};
</script>

<template>
    <article 
        class="relative bg-white dark:bg-[#161615] rounded-lg shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d] overflow-hidden hover:shadow-lg transition-all duration-300 cursor-pointer group"
        @click="handlePostClick"
    >
        <!-- Candado para contenido premium (solo si el usuario no es premium) -->
        <div 
            v-if="post.is_premium && !userIsPremium"
            class="absolute top-4 right-4 z-10 bg-yellow-500 text-white p-2 rounded-full shadow-lg"
        >
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
            </svg>
        </div>

        <!-- Tags del post -->
        <div class="absolute top-4 left-4 z-10 flex flex-wrap gap-1 max-w-[calc(100%-6rem)]">
            <span 
                v-for="tag in post.tags?.slice(0, 2)" 
                :key="tag.id"
                :class="getTagColor(tag.color)"
                class="px-2 py-1 rounded-full text-xs font-medium"
            >
                {{ tag.name }}
            </span>
            <span 
                v-if="post.tags && post.tags.length > 2"
                class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400"
            >
                +{{ post.tags.length - 2 }}
            </span>
        </div>
        
        <!-- Media del post (imagen o video) -->
        <div class="aspect-video bg-gray-200 dark:bg-gray-700 relative overflow-hidden">
            <!-- Video disponible y sin errores -->
            <div v-if="isVideo && mediaUrl && !mediaErrors[post.id]" class="relative w-full h-full">
                <video 
                    :src="mediaUrl" 
                    class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                    muted
                    preload="metadata"
                    @error="handleMediaError(post.id)"
                    @loadstart="console.log('Video loading started:', mediaUrl)"
                    @loadeddata="console.log('Video loaded successfully:', mediaUrl)"
                >
                    Tu navegador no soporta el elemento de video.
                </video>
                
                <!-- Overlay de play para videos -->
                <div class="absolute inset-0 flex items-center justify-center bg-black bg-opacity-30 opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                    <div class="bg-white bg-opacity-90 rounded-full p-3">
                        <Play class="h-6 w-6 text-gray-800 fill-current" />
                    </div>
                </div>
                
                <!-- Indicador de video en la esquina -->
                <div class="absolute bottom-2 right-2 bg-black bg-opacity-70 text-white px-2 py-1 rounded text-xs font-medium">
                    VIDEO
                </div>
            </div>
            
            <!-- Imagen disponible y sin errores -->
            <img 
                v-else-if="isImage && mediaUrl && !mediaErrors[post.id]"
                :src="mediaUrl" 
                :alt="post.title"
                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                @error="handleMediaError(post.id)"
                @load="console.log('Image loaded successfully:', mediaUrl)"
            />
            
            <!-- Placeholder cuando hay error de carga -->
            <div v-else-if="mediaUrl && mediaErrors[post.id]" class="w-full h-full flex items-center justify-center bg-gray-50 dark:bg-gray-900">
                <div class="text-center text-gray-400 dark:text-gray-500">
                    <Image class="h-8 w-8 mx-auto mb-2 opacity-40" />
                    <p class="text-sm font-medium mb-1">Media no disponible</p>
                    <p class="text-xs opacity-75">Error al cargar el contenido</p>
                </div>
            </div>
            
            <!-- Placeholder cuando no hay imagen/video -->
            <div v-else class="w-full h-full flex items-center justify-center bg-gray-50 dark:bg-gray-900">
                <div class="text-center text-gray-400 dark:text-gray-500">
                    <Image class="h-8 w-8 mx-auto mb-2 opacity-40" />
                    <p class="text-sm font-medium mb-1">Sin imagen</p>
                    <p class="text-xs opacity-75">Esta publicación no tiene imagen</p>
                </div>
            </div>
            
            <!-- Overlay para contenido premium -->
            <div 
                v-if="post.is_premium && !userIsPremium"
                class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center z-20"
            >
                <div class="text-center text-white">
                    <svg class="w-12 h-12 mx-auto mb-2 opacity-80" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                    </svg>
                    <p class="text-sm font-medium">Contenido Premium</p>
                </div>
            </div>
        </div>
        
        <!-- Contenido del post -->
        <div class="p-6">
            <!-- Fecha y archivo adjunto -->
            <div class="flex items-center justify-between text-sm text-[#706f6c] dark:text-[#A1A09A] mb-3">
                <span>{{ formatDate(post.published_at || post.created_at) }}</span>
                <div class="flex items-center space-x-2">
                    <!-- Indicador de archivo descargable -->
                    <svg v-if="post.file_path" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span>{{ companyName }}</span>
                </div>
            </div>
            
            <!-- Título -->
            <h2 class="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-3 line-clamp-2">
                {{ post.title }}
            </h2>
            
            <!-- Excerpt o contenido truncado -->
            <p class="text-[#706f6c] dark:text-[#A1A09A] mb-4 line-clamp-3">
                {{ post.excerpt || (post.content.substring(0, 150) + (post.content.length > 150 ? '...' : '')) }}
            </p>
            
            <!-- Meta description (solo visible en hover o para SEO) -->
            <div v-if="post.meta_description" class="hidden">
                {{ post.meta_description }}
            </div>
            
            <!-- Botón de acción -->
            <div class="flex items-center justify-between">
                <button class="inline-flex items-center text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors">
                    {{ post.is_premium  ? 'Ver Premium' : 'Leer más' }}
                    <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
                
                <!-- Indicador de contenido premium -->
                <div v-if="post.is_premium" class="flex items-center text-xs text-yellow-600 dark:text-yellow-400">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                    </svg>
                    Premium
                </div>
            </div>
        </div>
    </article>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>