<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { X, Trash2, Loader2, AlertTriangle, Shield } from 'lucide-vue-next';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';

// Props
interface Multa {
  id: number;
  name: string;
  description: string;
  file_path?: string;
  questions_count?: number;
  created_at: string;
  updated_at: string;
}

const props = defineProps<{
  multa: Multa;
}>();

// Emits
const emit = defineEmits<{
  close: [];
  deleted: [];
}>();

const processing = ref(false);
const isOpen = ref(true);

// Computed para verificar si se puede eliminar
const canDelete = computed(() => {
  // Por ahora siempre permitir eliminar hasta que se implemente la relación
  return true;
  // TODO: Cuando se implemente la relación, usar:
  // return (props.multa.questions_count || 0) === 0;
});

// Función para cerrar el modal
const close = () => {
  isOpen.value = false;
  emit('close');
};

// Función para eliminar la multa
const deleteMulta = () => {
  if (!canDelete.value) {
    return;
  }
  
  processing.value = true;
  
  router.delete(`/multas/${props.multa.id}`, {
    onSuccess: () => {
      emit('deleted');
      close();
    },
    onError: (errors) => {
      console.error('Error al eliminar:', errors);
    },
    onFinish: () => {
      processing.value = false;
    }
  });
};
</script>

<template>
  <Dialog :open="isOpen" @update:open="close">
    <DialogContent class="sm:max-w-[500px]">
      <DialogHeader>
        <DialogTitle class="text-xl font-semibold text-foreground flex items-center gap-2">
          <AlertTriangle class="w-5 h-5 text-destructive" />
          Eliminar Multa
        </DialogTitle>
      </DialogHeader>
      
      <div class="space-y-6">
        <!-- Información de la multa -->
        <Card class="p-4 bg-muted/30">
          <div class="space-y-2">
            <h4 class="font-semibold text-foreground">{{ multa.name }}</h4>
            <p v-if="multa.description" class="text-sm text-muted-foreground">
              {{ multa.description }}
            </p>
            <div class="flex items-center gap-4 text-sm text-muted-foreground">
              <span v-if="multa.file_path" class="text-blue-600">
                📎 Archivo adjunto
              </span>
              <span :class="(multa.questions_count || 0) > 0 ? 'text-destructive font-medium' : 'text-muted-foreground'">
                {{ multa.questions_count || 0 }} pregunta(s) asociada(s)
              </span>
            </div>
          </div>
        </Card>

        <!-- Advertencia si no se puede eliminar -->
        <Card v-if="!canDelete" class="p-4 border-destructive bg-destructive/5">
          <div class="flex items-start gap-3">
            <Shield class="w-5 h-5 text-destructive mt-0.5 flex-shrink-0" />
            <div class="space-y-2">
              <h4 class="font-semibold text-destructive">
                No se puede eliminar esta multa
              </h4>
              <p class="text-sm text-destructive/80">
                Esta multa tiene <strong>{{ multa.questions_count }} pregunta(s)</strong> asociada(s). 
                Para eliminarla, primero debes desasociar todas las preguntas de esta multa.
              </p>
            </div>
          </div>
        </Card>

        <!-- Confirmación si se puede eliminar -->
        <Card v-else class="p-4 border-destructive/20 bg-destructive/5">
          <div class="flex items-start gap-3">
            <AlertTriangle class="w-5 h-5 text-destructive mt-0.5 flex-shrink-0" />
            <div class="space-y-2">
              <h4 class="font-semibold text-destructive">
                ¿Estás seguro de que deseas eliminar esta multa?
              </h4>
              <p class="text-sm text-destructive/80">
                Esta acción no se puede deshacer. La multa y su archivo asociado serán eliminados permanentemente.
              </p>
            </div>
          </div>
        </Card>
      </div>
      
      <!-- Botones de acción -->
      <div class="flex justify-end space-x-3 pt-4 border-t border-border">
        <Button
          variant="outline"
          @click="close"
          :disabled="processing"
        >
          {{ canDelete ? 'Cancelar' : 'Cerrar' }}
        </Button>
        <Button
          v-if="canDelete"
          variant="destructive"
          @click="deleteMulta"
          :disabled="processing"
          class="flex items-center gap-2"
        >
          <Loader2 v-if="processing" class="w-4 h-4 animate-spin" />
          <Trash2 v-else class="w-4 h-4" />
          {{ processing ? 'Eliminando...' : 'Eliminar' }}
        </Button>
      </div>
    </DialogContent>
  </Dialog>
</template>