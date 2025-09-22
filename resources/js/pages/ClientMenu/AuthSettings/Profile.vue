<script setup lang="ts">
import { send } from '@/routes/verification';
import { useForm, Head, Link, usePage } from '@inertiajs/vue3';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import TopBar from '@/components/MyComponents/TopBar.vue';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const page = usePage();
const user = page.props.auth.user;

// Crear el formulario usando useForm de Inertia
const form = useForm({
    name: user.name,
    email: user.email,
});

const submit = () => {
    form.patch('/auth-settings/profile', {
        preserveScroll: true,
        onSuccess: () => {
            // Mantener en la misma página después de guardar
        },
    });
};
</script>

<template>
    <Head title="Configuración de Perfil" />
    
    <!-- TopBar en lugar del AppLayout -->
    <TopBar />
    
    <!-- Contenido principal sin sidebar -->
    <div class="min-h-screen bg-background max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="container mx-auto px-4 py-8">
            <!-- Encabezado -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    Configuración de Perfil
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">
                    Administra tu información personal y configuraciones de cuenta
                </p>
            </div>

            <!-- Contenido principal -->
            <div class="max-w-2xl">
                <div class="flex flex-col space-y-6">
                    <HeadingSmall title="Información del Perfil" description="Actualiza tu nombre y dirección de email" />

                    <form @submit.prevent="submit" class="space-y-6">
                        <div class="grid gap-2">
                            <Label for="name">Nombre</Label>
                            <Input
                                id="name"
                                v-model="form.name"
                                class="mt-1 block w-full"
                                required
                                autocomplete="name"
                                placeholder="Nombre completo"
                            />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="email">Dirección de Email</Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="mt-1 block w-full"
                                required
                                autocomplete="username"
                                placeholder="Dirección de email"
                            />
                            <InputError class="mt-2" :message="form.errors.email" />
                        </div>

                        <div v-if="mustVerifyEmail && !user.email_verified_at">
                            <p class="-mt-4 text-sm text-muted-foreground">
                                Tu dirección de email no está verificada.
                                <Link
                                    :href="send()"
                                    as="button"
                                    class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                                >
                                    Haz clic aquí para reenviar el email de verificación.
                                </Link>
                            </p>

                            <div v-if="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                                Un nuevo enlace de verificación ha sido enviado a tu dirección de email.
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <Button type="submit" :disabled="form.processing">Guardar</Button>

                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p v-show="form.recentlySuccessful" class="text-sm text-neutral-600">Guardado.</p>
                            </Transition>
                        </div>
                    </form>
                </div>

                <div class="mt-12">
                    <DeleteUser />
                </div>
            </div>
        </div>
    </div>
</template>