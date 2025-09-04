<script setup lang="ts">
import { dashboard, login, register, home } from '@/routes';
import { Head, Link } from '@inertiajs/vue3';
import TopBar from '@/components/MyComponents/TopBar.vue';

</script>

<template>
    <Head title="Welcome">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>

    <!-- MENU PRINCIPAL PARA CLIENTES -->
    <div class="flex min-h-screen flex-col items-center bg-[#FDFDFC] p-6 text-[#1b1b18] lg:justify-center lg:p-8 dark:bg-[#0a0a0a]">
        <header class="mb-6 w-full max-w-[335px] text-sm not-has-[nav]:hidden lg:max-w-4xl">
            <TopBar />





            <!-- <nav class="flex items-center justify-end gap-4">
                <button @click="toggleMenu" class="md:hidden text-white focus:outline-none">
                    <svg v-if="!isOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                    <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>

                <div :class="{'hidden': !isOpen, 'md:flex': true}" class="md:items-center md:w-auto w-full">

                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="home()"
                            class="block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                        >
                            Inicio
                        </Link>
                        <Link
                            :href="login()"
                            class="block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                        >
                            Publicaciones
                        </Link>
                        <Link
                            :href="login()"
                            class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                        >
                            Precios
                        </Link>
                        <Link
                            :href="login()"
                            class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="register()"
                            class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]"
                        >
                            Register
                        </Link>
                    </template>
                </div>
            </nav> -->

            <!-- <nav class="bg-gray-800 p-4">
                <div class="container mx-auto flex justify-between items-center">
                
                <router-link to="/" class="text-white text-lg font-bold">MyBrand</router-link>

                
                <button @click="toggleMenu" class="md:hidden text-white focus:outline-none">
                    <svg v-if="!isOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                    <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>

                
                <div :class="{'hidden': !isOpen, 'md:flex': true}" class="md:items-center md:w-auto w-full">
                    <ul class="md:flex md:space-x-4 mt-4 md:mt-0">
                    <li><router-link to="/" class="block text-white hover:text-gray-300 py-2 md:py-0">Home</router-link></li>
                    <li><router-link to="/about" class="block text-white hover:text-gray-300 py-2 md:py-0">About</router-link></li>
                    <li><router-link to="/contact" class="block text-white hover:text-gray-300 py-2 md:py-0">Contact</router-link></li>
                    </ul>
                </div>
                </div>
            </nav> -->
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