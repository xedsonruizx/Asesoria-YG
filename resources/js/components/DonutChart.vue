<script setup lang="ts">
interface DonutChartItem {
    name: string;
    value: number;
    color: string;
}

interface Props {
    data: DonutChartItem[];
    centerText?: string;
    centerSubtext?: string;
}

defineProps<Props>();
</script>

<template>
    <Card>
    <div class="flex flex-col lg:flex-row items-center justify-center gap-8">
        <!-- Gráfico de dona -->
        <div class="relative">
            <svg width="300" height="300" viewBox="0 0 300 300" class="transform -rotate-90">
                <!-- SVG content moved here -->
            </svg>
            
            <!-- Texto central -->
            <div class="absolute inset-0 flex flex-col items-center justify-center">
                <div class="text-3xl font-bold text-gray-800">{{ centerText }}</div>
                <div class="text-sm text-gray-600">{{ centerSubtext }}</div>
            </div>
        </div>
        
        <!-- Leyenda -->
        <div class="space-y-4">
            <div v-for="(item, index) in data" :key="item.name" 
                 class="flex items-center space-x-3">
                <div 
                    :style="{ backgroundColor: item.color }" 
                    class="w-4 h-4 rounded-full"
                ></div>
                <div class="flex-1">
                    <div class="font-medium text-gray-800">{{ item.name }}</div>
                    <div class="text-sm text-gray-600">{{ item.value.toFixed(1) }}%</div>
                </div>
            </div>
        </div>
    </div>
    </Card>
</template>

<style scoped>
@keyframes rotate {
    from {
        transform: rotate(-90deg);
    }
    to {
        transform: rotate(270deg);
    }
}

.hover\:stroke-width-45:hover {
    stroke-width: 45;
}
</style>