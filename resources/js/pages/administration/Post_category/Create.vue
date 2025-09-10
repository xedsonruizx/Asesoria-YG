<script setup lang="ts">
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { X, Save } from 'lucide-vue-next'
import { store } from '@/routes/post-categories'

interface Props {
    show: boolean
}

const props = defineProps<Props>()

const emit = defineEmits<{
    close: []
    created: []
}>()

const createForm = useForm({
    name: '',
    description: '',
    color: '#6B7280',
    is_active: true
})

const submitCreate = () => {
    createForm.post(store().url, {
        onSuccess: () => {
            createForm.reset()
            emit('created')
            emit('close')
        }
    })
}

const closeModal = () => {
    createForm.reset()
    createForm.clearErrors()
    emit('close')
}
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
        <div class="bg-card rounded-lg shadow-lg w-full max-w-md max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-foreground">Nueva Categoría</h2>
                    <button @click="closeModal" class="text-muted-foreground hover:text-foreground">
                        <X class="h-5 w-5" />
                    </button>
                </div>
                
                <form @submit.prevent="submitCreate" class="space-y-4">
                    <div>
                        <label for="create-name" class="block text-sm font-medium text-foreground mb-1">Nombre</label>
                        <input
                            id="create-name"
                            v-model="createForm.name"
                            type="text"
                            required
                            class="w-full px-3 py-2 border border-input rounded-md bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
                            placeholder="Nombre de la categoría"
                        />
                        <div v-if="createForm.errors.name" class="text-sm text-red-600 mt-1">{{ createForm.errors.name }}</div>
                    </div>
                    
                    <div>
                        <label for="create-description" class="block text-sm font-medium text-foreground mb-1">Descripción</label>
                        <textarea
                            id="create-description"
                            v-model="createForm.description"
                            rows="3"
                            class="w-full px-3 py-2 border border-input rounded-md bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent resize-none"
                            placeholder="Descripción de la categoría"
                        ></textarea>
                        <div v-if="createForm.errors.description" class="text-sm text-red-600 mt-1">{{ createForm.errors.description }}</div>
                    </div>
                    
                    <div>
                        <label for="create-color" class="block text-sm font-medium text-foreground mb-1">Color</label>
                        <input
                            id="create-color"
                            v-model="createForm.color"
                            type="color"
                            class="w-full h-10 border border-input rounded-md bg-background cursor-pointer"
                        />
                        <div v-if="createForm.errors.color" class="text-sm text-red-600 mt-1">{{ createForm.errors.color }}</div>
                    </div>
                    
                    <div class="flex items-center gap-2 hidden">
                        <input
                            
                            id="create-is_active"
                            v-model="createForm.is_active"
                            type="checkbox"
                            class="w-4 h-4 text-primary bg-background border-input rounded focus:ring-ring focus:ring-2"
                        />
                        <label for="create-is_active" class="text-sm font-medium text-foreground">Categoría activa</label>
                        <div v-if="createForm.errors.is_active" class="text-sm text-red-600 mt-1">{{ createForm.errors.is_active }}</div>
                    </div>
                    
                    <div class="flex gap-3 pt-4">
                        <button
                            type="button"
                            @click="closeModal"
                            class="flex-1 px-4 py-2 border border-input text-foreground hover:bg-muted rounded-md transition-colors"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-primary text-primary-foreground hover:bg-primary/90 rounded-md transition-colors disabled:opacity-50"
                        >
                            <Save class="h-4 w-4" />
                            {{ createForm.processing ? 'Guardando...' : 'Guardar Categoría' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>