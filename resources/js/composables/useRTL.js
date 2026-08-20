import { computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'

export function useRTL() {
    const { locale } = useI18n()
    
    // Computed property for RTL direction
    const isRTL = computed(() => locale.value === 'ar')
    const direction = computed(() => isRTL.value ? 'rtl' : 'ltr')
    
    // Watch for locale changes and update document direction
    watch(locale, (newLocale) => {
        const dir = newLocale === 'ar' ? 'rtl' : 'ltr'
        document.documentElement.setAttribute('dir', dir)
        document.documentElement.setAttribute('lang', newLocale)
        
        // Update body class for RTL styling
        if (newLocale === 'ar') {
            document.body.classList.add('rtl')
            document.body.classList.remove('ltr')
        } else {
            document.body.classList.add('ltr')
            document.body.classList.remove('rtl')
        }
    }, { immediate: true })
    
    return {
        isRTL,
        direction,
        locale
    }
}
