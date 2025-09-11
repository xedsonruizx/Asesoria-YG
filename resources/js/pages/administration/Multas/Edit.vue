<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { X, Upload, FileText, AlertCircle, Download, Trash2 } from 'lucide-vue-next';

interface Multa {
  id: number;
  name: string;
  description: string;
  path_file?: string;
  file_url?: string;
}

interface Props {
  show: boolean;
  multa: Multa | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{
  close: [];
  updated: [];
}>();

// Form data
const form = useForm({
  name: '',
  description: '',
  file: null as File | null,
});

const fileInput = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);
const showRemoveFileConfirm = ref(false);

// Watch para cargar datos cuando se abra el modal
watch(() => [props.show, props.multa], ([show, multa]) => {
  if (show && multa) {
    form.name = multa.name;
    form.description = multa.description;
    form.file = null;
    form.clearErrors();
  } else if (!show) {
    form.reset();
    form.clearErrors();
    showRemoveFileConfirm.value = false;
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

const removeNewFile = () => {
  form.file = null;
  if (fileInput.value) {
    fileInput.value.value = '';
  }
};

const triggerFileInput = () => {
  fileInput.value?.click();
};

const downloadCurrentFile = () => {
  if (props.multa?.file_url) {
    window.open(props.multa.file_url, '_blank');
  }
};

const removeCurrentFile = () => {
  if (!props.multa) return;
  
  // Hacer petición para eliminar el archivo actual
  fetch(`/multas/${props.multa.id}/remove-file`, {
    method: 'DELETE',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      'Accept': 'application/json',
    },
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      showRemoveFileConfirm.value = false;
      emit('updated'); // Recargar la página
    }
  })
  .catch(error => {
    console.error('Error al eliminar archivo:', error);
  });
};

// Submit form
const submit = () => {
  if (!props.multa) return;
  
  form.patch(`/multas/${props.multa.id}`, {
    onSuccess: () => {
      emit('updated');
    },
    onError: (errors) => {
      console.error('Error al actualizar multa:', errors);
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

const getFileExtension = (filename?: string) => {
  if (!filename) return '';
  return filename.split('.').pop()?.toUpperCase() || '';
};
</script>

<template>
  <!-- Modal Backdrop -->
  <div 
    v-if="show && multa" 
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
    @click.self="emit('close')"
  >
    <!-- Modal Content -->
    <div class="bg-card rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
      <!-- Header -->
      <div class="flex items-center justify-between p-6 border-b border-border">
        <h2 class="text-xl font-semibold text-foreground">Editar Multa</h2>
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
            <label for="edit-name" class="block text-sm font-medium text-foreground mb-2">
              Nombre de la Multa *
            </label>
            <input
              id="edit-name"
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
            <label for="edit-description" class="block text-sm font-medium text-foreground mb-2">
              Descripción *
            </label>
            <textarea
              id="edit-description"
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

          <!-- Archivo actual -->
          <div v-if="multa.path_file && !showRemoveFileConfirm">
            <label class="block text-sm font-medium text-foreground mb-2">
              Archivo Actual
            </label>
            <div class="flex items-center gap-3 p-3 bg-muted rounded-md">
              <FileText class="h-8 w-8 text-primary" />
              <div class="flex-1">
                <div class="font-medium text-foreground">{{ getFileExtension(multa.path_file) }} - Archivo actual</div>
                <div class="text-sm text-muted-foreground">Subido el {{ new Date(multa.updated_at).toLocaleDateString('es-ES') }}</div>
              </div>
              <div class="flex gap-2">
                <button
                  type="button"
                  @click="downloadCurrentFile"
                  class="text-blue-600 hover:text-blue-700 transition-colors p-1"
                  title="Descargar archivo"
                >
                  <Download class="h-4 w-4" />
                </button>
                <button
                  type="button"
                  @click="showRemoveFileConfirm = true"
                  class="text-red-600 hover:text-red-700 transition-colors p-1"
                  title="Eliminar archivo"
                >
                  <Trash2 class="h-4 w-4" />
                </button>
              </div>
            </div>
          </div>

          <!-- Confirmación de eliminación de archivo -->
          <div v-if="showRemoveFileConfirm" class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-md">
            <div class="flex items-start gap-3">
              <AlertCircle class="h-5 w-5 text-red-600 mt-0.5" />
              <div class="flex-1">
                <h4 class="font-medium text-red-800 dark:text-red-200">¿Eliminar archivo actual?</h4>
                <p class="text-sm text-red-700 dark:text-red-300 mt-1">
                  Esta acción no se puede deshacer. El archivo se eliminará permanentemente.
                </p>
                <div class="flex gap-2 mt-3">
                  <button
                    type="button"
                    @click="removeCurrentFile"
                    class="px-3 py-1 text-sm bg-red-600 text-white hover:bg-red-700 rounded transition-colors"
                  >
                    Sí, eliminar
                  </button>
                  <button
                    type="button"
                    @click="showRemoveFileConfirm = false"
                    class="px-3 py-1 text-sm bg-gray-200 text-gray-800 hover:bg-gray-300 rounded transition-colors"
                  >
                    Cancelar
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Nuevo archivo -->
          <div>
            <label class="block text-sm font-medium text-foreground mb-2">
              {{ multa.path_file ? 'Reemplazar Archivo' : 'Agregar Archivo' }} (Opcional)
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
              
              <!-- Nuevo archivo seleccionado -->
              <div v-if="form.file" class="space-y-3">
                <div class="flex items-center justify-center gap-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-md">
                  <component :is="getFileIcon(form.file.name)" class="h-8 w-8 text-green-600" />
                  <div class="flex-1 text-left">
                    <div class="font-medium text-green-800 dark:text-green-200">{{ form.file.name }}</div>
                    <div class="text-sm text-green-600 dark:text-green-300">{{ formatFileSize(form.file.size) }} - Nuevo archivo</div>
                  </div>
                  <button
                    type="button"
                    @click="removeNewFile"
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
              
              <!-- Estado sin nuevo archivo -->
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
          {{ form.processing ? 'Actualizando...' : 'Actualizar Multa' }}
        </button>
      </div>
    </div>
  </div>
</template>