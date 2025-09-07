<template>
    <div v-if="shouldShowAttachments" class="bg-card rounded-lg p-6 shadow-sm border border-border">
        <h3 class="text-lg font-semibold text-foreground mb-4 flex items-center gap-2">
            <Download class="h-5 w-5" />
            Archivos adjuntos
        </h3>
        <div class="space-y-4">
            <!-- Imagen destacada -->
            <div v-if="imageUrl && imageType === 'image'" class="space-y-2">
                <h4 class="text-sm font-medium text-foreground">Imagen destacada</h4>
                <div class="bg-muted rounded-lg overflow-hidden">
                    <div class="relative">
                        <img 
                            v-if="!imageLoadError"
                            :src="imageUrl" 
                            :alt="'Imagen destacada de ' + (title || 'publicación')"
                            class="w-full h-auto max-h-64 object-cover"
                            loading="lazy"
                            @error="handleImageError"
                        />
                        
                        <!-- Placeholder para imagen destacada -->
                        <div 
                            v-if="imageLoadError" 
                            class="w-full h-48 flex items-center justify-center bg-gray-100 dark:bg-gray-800 border-2 border-dashed border-gray-300 dark:border-gray-600"
                        >
                            <div class="text-center text-gray-500 dark:text-gray-400">
                                <Image class="h-12 w-12 mx-auto mb-2 opacity-50" />
                                <p class="text-sm font-medium mb-1">Imagen no disponible</p>
                                <p class="text-xs opacity-75">Error al cargar imagen destacada</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end">
                    <a 
                        :href="imageUrl" 
                        target="_blank"
                        class="inline-flex items-center px-3 py-1 text-xs font-medium bg-primary text-primary-foreground rounded hover:bg-primary/90 transition-colors"
                    >
                        Ver imagen completa
                    </a>
                </div>
            </div>

            <!-- Video destacado -->
            <div v-if="imageUrl && imageType === 'video'" class="space-y-2">
                <h4 class="text-sm font-medium text-foreground">Video destacado</h4>
                <div class="bg-muted rounded-lg overflow-hidden">
                    <video 
                        :src="imageUrl" 
                        controls
                        class="w-full h-auto max-h-64"
                        preload="metadata"
                    >
                        Tu navegador no soporta el elemento de video.
                    </video>
                </div>
            </div>

            <!-- Archivo principal si no es imagen/video -->
            <div v-if="imageUrl == null && imageType !== 'image' && imageType !== 'video'" class="flex items-center gap-3 p-3 bg-muted rounded-lg">
                <div class="flex-shrink-0">
                    <FileText class="h-6 w-6 text-gray-500" />
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-foreground truncate">Archivo principal</p>
                    <p class="text-xs text-muted-foreground">{{ imageType || 'documento' }}</p>
                </div>
                <a 
                    :href="imageUrl" 
                    target="_blank"
                    class="flex-shrink-0 inline-flex items-center px-3 py-1 text-xs font-medium bg-primary text-primary-foreground rounded hover:bg-primary/90 transition-colors"
                >
                    Ver
                </a>
            </div>
            {{fileUrl}}
            <!-- Archivo adicional -->
            <div v-if="fileUrl" class="space-y-2">
                <h4 class="text-sm font-medium text-foreground">Archivo adjunto</h4>
                
                <!-- Si es imagen -->
                <div v-if="fileType === 'image'" class="bg-muted rounded-lg overflow-hidden">
                    <div class="relative">
                        <img 
                            v-if="!fileImageLoadError"
                            :src="fileUrl" 
                            :alt="'Imagen adjunta de ' + (title || 'publicación')"
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
                
                <!-- Si es video -->
                <div v-else-if="fileType === 'video'" class="bg-muted rounded-lg overflow-hidden">
                    <video 
                        :src="fileUrl" 
                        controls
                        class="w-full h-auto max-h-48"
                        preload="metadata"
                    >
                        Tu navegador no soporta el elemento de video.
                    </video>
                </div>
                
                <!-- Si es otro tipo de archivo -->
                <div v-else class="flex items-center gap-3 p-3 bg-muted rounded-lg">
                    <div class="flex-shrink-0">
                        <FileText class="h-6 w-6 text-gray-500" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-foreground truncate">Archivo adjunto</p>
                        <p class="text-xs text-muted-foreground">{{ fileType || 'documento' }}</p>
                    </div>
                    <a 
                        :href="fileUrl" 
                        target="_blank"
                        class="flex-shrink-0 inline-flex items-center px-3 py-1 text-xs font-medium bg-primary text-primary-foreground rounded hover:bg-primary/90 transition-colors"
                    >
                        Ver
                    </a>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Download, FileText, Image, Video } from 'lucide-vue-next';

interface Props {
    imageUrl?: string | null;
    fileUrl?: string | null;
    title?: string;
}

const props = withDefaults(defineProps<Props>(), {
    imageUrl: null,
    fileUrl: null,
    title: ''
});

// Estados para manejo de errores de carga
const imageLoadError = ref(false);
const fileImageLoadError = ref(false);

// Funciones para manejar errores de carga
const handleImageError = () => {
    imageLoadError.value = true;
};

const handleFileImageError = () => {
    fileImageLoadError.value = true;
};

// Computed property que determina si mostrar la sección
const shouldShowAttachments = computed(() => {
    return !!(props.imageUrl || props.fileUrl);
});

// Función para detectar tipo de archivo por URL
const getFileType = (url: string | null) => {
    if (!url) return null;
    const extension = url.split('.').pop()?.toLowerCase();
    
    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension || '')) {
        return 'image';
    }
    if (['mp4', 'webm', 'ogg', 'avi', 'mov'].includes(extension || '')) {
        return 'video';
    }
    if (['pdf'].includes(extension || '')) {
        return 'pdf';
    }
    if (['doc', 'docx'].includes(extension || '')) {
        return 'document';
    }
    return 'file';
};

const imageType = computed(() => getFileType(props.imageUrl));
const fileType = computed(() => getFileType(props.fileUrl));
</script>