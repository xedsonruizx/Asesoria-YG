<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import TopBar from '@/components/MyComponents/TopBar.vue';
</script>

<template>
    <Head title="Configuración de Apariencia" />
    
    <!-- TopBar en lugar del AppLayout -->
    <TopBar />
    
    <!-- Contenido principal sin sidebar -->
    <div class="min-h-screen bg-background max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="container mx-auto px-4 py-8">
            <!-- Encabezado -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                    Configuración de Apariencia
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-2">
                    Personaliza la apariencia de la aplicación según tus preferencias
                </p>
            </div>

            <!-- Contenido principal -->
            <div class="max-w-2xl">
                <div class="space-y-6">
                    <HeadingSmall title="Configuración de Apariencia" description="Actualiza la configuración de apariencia de tu cuenta" />
                    <AppearanceTabs />
                </div>
            </div>
        </div>
    </div>
</template>