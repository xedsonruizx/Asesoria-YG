<script setup lang="ts">
import { publicaciones, evaluacion } from '@/routes';
import { Link } from '@inertiajs/vue3';
const companyName = import.meta.env.VITE_COMPANY_NAME || 'Asesorías YG';

</script>
<template>
        <!-- SECCIÓN HERO -->
        <section class="relative overflow-hidden py-20 lg:py-32">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h1 class="text-4xl md:text-6xl font-bold mb-6 leading-tight">
                        Asesoría Legal
                        <span class="text-blue-600 dark:text-blue-400">Profesional</span>
                    </h1>
                    <p class="text-xl md:text-2xl text-[#666666] dark:text-[#A6A6A6] mb-8 max-w-3xl mx-auto">
                        Brindamos servicios jurídicos especializados con más de 10 años de experiencia. 
                        Tu tranquilidad legal es nuestra prioridad.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <Link 
                            :href="evaluacion()" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-4 rounded-lg font-semibold text-lg transition-colors duration-200"
                        >
                            Evaluacion previa
                        </Link>
                        <Link 
                            :href="publicaciones()" 
                            class="border-2 border-blue-600 text-blue-600 hover:bg-blue-600 hover:text-white px-8 py-4 rounded-lg font-semibold text-lg transition-colors duration-200 dark:border-blue-400 dark:text-blue-400 dark:hover:bg-blue-400 dark:hover:text-white"
                        >
                            Ver Publicaciones
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        
        <!-- SECCIÓN POR QUÉ ELEGIRNOS -->
        <section class="py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl md:text-4xl font-bold mb-4">¿Por qué elegir {{ companyName }}</h2>
                    <p class="text-lg text-[#666666] dark:text-[#A6A6A6] max-w-2xl mx-auto">
                        Nuestra experiencia y compromiso nos distinguen en el mercado legal
                    </p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <div class="text-center">
                        <div class="text-4xl font-bold text-blue-600 dark:text-blue-400 mb-2">+10</div>
                        <div class="text-lg font-semibold mb-2">Años de Experiencia</div>
                        <p class="text-[#666666] dark:text-[#A6A6A6] text-sm">Más de una década brindando servicios legales de calidad</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="text-4xl font-bold text-blue-600 dark:text-blue-400 mb-2">500+</div>
                        <div class="text-lg font-semibold mb-2">Casos Exitosos</div>
                        <p class="text-[#666666] dark:text-[#A6A6A6] text-sm">Cientos de clientes satisfechos con nuestros servicios</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="text-4xl font-bold text-blue-600 dark:text-blue-400 mb-2">24/7</div>
                        <div class="text-lg font-semibold mb-2">Atención Disponible</div>
                        <p class="text-[#666666] dark:text-[#A6A6A6] text-sm">Estamos disponibles cuando nos necesites</p>
                    </div>
                    
                    <div class="text-center">
                        <div class="text-4xl font-bold text-blue-600 dark:text-blue-400 mb-2">100%</div>
                        <div class="text-lg font-semibold mb-2">Confidencialidad</div>
                        <p class="text-[#666666] dark:text-[#A6A6A6] text-sm">Garantizamos la privacidad de tu información</p>
                    </div>
                </div>
            </div>
        </section>
</template>