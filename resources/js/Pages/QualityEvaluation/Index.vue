<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $t('navigation.quality_evaluations') }}
                </h2>
                <div class="flex gap-2">
                    <Link
                        :href="route('quality-evaluation-follow-ups.index')"
                        class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-colors text-center"
                    >
                        {{ $t('quality.follow_up_list') }}
                    </Link>
                    <Link
                        v-if="canCreate"
                        :href="route('quality-evaluations.create')"
                        class="qc-button inline-block text-center px-6 py-2 w-auto"
                    >
                        {{ $t('quality.create_new') }}
                    </Link>
                </div>
            </div>
        </template>

        <div class="qc-container" style="max-width: 100%;">
            <div class="max-w-6xl mx-auto">
                <!-- Quality Filter -->
                <QualityFilter
                    :countries="countries"
                    :brands="brands"
                    :branches="branches"
                    :initial-filters="filters"
                    @filter-changed="handleFilterChanged"
                />

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="qc-card text-center">
                        <h3 class="text-2xl font-bold text-gray-800">{{ evaluations.total }}</h3>
                        <p class="text-gray-600">{{ $t('quality.total_evaluations') }}</p>
                    </div>
                    <div class="qc-card text-center">
                        <h3 class="text-2xl font-bold text-green-600">{{ completedCount }}</h3>
                        <p class="text-gray-600">{{ $t('quality.completed_evaluations') }}</p>
                    </div>
                    <div class="qc-card text-center">
                        <h3 class="text-2xl font-bold text-yellow-600">{{ draftCount }}</h3>
                        <p class="text-gray-600">{{ $t('quality.draft_evaluations') }}</p>
                    </div>
                </div>

                <!-- Evaluations List -->
                <div v-if="allEvaluations.length > 0" class="space-y-4">
                    <div
                        v-for="evaluation in allEvaluations"
                        :key="evaluation.id"
                        class="qc-card hover:shadow-lg transition-all duration-300"
                    >
                        <div class="flex flex-col md:flex-row md:items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h3 class="qc-title text-lg">{{ $t('quality.control_evaluation') }} {{ evaluation.branch?.localized_name || evaluation.branch?.name }}</h3>
                                    <span 
                                        class="px-3 py-1 rounded-full text-sm font-medium"
                                        :class="evaluation.status === 'completed' 
                                            ? 'bg-green-100 text-green-800' 
                                            : 'bg-yellow-100 text-yellow-800'"
                                    >
                                        {{ evaluation.status === 'completed' ? $t('quality.completed') : $t('quality.draft') }}
                                    </span>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-3">
                                    <div>
                                        <p class="text-sm text-gray-600">
                                            {{ $t('quality.created') }}: {{ formatDate(evaluation.created_at) }}
                                        </p>
                                        <p v-if="evaluation.completed_at" class="text-sm text-gray-600">
                                            {{ $t('quality.completed_on') }}: {{ formatDate(evaluation.completed_at) }}
                                        </p>
                                    </div>
                                    <!-- Regular Evaluation Score -->
                                    <div v-if="evaluation.total_score !== null && evaluation.type !== 'checklist'">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm text-gray-600">{{ $t('quality.total_score') }}:</span>
                                            <span
                                                class="text-lg font-bold"
                                                :class="getScoreColor(evaluation.total_score)"
                                            >
                                                <span v-if="lang === 'ar'" dir="ltr">100/{{ formatScore(evaluation.total_score) }}</span>
                                                <span v-else>
                                                    {{ formatScore(evaluation.total_score) }}/100
                                                </span>
                                            </span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                            <div
                                                class="h-2 rounded-full transition-all duration-500"
                                                :class="getScoreBarColor(evaluation.total_score)"
                                                :style="{ width: evaluation.total_score + '%' }"
                                            ></div>
                                        </div>
                                    </div>

                                    <!-- Checklist Evaluation Score -->
                                    <div v-else-if="evaluation.total_score !== null && evaluation.max_score !== null && evaluation.type === 'checklist'">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm text-gray-600">{{ $t('quality.checklist_score') }}:</span>
                                            <span
                                                class="text-lg font-bold"
                                                :class="getChecklistScoreColor(evaluation.total_score, evaluation.max_score)"
                                            >
                                                <span v-if="lang === 'ar'" dir="ltr">{{ formatScore(evaluation.max_score) }}/{{ formatScore(evaluation.total_score) }}</span>
                                                <span v-else>
                                                    {{ formatScore(evaluation.total_score) }}/{{ formatScore(evaluation.max_score) }}
                                                </span>
                                            </span>
                                        </div>
                                        <div class="w-full bg-gray-200 rounded-full h-2 mt-1">
                                            <div
                                                class="h-2 rounded-full transition-all duration-500"
                                                :class="getChecklistScoreBarColor(evaluation.total_score, evaluation.max_score)"
                                                :style="{ width: (parseInt(evaluation.max_score) > 0 ? (parseInt(evaluation.total_score) / parseInt(evaluation.max_score)) * 100 : 0) + '%' }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="evaluation.comments" class="mb-3">
                                    <p class="text-sm text-gray-700 line-clamp-2">
                                        {{ evaluation.comments }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-col sm:flex-row gap-2 mt-4 md:mt-0 md:ml-4">
                                <Link
                                    v-if="canView"
                                    :href="route('quality-evaluations.show', evaluation.id)"
                                    class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-center text-sm"
                                >
                                    {{ $t('quality.view') }}
                                </Link>
                                <a
                                    v-if="canView && evaluation.pdf_filename"
                                    :href="route('quality-evaluations.download-pdf', evaluation.id)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="px-3 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors text-center text-sm flex items-center gap-1"
                                >
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    PDF
                                </a>
                                <Link
                                    v-if="canEdit"
                                    :href="route('quality-evaluations.edit', evaluation.id)"
                                    class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors text-center text-sm"
                                >
                                    {{ $t('quality.edit') }}
                                </Link>
                                <button
                                    v-if="canDelete"
                                    @click="confirmDelete(evaluation)"
                                    class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors text-sm"
                                >
                                    {{ $t('quality.delete') }}
                                </button>

                                <!-- Show message if no actions are available -->
                                <div v-if="!canView && !canEdit && !canDelete" class="text-center py-2">
                                    <span class="text-gray-500 text-xs">{{ $t('quality.no_actions') || 'No actions available' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Infinite Scroll Sentinel -->
                    <div ref="scrollSentinel" class="h-4"></div>

                    <!-- Loading Spinner -->
                    <div v-if="loadingMore" class="flex justify-center py-6">
                        <svg class="animate-spin h-8 w-8 text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <!-- End of results -->
                    <div v-if="!hasMorePages && allEvaluations.length > 0" class="text-center py-4 text-gray-400 text-sm">
                        {{ $t('quality.no_more_evaluations') || 'No more evaluations to load' }}
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="qc-card text-center py-12">
                    <div class="text-gray-400 mb-4">
                        <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">No evaluations yet</h3>
                    <p class="text-gray-500 mb-6">
                        <span v-if="canCreate">Create your first quality control evaluation to get started.</span>
                        <span v-else>You don't have permission to create evaluations or no evaluations are available for your access level.</span>
                    </p>
                    <Link
                        v-if="canCreate"
                        :href="route('quality-evaluations.create')"
                        class="qc-button inline-block px-8 py-3 w-auto"
                    >
                        Create First Evaluation
                    </Link>
                </div>

            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-lg p-6 max-w-md w-full">
                <h3 class="text-lg font-semibold mb-4">{{ $t('quality.delete_confirmation') }}</h3>
                <p class="text-gray-600 mb-6">
                    {{ $t('quality.delete_warning') }}
                </p>
                <div class="flex gap-3">
                    <button
                        @click="showDeleteModal = false"
                        class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors"
                    >
                        {{ $t('quality.cancel') }}
                    </button>
                    <button
                        @click="deleteEvaluation"
                        class="flex-1 px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors"
                    >
                        {{ $t('quality.confirm_delete') }}
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import QualityFilter from '@/Components/QualityFilter.vue'
import axios from 'axios'

// Props
const props = defineProps({
    evaluations: Object,
    countries: { type: Array, default: () => [] },
    brands: { type: Array, default: () => [] },
    branches: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
})

// i18n
const { locale } = useI18n()

const lang = computed(() => locale.value)

// Get auth data from Inertia page props
const page = usePage()
const auth = computed(() => page.props.auth || {})
const permissions = computed(() => auth.value.permissions || {})
const qualityPermissions = computed(() => permissions.value.quality_evaluations || [])

// Permission computed properties
const canView = computed(() => qualityPermissions.value.includes('view'))
const canEdit = computed(() => qualityPermissions.value.includes('edit'))
const canDelete = computed(() => qualityPermissions.value.includes('delete'))
const canCreate = computed(() => qualityPermissions.value.includes('create'))

// Reactive data
const showDeleteModal = ref(false)
const evaluationToDelete = ref(null)

// Infinite scroll state
const allEvaluations = ref([...props.evaluations.data])
const currentPage = ref(props.evaluations.current_page || 1)
const lastPage = ref(props.evaluations.last_page || 1)
const loadingMore = ref(false)
const scrollSentinel = ref(null)
let observer = null
const currentFilters = ref({ ...props.filters })

const hasMorePages = computed(() => currentPage.value < lastPage.value)

// Computed properties
const completedCount = computed(() => {
    return allEvaluations.value.filter(e => e.status === 'completed').length
})

const draftCount = computed(() => {
    return allEvaluations.value.filter(e => e.status === 'draft').length
})

// Methods
const formatDate = (dateString) => {
    const localeCode = 'en-US'
    return new Date(dateString).toLocaleDateString(localeCode, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getScoreColor = (score) => {
    if (score >= 90) return 'text-green-600'
    if (score >= 80) return 'text-yellow-600'
    if (score >= 70) return 'text-orange-600'
    return 'text-red-600'
}

const getScoreBarColor = (score) => {
    if (score >= 90) return 'bg-green-500'
    if (score >= 80) return 'bg-yellow-500'
    if (score >= 70) return 'bg-orange-500'
    return 'bg-red-500'
}

const getChecklistScoreColor = (totalScore, maxScore) => {
    if (maxScore <= 0) return 'text-gray-600'
    const percentage = (totalScore / maxScore) * 100
    if (percentage >= 90) return 'text-green-600'
    if (percentage >= 80) return 'text-yellow-600'
    if (percentage >= 70) return 'text-orange-600'
    return 'text-red-600'
}

const getChecklistScoreBarColor = (totalScore, maxScore) => {
    if (maxScore <= 0) return 'bg-gray-500'
    const percentage = (totalScore / maxScore) * 100
    if (percentage >= 90) return 'bg-green-500'
    if (percentage >= 80) return 'bg-yellow-500'
    if (percentage >= 70) return 'bg-orange-500'
    return 'bg-red-500'
}

const formatScore = (score) => {
    if (score === null || score === undefined) return 0
    // Check if score has a decimal part
    const num = parseFloat(score)
    if (Number.isInteger(num)) {
        return num
    }
    // Return with decimal places if it's a fraction
    return num.toFixed(1)
}

const confirmDelete = (evaluation) => {
    evaluationToDelete.value = evaluation
    showDeleteModal.value = true
}

const deleteEvaluation = () => {
    if (evaluationToDelete.value) {
        router.delete(route('quality-evaluations.destroy', evaluationToDelete.value.id), {
            onSuccess: () => {
                // Remove from local list
                allEvaluations.value = allEvaluations.value.filter(e => e.id !== evaluationToDelete.value?.id)
            }
        })
        showDeleteModal.value = false
        evaluationToDelete.value = null
    }
}

// Filter handler
const handleFilterChanged = (newFilters) => {
    currentFilters.value = { ...newFilters }

    // Build query params, omitting empty values
    const params = {}
    Object.entries(newFilters).forEach(([key, value]) => {
        if (value) params[key] = value
    })

    // Navigate with Inertia to reload with filters (resets pagination)
    router.get(route('quality-evaluations.index'), params, {
        preserveState: true,
        preserveScroll: false,
        onSuccess: (page) => {
            const evalData = page.props.evaluations
            allEvaluations.value = [...evalData.data]
            currentPage.value = evalData.current_page
            lastPage.value = evalData.last_page
        }
    })
}

// Infinite scroll: load next page
const loadMore = async () => {
    if (loadingMore.value || !hasMorePages.value) return
    loadingMore.value = true

    const nextPage = currentPage.value + 1
    const params = { ...currentFilters.value, page: nextPage }

    // Remove empty params
    Object.keys(params).forEach(key => {
        if (!params[key]) delete params[key]
    })

    try {
        const response = await axios.get(route('quality-evaluations.index'), {
            params,
            headers: { 'X-Inertia': true, 'X-Inertia-Version': page.version, 'X-Inertia-Partial-Data': 'evaluations', 'X-Inertia-Partial-Component': 'QualityEvaluation/Index' }
        })

        const evalData = response.data?.props?.evaluations
        if (evalData && evalData.data) {
            allEvaluations.value.push(...evalData.data)
            currentPage.value = evalData.current_page
            lastPage.value = evalData.last_page
        }
    } catch (error) {
        console.error('Failed to load more evaluations:', error)
    } finally {
        loadingMore.value = false
    }
}

// Intersection Observer for infinite scroll
const setupObserver = () => {
    if (!scrollSentinel.value) return

    observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting && hasMorePages.value && !loadingMore.value) {
            loadMore()
        }
    }, { rootMargin: '200px' })

    observer.observe(scrollSentinel.value)
}

// Sync when Inertia re-renders the component with new props
watch(() => props.evaluations, (newVal) => {
    if (newVal && newVal.current_page === 1) {
        allEvaluations.value = [...newVal.data]
        currentPage.value = newVal.current_page
        lastPage.value = newVal.last_page
    }
})

onMounted(() => {
    setupObserver()
})

onUnmounted(() => {
    if (observer) {
        observer.disconnect()
        observer = null
    }
})
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
