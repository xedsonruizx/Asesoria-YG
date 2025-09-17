<script setup lang="ts">
import { computed } from 'vue';
import { X, BookOpen, Calendar, Users, Lock, Unlock, FileText, Tag, Eye } from 'lucide-vue-next';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

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
  deleted_at?: string;
  padre?: {
    id: number;
    titulo: string;
    slug: string;
  };
  hijos_count: number;
  nivel: number;
  ruta_completa: string;
}

interface Props {
  biblioteca: Biblioteca | null;
  isOpen: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  close: []
}>();

const close = () => {
  emit('close');
};

// Funciones de utilidad
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getTypeInfo = computed(() => {
  if (!props.biblioteca) {
    return { 
      label: 'Cargando...', 
      color: 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-300',
      icon: Lock 
    };
  }
  
  return props.biblioteca.is_premium 
    ? { 
        label: 'Premium', 
        color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300',
        icon: Lock
      }
    : { 
        label: 'Gratuito', 
        color: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300',
        icon: Unlock
      };
});

const getDependencyInfo = computed(() => {
  if (!props.biblioteca) {
    return { 
      text: 'Cargando...', 
      hasParent: false, 
      hasChildren: false 
    };
  }
  
  return {
    text: props.biblioteca.padre ? `Depende de: ${props.biblioteca.padre.titulo}` : 'Elemento raíz',
    hasParent: !!props.biblioteca.padre,
    hasChildren: props.biblioteca.hijos_count > 0
  };
});
</script>

<template>
  <!-- Overlay de fondo -->
  <div 
    v-if="isOpen" 
    class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
  >
    <!-- Card Modal -->
    <Card class="w-full max-w-4xl max-h-[95vh] overflow-hidden">
      <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-4 border-b">
        <CardTitle class="text-xl font-semibold text-foreground flex items-center gap-2">
          <Eye class="w-5 h-5 text-primary" />
          Ver Elemento de Biblioteca
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

        <!-- Content when biblioteca exists -->
        <div v-else class="space-y-6">
          <!-- Header con título y metadatos -->
          <div class="bg-card rounded-lg p-6 shadow-sm border border-border">
            <div class="flex items-start justify-between mb-4">
              <h1 class="text-2xl font-bold text-foreground leading-tight">{{ biblioteca.titulo }}</h1>
              <div class="flex gap-2">
                <span :class="getTypeInfo.color + ' inline-flex items-center px-3 py-1 rounded-full text-sm font-medium'">
                  <component :is="getTypeInfo.icon" class="h-4 w-4 mr-1" />
                  {{ getTypeInfo.label }}
                </span>
              </div>
            </div>
            
            <!-- Metadatos -->
            <div class="flex flex-wrap gap-4 text-sm text-muted-foreground">
              <div class="flex items-center gap-2">
                <Tag class="h-4 w-4" />
                <span>{{ biblioteca.slug }}</span>
              </div>
              <div class="flex items-center gap-2">
                <Calendar class="h-4 w-4" />
                <span>Creado {{ formatDate(biblioteca.created_at) }}</span>
              </div>
              <div class="flex items-center gap-2">
                <Users class="h-4 w-4" />
                <span>{{ biblioteca.hijos_count }} elemento(s) hijo(s)</span>
              </div>
            </div>
          </div>

          <!-- Contenido principal -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Descripción (contenido principal) -->
            <div class="lg:col-span-2">
              <div class="bg-card rounded-lg p-6 shadow-sm border border-border">
                <div class="flex items-center gap-2 mb-4">
                  <FileText class="h-5 w-5 text-muted-foreground" />
                  <h2 class="text-xl font-semibold text-foreground">Descripción</h2>
                </div>
                
                <!-- Contenido HTML enriquecido -->
                <div 
                  class="prose prose-sm max-w-none text-foreground"
                  v-html="biblioteca.descripcion"
                ></div>
              </div>
            </div>

            <!-- Sidebar con información adicional -->
            <div class="space-y-6">
              <!-- Información básica -->
              <div class="bg-card rounded-lg p-6 shadow-sm border border-border">
                <h3 class="text-lg font-semibold text-foreground mb-4">Información</h3>
                <div class="space-y-3">
                  <div>
                    <label class="text-sm font-medium text-muted-foreground">ID</label>
                    <p class="text-foreground">#{{ biblioteca.id }}</p>
                  </div>
                  <div>
                    <label class="text-sm font-medium text-muted-foreground">Slug</label>
                    <p class="text-foreground font-mono text-sm">{{ biblioteca.slug }}</p>
                  </div>
                  <div>
                    <label class="text-sm font-medium text-muted-foreground">Tipo</label>
                    <p class="text-foreground">{{ getTypeInfo.label }}</p>
                  </div>
                  <div>
                    <label class="text-sm font-medium text-muted-foreground">Orden</label>
                    <p class="text-foreground">{{ biblioteca.orden }}</p>
                  </div>
                  <div>
                    <label class="text-sm font-medium text-muted-foreground">Nivel</label>
                    <p class="text-foreground">{{ biblioteca.nivel }}</p>
                  </div>
                </div>
              </div>

              <!-- Jerarquía -->
              <div class="bg-card rounded-lg p-6 shadow-sm border border-border">
                <h3 class="text-lg font-semibold text-foreground mb-4">Jerarquía</h3>
                <div class="space-y-3">
                  <div>
                    <label class="text-sm font-medium text-muted-foreground">Ruta completa</label>
                    <p class="text-foreground text-sm">{{ biblioteca.ruta_completa }}</p>
                  </div>
                  <div v-if="biblioteca.padre">
                    <label class="text-sm font-medium text-muted-foreground">Elemento padre</label>
                    <p class="text-foreground">{{ biblioteca.padre.titulo }}</p>
                    <p class="text-muted-foreground text-sm">{{ biblioteca.padre.slug }}</p>
                  </div>
                  <div v-else>
                    <label class="text-sm font-medium text-muted-foreground">Elemento padre</label>
                    <p class="text-foreground">Elemento raíz</p>
                  </div>
                  <div>
                    <label class="text-sm font-medium text-muted-foreground">Elementos hijos</label>
                    <p class="text-foreground">{{ biblioteca.hijos_count }} elemento(s)</p>
                  </div>
                </div>
              </div>

              <!-- Fechas -->
              <div class="bg-card rounded-lg p-6 shadow-sm border border-border">
                <h3 class="text-lg font-semibold text-foreground mb-4">Fechas</h3>
                <div class="space-y-3">
                  <div>
                    <label class="text-sm font-medium text-muted-foreground">Creado</label>
                    <p class="text-foreground text-sm">{{ formatDate(biblioteca.created_at) }}</p>
                  </div>
                  <div>
                    <label class="text-sm font-medium text-muted-foreground">Actualizado</label>
                    <p class="text-foreground text-sm">{{ formatDate(biblioteca.updated_at) }}</p>
                  </div>
                  <div v-if="biblioteca.deleted_at">
                    <label class="text-sm font-medium text-muted-foreground">Eliminado</label>
                    <p class="text-foreground text-sm">{{ formatDate(biblioteca.deleted_at) }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>

<style scoped>
/* Estilos para el contenido HTML enriquecido */
:deep(.prose) {
  color: inherit;
}

:deep(.prose h1),
:deep(.prose h2),
:deep(.prose h3),
:deep(.prose h4),
:deep(.prose h5),
:deep(.prose h6) {
  color: inherit;
  margin-top: 1.5rem;
  margin-bottom: 0.75rem;
}

:deep(.prose h1) {
  font-size: 1.875rem;
  font-weight: 700;
}

:deep(.prose h2) {
  font-size: 1.5rem;
  font-weight: 600;
}

:deep(.prose h3) {
  font-size: 1.25rem;
  font-weight: 600;
}

:deep(.prose p) {
  margin-bottom: 1rem;
  line-height: 1.6;
}

:deep(.prose ul),
:deep(.prose ol) {
  margin: 1rem 0;
  padding-left: 1.5rem;
}

:deep(.prose li) {
  margin-bottom: 0.5rem;
}

:deep(.prose strong) {
  font-weight: 600;
}

:deep(.prose em) {
  font-style: italic;
}

:deep(.prose a) {
  color: rgb(59 130 246);
  text-decoration: underline;
}

:deep(.prose a:hover) {
  color: rgb(37 99 235);
}

:deep(.prose blockquote) {
  border-left: 4px solid rgb(209 213 219);
  padding-left: 1rem;
  margin: 1rem 0;
  font-style: italic;
  color: rgb(107 114 128);
}

:deep(.prose code) {
  background-color: rgb(243 244 246);
  padding: 0.125rem 0.25rem;
  border-radius: 0.25rem;
  font-size: 0.875rem;
  font-family: ui-monospace, SFMono-Regular, "SF Mono", Consolas, "Liberation Mono", Menlo, monospace;
}

:deep(.prose pre) {
  background-color: rgb(243 244 246);
  padding: 1rem;
  border-radius: 0.5rem;
  overflow-x: auto;
  margin: 1rem 0;
}

:deep(.prose pre code) {
  background-color: transparent;
  padding: 0;
}

/* Estilos para modo oscuro */
:root.dark :deep(.prose code) {
  background-color: rgb(55 65 81);
  color: rgb(229 231 235);
}

:root.dark :deep(.prose pre) {
  background-color: rgb(55 65 81);
}

:root.dark :deep(.prose blockquote) {
  border-left-color: rgb(75 85 99);
  color: rgb(156 163 175);
}
</style>