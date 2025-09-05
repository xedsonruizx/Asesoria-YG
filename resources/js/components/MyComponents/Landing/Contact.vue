<script setup lang="ts"> 
    import { Link } from '@inertiajs/vue3';
    import { register, publicaciones } from '@/routes';


</script>

<template>

  <!-- SECCIÓN CONTACTO -->
        <section class="py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-bold mb-4">¿Necesitas asesoría legal?</h2>
                    <p class="text-lg text-[#666666] dark:text-[#A6A6A6] max-w-2xl mx-auto mb-8">
                        Contáctanos hoy mismo para una consulta gratuita. Estamos aquí para ayudarte.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <Link 
                            :href="register()" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-4 rounded-lg font-semibold text-lg transition-colors duration-200"
                        >
                            Agendar Consulta
                        </Link>
                        <a 
                            href="tel:+1234567890" 
                            class="border-2 border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white px-8 py-4 rounded-lg font-semibold text-lg transition-colors duration-200 dark:border-blue-400 dark:text-blue-400 dark:hover:bg-blue-400 dark:hover:text-white"
                        >
                            Llamar Ahora
                        </a>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mt-16">
                    <div class="text-center">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold mb-2">Dirección</h3>
                        <p class="text-[#666666] dark:text-[#A6A6A6]">Av. Principal 123<br>Ciudad, País</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold mb-2">Teléfono</h3>
                        <p class="text-[#666666] dark:text-[#A6A6A6]">+1 (234) 567-8900<br>+1 (234) 567-8901</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mx-auto mb-4">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold mb-2">Email</h3>
                        <p class="text-[#666666] dark:text-[#A6A6A6]">info@asesoriasyg.com<br>consultas@asesoriasyg.com</p>
                    </div>
                </div>
            </div>
        </section>
</template>