<!-- <script setup lang="ts">
import { AlertDialogCancel } from 'radix-vue'
import { cn } from '@/lib/utils'
import { buttonVariants } from '@/components/ui/button'

interface Props {
  class?: string
  asChild?: boolean
}

defineProps<Props>()
</script>

<template>
  <AlertDialogCancel
    :class="cn(buttonVariants({ variant: 'outline' }), 'mt-2 sm:mt-0', $props.class)"
    :as-child="asChild"
  >
    <slot />
  </AlertDialogCancel>
</template> -->