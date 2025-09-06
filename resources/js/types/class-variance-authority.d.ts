declare module 'class-variance-authority' {
  export interface VariantProps<T> {
    [key: string]: any
  }
  
  export function cva(
    base: string,
    config?: {
      variants?: Record<string, Record<string, string>>
      defaultVariants?: Record<string, string>
    }
  ): {
    (props?: Record<string, any>): string
  }
}