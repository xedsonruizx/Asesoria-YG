<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';

interface Props {
    isOpen: boolean;
    postTitle?: string;
}

const props = withDefaults(defineProps<Props>(), {
    postTitle: 'este contenido premium'
});

// Agregar watcher para debug
watch(() => props.isOpen, (newValue, oldValue) => {
    console.log('🎭 Modal isOpen changed:', { from: oldValue, to: newValue });
    console.log('📝 Post title:', props.postTitle);
});

const emit = defineEmits<{
    close: [];
}>();

// Función para cerrar el modal
const closeModal = () => {
    console.log('❌ Closing modal...');
    emit('close');
};

// Función para redirigir a suscripción (por ahora a inicio)
const redirectToSubscription = () => {
    console.log('🚀 Redirecting to subscription...');
    closeModal();
    // Por ahora redirige a inicio, después cambiar por la página de suscripción
    router.visit('/');
};

// Función para cerrar con ESC
const handleKeydown = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && props.isOpen) {
        console.log('⌨️ ESC pressed, closing modal...');
        closeModal();
    }
};

// Agregar y remover listener correctamente
onMounted(() => {
    console.log('🔧 SubscriptionModal mounted');
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    console.log('🗑️ SubscriptionModal unmounted');
    document.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <!-- Overlay del modal -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div 
                v-if="isOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50 backdrop-blur-sm"
                @click="closeModal"
            >
                <!-- Contenido del modal -->
                <Transition
                    enter-active-class="transition-all duration-300"
                    enter-from-class="opacity-0 scale-95 translate-y-4"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition-all duration-300"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-4"
                >
                    <div 
                        v-if="isOpen"
                        class="relative w-full max-w-md mx-auto bg-white dark:bg-[#161615] rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 overflow-hidden"
                        @click.stop
                    >
                        <!-- Header del modal -->
                        <div class="relative bg-gradient-to-r from-yellow-400 to-orange-500 px-6 py-8 text-center">
                            <!-- Botón cerrar -->
                            <button 
                                @click="closeModal"
                                class="absolute top-4 right-4 text-white hover:text-gray-200 transition-colors"
                            >
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                            
                            <!-- Icono de candado -->
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-white bg-opacity-20 rounded-full mb-4">
                                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                            </div>
                            
                            <h2 class="text-2xl font-bold text-white mb-2">
                                ¡Contenido Premium!
                            </h2>
                            <p class="text-white text-opacity-90 text-sm">
                                Desbloquea todo nuestro contenido especializado
                            </p>
                        </div>
                        
                        <!-- Contenido del modal -->
                        <div class="px-6 py-6">
                            <div class="text-center mb-6">
                                <h3 class="text-lg font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-3">
                                    Para acceder a "{{ postTitle }}"
                                </h3>
                                <p class="text-[#706f6c] dark:text-[#A1A09A] text-sm leading-relaxed">
                                    Necesitas una suscripción activa para acceder a nuestro contenido premium especializado en Legal y RRHH.
                                </p>
                            </div>
                            
                            <!-- Beneficios de la suscripción -->
                            <div class="space-y-3 mb-6">
                                <div class="flex items-center text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                    <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    <span>Acceso completo a guías especializadas</span>
                                </div>
                                <div class="flex items-center text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                    <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    <span>Templates y documentos descargables</span>
                                </div>
                                <div class="flex items-center text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                    <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    <span>Casos de estudio y ejemplos reales</span>
                                </div>
                                <div class="flex items-center text-sm text-[#706f6c] dark:text-[#A1A09A]">
                                    <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    <span>Actualizaciones constantes de contenido</span>
                                </div>
                            </div>
                            
                            <!-- Botones de acción -->
                            <div class="space-y-3">
                                <button 
                                    @click="redirectToSubscription"
                                    class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-3 px-4 rounded-lg transition-all duration-200 transform hover:scale-[1.02] focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                                >
                                    🚀 Obtener Suscripción Premium
                                </button>
                                
                                <button 
                                    @click="closeModal"
                                    class="w-full bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-[#1b1b18] dark:text-[#EDEDEC] font-medium py-3 px-4 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
                                >
                                    Tal vez después
                                </button>
                            </div>
                            
                            <!-- Nota adicional -->
                            <p class="text-xs text-[#706f6c] dark:text-[#A1A09A] text-center mt-4">
                                Cancela en cualquier momento • Sin compromisos a largo plazo
                            </p>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>