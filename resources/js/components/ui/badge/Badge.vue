<!-- <script setup lang="ts">
import { cva, type VariantProps } from 'class-variance-authority'
import { cn } from '@/lib/utils'

const badgeVariants = cva(
  'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2',
  {
    variants: {
      variant: {
        default:
          'border-transparent bg-primary text-primary-foreground hover:bg-primary/80',
        secondary:
          'border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80',
        destructive:
          'border-transparent bg-destructive text-destructive-foreground hover:bg-destructive/80',
        outline: 'text-foreground',
        success:
          'border-transparent bg-green-500 text-white hover:bg-green-600',
        warning:
          'border-transparent bg-yellow-500 text-white hover:bg-yellow-600',
      },
      size: {
        default: '',
        sm: 'px-2 py-0.5 text-sm',
        lg: 'px-3 py-1 text-base'
      }
    },
    defaultVariants: {
      variant: 'default',
      size: 'default'
    },
  }
)

// 👉 Crear un alias de los VariantProps
type BadgeVariantProps = VariantProps<typeof badgeVariants>

type BadgeProps = VariantProps<typeof badgeVariants> & {
  class?: string
}

const props = defineProps<BadgeProps>()
</script>

<template>
  <div :class="cn(badgeVariants({ variant: props.variant, size: props.size }), props.class)">
    <slot />
  </div>
</template> -->

<template></template>