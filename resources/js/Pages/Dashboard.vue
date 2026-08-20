<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { useRTL } from '@/composables/useRTL';

// Props (locale is passed from the route but handled globally)
defineProps({
    locale: String
});

const { t } = useI18n();
const { isRTL } = useRTL();
</script>

<template>
    <Head :title="t('navigation.dashboard')" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
                :class="{ 'text-right': isRTL }"
            >
                {{ t('navigation.dashboard') }}
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-1 lg:grid-cols-1 gap-6">
       

                    <!-- Quality Evaluations Card -->
                    <div class="overflow-hidden bg-gradient-to-br from-orange-500 to-yellow-500 shadow-sm sm:rounded-lg">
                        <div class="p-6 text-white dashboard-card" :class="{ 'text-right': isRTL }">
                            <h3 class="text-lg font-semibold mb-2 card-title">{{ t('navigation.quality_control') }}</h3>
                            <p class="text-orange-100 mb-4 card-description">{{ t('navigation.quality_control_description') }}</p>
                            <div class="space-y-2">
                                <Link
                                    :href="route('quality-evaluations.create')"
                                    class="block w-full bg-white text-orange-600 text-center py-2 px-4 rounded-lg font-medium hover:bg-orange-50 transition-colors"
                                >
                                    {{ t('navigation.new_evaluation') }}
                                </Link>
                                <Link
                                    :href="route('quality-evaluations.index')"
                                    class="block w-full bg-orange-600 text-white text-center py-2 px-4 rounded-lg font-medium hover:bg-orange-700 transition-colors"
                                >
                                    {{ t('navigation.view_all') }}
                                </Link>
                            </div>
                        </div>
                    </div>

    
                </div>

                <!-- Quick Stats Section -->
                <div class="mt-8">
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div class="p-6" :class="{ 'text-right': isRTL }">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ t('navigation.quick_actions') }}</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-2 gap-4">
                                <Link
                                    :href="route('quality-evaluations.create')"
                                    class="flex items-center p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition-colors group quick-action-item"
                                    :class="{ 'flex-row-reverse': isRTL }"
                                >
                                    <div class="flex-shrink-0 action-icon" :class="{ 'ml-3 mr-0': isRTL, 'mr-3 ml-0': !isRTL }">
                                        <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                    </div>
                                    <div class="action-content" :class="{ 'text-right': isRTL }">
                                        <p class="text-sm font-medium text-gray-900 group-hover:text-orange-800">
                                            {{ t('navigation.create_new_evaluation') }}
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            {{ t('navigation.start_quality_assessment') }}
                                        </p>
                                    </div>
                                </Link>

                                <Link
                                    :href="route('quality-evaluations.index')"
                                    class="flex items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors group quick-action-item"
                                    :class="{ 'flex-row-reverse': isRTL }"
                                >
                                    <div class="flex-shrink-0 action-icon" :class="{ 'ml-3 mr-0': isRTL, 'mr-3 ml-0': !isRTL }">
                                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                    <div class="action-content" :class="{ 'text-right': isRTL }">
                                        <p class="text-sm font-medium text-gray-900 group-hover:text-blue-800">
                                            {{ t('navigation.view_evaluations') }}
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            {{ t('navigation.browse_all_assessments') }}
                                        </p>
                                    </div>
                                </Link>

                                <Link
                                    :href="route('quality-evaluation-follow-ups.index')"
                                    class="flex items-center p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition-colors group quick-action-item"
                                    :class="{ 'flex-row-reverse': isRTL }"
                                >
                                    <div class="flex-shrink-0 action-icon" :class="{ 'ml-3 mr-0': isRTL, 'mr-3 ml-0': !isRTL }">
                                        <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                        </svg>
                                    </div>
                                    <div class="action-content" :class="{ 'text-right': isRTL }">
                                        <p class="text-sm font-medium text-gray-900 group-hover:text-orange-800">
                                            {{ t('navigation.view_follow_ups') }}
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            {{ t('navigation.manage_follow_up_issues') }}
                                        </p>
                                    </div>
                                </Link>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
