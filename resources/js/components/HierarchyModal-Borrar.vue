<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/InputError.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Folder, FolderOpen, Plus, Edit, Trash2, Eye, EyeOff } from 'lucide-vue-next';

// ... existing code ...

// Estados para errores
const errors = ref({});
const isCreating = ref(false);
const isEditing = ref(false);

// ... existing code ...

// Función para limpiar errores
const clearErrors = () => {
    errors.value = {};
};

// Función para crear carpeta
const createFolder = () => {
    if (!newFolderName.value.trim()) return;
    
    clearErrors();
    isCreating.value = true;
    
    const data = {
        nombre: newFolderName.value,
        descripcion: newFolderDescription.value || null,
        color: newFolderColor.value || null,
        icono: newFolderIcon.value || null,
        es_activa: newFolderActive.value,
    };
    
    // Solo incluir padre_id si hay un padre
    if (selectedParent.value) {
        data.padre_id = selectedParent.value.id;
    }
    
    router.post('/carpetas', data, {
        onSuccess: () => {
            resetForm();
            showModal.value = false;
        },
        onError: (pageErrors) => {
            errors.value = pageErrors;
        },
        onFinish: () => {
            isCreating.value = false;
        }
    });
};

// Función para actualizar carpeta
const updateFolder = () => {
    if (!editingFolder.value || !editFolderName.value.trim()) return;
    
    clearErrors();
    isEditing.value = true;
    
    const data = {
        nombre: editFolderName.value,
        descripcion: editFolderDescription.value || null,
        color: editFolderColor.value || null,
        icono: editFolderIcon.value || null,
        es_activa: editFolderActive.value,
        _method: 'PUT'
    };
    
    router.post(`/carpetas/${editingFolder.value.id}`, data, {
        onSuccess: () => {
            resetForm();
            showModal.value = false;
        },
        onError: (pageErrors) => {
            errors.value = pageErrors;
        },
        onFinish: () => {
            isEditing.value = false;
        }
    });
};

// ... existing code ...

// Limpiar errores cuando se cierra el modal
watch(showModal, (newValue) => {
    if (!newValue) {
        clearErrors();
    }
});
</script>

<template>
    <Dialog v-model:open="showModal">
        <DialogContent class="sm:max-w-[600px] max-h-[80vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>
                    {{ isEditMode ? 'Editar Carpeta' : 'Crear Nueva Carpeta' }}
                </DialogTitle>
                <DialogDescription>
                    {{ isEditMode ? 'Modifica los datos de la carpeta' : 'Completa los datos para crear una nueva carpeta' }}
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4 py-4">
                <!-- Campo Nombre -->
                <div class="grid grid-cols-4 items-start gap-4">
                    <Label for="folder-name" class="text-right pt-2">
                        Nombre *
                    </Label>
                    <div class="col-span-3">
                        <Input
                            id="folder-name"
                            v-model="isEditMode ? editFolderName : newFolderName"
                            placeholder="Nombre de la carpeta"
                            :class="{ 'border-red-500': errors.nombre }"
                        />
                        <InputError :message="errors.nombre" />
                    </div>
                </div>

                <!-- Campo Descripción -->
                <div class="grid grid-cols-4 items-start gap-4">
                    <Label for="folder-description" class="text-right pt-2">
                        Descripción
                    </Label>
                    <div class="col-span-3">
                        <Textarea
                            id="folder-description"
                            v-model="isEditMode ? editFolderDescription : newFolderDescription"
                            placeholder="Descripción opcional"
                            rows="3"
                            :class="{ 'border-red-500': errors.descripcion }"
                        />
                        <InputError :message="errors.descripcion" />
                    </div>
                </div>

                <!-- Campo Color -->
                <div class="grid grid-cols-4 items-start gap-4">
                    <Label for="folder-color" class="text-right pt-2">
                        Color
                    </Label>
                    <div class="col-span-3">
                        <Input
                            id="folder-color"
                            v-model="isEditMode ? editFolderColor : newFolderColor"
                            type="color"
                            :class="{ 'border-red-500': errors.color }"
                        />
                        <InputError :message="errors.color" />
                    </div>
                </div>

                <!-- Campo Ícono -->
                <div class="grid grid-cols-4 items-start gap-4">
                    <Label for="folder-icon" class="text-right pt-2">
                        Ícono
                    </Label>
                    <div class="col-span-3">
                        <Input
                            id="folder-icon"
                            v-model="isEditMode ? editFolderIcon : newFolderIcon"
                            placeholder="Nombre del ícono (opcional)"
                            :class="{ 'border-red-500': errors.icono }"
                        />
                        <InputError :message="errors.icono" />
                    </div>
                </div>

                <!-- Campo Estado Activo -->
                <div class="grid grid-cols-4 items-center gap-4">
                    <Label for="folder-active" class="text-right">
                        Estado
                    </Label>
                    <div class="col-span-3 flex items-center space-x-2">
                        <input
                            id="folder-active"
                            type="checkbox"
                            v-model="isEditMode ? editFolderActive : newFolderActive"
                            class="rounded border-gray-300"
                        />
                        <Label for="folder-active" class="text-sm">
                            Carpeta activa
                        </Label>
                        <InputError :message="errors.es_activa" />
                    </div>
                </div>

                <!-- Carpeta padre (solo en modo creación) -->
                <div v-if="!isEditMode" class="grid grid-cols-4 items-start gap-4">
                    <Label class="text-right pt-2">
                        Carpeta padre
                    </Label>
                    <div class="col-span-3">
                        <p class="text-sm text-gray-600">
                            {{ selectedParent ? selectedParent.nombre : 'Carpeta raíz' }}
                        </p>
                        <InputError :message="errors.padre_id" />
                    </div>
                </div>
            </div>

            <DialogFooter>
                <Button 
                    type="button" 
                    variant="outline" 
                    @click="showModal = false"
                    :disabled="isCreating || isEditing"
                >
                    Cancelar
                </Button>
                <Button 
                    type="button" 
                    @click="isEditMode ? updateFolder() : createFolder()"
                    :disabled="isCreating || isEditing"
                >
                    <span v-if="isCreating || isEditing" class="mr-2">
                        <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </span>
                    {{ isEditMode ? 'Actualizar' : 'Crear' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>