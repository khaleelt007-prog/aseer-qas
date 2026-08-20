<template>
    <div class="application-logo">
        <!-- Modern SVG Logo with fallback -->
        <svg
            class="logo-svg"
            viewBox="0 0 100 100"
            fill="none"
            xmlns="http://www.w3.org/2000/svg"
            :style="{ color: logoColor }"
        >
            <!-- Quality Control Symbol -->
            <circle cx="50" cy="50" r="45" stroke="currentColor" stroke-width="3" fill="none" opacity="0.2"/>
            <circle cx="50" cy="50" r="35" stroke="currentColor" stroke-width="2" fill="none"/>

            <!-- Checkmark -->
            <path
                d="M35 50 L45 60 L65 40"
                stroke="currentColor"
                stroke-width="4"
                stroke-linecap="round"
                stroke-linejoin="round"
                fill="none"
            />

            <!-- Quality Indicators -->
            <circle cx="25" cy="25" r="3" fill="currentColor" opacity="0.6"/>
            <circle cx="75" cy="25" r="3" fill="currentColor" opacity="0.6"/>
            <circle cx="25" cy="75" r="3" fill="currentColor" opacity="0.6"/>
            <circle cx="75" cy="75" r="3" fill="currentColor" opacity="0.6"/>

            <!-- Center highlight -->
            <circle cx="50" cy="50" r="8" fill="currentColor" opacity="0.1"/>
        </svg>

        <!-- Fallback image if SVG is not supported -->
        <img
            src="/images/logo.png"
            alt="Aseer QAS Logo"
            class="logo-fallback"
            style="display: none;"
            @error="showFallback"
        />
    </div>
</template>

<script setup>
const props = defineProps({
    logoColor: {
        type: String,
        default: 'var(--button)'
    }
});

const showFallback = () => {
    // Show fallback image if SVG fails or logo.png exists
    const svg = document.querySelector('.logo-svg');
    const fallback = document.querySelector('.logo-fallback');
    if (svg && fallback) {
        svg.style.display = 'none';
        fallback.style.display = 'block';
    }
};
</script>

<style scoped>
.application-logo {
    display: inline-block;
    position: relative;
}

.logo-svg {
    width: 100%;
    height: 100%;
    transition: all 0.3s ease;
}

.logo-svg:hover {
    transform: rotate(5deg) scale(1.05);
}

.logo-fallback {
    width: 100%;
    height: 100%;
    object-fit: contain;
    transition: all 0.3s ease;
}

.logo-fallback:hover {
    transform: scale(1.05);
}

/* Animation for the checkmark */
@keyframes checkmark {
    0% {
        stroke-dasharray: 0 50;
    }
    100% {
        stroke-dasharray: 50 0;
    }
}

.logo-svg path {
    animation: checkmark 2s ease-in-out infinite alternate;
}

/* Responsive sizing */
@media (max-width: 640px) {
    .logo-svg:hover {
        transform: rotate(3deg) scale(1.03);
    }
}
</style>
