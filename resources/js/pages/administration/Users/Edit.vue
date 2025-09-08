<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Save, User, Mail, Lock, Crown, Shield } from 'lucide-vue-next';
import { useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { type BreadcrumbItem } from '@/types';
import users from '@/routes/users';

interface UserForm {
    name: string;
    email: string;
    password?: string;
    password_confirmation?: string;
    ispremium: boolean;
    role: string;
}

interface User {
    id: number;
    name: string;
    email: string;
    ispremium: boolean;
    roles: Array<{
        id: number;
        name: string;
    }>;
}

interface Props {
    user: User;
    availableRoles?: Array<{
        id: number;
        name: string;
    }>;
}

const props = withDefaults(defineProps<Props>(), {
    availableRoles: () => []
});

const form = useForm<UserForm>({
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
    ispremium: props.user.ispremium,
    role: props.user.roles[0]?.name || ''
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Usuarios', href: users.index().url },
    { title: 'Editar Usuario', current: true },
];

// Funciones de formulario
const submitForm = () => {
    form.put(users.update(props.user.id).url, {
        onSuccess: () => {
            router.visit(users.index().url);
        }
    });
};

const getRoleDescription = (roleName: string) => {
    const descriptions: Record<string, string> = {
        'admin': 'Acceso completo al sistema',
        'cliente': 'Acceso limitado como cliente'
    };
    return descriptions[roleName] || 'Rol personalizado';
};
</script>

<template>
    <Head title="Editar Usuario" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
            <!-- Header -->
            <div class="">
                <div class="bg-card rounded-lg p-6 shadow-sm border border-border">
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4 mb-4">
                        <div class="flex-1">
                            <h1 class="text-xl sm:text-2xl font-bold mb-2 text-foreground">Editar Usuario</h1>
                            <p class="text-muted-foreground text-sm sm:text-base">Modifique la información del usuario {{ props.user.name }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Form -->
                <div class="lg:col-span-2">
                    <div class="rounded-lg border bg-card p-6 shadow-sm">
                        <h2 class="mb-6 text-lg font-semibold text-card-foreground">Información del Usuario</h2>
                        
                        <form @submit.prevent="submitForm" class="flex flex-col gap-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Nombre -->
                                <div class="grid gap-2">
                                    <label for="name" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                        <User class="inline h-4 w-4 mr-1" />
                                        Nombre Completo
                                    </label>
                                    <input
                                        id="name"
                                        v-model="form.name"
                                        type="text"
                                        required
                                        placeholder="Ingrese el nombre completo"
                                        class="file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input flex h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive"
                                        :class="{ 'aria-invalid': form.errors.name }"
                                    />
                                    <InputError :message="form.errors.name" />
                                </div>

                                <!-- Email -->
                                <div class="grid gap-2">
                                    <label for="email" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                        <Mail class="inline h-4 w-4 mr-1" />
                                        Correo Electrónico
                                    </label>
                                    <input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        required
                                        placeholder="usuario@ejemplo.com"
                                        class="file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input flex h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive"
                                        :class="{ 'aria-invalid': form.errors.email }"
                                    />
                                    <InputError :message="form.errors.email" />
                                </div>

                                <!-- Contraseña -->
                                <div class="grid gap-2">
                                    <label for="password" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                        <Lock class="inline h-4 w-4 mr-1" />
                                        Nueva Contraseña (opcional)
                                    </label>
                                    <input
                                        id="password"
                                        v-model="form.password"
                                        type="password"
                                        placeholder="Dejar vacío para mantener actual"
                                        class="file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input flex h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive"
                                        :class="{ 'aria-invalid': form.errors.password }"
                                    />
                                    <InputError :message="form.errors.password" />
                                </div>

                                <!-- Confirmar Contraseña -->
                                <div class="grid gap-2">
                                    <label for="password_confirmation" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                        <Lock class="inline h-4 w-4 mr-1" />
                                        Confirmar Nueva Contraseña
                                    </label>
                                    <input
                                        id="password_confirmation"
                                        v-model="form.password_confirmation"
                                        type="password"
                                        placeholder="Confirme la nueva contraseña"
                                        class="file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input flex h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive"
                                        :class="{ 'aria-invalid': form.errors.password_confirmation }"
                                    />
                                    <InputError :message="form.errors.password_confirmation" />
                                </div>
                            </div>

                            <!-- Rol -->
                            <div class="grid gap-2">
                                <label for="role" class="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                    <Shield class="inline h-4 w-4 mr-1" />
                                    Rol del Usuario
                                </label>
                                <select
                                    id="role"
                                    v-model="form.role"
                                    required
                                    class="file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground dark:bg-input/30 border-input flex h-9 w-full min-w-0 rounded-md border bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 md:text-sm focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive"
                                    :class="{ 'aria-invalid': form.errors.role }"
                                >
                                    <option value="" disabled>Seleccione un rol</option>
                                    <option 
                                        v-for="role in props.availableRoles" 
                                        :key="role.id" 
                                        :value="role.name"
                                    >
                                        {{ role.name.charAt(0).toUpperCase() + role.name.slice(1) }}
                                    </option>
                                </select>
                                <p v-if="form.role" class="text-xs text-muted-foreground">
                                    {{ getRoleDescription(form.role) }}
                                </p>
                                <InputError :message="form.errors.role" />
                            </div>

                            <!-- Premium -->
                            <div class="grid gap-2">
                                <div class="flex items-center space-x-3">
                                    <input
                                        id="ispremium"
                                        v-model="form.ispremium"
                                        type="checkbox"
                                        class="peer h-4 w-4 shrink-0 rounded-sm border border-primary shadow focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground"
                                    />
                                    <label for="ispremium" class="flex items-center text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                                        <Crown class="h-4 w-4 mr-1 text-yellow-500" />
                                        Usuario Premium
                                    </label>
                                </div>
                                <p class="text-xs text-muted-foreground ml-7">
                                    Los usuarios premium tienen acceso a funcionalidades adicionales
                                </p>
                                <InputError :message="form.errors.ispremium" />
                            </div>

                            <!-- Botones -->
                            <div class="flex flex-col sm:flex-row gap-3 pt-6 border-t border-border">
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 transition-colors"
                                >
                                    <Save class="h-4 w-4 mr-2" />
                                    {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                                </button>
                                
                                <button
                                    type="button"
                                    @click="router.visit('/users')"
                                    class="inline-flex items-center justify-center px-4 py-2 border border-input shadow-sm text-sm font-medium rounded-md text-foreground bg-background hover:bg-accent hover:text-accent-foreground focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ring transition-colors"
                                >
                                    Cancelar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <div class="rounded-lg border bg-card p-6 shadow-sm">
                        <h3 class="font-medium text-card-foreground mb-4 flex items-center gap-2">
                            <Shield class="h-5 w-5 text-blue-600" />
                            Información sobre Roles
                        </h3>
                        <div class="space-y-4">
                            <div class="p-3 rounded-md bg-muted/50">
                                <h4 class="font-medium text-sm text-foreground mb-1">Admin</h4>
                                <p class="text-xs text-muted-foreground">Acceso completo al sistema de administración</p>
                            </div>
                            <div class="p-3 rounded-md bg-muted/50">
                                <h4 class="font-medium text-sm text-foreground mb-1">Cliente</h4>
                                <p class="text-xs text-muted-foreground">Acceso limitado a funcionalidades de cliente</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border bg-card p-6 shadow-sm mt-6">
                        <h3 class="font-medium text-card-foreground mb-4 flex items-center gap-2">
                            <Crown class="h-5 w-5 text-yellow-500" />
                            Usuario Premium
                        </h3>
                        <div class="space-y-2">
                            <p class="text-xs text-muted-foreground">
                                Los usuarios premium tienen acceso a:
                            </p>
                            <ul class="text-xs text-muted-foreground space-y-1 ml-3">
                                <li>• Contenido exclusivo</li>
                                <li>• Funcionalidades avanzadas</li>
                                <li>• Soporte prioritario</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>