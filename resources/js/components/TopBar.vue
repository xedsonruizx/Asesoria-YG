// ... existing code ...
<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import MobileMenu from './MobileMenu.vue'

const { auth } = usePage().props

const canManage = computed(() => {
    return auth.permissions && auth.permissions.includes('manage')
})

const logout = () => {
    // Implementar logout
    window.location.href = '/logout'
}
</script>
// ... existing code ...