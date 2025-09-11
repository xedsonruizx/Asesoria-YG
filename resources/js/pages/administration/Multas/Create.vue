<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { X, Upload, FileText, AlertCircle } from 'lucide-vue-next';

interface Props {
  show: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  close: [];
  created: [];
}>();

// Form data
const form = useForm({
  name: '',
  description: '',
  file: null as File | null,
});

const fileInput = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);

// Watch para resetear el formulario cuando se cierre el modal
watch(() => props.show, (newValue) => {
  if (!newValue) {
    form.reset();
    form.clearErrors();
    if (fileInput.value) {
      fileInput.value.value = '';
    }
  }
});

// Funciones de manejo de archivos
const handleFileSelect = (event: Event) => {
  const target = event.target as HTMLInputElement;
  if (target.files && target.files[0]) {
    form.file = target.files[0];
  }
};

const handleDrop = (event: DragEvent) => {
  event.preventDefault();
  isDragging.value = false;
  
  if (event.dataTransfer?.files && event.dataTransfer.files[0]) {
    form.file = event.dataTransfer.files[0];
  }
};

const handleDragOver = (event: DragEvent) => {
  event.preventDefault();
  isDragging.value = true;
};

const handleDragLeave = () => {
  isDragging.value = false;
};

const removeFile = () => {
  form.file = null;
  if (fileInput.value) {
    fileInput.value.value = '';
  }
};

const triggerFileInput = () => {
  fileInput.value?.click();
};

// Submit form
const submit = () => {
  form.post('/multas', {
    onSuccess: () => {
      emit('created');
    },
    onError: (errors) => {
      console.error('Error al crear multa:', errors);
    }
  });
};

// Funciones de utilidad
const formatFileSize = (bytes: number) => {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const getFileIcon = (filename: string) => {
  const extension = filename.split('.').pop()?.toLowerCase();
  return FileText; // Puedes expandir esto para diferentes tipos de archivo
};
</script>

<template>
  <!-- Modal Backdrop -->
  <div 
    v-if="show" 
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
    @click.self="emit('close')"
  >
    <!-- Modal Content -->
    <div class="bg-card rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
      <!-- Header -->
      <div class="flex items-center justify-between p-6 border-b border-border">
        <h2 class="text-xl font-semibold text-foreground">Nueva Multa</h2>
        <button 
          @click="emit('close')"
          class="text-muted-foreground hover:text-foreground transition-colors"
        >
          <X class="h-5 w-5" />
        </button>
      </div>

      <!-- Body -->
      <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)]">
        <form @submit.prevent="submit" class="space-y-6">
          <!-- Nombre -->
          <div>
            <label for="name" class="block text-sm font-medium text-foreground mb-2">
              Nombre de la Multa *
            </label>
            <input
              id="name"
              v-model="form.name"
              type="text"
              class="w-full px-3 py-2 border border-input rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent bg-background text-foreground"
              placeholder="Ej: Multa por exceso de velocidad"
              required
            />
            <div v-if="form.errors.name" class="mt-1 text-sm text-red-600 flex items-center gap-1">
              <AlertCircle class="h-4 w-4" />
              {{ form.errors.name }}
            </div>
          </div>

          <!-- Descripción -->
          <div>
            <label for="description" class="block text-sm font-medium text-foreground mb-2">
              Descripción *
            </label>
            <textarea
              id="description"
              v-model="form.description"
              rows="4"
              class="w-full px-3 py-2 border border-input rounded-md focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent bg-background text-foreground resize-none"
              placeholder="Describe los detalles de la multa..."
              required
            ></textarea>
            <div v-if="form.errors.description" class="mt-1 text-sm text-red-600 flex items-center gap-1">
              <AlertCircle class="h-4 w-4" />
              {{ form.errors.description }}
            </div>
          </div>

          <!-- Archivo -->
          <div>
            <label class="block text-sm font-medium text-foreground mb-2">
              Archivo (Opcional)
            </label>
            
            <!-- Área de drop -->
            <div 
              :class="[
                'border-2 border-dashed rounded-lg p-6 text-center transition-colors',
                isDragging 
                  ? 'border-primary bg-primary/5' 
                  : 'border-border hover:border-primary/50'
              ]"
              @drop="handleDrop"
              @dragover="handleDragOver"
              @dragleave="handleDragLeave"
            >
              <input
                ref="fileInput"
                type="file"
                class="hidden"
                accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png"
                @change="handleFileSelect"
              />
              
              <!-- Archivo seleccionado -->
              <div v-if="form.file" class="space-y-3">
                <div class="flex items-center justify-center gap-3 p-3 bg-muted rounded-md">
                  <component :is="getFileIcon(form.file.name)" class="h-8 w-8 text-primary" />
                  <div class="flex-1 text-left">
                    <div class="font-medium text-foreground">{{ form.file.name }}</div>
                    <div class="text-sm text-muted-foreground">{{ formatFileSize(form.file.size) }}</div>
                  </div>
                  <button
                    type="button"
                    @click="removeFile"
                    class="text-red-600 hover:text-red-700 transition-colors"
                  >
                    <X class="h-5 w-5" />
                  </button>
                </div>
                <button
                  type="button"
                  @click="triggerFileInput"
                  class="text-sm text-primary hover:text-primary/80 transition-colors"
                >
                  Cambiar archivo
                </button>
              </div>
              
              <!-- Estado sin archivo -->
              <div v-else class="space-y-3">
                <Upload class="h-12 w-12 text-muted-foreground mx-auto" />
                <div>
                  <p class="text-foreground font-medium">Arrastra un archivo aquí o</p>
                  <button
                    type="button"
                    @click="triggerFileInput"
                    class="text-primary hover:text-primary/80 transition-colors font-medium"
                  >
                    selecciona uno
                  </button>
                </div>
                <p class="text-sm text-muted-foreground">
                  Formatos soportados: PDF, DOC, DOCX, TXT, JPG, PNG (máx. 10MB)
                </p>
              </div>
            </div>
            
            <div v-if="form.errors.file" class="mt-1 text-sm text-red-600 flex items-center gap-1">
              <AlertCircle class="h-4 w-4" />
              {{ form.errors.file }}
            </div>
          </div>
        </form>
      </div>

      <!-- Footer -->
      <div class="flex items-center justify-end gap-3 p-6 border-t border-border">
        <button
          type="button"
          @click="emit('close')"
          class="px-4 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors"
        >
          Cancelar
        </button>
        <button
          @click="submit"
          :disabled="form.processing"
          class="px-4 py-2 text-sm font-medium bg-primary text-primary-foreground hover:bg-primary/90 rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {{ form.processing ? 'Creando...' : 'Crear Multa' }}
        </button>
      </div>
    </div>
  </div>
</template>