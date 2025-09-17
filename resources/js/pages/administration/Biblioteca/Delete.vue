<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { X, Trash2, Loader2, AlertTriangle, BookOpen } from 'lucide-vue-next';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';

// Props
interface Biblioteca {
  id: number;
  titulo: string;
  slug: string;
  descripcion: string;
  padre_id?: number;
  is_premium: boolean;
  orden: number;
  hijos_count: number;
  nivel: number;
  ruta_completa: string;
  created_at: string;
  updated_at: string;
}

const props = defineProps<{
  biblioteca: Biblioteca;
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
  // Verificar si realmente tiene hijos
  return !props.biblioteca.hijos_count || props.biblioteca.hijos_count === 0;
});

// Función para cerrar el modal
const close = () => {
  isOpen.value = false;
  emit('close');
};

// Función para eliminar el elemento
const deleteBiblioteca = () => {
  if (!canDelete.value) {
    return;
  }
  
  processing.value = true;
  
  // Obtener filtros del componente padre si están disponibles
  const currentUrl = new URL(window.location.href);
  const filters: any = {};
  
  // Extraer filtros de la URL actual
  if (currentUrl.searchParams.get('search')) filters.search = currentUrl.searchParams.get('search');
  if (currentUrl.searchParams.get('is_premium')) filters.is_premium = currentUrl.searchParams.get('is_premium');
  if (currentUrl.searchParams.get('padre_id')) filters.padre_id = currentUrl.searchParams.get('padre_id');
  if (currentUrl.searchParams.get('show_deleted')) filters.show_deleted = currentUrl.searchParams.get('show_deleted');
  
  router.delete(`/admin/biblioteca/${props.biblioteca.id}`, {
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
          Eliminar Elemento de Biblioteca
        </DialogTitle>
      </DialogHeader>
      
      <div class="space-y-6">
        <!-- Información del elemento -->
        <Card class="p-4 bg-muted/30">
          <div class="space-y-2">
            <div class="flex items-center gap-2">
              <BookOpen class="h-4 w-4 text-primary" />
              <h4 class="font-semibold text-foreground">{{ biblioteca.titulo }}</h4> 
            </div>
            <div class="flex items-center gap-4 text-sm text-muted-foreground">
              <span class="px-2 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300 rounded-md text-xs">
                /{{ biblioteca.slug }}
              </span>
              <span v-if="biblioteca.is_premium" 
                    class="px-2 py-1 bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300 rounded-md text-xs">
                Premium
              </span>
              <span v-else 
                    class="px-2 py-1 bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300 rounded-md text-xs">
                Gratuito
              </span>
            </div>
            <div class="text-sm text-muted-foreground">
              <strong>Ruta:</strong> {{ biblioteca.ruta_completa }}
            </div>
            <div class="text-sm text-muted-foreground">
              <strong>Orden:</strong> {{ biblioteca.orden }}
            </div>
          </div>
        </Card>

        <!-- Verificación de dependencias -->
        <div v-if="!canDelete" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
          <div class="flex items-start gap-3">
            <AlertTriangle class="w-5 h-5 text-destructive flex-shrink-0 mt-0.5" />
            <div>
              <h4 class="font-semibold text-destructive mb-2">No se puede eliminar</h4>
              <p class="text-sm text-destructive/80 mb-3">
                Este elemento no se puede eliminar porque tiene {{ biblioteca.hijos_count }} elemento{{ biblioteca.hijos_count !== 1 ? 's' : '' }} hijo{{ biblioteca.hijos_count !== 1 ? 's' : '' }} asociado{{ biblioteca.hijos_count !== 1 ? 's' : '' }}.
              </p>
              <div class="bg-destructive/5 rounded-md p-3">
                <p class="text-sm text-destructive/80">
                  <strong>Elementos hijos:</strong> {{ biblioteca.hijos_count }}
                </p>
                <p class="text-xs text-destructive/60 mt-1">
                  Elimina primero todos los elementos hijos antes de eliminar este elemento.
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Mensaje cuando no hay hijos pero aún no se puede eliminar por otras razones -->
        <div v-else-if="biblioteca.hijos_count === 0" class="bg-green-50 border border-green-200 rounded-lg p-4 dark:bg-green-900/10 dark:border-green-800/30">
          <div class="flex items-start gap-3">
            <BookOpen class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" />
            <div>
              <h4 class="font-semibold text-green-800 dark:text-green-300 mb-2">Sin elementos hijos</h4>
              <p class="text-sm text-green-700 dark:text-green-400">
                Este elemento no tiene elementos hijos asociados y puede ser eliminado de forma segura.
              </p>
            </div>
          </div>
        </div>

        <!-- Confirmación de eliminación -->
        <div v-else class="bg-muted/50 border border-border rounded-lg p-4">
          <div class="flex items-start gap-3">
            <AlertTriangle class="w-5 h-5 text-orange-500 flex-shrink-0 mt-0.5" />
            <div>
              <h4 class="font-semibold text-foreground mb-2">Confirmar eliminación</h4>
              <p class="text-sm text-muted-foreground mb-3">
                ¿Estás seguro de que quieres eliminar este elemento de la biblioteca?
              </p>
              <div class="bg-orange-50 dark:bg-orange-900/20 rounded-md p-3">
                <p class="text-sm text-orange-800 dark:text-orange-300">
                  <strong>Nota:</strong> Esta acción se puede deshacer. El elemento se moverá a la papelera y podrá ser restaurado posteriormente.
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Botones -->
        <div class="flex justify-end gap-3 pt-4 border-t border-border">
          <Button
            type="button"
            variant="outline"
            @click="close"
            :disabled="processing"
          >
            Cancelar
          </Button>
          <Button
            v-if="canDelete"
            type="button"
            variant="destructive"
            @click="deleteBiblioteca"
            :disabled="processing"
            class="min-w-[120px]"
          >
            <Loader2 v-if="processing" class="w-4 h-4 mr-2 animate-spin" />
            <Trash2 v-else class="w-4 h-4 mr-2" />
            {{ processing ? 'Eliminando...' : 'Eliminar' }}
          </Button>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>