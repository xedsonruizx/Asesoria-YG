<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { login, register, inicio } from '@/routes';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';

defineProps<{
    isMenuOpen: boolean;
    closeMenu: () => void;
    addActiveClasses: (baseClasses: string, route: string) => string;
    canManage?: boolean;
    handleLogout?: () => void;
}>();
</script>

<template>
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
                href="/publicaciones"
                @click="closeMenu"
                :class="addActiveClasses('text-gray-700 hover:text-gray-900 block px-3 py-2 rounded-md text-base font-medium dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700', '/publicaciones')"
            >
                Publicaciones
            </Link>
            
            <!-- Nueva opción de Biblioteca -->
            <Link
                href="/biblioteca"
                @click="closeMenu"
                :class="addActiveClasses('text-gray-700 hover:text-gray-900 block px-3 py-2 rounded-md text-base font-medium dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700', '/biblioteca')"
            >
                Biblioteca
            </Link>
            
            <!-- Dashboard link para móvil - solo visible para usuarios con permisos de manage -->
            <Link
                v-if="canManage"
                href="/dashboard"
                @click="closeMenu"
                :class="addActiveClasses('text-gray-700 hover:text-gray-900 block px-3 py-2 rounded-md text-base font-medium dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700', '/admin')"
            >
                Dashboard
            </Link>
            
            <Link
                v-if="$page.props.auth.user == null" 
                :href="login()"
                @click="closeMenu"
                :class="addActiveClasses('text-gray-700 hover:text-gray-900 block px-3 py-2 rounded-md text-base font-medium dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700', '/login')"
            >
                Login
            </Link>
             <Link
                v-if="$page.props.auth.user == null" 
                :href="register()"
                @click="closeMenu"
                :class="addActiveClasses('text-gray-700 hover:text-gray-900 block px-3 py-2 rounded-md text-base font-medium dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700', '/register')"
            >
                Registrate
            </Link>

            <!-- Logout para móvil -->
            <button
                v-if="$page.props.auth.user && handleLogout"
                @click="handleLogout"
                class="text-red-600 hover:text-red-700 block px-3 py-2 rounded-md text-base font-medium dark:text-red-400 dark:hover:text-red-300 dark:hover:bg-gray-700 w-full text-left"
            >
                Cerrar sesión
            </button>
        </div>
    </div>
</template>
