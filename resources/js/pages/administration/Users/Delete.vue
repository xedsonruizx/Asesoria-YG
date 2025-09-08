<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
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

interface User {
    id: number;
    name: string;
    email: string;
    role: string;
    created_at: string;
    updated_at: string;
}

interface Props {
    user: User;
    isOpen: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    confirm: [userId: number]
    'update:isOpen': [value: boolean]
}>();

const isDeleting = ref(false);

const isCurrentUser = computed(() => {
    const currentUser = usePage().props.auth?.user as User;
    return currentUser?.id === props.user.id;
});

const confirmDelete = async () => {
    if (isCurrentUser.value) {
        return; // No permitir eliminación del usuario actual
    }
    
    isDeleting.value = true;
    try {
        emit('confirm', props.user.id);
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
                    <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center" :class="isCurrentUser ? 'bg-warning/10' : 'bg-destructive/10'">
                        <AlertTriangle v-if="isCurrentUser" class="h-6 w-6 text-warning" />
                        <Trash2 v-else class="h-6 w-6 text-destructive" />
                    </div>
                    <span>{{ isCurrentUser ? 'Acción no permitida' : 'Confirmar eliminación' }}</span>
                </DialogTitle>
                <DialogDescription class="text-left">
                    <span v-if="isCurrentUser">
                        No puedes eliminar tu propia cuenta desde esta sección.
                    </span>
                    <span v-else>
                        ¿Estás seguro de que quieres eliminar este usuario?
                    </span>
                </DialogDescription>
            </DialogHeader>
            
            <div class="py-4">
                <div class="rounded-md p-4 border-l-4" :class="isCurrentUser ? 'bg-warning/5 border-warning' : 'bg-muted/50 border-destructive'">
                    <p class="font-medium text-foreground mb-2">
                        "{{ user.name }}" ({{ user.email }})
                    </p>
                    <p class="text-sm text-muted-foreground">
                        <strong v-if="isCurrentUser">Información:</strong>
                        <strong v-else>Advertencia:</strong>
                        <span v-if="isCurrentUser">
                            Esta es tu cuenta actual. Para eliminar tu cuenta, ve a la sección de configuración de perfil.
                        </span>
                        <span v-else>
                            Esta acción no se puede deshacer. Todos los datos asociados a este usuario se perderán definitivamente.
                        </span>
                    </p>
                </div>
            </div>
            
            <DialogFooter class="gap-3">
                <Button 
                    variant="outline" 
                    :disabled="isDeleting"
                    @click="closeModal"
                >
                    {{ isCurrentUser ? 'Entendido' : 'Cancelar' }}
                </Button>
                <Button 
                    v-if="!isCurrentUser"
                    variant="destructive" 
                    @click="confirmDelete"
                    :disabled="isDeleting"
                    class="flex items-center gap-2"
                >
                    <Trash2 class="h-4 w-4" />
                    <span v-if="isDeleting">Eliminando...</span>
                    <span v-else>Eliminar usuario</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>