<script setup lang="ts">
import { dashboard, login, register, inicio } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import TopBar from '@/components/Mycomponents/TopBar.vue';


</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <!-- MENU PRINCIPAL PARA CLIENTES -->
            <TopBar />

    <div class="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]">
        <header class="mb-6 w-full text-sm not-has-[nav]:hidden lg:max-w-4xl">
        </header>
        <!-- Todo: BODY -->
        


        <div class="flex w-full items-center justify-center opacity-100 transition-opacity duration-750 lg:grow starting:opacity-0">
            <main class="flex w-full max-w-[335px] flex-col-reverse overflow-hidden rounded-lg lg:max-w-4xl lg:flex-row">
                <div class="flex w-full flex-col items-center justify-center p-6 text-center lg:py-12">
                    <h1 class="mb-1 font-medium dark:text-[#EDEDEC] " >Bienvenido</h1>
                    <p class="mb-6 text-sm text-[#666666] dark:text-[#A6A6A6]">
                        Explora nuestras publicaciones y precios.
                    </p>
                </div>
            </main>
        </div>
        <div class="hidden h-14.5 lg:block"></div>
    </div>
</template>