<script setup lang="ts">
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { login, register, inicio } from '@/routes';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { ChevronDown, Settings, User, Lock, Palette } from 'lucide-vue-next';

const showConfigDropdown = ref(false);

defineProps<{
    isMenuOpen: boolean;
    closeMenu: () => void;
    addActiveClasses: (baseClasses: string, route: string) => string;
    canManage?: boolean;
    handleLogout?: () => void;
}>();

const toggleConfigDropdown = () => {
    showConfigDropdown.value = !showConfigDropdown.value;
};
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
            
            <!-- Dropdown de Configuraciones - solo visible para usuarios autenticados -->
            <div v-if="$page.props.auth.user" class="border-t border-gray-200 dark:border-gray-700 pt-2 mt-2">
                <!-- Botón principal de Configuraciones -->
                <button
                    @click="toggleConfigDropdown"
                    class="w-full flex items-center justify-between px-3 py-2 text-left text-gray-700 hover:text-gray-900 hover:bg-gray-100 rounded-md text-base font-medium dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700 transition-colors"
                >
                    <div class="flex items-center gap-2">
                        <Settings class="h-4 w-4" />
                        <span>Perfil</span>
                    </div>
                    <ChevronDown 
                        :class="{ 'rotate-180': showConfigDropdown }" 
                        class="h-4 w-4 transition-transform duration-200"
                    />
                </button>
                
                <!-- Submenu desplegable -->
                <div 
                    v-show="showConfigDropdown" 
                    class="ml-4 mt-1 space-y-1 border-l-2 border-gray-200 dark:border-gray-600 pl-3"
                >
                    <Link
                        href="/auth-settings/profile"
                        @click="closeMenu"
                        class="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700 transition-colors"
                    >
                        <User class="h-4 w-4" />
                        <span>Perfil</span>
                    </Link>
                    
                    <Link
                        href="/auth-settings/password"
                        @click="closeMenu"
                        class="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700 transition-colors"
                    >
                        <Lock class="h-4 w-4" />
                        <span>Contraseña</span>
                    </Link>
                    
                    <Link
                        href="/auth-settings/appearance"
                        @click="closeMenu"
                        class="flex items-center gap-2 px-3 py-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-gray-50 rounded-md dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700 transition-colors"
                    >
                        <Palette class="h-4 w-4" />
                        <span>Apariencia</span>
                    </Link>
                    <!-- Logout para móvil -->
                    <button
                        v-if="$page.props.auth.user && handleLogout"
                        @click="handleLogout"
                        class="flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:text-gray-900 hover:bg-gray-50 rounded-md dark:text-gray-400 dark:hover:text-white dark:hover:bg-gray-700 transition-colors"
                    >
                        Cerrar sesión
                    </button>



                </div>
            </div>
            
      
            
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


        </div>
    </div>
</template>
