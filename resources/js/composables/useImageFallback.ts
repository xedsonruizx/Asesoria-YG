import { ref } from 'vue';

export function useImageFallback(fallbackUrl: string = '/images/no-image-placeholder.jpg') {
  const hasError = ref(false);
  
  const handleError = () => {
    hasError.value = true;
  };
  
  const getImageUrl = (originalUrl: string) => {
    return hasError.value ? fallbackUrl : originalUrl;
  };
  
  const reset = () => {
    hasError.value = false;
  };
  
  return {
    hasError,
    handleError,
    getImageUrl,
    reset
  };
}