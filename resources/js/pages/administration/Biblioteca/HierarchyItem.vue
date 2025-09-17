<script setup lang="ts">
import { ref } from 'vue';
import { ChevronRight, Lock, Unlock, Plus, Minus, Folder, FolderPlus, BookOpen, Trash2, X } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';

interface Item {
  id: number;
  nombre?: string;
  titulo?: string;
  type: 'carpeta' | 'biblioteca';
  activa?: boolean;
  is_premium?: boolean;
  children?: Item[];
  elementos?: Item[];
  subcarpetas?: Item[];
  subcarpetas_recursivas?: Item[];
  bibliotecas?: Item[];
}

const props = defineProps<{
  item: Item;
  level: number;
}>();

const emit = defineEmits<{
  createFolder: [parentId: number | null, nombre: string];
  deleteFolder: [item: Item];
}>();

// Estados locales
const isExpanded = ref(false);
const showingCreateForm = ref(false);
const newFolderName = ref('');
const errors = ref<Record<string, string[]>>({});

// Funciones
const toggleExpanded = () => {
  isExpanded.value = !isExpanded.value;
};

const showCreateForm = () => {
  showingCreateForm.value = true;
  newFolderName.value = '';
  errors.value = {};
};

const cancelCreate = () => {
  showingCreateForm.value = false;
  newFolderName.value = '';
  errors.value = {};
};

const createFolder = () => {
  if (!newFolderName.value.trim()) return;
  
  emit('createFolder', props.item.id, newFolderName.value.trim());
  cancelCreate();
};

const deleteItem = () => {
  emit('deleteFolder', props.item);
};

// Computed
const hasChildren = () => {
  if (props.item.type === 'carpeta') {
    const subcarpetas = props.item.subcarpetas_recursivas || props.item.subcarpetas || props.item.children || [];
    const elementos = props.item.bibliotecas || props.item.elementos || [];
    return subcarpetas.length > 0 || elementos.length > 0;
  }
  return false;
};

const getChildren = () => {
  if (props.item.type === 'carpeta') {
    const subcarpetas = props.item.subcarpetas_recursivas || props.item.subcarpetas || props.item.children || [];
    const elementos = props.item.bibliotecas || props.item.elementos || [];
    return [...subcarpetas, ...elementos];
  }
  return [];
};

const getItemName = () => {
  return props.item.nombre || props.item.titulo || 'Sin nombre';
};

const getItemIcon = () => {
  if (props.item.type === 'carpeta') {
    return Folder;
  } else {
    return BookOpen;
  }
};

const getStatusIcon = () => {
  if (props.item.type === 'carpeta') {
    return props.item.activa ? Unlock : Lock;
  } else {
    return props.item.is_premium ? Lock : Unlock;
  }
};

const getStatusColor = () => {
  if (props.item.type === 'carpeta') {
    return props.item.activa ? 'text-green-600' : 'text-red-600';
  } else {
    return props.item.is_premium ? 'text-amber-600' : 'text-green-600';
  }
};
</script>

<template>
  <div class="space-y-1">
    <!-- Item principal -->
    <div 
      class="flex items-center gap-2 p-2 rounded-lg hover:bg-muted/50 transition-colors cursor-pointer"
      :style="{ paddingLeft: `${level * 20 + 8}px` }"
      @click="hasChildren() ? toggleExpanded() : null"
    >
      <!-- Botón de expansión -->
      <Button
        v-if="hasChildren()"
        variant="ghost"
        size="sm"
        class="h-6 w-6 p-0 pointer-events-none"
      >
        <ChevronRight 
          class="h-3 w-3 transition-transform"
          :class="{ 'rotate-90': isExpanded }"
        />
      </Button>
      <div v-else class="w-6"></div>

      <!-- Icono del item -->
      <component 
        :is="getItemIcon()" 
        class="h-4 w-4 text-muted-foreground flex-shrink-0"
      />

      <!-- Nombre del item -->
      <span class="flex-1 text-sm font-medium text-foreground">
        {{ getItemName() }}
      </span>

      <!-- Estado -->
      <component 
        :is="getStatusIcon()" 
        class="h-3 w-3 flex-shrink-0"
        :class="getStatusColor()"
      />

      <!-- Acciones -->
      <div class="flex items-center gap-1" @click.stop>
        <!-- Botón para crear subcarpeta (solo en carpetas) -->
        <Button
          v-if="item.type === 'carpeta'"
          variant="ghost"
          size="sm"
          class="h-6 w-6 p-0"
          @click="showCreateForm"
          title="Crear subcarpeta"
        >
          <FolderPlus class="h-3 w-3" />
        </Button>

        <!-- Botón para eliminar -->
        <Button
          variant="ghost"
          size="sm"
          class="h-6 w-6 p-0 text-red-600 hover:text-red-700"
          @click="deleteItem"
          title="Eliminar"
        >
          <Trash2 class="h-3 w-3" />
        </Button>
      </div>
    </div>

    <!-- Formulario de nueva subcarpeta -->
    <div 
      v-if="showingCreateForm" 
      class="bg-muted/20 rounded-lg p-3 border-2 border-dashed border-primary/30"
      :style="{ marginLeft: `${(level + 1) * 20 + 8}px` }"
    >
      <div class="space-y-2">
        <div class="flex items-center gap-2">
          <Input
            v-model="newFolderName"
            placeholder="Nombre de la subcarpeta"
            class="flex-1 h-8"
            @keyup.enter="createFolder"
            @keyup.escape="cancelCreate"
          />
          <Button 
            @click="createFolder"
            :disabled="!newFolderName.trim()"
            size="sm"
            class="h-8 px-2"
          >
            <Plus class="w-3 h-3" />
          </Button>
          <Button 
            @click="cancelCreate"
            variant="outline"
            size="sm"
            class="h-8 px-2"
          >
            <X class="w-3 h-3" />
          </Button>
        </div>
        <InputError :message="errors.nombre?.[0]" />
      </div>
    </div>

    <!-- Elementos hijos -->
    <div v-if="isExpanded && hasChildren()">
      <HierarchyItem
        v-for="child in getChildren()"
        :key="`${child.type}-${child.id}`"
        :item="child"
        :level="level + 1"
        @create-folder="$emit('createFolder', $event)"
        @delete-folder="$emit('deleteFolder', $event)"
      />
    </div>
  </div>
</template>