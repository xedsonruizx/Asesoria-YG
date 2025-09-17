<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { X, BookOpen, Loader2, Lock, Unlock, Folder } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import RichTextEditor from '@/components/ui/rich-text-editor/RichTextEditor.vue';
import axios from 'axios';

// Props
const props = defineProps<{
  show: boolean;
}>();

// Emits
const emit = defineEmits<{
  close: [];
  created: [];
}>();

const processing = ref(false);

// Función para cerrar el modal
const close = () => {
  emit('close');
};

// Datos del formulario
const form = ref({
  titulo: '',
  slug: '',
  descripcion: '',
  padre_id: null as number | null,
  carpeta_id: null as number | null,
  is_premium: false,
  orden: 0
});

const errors = ref<Record<string, string>>({});

// Elementos padre disponibles (se cargarán del backend)
const elementosPadre = ref<Array<{
  id: number;
  titulo: string;
  nivel: number;
}>>([]);

// Carpetas disponibles (se cargarán del backend)
const carpetas = ref<Array<{
  id: number;
  nombre: string;
  nivel: number;
  ruta_completa: string;
}>>([]);

// Función para generar slug automáticamente
const generateSlug = () => {
  if (form.value.titulo && !form.value.slug) {
    form.value.slug = form.value.titulo
      .toLowerCase()
      .replace(/[áàäâ]/g, 'a')
      .replace(/[éèëê]/g, 'e')
      .replace(/[íìïî]/g, 'i')
      .replace(/[óòöô]/g, 'o')
      .replace(/[úùüû]/g, 'u')
      .replace(/[ñ]/g, 'n')
      .replace(/[^a-z0-9]/g, '-')
      .replace(/-+/g, '-')
      .replace(/^-|-$/g, '');
  }
};

// Función para crear el elemento
const createBiblioteca = () => {
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
  
  router.post('/admin/biblioteca', {
    ...form.value,
    ...filters
  }, {
    onSuccess: () => {
      emit('created');
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

// Cargar elementos padre y carpetas al montar el componente
const loadData = async () => {
  try {
    const response = await axios.get('/admin/biblioteca/create', {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
    elementosPadre.value = response.data.elementosPadre || [];
    carpetas.value = response.data.carpetas || [];
  } catch (error) {
    console.error('Error cargando datos:', error);
  }
};

// Cargar datos al montar
onMounted(() => {
  loadData();
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
          Crear Elemento de Biblioteca
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
        <form @submit.prevent="createBiblioteca" class="space-y-6">
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
          <div hidden>
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
              Se genera automáticamente desde el título. Debe ser único.
            </p>
            <p v-if="errors.slug" class="mt-1 text-sm text-destructive">{{ errors.slug }}</p>
          </div>

          <!-- Carpeta Padre -->
          <div>
            <label class="block text-sm font-medium text-foreground mb-2 flex items-center gap-2">
              <Folder class="w-4 h-4" />
              Carpeta Padre
            </label>
            <select
              v-model="form.carpeta_id"
              class="w-full px-3 py-2 border border-input rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
              :class="{ 'border-destructive': errors.carpeta_id }"
            >
              <option :value="null">Sin carpeta padre</option>
              <option v-for="carpeta in carpetas" :key="carpeta.id" :value="carpeta.id">
                {{ '—'.repeat(carpeta.nivel) }}{{ carpeta.nivel > 0 ? ' ' : '' }}{{ carpeta.nombre }}
              </option>
            </select>
            <p class="mt-1 text-xs text-muted-foreground">
              Selecciona la carpeta donde se organizará este elemento
            </p>
            <p v-if="errors.carpeta_id" class="mt-1 text-sm text-destructive">{{ errors.carpeta_id }}</p>
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
              {{ processing ? 'Creando...' : 'Crear Elemento' }}
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  </div>
</template>