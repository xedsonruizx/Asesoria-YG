<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { X, Trash2, Loader2, AlertTriangle, Shield } from 'lucide-vue-next';
import { destroy } from '@/routes/question-categories';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';

// Props
interface EvaluationCategory {
  id: number;
  name: string;
  slug: string;
  description?: string;
  color?: string;
  icon?: string;
  max_score: number;
  order: number;
  is_active: boolean;
  questions_count: number;
}

const props = defineProps<{
  category: EvaluationCategory;
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
  return props.category.questions_count === 0;
});

// Función para eliminar la categoría
const deleteCategory = () => {
  if (!canDelete.value) {
    return;
  }
  
  processing.value = true;
  
  router.delete(destroy(props.category.id).url, {
    onSuccess: () => {
      emit('deleted');
      close();
    },
    onFinish: () => {
      processing.value = false;
    }
  });
};

// Función para cerrar el modal
const close = () => {
  isOpen.value = false;
  emit('close');
};
</script>

<template>
  <Dialog :open="isOpen" @update:open="close">
    <DialogContent class="sm:max-w-[500px]">
      <DialogHeader>
        <DialogTitle class="text-xl font-semibold text-foreground flex items-center gap-2">
          <AlertTriangle class="w-5 h-5 text-destructive" />
          Eliminar Categoría de Evaluación
        </DialogTitle>
      </DialogHeader>
      
      <div class="space-y-6">
        <!-- Información de la categoría -->
        <Card class="p-4 bg-muted/30">
          <div class="space-y-2">
            <h4 class="font-semibold text-foreground">{{ category.name }}</h4>
            <p v-if="category.description" class="text-sm text-muted-foreground">
              {{ category.description }}
            </p>
            <div class="flex items-center gap-4 text-sm text-muted-foreground">
              <span :class="category.questions_count > 0 ? 'text-destructive font-medium' : 'text-muted-foreground'">
                {{ category.questions_count }} pregunta(s) asociada(s)
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
                No se puede eliminar esta categoría
              </h4>
              <p class="text-sm text-destructive/80">
                Esta categoría tiene <strong>{{ category.questions_count }} pregunta(s)</strong> asociada(s). 
                Para eliminarla, primero debes eliminar o reasignar todas las preguntas a otra categoría.
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
                ¿Estás seguro de que deseas eliminar esta categoría?
              </h4>
              <p class="text-sm text-destructive/80">
                Esta acción no se puede deshacer. La categoría será eliminada permanentemente.
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
          @click="deleteCategory"
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