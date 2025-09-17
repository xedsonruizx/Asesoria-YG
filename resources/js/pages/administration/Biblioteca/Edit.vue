<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { X, BookOpen, Loader2, Lock, Unlock } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import RichTextEditor from '@/components/ui/rich-text-editor/RichTextEditor.vue';

// Props
interface Biblioteca {
  id: number;
  titulo: string;
  slug: string;
  descripcion: string;
  padre_id?: number;
  is_premium: boolean;
  orden: number;
  created_at: string;
  updated_at: string;
}

const props = defineProps<{
  biblioteca: Biblioteca | null;
  show: boolean;
}>();

// Emits
const emit = defineEmits<{
  close: [];
  updated: [];
}>();

const processing = ref(false);


// Datos del formulario - inicializar con valores por defecto
const form = ref({
  titulo: '',
  slug: '',
  descripcion: '',
  padre_id: null as number | null,
  is_premium: false,
  orden: 0
});

const errors = ref<Record<string, string>>({});

// Inicializar el formulario cuando biblioteca cambie
watch(() => props.biblioteca, (newBiblioteca) => {
  if (newBiblioteca) {
    form.value = {
      titulo: newBiblioteca.titulo,
      slug: newBiblioteca.slug,
      descripcion: newBiblioteca.descripcion,
      padre_id: newBiblioteca.padre_id || null,
      is_premium: newBiblioteca.is_premium,
      orden: newBiblioteca.orden
    };
  }
}, { immediate: true });

// Elementos padre disponibles (se cargarán del backend)
const elementosPadre = ref<Array<{
  id: number;
  titulo: string;
  nivel: number;
}>>([]);

// Función para cerrar el modal
const close = () => {
  emit('close');
};

// Función para generar slug automáticamente
const generateSlug = () => {
  if (form.value.titulo && form.value.slug === props.biblioteca.slug) {
    form.value.slug = form.value.titulo
      .toLowerCase()
      .replace(/[áàäâ]/g, 'a')
      .replace(/[éèëê]/g, 'e')
      .replace(/[íìïî]/g, 'i')
      .replace(/[óòöô]/g, 'o')
      .replace(/[úùüû]/g, 'u')
      .replace(/[ñ]/g, 'n')
      .replace(/[^a-z0-9\s-]/g, '')
      .replace(/\s+/g, '-')
      .replace(/-+/g, '-')
      .trim('-');
  }
};

// Función para actualizar el elemento
const updateBiblioteca = () => {
  processing.value = true;
  errors.value = {};
  
  // Obtener filtros del componente padre si están disponibles
  const currentUrl = new URL(window.location.href);
  const filters: any = {};
  
  // Extraer filtros de la URL actual
  if (currentUrl.searchParams.get('search')) filters.search = currentUrl.searchParams.get('search');
  if (currentUrl.searchParams.get('is_premium')) filters.is_premium = currentUrl.searchParams.get('is_premium');
  if (currentUrl.searchParams.get('padre_id')) filters.padre_id = currentUrl.searchParams.get('padre_id');
  if (currentUrl.searchParams.get('show_deleted')) filters.show_deleted = currentUrl.searchParams.get('show_deleted');
  
  router.put(`/admin/biblioteca/${props.biblioteca.id}`, {
    ...form.value,
    ...filters
  }, {
    onSuccess: () => {
      emit('updated');
      close();
    },
    onError: (responseErrors) => {
      errors.value = responseErrors;
    },
    onFinish: () => {
      processing.value = false;
    }
  });
};

// Cargar elementos padre al montar el componente
const loadElementosPadre = () => {
  // En una implementación real, esto vendría del backend
  // Por ahora usamos datos de ejemplo
  elementosPadre.value = [];
};

// Cargar elementos padre
onMounted(() => {
  loadElementosPadre();
});
</script>

<template>
  <!-- Overlay de fondo -->
  <div 
    v-if="show" 
    class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
  >
    <!-- Card Modal -->
    <Card class="w-full max-w-4xl max-h-[95vh] overflow-hidden">
      <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-4 border-b">
        <CardTitle class="text-xl font-semibold text-foreground flex items-center gap-2">
          <BookOpen class="w-5 h-5 text-primary" />
          Editar Elemento de Biblioteca
        </CardTitle>
        <Button 
          variant="ghost" 
          size="sm" 
          @click="close"
          class="h-8 w-8 p-0"
        >
          <X class="h-4 w-4" />
        </Button>
      </CardHeader>
      
      <CardContent class="overflow-y-auto max-h-[calc(95vh-80px)] p-6">
        <!-- Loading state -->
        <div v-if="!biblioteca" class="flex items-center justify-center py-12">
          <div class="text-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary mx-auto mb-4"></div>
            <p class="text-muted-foreground">Cargando información...</p>
          </div>
        </div>

        <!-- Form content when biblioteca exists -->
        <form v-else @submit.prevent="updateBiblioteca" class="space-y-6">
          <!-- Título -->
          <div>
            <label class="block text-sm font-medium text-foreground mb-2">
              Título *
            </label>
            <input
              v-model="form.titulo"
              @blur="generateSlug"
              type="text"
              required
              class="w-full px-3 py-2 border border-input rounded-md bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
              :class="{ 'border-destructive': errors.titulo }"
              placeholder="Ingresa el título del elemento"
            />
            <p v-if="errors.titulo" class="mt-1 text-sm text-destructive">{{ errors.titulo }}</p>
          </div>

          <!-- Slug -->
          <div>
            <label class="block text-sm font-medium text-foreground mb-2">
              Slug *
            </label>
            <input
              v-model="form.slug"
              type="text"
              required
              class="w-full px-3 py-2 border border-input rounded-md bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
              :class="{ 'border-destructive': errors.slug }"
              placeholder="slug-del-elemento"
            />
            <p class="mt-1 text-xs text-muted-foreground">
              Debe ser único. Cambiar el slug puede afectar enlaces existentes.
            </p>
            <p v-if="errors.slug" class="mt-1 text-sm text-destructive">{{ errors.slug }}</p>
          </div>

          <!-- Elemento Padre -->
          <div>
            <label class="block text-sm font-medium text-foreground mb-2">
              Elemento Padre
            </label>
            <select
              v-model="form.padre_id"
              class="w-full px-3 py-2 border border-input rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
              :class="{ 'border-destructive': errors.padre_id }"
            >
              <option :value="null">Seleccione</option>
              <option v-for="elemento in elementosPadre" :key="elemento.id" :value="elemento.id">
                {{ '—'.repeat(elemento.nivel) }}{{ elemento.nivel > 0 ? ' ' : '' }}{{ elemento.titulo }}
              </option>
            </select>
            <p class="mt-1 text-xs text-muted-foreground">
              No puedes seleccionar este elemento o sus descendientes como padre
            </p>
            <p v-if="errors.padre_id" class="mt-1 text-sm text-destructive">{{ errors.padre_id }}</p>
          </div>

          <!-- Descripción -->
          <div>
            <label class="block text-sm font-medium text-foreground mb-2">
              Descripción *
            </label>
            <RichTextEditor
              v-model="form.descripcion"
              placeholder="Ingresa la descripción del elemento. Puedes usar el editor para dar formato al contenido."
              :error="errors.descripcion"
            />
            <p class="mt-1 text-xs text-muted-foreground">
              Usa la barra de herramientas para dar formato al contenido (negrita, cursiva, listas, enlaces, etc.)
            </p>
          </div>

          <!-- Configuraciones adicionales -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Es Premium -->
            <div>
              <label class="flex items-center gap-2 text-sm font-medium text-foreground">
                <input
                  v-model="form.is_premium"
                  type="checkbox"
                  class="rounded border-input text-primary focus:ring-ring"
                />
                <Lock v-if="form.is_premium" class="h-4 w-4 text-yellow-600" />
                <Unlock v-else class="h-4 w-4 text-blue-600" />
                Contenido Premium
              </label>
              <p class="mt-1 text-xs text-muted-foreground">
                El contenido premium requiere suscripción para acceder
              </p>
              <p v-if="errors.is_premium" class="mt-1 text-sm text-destructive">{{ errors.is_premium }}</p>
            </div>

            <!-- Orden -->
            <div>
              <label class="block text-sm font-medium text-foreground mb-2">
                Orden
              </label>
              <input
                v-model.number="form.orden"
                type="number"
                min="0"
                class="w-full px-3 py-2 border border-input rounded-md bg-background text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
                :class="{ 'border-destructive': errors.orden }"
                placeholder="0"
              />
              <p class="mt-1 text-xs text-muted-foreground">
                Orden de aparición en la lista
              </p>
              <p v-if="errors.orden" class="mt-1 text-sm text-destructive">{{ errors.orden }}</p>
            </div>
          </div>

          <!-- Botones -->
          <div class="flex justify-end gap-3 pt-4 border-t">
            <Button 
              type="button" 
              variant="outline" 
              @click="close"
              :disabled="processing"
            >
              Cancelar
            </Button>
            <Button 
              type="submit" 
              :disabled="processing"
              class="min-w-[120px]"
            >
              <Loader2 v-if="processing" class="w-4 h-4 mr-2 animate-spin" />
              {{ processing ? 'Actualizando...' : 'Actualizar Elemento' }}
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  </div>
</template>