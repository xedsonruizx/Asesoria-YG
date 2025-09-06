<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { index as postsIndex } from '@/routes/posts';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Publicaciones',
        href: postsIndex().url,
    },
];
</script>

<template>
    <Head title="Publicaciones" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                hola
        </div>
    </AppLayout>
</template>
