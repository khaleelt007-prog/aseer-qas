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
    const baseClasses = 'nav-link-modern inline-flex items-center px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2';

    if (props.active) {
        return `${baseClasses} nav-link-active`;
    }

    return `${baseClasses} nav-link-inactive`;
});
</script>

<template>
    <Link
        :href="href"
        :class="[classes, { 'font-cairo': isRTL }]"
    >
        <slot />
    </Link>
</template>

<style scoped>
.nav-link-modern {
    position: relative;
    overflow: hidden;
}

.nav-link-active {
    background-color: var(--button);
    color: var(--button-text);
    box-shadow: 0 4px 12px rgba(255, 142, 60, 0.3);

}

.nav-link-inactive {
    color: var(--paragraph);
    background-color: transparent;
}

.nav-link-inactive:hover {
    background-color: var(--background);
    color: var(--headline);

    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.nav-link-modern:focus {
    ring-color: var(--button);
}

/* Smooth hover effect */
.nav-link-modern::before {
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

.nav-link-modern:hover::before {
    opacity: 0.1;
}

.nav-link-active::before {
    opacity: 1;
}
</style>
