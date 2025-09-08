
<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Plus, Edit, Trash2, Users, ChevronLeft, ChevronRight, Crown } from 'lucide-vue-next';
import users from '@/routes/users';
import { type BreadcrumbItem } from '@/types';

// Props del backend
interface User {
  id: number;
  name: string;
  email: string;
  ispremium: boolean;
  created_at: string;
  updated_at: string;
}

interface UsersData {
  data: User[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
}

const props = withDefaults(defineProps<{
  users: UsersData;
}>(), {
  users: () => ({ data: [], current_page: 1, last_page: 1, per_page: 10, total: 0, from: 0, to: 0 })
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Usuarios',
        href: users.index().url,
    },
];

// Funciones
const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
};

const createUser = () => {
  router.visit(users.create().url);
};

const editUser = (id: number) => {
  router.visit(users.edit(id).url);
};

const viewUser = (user: User) => {
  router.visit(users.show(user.id).url);
};

const deleteUser = (id: number) => {
  if (confirm('¿Estás seguro de que quieres eliminar este usuario?')) {
    router.delete(users.destroy(id).url);
  }
};

// Funciones de paginación
const goToPage = (page: number) => {
  if (page >= 1 && page <= (props.users?.last_page || 1)) {
    router.visit(users.index().url, {
      data: { page },
      preserveState: true,
      preserveScroll: true,
    });
  }
};

const goToPreviousPage = () => {
  const currentPage = props.users?.current_page || 1;
  if (currentPage > 1) {
    goToPage(currentPage - 1);
  }
};

const goToNextPage = () => {
  const currentPage = props.users?.current_page || 1;
  const lastPage = props.users?.last_page || 1;
  if (currentPage < lastPage) {
    goToPage(currentPage + 1);
  }
};

// Generar números de página para mostrar
const getPageNumbers = () => {
  const currentPage = props.users?.current_page || 1;
  const lastPage = props.users?.last_page || 1;
  const pages: number[] = [];
  
  // Mostrar máximo 5 páginas
  let startPage = Math.max(1, currentPage - 2);
  let endPage = Math.min(lastPage, startPage + 4);
  
  // Ajustar si estamos cerca del final
  if (endPage - startPage < 4) {
    startPage = Math.max(1, endPage - 4);
  }
  
  for (let i = startPage; i <= endPage; i++) {
    pages.push(i);
  }
  
  return pages;
};

const truncateEmail = (email: string, maxLength: number = 30) => {
  return email.length > maxLength ? email.substring(0, maxLength) + '...' : email;
};
</script>

<template>
    <Head title="Usuarios" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <!-- Header con información de usuarios -->
            <div class="bg-card rounded-lg p-6 shadow-sm border border-border">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4 mb-4">
                    <div class="flex-1">
                        <h1 class="text-xl sm:text-2xl font-bold mb-2 text-foreground">Gestión de Usuarios</h1>
                        <p class="text-muted-foreground text-sm sm:text-base">Administra y visualiza todos los usuarios del sistema</p>
                    </div>
                    <button 
                        @click="createUser"
                        class="inline-flex items-center justify-center gap-2 px-3 sm:px-4 py-2 bg-primary text-primary-foreground hover:bg-primary/90 rounded-md transition-colors font-medium text-xs sm:text-sm w-full sm:w-auto"
                    >
                        <Plus class="h-4 w-4 flex-shrink-0" />
                        <span class="truncate">Crear Nuevo Usuario</span>
                    </button>
                </div>
                
                <!-- Estadísticas básicas -->
                <div class="flex gap-2 sm:gap-4 flex-wrap">
                    <div class="bg-muted px-3 sm:px-4 py-2 sm:py-3 rounded-md flex-1 sm:flex-none">
                        <span class="font-semibold text-foreground text-xs sm:text-sm">Total: {{ props.users?.total || 0 }}</span>
                    </div>
                    <div class="bg-green-50 dark:bg-green-900/20 px-3 sm:px-4 py-2 sm:py-3 rounded-md flex-1 sm:flex-none">
                        <span class="font-semibold text-green-700 dark:text-green-300 text-xs sm:text-sm">Página {{ props.users?.current_page || 1 }} de {{ props.users?.last_page || 1 }}</span>
                    </div>
                </div>
            </div>

            <!-- Información de paginación con controles -->
            <div v-if="props.users?.data && props.users.data.length > 0" class="bg-card rounded-lg p-4 shadow-sm border border-border">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <!-- Información de registros -->
                    <div class="text-muted-foreground text-sm">
                        <span>Mostrando {{ props.users.from }} a {{ props.users.to }} de {{ props.users.total }} usuarios</span>
                    </div>
                    
                    <!-- Controles de paginación -->
                    <div v-if="props.users.last_page > 1" class="flex items-center gap-2">
                        <!-- Botón anterior -->
                        <button 
                            @click="goToPreviousPage"
                            :disabled="props.users.current_page <= 1"
                            class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <ChevronLeft class="h-4 w-4" />
                            Anterior
                        </button>
                        
                        <!-- Números de página -->
                        <div class="flex items-center gap-1">
                            <!-- Primera página si no está visible -->
                            <template v-if="getPageNumbers()[0] > 1">
                                <button 
                                    @click="goToPage(1)"
                                    class="inline-flex items-center justify-center w-8 h-8 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors"
                                >
                                    1
                                </button>
                                <span v-if="getPageNumbers()[0] > 2" class="text-muted-foreground px-1">...</span>
                            </template>
                            
                            <!-- Páginas visibles -->
                            <button 
                                v-for="page in getPageNumbers()" 
                                :key="page"
                                @click="goToPage(page)"
                                :class="[
                                    'inline-flex items-center justify-center w-8 h-8 text-sm font-medium rounded-md transition-colors',
                                    page === props.users?.current_page 
                                        ? 'bg-primary text-primary-foreground' 
                                        : 'text-muted-foreground hover:text-foreground hover:bg-muted'
                                ]"
                            >
                                {{ page }}
                            </button>
                            
                            <!-- Última página si no está visible -->
                            <template v-if="getPageNumbers()[getPageNumbers().length - 1] < props.users.last_page">
                                <span v-if="getPageNumbers()[getPageNumbers().length - 1] < props.users.last_page - 1" class="text-muted-foreground px-1">...</span>
                                <button 
                                    @click="goToPage(props.users.last_page)"
                                    class="inline-flex items-center justify-center w-8 h-8 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors"
                                >
                                    {{ props.users.last_page }}
                                </button>
                            </template>
                        </div>
                        
                        <!-- Botón siguiente -->
                        <button 
                            @click="goToNextPage"
                            :disabled="props.users.current_page >= props.users.last_page"
                            class="inline-flex items-center gap-1 px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground hover:bg-muted rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Siguiente
                            <ChevronRight class="h-4 w-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Tabla de usuarios -->
            <div class="bg-card rounded-lg overflow-hidden shadow-sm border border-border">
                <div v-if="props.users?.data && props.users.data.length > 0">
                    <!-- Encabezados -->
                    <div class="bg-muted/30 p-4 border-b border-border">
                        <div class="grid grid-cols-1 md:grid-cols-6 gap-4 font-semibold text-foreground">
                            <div class="md:col-span-1">ID</div>
                            <div class="md:col-span-1">Nombre</div>
                            <div class="md:col-span-1">Email</div>
                            <div class="hidden md:block">Premium</div>
                            <div class="hidden md:block">Fecha de Registro</div>
                            <div class="hidden md:block">Acciones</div>
                        </div>
                    </div>
                    
                    <!-- Filas de datos -->
                    <div>
                        <div 
                            v-for="user in props.users.data" 
                            :key="user.id"
                            class="border-b border-border p-4 hover:bg-muted/50 transition-colors group"
                        >
                            <div class="grid grid-cols-1 md:grid-cols-6 gap-4 items-start md:items-center">
                                <!-- ID -->
                                <div class="md:col-span-1">
                                    <div class="font-semibold text-foreground">#{{ user.id }}</div>
                                </div>
                                
                                <!-- Nombre -->
                                <div class="md:col-span-1 cursor-pointer" @click="viewUser(user)">
                                    <div class="font-semibold text-foreground mb-1 group-hover:text-primary transition-colors flex items-center gap-2">
                                        {{ user.name }}
                                        <Crown v-if="user.ispremium" class="w-4 h-4 text-yellow-500" title="Usuario Premium" />
                                    </div>
                                    
                                    <!-- Información adicional en móvil -->
                                    <div class="md:hidden mt-2 space-y-1">
                                        <div class="text-sm text-muted-foreground">
                                            <strong>Email:</strong> {{ truncateEmail(user.email) }}
                                        </div>
                                        <div class="text-sm text-muted-foreground flex items-center gap-2">
                                            <strong>Premium:</strong> 
                                            <span :class="user.ispremium ? 'text-yellow-600 font-medium' : 'text-gray-500'">
                                                {{ user.ispremium ? 'Sí' : 'No' }}
                                            </span>
                                            <Crown v-if="user.ispremium" class="w-3 h-3 text-yellow-500" />
                                        </div>
                                        <div class="text-sm text-muted-foreground">
                                            <strong>Registrado:</strong> {{ formatDate(user.created_at) }}
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Email (solo desktop) -->
                                <div class="hidden md:block">
                                    <div class="text-sm text-foreground">{{ user.email }}</div>
                                </div>
                                
                                <!-- Premium Status (solo desktop) -->
                                <div class="hidden md:block">
                                    <div class="flex items-center gap-2">
                                        <span :class="[
                                            'inline-flex items-center px-2 py-1 rounded-full text-xs font-medium',
                                            user.ispremium 
                                                ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-300' 
                                                : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300'
                                        ]">
                                            <Crown v-if="user.ispremium" class="w-3 h-3 mr-1" />
                                            {{ user.ispremium ? 'Premium' : 'Estándar' }}
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Fecha (solo desktop) -->
                                <div class="hidden md:block text-sm text-muted-foreground">
                                    {{ formatDate(user.created_at) }}
                                </div>
                                
                                <!-- Acciones (solo desktop) -->
                                <div class="hidden md:flex md:gap-2">
                                    <Button @click="editUser(user.id)" variant="outline" size="sm">
                                        <Edit class="w-4 h-4" />
                                    </Button>
                                    <Button @click="deleteUser(user.id)" variant="destructive" size="sm">
                                        <Trash2 class="w-4 h-4" />
                                    </Button>
                                </div>
                                
                                <!-- Acciones en móvil -->
                                <div class="md:hidden col-span-full flex justify-between items-center mt-3 pt-3 border-t border-border">
                                    <button 
                                        @click="viewUser(user)"
                                        class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-primary hover:text-primary-foreground hover:bg-primary rounded-md transition-colors border border-primary/20 hover:border-primary"
                                    >
                                        Ver detalles
                                    </button>
                                    <div class="flex gap-2">
                                        <Button @click="editUser(user.id)" variant="outline" size="sm">
                                            <Edit class="h-4 w-4" />
                                            Editar
                                        </Button>
                                        <Button @click="deleteUser(user.id)" variant="destructive" size="sm">
                                            <Trash2 class="w-4 h-4" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Estado vacío -->
                <div v-else class="text-center py-12 px-4">
                    <div class="text-4xl mb-4">👥</div>
                    <h3 class="text-lg font-semibold text-foreground mb-2">No hay usuarios</h3>
                    <p class="text-muted-foreground">Aún no se han registrado usuarios en el sistema.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
