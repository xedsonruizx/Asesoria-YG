<script setup lang="ts">
import { ref, watch, onMounted, computed } from 'vue';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';

interface Multa {
  id: number;
  name: string;
  description: string;
}

interface MultaCondition {
  multa_id?: number | null;
  trigger_condition?: string;
  trigger_value?: string;
}

interface Props {
  modelValue: boolean;
  multaCondition: MultaCondition;
  errors?: Record<string, string>;
}

interface Emits {
  'update:modelValue': [value: boolean];
  'update:multaCondition': [value: MultaCondition];
}

const props = withDefaults(defineProps<Props>(), {
  errors: () => ({})
});

const emit = defineEmits<Emits>();

const availableMultas = ref<Multa[]>([]);
const loading = ref(false);

// Computed para el valor del checkbox
const hasMultaAssignment = computed({
  get: () => props.modelValue,
  set: (value: boolean) => emit('update:modelValue', value)
});

// Computed para multa_condition
const condition = computed({
  get: () => props.multaCondition,
  set: (value: MultaCondition) => emit('update:multaCondition', value)
});

// Computed para las opciones de condición de activación
const availableTriggerConditions = computed(() => [
  { value: 'always', label: 'Siempre aplicar' },
  { value: 'on_fail', label: 'Al fallar la pregunta' },
  { value: 'on_specific_answer', label: 'Con respuesta específica' },
  { value: 'on_low_score', label: 'Con puntuación baja' }
]);

// Función para cargar multas disponibles
const loadAvailableMultas = async () => {
  loading.value = true;
  try {
    const response = await fetch('/api/multas');
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const contentType = response.headers.get('content-type');
    if (!contentType || !contentType.includes('application/json')) {
      throw new Error('Response is not JSON');
    }
    
    const multas = await response.json();
    availableMultas.value = multas;
  } catch (error) {
    console.error('Error loading multas:', error);
    availableMultas.value = [];
    
    // Agregar un fallback para debugging
    console.log('Intentando cargar multas desde el endpoint alternativo...');
    try {
      // Intentar con el endpoint directo del controlador
      const fallbackResponse = await fetch('/multas');
      if (fallbackResponse.ok) {
        console.log('Endpoint /multas funciona, pero necesita configuración API');
      }
    } catch (fallbackError) {
      console.error('Fallback también falló:', fallbackError);
    }
  } finally {
    loading.value = false;
  }
};

// Función para actualizar multa_id
const updateMultaId = (value: number | null) => {
  const newCondition = {
    ...props.multaCondition,
    multa_id: value
  };
  
  emit('update:multaCondition', newCondition);
};

// Función para actualizar trigger_condition
const updateTriggerCondition = (value: string) => {
  emit('update:multaCondition', {
    ...props.multaCondition,
    trigger_condition: value
  });
};

// Función para actualizar trigger_value
const updateTriggerValue = (value: string) => {
  emit('update:multaCondition', {
    ...props.multaCondition,
    trigger_value: value
  });
};

// Cargar multas cuando se active la asignación
watch(hasMultaAssignment, (newValue) => {
  if (newValue) {
    loadAvailableMultas();
  } else {
    availableMultas.value = [];
    // Limpiar los datos de asignación cuando se desmarca
    emit('update:multaCondition', {
      multa_id: null,
      trigger_condition: 'always',
      trigger_value: null
    });
  }
});

// Cargar multas al montar el componente si ya hay asignación
onMounted(() => {
  // Auto-detectar si existe una multa asociada
  if (props.multaCondition?.multa_id && !props.modelValue) {
    // Si hay multa_id pero el checkbox no está marcado, activarlo
    emit('update:modelValue', true);
  }
  
  if (hasMultaAssignment.value || props.multaCondition?.multa_id) {
    loadAvailableMultas();
  }
});

// Watcher adicional para detectar cambios en multaCondition
watch(() => props.multaCondition?.multa_id, (newMultaId) => {
  if (newMultaId && !hasMultaAssignment.value) {
    // Si se asigna una multa externamente, activar el checkbox
    emit('update:modelValue', true);
    if (availableMultas.value.length === 0) {
      loadAvailableMultas();
    }
  }
}, { immediate: true });
</script>

<template>
  <div class="space-y-4 border-t border-border pt-4">
    <h4 class="font-medium text-foreground">Asignación de Multas</h4>
    
    <div class="flex items-center space-x-2">
      <input 
        id="has_multa_assignment"
        v-model="hasMultaAssignment"
        type="checkbox"
        class="rounded border-input text-primary focus:ring-ring"
      />
      <Label for="has_multa_assignment">Esta pregunta puede generar una multa</Label>
    </div>
    
    <div v-if="hasMultaAssignment" class="space-y-4 ml-6">
      <!-- Mostrar estado de carga y debugging -->
      <div v-if="loading" class="text-sm text-muted-foreground">
        Cargando multas...
      </div>
      
      <div v-if="!loading && availableMultas.length === 0" class="text-sm text-red-600">
        No se pudieron cargar las multas. Verifica la consola para más detalles.
      </div>
      
      <div>
        <Label for="multa_select">Multa a aplicar</Label>
        <select 
          id="multa_select"
          :value="condition.multa_id || ''"
          @change="updateMultaId($event.target.value ? parseInt($event.target.value) : null)"
          class="w-full px-3 py-2 border border-input rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
        >
          <option value="">{{ availableMultas.length === 0 ? 'No hay multas disponibles' : 'Seleccionar multa' }}</option>
          <option v-for="multa in availableMultas" :key="multa.id" :value="multa.id">
            {{ multa.name }}
          </option>
        </select>
        <InputError :message="errors['multa_condition.multa_id']" />
      </div>
      
      <div>
        <Label for="trigger_condition">Condición de activación</Label>
        <select 
          id="trigger_condition"
          :value="multaCondition.trigger_condition || 'always'"
          @input="updateTriggerCondition(($event.target as HTMLSelectElement).value)"
          class="w-full px-3 py-2 border border-input rounded-md bg-background text-foreground focus:outline-none focus:ring-2 focus:ring-ring"
        >
          <option 
            v-for="trigger in availableTriggerConditions" 
            :key="trigger.value" 
            :value="trigger.value"
          >
            {{ trigger.label }}
          </option>
        </select>
        <InputError :message="errors['multa_condition.trigger_condition']" />
      </div>
      
      <div v-if="['on_specific_answer', 'on_low_score'].includes(multaCondition.trigger_condition || 'always')">
        <Label for="trigger_value">Valor de activación</Label>
        <Input 
          id="trigger_value"
          :model-value="multaCondition.trigger_value || ''"
          @update:model-value="updateTriggerValue"
          :placeholder="multaCondition.trigger_condition === 'on_low_score' ? 'Puntuación mínima (ej: 5)' : 'Respuesta específica'"
        />
        <InputError :message="errors['multa_condition.trigger_value']" />
      </div>
      
      <div v-if="condition.multa_id" class="bg-muted p-3 rounded-md">
        <p class="text-sm text-muted-foreground">
          <strong>Multa seleccionada:</strong> 
          {{ availableMultas.find(m => m.id === condition.multa_id)?.name }}
        </p>
        <p class="text-sm text-muted-foreground mt-1">
          {{ availableMultas.find(m => m.id === condition.multa_id)?.description }}
        </p>
      </div>
    </div>
  </div>
</template>