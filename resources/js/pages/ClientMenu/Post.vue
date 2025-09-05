<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TopBar from '@/components/MyComponents/TopBar.vue';

// Datos de ejemplo para las publicaciones
const posts = [
    {
        id: 1,
        title: 'Introducción a Laravel y Vue.js',
        excerpt: 'Aprende los fundamentos de Laravel y Vue.js para crear aplicaciones web modernas.',
        content: 'Laravel es un framework de PHP que facilita el desarrollo web, mientras que Vue.js es un framework de JavaScript para crear interfaces de usuario interactivas.',
        author: 'Asesorías YG',
        date: '2024-01-15',
        image: 'https://via.placeholder.com/400x200'
    },
    {
        id: 2,
        title: 'Mejores prácticas en desarrollo web',
        excerpt: 'Descubre las mejores prácticas para crear aplicaciones web escalables y mantenibles.',
        content: 'El desarrollo web moderno requiere seguir ciertas prácticas que garanticen la calidad y mantenibilidad del código.',
        author: 'Asesorías YG',
        date: '2024-01-10',
        image: 'https://via.placeholder.com/400x200'
    },
    {
        id: 3,
        title: 'Optimización de rendimiento en aplicaciones web',
        excerpt: 'Técnicas avanzadas para mejorar el rendimiento de tus aplicaciones web.',
        content: 'La optimización del rendimiento es crucial para proporcionar una buena experiencia de usuario.',
        author: 'Asesorías YG',
        date: '2024-01-05',
        image: 'https://via.placeholder.com/400x200'
    }
];
</script>

<template>
    <Head title="Publicaciones - Asesorías YG">
        <link rel="preconnect" href="https://rsms.me/" />
        <link rel="stylesheet" href="https://rsms.me/inter/inter.css" />
    </Head>
    
    <div class="min-h-screen bg-[#FDFDFC] dark:bg-[#0a0a0a]">
        <!-- TopBar -->
        <TopBar />
        
        <!-- Contenido principal -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header de la página -->
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-[#1b1b18] dark:text-[#EDEDEC] mb-4">
                    Publicaciones
                </h1>
                <p class="text-lg text-[#706f6c] dark:text-[#A1A09A] max-w-2xl mx-auto">
                    Descubre nuestros artículos sobre desarrollo web, mejores prácticas y consejos técnicos.
                </p>
            </div>
            
            <!-- Grid de publicaciones -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <article 
                    v-for="post in posts" 
                    :key="post.id"
                    class="bg-white dark:bg-[#161615] rounded-lg shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d] overflow-hidden hover:shadow-lg transition-shadow duration-300"
                >
                    <!-- Imagen del post -->
                    <div class="aspect-video bg-gray-200 dark:bg-gray-700">
                        <img 
                            :src="post.image" 
                            :alt="post.title"
                            class="w-full h-full object-cover"
                        />
                    </div>
                    
                    <!-- Contenido del post -->
                    <div class="p-6">
                        <!-- Fecha y autor -->
                        <div class="flex items-center text-sm text-[#706f6c] dark:text-[#A1A09A] mb-3">
                            <span>{{ post.date }}</span>
                            <span class="mx-2">•</span>
                            <span>{{ post.author }}</span>
                        </div>
                        
                        <!-- Título -->
                        <h2 class="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-3 line-clamp-2">
                            {{ post.title }}
                        </h2>
                        
                        <!-- Excerpt -->
                        <p class="text-[#706f6c] dark:text-[#A1A09A] mb-4 line-clamp-3">
                            {{ post.excerpt }}
                        </p>
                        
                        <!-- Botón de leer más -->
                        <button class="inline-flex items-center text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors">
                            Leer más
                            <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </article>
            </div>
            
            <!-- Mensaje si no hay publicaciones -->
            <div v-if="posts.length === 0" class="text-center py-12">
                <div class="text-6xl mb-4">📝</div>
                <h3 class="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-2">
                    No hay publicaciones disponibles
                </h3>
                <p class="text-[#706f6c] dark:text-[#A1A09A]">
                    Pronto tendremos contenido interesante para ti.
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
