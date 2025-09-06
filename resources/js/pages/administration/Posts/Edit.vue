<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { edit, update } from '@/routes/posts'
import { router } from '@inertiajs/vue3'

interface Props {
  post: {
    id: number
    title: string
    content: string
    excerpt: string
    image?: string
    file?: string
    status: 'draft' | 'published'
    is_premium: boolean
    slug: string
    meta_description?: string
    tags?: Array<{
      id: number
      name: string
      color: string
      slug: string
    }>
    author_id: number
    published_at?: string
    created_at: string
    updated_at: string
  }
  availableTags: Array<{
    id: number
    name: string
    color: string
    slug: string
  }>
}

const props = defineProps<Props>()

const form = useForm({
  title: props.post.title,
  content: props.post.content,
  excerpt: props.post.excerpt,
  image: null as File | null,
  file: null as File | null,
  status: props.post.status,
  is_premium: props.post.is_premium,
  slug: props.post.slug,
  meta_description: props.post.meta_description || '',
  tags: props.post.tags?.map(tag => tag.id) || [],
  _method: 'PUT'
})

const imagePreview = ref<string | null>(props.post.image || null)
const filePreview = ref<string | null>(props.post.file || null)
const isLoading = ref(false)

const handleImageChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  
  if (file) {
    form.image = file
    const reader = new FileReader()
    reader.onload = (e) => {
      imagePreview.value = e.target?.result as string
    }
    reader.readAsDataURL(file)
  }
}

const handleFileChange = (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]
  
  if (file) {
    form.file = file
    filePreview.value = file.name
  }
}

const removeImage = () => {
  form.image = null
  imagePreview.value = null
  const input = document.getElementById('image') as HTMLInputElement
  if (input) input.value = ''
}

const removeFile = () => {
  form.file = null
  filePreview.value = null
  const input = document.getElementById('file') as HTMLInputElement
  if (input) input.value = ''
}

const generateSlug = () => {
  if (form.title) {
    form.slug = form.title
      .toLowerCase()
      .replace(/[^a-z0-9\s-]/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')
      .trim()
  }
}

const submitForm = () => {
  isLoading.value = true
  
  form.transform((data) => ({
    ...data,
    _method: 'PUT'
  })).post(update.url({ post: props.post.id }), {
    onSuccess: () => {
      isLoading.value = false
      router.visit('/administration/posts')
    },
    onError: () => {
      isLoading.value = false
    }
  })
}

const saveDraft = () => {
  form.status = 'draft'
  submitForm()
}

const publish = () => {
  form.status = 'published'
  submitForm()
}

const wordCount = computed(() => {
  return form.content ? form.content.split(/\s+/).filter(word => word.length > 0).length : 0
})

const characterCount = computed(() => {
  return form.content ? form.content.length : 0
})
</script>

<template>
  <div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <!-- Header -->
      <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <div>
              <h1 class="text-2xl font-bold text-gray-900">Editar Publicación</h1>
              <p class="text-sm text-gray-600 mt-1">Modifica los detalles de tu publicación</p>
            </div>
            <div class="flex space-x-3">
              <button
                @click="saveDraft"
                :disabled="isLoading"
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
              >
                <span v-if="isLoading && form.status === 'draft'" class="flex items-center">
                  <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-gray-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Guardando...
                </span>
                <span v-else>Guardar Borrador</span>
              </button>
              <button
                @click="publish"
                :disabled="isLoading"
                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
              >
                <span v-if="isLoading && form.status === 'published'" class="flex items-center">
                  <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Actualizando...
                </span>
                <span v-else>Actualizar Publicación</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <form @submit.prevent="submitForm" class="space-y-6">
        <!-- Información básica -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Información Básica</h2>
          </div>
          <div class="px-6 py-4 space-y-6">
            <!-- Título -->
            <div>
              <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                Título *
              </label>
              <input
                id="title"
                v-model="form.title"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Ingresa el título de la publicación"
                @input="generateSlug"
              />
              <div v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</div>
            </div>

            <!-- Slug -->
            <div>
              <label for="slug" class="block text-sm font-medium text-gray-700 mb-2">
                Slug (URL amigable) *
              </label>
              <input
                id="slug"
                v-model="form.slug"
                type="text"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="url-amigable-del-post"
              />
              <p class="mt-1 text-sm text-gray-500">Se genera automáticamente desde el título, pero puedes editarlo</p>
              <div v-if="form.errors.slug" class="mt-1 text-sm text-red-600">{{ form.errors.slug }}</div>
            </div>

            <!-- Categoría -->
            <div>
              <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">
                Categoría
              </label>
              <select
                id="category_id"
                v-model="form.category_id"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              >
                <option value="">Selecciona una categoría</option>
                <option v-for="category in categories" :key="category.id" :value="category.id">
                  {{ category.name }}
                </option>
              </select>
              <div v-if="form.errors.category_id" class="mt-1 text-sm text-red-600">{{ form.errors.category_id }}</div>
            </div>

            <!-- Extracto -->
            <div>
              <label for="excerpt" class="block text-sm font-medium text-gray-700 mb-2">
                Extracto *
              </label>
              <textarea
                id="excerpt"
                v-model="form.excerpt"
                rows="3"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Breve descripción de la publicación (máximo 160 caracteres)"
                maxlength="160"
              ></textarea>
              <div class="flex justify-between mt-1">
                <div v-if="form.errors.excerpt" class="text-sm text-red-600">{{ form.errors.excerpt }}</div>
                <div class="text-sm text-gray-500">{{ form.excerpt?.length || 0 }}/160 caracteres</div>
              </div>
            </div>

            <!-- Meta descripción -->
            <div>
              <label for="meta_description" class="block text-sm font-medium text-gray-700 mb-2">
                Meta Descripción (SEO)
              </label>
              <textarea
                id="meta_description"
                v-model="form.meta_description"
                rows="2"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                placeholder="Descripción para motores de búsqueda (máximo 160 caracteres)"
                maxlength="160"
              ></textarea>
              <div class="flex justify-between mt-1">
                <div v-if="form.errors.meta_description" class="text-sm text-red-600">{{ form.errors.meta_description }}</div>
                <div class="text-sm text-gray-500">{{ form.meta_description?.length || 0 }}/160 caracteres</div>
              </div>
            </div>

            <!-- Tags -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Tags de Categoría
              </label>
              <div class="space-y-2 max-h-40 overflow-y-auto border border-gray-300 rounded-md p-3">
                <div 
                  v-for="tag in availableTags" 
                  :key="tag.id"
                  class="flex items-center space-x-2"
                >
                  <input
                    :id="`tag-${tag.id}`"
                    v-model="form.tags"
                    :value="tag.id"
                    type="checkbox"
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                  />
                  <label 
                    :for="`tag-${tag.id}`" 
                    class="flex items-center space-x-2 cursor-pointer flex-1"
                  >
                    <span 
                      class="inline-block w-3 h-3 rounded-full"
                      :style="{ backgroundColor: tag.color }"
                    ></span>
                    <span class="text-sm text-gray-700">{{ tag.name }}</span>
                  </label>
                </div>
              </div>
              <p class="mt-1 text-sm text-gray-500">Selecciona las categorías que aplican a esta publicación</p>
              <div v-if="form.errors.tags" class="mt-1 text-sm text-red-600">{{ form.errors.tags }}</div>
            </div>
          </div>
        </div>

        <!-- Contenido -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Contenido</h2>
          </div>
          <div class="px-6 py-4">
            <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
              Contenido de la publicación *
            </label>
            <textarea
              id="content"
              v-model="form.content"
              rows="15"
              required
              class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
              placeholder="Escribe el contenido de tu publicación aquí..."
            ></textarea>
            <div class="flex justify-between mt-2">
              <div v-if="form.errors.content" class="text-sm text-red-600">{{ form.errors.content }}</div>
              <div class="text-sm text-gray-500">
                {{ wordCount }} palabras • {{ characterCount }} caracteres
              </div>
            </div>
          </div>
        </div>

        <!-- Multimedia -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Multimedia</h2>
          </div>
          <div class="px-6 py-4 space-y-6">
            <!-- Imagen destacada -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Imagen Destacada
              </label>
              <div class="space-y-4">
                <div v-if="imagePreview" class="relative inline-block">
                  <img :src="imagePreview" alt="Vista previa" class="w-32 h-32 object-cover rounded-lg border border-gray-300" />
                  <button
                    @click="removeImage"
                    type="button"
                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600"
                  >
                    ×
                  </button>
                </div>
                <input
                  id="image"
                  type="file"
                  accept="image/*"
                  @change="handleImageChange"
                  class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                />
              </div>
              <p class="mt-1 text-sm text-gray-500">Formatos soportados: JPG, PNG, GIF. Tamaño máximo: 2MB</p>
              <div v-if="form.errors.image" class="mt-1 text-sm text-red-600">{{ form.errors.image }}</div>
            </div>

            <!-- Archivo adjunto -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-2">
                Archivo Adjunto
              </label>
              <div class="space-y-4">
                <div v-if="filePreview" class="flex items-center space-x-2 p-3 bg-gray-50 rounded-lg border border-gray-200">
                  <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                  </svg>
                  <span class="text-sm text-gray-700 flex-1">{{ filePreview }}</span>
                  <button
                    @click="removeFile"
                    type="button"
                    class="text-red-500 hover:text-red-700 text-sm"
                  >
                    Eliminar
                  </button>
                </div>
                <input
                  id="file"
                  type="file"
                  @change="handleFileChange"
                  class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                />
              </div>
              <p class="mt-1 text-sm text-gray-500">Cualquier tipo de archivo. Tamaño máximo: 10MB</p>
              <div v-if="form.errors.file" class="mt-1 text-sm text-red-600">{{ form.errors.file }}</div>
            </div>
          </div>
        </div>

        <!-- Configuración de publicación -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
          <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Configuración de Publicación</h2>
          </div>
          <div class="px-6 py-4 space-y-6">
            <!-- Estado -->
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-3">
                Estado de la publicación
              </label>
              <div class="space-y-2">
                <label class="flex items-center">
                  <input
                    v-model="form.status"
                    type="radio"
                    value="draft"
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                  />
                  <span class="ml-2 text-sm text-gray-700">
                    <span class="font-medium">Borrador</span>
                    <span class="text-gray-500"> - Solo visible para ti</span>
                  </span>
                </label>
                <label class="flex items-center">
                  <input
                    v-model="form.status"
                    type="radio"
                    value="published"
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                  />
                  <span class="ml-2 text-sm text-gray-700">
                    <span class="font-medium">Publicado</span>
                    <span class="text-gray-500"> - Visible para todos los usuarios</span>
                  </span>
                </label>
              </div>
            </div>

            <!-- Contenido premium -->
            <div>
              <label class="flex items-start">
                <input
                  v-model="form.is_premium"
                  type="checkbox"
                  class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded mt-0.5"
                />
                <div class="ml-3">
                  <span class="text-sm font-medium text-gray-700">Contenido Premium</span>
                  <p class="text-sm text-gray-500">
                    Marca esta publicación como contenido premium. Solo los usuarios con suscripción activa podrán acceder al contenido completo.
                  </p>
                </div>
              </label>
            </div>
          </div>
        </div>

        <!-- Información adicional -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-blue-800">Información importante</h3>
              <div class="mt-2 text-sm text-blue-700">
                <ul class="list-disc list-inside space-y-1">
                  <li>Los campos marcados con * son obligatorios</li>
                  <li>Las imágenes se redimensionarán automáticamente para optimizar el rendimiento</li>
                  <li>Los borradores solo son visibles para ti hasta que los publiques</li>
                  <li>El contenido premium requiere suscripción activa para ser visualizado completamente</li>
                  <li>Tamaño máximo para imágenes: 2MB</li>
                  <li>Tamaño máximo para archivos: 10MB</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>