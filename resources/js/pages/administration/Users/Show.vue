<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { ArrowLeft, Calendar, User, Mail, Shield, Crown, Trash2, Edit } from 'lucide-vue-next';
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

// Funciones para las acciones
const confirmDelete = () => {
    showDeleteModal.value = true;
};

const handleDeleteConfirm = (userId: number) => {
    router.delete(`/users/${userId}`, {
        onSuccess: () => {
            console.log('Usuario eliminado exitosamente');
        },
        onError: (errors) => {
            console.error('Error al eliminar el usuario:', errors);
            alert('Error al eliminar el usuario. Inténtalo de nuevo.');
        }
    });
};
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
    </AppLayout>
</template>