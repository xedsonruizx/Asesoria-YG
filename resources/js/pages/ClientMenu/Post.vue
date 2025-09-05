<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import TopBar from '@/components/MyComponents/TopBar.vue';
import SubscriptionModal from '@/components/MyComponents/SubscriptionModal.vue';
import { computed, ref } from 'vue';

// Obtener el nombre de la empresa desde las variables de entorno
const companyName = import.meta.env.VITE_COMPANY_NAME || 'Asesorías YG';

// Estado del modal
const showSubscriptionModal = ref(false);
const selectedPostTitle = ref('');

// Simulamos el estado de suscripción del usuario (esto vendría de props o store)
const userHasActiveSubscription = false; // Cambiar según el estado real del usuario

// Datos de ejemplo para las publicaciones según el esquema de BD
const posts = [
    {
        id: 1,
        title: 'Guía Completa de Compliance Legal para Empresas',
        content: 'Una guía exhaustiva sobre cómo implementar un sistema de compliance efectivo en tu empresa. Incluye templates, checklists y casos de estudio reales de empresas que han logrado certificaciones internacionales.',
        category: 'Legal',
        status: 'published',
        image_path: 'https://via.placeholder.com/400x200/3B82F6/FFFFFF?text=Legal+Compliance',
        file_path: '/downloads/compliance-guide.pdf',
        subscripcion: true, // Contenido de pago
        created_at: '2024-01-15',
        updated_at: '2024-01-15'
    },
    {
        id: 2,
        title: 'Estrategias de Reclutamiento Digital',
        content: 'Descubre las mejores prácticas para atraer talento en la era digital. Incluye herramientas gratuitas, técnicas de sourcing y templates de entrevistas estructuradas.',
        category: 'RRHH',
        status: 'published',
        image_path: 'https://via.placeholder.com/400x200/10B981/FFFFFF?text=RRHH+Digital',
        file_path: null,
        subscripcion: false, // Contenido gratuito
        created_at: '2024-01-10',
        updated_at: '2024-01-10'
    },
    {
        id: 3,
        title: 'Manual de Contratos Laborales 2024',
        content: 'Manual actualizado con todos los tipos de contratos laborales, cláusulas especiales, y modelos descargables. Incluye las últimas reformas laborales y jurisprudencia relevante.',
        category: 'Legal',
        status: 'published',
        image_path: 'https://via.placeholder.com/400x200/8B5CF6/FFFFFF?text=Contratos+2024',
        file_path: '/downloads/manual-contratos-2024.pdf',
        subscripcion: true, // Contenido de pago
        created_at: '2024-01-05',
        updated_at: '2024-01-08'
    },
    {
        id: 4,
        title: 'Evaluación de Desempeño: Metodologías Modernas',
        content: 'Aprende a implementar sistemas de evaluación de desempeño efectivos. Incluye matrices de competencias, formularios y técnicas de feedback constructivo.',
        category: 'RRHH',
        status: 'published',
        image_path: 'https://via.placeholder.com/400x200/F59E0B/FFFFFF?text=Evaluacion+360',
        file_path: null,
        subscripcion: false, // Contenido gratuito
        created_at: '2024-01-03',
        updated_at: '2024-01-03'
    },
    {
        id: 5,
        title: 'Protección de Datos y GDPR para PyMEs',
        content: 'Guía práctica para implementar medidas de protección de datos personales. Incluye políticas de privacidad, procedimientos de seguridad y formularios de consentimiento.',
        category: 'Legal',
        status: 'published',
        image_path: 'https://via.placeholder.com/400x200/EF4444/FFFFFF?text=GDPR+PyMEs',
        file_path: '/downloads/gdpr-pymes-guide.pdf',
        subscripcion: true, // Contenido de pago
        created_at: '2024-01-01',
        updated_at: '2024-01-01'
    }
];

// Computed para filtrar posts publicados
const publishedPosts = computed(() => {
    return posts.filter(post => post.status === 'published');
});

// Función para obtener el color de la categoría
const getCategoryColor = (category: string) => {
    const colors = {
        'Legal': 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
        'RRHH': 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
        'Finanzas': 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200',
        'Tecnología': 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200'
    };
    return colors[category] || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
};

// Función para formatear fecha
const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('es-ES', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

// Función para manejar clic en post
const handlePostClick = (post: any) => {
    console.log('🔍 Post clicked:', post.title);
    console.log('🔒 Subscription required:', post.subscripcion);
    console.log('👤 User has subscription:', userHasActiveSubscription);
    console.log('📊 Modal state before:', showSubscriptionModal.value);
    
    if (post.subscripcion && !userHasActiveSubscription) {
        // Mostrar modal de suscripción
        console.log('✅ Opening subscription modal...');
        selectedPostTitle.value = post.title;
        showSubscriptionModal.value = true;
        console.log('📊 Modal state after:', showSubscriptionModal.value);
        console.log('📝 Selected post title:', selectedPostTitle.value);
        return;
    }
    // Lógica para abrir el post completo
    console.log('Abriendo post:', post.title);
};

// Función para cerrar el modal
const closeSubscriptionModal = () => {
    showSubscriptionModal.value = false;
    selectedPostTitle.value = '';
};
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
                    Descubre nuestros recursos especializados en Legal y RRHH para hacer crecer tu empresa.
                </p>
            </div>
            
            <!-- Grid de publicaciones -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <article 
                    v-for="post in publishedPosts" 
                    :key="post.id"
                    class="relative bg-white dark:bg-[#161615] rounded-lg shadow-[inset_0px_0px_0px_1px_rgba(26,26,0,0.16)] dark:shadow-[inset_0px_0px_0px_1px_#fffaed2d] overflow-hidden hover:shadow-lg transition-all duration-300 cursor-pointer group"
                    @click="handlePostClick(post)"
                >
                    <!-- Candado para contenido de pago -->
                    <div 
                        v-if="post.subscripcion && !userHasActiveSubscription"
                        class="absolute top-4 right-4 z-10 bg-yellow-500 text-white p-2 rounded-full shadow-lg"
                    >
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                        </svg>
                    </div>

                    <!-- Badge de categoría -->
                    <div class="absolute top-4 left-4 z-10">
                        <span :class="getCategoryColor(post.category)" class="px-2 py-1 rounded-full text-xs font-medium">
                            {{ post.category }}
                        </span>
                    </div>
                    
                    <!-- Imagen del post -->
                    <div class="aspect-video bg-gray-200 dark:bg-gray-700 relative overflow-hidden">
                        <img 
                            :src="post.image_path" 
                            :alt="post.title"
                            class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                        />
                        <!-- Overlay para contenido de pago -->
                        <div 
                            v-if="post.subscripcion && !userHasActiveSubscription"
                            class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center"
                        >
                            <div class="text-center text-white">
                                <svg class="w-12 h-12 mx-auto mb-2 opacity-80" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                                </svg>
                                <p class="text-sm font-medium">Contenido Premium</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Contenido del post -->
                    <div class="p-6">
                        <!-- Fecha y archivo adjunto -->
                        <div class="flex items-center justify-between text-sm text-[#706f6c] dark:text-[#A1A09A] mb-3">
                            <span>{{ formatDate(post.created_at) }}</span>
                            <div class="flex items-center space-x-2">
                                <!-- Indicador de archivo descargable -->
                                <svg v-if="post.file_path" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                <span>{{ companyName }}</span>
                            </div>
                        </div>
                        
                        <!-- Título -->
                        <h2 class="text-xl font-semibold text-[#1b1b18] dark:text-[#EDEDEC] mb-3 line-clamp-2">
                            {{ post.title }}
                        </h2>
                        
                        <!-- Contenido truncado -->
                        <p class="text-[#706f6c] dark:text-[#A1A09A] mb-4 line-clamp-3">
                            {{ post.content.substring(0, 150) }}{{ post.content.length > 150 ? '...' : '' }}
                        </p>
                        
                        <!-- Botón de acción -->
                        <div class="flex items-center justify-between">
                            <button class="inline-flex items-center text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors">
                                {{ post.subscripcion && !userHasActiveSubscription ? 'Ver Premium' : 'Leer más' }}
                                <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                            
                            <!-- Indicador de contenido premium -->
                            <div v-if="post.subscripcion" class="flex items-center text-xs text-yellow-600 dark:text-yellow-400">
                                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                                </svg>
                                Premium
                            </div>
                        </div>
                    </div>
                </article>
            </div>
            
            <!-- Mensaje si no hay publicaciones -->
            <div v-if="publishedPosts.length === 0" class="text-center py-12">
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

<!-- Modal de suscripción -->
<SubscriptionModal 
    :is-open="showSubscriptionModal"
    :post-title="selectedPostTitle"
    @close="closeSubscriptionModal"
/>


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


