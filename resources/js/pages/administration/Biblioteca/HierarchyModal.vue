<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue';
import { X, BookOpen, ChevronRight, Lock, Unlock, Plus, Minus, Folder, FolderPlus } from 'lucide-vue-next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import HierarchyItem from './HierarchyItem.vue';

// Props
interface Biblioteca {
  id: number;
  titulo: string;
  slug: string;
  descripcion: string;
  padre_id?: number;
  carpeta_id?: number;
  is_premium: boolean;
  orden: number;
  created_at: string;
  updated_at: string;
  deleted_at?: string;
  padre?: {
    id: number;
    titulo: string;
    slug: string;
  };
  carpeta?: {
    id: number;
    nombre: string;
    slug: string;
  };
  hijos_count: number;
  nivel: number;
  ruta_completa: string;
}

interface Carpeta {
  id: number;
  nombre: string;
  activa: boolean;
  padre_id?: number | null;
  subcarpetas?: Carpeta[];
  subcarpetas_recursivas?: Carpeta[];
  bibliotecas?: Biblioteca[];
  type: 'carpeta';
}

const props = defineProps<{
  biblioteca: Biblioteca[];
  carpetas?: Carpeta[];
  show: boolean;
}>();

// Emits
const emit = defineEmits<{
  close: [];
  refresh: [];
}>();

// Estados reactivos
const showingCreateForm = ref<number | null>(null);
const newFolderName = ref('');
const isCreating = ref(false);
const errors = ref<Record<string, string[]>>({});
const localCarpetas = ref<Carpeta[]>(props.carpetas || []);
const localBiblioteca = ref<Biblioteca[]>(props.biblioteca || []);

// Obtener errores de la página
const page = usePage();
const pageErrors = computed(() => page.props.errors || {});

// Computed para generar los datos de jerarquía
const hierarchyData = computed(() => {
  const data: any[] = [];
  
  // Agregar carpetas si existen
  if (localCarpetas.value && localCarpetas.value.length > 0) {
    localCarpetas.value.forEach(carpeta => {
      data.push({
        ...carpeta,
        type: 'carpeta',
        children: carpeta.subcarpetas || [],
        elementos: carpeta.bibliotecas || []
      });
    });
  }
  
  // Agregar elementos de biblioteca que no están en carpetas
  if (localBiblioteca.value && localBiblioteca.value.length > 0) {
    localBiblioteca.value.forEach(item => {
      if (!item.carpeta_id) {
        data.push({
          ...item,
          type: 'biblioteca'
        });
      }
    });
  }
  
  return data;
});

// Función para limpiar errores
const clearErrors = () => {
  errors.value = {};
};

// Función para mostrar formulario de nueva carpeta
const showCreateForm = (parentId: number | null = null) => {
  showingCreateForm.value = parentId;
  newFolderName.value = '';
  clearErrors();
};

// Función para cancelar creación
const cancelCreate = () => {
  showingCreateForm.value = null;
  newFolderName.value = '';
  clearErrors();
};

// Función para refrescar datos
const refreshData = async () => {
  try {
    const response = await axios.get('/api/carpetas/arbol');
    localCarpetas.value = response.data || [];
  } catch (error) {
    console.error('Error al refrescar datos:', error);
  }
};

// Función para crear carpeta con axios
const createFolder = async (parentId: number | null = null) => {
  if (!newFolderName.value.trim()) return;
  
  isCreating.value = true;
  clearErrors();
  
  try {
    const data: any = {
      nombre: newFolderName.value.trim(),
      activa: true,
      orden: 0
    };
    
    // Solo agregar padre_id si realmente hay un padre
    if (parentId !== null && parentId !== undefined) {
      data.padre_id = parentId;
    }
    
    const response = await axios.post('/carpetas', data);
    
    // Actualizar datos locales
    await refreshData();
    
    // Limpiar formulario
    cancelCreate();
    
  } catch (error: any) {
    console.error('Error creando carpeta:', error);
    if (error.response && error.response.data && error.response.data.errors) {
      errors.value = error.response.data.errors;
    } else {
      errors.value = { general: ['Ocurrió un error inesperado'] };
    }
  } finally {
    isCreating.value = false;
  }
};

// Función para eliminar carpeta con axios
const deleteFolder = async (carpeta: Carpeta) => {
  if (!confirm(`¿Estás seguro de que deseas eliminar la carpeta "${carpeta.nombre}"? Esto también eliminará todas sus subcarpetas y moverá los elementos de biblioteca a la carpeta padre.`)) {
    return;
  }

  clearErrors();
  
  try {
    // Usar ID como corresponde
    await axios.delete(`/carpetas/${carpeta.id}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    });
    
    // Recargar datos después de eliminar
    await refreshData();
    
    console.log('Carpeta eliminada exitosamente');
    
  } catch (error: any) {
    console.error('Error eliminando carpeta:', error);
    if (error.response && error.response.data && error.response.data.errors) {
      errors.value = error.response.data.errors;
    } else {
      errors.value = { general: ['Error al eliminar la carpeta: ' + (error.response?.data?.message || error.message)] };
    }
  }
};

// Función para manejar eventos del componente hijo
const handleCreateFolder = async (parentId: number | null, nombre: string) => {
  newFolderName.value = nombre;
  await createFolder(parentId);
};

const handleDeleteFolder = async (carpeta: Carpeta) => {
  await deleteFolder(carpeta);
};

// Cargar datos al montar el componente
onMounted(() => {
  if (props.show) {
    refreshData();
  }
});

// Observar cambios en la prop show para recargar datos
watch(() => props.show, (newValue) => {
  if (newValue) {
    refreshData();
  }
});
</script>

<template>
  <!-- Overlay de fondo -->
  <div 
    v-if="show" 
    class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
  >
    <!-- Card Modal -->
    <Card class="w-full max-w-5xl max-h-[95vh] overflow-hidden">
      <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-4 border-b">
        <CardTitle class="text-xl font-semibold text-foreground flex items-center gap-2">
          <BookOpen class="w-5 h-5 text-primary" />
          Jerarquía de Biblioteca y Carpetas
        </CardTitle>
        <Button 
          variant="ghost" 
          size="sm" 
          @click="$emit('close')"
          class="h-8 w-8 p-0"
        >
          <X class="h-4 w-4" />
        </Button>
      </CardHeader>
      
      <CardContent class="overflow-y-auto max-h-[calc(95vh-80px)] p-6">
        <div class="space-y-4">
          <!-- Información general -->
          <div class="bg-muted/30 rounded-lg p-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="font-semibold text-foreground mb-2">Vista de Jerarquía</h3>
                <p class="text-sm text-muted-foreground">
                  Gestiona la estructura jerárquica de carpetas y elementos de la biblioteca.
                </p>
              </div>
              <Button 
                @click="showCreateForm(null)"
                variant="outline"
                size="sm"
                class="flex items-center gap-2"
              >
                <FolderPlus class="w-4 h-4" />
                Nueva Carpeta Raíz
              </Button>
            </div>
          </div>

          <!-- Formulario de nueva carpeta raíz -->
          <div v-if="showingCreateForm === null" class="bg-muted/20 rounded-lg p-4 border-2 border-dashed border-primary/30">
            <div class="space-y-3">
              <div class="space-y-2">
                <div class="flex items-center gap-2">
                  <Input
                    v-model="newFolderName"
                    placeholder="Nombre de la nueva carpeta"
                    :class="[
                      'flex-1',
                      (errors.nombre || pageErrors.nombre) ? 'border-red-500 focus:border-red-500' : ''
                    ]"
                    @keyup.enter="createFolder(null)"
                    @keyup.escape="cancelCreate"
                  />
                  <Button 
                    @click="createFolder(null)"
                    :disabled="!newFolderName.trim() || isCreating"
                    size="sm"
                    class="flex items-center gap-1"
                  >
                    <Plus class="w-3 h-3" />
                    {{ isCreating ? 'Creando...' : 'Crear' }}
                  </Button>
                  <Button 
                    @click="cancelCreate"
                    variant="outline"
                    size="sm"
                  >
                    <X class="w-3 h-3" />
                  </Button>
                </div>
                <InputError :message="errors.nombre?.[0] || pageErrors.nombre?.[0]" />
                <InputError :message="errors.padre_id?.[0] || pageErrors.padre_id?.[0]" />
              </div>
            </div>
          </div>

          <!-- Mostrar jerarquía -->
          <div v-if="hierarchyData.length > 0" class="space-y-2">
            <HierarchyItem
              v-for="item in hierarchyData"
              :key="`${item.type}-${item.id}`"
              :item="item"
              :level="0"
              @create-folder="handleCreateFolder"
              @delete-folder="handleDeleteFolder"
            />
          </div>
          
          <!-- Mensaje cuando no hay datos -->
          <div v-else class="text-center py-8 text-muted-foreground">
            <Folder class="w-12 h-12 mx-auto mb-4 opacity-50" />
            <p>No hay carpetas o elementos para mostrar</p>
            <p class="text-sm">Crea una nueva carpeta para comenzar</p>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>