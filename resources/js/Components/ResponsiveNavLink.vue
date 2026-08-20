<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useRTL } from '@/composables/useRTL';

const props = defineProps({
    href: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
    },
});

const { isRTL } = useRTL();

const classes = computed(() => {
    const baseClasses = 'responsive-nav-link-modern flex items-center w-full px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2';

    if (props.active) {
        return `${baseClasses} responsive-nav-active`;
    }

    return `${baseClasses} responsive-nav-inactive`;
});
</script>

<template>
    <Link
        :href="href"
        :class="[classes, { 'font-cairo': isRTL, 'text-right': isRTL }]"
    >
        <slot />
    </Link>
</template>

<style scoped>
.responsive-nav-link-modern {
    position: relative;
    overflow: hidden;
}

.responsive-nav-active {
    background-color: var(--button);
    color: var(--button-text);
    box-shadow: 0 4px 12px rgba(255, 142, 60, 0.3);
    transform: translateX(4px);
}

.responsive-nav-inactive {
    color: var(--paragraph);
    background-color: transparent;
}

.responsive-nav-inactive:hover {
    background-color: var(--background);
    color: var(--headline);
    transform: translateX(4px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.responsive-nav-link-modern:focus {
    ring-color: var(--button);
}

/* RTL Support */
[dir="rtl"] .responsive-nav-active {
    transform: translateX(-4px);
}

[dir="rtl"] .responsive-nav-inactive:hover {
    transform: translateX(-4px);
}

/* Smooth hover effect */
.responsive-nav-link-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, var(--highlight), var(--button));
    opacity: 0;
    transition: opacity 0.3s ease;
    z-index: -1;
}

.responsive-nav-link-modern:hover::before {
    opacity: 0.1;
}

.responsive-nav-active::before {
    opacity: 1;
}

/* Icon spacing for RTL */
[dir="rtl"] .responsive-nav-link-modern svg {
    margin-left: 0.75rem;
    margin-right: 0;
}
</style>
