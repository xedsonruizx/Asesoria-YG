<script setup lang="ts">
import { ref } from 'vue';
import { Trash2 } from 'lucide-vue-next';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';

interface Post {
  id: number;
  title: string;
}

interface Props {
  post: Post;
  isOpen: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    confirm: [postId: number]
    'update:isOpen': [value: boolean]
}>()

const isDeleting = ref(false);

const confirmDelete = async () => {
  isDeleting.value = true;
  try {
    emit('confirm', props.post.slug);
  } finally {
    isDeleting.value = false;
  }
};

const closeModal = () => {
  emit('update:isOpen', false);
};
</script>

<template>
  <Dialog :open="isOpen" @update:open="(value) => emit('update:isOpen', value)">
    <DialogContent class="max-w-md">
      <DialogHeader class="space-y-3">
        <DialogTitle class="flex items-center gap-3">
          <div class="flex-shrink-0 w-12 h-12 bg-destructive/10 rounded-full flex items-center justify-center">
            <Trash2 class="h-6 w-6 text-destructive" />
          </div>
          <span>Confirmar eliminación</span>
        </DialogTitle>
        <DialogDescription class="text-left">
          ¿Estás seguro de que quieres eliminar esta publicación?
        </DialogDescription>
      </DialogHeader>
      
      <div class="py-4">
        <div class="bg-muted/50 rounded-md p-4 border-l-4 border-destructive">
          <p class="font-medium text-foreground mb-2">
            "{{ post.title }}"
          </p>
          <p class="text-sm text-muted-foreground">
            <strong>Advertencia:</strong> Esta acción no se puede deshacer. Todos los datos asociados a esta publicación se perderán definitivamente.
          </p>
        </div>
      </div>
      
      <DialogFooter class="gap-3">
        <Button 
          variant="outline" 
          :disabled="isDeleting"
          @click="closeModal"
        >
          Cancelar
        </Button>
        <Button 
          variant="destructive" 
          @click="confirmDelete"
          :disabled="isDeleting"
          class="flex items-center gap-2"
        >
          <Trash2 class="h-4 w-4" />
          <span v-if="isDeleting">Eliminando...</span>
          <span v-else>Eliminar publicación</span>
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>