<template>
    <div class="relative">
        <button
            @click="showDropdown = !showDropdown"
            class="language-switcher-button inline-flex items-center px-4 py-2 rounded-xl text-sm font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2"
            :class="{ 'font-cairo': isRTL }"
            style="background-color: var(--secondary); border: 1px solid var(--stroke); color: var(--paragraph); focus:ring-color: var(--button);"
            :aria-expanded="showDropdown"
            aria-label="Language switcher"
        >
            <svg class="w-4 h-4 mr-2" :class="{ 'ml-2 mr-0': isRTL }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
            </svg>
            <span class="hidden sm:inline">{{ $t('navigation.language') }}</span>
            <svg
                class="ml-2 h-4 w-4 transition-transform duration-200"
                :class="{ 'mr-2 ml-0': isRTL, 'rotate-180': showDropdown }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>

        <div
            v-show="showDropdown"
            class="absolute z-50 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5"
            :class="{ 'right-0': !isRTL, 'left-0': isRTL }"
        >
            <div class="py-1">
                <Link
                    :href="route('language.switch', 'en')"
                    class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                    :class="{ 'active-language': currentLocale === 'en' }"
                    @click="showDropdown = false"
                >
                    <span class="fi fi-us mr-2" :class="{ 'ml-2 mr-0': isRTL }"></span>
                    {{ $t('navigation.english') }}
                </Link>
                <Link
                    :href="route('language.switch', 'ar')"
                    class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                    :class="{ 'active-language': currentLocale === 'ar' }"
                    @click="showDropdown = false"
                >
                    <span class="fi fi-sa mr-2" :class="{ 'ml-2 mr-0': isRTL }"></span>
                    {{ $t('navigation.arabic') }}
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'

const { locale, t } = useI18n()
const showDropdown = ref(false)

const currentLocale = computed(() => locale.value)
const isRTL = computed(() => locale.value === 'ar')
</script>

<style scoped>
.active-language {
    @apply bg-gray-100 font-medium;
}

.rtl-button {
    direction: rtl;
}

/* Flag icons - you can add flag-icons CSS library or use emojis */
.fi {
    width: 16px;
    height: 12px;
    background-size: cover;
    display: inline-block;
}

.fi-us {
    background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTIiIHZpZXdCb3g9IjAgMCAxNiAxMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjE2IiBoZWlnaHQ9IjEyIiBmaWxsPSIjQjIyMjM0Ii8+CjxyZWN0IHdpZHRoPSIxNiIgaGVpZ2h0PSIxIiBmaWxsPSJ3aGl0ZSIvPgo8L3N2Zz4K');
}

.fi-sa {
    background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTIiIHZpZXdCb3g9IjAgMCAxNiAxMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjE2IiBoZWlnaHQ9IjEyIiBmaWxsPSIjMDA2QzM1Ii8+Cjx0ZXh0IHg9IjgiIHk9IjciIGZpbGw9IndoaXRlIiBmb250LXNpemU9IjgiIHRleHQtYW5jaG9yPSJtaWRkbGUiPtmE2KcgYWxs2KfZhyDYpdmE2Kcg2KfZhNmE2YfYjDwvdGV4dD4KPC9zdmc+Cg==');
}
</style>
