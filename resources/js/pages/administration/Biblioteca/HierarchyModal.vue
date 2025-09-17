<script setup lang="ts">
import { ref, computed } from 'vue';
import { X, BookOpen, ChevronRight, Lock, Unlock } from 'lucide-vue-next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

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

const props = defineProps<{
  biblioteca: Biblioteca[];
  show: boolean;
}>();

// Emits
const emit = defineEmits<{
  close: [];
}>();

// Función para cerrar el modal
const close = () => {
  emit('close');
};

// Organizar elementos en jerarquía
const hierarchyData = computed(() => {
  const elements = props.biblioteca.filter(b => !b.deleted_at);
  
  const buildHierarchy = (parentId: number | null = null, level: number = 0): any[] => {
    return elements
      .filter(b => b.padre_id === parentId)
      .sort((a, b) => a.orden - b.orden)
      .map(element => ({
        ...element,
        level,
        children: buildHierarchy(element.id, level + 1)
      }));
  };
  
  return buildHierarchy();
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
          Jerarquía de Biblioteca
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
        <div class="space-y-4">
          <!-- Información general -->
          <div class="bg-muted/30 rounded-lg p-4">
            <h3 class="font-semibold text-foreground mb-2">Vista de Jerarquía</h3>
            <p class="text-sm text-muted-foreground">
              Esta vista muestra la estructura jerárquica completa de todos los elementos de la biblioteca.
            </p>
          </div>

          <!-- Jerarquía de elementos -->
          <div class="space-y-2">
            <template v-for="item in hierarchyData" :key="item.id">
              <HierarchyItem :item="item" :level="0" />
            </template>
            
            <!-- Estado vacío -->
            <div v-if="hierarchyData.length === 0" class="text-center py-8">
              <BookOpen class="w-12 h-12 text-muted-foreground mx-auto mb-4" />
              <p class="text-muted-foreground">No hay elementos en la biblioteca</p>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>

<script lang="ts">
import { defineComponent } from 'vue';

// Componente recursivo para mostrar elementos de jerarquía
const HierarchyItem = defineComponent({
  name: 'HierarchyItem',
  props: {
    item: {
      type: Object,
      required: true
    },
    level: {
      type: Number,
      default: 0
    }
  },
  components: {
    BookOpen,
    ChevronRight,
    Lock,
    Unlock
  },
  template: `
    <div 
      :class="[
        'border rounded-lg p-3 bg-card',
        level > 0 ? 'ml-6 border-l-4 border-l-primary/30' : ''
      ]"
    >
      <!-- Información del elemento -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <!-- Indicador de nivel -->
          <div class="flex items-center gap-1">
            <template v-for="i in level" :key="i">
              <ChevronRight class="w-3 h-3 text-muted-foreground" />
            </template>
            <BookOpen class="w-4 h-4 text-primary" />
          </div>
          
          <!-- Título y información -->
          <div>
            <h4 class="font-medium text-foreground">{{ item.titulo }}</h4>
            <div class="flex items-center gap-2 mt-1">
              <span class="text-xs text-muted-foreground">/{{ item.slug }}</span>
              <span 
                :class="[
                  'px-2 py-0.5 rounded-full text-xs font-medium',
                  item.is_premium 
                    ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300'
                    : 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300'
                ]"
              >
                <Lock v-if="item.is_premium" class="w-3 h-3 inline mr-1" />
                <Unlock v-else class="w-3 h-3 inline mr-1" />
                {{ item.is_premium ? 'Premium' : 'Gratuito' }}
              </span>
            </div>
          </div>
        </div>
        
        <!-- Información adicional -->
        <div class="text-right">
          <div class="text-sm text-muted-foreground">Orden: {{ item.orden }}</div>
          <div class="text-xs text-muted-foreground">
            {{ item.children?.length || 0 }} hijo{{ (item.children?.length || 0) !== 1 ? 's' : '' }}
          </div>
        </div>
      </div>
      
      <!-- Descripción si existe -->
      <div v-if="item.descripcion" class="mt-2 text-sm text-muted-foreground">
        <div v-html="item.descripcion.substring(0, 150) + (item.descripcion.length > 150 ? '...' : '')"></div>
      </div>
      
      <!-- Elementos hijos -->
      <div v-if="item.children && item.children.length > 0" class="mt-3 space-y-2">
        <template v-for="child in item.children" :key="child.id">
          <HierarchyItem :item="child" :level="level + 1" />
        </template>
      </div>
    </div>
  `
});

export { HierarchyItem };
</script>