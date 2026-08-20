<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-cyan-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <!-- Background Pattern -->
        <div class="absolute inset-0 overflow-hidden">
            <svg class="absolute left-[max(50%,25rem)] top-0 h-[64rem] w-[128rem] -translate-x-1/2 stroke-gray-200 [mask-image:radial-gradient(64rem_64rem_at_top,white,transparent)]" aria-hidden="true">
                <defs>
                    <pattern id="e813992c-7d03-4cc4-a2bd-151760b470a0" width="200" height="200" x="50%" y="-1" patternUnits="userSpaceOnUse">
                        <path d="M100 200V.5M.5 .5H200" fill="none" />
                    </pattern>
                </defs>
                <rect width="100%" height="100%" stroke-width="0" fill="url(#e813992c-7d03-4cc4-a2bd-151760b470a0)" />
            </svg>
        </div>

        <div class="relative sm:mx-auto sm:w-full sm:max-w-md">
            <!-- Logo -->
            <div class="text-center">
                <Link href="/" class="inline-block">
                    <ApplicationLogo class="h-16 w-16 fill-current text-indigo-600 hover:text-indigo-700 transition duration-200" />
                </Link>
            </div>

            <!-- Card -->
            <div class="mt-8 bg-white py-8 px-6 shadow-xl rounded-xl border border-gray-100 sm:px-10">
                <slot />
            </div>

            <!-- Footer -->
            <div class="mt-8 text-center">
                <p class="text-xs text-gray-500">
                    © {{ new Date().getFullYear() }} Aseer QAS. All rights reserved.
                </p>
            </div>
        </div>
    </div>
</template>
