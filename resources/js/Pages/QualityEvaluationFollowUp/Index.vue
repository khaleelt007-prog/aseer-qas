<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ $t('quality.follow_up_list') }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ $t('quality.follow_up_list_description') }}
                    </p>
                </div>

                <Link
                    :href="route('quality-evaluations.index')"
                    class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors text-center"
                >
                    {{ $t('quality.back_to_list') }}
                </Link>
            </div>
        </template>

        <div class="qc-container" style="max-width: 100%;">
            <div class="max-w-6xl mx-auto">
                <QualityFilter
                    :countries="countries"
                    :brands="brands"
                    :branches="branches"
                    :initial-filters="filters"
                    @filter-changed="handleFilterChanged"
                />

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="qc-card text-center">
                        <h3 class="text-2xl font-bold text-gray-800">{{ evaluations.total }}</h3>
                        <p class="text-gray-600">{{ $t('quality.total_follow_up_evaluations') }}</p>
                    </div>
                    <div class="qc-card text-center">
                        <h3 class="text-2xl font-bold text-red-600">{{ warningCount }}</h3>
                        <p class="text-gray-600">{{ $t('quality.warning_evaluations') }}</p>
                    </div>
                    <div class="qc-card text-center">
                        <h3 class="text-2xl font-bold text-orange-600">{{ openFollowUpCount }}</h3>
                        <p class="text-gray-600">{{ $t('quality.open_follow_ups') }}</p>
                    </div>
                    <div class="qc-card text-center">
                        <h3 class="text-2xl font-bold text-green-600">{{ solvedFollowUpCount }}</h3>
                        <p class="text-gray-600">{{ $t('quality.solved_follow_ups') }}</p>
                    </div>
                </div>

                <div v-if="allEvaluations.length > 0" class="space-y-4">
                    <div
                        v-for="evaluation in allEvaluations"
                        :key="evaluation.id"
                        class="rounded-xl border p-5 transition-all duration-300 hover:shadow-lg"
                        :class="evaluation.warning_flag ? 'border-red-300 bg-red-50/80' : 'border-gray-200 bg-white'"
                    >
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="flex-1">
                                <div class="flex flex-wrap items-center gap-2 mb-3">
                                    <h3 class="text-lg font-semibold text-gray-900">
                                        {{ evaluation.branch?.localized_name || evaluation.branch?.name }}
                                    </h3>

                                    <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700">
                                        {{ $t('quality.checklist_follow_up') }}
                                    </span>

                                    <span
                                        v-if="evaluation.warning_flag"
                                        class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700"
                                    >
                                        {{ $t('quality.overdue_warning') }}
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-gray-600 mb-4">
                                    <div>
                                        <p>{{ $t('quality.created') }}: {{ formatDate(evaluation.created_at) }}</p>
                                        <p v-if="evaluation.completed_at">{{ $t('quality.completed_on') }}: {{ formatDate(evaluation.completed_at) }}</p>
                                    </div>
                                    <div>
                                        <p>
                                            {{ $t('quality.checklist_score') }}:
                                            <span class="font-semibold text-gray-800">{{ formatScore(evaluation.total_score) }}/{{ formatScore(evaluation.max_score) }}</span>
                                        </p>
                                        <p v-if="evaluation.warning_flagged_at">
                                            {{ $t('quality.warning_raised_at') }}: {{ formatDate(evaluation.warning_flagged_at) }}
                                        </p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                                    <div class="rounded-lg bg-gray-50 p-3 text-center">
                                        <div class="text-lg font-bold text-gray-800">{{ evaluation.bad_answers_count || 0 }}</div>
                                        <div class="text-xs text-gray-500">{{ $t('quality.bad_questions') }}</div>
                                    </div>
                                    <div class="rounded-lg bg-orange-50 p-3 text-center">
                                        <div class="text-lg font-bold text-orange-700">{{ evaluation.open_follow_ups_count || 0 }}</div>
                                        <div class="text-xs text-orange-600">{{ $t('quality.open_follow_ups') }}</div>
                                    </div>
                                    <div class="rounded-lg bg-green-50 p-3 text-center">
                                        <div class="text-lg font-bold text-green-700">{{ evaluation.solved_follow_ups_count || 0 }}</div>
                                        <div class="text-xs text-green-600">{{ $t('quality.solved_follow_ups') }}</div>
                                    </div>
                                    <div class="rounded-lg bg-gray-100 p-3 text-center">
                                        <div class="text-lg font-bold text-gray-700">{{ evaluation.skipped_follow_ups_count || 0 }}</div>
                                        <div class="text-xs text-gray-500">{{ $t('quality.skipped_follow_ups') }}</div>
                                    </div>
                                    <div class="rounded-lg bg-red-50 p-3 text-center">
                                        <div class="text-lg font-bold text-red-700">{{ evaluation.overdue_follow_ups_count || 0 }}</div>
                                        <div class="text-xs text-red-600">{{ $t('quality.overdue_items') }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col gap-2 lg:w-44">
                                <Link
                                    :href="route('quality-evaluation-follow-ups.show', evaluation.id)"
                                    class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors text-center text-sm"
                                >
                                    {{ $t('quality.open_follow_up') }}
                                </Link>

                                <Link
                                    :href="route('quality-evaluations.show', evaluation.id)"
                                    class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-center text-sm"
                                >
                                    {{ $t('quality.view_evaluation') }}
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div ref="scrollSentinel" class="h-4"></div>

                    <div v-if="loadingMore" class="flex justify-center py-6">
                        <svg class="animate-spin h-8 w-8 text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <div v-if="!hasMorePages" class="text-center py-4 text-gray-400 text-sm">
                        {{ $t('quality.no_more_evaluations') }}
                    </div>
                </div>

                <div v-else class="qc-card text-center py-12">
                    <div class="text-gray-400 mb-4">
                        <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">{{ $t('quality.no_follow_up_evaluations') }}</h3>
                    <p class="text-gray-500">{{ $t('quality.no_follow_up_evaluations_description') }}</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import QualityFilter from '@/Components/QualityFilter.vue'
import axios from 'axios'

const props = defineProps({
    evaluations: Object,
    countries: { type: Array, default: () => [] },
    brands: { type: Array, default: () => [] },
    branches: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
})

const page = usePage()
const allEvaluations = ref([...(props.evaluations?.data || [])])
const currentPage = ref(props.evaluations?.current_page || 1)
const lastPage = ref(props.evaluations?.last_page || 1)
const loadingMore = ref(false)
const scrollSentinel = ref(null)
const currentFilters = ref({ ...props.filters })
let observer = null

const hasMorePages = computed(() => currentPage.value < lastPage.value)
const warningCount = computed(() => allEvaluations.value.filter(item => item.warning_flag).length)
const openFollowUpCount = computed(() => allEvaluations.value.reduce((sum, item) => sum + Number(item.open_follow_ups_count || 0), 0))
const solvedFollowUpCount = computed(() => allEvaluations.value.reduce((sum, item) => sum + Number(item.solved_follow_ups_count || 0), 0))

const formatDate = (dateString) => {
    if (!dateString) return '-'
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

const formatScore = (score) => {
    if (score === null || score === undefined || score === '') return 0
    const value = Number(score)
    return Number.isInteger(value) ? value : value.toFixed(1)
}

const handleFilterChanged = (newFilters) => {
    currentFilters.value = { ...newFilters }
    const params = {}

    Object.entries(newFilters).forEach(([key, value]) => {
        if (value) params[key] = value
    })

    router.get(route('quality-evaluation-follow-ups.index'), params, {
        preserveState: true,
        preserveScroll: false,
        onSuccess: (responsePage) => {
            const data = responsePage.props.evaluations
            allEvaluations.value = [...data.data]
            currentPage.value = data.current_page
            lastPage.value = data.last_page
        },
    })
}

const loadMore = async () => {
    if (loadingMore.value || !hasMorePages.value) return

    loadingMore.value = true
    const nextPage = currentPage.value + 1
    const params = { ...currentFilters.value, page: nextPage }

    Object.keys(params).forEach((key) => {
        if (!params[key]) delete params[key]
    })

    try {
        const response = await axios.get(route('quality-evaluation-follow-ups.index'), {
            params,
            headers: {
                'X-Inertia': true,
                'X-Inertia-Version': page.version,
                'X-Inertia-Partial-Data': 'evaluations',
                'X-Inertia-Partial-Component': 'QualityEvaluationFollowUp/Index',
            },
        })

        const data = response.data?.props?.evaluations

        if (data?.data) {
            allEvaluations.value.push(...data.data)
            currentPage.value = data.current_page
            lastPage.value = data.last_page
        }
    } catch (error) {
        console.error('Failed to load more follow-up evaluations:', error)
    } finally {
        loadingMore.value = false
    }
}

const setupObserver = () => {
    if (!scrollSentinel.value) return

    observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting && hasMorePages.value && !loadingMore.value) {
            loadMore()
        }
    }, { rootMargin: '200px' })

    observer.observe(scrollSentinel.value)
}

watch(() => props.evaluations, (newValue) => {
    if (newValue?.current_page === 1) {
        allEvaluations.value = [...newValue.data]
        currentPage.value = newValue.current_page
        lastPage.value = newValue.last_page
    }
})

onMounted(setupObserver)

onUnmounted(() => {
    if (observer) {
        observer.disconnect()
        observer = null
    }
})
</script>