<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { Trash2, AlertTriangle } from 'lucide-vue-next';
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
import { destroy } from '@/routes/post-categories';

interface PostCategory {
    id: number;
    name: string;
    description?: string;
    color?: string;
    is_active: boolean;
    posts_count: number;
    created_at: string;
    updated_at: string;
}

interface Props {
    category?: PostCategory | null;
    isOpen: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    confirm: [categoryId: number]
    'update:isOpen': [value: boolean]
}>();


const hasAssociatedPosts = computed(() => {
    return props.category ? props.category.posts_count > 0 : false;
});

const confirmDelete = async () => {
    console.log('Categoría a eliminar:', props.category);
    
    if (!props.category || hasAssociatedPosts.value) {
        return;
    }
    
    try {
        router.delete(destroy(props.category.id).url, {
            onSuccess: () => {
                console.log('Eliminación exitosa');
                emit('confirm', props.category!.id);
                closeModal();
            },
            onError: (errors) => {
                console.error('Error en eliminación:', errors);
                isDeleting.value = false;
            }
        });
    } catch (error) {
        console.error('Error en try-catch:', error);
        isDeleting.value = false;
    }
};

const closeModal = () => {
    emit('update:isOpen', false);
};
</script>

<template>
    <Dialog :open="isOpen && !!category" @update:open="(value) => emit('update:isOpen', value)">
        <DialogContent v-if="category" class="max-w-md">
            <DialogHeader class="space-y-3">
                <DialogTitle class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center" :class="hasAssociatedPosts ? 'bg-warning/10' : 'bg-destructive/10'">
                        <AlertTriangle v-if="hasAssociatedPosts" class="h-6 w-6 text-warning" />
                        <Trash2 v-else class="h-6 w-6 text-destructive" />
                    </div>
                    <span>{{ hasAssociatedPosts ? 'Acción no permitida' : 'Confirmar eliminación' }}</span>
                </DialogTitle>
                <DialogDescription class="text-left">
                    <span v-if="hasAssociatedPosts">
                        No puedes eliminar una categoría que tiene posts asociados.
                    </span>
                    <span v-else>
                        ¿Estás seguro de que quieres eliminar esta categoría?
                    </span>
                </DialogDescription>
            </DialogHeader>
            
            <div class="py-4">
                <div class="rounded-md p-4 border-l-4" :class="hasAssociatedPosts ? 'bg-warning/5 border-warning' : 'bg-muted/50 border-destructive'">
                    <div class="flex items-center gap-3 mb-2">
                        <div 
                            class="w-4 h-4 rounded-full border-2 border-white shadow-sm" 
                            :style="{ backgroundColor: category.color || '#6B7280' }"
                        ></div>
                        <p class="font-medium text-foreground">
                            "{{ category.name }}"
                        </p>
                    </div>
                    <p v-if="category.description" class="text-sm text-muted-foreground mb-2">
                        {{ category.description }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        <strong v-if="hasAssociatedPosts">Información:</strong>
                        <strong v-else>Advertencia:</strong>
                        <span v-if="hasAssociatedPosts">
                            Esta categoría tiene {{ category.posts_count }} post(s) asociado(s). Primero debes reasignar o eliminar estos posts.
                        </span>
                        <span v-else>
                            Esta acción no se puede deshacer. La categoría se eliminará definitivamente.
                        </span>
                    </p>
                </div>
            </div>
            
            <DialogFooter class="gap-3">
                <Button 
                    variant="outline" 
                    @click="closeModal"
                >
                    {{ hasAssociatedPosts ? 'Entendido' : 'Cancelar' }}
                </Button>
                <Button 
                    v-if="!hasAssociatedPosts"
                    variant="destructive" 
                    @click="confirmDelete"
                    class="flex items-center gap-2"
                >
                    <Trash2 class="h-4 w-4" />
                    <span >Eliminar categoría</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>