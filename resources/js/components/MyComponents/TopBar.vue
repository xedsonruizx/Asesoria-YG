<script setup lang="ts">
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { dashboard, login, register, home } from '@/routes';

const isMenuOpen = ref(false);

const toggleMenu = () => {
    isMenuOpen.value = !isMenuOpen.value;
};

const closeMenu = () => {
    isMenuOpen.value = false;
};

</script>

<template>
    <nav class="">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <Link href="/" class="flex-shrink-0 flex items-center">
                        <span class="text-xl font-bold text-gray-800 dark:text-white">
                            Asesorias YG
                        </span>
                    </Link>
                </div>
              
                <!-- Desktop Menu -->
                <div class="hidden lg:flex items-center space-x-8">
                    <Link :href="home()"
                        class="rounded-sm border border-transparent px-5 
                        py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                    >
                        Inicio
                    </Link>
                    <Link v-if="$page.props.auth.user" :href="dashboard()"
                        class="rounded-sm border border-transparent px-5 
                        py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]"
                    >
                        Dashboard
                    </Link>
                 
                    <Link href="/publicaciones" class="text-gray-700 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium dark:text-gray-300 dark:hover:text-white">
                        Publicaciones
                    </Link>
                    <Link v-if="$page.props.auth.user == null" :href="login()" 
                        class="text-gray-700 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium dark:text-gray-300 dark:hover:text-white">
                        Iniciar sesion
                    </Link>
                    <Link v-if="$page.props.auth.user == null" :href="register()" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                        Registrarse
                    </Link>
                </div>

                <!-- Mobile menu button -->
                <div class="lg:hidden flex items-center">
                    <button
                        @click="toggleMenu"
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-blue-500 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700"
                        aria-expanded="false"
                    >
                        <span class="sr-only">Abrir menú principal</span>
                        <!-- Hamburger icon -->
                        <svg
                            :class="{ 'hidden': isMenuOpen, 'block': !isMenuOpen }"
                            class="h-6 w-6"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            aria-hidden="true"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <!-- Close icon -->
                        <svg
                            :class="{ 'block': isMenuOpen, 'hidden': !isMenuOpen }"
                            class="h-6 w-6"
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            aria-hidden="true"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div :class="{ 'block': isMenuOpen, 'hidden': !isMenuOpen }" class="lg:hidden">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <Link
                    href="/"
                    @click="closeMenu"
                    class="text-gray-700 hover:text-gray-900 block px-3 py-2 rounded-md text-base font-medium dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700"
                >
                    Inicio
                </Link>
                <Link
                    href="/precios"
                    @click="closeMenu"
                    class="text-gray-700 hover:text-gray-900 block px-3 py-2 rounded-md text-base font-medium dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700"
                >
                    Precios
                </Link>
                <Link
                    href="/publicaciones"
                    @click="closeMenu"
                    class="text-gray-700 hover:text-gray-900 block px-3 py-2 rounded-md text-base font-medium dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700"
                >
                    Publicaciones
                </Link>
                <Link
                    :href="login()"
                    @click="closeMenu"
                    class="text-gray-700 hover:text-gray-900 block px-3 py-2 rounded-md text-base font-medium dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700"
                >
                    Login
                </Link>
                <Link
                    :href="register()"
                    @click="closeMenu"
                    class="bg-blue-600 hover:bg-blue-700 text-white block px-3 py-2 rounded-md text-base font-medium w-full text-center"
                >
                    Registrarse
                </Link>
            </div>
        </div>
    </nav>
</template>