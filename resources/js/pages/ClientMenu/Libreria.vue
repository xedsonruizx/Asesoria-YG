<script setup lang="ts">
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import { Head } from '@inertiajs/vue3';
import TopBar from '@/components/MyComponents/TopBar.vue';
import { ChevronRight, ChevronDown, ChevronLeft, Folder, BookOpen, Lock } from 'lucide-vue-next';
import axios from 'axios';

interface Biblioteca {
  id: number;
  titulo: string;
  descripcion?: string;
  contenido?: string;
  is_premium: boolean;
  orden: number;
}

interface Carpeta {
  id: number;
  nombre: string;
  activa: boolean;
  subcarpetas_recursivas?: Carpeta[];
  bibliotecas?: Biblioteca[];
}

// Props
const props = defineProps<{
  carpetas?: Carpeta[];
  auth?: {
    user?: any;
    permissions?: string[];
  };
}>();

// Estado reactivo
const selectedBiblioteca = ref<Biblioteca | null>(null);
const expandedFolders = ref<Set<number>>(new Set());
const loading = ref(false);
const error = ref<string | null>(null);
const sidebarOpen = ref(true);
const showPremiumModal = ref(false);

// Computed
const isPremiumUser = computed(() => {
  return props.auth?.user?.ispremium || false;
});

const carpetasData = computed(() => {
  return props.carpetas || [];
});

// Funciones para manejo de URL
const updateUrlHash = (bibliotecaId: number | null) => {
  if (bibliotecaId) {
    window.history.replaceState(null, '', `#biblioteca-${bibliotecaId}`);
  } else {
    window.history.replaceState(null, '', window.location.pathname);
  }
};

const getBibliotecaIdFromUrl = (): number | null => {
  const hash = window.location.hash;
  const match = hash.match(/^#biblioteca-(\d+)$/);
  return match ? parseInt(match[1], 10) : null;
};

const expandParentFolders = (bibliotecaId: number, carpetas: Carpeta[]): void => {
  const findAndExpandParents = (carpetas: Carpeta[], targetId: number): boolean => {
    for (const carpeta of carpetas) {
      // Verificar si la biblioteca está en esta carpeta
      if (carpeta.bibliotecas?.some(b => b.id === targetId)) {
        expandedFolders.value.add(carpeta.id);
        return true;
      }
      
      // Buscar recursivamente en subcarpetas
      if (carpeta.subcarpetas_recursivas && findAndExpandParents(carpeta.subcarpetas_recursivas, targetId)) {
        expandedFolders.value.add(carpeta.id);
        return true;
      }
    }
    return false;
  };
  
  findAndExpandParents(carpetas, bibliotecaId);
};

// Funciones para persistencia
const saveStateToLocalStorage = () => {
  try {
    const state = {
      selectedBibliotecaId: selectedBiblioteca.value?.id || null,
      expandedFolders: Array.from(expandedFolders.value),
      sidebarOpen: sidebarOpen.value
    };
    localStorage.setItem('libreria-state', JSON.stringify(state));
  } catch (error) {
    console.warn('No se pudo guardar el estado en localStorage:', error);
  }
};

const loadStateFromLocalStorage = () => {
  try {
    // Primero verificar si hay un marcador en la URL
    const urlBibliotecaId = getBibliotecaIdFromUrl();
    
    if (urlBibliotecaId && carpetasData.value.length > 0) {
      // Si hay un marcador en la URL, usarlo como prioridad
      expandParentFolders(urlBibliotecaId, carpetasData.value);
      restoreSelectedBiblioteca(urlBibliotecaId);
      return;
    }
    
    // Si no hay marcador en URL, usar localStorage
    const savedState = localStorage.getItem('libreria-state');
    if (savedState) {
      const state = JSON.parse(savedState);
      
      // Restaurar carpetas expandidas
      if (state.expandedFolders && Array.isArray(state.expandedFolders)) {
        expandedFolders.value = new Set(state.expandedFolders);
      }
      
      // Restaurar estado del sidebar
      if (typeof state.sidebarOpen === 'boolean') {
        sidebarOpen.value = state.sidebarOpen;
      }
      
      // Restaurar biblioteca seleccionada si ya hay datos
      if (state.selectedBibliotecaId && carpetasData.value.length > 0) {
        restoreSelectedBiblioteca(state.selectedBibliotecaId);
      }
    }
  } catch (error) {
    console.warn('No se pudo cargar el estado desde localStorage:', error);
  }
};

const restoreSelectedBiblioteca = (bibliotecaId: number) => {
  const findBiblioteca = (carpetas: Carpeta[]): Biblioteca | null => {
    for (const carpeta of carpetas) {
      // Buscar en las bibliotecas de esta carpeta
      if (carpeta.bibliotecas) {
        const found = carpeta.bibliotecas.find(b => b.id === bibliotecaId);
        if (found) return found;
      }
      
      // Buscar recursivamente en subcarpetas
      if (carpeta.subcarpetas_recursivas) {
        const found = findBiblioteca(carpeta.subcarpetas_recursivas);
        if (found) return found;
      }
    }
    return null;
  };
  
  const biblioteca = findBiblioteca(carpetasData.value);
  if (biblioteca) {
    selectedBiblioteca.value = biblioteca;
    updateUrlHash(biblioteca.id);
  }
};

// Función para sanitizar contenido HTML
const sanitizeHtmlContent = (content: string): string => {
  if (!content) return '';
  
  // Crear un elemento temporal para manipular el HTML
  const tempDiv = document.createElement('div');
  tempDiv.innerHTML = content;
  
  // Encontrar todas las imágenes y corregir src undefined
  const images = tempDiv.querySelectorAll('img');
  images.forEach(img => {
    if (!img.src || img.src === 'undefined' || img.getAttribute('src') === 'undefined') {
      img.removeAttribute('src');
      img.style.display = 'none'; // Ocultar imágenes sin src válido
    }
  });
  
  return tempDiv.innerHTML;
};

// Computed para contenido sanitizado
const sanitizedContent = computed(() => {
  if (!selectedBiblioteca.value?.contenido) return '';
  return sanitizeHtmlContent(selectedBiblioteca.value.contenido);
});

// Funciones
const toggleFolder = (folderId: number) => {
  if (expandedFolders.value.has(folderId)) {
    expandedFolders.value.delete(folderId);
  } else {
    expandedFolders.value.add(folderId);
  }
  saveStateToLocalStorage();
};

const selectBiblioteca = (biblioteca: Biblioteca) => {
  // Verificar si el usuario puede acceder al contenido premium
  if (biblioteca.is_premium && !isPremiumUser.value) {
    // Mostrar modal de suscripción o mensaje de acceso restringido
    showPremiumModal.value = true;
    return;
  }
  selectedBiblioteca.value = biblioteca;
  updateUrlHash(biblioteca.id);
  saveStateToLocalStorage();
};

// Nueva función para cerrar el modal
const closePremiumModal = () => {
  showPremiumModal.value = false;
};

const isFolderExpanded = (folderId: number) => {
  return expandedFolders.value.has(folderId);
};

const hasContent = (carpeta: Carpeta): boolean => {
  const hasSubfolders = carpeta.subcarpetas_recursivas && carpeta.subcarpetas_recursivas.length > 0;
  const hasBibliotecas = carpeta.bibliotecas && carpeta.bibliotecas.length > 0;
  return hasSubfolders || hasBibliotecas;
};

const toggleSidebar = () => {
  sidebarOpen.value = !sidebarOpen.value;
  saveStateToLocalStorage();
};

// Función recursiva para renderizar carpetas
const renderCarpetas = (carpetas: Carpeta[], level: number = 0): any[] => {
  const result: any[] = [];
  
  carpetas.forEach(carpeta => {
    if (!carpeta.activa) return;
    
    result.push({
      type: 'carpeta',
      data: carpeta,
      level: level
    });
    
    if (isFolderExpanded(carpeta.id)) {
      // Agregar bibliotecas
      if (carpeta.bibliotecas) {
        carpeta.bibliotecas.forEach(biblioteca => {
          result.push({
            type: 'biblioteca',
            data: biblioteca,
            level: level + 1
          });
        });
      }
      
      // Agregar subcarpetas recursivamente
      if (carpeta.subcarpetas_recursivas) {
        result.push(...renderCarpetas(carpeta.subcarpetas_recursivas, level + 1));
      }
    }
  });
  
  return result;
};

const flattenedItems = computed(() => {
  return renderCarpetas(carpetasData.value);
});

// Lifecycle hooks
onMounted(() => {
  // Cargar estado guardado cuando el componente se monta
  loadStateFromLocalStorage();
  
  // Escuchar cambios en el hash de la URL
  const handleHashChange = () => {
    const bibliotecaId = getBibliotecaIdFromUrl();
    if (bibliotecaId && carpetasData.value.length > 0) {
      expandParentFolders(bibliotecaId, carpetasData.value);
      restoreSelectedBiblioteca(bibliotecaId);
    }
  };
  
  window.addEventListener('hashchange', handleHashChange);
});

// Watcher para guardar estado cuando cambian los datos
watch(carpetasData, (newData) => {
  if (newData.length > 0) {
    // Verificar primero si hay un marcador en la URL
    const urlBibliotecaId = getBibliotecaIdFromUrl();
    
    if (urlBibliotecaId) {
      // Si hay marcador en URL, expandir carpetas padre y seleccionar
      expandParentFolders(urlBibliotecaId, newData);
      restoreSelectedBiblioteca(urlBibliotecaId);
    } else {
      // Si no hay marcador en URL, usar localStorage
      const savedState = localStorage.getItem('libreria-state');
      if (savedState) {
        try {
          const state = JSON.parse(savedState);
          if (state.selectedBibliotecaId && !selectedBiblioteca.value) {
            restoreSelectedBiblioteca(state.selectedBibliotecaId);
          }
        } catch (error) {
          console.warn('Error al restaurar biblioteca seleccionada:', error);
        }
      }
    }
  }
}, { immediate: true });
</script>

<template>
  <div class="min-h-screen bg-[#FDFDFC] dark:bg-[#0a0a0a]">
    <Head title="Biblioteca" />
    
    <!-- TopBar -->
    <TopBar />
    
    <div class="flex">
      <!-- Sidebar -->
      <div 
        class="bg-[#FDFDFC] dark:bg-[#0a0a0a] shadow-lg h-screen sticky top-0 overflow-y-auto border-r border-gray-300 dark:border-gray-600 transition-all duration-300"
        :class="sidebarOpen ? 'w-80' : 'w-16'"
      >
        <!-- Toggle Button -->
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
          <button
            @click="toggleSidebar"
            class="w-full flex items-center justify-center p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          >
            <component 
              :is="sidebarOpen ? ChevronLeft : ChevronRight" 
              class="w-5 h-5 text-gray-600 dark:text-gray-400"
            />
          </button>
        </div>

        <!-- Header (solo visible cuando está expandido) -->
        <div v-if="sidebarOpen" class="p-6 border-b border-gray-200 dark:border-gray-700">
          <h2 class="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <BookOpen class="w-6 h-6 text-blue-600" />
            Biblioteca
          </h2>
          <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            Explora nuestros recursos organizados
          </p>
        </div>

        <!-- Icono cuando está colapsado -->
        <div v-else class="p-4 flex justify-center">
          <BookOpen class="w-6 h-6 text-blue-600" />
        </div>

        <!-- Hierarchy Navigation (solo visible cuando está expandido) -->
        <nav v-if="sidebarOpen" class="p-4">
          <div class="space-y-2">
            <!-- Renderizado plano de elementos -->
            <template v-for="item in flattenedItems" :key="`${item.type}-${item.data.id}`">
              <!-- Carpeta -->
              <div 
                v-if="item.type === 'carpeta'"
                :style="{ marginLeft: `${item.level * 16}px` }"
              >
                <div 
                  @click="toggleFolder(item.data.id)"
                  class="flex items-center gap-2 p-3 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer transition-colors"
                  :class="{ 'bg-gray-100 dark:bg-gray-700': isFolderExpanded(item.data.id) }"
                >
                  <component 
                    :is="isFolderExpanded(item.data.id) ? ChevronDown : ChevronRight" 
                    class="w-4 h-4 text-gray-500 dark:text-gray-400"
                    v-if="hasContent(item.data)"
                  />
                  <div class="w-4" v-else></div>
                  <Folder :class="item.level === 0 ? 'w-5 h-5 text-blue-600' : 'w-4 h-4 text-blue-500'" />
                  <span 
                    :class="item.level === 0 ? 'font-medium text-gray-900 dark:text-white' : 'text-sm font-medium text-gray-800 dark:text-gray-200'"
                  >
                    {{ item.data.nombre }}
                  </span>
                </div>
              </div>

              <!-- Biblioteca -->
              <div 
                v-else-if="item.type === 'biblioteca'"
                :style="{ marginLeft: `${(item.level * 16) + 24}px` }"
              >
                <div 
                  @click="selectBiblioteca(item.data)"
                  class="flex items-center gap-2 p-2 rounded-md hover:bg-blue-50 dark:hover:bg-blue-900/20 cursor-pointer transition-colors group"
                  :class="{ 
                    'bg-blue-50 dark:bg-blue-900/20 border-l-2 border-blue-500': selectedBiblioteca?.id === item.data.id,
                    'opacity-60 cursor-not-allowed': item.data.is_premium && !isPremiumUser,
                    'hover:opacity-80': item.data.is_premium && !isPremiumUser
                  }"
                >
                  <BookOpen :class="item.level <= 1 ? 'w-4 h-4' : 'w-3 h-3'" class="text-gray-500 dark:text-gray-400 group-hover:text-blue-600" />
                  <span 
                    :class="item.level <= 1 ? 'text-sm' : 'text-xs'"
                    class="text-gray-700 dark:text-gray-300 group-hover:text-blue-700 dark:group-hover:text-blue-300 flex-1"
                  >
                    {{ item.data.titulo }}
                  </span>
                  <Lock v-if="item.data.is_premium && !isPremiumUser" class="w-3 h-3 text-amber-500" />
                </div>
              </div>
            </template>
          </div>
        </nav>
      </div>

      <!-- Main Content Area -->
      <div class="flex-1 p-4 lg:p-8 min-h-screen">
        <!-- Estado inicial -->
        <div v-if="!selectedBiblioteca" class="flex items-center justify-center h-96">
          <div class="text-center">
            <BookOpen class="w-16 h-16 text-gray-400 mx-auto mb-4" />
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
              Selecciona un elemento de la biblioteca
            </h3>
            <p class="text-gray-600 dark:text-gray-400">
              Explora las carpetas en el sidebar y selecciona un elemento para ver su contenido
            </p>
          </div>
        </div>

        <!-- Contenido de la biblioteca seleccionada -->
        <div v-else class="max-w-4xl">
          <!-- Header del contenido -->
          <div class="mb-8">
            <div class="flex items-center gap-3 mb-4">
              <BookOpen class="w-8 h-8 text-blue-600" />
              <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                {{ selectedBiblioteca.titulo }}
              </h1>
              <div v-if="selectedBiblioteca.is_premium" class="flex items-center gap-1 px-2 py-1 bg-amber-100 dark:bg-amber-900/20 rounded-full">
                <Lock class="w-3 h-3 text-amber-600" />
                <span class="text-xs font-medium text-amber-700 dark:text-amber-400">Premium</span>
              </div>
            </div>
            
            <div v-if="selectedBiblioteca.descripcion" class="text-lg text-gray-600 dark:text-gray-400 leading-relaxed prose prose-lg dark:prose-invert max-w-none mb-6" v-html="selectedBiblioteca.descripcion"></div>
          </div>

          <!-- Contenido principal -->
          <div class="prose prose-lg dark:prose-invert max-w-none">
            <div v-if="selectedBiblioteca.descripcion" v-html="sanitizedContent"></div>
            <div v-else class="bg-gray-50 dark:bg-gray-800 rounded-lg p-8 text-center">
              <p class="text-gray-600 dark:text-gray-400">
                Este elemento no tiene contenido disponible.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Premium -->
    <div v-if="showPremiumModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md mx-4">
        <div class="flex items-center gap-3 mb-4">
          <Lock class="w-6 h-6 text-amber-500" />
          <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Contenido Premium
          </h3>
        </div>
        <p class="text-gray-600 dark:text-gray-400 mb-6">
          Este contenido está disponible solo para usuarios premium. Actualiza tu suscripción para acceder a todos nuestros recursos.
        </p>
        <div class="flex gap-3 justify-end">
          <button
            @click="closePremiumModal"
            class="px-4 py-2 text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors"
          >
            Cerrar
          </button>
          <button
            @click="closePremiumModal"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            Ver Planes
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@reference "../../../css/app.css";

/* Estilos adicionales para el contenido HTML */
.prose :deep(h1) {
  @apply text-2xl font-bold text-gray-900 dark:text-white mb-4;
}

.prose :deep(h2) {
  @apply text-xl font-semibold text-gray-900 dark:text-white mb-3;
}

.prose :deep(h3) {
  @apply text-lg font-medium text-gray-900 dark:text-white mb-2;
}

.prose :deep(p) {
  @apply text-gray-700 dark:text-gray-300 mb-4 leading-relaxed;
}

.prose :deep(ul) {
  @apply list-disc list-inside mb-4 text-gray-700 dark:text-gray-300;
}

.prose :deep(ol) {
  @apply list-decimal list-inside mb-4 text-gray-700 dark:text-gray-300;
}

.prose :deep(li) {
  @apply mb-1;
}

.prose :deep(a) {
  @apply text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 underline;
}

.prose :deep(blockquote) {
  @apply border-l-4 border-blue-500 pl-4 italic text-gray-600 dark:text-gray-400 mb-4;
}

.prose :deep(code) {
  @apply bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded text-sm font-mono;
}

.prose :deep(pre) {
  @apply bg-gray-100 dark:bg-gray-800 p-4 rounded-lg overflow-x-auto mb-4;
}
</style>