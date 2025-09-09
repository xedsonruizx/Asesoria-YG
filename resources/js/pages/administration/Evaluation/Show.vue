<script setup lang="ts">
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { ArrowLeft, User, Calendar, CheckCircle, Clock, FileText, RotateCcw } from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';

interface User {
  id: number;
  name: string;
  email: string;
}

interface Question {
  id: number;
  question_text: string;
  question_type: 'text' | 'textarea' | 'select' | 'number' | 'checkbox' | 'yes_no';
  options?: string[];
  points: number;
  category: {
    id: number;
    name: string;
  };
}

interface Answer {
  id: number;
  question_id: number;
  answer_value: any;
  points_earned: number;
  question: Question;
}

interface Evaluation {
  id: number;
  user: User;
  status: 'draft' | 'in_progress' | 'completed';
  total_score: number;
  total_progress: number;
  completed_at?: string;
  created_at: string;
  updated_at: string;
  answers: Answer[];
}

interface CategoryScore {
  name: string;
  score: number;
  max_score: number;
  percentage: number;
  questions_count: number;
  answered_count: number;
}

interface Report {
  total_score: number;
  max_possible_score: number;
  overall_percentage: number;
  categories: CategoryScore[];
  recommendations: string[];
}

const props = defineProps<{
  evaluation: Evaluation;
  report: Report;
}>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Evaluaciones', href: '/evaluations' },
  { title: `Evaluación de ${props.evaluation.user.name}`, current: true },
];

// Computed properties
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getStatusColor = (status: string) => {
  switch (status) {
    case 'completed':
      return 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-300';
    case 'in_progress':
      return 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300';
    case 'draft':
      return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300';
    default:
      return 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300';
  }
};

const getStatusText = (status: string) => {
  switch (status) {
    case 'completed':
      return 'Completada';
    case 'in_progress':
      return 'En Progreso';
    case 'draft':
      return 'Borrador';
    default:
      return status;
  }
};

const getScoreColor = (percentage: number) => {
  if (percentage >= 80) return 'text-green-600';
  if (percentage >= 60) return 'text-yellow-600';
  return 'text-red-600';
};

const getProgressBarColor = (percentage: number) => {
  if (percentage >= 80) return 'bg-green-500';
  if (percentage >= 60) return 'bg-yellow-500';
  return 'bg-red-500';
};

const formatAnswer = (answer: Answer) => {
  const value = answer.answer_value;
  
  switch (answer.question.question_type) {
    case 'yes_no':
      return value ? 'Sí' : 'No';
    case 'checkbox':
      return Array.isArray(value) ? value.join(', ') : value;
    case 'select':
      return value;
    default:
      return value;
  }
};

const answersByCategory = computed(() => {
  const grouped: Record<string, Answer[]> = {};
  
  props.evaluation.answers.forEach(answer => {
    const categoryName = answer.question.category.name;
    if (!grouped[categoryName]) {
      grouped[categoryName] = [];
    }
    grouped[categoryName].push(answer);
  });
  
  return grouped;
});

// Functions
const goBack = () => {
  router.visit('/evaluations');
};

const resetEvaluation = () => {
  if (confirm('¿Estás seguro de que quieres reiniciar esta evaluación? Se perderán todas las respuestas.')) {
    router.post(`/evaluations/${props.evaluation.id}/reset`);
  }
};
</script>

<template>
  <Head :title="`Evaluación de ${evaluation.user.name}`" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
      <!-- Header -->
      <div class="bg-card rounded-lg p-6 shadow-sm border border-border">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">
          <div class="flex-1">
            <div class="flex items-center gap-3 mb-2">
              <Button 
                variant="outline" 
                size="sm" 
                @click="goBack"
                class="flex items-center gap-2"
              >
                <ArrowLeft class="h-4 w-4" />
                Volver
              </Button>
              <span 
                :class="getStatusColor(evaluation.status)"
                class="px-2 py-1 rounded-full text-xs font-medium"
              >
                {{ getStatusText(evaluation.status) }}
              </span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold mb-2 text-foreground">
              Evaluación de {{ evaluation.user.name }}
            </h1>
            <div class="flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
              <div class="flex items-center gap-2">
                <User class="h-4 w-4" />
                {{ evaluation.user.email }}
              </div>
              <div class="flex items-center gap-2">
                <Calendar class="h-4 w-4" />
                Creada: {{ formatDate(evaluation.created_at) }}
              </div>
              <div v-if="evaluation.completed_at" class="flex items-center gap-2">
                <CheckCircle class="h-4 w-4" />
                Completada: {{ formatDate(evaluation.completed_at) }}
              </div>
            </div>
          </div>
          <div class="flex gap-2">
            <Button 
              v-if="evaluation.status === 'completed'"
              variant="outline" 
              @click="resetEvaluation"
              class="flex items-center gap-2"
            >
              <RotateCcw class="h-4 w-4" />
              Reiniciar
            </Button>
          </div>
        </div>
      </div>

      <!-- Resumen de Puntuación -->
      <div class="bg-card rounded-lg p-6 shadow-sm border border-border">
        <h2 class="text-lg font-semibold mb-4 flex items-center gap-2">
          <FileText class="h-5 w-5" />
          Resumen de Puntuación
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div class="text-center p-4 bg-muted rounded-lg">
            <div class="text-2xl font-bold text-foreground">{{ report.total_score }}</div>
            <div class="text-sm text-muted-foreground">de {{ report.max_possible_score }} puntos</div>
          </div>
          <div class="text-center p-4 bg-muted rounded-lg">
            <div :class="getScoreColor(report.overall_percentage)" class="text-2xl font-bold">
              {{ Math.round(report.overall_percentage) }}%
            </div>
            <div class="text-sm text-muted-foreground">Puntuación General</div>
          </div>
          <div class="text-center p-4 bg-muted rounded-lg">
            <div class="text-2xl font-bold text-foreground">{{ evaluation.answers.length }}</div>
            <div class="text-sm text-muted-foreground">Preguntas Respondidas</div>
          </div>
        </div>

        <!-- Puntuación por Categorías -->
        <div class="space-y-4">
          <h3 class="font-medium text-foreground">Puntuación por Categorías</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div 
              v-for="category in report.categories" 
              :key="category.name"
              class="p-4 border border-border rounded-lg"
            >
              <div class="flex justify-between items-center mb-2">
                <h4 class="font-medium text-foreground">{{ category.name }}</h4>
                <span :class="getScoreColor(category.percentage)" class="font-semibold">
                  {{ Math.round(category.percentage) }}%
                </span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2 mb-2">
                <div 
                  :class="getProgressBarColor(category.percentage)"
                  class="h-2 rounded-full transition-all duration-300"
                  :style="{ width: category.percentage + '%' }"
                ></div>
              </div>
              <div class="text-sm text-muted-foreground">
                {{ category.score }} de {{ category.max_score }} puntos
                ({{ category.answered_count }} de {{ category.questions_count }} preguntas)
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Preguntas y Respuestas por Categoría -->
      <div class="space-y-6">
        <div 
          v-for="(answers, categoryName) in answersByCategory" 
          :key="categoryName"
          class="bg-card rounded-lg p-6 shadow-sm border border-border"
        >
          <h2 class="text-lg font-semibold mb-4 text-foreground border-b border-border pb-2">
            {{ categoryName }}
          </h2>
          
          <div class="space-y-4">
            <div 
              v-for="answer in answers" 
              :key="answer.id"
              class="p-4 bg-muted rounded-lg"
            >
              <div class="mb-2">
                <h3 class="font-medium text-foreground mb-1">
                  {{ answer.question.question_text }}
                </h3>
                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                  <span>Tipo: {{ answer.question.question_type }}</span>
                  <span>•</span>
                  <span>Puntos: {{ answer.points_earned }} / {{ answer.question.points }}</span>
                </div>
              </div>
              
              <div class="mt-2">
                <div class="font-medium text-sm text-muted-foreground mb-1">Respuesta:</div>
                <div class="p-3 bg-background rounded border border-border">
                  <span class="text-foreground">{{ formatAnswer(answer) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recomendaciones -->
      <div 
        v-if="report.recommendations && report.recommendations.length > 0"
        class="bg-card rounded-lg p-6 shadow-sm border border-border"
      >
        <h2 class="text-lg font-semibold mb-4 text-foreground">
          Recomendaciones
        </h2>
        <ul class="space-y-2">
          <li 
            v-for="(recommendation, index) in report.recommendations" 
            :key="index"
            class="flex items-start gap-2 text-muted-foreground"
          >
            <span class="text-primary mt-1">•</span>
            <span>{{ recommendation }}</span>
          </li>
        </ul>
      </div>
    </div>
  </AppLayout>
</template>