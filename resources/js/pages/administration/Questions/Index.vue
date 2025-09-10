<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Plus, Edit, Trash2, Search, Filter, Users } from 'lucide-vue-next';
import { type BreadcrumbItem } from '@/types';

// Interfaces
interface Category {
    id: number;
    name: string;
    slug: string;
    description?: string;
    color?: string;
    icon?: string;
    is_active: boolean;
}

interface Question {
    id: number;
    category_id: number;
    question_text: string;
    question_type: string;
    options?: string[];
    placeholder?: string;
    min_value?: number;
    max_value?: number;
    points: number;
    order: number;
    show_condition?: {
        parent_question_id: number;
        operator: string;
        expected_value: any;
    };
    validation_rules?: any;
    is_required: boolean;
    is_active: boolean;
    created_at: string;
    updated_at: string;
    category: Category;
    has_answers: boolean;
    answers_count: number;
}

interface Stats {
    total: number;
    active: number;
    inactive: number;
    with_answers: number;
}

interface QuestionsData {
    questions: Question[];
    stats: Stats;
    categories: Category[];
    filters: {
        search?: string;
        category_id?: number;
        question_type?: string;
        is_active?: boolean;
    };
}

// Props
const props = withDefaults(defineProps<QuestionsData>(), {
    questions: () => [],
    stats: () => ({ total: 0, active: 0, inactive: 0, with_answers: 0 }),
    categories: () => [],
    filters: () => ({}),
});

// Reactive variables
const search = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category_id?.toString() || '');
const selectedType = ref(props.filters.question_type || '');
const selectedStatus = ref(props.filters.is_active?.toString() || '');
const questionToDelete = ref<Question | null>(null);
const showDeleteModal = ref(false);

// Constants
const questionTypes = [
    { value: 'text', label: 'Texto' },
    { value: 'textarea', label: 'Área de texto' },
    { value: 'select', label: 'Selección' },
    { value: 'number', label: 'Número' },
    { value: 'checkbox', label: 'Casillas' },
    { value: 'yes_no', label: 'Sí/No' },
];

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Preguntas', href: '/admin/questions' },
];

// Utility functions
const truncateText = (text: string, maxLength: number) => {
    return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
};

const getTypeLabel = (type: string) => {
    const typeObj = questionTypes.find(t => t.value === type);
    return typeObj ? typeObj.label : type;
};

// Navigation functions
const openCreateQuestion = () => {
    if (typeof window !== 'undefined') {
        window.open('/admin/questions/create', '_blank');
    }
};

const openEditInNewTab = (questionId: number) => {
    window.open(`/admin/questions/${questionId}/edit`, '_blank');
};

// Filter functions
const applyFilters = () => {
    const params: any = {};
    
    if (search.value) params.search = search.value;
    if (selectedCategory.value) params.category_id = selectedCategory.value;
    if (selectedType.value) params.question_type = selectedType.value;
    if (selectedStatus.value) params.is_active = selectedStatus.value === 'true';
    
    router.get('/admin/questions', params, {
        preserveState: true,
        replace: true,
    });
};

const clearFilters = () => {
    search.value = '';
    selectedCategory.value = '';
    selectedType.value = '';
    selectedStatus.value = '';
    
    router.get('/admin/questions', {}, {
        preserveState: true,
        replace: true,
    });
};

// Question management functions
const toggleStatus = (question: Question) => {
    router.patch(`/admin/questions/${question.id}/toggle-status`, {}, {
        preserveState: true,
    });
};

const confirmDelete = (question: Question) => {
    questionToDelete.value = question;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    questionToDelete.value = null;
};

const deleteQuestion = () => {
    if (questionToDelete.value) {
        router.delete(`/admin/questions/${questionToDelete.value.id}`, {
            preserveState: true,
            onSuccess: () => {
                closeDeleteModal();
            },
        });
    }
};

// Computed property para optimizar las llamadas a getDependencyInfo
const questionDependencyInfo = computed(() => {
    const dependencyMap = new Map();
    
    props.questions.forEach(question => {
        const dependencies = [];
        let canDelete = true;
        
        // Si tiene respuestas asociadas (otras preguntas dependen de esta)
        const dependents = props.questions?.filter(q =>
            q.show_condition &&
            q.show_condition.parent_question_id === question.id
        ) || [];

        if (dependents && dependents.length > 0) {
            // Mostrar los números de orden de las preguntas que dependen de esta
            const dependentOrders = dependents.map(q => q.order).sort((a, b) => a - b);
            dependencies.push({
                text: `Dependen de pregunta orden: ${dependentOrders.join(', ')}`,
                class: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-300'
            });
            canDelete = false;
        }
        
        // Si esta pregunta depende de otra
        if (question.show_condition) {
            const parentQuestionId = question.show_condition.parent_question_id;
            const operator = question.show_condition.operator || 'equals';
            const expectedValue = question.show_condition.expected_value;
                
            if (parentQuestionId) {
                const parentQuestion = props.questions.find(q => q.id === parentQuestionId);
                const parentText = parentQuestion ? truncateText(parentQuestion.question_text, 25) : `Pregunta #${parentQuestionId}`;
                const parentOrder = parentQuestion ? parentQuestion.order : 'N/A';
                
                let operatorText = '';
                switch (operator) {
                    case 'equals':
                        operatorText = '=';
                        break;
                    case 'not_equals':
                        operatorText = '≠';
                        break;
                    case 'greater_than':
                        operatorText = '>';
                        break;
                    case 'less_than':
                        operatorText = '<';
                        break;
                    case 'contains':
                        operatorText = 'contiene';
                        break;
                    case 'not_contains':
                        operatorText = 'no contiene';
                        break;
                    default:
                        operatorText = operator;
                }
                
                let displayValue;
                if (expectedValue === true) {
                    displayValue = 'Sí';
                } else if (expectedValue === false) {
                    displayValue = 'No';
                } else if (expectedValue !== undefined && expectedValue !== null) {
                    displayValue = String(expectedValue);
                } else {
                    displayValue = 'vacio';
                }
                
                dependencies.push({
                    text: `Depende de: Orden ${parentOrder}`,
                    class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300'
                });
            }
        }
        
        // Determinar el resultado final
        let result;
        if (!dependencies || dependencies.length === 0) {
            result = {
                text: 'Sin dependencias',
                class: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
                canDelete: true,
                dependencies: []
            };
        } else if (dependencies.length > 1) {
            result = {
                text: dependencies.map(dep => dep.text).join(' • '),
                class: 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-300',
                canDelete,
                dependencies
            };
        } else {
            result = {
                text: dependencies[0].text,
                class: dependencies[0].class,
                canDelete,
                dependencies
            };
        }
        
        dependencyMap.set(question.id, result);
    });
    
    return dependencyMap;
});

// Función helper para obtener la info de dependencias
const getDependencyInfoOptimized = (question: Question) => {
    return questionDependencyInfo.value.get(question.id) || {
        text: 'Sin dependencias',
        class: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
        canDelete: true,
        dependencies: []
    };
};
</script>

<template>
    <Head title="Gestión de Preguntas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <!-- Header con información y botón de crear -->
            <div class="bg-card rounded-lg p-6 shadow-sm border border-border">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4 mb-4">
                    <div class="flex-1">
                        <h1 class="text-xl sm:text-2xl font-bold mb-2 text-foreground">Gestión de Preguntas</h1>
                        <p class="text-muted-foreground text-sm sm:text-base">Administra las preguntas de evaluación del sistema</p>
                    </div>
                    <Button @click="openCreateQuestion" class="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2 bg-primary text-primary-foreground hover:bg-primary/90 rounded-md transition-colors font-medium text-xs sm:text-sm w-full sm:w-auto">
                        <Plus class="h-4 w-4 flex-shrink-0" />
                        <span class="truncate">Nueva Pregunta</span>
                    </Button>
                </div>
                
                <!-- Estadísticas -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-muted px-3 sm:px-4 py-2 sm:py-3 rounded-md">
                        <div class="flex items-center gap-2">
                            <div>
                                <p class="text-xs text-muted-foreground">Total</p>
                                <p class="font-semibold text-foreground">{{ props.stats.total }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/20 px-3 sm:px-4 py-2 sm:py-3 rounded-md">
                        <div class="flex items-center gap-2">
                            <div>
                                <p class="text-xs text-green-700 dark:text-green-300">Activas</p>
                                <p class="font-semibold text-green-700 dark:text-green-300">{{ props.stats.active }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-red-50 dark:bg-red-900/20 px-3 sm:px-4 py-2 sm:py-3 rounded-md">
                        <div class="flex items-center gap-2">
                            <div>
                                <p class="text-xs text-red-700 dark:text-red-300">Inactivas</p>
                                <p class="font-semibold text-red-700 dark:text-red-300">{{ props.stats.inactive }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-blue-50 dark:bg-blue-900/20 px-3 sm:px-4 py-2 sm:py-3 rounded-md">
                        <div class="flex items-center gap-2">
                            <div>
                                <p class="text-xs text-blue-700 dark:text-blue-300">Con Respuestas</p>
                                <p class="font-semibold text-blue-700 dark:text-blue-300">{{ props.stats.with_answers }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="bg-card rounded-lg p-4 shadow-sm border border-border">
                <div class="flex items-center gap-2 mb-4">
                    <Filter class="h-4 w-4" />
                    <h3 class="font-medium">Filtros</h3>
                </div>
                <div class="grid gap-4 md:grid-cols-5">
                    <div class="relative">
                        <Search class="absolute left-2 top-2.5 h-4 w-4 text-muted-foreground" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Buscar pregunta..."
                            class="w-full pl-8 pr-3 py-2 border border-input bg-background rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
                            @keyup.enter="applyFilters"
                        />
                    </div>
                    
                    <select 
                        v-model="selectedCategory"
                        class="w-full px-3 py-2 border border-input bg-background rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
                    >
                        <option value="">Todas las categorías</option>
                        <option 
                            v-for="category in props.categories" 
                            :key="category.id" 
                            :value="category.id.toString()"
                        >
                            {{ category.name }}
                        </option>
                    </select>

                    <select 
                        v-model="selectedType"
                        class="w-full px-3 py-2 border border-input bg-background rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
                    >
                        <option value="">Todos los tipos</option>
                        <option 
                            v-for="type in questionTypes" 
                            :key="type.value" 
                            :value="type.value"
                        >
                            {{ type.label }}
                        </option>
                    </select>

                    <select 
                        v-model="selectedStatus"
                        class="w-full px-3 py-2 border border-input bg-background rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-ring focus:border-transparent"
                    >
                        <option value="">Todos los estados</option>
                        <option value="true">Activas</option>
                        <option value="false">Inactivas</option>
                    </select>

                    <div class="flex gap-2">
                        <Button @click="applyFilters" class="flex-1">
                            Aplicar
                        </Button>
                        <Button @click="clearFilters" variant="outline">
                            Limpiar
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Tabla de preguntas -->
            <div class="bg-card rounded-lg overflow-hidden shadow-sm border border-border">
                <div v-if="props.questions && props.questions.length > 0">
                    <!-- Encabezados -->
                    <div class="bg-muted/30 p-4 border-b border-border">
                        <div class="grid grid-cols-1 md:grid-cols-7 gap-4 font-semibold text-foreground">
                            <div class="md:col-span-2">Pregunta</div>
                            <div class="hidden md:block">Categoría</div>
                            <div class="hidden md:block">Tipo</div>
                            <div class="hidden md:block">Puntos</div>
                            <div class="hidden md:block">Dependencias</div>
                            <div class="hidden md:block">Acciones</div>
                        </div>
                    </div>
                    
                    <!-- Filas de datos -->
                    <div>
                        <div 
                            v-for="question in props.questions" 
                            :key="question.id"
                            class="border-b border-border p-4 hover:bg-muted/50 transition-colors group"
                        >
                            <div class="grid grid-cols-1 md:grid-cols-7 gap-4 items-start md:items-center">
                                <!-- Pregunta -->
                                <div class="md:col-span-2">
                                    <div class="font-medium text-foreground mb-1">
                                        {{ truncateText(question.question_text, 60) }}
                                    </div>
                                    <div class="text-sm text-muted-foreground">
                                        Orden: {{ question.order }} • 
                                        <span :class="question.is_active ? 'text-green-600' : 'text-red-600'">
                                            {{ question.is_active ? 'Activa' : 'Inactiva' }}
                                        </span>
                                    </div>
                                    
                                    <!-- Información adicional en móvil -->
                                    <div class="md:hidden mt-3 space-y-2">
                                        <div class="text-sm">
                                            <strong class="text-foreground">Categoría:</strong> 
                                            <span class="inline-flex px-2 py-1 text-xs font-medium bg-muted text-muted-foreground rounded-full ml-1">
                                                {{ question.category.name }}
                                            </span>
                                        </div>
                                        <div class="text-sm">
                                            <strong class="text-foreground">Tipo:</strong> 
                                            <span class="text-muted-foreground">{{ getTypeLabel(question.question_type) }}</span>
                                        </div>
                                        <div class="text-sm">
                                            <strong class="text-foreground">Puntos:</strong> 
                                            <span class="text-muted-foreground">{{ question.points }}</span>
                                        </div>
                                        <div class="text-sm">
                                            <strong class="text-foreground">Dependencias:</strong>
                                            <span :class="['inline-flex px-2 py-1 text-xs font-medium rounded-full ml-1', getDependencyInfoOptimized(question).class]">
                                                <Users v-if="question.has_answers" class="mr-1 h-3 w-3" />
                                                {{ getDependencyInfoOptimized(question).text }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Categoría (solo desktop) -->
                                <div class="hidden md:block">
                                    <span class="inline-flex px-2 py-1 text-xs font-medium bg-muted text-muted-foreground rounded-full">
                                        {{ question.category.name }}
                                    </span>
                                </div>
                                
                                <!-- Tipo (solo desktop) -->
                                <div class="hidden md:block">
                                    <span class="text-sm text-foreground">{{ getTypeLabel(question.question_type) }}</span>
                                </div>
                                
                                <!-- Puntos (solo desktop) -->
                                <div class="hidden md:block">
                                    <span class="font-medium text-foreground">{{ question.points }}</span>
                                </div>
                                
                                <!-- Dependencias (solo desktop) -->
                                <div class="hidden md:block">
                                    <span :class="['inline-flex items-center px-2 py-1 text-xs font-medium rounded-full', getDependencyInfoOptimized(question).class]">
                                        <Users v-if="question.has_answers" class="mr-1 h-3 w-3" />
                                        {{ getDependencyInfoOptimized(question).text }}
                                    </span>
                                </div>
                                
                                <!-- Acciones (solo desktop) -->
                                <div class="hidden md:block">
                                    <div class="flex items-center justify-end gap-2">
                                        <Button 
                                            @click="openEditInNewTab(question.id)"
                                            variant="ghost" 
                                            size="sm" 
                                            class="h-8 w-8 p-0"
                                        >
                                            <Edit class="h-4 w-4" />
                                        </Button>
                                        <button 
                                            @click="confirmDelete(question)" 
                                            :disabled="!getDependencyInfoOptimized(question).canDelete"
                                            :class="[
                                                'inline-flex items-center justify-center px-3 py-1.5 text-sm font-medium rounded-md transition-colors border border-input',
                                                !getDependencyInfoOptimized(question).canDelete
                                                    ? 'opacity-50 cursor-not-allowed text-muted-foreground'
                                                    : 'text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20'
                                            ]"
                                        >
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Acciones móvil -->
                                <div class="md:hidden mt-3 flex gap-2 flex-wrap">
                                    <Button 
                                        @click="openEditInNewTab(question.id)"
                                        variant="outline" 
                                        size="sm"
                                    >
                                        <Edit class="h-4 w-4 mr-1" />
                                        Editar
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div v-else class="p-8 text-center">
                    <p class="text-muted-foreground">No se encontraron preguntas</p>
                </div>
            </div>
        </div>

        <!-- Modal de confirmación de eliminación -->
        <Transition
            enter-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div 
                v-if="showDeleteModal" 
                class="fixed inset-0 bg-black/50 overflow-y-auto h-full w-full z-50 flex items-center justify-center p-4"
                @click="closeDeleteModal"
            >
                <Transition
                    enter-active-class="transition-all duration-300"
                    enter-from-class="opacity-0 scale-95 translate-y-4"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition-all duration-300"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-4"
                >
                    <div 
                        v-if="showDeleteModal"
                        class="bg-card border border-border rounded-lg shadow-lg w-full max-w-md"
                        @click.stop
                    >
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-foreground mb-2">Confirmar eliminación</h3>
                            <p class="text-muted-foreground mb-4">
                                ¿Estás seguro de que deseas eliminar la pregunta "{{ questionToDelete?.question_text }}"?
                                Esta acción no se puede deshacer.
                            </p>
                            <div class="flex justify-end gap-3">
                                <Button @click="closeDeleteModal" variant="outline">
                                    Cancelar
                                </Button>
                                <Button @click="deleteQuestion" variant="destructive">
                                    Eliminar
                                </Button>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </AppLayout>
</template>