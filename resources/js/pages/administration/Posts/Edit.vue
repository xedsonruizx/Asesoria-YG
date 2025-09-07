<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { Head, router, Link  } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as postsIndex, show as postShow, update } from '@/routes/posts';
import { ArrowLeft, Save, FileText, ImageIcon, Upload, X, Download, Image, Video } from 'lucide-vue-next';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import AttachedFiles from '@/components/AttachedFiles.vue'

// Obtener la función route desde las props de la página
const route = (name: string, params?: any) => {
    return page.props.ziggy.routes[name] ? 
        page.props.ziggy.routes[name].uri.replace(/\{[^}]+\}/g, (match: string) => {
            const param = match.slice(1, -1);
            return params && params[param] ? params[param] : match;
        }) : name;
};
interface PostForm {
    id: number;
    title: string;
    content: string;
    meta_description: string;
    status: 'draft' | 'published' | 'archived';
    is_premium: boolean;
    featured_image: File | null;
    file: File | null;
    tag_categories: number[];
}

interface Props {
    post: {
        id: number;
        title: string;
        content: string;
        meta_description: string;
        status: 'draft' | 'published' | 'archived';
        is_premium: boolean;
        image_path?: string;
        file_path?: string;
        tag_categories?: Array<{ id: number; name: string; color: string; slug: string; }>;
    };
    availableTags?: Array<{
        id: number;
        name: string;
        color: string;
        slug: string;
    }>;
}

const props = withDefaults(defineProps<Props>(), {
    post: () => ({
        id: 0,
        title: '',
        content: '',
        meta_description: '',
        status: 'draft',
        is_premium: false,
        image_path: undefined,
        file_path: undefined,
        tag_categories: []
    }),
    availableTags: () => []
});

// Verificación defensiva
if (!props.post || typeof props.post !== 'object') {
    console.error('Post data is invalid:', props.post);
}
const form = useForm<PostForm>({
    id: props.post.id,
    title: props.post.title,
    content: props.post.content,
    meta_description: props.post.meta_description,
    status: props.post.status,
    is_premium: props.post.is_premium || false,
    featured_image: null, // Correcto
    file: null, // Correcto
    tag_categories: props.post.tag_categories ? props.post.tag_categories.map(tag => tag.id) : []
});

const imageInputRef = ref<HTMLInputElement>();
const fileInputRef = ref<HTMLInputElement>();
const imagePreview = ref<string | null>(null);
const isDragOver = ref(false);
const isFileDragOver = ref(false);

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Publicaciones', href: postsIndex().url },
    { title: props.post.title, href: postShow(props.post.id).url },
    { title: 'Editar Publicación', current: true },
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
        if (file.type.startsWith('image/')) {
            form.featured_image = file;
            const reader = new FileReader();
            reader.onload = (e) => {
                imagePreview.value = e.target?.result as string;
            };
            reader.readAsDataURL(file);
        }
    }
};

const removeImage = () => {
    form.featured_image = null;
    imagePreview.value = null;
    if (imageInputRef.value) {
        imageInputRef.value.value = '';
    }
};

// Funciones para manejo de archivos
const handleFileUpload = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        // Validar tamaño de archivo (10MB máximo)
        if (file.size > 10240 * 1024) {
            alert('El archivo no puede ser mayor a 10MB');
            return;
        }
        
        form.file = file;
    }
};

const handleFileDragOver = (event: DragEvent) => {
    event.preventDefault();
    event.stopPropagation();
    isFileDragOver.value = true;
};

const handleFileDragLeave = () => {
    isFileDragOver.value = false;
};

const handleFileDrop = (event: DragEvent) => {
    event.preventDefault();
    event.stopPropagation();
    isFileDragOver.value = false;
    
    const files = event.dataTransfer?.files;
    if (files && files.length > 0) {
        const file = files[0];
        form.file = file;
    }
};

const removeFile = () => {
    form.file = null;
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};


// Computed properties para URLs de archivos existentes
const currentImageUrl = computed(() => {
    if (props.post.image_path) {
        return `/storage/${props.post.image_path}`;
    }
    return null;
});

const currentFileUrl = computed(() => {
    return props.post.file_path ? `/storage/${props.post.file_path}` : null;
});

// Función para cargar automáticamente los archivos actuales
const loadCurrentFilesAutomatically = async () => {
    try {
        // Cargar imagen destacada actual si existe
        if (props.post.image_path && currentImageUrl.value) {
            const imageResponse = await fetch(currentImageUrl.value);
            if (imageResponse.ok) {
                const imageBlob = await imageResponse.blob();
                const imageFile = new File([imageBlob], `imagen-${props.post.id}.${imageBlob.type.split('/')[1]}`, {
                    type: imageBlob.type
                });
                
                form.featured_image = imageFile;
                
                // Crear preview de la imagen
                const reader = new FileReader();
                reader.onload = (e) => {
                    imagePreview.value = e.target?.result as string;
                };
                reader.readAsDataURL(imageFile);
                
                console.log('✅ Imagen destacada cargada automáticamente');
            }
        }
        
        // Cargar archivo adjunto actual si existe
        if (props.post.file_path && currentFileUrl.value) {
            const fileResponse = await fetch(currentFileUrl.value);
            if (fileResponse.ok) {
                const fileBlob = await fileResponse.blob();
                const fileName = props.post.file_path.split('/').pop() || `archivo-${props.post.id}`;
                const attachedFile = new File([fileBlob], fileName, {
                    type: fileBlob.type
                });
                
                form.file = attachedFile;
                console.log('✅ Archivo adjunto cargado automáticamente');
            }
        }
        
        if (props.post.image_path || props.post.file_path) {
            console.log('🔄 Archivos actuales cargados automáticamente en el formulario');
        }
        
    } catch (error) {
        console.error('❌ Error al cargar archivos automáticamente:', error);
    }
};

// Ejecutar carga automática al montar el componente
onMounted(() => {
    loadCurrentFilesAutomatically();
});

// Función para detectar tipo de archivo
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

// Función para cargar archivos actuales en el formulario
const loadCurrentFiles = async () => {
    try {
        // Cargar imagen actual si existe
        if (props.post.image_path && currentImageUrl.value) {
            const imageResponse = await fetch(currentImageUrl.value);
            if (imageResponse.ok) {
                const imageBlob = await imageResponse.blob();
                const imageFile = new File([imageBlob], props.post.image_path.split('/').pop() || 'image.jpg', {
                    type: imageBlob.type
                });
                form.featured_image = imageFile;
                imagePreview.value = currentImageUrl.value;
                console.log('Imagen actual cargada:', imageFile.name);
            }
        }
        
        // Cargar archivo actual si existe
        if (props.post.file_path && currentFileUrl.value) {
            const fileResponse = await fetch(currentFileUrl.value);
            if (fileResponse.ok) {
                const fileBlob = await fileResponse.blob();
                const fileName = props.post.file_path.split('/').pop() || 'archivo';
                const file = new File([fileBlob], fileName, {
                    type: fileBlob.type
                });
                form.file = file;
                console.log('Archivo actual cargado:', file.name);
            }
        }
        
        alert('Archivos actuales cargados en el formulario exitosamente');
    } catch (error) {
        console.error('Error al cargar archivos actuales:', error);
        alert('Error al cargar los archivos actuales');
    }
};

// Función de envío del formulario
const submitForm = (status: 'draft' | 'published' | 'archived') => {

    // Validaciones mejoradas
    if (!form.title || form.title.trim() === '') {
        alert('El título es obligatorio');
        console.log('Validación falló: título vacío');
        return;
    }
    
    if (!form.content || form.content.trim() === '') {
        alert('El contenido es obligatorio');
        console.log('Validación falló: contenido vacío');
        return;
    }
    
    if (!form.tag_categories || !Array.isArray(form.tag_categories) || form.tag_categories.length === 0) {
        alert('Debe seleccionar al menos un tag');
        console.log('Validación falló: tags vacíos', form.tag_categories);
        return;
    }
    
    if (!form.meta_description || form.meta_description.trim() === '') {
        alert('La meta descripción es obligatoria');
        console.log('Validación falló: meta descripción vacía');
        return;
    }
    
    console.log('Todas las validaciones pasaron, enviando formulario...');
    
    form.status = status;
    



declare global {
    function route(name: string, params?: any): string;
}


    // Debug: verificar datos antes del envío
    console.log('Datos del formulario antes del envío:', {
        id: form.id,
        title: form.title,
        content: form.content,
        meta_description: form.meta_description,
        status: form.status,
        is_premium: form.is_premium,
        tag_categories: form.tag_categories,
        featured_image: form.featured_image,
        file: form.file
    });
    
    // Usar la ruta POST específica para archivos
    form.post(`/posts/${props.post.id}/update-with-files`, {
        forceFormData: true,
        onSuccess: () => {
            console.log('Formulario enviado exitosamente');
            router.visit(`/posts/${props.post.id}`);
        },
        onError: (errors) => {
            console.error('Errores de validación:', errors);
            alert('Error al actualizar la publicación. Revisa la consola para más detalles.');
        },
        onBefore: () => {
            console.log('Iniciando envío del formulario...');
        },
        onFinish: () => {
            console.log('Envío del formulario completado');
        }
    });
};

const saveDraft = () => submitForm('draft');
const publish = () => submitForm('published');

const goBack = () => {
    router.visit(postShow(props.post.id).url);
};





// Llamar debugForm() antes de submitForm para ver qué campo está causando el problema
// console.log('Props recibidas:', props);
// console.log('Post data:', props.post);
// console.log('Available tags:', props.availableTags);



</script>

<template>
    <Head title="Editar Publicación" />
    
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="">
                <div class="bg-card rounded-lg p-6 shadow-sm border border-border">
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4 mb-4">
                        <div class="flex-1">
                            <h1 class="text-xl sm:text-2xl font-bold mb-2 text-foreground">Editar Publicación</h1>
                            <p class="text-muted-foreground text-sm sm:text-base">Modifica los detalles de tu publicación</p>
                        </div>
                        <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 w-full sm:w-auto">
                            <button
                                @click="submitForm(form.status)"
                                :disabled="form.processing || !form.title?.trim() || !form.content?.trim() || !form.tag_categories?.length || !form.meta_description?.trim()"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2 text-xs sm:text-sm font-medium text-primary-foreground bg-primary hover:bg-primary/90 rounded-md transition-colors disabled:opacity-50"
                            >
                                <Save class="h-3 w-3 sm:h-4 sm:w-4 flex-shrink-0" />
                                <span class="truncate">{{ form.processing ? 'Actualizando...' : (form.status === 'draft' ? 'Guardar Borrador' : 'Actualizar Publicación') }}</span>
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

                            <!-- Meta Descripción -->
                            <div class="grid gap-2">
                                <Label for="meta_description">Meta Descripción</Label>
                                <textarea
                                    id="meta_description"
                                    v-model="form.meta_description"
                                    rows="3"
                                    class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    placeholder="Descripción breve para SEO (máximo 160 caracteres)"
                                    maxlength="160"
                                    required
                                ></textarea>
                                <div class="flex justify-between text-xs text-muted-foreground">
                                    <span>Descripción para motores de búsqueda</span>
                                    <span>{{ form.meta_description?.length }}/160</span>
                                </div>
                                <InputError :message="form.errors.meta_description" />
                            </div>

                            <!-- Contenido -->
                            <div class="grid gap-2">
                                <Label for="content">Contenido</Label>
                                <textarea
                                    id="content"
                                    v-model="form.content"
                                    rows="12"
                                    class="flex min-h-[200px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    placeholder="Escribe el contenido de tu publicación aquí..."
                                    required
                                ></textarea>
                                <InputError :message="form.errors.content" />
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
                                <InputError :message="form.errors.featured_image" />
                                <Button  v-if="form.featured_image" type="button" variant="outline" size="sm" @click="removeImage">
                                        Remover imagen
                                </Button>
                            </div>

                            

                            <!-- Archivo Adjunto -->
                            <div class="grid gap-2">
                                <Label>Archivo Adjunto</Label>
                                <div 
                                    class="relative rounded-lg border-2 border-dashed border-input p-6 text-center transition-colors"
                                    :class="{
                                        'border-primary bg-primary/5': isFileDragOver,
                                        'hover:border-primary/50 hover:bg-accent/50': !isFileDragOver
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
                                    
                                    <div v-else class="space-y-2">
                                        <div class="flex items-center justify-center space-x-2">
                                            <FileText class="h-8 w-8 text-primary" />
                                            <div class="text-left">
                                                <p class="text-sm font-medium text-foreground">{{ form.file.name }}</p>
                                                <p class="text-xs text-muted-foreground">{{ (form.file.size / 1024 / 1024).toFixed(2) }} MB</p>
                                            </div>
                                        </div>
                                        <Button v-if="form.file" type="button" variant="outline" size="sm" class="relative z-10" @click="removeFile">
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
                        <h3 class="mb-4 text-lg font-semibold text-card-foreground">Tags de Categoría</h3>
                        
                        <div class="grid gap-4">
                            <div v-if="props.availableTags.length === 0" class="text-center py-4">
                                <p class="text-sm text-muted-foreground">No hay tags disponibles</p>
                            </div>
                            
                            <div v-else class="max-h-64 space-y-3 overflow-y-auto">
                                <label 
                                    v-for="tag in props.availableTags" 
                                    :key="tag.id"
                                    class="flex items-center space-x-3 cursor-pointer p-2 rounded-md hover:bg-accent transition-colors"
                                >
                                    <input
                                        type="checkbox"
                                        :value="tag.id"
                                        v-model="form.tag_categories"
                                        class="rounded border-gray-300 text-primary focus:ring-primary"
                                    >
                                    <div class="flex items-center space-x-2">
                                        <span 
                                            class="inline-block w-3 h-3 rounded-full" 
                                            :style="{ backgroundColor: tag.color }"
                                        ></span>
                                        <span class="text-sm font-medium text-foreground">{{ tag.name }}</span>
                                    </div>
                                </label>
                            </div>
                            <InputError :message="form.errors.tag_categories" />
                        </div>
                    </div>

          
               

                    <!-- Configuración -->
                    <div class="bg-card rounded-lg p-6 shadow-sm border border-border">
                        <h3 class="mb-4 text-lg font-semibold text-card-foreground">Configuración</h3>
                        
                        <div class="space-y-4">
                            <!-- Estado -->
                            <div class="grid gap-2">
                                <Label for="status">Estado</Label>
                                <select
                                    id="status"
                                    v-model="form.status"
                                    class="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    required
                                >
                                    <option value="draft">Borrador</option>
                                    <option value="published">Publicado</option>
                                </select>
                                <InputError :message="form.errors.status" />
                            </div>

                            <!-- Premium -->
                            <div class="flex items-center space-x-2">
                                <input
                                    id="is_premium"
                                    type="checkbox"
                                    v-model="form.is_premium"
                                    class="rounded border-gray-300 text-primary focus:ring-primary"
                                >
                                <Label for="is_premium" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                    Contenido Premium
                                </Label>
                            </div>
                        </div>
                    </div>

                    <!-- Archivos Actuales -->
                    <AttachedFiles 
                        :imageUrl="post.image_path ? `/storage/${post.image_path}` : null"
                        :fileUrl="post.file_path ? `/storage/${post.file_path}` : null"
                        :title="post.title"
                    />


                </div>
            </div>
        </div>
    </AppLayout>
</template>
