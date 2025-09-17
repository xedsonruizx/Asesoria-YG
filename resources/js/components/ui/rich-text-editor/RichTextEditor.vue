<script setup lang="ts">
import { ref, onMounted, watch, nextTick } from 'vue';
import { Bold, Italic, Underline, List, ListOrdered, Link, Type, AlignLeft, AlignCenter, AlignRight, Code } from 'lucide-vue-next';

interface Props {
  modelValue: string;
  placeholder?: string;
  disabled?: boolean;
  error?: string;
}

const props = withDefaults(defineProps<Props>(), {
  placeholder: 'Escribe aquí...',
  disabled: false,
  error: ''
});

const emit = defineEmits<{
  'update:modelValue': [value: string];
}>();

const editorRef = ref<HTMLDivElement>();
const codeViewRef = ref<HTMLTextAreaElement>();
const isActive = ref(false);
const isCodeView = ref(false);
const isUpdatingFromProps = ref(false);
const currentContent = ref('');

// Comandos de formato
const execCommand = (command: string, value?: string) => {
  document.execCommand(command, false, value);
  editorRef.value?.focus();
  updateContent();
};

// Actualizar contenido
const updateContent = () => {
  if (isUpdatingFromProps.value) return;
  
  let newContent = '';
  if (isCodeView.value && codeViewRef.value) {
    newContent = codeViewRef.value.value;
  } else if (editorRef.value) {
    newContent = editorRef.value.innerHTML;
  }
  
  currentContent.value = newContent;
  emit('update:modelValue', newContent);
};

// Verificar si un comando está activo
const isCommandActive = (command: string): boolean => {
  return document.queryCommandState(command);
};

// Insertar enlace
const insertLink = () => {
  const url = prompt('Ingresa la URL:');
  if (url) {
    execCommand('createLink', url);
  }
};

// Alternar vista de código
const toggleCodeView = () => {
  // Guardar el contenido actual antes de cambiar de vista
  if (isCodeView.value) {
    // Estamos en vista de código, guardamos el contenido del textarea
    if (codeViewRef.value) {
      currentContent.value = codeViewRef.value.value;
    }
  } else {
    // Estamos en vista visual, guardamos el contenido del editor
    if (editorRef.value) {
      currentContent.value = editorRef.value.innerHTML;
    }
  }
  
  // Cambiar la vista
  isCodeView.value = !isCodeView.value;
  
  // Esperar al siguiente tick para que el DOM se actualice y luego aplicar el contenido
  nextTick(() => {
    if (isCodeView.value) {
      // Cambió a vista de código
      if (codeViewRef.value) {
        codeViewRef.value.value = currentContent.value;
      }
    } else {
      // Cambió a vista visual
      if (editorRef.value) {
        editorRef.value.innerHTML = currentContent.value;
      }
    }
    updateContent();
  });
};

// Manejar entrada de texto
const handleInput = () => {
  updateContent();
};

// Manejar entrada en vista de código
const handleCodeInput = () => {
  updateContent();
};

// Manejar pegado
const handlePaste = (event: ClipboardEvent) => {
  event.preventDefault();
  const text = event.clipboardData?.getData('text/plain') || '';
  document.execCommand('insertText', false, text);
  updateContent();
};

// Inicializar contenido
onMounted(() => {
  if (props.modelValue) {
    currentContent.value = props.modelValue;
    if (editorRef.value) {
      editorRef.value.innerHTML = props.modelValue;
    }
    // No inicializamos codeViewRef aquí porque no está renderizado inicialmente
  }
});

// Observar cambios en modelValue (solo cuando viene de fuera, no de nuestras actualizaciones)
watch(() => props.modelValue, (newValue) => {
  // Evitar bucles infinitos cuando nosotros mismos actualizamos el valor
  if (isUpdatingFromProps.value) return;
  
  isUpdatingFromProps.value = true;
  currentContent.value = newValue;
  
  nextTick(() => {
    if (isCodeView.value) {
      if (codeViewRef.value && codeViewRef.value.value !== newValue) {
        codeViewRef.value.value = newValue;
      }
    } else {
      if (editorRef.value && editorRef.value.innerHTML !== newValue) {
        editorRef.value.innerHTML = newValue;
      }
    }
    
    isUpdatingFromProps.value = false;
  });
});
</script>

<template>
  <div class="rich-text-editor border border-input rounded-md bg-background" :class="{ 'border-destructive': error }">
    <!-- Barra de herramientas -->
    <div class="flex flex-wrap items-center gap-1 p-2 border-b border-input bg-muted/50">
      <!-- Vista de código -->
      <button
        type="button"
        @click="toggleCodeView"
        :class="[
          'p-2 rounded hover:bg-accent hover:text-accent-foreground transition-colors',
          isCodeView ? 'bg-accent text-accent-foreground' : 'text-muted-foreground'
        ]"
        title="Vista de código"
      >
        <Code class="h-4 w-4" />
      </button>

      <div class="w-px h-6 bg-border mx-1"></div>

      <!-- Formato de texto (solo en vista visual) -->
      <div v-if="!isCodeView" class="flex items-center gap-1">
        <button
          type="button"
          @click="execCommand('bold')"
          :class="[
            'p-2 rounded hover:bg-accent hover:text-accent-foreground transition-colors',
            isCommandActive('bold') ? 'bg-accent text-accent-foreground' : 'text-muted-foreground'
          ]"
          title="Negrita"
        >
          <Bold class="h-4 w-4" />
        </button>
        <button
          type="button"
          @click="execCommand('italic')"
          :class="[
            'p-2 rounded hover:bg-accent hover:text-accent-foreground transition-colors',
            isCommandActive('italic') ? 'bg-accent text-accent-foreground' : 'text-muted-foreground'
          ]"
          title="Cursiva"
        >
          <Italic class="h-4 w-4" />
        </button>
        <button
          type="button"
          @click="execCommand('underline')"
          :class="[
            'p-2 rounded hover:bg-accent hover:text-accent-foreground transition-colors',
            isCommandActive('underline') ? 'bg-accent text-accent-foreground' : 'text-muted-foreground'
          ]"
          title="Subrayado"
        >
          <Underline class="h-4 w-4" />
        </button>
      </div>

      <div v-if="!isCodeView" class="w-px h-6 bg-border mx-1"></div>

      <!-- Listas (solo en vista visual) -->
      <div v-if="!isCodeView" class="flex items-center gap-1">
        <button
          type="button"
          @click="execCommand('insertUnorderedList')"
          :class="[
            'p-2 rounded hover:bg-accent hover:text-accent-foreground transition-colors',
            isCommandActive('insertUnorderedList') ? 'bg-accent text-accent-foreground' : 'text-muted-foreground'
          ]"
          title="Lista con viñetas"
        >
          <List class="h-4 w-4" />
        </button>
        <button
          type="button"
          @click="execCommand('insertOrderedList')"
          :class="[
            'p-2 rounded hover:bg-accent hover:text-accent-foreground transition-colors',
            isCommandActive('insertOrderedList') ? 'bg-accent text-accent-foreground' : 'text-muted-foreground'
          ]"
          title="Lista numerada"
        >
          <ListOrdered class="h-4 w-4" />
        </button>
      </div>

      <div v-if="!isCodeView" class="w-px h-6 bg-border mx-1"></div>

      <!-- Alineación (solo en vista visual) -->
      <div v-if="!isCodeView" class="flex items-center gap-1">
        <button
          type="button"
          @click="execCommand('justifyLeft')"
          :class="[
            'p-2 rounded hover:bg-accent hover:text-accent-foreground transition-colors',
            isCommandActive('justifyLeft') ? 'bg-accent text-accent-foreground' : 'text-muted-foreground'
          ]"
          title="Alinear izquierda"
        >
          <AlignLeft class="h-4 w-4" />
        </button>
        <button
          type="button"
          @click="execCommand('justifyCenter')"
          :class="[
            'p-2 rounded hover:bg-accent hover:text-accent-foreground transition-colors',
            isCommandActive('justifyCenter') ? 'bg-accent text-accent-foreground' : 'text-muted-foreground'
          ]"
          title="Centrar"
        >
          <AlignCenter class="h-4 w-4" />
        </button>
        <button
          type="button"
          @click="execCommand('justifyRight')"
          :class="[
            'p-2 rounded hover:bg-accent hover:text-accent-foreground transition-colors',
            isCommandActive('justifyRight') ? 'bg-accent text-accent-foreground' : 'text-muted-foreground'
          ]"
          title="Alinear derecha"
        >
          <AlignRight class="h-4 w-4" />
        </button>
      </div>

      <div v-if="!isCodeView" class="w-px h-6 bg-border mx-1"></div>

      <!-- Enlace (solo en vista visual) -->
      <button
        v-if="!isCodeView"
        type="button"
        @click="insertLink"
        class="p-2 rounded hover:bg-accent hover:text-accent-foreground transition-colors text-muted-foreground"
        title="Insertar enlace"
      >
        <Link class="h-4 w-4" />
      </button>

      <!-- Encabezados (solo en vista visual) -->
      <select
        v-if="!isCodeView"
        @change="(e) => execCommand('formatBlock', (e.target as HTMLSelectElement).value)"
        class="ml-2 px-2 py-1 text-sm border border-input rounded bg-background text-foreground"
      >
        <option value="">Formato</option>
        <option value="<h1>">Título 1</option>
        <option value="<h2>">Título 2</option>
        <option value="<h3>">Título 3</option>
        <option value="<p>">Párrafo</option>
      </select>

      <!-- Indicador de vista actual -->
      <span class="ml-auto text-xs text-muted-foreground">
        {{ isCodeView ? 'Vista de código' : 'Vista visual' }}
      </span>
    </div>

    <!-- Editor visual -->
    <div
      v-if="!isCodeView"
      ref="editorRef"
      contenteditable
      :class="[
        'min-h-[200px] p-3 text-sm text-foreground focus:outline-none',
        disabled ? 'opacity-50 cursor-not-allowed' : ''
      ]"
      :placeholder="placeholder"
      @input="handleInput"
      @paste="handlePaste"
      @focus="isActive = true"
      @blur="isActive = false"
    ></div>

    <!-- Editor de código -->
    <textarea
      v-if="isCodeView"
      ref="codeViewRef"
      :value="currentContent"
      :class="[
        'min-h-[200px] p-3 text-sm text-foreground focus:outline-none bg-background border-0 resize-none w-full font-mono',
        disabled ? 'opacity-50 cursor-not-allowed' : ''
      ]"
      :placeholder="placeholder"
      @input="handleCodeInput"
      @focus="isActive = true"
      @blur="isActive = false"
    ></textarea>
  </div>

  <!-- Error -->
  <p v-if="error" class="mt-1 text-sm text-destructive">{{ error }}</p>
</template>

<style scoped>
.rich-text-editor [contenteditable]:empty:before {
  content: attr(placeholder);
  color: hsl(var(--muted-foreground));
  pointer-events: none;
}

.rich-text-editor [contenteditable] {
  outline: none;
  resize: vertical;
  overflow: auto;
}

.rich-text-editor [contenteditable]:focus {
  outline: none;
}

/* Estilos para el contenido del editor */
.rich-text-editor :deep(h1) {
  font-size: 2em;
  font-weight: bold;
  margin: 0.5em 0;
}

.rich-text-editor :deep(h2) {
  font-size: 1.5em;
  font-weight: bold;
  margin: 0.5em 0;
}

.rich-text-editor :deep(h3) {
  font-size: 1.2em;
  font-weight: bold;
  margin: 0.5em 0;
}

.rich-text-editor :deep(p) {
  margin: 0.5em 0;
}

.rich-text-editor :deep(ul),
.rich-text-editor :deep(ol) {
  margin: 0.5em 0;
  padding-left: 2em;
}

.rich-text-editor :deep(li) {
  margin: 0.25em 0;
}

.rich-text-editor :deep(a) {
  color: hsl(var(--primary));
  text-decoration: underline;
}

.rich-text-editor :deep(strong) {
  font-weight: bold;
}

.rich-text-editor :deep(em) {
  font-style: italic;
}

.rich-text-editor :deep(u) {
  text-decoration: underline;
}

/* Estilos para la vista de código */
.rich-text-editor textarea {
  font-family: 'Courier New', Consolas, 'Liberation Mono', Menlo, Courier, monospace;
  line-height: 1.5;
  tab-size: 2;
  resize: vertical;
}

.rich-text-editor textarea:focus {
  outline: none;
}
</style>