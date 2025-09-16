<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';
import TopBar from '@/components/MyComponents/TopBar.vue';
import SubscriptionModal from '@/components/MyComponents/SubscriptionModal.vue';
import PostCard from './Posts/PostCard.vue';
import { computed, ref } from 'vue';
import { Image } from 'lucide-vue-next';

// Props para recibir los posts del servidor
interface Post {
    id: number;
    title: string;
    content: string;
    excerpt?: string;
    slug: string;
    meta_description?: string;
    status: string;
    is_premium: boolean;
    image_path: string | null;
    file_path: string | null;
    author_id?: number;
    published_at?: string;
    created_at: string;
    updated_at: string;
    tags?: Array<{
        id: number;
        name: string;
        slug: string;
        color: string;
    }>;
}

interface User {
    id: number;
    name: string;
    email: string;
    ispremium: boolean;
}

interface Props {
    posts: {
        data: Post[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        // Add other pagination properties as needed
    } | Post[];
    auth?: {
        user: User;
    };
}

const props = defineProps<Props>();

// Obtener el nombre de la empresa desde las variables de entorno
const companyName = import.meta.env.VITE_COMPANY_NAME || 'Asesorías YG';

// Estado del modal
const showSubscriptionModal = ref(false);
const selectedPostTitle = ref('');

// Estado para manejar errores de imagen
const imageErrors = ref<Record<number, boolean>>({});

// Computed para verificar si el usuario tiene suscripción premium
const userIsPremium = computed(() => {
    return props.auth?.user?.ispremium || false;
});

// Computed para filtrar posts publicados (ya vienen filtrados del servidor)
const publishedPosts = computed(() => {
    // Handle both paginated and array formats
    if (Array.isArray(props.posts)) {
        return props.posts;
    }
    return props.posts?.data || [];
});

// Función para cerrar el modal
const closeSubscriptionModal = () => {
    showSubscriptionModal.value = false;
    selectedPostTitle.value = '';
};

// Función para abrir el modal de suscripción
const openSubscriptionModal = (postTitle: string) => {
    selectedPostTitle.value = postTitle;
    showSubscriptionModal.value = true;
};

// Función para manejar errores de imagen
const handleImageError = (postId: number) => {
    imageErrors.value[postId] = true;
};

// Función para obtener el color de la etiqueta
const getTagColor = (color: string) => {
    return color || 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200';
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
const handlePostClick = (post: Post) => {
    console.log('🔍 Post clicked:', post.title);
    console.log('🔒 Premium required:', post.is_premium);
    console.log('👤 User is premium:', userIsPremium.value);
    console.log('🔑 User is premium:', props.auth?.user?.ispremium);

    
    if (post.is_premium && !userIsPremium.value) {
        // Mostrar modal de suscripción si el post es premium y el usuario no lo es
        console.log('✅ Opening subscription modal...');
        openSubscriptionModal(post.title);
        return;
    }
    
    // Navegar al show del post usando slug si está disponible
    const url = post.slug ? `/publicacion/${post.slug}` : `/publicacion/${post.id}`;
    router.visit(url);
};

const openPost = (post: Post) => {
    // Verificar si el post es premium y el usuario no tiene suscripción
    if (post.is_premium && !userIsPremium.value) {
        console.log('✅ Opening subscription modal...');
        openSubscriptionModal(post.title);
        return;
    }
    
    // Navegar al show del post usando slug
    router.visit(`/publicacion/${post.slug}`);
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
                <PostCard 
                    v-for="post in publishedPosts" 
                    :key="post.id"
                    :post="post"
                    :company-name="companyName"
                    :user-is-premium="userIsPremium"
                    @open-subscription-modal="openSubscriptionModal"
                />
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


