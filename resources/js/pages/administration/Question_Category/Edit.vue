<script setup lang="ts">
import { ref, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { X, Save } from 'lucide-vue-next'
import { update } from '@/routes/question-categories'

interface Category {
    id: number
    name: string
    description?: string
    color?: string
    slug: string
}

interface Props {
    show: boolean
    category?: Category
}

const props = defineProps<Props>()

const emit = defineEmits<{
    close: []
    updated: []
}>()

const editForm = useForm({
    name: '',
    description: '',
    color: '#6B7280'
})

// Cargar datos cuando se abre el modal o cambia la categoría
watch(() => [props.show, props.category], ([show, newCategory]) => {
    if (show && newCategory) {
        // Resetear el formulario con los datos de la categoría
        editForm.reset({
            name: newCategory.name,
            description: newCategory.description || '',
            color: newCategory.color || '#6B7280'
        })
        // También actualizar los valores directamente
        editForm.name = newCategory.name
        editForm.description = newCategory.description || ''
        editForm.color = newCategory.color || '#6B7280'
    }
}, { immediate: true })

const submitEdit = () => {
    if (!props.category) return
    
    editForm.put(update(props.category.slug).url, {
        onSuccess: () => {
            emit('updated')
            emit('close')
        }
    })
}

const closeModal = () => {
    editForm.clearErrors()
    emit('close')
}
</script>

<template>
    <div v-if="show && category" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
        <div class="bg-card rounded-lg shadow-lg w-full max-w-md max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-foreground">Editar Categoría de Pregunta</h2>
                    <button @click="closeModal" class="text-muted-foreground hover:text-foreground">
                        <X class="h-5 w-5" />
                    </button>
                </div>
                
                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div>
                        <label for="edit-name" class="block text-sm font-medium text-foreground mb-1">Nombre</label>
                        <input
                            id="edit-name"
                            v-model="editForm.name"
                            type="text"
                            required
                            class="w-full px-3 py-2 border border-input rounded-md bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
                            placeholder="Nombre de la categoría"
                        />
                        <div v-if="editForm.errors.name" class="text-sm text-red-600 mt-1">{{ editForm.errors.name }}</div>
                    </div>
                    
                    <div>
                        <label for="edit-description" class="block text-sm font-medium text-foreground mb-1">Descripción</label>
                        <textarea
                            id="edit-description"
                            v-model="editForm.description"
                            rows="3"
                            class="w-full px-3 py-2 border border-input rounded-md bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent resize-none"
                            placeholder="Descripción de la categoría"
                        ></textarea>
                        <div v-if="editForm.errors.description" class="text-sm text-red-600 mt-1">{{ editForm.errors.description }}</div>
                    </div>
                    
                    <div>
                        <label for="edit-color" class="block text-sm font-medium text-foreground mb-1">Color</label>
                        <input
                            id="edit-color"
                            v-model="editForm.color"
                            type="color"
                            class="w-full h-10 border border-input rounded-md bg-background cursor-pointer"
                        />
                        <div v-if="editForm.errors.color" class="text-sm text-red-600 mt-1">{{ editForm.errors.color }}</div>
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
                            :disabled="editForm.processing"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 bg-primary text-primary-foreground hover:bg-primary/90 rounded-md transition-colors disabled:opacity-50"
                        >
                            <Save class="h-4 w-4" />
                            {{ editForm.processing ? 'Actualizando...' : 'Actualizar Categoría' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>