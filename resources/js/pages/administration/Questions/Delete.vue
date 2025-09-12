<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { X, Trash2, Loader2, AlertTriangle, Shield } from 'lucide-vue-next';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';

// Props
interface Question {
  id: number;
  question: string;
  question_type: string;
  category_id: number;
  is_active: boolean;
  evaluations_count?: number;
  created_at: string;
  updated_at: string;
}

const props = defineProps<{
  question: Question;
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
  // return (props.question.evaluations_count || 0) === 0;
});

// Función para cerrar el modal
const close = () => {
  isOpen.value = false;
  emit('close');
};

// Función para eliminar la pregunta
const deleteQuestion = () => {
  if (!canDelete.value) {
    return;
  }
  
  processing.value = true;
  
  // Obtener filtros del componente padre si están disponibles
  const currentUrl = new URL(window.location.href);
  const filters: any = {};
  
  // Extraer filtros de la URL actual
  if (currentUrl.searchParams.get('search')) filters.search = currentUrl.searchParams.get('search');
  if (currentUrl.searchParams.get('category_id')) filters.category_id = currentUrl.searchParams.get('category_id');
  if (currentUrl.searchParams.get('question_type')) filters.question_type = currentUrl.searchParams.get('question_type');
  if (currentUrl.searchParams.get('is_active')) filters.is_active = currentUrl.searchParams.get('is_active');
  if (currentUrl.searchParams.get('show_deleted')) filters.show_deleted = currentUrl.searchParams.get('show_deleted');
  
  router.delete(`/admin/questions/${props.question.id}`, {
    data: filters,
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
          Eliminar Pregunta
        </DialogTitle>
      </DialogHeader>
      
      <div class="space-y-6">
        <!-- Información de la pregunta -->
        <Card class="p-4 bg-muted/30">
          <div class="space-y-2">
            <h4 class="font-semibold text-foreground">{{ question.question }}</h4>
            <div class="flex items-center gap-4 text-sm text-muted-foreground">
              <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-md text-xs">
                {{ question.question_type }}
              </span>
              <span :class="question.is_active ? 'text-green-600' : 'text-red-600'">
                {{ question.is_active ? 'Activa' : 'Inactiva' }}
              </span>
              <span :class="(question.evaluations_count || 0) > 0 ? 'text-destructive font-medium' : 'text-muted-foreground'">
                {{ question.evaluations_count || 0 }} evaluación(es) asociada(s)
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
                No se puede eliminar esta pregunta
              </h4>
              <p class="text-sm text-destructive/80">
                Esta pregunta tiene <strong>{{ question.evaluations_count }} evaluación(es)</strong> asociada(s). 
                Para eliminarla, primero debes desasociar todas las evaluaciones de esta pregunta.
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
                ¿Estás seguro de que deseas eliminar esta pregunta?
              </h4>
              <p class="text-sm text-destructive/80">
                Esta acción no se puede deshacer. La pregunta será eliminada permanentemente.
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
          @click="deleteQuestion"
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