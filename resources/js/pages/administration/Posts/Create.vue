<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { admin as postsAdminIndex, store } from '@/routes/posts';
import { Plus, FileText, Image, ImageIcon } from 'lucide-vue-next';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';

interface PostForm {
   title: string;
    content: string;
    meta_description: string; // Agregar este campo
    status: 'draft' | 'published';
    is_premium: boolean;
    featured_image: File | null;
    file: File | null;
    tag_categories: number[];
}

interface Props {
    availableTags?: Array<{
        id: number;
        name: string;
        color: string;
        slug: string;
    }>;
}

const props = withDefaults(defineProps<Props>(), {
    availableTags: () => []
});

const form = useForm<PostForm>({
    title: '',
    content: '',
    meta_description: '', // Agregar este campo
    status: 'draft',
    is_premium: false,
    featured_image: null,
    file: null,
    tag_categories: []
});

const imageInputRef = ref<HTMLInputElement | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);
const imagePreview = ref<string | null>(null);
const isDragOver = ref(false);
const isFileDragOver = ref(false);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Publicaciones', href: postsAdminIndex().url },
    { title: 'Crear Nueva Publicación', current: true },
];

// Funciones para manejo de imagen destacada
const handleImageUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        // Validar tamaño de imagen (2MB máximo)
        if (file.size > 2048 * 1024) {
            alert('La imagen no puede ser mayor a 2MB');
            return;
        }
        
        // Validar tipo de archivo
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            alert('Solo se permiten archivos JPG, PNG y GIF');
            return;
        }
        
        form.featured_image = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const handleImageDragOver = (event: DragEvent) => {
    event.preventDefault();
    event.stopPropagation();
    isDragOver.value = true;
};

const handleImageDragLeave = () => {
    isDragOver.value = false;
};

const handleImageDrop = (event: DragEvent) => {
    event.preventDefault();
    event.stopPropagation();
    isDragOver.value = false;
    
    const files = event.dataTransfer?.files;
    if (files && files.length > 0) {
        const file = files[0];
        
        // Validar que sea una imagen
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        if (!allowedTypes.includes(file.type)) {
            alert('Solo se permiten archivos JPG, PNG y GIF');
            return;
        }
        
        // Validar tamaño
        if (file.size > 2048 * 1024) {
            alert('La imagen no puede ser mayor a 2MB');
            return;
        }
        
        form.featured_image = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            imagePreview.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
};

const removeImage = () => {
    console.log('removeImage called', { 
        currentImage: form.featured_image, 
        inputRef: imageInputRef.value 
    });
    form.featured_image = null;
    imagePreview.value = null;
    if (imageInputRef.value) {
        imageInputRef.value.value = '';
        imageInputRef.value.files = null;
    }
    form.clearErrors('featured_image');
    console.log('removeImage completed', { image: form.featured_image });
};

const removeFile = () => {
    console.log('removeFile called', { 
        currentFile: form.file, 
        inputRef: fileInputRef.value 
    });
    form.file = null;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
        fileInputRef.value.files = null;
    }
    form.clearErrors('file');
    console.log('removeFile completed', { file: form.file });
};

const handleFileUpload = (e: Event) => {
    const target = e.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        handleFileSelection(file);
    }
};

const handleFileDragOver = (e: DragEvent) => {
    e.preventDefault();
    isFileDragOver.value = true;
};

const handleFileDragLeave = (e: DragEvent) => {
    e.preventDefault();
    isFileDragOver.value = false;
};

const handleFileDrop = (e: DragEvent) => {
    e.preventDefault();
    isFileDragOver.value = false;
    const files = e.dataTransfer?.files;
    if (files && files[0]) {
        handleFileSelection(files[0]);
    }
};

const handleFileSelection = (file: File) => {
    // Validar tamaño (10MB máximo)
    if (file.size > 10 * 1024 * 1024) {
        form.setError('file', 'El archivo no puede ser mayor a 10MB');
        return;
    }
    
    // Validar tipo de archivo
    const allowedTypes = ['.pdf', '.doc', '.docx', '.txt', '.zip', '.rar'];
    const fileExtension = '.' + file.name.split('.').pop()?.toLowerCase();
    
    if (!allowedTypes.includes(fileExtension)) {
        form.setError('file', 'Tipo de archivo no permitido. Solo se aceptan: PDF, DOC, DOCX, TXT, ZIP, RAR');
        return;
    }
    
    form.file = file;
    form.clearErrors('file');
};

const formatFileSize = (bytes: number): string => {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

// Funciones de formulario
const submitForm = (status: 'draft' | 'published') => {

    form.status = status;
    
    form.post(store().url, {
        onSuccess: () => {
            router.visit(postsIndex().url);
        },
        onError: (errors) => {
            console.error('Errores de validación:', errors);
            // Los errores se mostrarán automáticamente en el formulario
        },
    });
};

const saveDraft = () => submitForm('draft');
const publish = () => submitForm('published');


</script>

<template>
    <Head title="Crear Nueva Publicación" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
  <!-- Header -->
        <div class="">
            <div class="bg-card rounded-lg p-6 shadow-sm border border-border">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4 mb-4">
                    <div class="flex-1">
                        <h1 class="text-xl sm:text-2xl font-bold mb-2 text-foreground">Crear Nueva Publicación</h1>
                        <p class="text-muted-foreground text-sm sm:text-base">Completa todos los campos para crear una nueva publicación</p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 w-full sm:w-auto">
                        <button
                            @click="saveDraft"
                            :disabled="form.processing"
                            class="inline-flex items-center justify-center px-3 sm:px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm text-xs sm:text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800 disabled:opacity-50 w-full sm:w-auto"
                        >
                            <FileText class="h-4 w-4 mr-2 flex-shrink-0" />
                            <span class="truncate">{{ form.processing ? 'Guardando...' : 'Guardar Borrador' }}</span>
                        </button>
                        <button
                            @click="publish"
                            :disabled="form.processing || !form.title || !form.content || form.tag_categories.length === 0"
                            class="inline-flex items-center justify-center px-3 sm:px-4 py-2 border border-transparent text-xs sm:text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800 disabled:opacity-50 w-full sm:w-auto"
                        >
                            <Plus class="h-4 w-4 mr-2 flex-shrink-0" />
                            <span class="truncate">{{ form.processing ? 'Publicando...' : 'Publicar' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Form -->
              <div class="lg:col-span-2">
                        <div class="rounded-lg border bg-card p-6 shadow-sm">
                            <h2 class="mb-6 text-lg font-semibold text-card-foreground">Información Básica</h2>
                            
                            <div class="flex flex-col gap-6">
                                <!-- Título -->
                                <div class="grid gap-2">
                                    <Label for="title">Título de la publicación</Label>
                                    <Input
                                        id="title"
                                        v-model="form.title"
                                        type="text"
                                        placeholder="Ingresa el título de tu publicación"
                                        required
                                        :class="{ 'aria-invalid': form.errors.title }"
                                    />
                                    <InputError :message="form.errors.title" />
                                </div>

                                <!-- Contenido -->
                                <div class="grid gap-2">
                                    <Label for="content">Contenido</Label>
                                    <textarea
                                        id="content"
                                        v-model="form.content"
                                        rows="12"
                                        placeholder="Escribe el contenido de tu publicación..."
                                        required
                                        :class="[
                                            'file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input flex w-full min-w-0 rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
                                            'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                                            'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
                                            { 'aria-invalid': form.errors.content }
                                        ]"
                                    ></textarea>
                                    <InputError :message="form.errors.content" />
                                </div>

                                <!-- Meta Descripción -->
                                <div class="grid gap-2">
                                    <Label for="meta_description">Meta Descripción *</Label>
                                    <textarea
                                        id="meta_description"
                                        v-model="form.meta_description"
                                        rows="3"
                                        maxlength="160"
                                        placeholder="Descripción para motores de búsqueda (máximo 160 caracteres)"
                                        required
                                        :class="[
                                            'file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input flex w-full min-w-0 rounded-md border bg-transparent px-3 py-2 text-base shadow-xs transition-[color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
                                            'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                                            'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive',
                                            { 'aria-invalid': form.errors.meta_description }
                                        ]"
                                    ></textarea>
                                    <div class="text-sm text-muted-foreground mt-1">
                                        {{ form.meta_description.length }}/160 caracteres
                                    </div>
                                    <InputError :message="form.errors.meta_description" />
                                </div>

                        <!-- Imagen Destacada -->
                            <div class="grid gap-2">
                                <Label>Imagen Destacada</Label>
                                <div 
                                    class="relative rounded-lg border-2 border-dashed border-input p-6 text-center transition-colors"
                                    :class="{
                                        'border-primary bg-primary/5': isDragOver,
                                        'hover:border-primary/50 hover:bg-accent/50': !isDragOver
                                    }"
                                    @drop="handleImageDrop"
                                    @dragover="handleImageDragOver"
                                    @dragleave="handleImageDragLeave"
                                >
                                    <div v-if="!imagePreview && !form.featured_image" class="space-y-2">
                                        <div class="mx-auto h-12 w-12 text-muted-foreground">
                                            <ImageIcon class="h-full w-full" />
                                        </div>
                                        <div class="text-sm text-muted-foreground">
                                            <span class="font-medium text-primary">Haz clic para subir</span> o arrastra una imagen aquí
                                        </div>
                                        <p class="text-xs text-muted-foreground">PNG, JPG, GIF hasta 2MB</p>
                                    </div>
                                    
                                    <div v-else class="space-y-2">
                                        <div class="relative mx-auto h-32 w-32 overflow-hidden rounded-lg">
                                            <img 
                                                :src="imagePreview || '/storage/' + props.post.image_path" 
                                                alt="Preview" 
                                                class="h-full w-full object-cover"
                                            >
                                            <button
                                                @click="removeImage"
                                                type="button"
                                                class="absolute -right-2 -top-2 rounded-full bg-destructive p-1 text-destructive-foreground hover:bg-destructive/90"
                                            >
                                                <X class="h-4 w-4" />
                                            </button>
                                        </div>
                                        <p class="text-xs text-muted-foreground">{{ form.featured_image?.name || 'Imagen actual' }}</p>
                                    </div>
                                    
                                    <input 
                                        ref="imageInputRef"
                                        type="file" 
                                        accept="image/*" 
                                        class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                                        @change="handleImageUpload"
                                    >
                                </div>
                                <Button  v-if="form.featured_image" type="button" variant="outline" size="sm" @click="removeImage">
                                        Remover imagen
                                </Button>
                                <InputError :message="form.errors.featured_image" />
                            </div>





                                <!-- Archivo Adjunto -->
                                <div class="grid gap-2">
                                    <Label>Archivo Adjunto</Label>
                                    <div 
                                        class="relative rounded-lg border-2 border-dashed border-input p-6 text-center transition-colors"
                                        :class="{
                                            'border-primary bg-primary/5': isFileDragOver,
                                            'hover:border-primary/50 hover:bg-accent/50': !isFileDragOver && !form.file
                                        }"
                                        @drop="handleFileDrop"
                                        @dragover="handleFileDragOver"
                                        @dragleave="handleFileDragLeave"
                                    >
                                        <div v-if="!form.file" class="space-y-2">
                                            <div class="mx-auto h-12 w-12 text-muted-foreground">
                                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                                </svg>
                                            </div>
                                            <div class="text-sm text-muted-foreground">
                                                <span class="font-medium text-primary">Haz clic para subir</span> o arrastra un archivo aquí
                                            </div>
                                            <p class="text-xs text-muted-foreground">PDF, DOC, DOCX, TXT, ZIP hasta 10MB</p>
                                            <input 
                                                ref="fileInputRef"
                                                type="file" 
                                                accept=".pdf,.doc,.docx,.txt,.zip,.rar"
                                                class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                                                @change="handleFileUpload"
                                            >
                                        </div>
                                        
                                        <div v-else class="space-y-2 relative z-10">
                                            <div class="flex items-center justify-center space-x-2">
                                                <div class="h-8 w-8 text-muted-foreground">
                                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                </div>
                                                <div class="text-center">
                                                    <div class="text-sm font-medium text-foreground">{{ form.file.name }}</div>
                                                    <div class="text-xs text-muted-foreground">{{ formatFileSize(form.file.size) }}</div>
                                                </div>
                                            </div>
                                            <Button v-if="form.file" type="button" variant="outline" size="sm" @click="removeFile">
                                                Remover archivo
                                            </Button>
                                        </div>
                                    </div>
                                    <InputError :message="form.errors.file" />
                                </div>
                            </div>
                        </div>
                    </div>

             <!-- Sidebar -->
                <div class="space-y-6">
                        <!-- Tags -->
                        <div class="rounded-lg border bg-card p-6 shadow-sm">
                            <h3 class="mb-4 text-lg font-semibold text-card-foreground">Etiquetas</h3>
                            
                            <div class="grid gap-4">
                                <div v-if="props.availableTags.length === 0" class="text-center py-4">
                                    <p class="text-sm text-muted-foreground">No hay tags disponibles</p>
                                </div>
                                
                                <div v-else class="max-h-64 space-y-3 overflow-y-auto">
                                    <label 
                                        v-for="tag in props.availableTags" 
                                        :key="tag.id"
                                        class="flex items-center space-x-3 cursor-pointer group"
                                    >
                                        <input 
                                            type="checkbox" 
                                            :value="tag.id" 
                                            v-model="form.tag_categories"
                                            class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-background"
                                        >
                                        <span 
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium transition-colors group-hover:opacity-80"
                                            :style="{ 
                                                backgroundColor: tag.color + '20', 
                                                color: tag.color,
                                                borderColor: tag.color + '40'
                                            }"
                                            :class="'border'"
                                        >
                                            {{ tag.name }}
                                        </span>
                                    </label>
                                </div>
                                
                                <InputError :message="form.errors.tag_categories" />
                                
                                <div class="text-xs text-muted-foreground">
                                    Selecciona al menos un tag para categorizar tu publicación
                                </div>
                            </div>
                        </div>

                        <!-- Estado -->
                        <div class="rounded-lg border bg-card p-6 shadow-sm">
                            <h3 class="mb-4 text-lg font-semibold text-card-foreground">Estado</h3>
                            
                            <div class="grid gap-4">
                                <div class="flex items-center space-x-3">
                                    <input 
                                        id="is_premium" 
                                        type="checkbox" 
                                        v-model="form.is_premium"
                                        class="h-4 w-4 rounded border-input text-primary focus:ring-2 focus:ring-primary focus:ring-offset-2 dark:focus:ring-offset-background"
                                    >
                                    <Label for="is_premium" class="text-sm font-medium text-foreground">
                                        Contenido Premium
                                    </Label>
                                </div>
                                
                                <div class="text-xs text-muted-foreground">
                                    El contenido premium solo será visible para suscriptores
                                </div>
                            </div>
                        </div>

                        <!-- Información de Ayuda -->
                        <div class="rounded-lg border bg-muted/50 p-6">
                            <h3 class="mb-3 text-sm font-semibold text-foreground">Consejos</h3>
                            <ul class="space-y-2 text-xs text-muted-foreground">
                                <li>• Usa un título descriptivo y atractivo</li>
                                <li>• Selecciona tags relevantes para mejor categorización</li>
                                <li>• La imagen destacada aparecerá en las vistas previas</li>
                                <li>• Puedes guardar como borrador y publicar después</li>
                            </ul>
                        </div>
                </div>
        </div>
    </div>
    </AppLayout>
</template>