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

    <Pre>
      {{ triggeredMultas }}
    </Pre>

      <!-- Sección de Multas Activadas -->
      <div v-if="triggeredMultas && triggeredMultas.length > 0" class="mb-12">
        <div class="bg-red-900/30 border border-red-500/50 rounded-lg p-6">
          <h2 class="text-red-400 text-2xl font-bold mb-4 flex items-center">
            <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            Aspectos que Requieren Atención Inmediata
          </h2>
          <p class="text-red-300 mb-6">
            Basándose en sus respuestas, se han identificado los siguientes aspectos que requieren atención legal inmediata:
          </p>
          
          <div class="space-y-4">
            <div 
              v-for="(multa, index) in triggeredMultas" 
              :key="`multa-${multa.multa_id}-${index}`"
              class="bg-red-800/20 border border-red-400/30 rounded-lg p-4"
            >
              <div class="flex items-start space-x-3">
                <div class="flex-shrink-0 w-6 h-6 bg-red-500 rounded-full flex items-center justify-center text-white text-sm font-bold mt-1">
                  {{ index + 1 }}
                </div>
                <div class="flex-1">
                  <h3 class="text-red-300 font-semibold text-lg mb-2">
                    {{ multa.multa_name }}
                  </h3>
                  <p class="text-red-200 mb-3">
                    {{ multa.multa_description }}
                  </p>
                  <div class="text-sm text-red-400 space-y-1">
                    <p><strong>Categoría:</strong> {{ multa.category_name }}</p>
                    <p><strong>Pregunta relacionada:</strong> {{ multa.question_text }}</p>
                    <p><strong>Su respuesta:</strong> 
                      <span class="text-red-300">
                        {{ formatAnswerValue(multa.answer_value) }}
                      </span>
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="mt-6 p-4 bg-yellow-900/30 border border-yellow-500/50 rounded-lg">
            <p class="text-yellow-300 text-sm">
              <strong>Recomendación:</strong> Se recomienda encarecidamente solicitar una consulta profesional para abordar estos aspectos críticos y evitar posibles consecuencias legales.
            </p>
          </div>
        </div>
      </div>

      <!-- Círculos de progreso por categoría (dinámico) -->
      <div class="grid gap-12 mb-12" :class="{
        'grid-cols-1': visibleCategories.length === 1,
        'grid-cols-1 md:grid-cols-2': visibleCategories.length === 2,
        'grid-cols-1 md:grid-cols-2 lg:grid-cols-3': visibleCategories.length >= 3
      }">
        <div v-for="(category, index) in visibleCategories" :key="category.id" class="text-center">
          <h2 class="text-xl font-semibold mb-6" :style="{ color: getCategoryColor(category, index) }">
            {{ category.name }}
          </h2>
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
                :stroke="getColorByScore(getCategoryPercentage(category.name))"
                stroke-width="8"
                fill="none"
                stroke-linecap="round"
                :stroke-dasharray="circumference"
                :stroke-dashoffset="circumference - (getCategoryPercentage(category.name) / 100) * circumference"
                class="transition-all duration-1000 ease-out"
              />
            </svg>
            <!-- Porcentaje en el centro -->
            <div class="absolute inset-0 flex items-center justify-center">
              <span class="text-white text-4xl font-bold">{{ getCategoryPercentage(category.name) }}%</span>
            </div>
          </div>
          <div class="mt-4">
            <h3 :class="getStatusClass(getCategoryPercentage(category.name))" class="text-lg font-semibold mb-2">
              {{ getStatusText(getCategoryPercentage(category.name), category.name) }}
            </h3>
            <p class="text-gray-400 text-sm">
              {{ getStatusDescription(getCategoryPercentage(category.name), category.name) }}
            </p>
          </div>
        </div>
      </div>

      <!-- Estadísticas resumidas (dinámico) -->
      <div class="grid gap-6 mb-12" :class="{
        'grid-cols-1 md:grid-cols-2': visibleCategories.length === 1,
        'grid-cols-1 md:grid-cols-3': visibleCategories.length === 2,
        'grid-cols-2 md:grid-cols-4': visibleCategories.length >= 3
      }">
        <!-- <div class="bg-gray-800 rounded-lg p-6 text-center">
          <div class="text-white text-3xl font-bold mb-2">{{ totalQuestions }}</div>
          <div class="text-gray-400 text-sm">Preguntas respondidas</div>
        </div> -->
        <div v-for="category in visibleCategories" :key="`stat-${category.id}`" class="bg-gray-800 rounded-lg p-6 text-center">
          <div class="text-white text-3xl font-bold mb-2">{{ getCategoryPercentage(category.name) }}%</div>
          <div class="text-gray-400 text-sm">Puntaje {{ category.name }}</div>
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
          :class="{ 'bg-red-600 hover:bg-red-700 animate-pulse': triggeredMultas && triggeredMultas.length > 0 }"
        >
          {{ triggeredMultas && triggeredMultas.length > 0 ? 'Solicitar consulta urgente' : 'Solicitar consulta profesional' }}
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

interface Category {
  id: number
  name: string
  slug: string
  color?: string
}

interface CategoryScore {
  category: string
  score: number
  maxScore: number
  progress: number
}

interface TriggeredMulta {
  multa_id: number
  multa_name: string
  multa_description: string
  question_id: number
  question_text: string
  answer_value: any
  trigger_condition: string
  trigger_value?: string
  category_name: string
}

interface Props {
  categories: Category[]
  categoryScores: Record<string, CategoryScore>
  totalQuestions: number
  triggeredMultas?: TriggeredMulta[]
}

interface Emits {
  restart: []
  requestConsultation: []
}

const props = defineProps<Props>()
defineEmits<Emits>()

// Filtrar categorías que tienen preguntas respondidas (score > 0)
const visibleCategories = computed(() => {
  return props.categories.filter(category => {
    const categoryScore = props.categoryScores[category.name]
    return categoryScore && categoryScore.score > 0
  })
})

// Calcular el porcentaje para cada categoría
const getCategoryPercentage = (categoryName: string): number => {
  const categoryScore = props.categoryScores[categoryName]
  if (!categoryScore || categoryScore.maxScore === 0) return 0
  return Math.round((categoryScore.score / categoryScore.maxScore) * 100)
}

// Obtener el color de la categoría o uno por defecto
const getCategoryColor = (category: Category, index: number): string => {
  if (category.color) return category.color
  // Colores por defecto si no se especifica
  const defaultColors = ['#3B82F6', '#8B5CF6', '#10B981', '#F59E0B', '#EF4444']
  return defaultColors[index % defaultColors.length]
}

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
const getStatusText = (score: number, categoryName: string): string => {
  if (score >= 70) return `Excelente situación en ${categoryName}`
  if (score >= 50) return `Situación mejorable en ${categoryName}`
  return `Requiere atención en ${categoryName}`
}

// Función para obtener la descripción del estado
const getStatusDescription = (score: number, categoryName: string): string => {
  if (score >= 70) {
    return `Su situación en ${categoryName} está muy bien estructurada.`
  }
  if (score >= 50) {
    return `Hay aspectos en ${categoryName} que podrían mejorarse.`
  }
  return `Hay aspectos críticos en ${categoryName} que requieren atención inmediata.`
}

// Función para formatear el valor de la respuesta
const formatAnswerValue = (value: any): string => {
  if (Array.isArray(value)) {
    return value.join(', ')
  }
  if (typeof value === 'boolean') {
    return value ? 'Sí' : 'No'
  }
  if (value === '1' || value === 1) {
    return 'Sí'
  }
  if (value === '0' || value === 0) {
    return 'No'
  }
  return String(value)
}
</script>