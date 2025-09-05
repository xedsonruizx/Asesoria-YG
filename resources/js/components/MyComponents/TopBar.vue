<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { dashboard, login, register, inicio } from '@/routes';

const isMenuOpen = ref(false);
const isScrolled = ref(false);
const page = usePage();

// Computed para obtener la ruta actual
const currentRoute = computed(() => page.url);

// Función para verificar si una ruta está activa
const isActiveRoute = (route: string) => {
    return currentRoute.value === route || currentRoute.value.startsWith(route);
};

// Función para agregar clases de estado activo manteniendo el estilo original
const addActiveClasses = (baseClasses: string, route: string) => {
    if (isActiveRoute(route)) {
        return `${baseClasses} bg-blue-50 border-blue-200 text-blue-700 dark:bg-blue-900/20 dark:border-blue-700 dark:text-blue-300`;
    }
    return baseClasses;
};

const toggleMenu = () => {
    isMenuOpen.value = !isMenuOpen.value;
};

const closeMenu = () => {
    isMenuOpen.value = false;
};

// Función para manejar el scroll
const handleScroll = () => {
    isScrolled.value = window.scrollY > 10;
};

// Agregar y remover el listener de scroll
onMounted(() => {
    window.addEventListener('scroll', handleScroll);
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});
</script>

<template>
    <nav 
        :class="[
            'sticky top-0 p-3 transition-all duration-300 z-50',
            isScrolled 
                ? 'bg-[#FDFDFC] backdrop-blur-md shadow-lg border-b border-gray-200 dark:bg-[#0a0a0a] dark:border-gray-700' 
                : 'bg-transparent'
        ]"
    >
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
                     <Link 
                        :href="inicio()" 
                        :class="addActiveClasses('text-gray-700 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium dark:text-gray-300 dark:hover:text-white', '/inicio')"
                    >
                        Inicio
                    </Link>
                    
                    <Link 
                        v-if="$page.props.auth.user" 
                        :href="dashboard()"
                        :class="addActiveClasses('rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]', '/dashboard')"
                    >
                        Dashboard
                    </Link>
                 
                    <Link 
                        href="/publicaciones" 
                        :class="addActiveClasses('text-gray-700 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium dark:text-gray-300 dark:hover:text-white', '/publicaciones')"
                    >
                        Publicaciones
                    </Link>
                    
                    <Link 
                        v-if="$page.props.auth.user == null" 
                        :href="login()" 
                        :class="addActiveClasses('text-gray-700 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium dark:text-gray-300 dark:hover:text-white', '/login')"
                    >
                        Iniciar sesion
                    </Link>
                    
                    <Link
                        v-if="$page.props.auth.user == null" 
                        :href="register()"
                        @click="closeMenu"
                        :class="addActiveClasses('text-gray-700 hover:text-gray-900 block px-3 py-2 rounded-md text-base font-medium dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700', '/register')"
                    >
                            Registrate
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
                    :href="inicio()"
                    @click="closeMenu"
                    :class="addActiveClasses('text-gray-700 hover:text-gray-900 block px-3 py-2 rounded-md text-base font-medium dark:text-gray-300 dark:hover:text-white ', '/inicio')"
                >
                    Inicio
                </Link>
                
                <Link
                    href="/precios"
                    @click="closeMenu"
                    :class="addActiveClasses('text-gray-700 hover:text-gray-900 block px-3 py-2 rounded-md text-base font-medium dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700', '/precios')"
                >
                    Precios
                </Link>
                
                <Link
                    href="/publicaciones"
                    @click="closeMenu"
                    :class="addActiveClasses('text-gray-700 hover:text-gray-900 block px-3 py-2 rounded-md text-base font-medium dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700', '/publicaciones')"
                >
                    Publicaciones
                </Link>
                
                <Link
                    :href="login()"
                    @click="closeMenu"
                    :class="addActiveClasses('text-gray-700 hover:text-gray-900 block px-3 py-2 rounded-md text-base font-medium dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700', '/login')"
                >
                    Login
                </Link>
                 <Link
                    :href="register()"
                    @click="closeMenu"
                    :class="addActiveClasses('text-gray-700 hover:text-gray-900 block px-3 py-2 rounded-md text-base font-medium dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700', '/register')"
                >
                    Registrate
                </Link>
            </div>
        </div>
    </nav>
</template>