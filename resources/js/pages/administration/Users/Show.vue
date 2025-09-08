<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { ArrowLeft, Calendar, User, Mail, Shield, Crown, Trash2, Edit, AlertTriangle, X } from 'lucide-vue-next';
import users from '@/routes/users';
import { route } from 'ziggy-js';

// Definir la interfaz User
interface User {
  id: number;
  name: string;
  email: string;
  ispremium: boolean;
  email_verified_at?: string;
  created_at: string;
  updated_at: string;
  roles?: Array<{
    id: number;
    name: string;
    guard_name: string;
  }>;
}

interface Props {
  user: User;
}

const props = defineProps<Props>();
const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Usuarios',
        href: users.index().url,
    },
    {
        title: 'Detalles del Usuario',
        href: users.show(props.user.id).url,
    },
];

const editUser = () => {
    router.visit(users.edit(props.user.id).url);
};

// Funciones de utilidad
const getRoleLabel = (roles: User['roles']) => {
  if (!roles || roles.length === 0) return 'Sin rol asignado';
  return roles.map(role => role.name).join(', ');
};

const getRoleColor = (roles: User['roles']) => {
  if (!roles || roles.length === 0) return 'bg-gray-500 text-white';
  
  // Colores basados en el tipo de rol
  const roleColors = {
    'admin': 'bg-red-500 text-white',
    'editor': 'bg-blue-500 text-white',
    'user': 'bg-green-500 text-white',
    'moderator': 'bg-purple-500 text-white'
  };
  
  const primaryRole = roles[0].name.toLowerCase();
  return roleColors[primaryRole as keyof typeof roleColors] || 'bg-gray-500 text-white';
};

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('es-ES', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};

const getVerificationStatus = (emailVerifiedAt?: string) => {
  return emailVerifiedAt ? 'Verificado' : 'No verificado';
};

const getVerificationColor = (emailVerifiedAt?: string) => {
  return emailVerifiedAt ? 'bg-green-500 text-white' : 'bg-yellow-500 text-white';
};

// Estado para el modal
const showDeleteModal = ref(false);
const isDeleting = ref(false);

// Verificar si el usuario a eliminar es el usuario actual
const isCurrentUser = computed(() => {
  return page.props.auth?.user?.id === props.user.id;
});

// Funciones para las acciones
const confirmDelete = () => {
    showDeleteModal.value = true;
};

const closeModal = () => {
  showDeleteModal.value = false;
};

const handleDeleteConfirm = async () => {
    if (isCurrentUser.value) {
        return; // No permitir eliminación del usuario actual
    }
    
    isDeleting.value = true;
    try {
        router.delete(`/users/${props.user.id}`, {
            onSuccess: () => {
                console.log('Usuario eliminado exitosamente');
                showDeleteModal.value = false;
            },
            onError: (errors) => {
                console.error('Error al eliminar el usuario:', errors);
                alert('Error al eliminar el usuario. Inténtalo de nuevo.');
            },
            onFinish: () => {
                isDeleting.value = false;
            }
        });
    } catch (error) {
        isDeleting.value = false;
    }
};

// Manejar tecla Escape
const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Escape' && showDeleteModal.value) {
    closeModal();
  }
};

onMounted(() => {
  document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <Head :title="user.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <!-- Header con botón de regreso -->
            <!-- <div class="flex items-center gap-4">
                <Link 
                    :href="usersIndex().url"
                    class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-muted-foreground hover:text-foreground transition-colors rounded-md hover:bg-muted"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Volver a usuarios
                </Link>
            </div> -->

            <!-- Contenido principal -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Información del usuario -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Título y metadatos -->
                    <div class="bg-card rounded-lg p-6 shadow-sm border border-border">
                        <div class="flex items-start justify-between mb-4">
                            <h1 class="text-3xl font-bold text-foreground leading-tight">{{ user.name }}</h1>
                            <div class="flex gap-2">
                                <span :class="getRoleColor(user.roles) + ' inline-flex items-center px-3 py-1 rounded-full text-sm font-medium'">
                                    {{ getRoleLabel(user.roles) }}
                                </span>
                                <span v-if="user.ispremium" class="bg-gradient-to-r from-pink-500 to-purple-500 text-white inline-flex items-center px-3 py-1 rounded-full text-sm font-medium">
                                    <Crown class="h-4 w-4 mr-1" />
                                    Premium
                                </span>
                            </div>
                        </div>
                        
                        <!-- Metadatos -->
                        <div class="flex flex-wrap gap-4 text-sm text-muted-foreground">
                            <div class="flex items-center gap-2">
                                <Mail class="h-4 w-4" />
                                <span>{{ user.email }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <Calendar class="h-4 w-4" />
                                <span>Registrado {{ formatDate(user.created_at) }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span :class="getVerificationColor(user.email_verified_at) + ' inline-flex items-center px-2 py-1 rounded-full text-xs font-medium'">
                                    {{ getVerificationStatus(user.email_verified_at) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Avatar placeholder -->
                    <div class="bg-card rounded-lg overflow-hidden shadow-sm border border-border">
                        <div class="w-full h-64 flex items-center justify-center bg-gray-50 dark:bg-gray-900 border-2 border-dashed border-gray-300 dark:border-gray-600">
                            <div class="text-center text-gray-400 dark:text-gray-500">
                                <User class="h-16 w-16 mx-auto mb-3 opacity-40" />
                                <p class="text-base font-medium mb-1">{{ user.name }}</p>
                                <p class="text-sm opacity-75">Avatar del usuario</p>
                            </div>
                        </div>
                    </div>

                    <!-- Información detallada -->
                    <div class="bg-card rounded-lg p-6 shadow-sm border border-border">
                        <div class="flex items-center gap-2 mb-4">
                            <User class="h-5 w-5 text-muted-foreground" />
                            <h2 class="text-xl font-semibold text-foreground">Información del Usuario</h2>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Nombre completo</label>
                                <p class="text-foreground font-medium">{{ user.name }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Correo electrónico</label>
                                <p class="text-foreground font-medium">{{ user.email }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Estado de verificación</label>
                                <p class="text-foreground font-medium">{{ getVerificationStatus(user.email_verified_at) }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Tipo de cuenta</label>
                                <p class="text-foreground font-medium">{{ user.ispremium ? 'Premium' : 'Estándar' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar con información adicional -->
                <div class="space-y-6">
                    <!-- Información del usuario -->
                    <div class="bg-card rounded-lg p-6 shadow-sm border border-border">
                        <h3 class="text-lg font-semibold text-foreground mb-4">Información</h3>
                        <div class="space-y-3">
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">ID</label>
                                <p class="text-foreground">#{{ user.id }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Rol</label>
                                <p class="text-foreground">{{ getRoleLabel(user.roles) }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Estado Premium</label>
                                <p class="text-foreground">{{ user.ispremium ? 'Activo' : 'Inactivo' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Email verificado</label>
                                <p class="text-foreground">{{ user.email_verified_at ? formatDate(user.email_verified_at) : 'No verificado' }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Creado</label>
                                <p class="text-foreground">{{ formatDate(user.created_at) }}</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-muted-foreground">Actualizado</label>
                                <p class="text-foreground">{{ formatDate(user.updated_at) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Información de roles -->
                    <div class="bg-card rounded-lg p-6 shadow-sm border border-border">
                        <h3 class="text-lg font-semibold text-foreground mb-4">Roles y Permisos</h3>
                        <div class="space-y-3">
                            <div v-if="user.roles && user.roles.length > 0">
                                <label class="text-sm font-medium text-muted-foreground">Roles asignados</label>
                                <div class="flex flex-wrap gap-2 mt-2">
                                    <span 
                                        v-for="role in user.roles" 
                                        :key="role.id"
                                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary text-primary-foreground"
                                    >
                                        <Shield class="h-3 w-3 mr-1" />
                                        {{ role.name }}
                                    </span>
                                </div>
                            </div>
                            <div v-else>
                                <label class="text-sm font-medium text-muted-foreground">Roles</label>
                                <p class="text-muted-foreground text-sm">Sin roles asignados</p>
                            </div>
                            <div v-if="user.ispremium" class="mt-4 p-3 bg-gradient-to-r from-pink-50 to-purple-50 dark:from-pink-900/20 dark:to-purple-900/20 rounded-lg border border-pink-200 dark:border-pink-800">
                                <div class="flex items-center gap-2 text-pink-700 dark:text-pink-300">
                                    <Crown class="h-4 w-4" />
                                    <span class="font-medium">Usuario Premium</span>
                                </div>
                                <p class="text-sm text-pink-600 dark:text-pink-400 mt-1">
                                    Este usuario tiene acceso a contenido premium y funcionalidades exclusivas.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="bg-card rounded-lg p-6 shadow-sm border border-border">
                        <h3 class="text-lg font-semibold text-foreground mb-4">Acciones</h3>
                        <div class="space-y-2">
                            <button 
                                @click="editUser"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium bg-primary text-primary-foreground rounded-md hover:bg-primary/90 transition-colors"
                            >
                                <Edit class="h-4 w-4" />
                                Editar usuario
                            </button>
                            <button  
                                @click="confirmDelete"
                                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 text-sm font-medium bg-destructive text-destructive-foreground rounded-md hover:bg-destructive/90 transition-colors"
                            >
                                <Trash2 class="h-4 w-4" />
                                Eliminar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Modal de confirmación de eliminación -->
        <Teleport to="body">
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
                    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50 backdrop-blur-sm"
                    @click="closeModal"
                >
                    <!-- Contenido del modal -->
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
                            class="relative w-full max-w-md mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden"
                            @click.stop
                        >
                            <!-- Header -->
                            <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                                <div class="flex items-center gap-3">
                                    <div class="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center" :class="isCurrentUser ? 'bg-yellow-100 dark:bg-yellow-900' : 'bg-red-100 dark:bg-red-900'">
                                        <AlertTriangle v-if="isCurrentUser" class="h-6 w-6 text-yellow-600 dark:text-yellow-400" />
                                        <Trash2 v-else class="h-6 w-6 text-red-600 dark:text-red-400" />
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                        {{ isCurrentUser ? 'Acción no permitida' : 'Confirmar eliminación' }}
                                    </h3>
                                </div>
                                <button 
                                    @click="closeModal"
                                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                                >
                                    <X class="h-5 w-5" />
                                </button>
                            </div>

                            <!-- Contenido -->
                            <div class="p-6">
                                <p class="text-gray-600 dark:text-gray-300 mb-4">
                                    <span v-if="isCurrentUser">
                                        No puedes eliminar tu propia cuenta desde esta sección.
                                    </span>
                                    <span v-else>
                                        ¿Estás seguro de que quieres eliminar este usuario?
                                    </span>
                                </p>
                                
                                <div class="rounded-md p-4 border-l-4 mb-6" :class="isCurrentUser ? 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-400' : 'bg-red-50 dark:bg-red-900/20 border-red-400'">
                                    <p class="font-medium text-gray-900 dark:text-white mb-2">
                                        "{{ user.name }}" ({{ user.email }})
                                    </p>
                                    <p class="text-sm" :class="isCurrentUser ? 'text-yellow-700 dark:text-yellow-300' : 'text-red-700 dark:text-red-300'">
                                        <strong v-if="isCurrentUser">Información:</strong>
                                        <strong v-else>Advertencia:</strong>
                                        <span v-if="isCurrentUser">
                                            Esta es tu cuenta actual. Para eliminar tu cuenta, ve a la sección de configuración de perfil.
                                        </span>
                                        <span v-else>
                                            Esta acción no se puede deshacer. Todos los datos asociados a este usuario se perderán definitivamente.
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <!-- Footer -->
                            <div class="flex justify-end gap-3 p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-750">
                                <button 
                                    @click="closeModal"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                                >
                                    {{ isCurrentUser ? 'Entendido' : 'Cancelar' }}
                                </button>
                                <button 
                                    v-if="!isCurrentUser"
                                    @click="handleDeleteConfirm"
                                    :disabled="isDeleting"
                                    class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 disabled:bg-red-400 rounded-md transition-colors"
                                >
                                    <Trash2 class="h-4 w-4" />
                                    <span v-if="isDeleting">Eliminando...</span>
                                    <span v-else>Eliminar usuario</span>
                                </button>
                            </div>
                        </div>
                    </Transition>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>