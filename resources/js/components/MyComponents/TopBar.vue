<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { login, register, inicio } from '@/routes';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger, DropdownMenuItem, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { getInitials } from '@/composables/useInitials';
import { router } from '@inertiajs/vue3';
import MobileMenu from '@/components/MyComponents/MobileMenu.vue';






const isMenuOpen = ref(false);
const isScrolled = ref(false);
const page = usePage();
const auth = computed(() => page.props.auth);

// Computed para verificar si el usuario tiene permisos de manage
const canManage = computed(() => {
    return auth.value.permissions && auth.value.permissions.includes('manage');
});

// Computed para obtener la ruta actual
const currentRoute = computed(() => page.url);

const logout = () => {
    router.post('/logout');
}




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

// Función para manejar el logout
const handleLogout = () => {
    router.post('/logout');
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
                <div class="hidden lg:flex items-center space-x-8 text-base">
                     <Link 
                        :href="inicio()" 
                        :class="addActiveClasses('text-gray-700 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium dark:text-gray-300 dark:hover:text-white', '/inicio')"
                    >
                        Inicio
                    </Link>
                    <Link 
                        href="/publicaciones" 
                        :class="addActiveClasses('text-gray-700 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium dark:text-gray-300 dark:hover:text-white', '/publicaciones')"
                    >
                        Publicaciones
                    </Link>
                    
                    <!-- Dashboard link - solo visible para usuarios con permisos de manage -->
                    <Link 
                        v-if="canManage"
                        href="/admin/dashboard" 
                        :class="addActiveClasses('text-gray-700 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium dark:text-gray-300 dark:hover:text-white', '/admin')"
                    >
                        Dashboard
                    </Link>
                    
                    <Link 
                        v-if="auth.user == null" 
                        :href="login()" 
                        :class="addActiveClasses('text-gray-700 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium dark:text-gray-300 dark:hover:text-white', '/login')"
                    >
                        Iniciar sesion
                    </Link>
                     <Link 
                        v-if="auth.user == null" 
                        :href="register()" 
                        :class="addActiveClasses('text-gray-700 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium dark:text-gray-300 dark:hover:text-white', '/register')"
                    >
                        Registrate
                    </Link>

                    <!-- User Menu Dropdown -->
                    <DropdownMenu v-if="auth.user">
                        <DropdownMenuTrigger :as-child="true">
                            <Button
                                variant="ghost"
                                size="icon"
                                class="relative size-10 w-auto rounded-full p-1 focus-within:ring-2 focus-within:ring-primary"
                            >
                                <Avatar class="size-8 overflow-hidden rounded-full">
                                    <AvatarImage v-if="auth.user.avatar" :src="auth.user.avatar" :alt="auth.user.name" />
                                    <AvatarFallback class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white">
                                        {{ getInitials(auth.user?.name) }}
                                    </AvatarFallback>
                                </Avatar> 
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <!-- User Info -->
                            <div class="px-3 py-2 border-b border-gray-200 dark:border-gray-700">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ auth.user.name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ auth.user.email }}</p>
                            </div>
                            
                            <!-- Menu Items -->
                            <DropdownMenuItem as-child>
                                <Link href="/profile" class="flex items-center w-full px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Perfil
                                </Link>
                            </DropdownMenuItem>
                            
                            <DropdownMenuItem v-if="canManage" as-child>
                                <Link href="/admin/dashboard" class="flex items-center w-full px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                    Dashboard
                                </Link>
                            </DropdownMenuItem>
                            
                            <DropdownMenuSeparator />
                            
                            <!-- Logout -->
                            <DropdownMenuItem>
                                <button 
                                    @click="handleLogout"
                                    class="flex items-center w-full px-3 py-2 text-sm text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    Cerrar sesión
                                </button>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu> 
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
        
        <!-- Mobile Menu Component -->
        <MobileMenu 
            :isMenuOpen="isMenuOpen" 
            :closeMenu="closeMenu" 
            :addActiveClasses="addActiveClasses"
            :canManage="canManage"
            :handleLogout="handleLogout"
        />
    </nav>
</template>