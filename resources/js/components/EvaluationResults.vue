<template>
  <div class="min-h-screen bg-gray-900 py-8 px-4">
    <div class="max-w-4xl mx-auto">
      <!-- Header con indicadores de estado -->
      <div class="flex justify-center mb-8 space-x-8">
        <div class="flex items-center space-x-2">
          <div class="w-3 h-3 bg-red-500 rounded-full"></div>
          <span class="text-white text-sm">Requiere atención</span>
        </div>
        <div class="flex items-center space-x-2">
          <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
          <span class="text-white text-sm">Mejorable</span>
        </div>
        <div class="flex items-center space-x-2">
          <div class="w-3 h-3 bg-green-500 rounded-full"></div>
          <span class="text-white text-sm">Excelente</span>
        </div>
      </div>

      <!-- Título principal -->
      <h1 class="text-white text-3xl font-bold text-center mb-12">
        Resultados de su Evaluación
      </h1>

      <!-- Círculos de progreso por categoría -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-12 mb-12">
        <!-- Recursos Humanos -->
        <div class="text-center">
          <h2 class="text-blue-400 text-xl font-semibold mb-6">Recursos Humanos</h2>
          <div class="relative inline-flex items-center justify-center">
            <!-- Círculo de progreso -->
            <svg class="w-48 h-48 transform -rotate-90" viewBox="0 0 100 100">
              <!-- Círculo de fondo -->
              <circle
                cx="50"
                cy="50"
                r="40"
                stroke="#374151"
                stroke-width="8"
                fill="none"
              />
              <!-- Círculo de progreso -->
              <circle
                cx="50"
                cy="50"
                r="40"
                :stroke="getColorByScore(rhScore)"
                stroke-width="8"
                fill="none"
                stroke-linecap="round"
                :stroke-dasharray="circumference"
                :stroke-dashoffset="circumference - (rhScore / 100) * circumference"
                class="transition-all duration-1000 ease-out"
              />
            </svg>
            <!-- Porcentaje en el centro -->
            <div class="absolute inset-0 flex items-center justify-center">
              <span class="text-white text-4xl font-bold">{{ rhScore }}%</span>
            </div>
          </div>
          <div class="mt-4">
            <h3 :class="getStatusClass(rhScore)" class="text-lg font-semibold mb-2">
              {{ getStatusText(rhScore, 'RRHH') }}
            </h3>
            <p class="text-gray-400 text-sm">
              {{ getStatusDescription(rhScore, 'RRHH') }}
            </p>
          </div>
        </div>

        <!-- Legal -->
        <div class="text-center">
          <h2 class="text-purple-400 text-xl font-semibold mb-6">Legal</h2>
          <div class="relative inline-flex items-center justify-center">
            <!-- Círculo de progreso -->
            <svg class="w-48 h-48 transform -rotate-90" viewBox="0 0 100 100">
              <!-- Círculo de fondo -->
              <circle
                cx="50"
                cy="50"
                r="40"
                stroke="#374151"
                stroke-width="8"
                fill="none"
              />
              <!-- Círculo de progreso -->
              <circle
                cx="50"
                cy="50"
                r="40"
                :stroke="getColorByScore(legalScore)"
                stroke-width="8"
                fill="none"
                stroke-linecap="round"
                :stroke-dasharray="circumference"
                :stroke-dashoffset="circumference - (legalScore / 100) * circumference"
                class="transition-all duration-1000 ease-out"
              />
            </svg>
            <!-- Porcentaje en el centro -->
            <div class="absolute inset-0 flex items-center justify-center">
              <span class="text-white text-4xl font-bold">{{ legalScore }}%</span>
            </div>
          </div>
          <div class="mt-4">
            <h3 :class="getStatusClass(legalScore)" class="text-lg font-semibold mb-2">
              {{ getStatusText(legalScore, 'LEGAL') }}
            </h3>
            <p class="text-gray-400 text-sm">
              {{ getStatusDescription(legalScore, 'LEGAL') }}
            </p>
          </div>
        </div>
      </div>

      <!-- Estadísticas resumidas -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
        <div class="bg-gray-800 rounded-lg p-6 text-center">
          <div class="text-white text-3xl font-bold mb-2">{{ totalQuestions }}</div>
          <div class="text-gray-400 text-sm">Preguntas respondidas</div>
        </div>
        <div class="bg-gray-800 rounded-lg p-6 text-center">
          <div class="text-white text-3xl font-bold mb-2">{{ rhScore }}%</div>
          <div class="text-gray-400 text-sm">Puntaje RRHH</div>
        </div>
        <div class="bg-gray-800 rounded-lg p-6 text-center">
          <div class="text-white text-3xl font-bold mb-2">{{ legalScore }}%</div>
          <div class="text-gray-400 text-sm">Puntaje Legal</div>
        </div>
      </div>

      <!-- Botones de acción -->
      <div class="flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-4">
        <button
          @click="$emit('restart')"
          class="px-8 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition-colors duration-200 border border-gray-600"
        >
          Realizar nueva evaluación
        </button>
        <button
          @click="$emit('requestConsultation')"
          class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors duration-200"
        >
          Solicitar consulta profesional
        </button>
      </div>

      <!-- Disclaimer -->
      <div class="mt-12 text-center">
        <p class="text-gray-400 text-sm mb-2">
          Esta evaluación es solo orientativa y no constituye asesoría legal profesional.
        </p>
        <p class="text-gray-400 text-sm">
          Para obtener asesoría personalizada, contacte con nuestros especialistas.
        </p>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  rhScore: number
  legalScore: number
  totalQuestions: number
}

interface Emits {
  restart: []
  requestConsultation: []
}

const props = defineProps<Props>()
defineEmits<Emits>()

// Circunferencia del círculo para el cálculo del stroke-dasharray
const circumference = computed(() => 2 * Math.PI * 40)

// Función para obtener el color según el puntaje
const getColorByScore = (score: number): string => {
  if (score >= 70) return '#10B981' // Verde
  if (score >= 50) return '#F59E0B' // Amarillo
  return '#EF4444' // Rojo
}

// Función para obtener la clase CSS del estado
const getStatusClass = (score: number): string => {
  if (score >= 70) return 'text-green-400'
  if (score >= 50) return 'text-yellow-400'
  return 'text-red-400'
}

// Función para obtener el texto del estado
const getStatusText = (score: number, category: string): string => {
  if (score >= 70) return `Excelente situación en ${category}`
  if (score >= 50) return `Situación mejorable en ${category}`
  return `Requiere atención en ${category}`
}

// Función para obtener la descripción del estado
const getStatusDescription = (score: number, category: string): string => {
  if (score >= 70) {
    return category === 'RRHH' 
      ? 'Su situación en RRHH está muy bien estructurada.'
      : 'Su situación en LEGAL está muy bien estructurada.'
  }
  if (score >= 50) {
    return category === 'RRHH'
      ? 'Hay aspectos en RRHH que podrían mejorarse.'
      : 'Hay aspectos en LEGAL que podrían mejorarse.'
  }
  return category === 'RRHH'
    ? 'Hay aspectos críticos en RRHH que requieren atención inmediata.'
    : 'Hay aspectos críticos en LEGAL que requieren atención inmediata.'
}
</script>