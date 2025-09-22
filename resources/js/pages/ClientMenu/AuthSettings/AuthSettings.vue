<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import TopBar from '@/components/MyComponents/TopBar.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

const page = usePage();
const currentPath = computed(() => page.url);

const settingsItems = [
    {
        title: 'Perfil',
        description: 'Administra tu información personal',
        href: '/auth-settings/profile',
        icon: '👤'
    },
    {
        title: 'Contraseña',
        description: 'Actualiza tu contraseña de acceso',
        href: '/auth-settings/password',
        icon: '🔒'
    },
    {
        title: 'Apariencia',
        description: 'Personaliza el tema de la aplicación',
        href: '/auth-settings/appearance',
        icon: '🎨'
    }
];

const isActiveRoute = (route: string) => {
    return currentPath.value === route || currentPath.value.startsWith(route);
};
</script>

<template>
    <!-- TopBar -->
    <TopBar />
    
    <!-- Contenido principal -->
    <div class="min-h-screen bg-background max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="container mx-auto px-4 py-8">
            <!-- Encabezado -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    Configuraciones
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">
                    Administra tu perfil y configuraciones de cuenta
                </p>
            </div>

            <!-- Grid de opciones de configuración -->
            <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                <Card 
                    v-for="item in settingsItems" 
                    :key="item.href"
                    :class="[
                        'cursor-pointer transition-all duration-200 hover:shadow-lg',
                        isActiveRoute(item.href) ? 'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-800'
                    ]"
                >
                    <Link :href="item.href" class="block">
                        <CardHeader class="pb-3">
                            <div class="flex items-center space-x-3">
                                <div class="text-2xl">{{ item.icon }}</div>
                                <div>
                                    <CardTitle class="text-lg">{{ item.title }}</CardTitle>
                                    <CardDescription class="text-sm">
                                        {{ item.description }}
                                    </CardDescription>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent class="pt-0">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-muted-foreground">
                                    Configurar
                                </span>
                                <svg class="w-4 h-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </div>
                        </CardContent>
                    </Link>
                </Card>
            </div>

            <!-- Información adicional -->
            <div class="mt-12 p-6 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                    ¿Necesitas ayuda?
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-4">
                    Si tienes problemas con tu cuenta o necesitas asistencia, no dudes en contactarnos.
                </p>
                <Button variant="outline">
                    Contactar Soporte
                </Button>
            </div>
        </div>
    </div>
</template>