<script setup lang="ts">
import PasswordController from '@/actions/App/Http/Controllers/Settings/PasswordController';
import InputError from '@/components/InputError.vue';
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import TopBar from '@/components/MyComponents/TopBar.vue';

const passwordInput = ref<HTMLInputElement | null>(null);
const currentPasswordInput = ref<HTMLInputElement | null>(null);
</script>

<template>
    <Head title="Configuración de Contraseña" />
    
    <!-- TopBar en lugar del AppLayout -->
    <TopBar />
    
    <!-- Contenido principal sin sidebar -->
    <div class="min-h-screen bg-background max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="container mx-auto px-4 py-8">
            <!-- Encabezado -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    Configuración de Contraseña
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">
                    Actualiza tu contraseña para mantener tu cuenta segura
                </p>
            </div>

            <!-- Contenido principal -->
            <div class="max-w-2xl">
                <div class="space-y-6">
                    <HeadingSmall title="Actualizar Contraseña" description="Asegúrate de usar una contraseña larga y aleatoria para mantener tu cuenta segura" />

                    <Form
                        v-bind="PasswordController.update.form()"
                        :options="{
                            preserveScroll: true,
                        }"
                        reset-on-success
                        :reset-on-error="['password', 'password_confirmation', 'current_password']"
                        class="space-y-6"
                        v-slot="{ errors, processing, recentlySuccessful }"
                    >
                        <div class="grid gap-2">
                            <Label for="current_password">Contraseña Actual</Label>
                            <Input
                                id="current_password"
                                ref="currentPasswordInput"
                                name="current_password"
                                type="password"
                                class="mt-1 block w-full"
                                autocomplete="current-password"
                                placeholder="Contraseña actual"
                            />
                            <InputError :message="errors.current_password" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password">Nueva Contraseña</Label>
                            <Input
                                id="password"
                                ref="passwordInput"
                                name="password"
                                type="password"
                                class="mt-1 block w-full"
                                autocomplete="new-password"
                                placeholder="Nueva contraseña"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password_confirmation">Confirmar Contraseña</Label>
                            <Input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                class="mt-1 block w-full"
                                autocomplete="new-password"
                                placeholder="Confirmar contraseña"
                            />
                            <InputError :message="errors.password_confirmation" />
                        </div>

                        <div class="flex items-center gap-4">
                            <Button :disabled="processing">Guardar Contraseña</Button>

                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p v-show="recentlySuccessful" class="text-sm text-neutral-600">Guardado.</p>
                            </Transition>
                        </div>
                    </Form>
                </div>
            </div>
        </div>
    </div>
</template>