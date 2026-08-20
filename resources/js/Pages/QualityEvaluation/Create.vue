<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $t('quality.quality_control_evaluation') }}
            </h2>
        </template>

        <div class="qc-container">
            <form @submit.prevent="submitForm" class="max-w-4xl mx-auto">
                <!-- Header Card -->
                <div class="qc-card">
                    <h1 class="qc-title">{{ $t('quality.quality_control_scoring') }}</h1>
                    <p class="qc-subtitle">
                        {{ $t('quality.complete_evaluation_description') }}
                    </p>
                    
                    <!-- Progress Bar (for evaluation items only, shown after branch selection) -->
                    <div v-if="form.branch_id && formType === 'REGULAR'" class="qc-progress-bar">
                        <div
                            class="qc-progress-fill"
                            :style="{ width: progressPercentage + '%' }"
                        ></div>
                    </div>
                    <p v-if="form.branch_id && formType === 'REGULAR'" class="text-sm text-gray-600 text-center">
                        {{ completedItems }}/{{ $props.evaluationItems.length }} {{ $t('quality.items_completed') }}
                    </p>
                </div>

                <!-- Progressive Selection: Country, Brand, Branch -->
                <div class="qc-card">
                    <!-- Show message if no branches are available -->
                    <div v-if="$props.branches.length === 0" class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-yellow-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-yellow-800 text-sm">
                                {{ $t('quality.no_branches_available') || 'No branches are available for your access level. Please contact your administrator.' }}
                            </span>
                        </div>
                    </div>

                    <!-- Progressive Dropdowns -->
                    <div v-else>
                        <!-- Country Selection -->
                        <div class="mb-6">
                            <h3 class="qc-title text-lg mb-4">{{ $t('quality.country') }}</h3>
                            <select
                                ref="countrySelect"
                                v-model="form.country_id"
                                class="qc-select"
                                required
                            >
                                <option value="" disabled>{{ $t('quality.select_country') }}</option>
                                <option
                                    v-for="country in $props.countries"
                                    :key="country.id"
                                    :value="country.id"
                                >
                                    {{ country.localized_name }}
                                </option>
                            </select>
                            <div v-if="form.errors.country_id" class="text-red-600 text-sm mt-2">
                                {{ form.errors.country_id }}
                            </div>
                        </div>

                        <!-- Brand Selection (shown after country selection) -->
                        <div v-if="form.country_id" class="mb-6">
                            <h3 class="qc-title text-lg mb-4">{{ $t('quality.brand') }}</h3>
                            <select
                                ref="brandSelect"
                                v-model="form.brand_id"
                                class="qc-select"
                                required
                            >
                                <option value="" disabled>{{ $t('quality.select_brand') }}</option>
                                <option
                                    v-for="brand in filteredBrands"
                                    :key="brand.id"
                                    :value="brand.id"
                                >
                                    {{ brand.localized_name }}
                                </option>
                            </select>
                            <div v-if="form.errors.brand_id" class="text-red-600 text-sm mt-2">
                                {{ form.errors.brand_id }}
                            </div>
                        </div>

                        <!-- Branch Selection (shown after brand selection) -->
                        <div v-if="form.brand_id" class="mb-6">
                            <h3 class="qc-title text-lg mb-4">{{ $t('quality.branch') }}</h3>
                            <select
                                ref="branchSelect"
                                v-model="form.branch_id"
                                class="qc-select"
                                required
                            >
                                <option value="" disabled>{{ $t('quality.select_branch') }}</option>
                                <option
                                    v-for="branch in filteredBranches"
                                    :key="branch.id"
                                    :value="branch.id"
                                >
                                    {{ branch.localized_name }}
                                </option>
                            </select>
                            <div v-if="form.errors.branch_id" class="text-red-600 text-sm mt-2">
                                {{ form.errors.branch_id }}
                            </div>
                        </div>
                    </div>

                    <!-- Template loading indicator -->
                    <div v-if="templateLoading" class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-center">
                            <svg class="animate-spin h-5 w-5 text-blue-600 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span class="text-blue-700 text-sm">{{ $t('quality.loading_template') || 'Loading template...' }}</span>
                        </div>
                    </div>

                    <!-- Template error -->
                    <div v-if="templateError" class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <span class="text-red-700 text-sm">{{ templateError }}</span>
                        </div>
                    </div>
                </div>

                <!-- Checklist Template Form (shown only if form_type is CHECKLIST) -->
                <QcChecklistTemplate
                    v-if="currentTemplate && !templateLoading && formType === 'CHECKLIST'"
                    ref="checklistTemplateRef"
                    :template="currentTemplate"
                    :disabled="processing"
                    @update:answers="(answers) => form.answers = answers"
                    @photos-updated="handleChecklistPhotosUpdated"
                />

                <!-- Evaluation Items (shown when branch is selected and form_type is REGULAR) -->
                <template v-if="form.branch_id && formType === 'REGULAR' && !templateLoading">
                <div
                    v-for="(item, index) in $props.evaluationItems"
                    :key="item.id"
                    :ref="el => itemRefs[index] = el"
                    class="qc-card"
                    :class="{ 'active': currentItemIndex === index }"
                >
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="qc-title text-lg">{{ item.localized_title || item.title }}</h3>
                        <span class="qc-weight-badge">{{ item.weight }}%</span>
                    </div>
                    
                    <div class="qc-score-input">
                        <input
                            :ref="el => scoreInputRefs[index] = el"
                            v-model.number="form[item.id + '_achieved']"
                            type="number"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            min="0"
                            :max="form[item.id + '_max']"
                            placeholder="0"
                            @input="handleAchievedScoreInput(item.id, index)"
                            @keydown.enter="handleEnterKey(item.id, index)"
                            @focus="currentItemIndex = index"
                        />
                        <span class="text-xl font-bold text-gray-600">/</span>
                        <input
                            v-model.number="form[item.id + '_max']"
                            type="number"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            min="1"
                            placeholder="30"
                            readonly
                            class="bg-gray-100 cursor-not-allowed text-sm"
                        />
                        <span class="text-sm text-gray-600 ml-2">{{ $t('quality.points') || 'points' }}</span>
                    </div>
                    
                    <div v-if="form[item.id + '_achieved'] !== null && form[item.id + '_max']" class="mt-2">
                        <div class="text-sm text-gray-600">
                            Score: {{ calculateItemPercentage(item.id) }}% 
                            ({{ calculateWeightedScore(item.id) }}/{{ item.weight }} weighted points)
                        </div>
                    </div>
                    
                    <div v-if="form.errors[item.id + '_achieved']" class="text-red-600 text-sm mt-2">
                        {{ form.errors[item.id + '_achieved'] }}
                    </div>
                </div>
                </template>

                <!-- Total Score Display (for evaluation items only, shown after branch selection) -->
                <div
                    v-if="form.branch_id && formType === 'REGULAR' && totalScore > 0"
                    class="qc-total-score"
                    :class="scoreDisplayData.class"
                    :aria-label="scoreDisplayData.ariaLabel"
                >
                    <div v-html="getScoreIcon(scoreDisplayData.icon)"></div>
                    <h3>{{ finalTotalScore.toFixed(1) }}/100</h3>
                    <p>{{ $t('quality.total_score') }}</p>
                    <div v-if="form.extra_points !== 0" class="text-sm mt-1 opacity-75">
                        Base: {{ totalScore.toFixed(1) }} {{ form.extra_points > 0 ? '+' : '' }}{{ form.extra_points }}
                    </div>
                </div>

                <!-- Extra Points Section (for evaluation items only, shown after branch selection) -->
                <div v-if="form.branch_id && formType === 'REGULAR'" class="qc-card" ref="extraPointsSection">
                    <h3 class="qc-title text-lg mb-4">{{ $t('quality.extra_points') }}</h3>
                    <p class="text-sm text-gray-600 mb-4">{{ $t('quality.extra_points_description') }}</p>

                    <div class="qc-score-input">
                        <label for="extra_points" class="block text-sm font-medium text-gray-700 mb-2">
                            {{ $t('quality.bonus_penalty_points') }}
                        </label>
                        <select
                            id="extra_points"
                            ref="extraPointsSelect"
                            v-model.number="form.extra_points"
                            class="qc-input w-full max-w-xs"
                            @change="handleExtraPointsChange"
                        >
                            <option value="5">+5</option>
                            <option value="4">+4</option>
                            <option value="3">+3</option>
                            <option value="2">+2</option>
                            <option value="1">+1</option>
                            <option value="0" selected>0</option>
                            <option value="-1">-1</option>
                            <option value="-2">-2</option>
                            <option value="-3">-3</option>
                            <option value="-4">-4</option>
                            <option value="-5">-5</option>
                        </select>
                    </div>

                    <div v-if="form.extra_points !== 0" class="mt-3 p-3 rounded-lg" :class="{
                        'bg-green-50 border border-green-200': form.extra_points > 0,
                        'bg-red-50 border border-red-200': form.extra_points < 0
                    }">
                        <div class="text-sm" :class="{
                            'text-green-700': form.extra_points > 0,
                            'text-red-700': form.extra_points < 0
                        }">
                            <span v-if="form.extra_points > 0">
                                ✓ Adding {{ form.extra_points }} bonus points to final score
                            </span>
                            <span v-else>
                                ⚠ Subtracting {{ Math.abs(form.extra_points) }} points from final score
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Photo Documentation Section (shown after branch selection) -->
                <div v-if="form.branch_id" class="qc-card">
                    <h3 class="qc-title text-lg mb-4">{{ $t('quality.photo_documentation') || 'Photo Documentation' }}</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        {{ $t('quality.photo_description') || 'Capture photos to document your evaluation findings' }}
                    </p>

                    <PhotoCapture
                        @photo-captured="handlePhotoCaptured"
                        :disabled="processing"
                    />

                    <PhotoGallery
                        :photos="capturedPhotos"
                        @photo-deleted="handlePhotoDeleted"
                        :disabled="processing"
                    />
                </div>

                <!-- Comments Section (shown after branch selection) -->
                <div v-if="form.branch_id" class="qc-card" ref="commentsSection">
                    <h3 class="qc-title text-lg mb-4">{{ $t('quality.additional_comments') }}</h3>
                    <textarea
                        ref="commentsTextarea"
                        v-model="form.comments"
                        class="qc-textarea"
                        :placeholder="$t('quality.optional_comments')"
                        maxlength="2000"
                    ></textarea>
                    <div class="text-sm text-gray-500 mt-2 text-right">
                        {{ form.comments?.length || 0 }}/2000 characters
                    </div>
                </div>

                <!-- Action Buttons (shown after branch selection) -->
                <div v-if="form.branch_id" class="qc-card">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <button
                            type="submit"
                            name="status"
                            value="draft"
                            class="qc-button bg-gray-500 hover:bg-gray-600"
                            :disabled="processing"
                        >
                            {{ processing ? $t('quality.saving') || 'Saving...' : $t('quality.save_draft') }}
                        </button>
                        <button
                            ref="submitButton"
                            type="submit"
                            name="status"
                            value="completed"
                            class="qc-button"
                            :disabled="processing || !canComplete"
                        >
                            {{ processing ? $t('quality.submitting') || 'Submitting...' : $t('quality.complete_evaluation') }}
                        </button>
                    </div>

                    <div v-if="!canComplete" class="text-sm text-gray-600 mt-2 text-center">
                        <span v-if="$props.branches.length === 0">{{ $t('quality.no_branches_available') || 'No branches are available for your access level.' }}</span>
                        <span v-else-if="!form.branch_id">{{ $t('quality.branch_required') }}</span>
                        <span v-else-if="formType === 'REGULAR' && !currentTemplate">{{ $t('quality.complete_all_items') || 'Please complete all evaluation items to submit' }}</span>
                        <span v-else-if="templateLoading">{{ $t('quality.loading_form') || 'Loading form...' }}</span>
                    </div>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, nextTick, onMounted, onUnmounted, watch } from 'vue'
import { useForm, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import PhotoCapture from '@/Components/PhotoCapture.vue'
import PhotoGallery from '@/Components/PhotoGallery.vue'
import QcChecklistTemplate from '@/Components/QcChecklistTemplate.vue'
import SlimSelect from 'slim-select'
import 'slim-select/styles'

// Props
const props = defineProps({
    evaluationItems: Array,
    branches: Array,
    countries: Array,
    brands: Array
})

// i18n setup for component
const i18n = useI18n()

// Template loading state
const templateLoading = ref(false)
const templateError = ref(null)
const currentTemplate = ref(null)
const checklistTemplateRef = ref(null)
const formType = ref(null) // 'CHECKLIST' or 'REGULAR'

// Form data - dynamically create form fields based on evaluation items
const createFormData = () => {
    const formData = {
        country_id: null,
        brand_id: null,
        branch_id: null,
        title: 'Quality Control Evaluation',
        comments: '',
        status: 'draft',
        extra_points: 0,
        is_checklist_template: false,
        answers: {}
    }

    // Add dynamic fields for each evaluation item
    props.evaluationItems.forEach(item => {
        formData[item.id + '_achieved'] = null
        formData[item.id + '_max'] = item.weight
    })

    return formData
}

const form = useForm(createFormData())

// Load template for selected branch
const loadTemplate = async (branchId) => {
    if (!branchId) {
        currentTemplate.value = null
        formType.value = null
        templateError.value = null
        return
    }

    templateLoading.value = true
    templateError.value = null

    try {
        const response = await fetch(`/api/quality-evaluations/get-template?branch_id=${branchId}`)

        if (response.ok) {
            const data = await response.json()

            // Extract form_type from response
            const responseFormType = data.form_type || 'CHECKLIST'
            formType.value = responseFormType

            // If form_type is CHECKLIST, set currentTemplate and is_checklist_template
            if (responseFormType === 'CHECKLIST') {
                currentTemplate.value = data
                form.is_checklist_template = true
            } else {
                // For REGULAR form type, don't set currentTemplate (use evaluation items)
                currentTemplate.value = null
                form.is_checklist_template = false
            }
        } else if (response.status === 404) {
            // No template found, use evaluation items with REGULAR form type
            currentTemplate.value = null
            formType.value = 'REGULAR'
            form.is_checklist_template = false
        } else {
            throw new Error('Failed to load template')
        }
    } catch (error) {
        console.error('Error loading template:', error)
        templateError.value = 'Failed to load template'
        currentTemplate.value = null
        formType.value = 'REGULAR'
        form.is_checklist_template = false
    } finally {
        templateLoading.value = false
    }
}

// Reactive data
const currentItemIndex = ref(0)
const itemRefs = ref([])
const scoreInputRefs = ref([])
const processing = ref(false)
const extraPointsSection = ref(null)
const extraPointsSelect = ref(null)
const commentsSection = ref(null)
const commentsTextarea = ref(null)
const submitButton = ref(null)
const countrySelect = ref(null)
const brandSelect = ref(null)
const branchSelect = ref(null)
let countrySlimSelectInstance = null
let brandSlimSelectInstance = null
let slimSelectInstance = null
let extraPointsSlimSelectInstance = null

// Photo management
const capturedPhotos = ref([])

// Mobile full-screen behavior
const isMobileFullscreen = ref(false)
const originalBodyOverflow = ref('')

// Computed properties for progressive filtering
const filteredBrands = computed(() => {
    if (!form.country_id) return []

    // Convert country_id to number for comparison
    const countryId = Number(form.country_id)

    // Get all branches for the selected country
    const branchesForCountry = props.branches.filter(b => Number(b.country_id) === countryId)

    // Extract unique brand IDs from those branches
    const brandIds = [...new Set(branchesForCountry.map(b => b.brand_id))]

    // Return brands that have branches in the selected country
    return props.brands.filter(brand => brandIds.includes(brand.id))
})

const filteredBranches = computed(() => {
    if (!form.brand_id) return []

    // Convert IDs to numbers for comparison
    const countryId = Number(form.country_id)
    const brandId = Number(form.brand_id)

    // Get branches for the selected country and brand
    return props.branches.filter(b =>
        Number(b.country_id) === countryId &&
        Number(b.brand_id) === brandId
    )
})

// Computed properties
const completedItems = computed(() => {
    return props.evaluationItems.filter(item =>
        form[item.id + '_achieved'] !== null &&
        form[item.id + '_achieved'] !== ''
    ).length
})

const progressPercentage = computed(() => {
    return (completedItems.value / props.evaluationItems.length) * 100
})

const canComplete = computed(() => {
    // Check if branches are available
    if (props.branches.length === 0) {
        return false
    }

    // Branch must be selected
    if (!form.branch_id) {
        return false
    }

    // If form type is CHECKLIST and template is loaded, only require branch selection
    if (formType.value === 'CHECKLIST' && currentTemplate.value) {
        return true
    }

    // For REGULAR form type or when no template, require all items to be scored
    return props.evaluationItems.every(item =>
        form[item.id + '_achieved'] !== null &&
        form[item.id + '_achieved'] !== ''
    )
})

const totalScore = computed(() => {
    // Check for zero override conditions first
    let zeroOverrideItem = null
    for (const item of props.evaluationItems) {
        const achieved = form[item.id + '_achieved']

        // Check if this item has zero override enabled and achieved score is 0
        // Zero override takes precedence over exclude_from_score
        if (item.overwrite_total_score_if_zero && achieved === 0) {
            zeroOverrideItem = item
            break
        }
    }

    // Normal calculation
    let total = 0
    props.evaluationItems.forEach(item => {
        // If we have a zero override item, skip it from calculation
        if (zeroOverrideItem && item.id === zeroOverrideItem.id) {
            return
        }

        const achieved = form[item.id + '_achieved']

        // Skip items that are excluded from score calculation only if score > 0
        if (item.exclude_from_score && achieved > 0) {
            return
        }

        const max = form[item.id + '_max']
        if (achieved !== null && max > 0) {
            const percentage = (achieved / max) * 100
            total += (percentage * item.weight) / 100
        }
    })

    // If we found a zero override item, subtract 60 points from the calculated total
    if (zeroOverrideItem) {
        total -= 60
    }

    return total
})

const finalTotalScore = computed(() => {
    const baseScore = totalScore.value

    // With the new overwrite_total_score_if_zero behavior:
    // - The base score already includes the -60 penalty when applicable
    // - Extra points are added to the adjusted score
    // - Final score is clamped between 0 and 100

    const extraPoints = form.extra_points || 0
    const finalScore = baseScore + extraPoints

    // Ensure the final score doesn't go below 0 or above 100
    return Math.max(0, Math.min(100, finalScore))
})

// Computed property for score styling and icon
const scoreDisplayData = computed(() => {
    const score = finalTotalScore.value

    if (score < 50) {
        return {
            class: 'danger',
            icon: 'warning',
            ariaLabel: 'Low quality score - needs improvement'
        }
    } else if (score < 70) {
        return {
            class: 'neutral',
            icon: 'info',
            ariaLabel: 'Neutral quality score - room for improvement'
        }
    } else if (score < 85) {
        return {
            class: 'warning',
            icon: 'caution',
            ariaLabel: 'Good quality score - approaching excellence'
        }
    } else {
        return {
            class: 'success',
            icon: 'success',
            ariaLabel: 'Excellent quality score'
        }
    }
})

// Watch for changes and implement auto-selection logic
// Auto-select country if only one exists
watch(() => props.countries, (countries) => {
    if (countries && countries.length === 1 && !form.country_id) {
        form.country_id = countries[0].id
    }
}, { immediate: true })

// Auto-select brand if only one exists for the selected country
watch(() => filteredBrands.value, (brands) => {
    if (brands && brands.length === 1 && !form.brand_id) {
        form.brand_id = brands[0].id
    } else if (brands && brands.length === 0) {
        // Reset brand if no brands available for selected country
        form.brand_id = null
    }

    // Rebuild SlimSelect for brand dropdown to reflect filtered options
    if (brandSlimSelectInstance) {
        nextTick(() => {
            brandSlimSelectInstance.setData(brands.map(b => ({
                text: b.localized_name,
                value: b.id
            })))
        })
    }
})

// Auto-select branch if only one exists for the selected brand
watch(() => filteredBranches.value, (branches) => {
    if (branches && branches.length === 1 && !form.branch_id) {
        form.branch_id = branches[0].id
    } else if (branches && branches.length === 0) {
        // Reset branch if no branches available for selected brand
        form.branch_id = null
    }

    // Rebuild SlimSelect for branch dropdown to reflect filtered options
    if (slimSelectInstance) {
        nextTick(() => {
            slimSelectInstance.setData(branches.map(b => ({
                text: b.localized_name,
                value: b.id
            })))
        })
    }
})

// Watch for branch changes and load template
watch(() => form.branch_id, (newBranchId) => {
    if (newBranchId) {
        loadTemplate(newBranchId)
    }
})

// Method to get SVG icon based on score
const getScoreIcon = (iconType) => {
    const icons = {
        warning: `<svg class="score-icon" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" />
        </svg>`,
        info: `<svg class="score-icon" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm8.706-1.442c1.146-.573 2.437.463 2.126 1.706l-.709 2.836.042-.02a.75.75 0 01.67 1.34l-.04.022c-1.147.573-2.438-.463-2.127-1.706l.71-2.836-.042.02a.75.75 0 11-.671-1.34l.041-.022zM12 9a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" />
        </svg>`,
        caution: `<svg class="score-icon" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6a.75.75 0 001.5 0V6zM12 17.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" />
        </svg>`,
        success: `<svg class="score-icon" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
        </svg>`
    }
    return icons[iconType] || icons.info
}

// Methods
// Mobile detection utility
const isMobileViewport = () => {
    return window.innerWidth < 768
}

// Mobile full-screen SlimSelect behavior
const enableMobileFullscreen = (slimSelectElement) => {

    if (!isMobileViewport()) return
  
    isMobileFullscreen.value = true

    // Store original body overflow
    originalBodyOverflow.value = document.body.style.overflow

    // Prevent body scrolling
    document.body.style.overflow = 'hidden'

    // Add full-screen class to SlimSelect content
    const ssContent = slimSelectElement;
    if (ssContent) {
        ssContent.classList.add('slim-fullscreen')

    //remove max-height: 200px; from .ss-content .ss-list
    ssContent.querySelector('.ss-list').style.maxHeight = 'none'
    document.querySelector('.ss-search > input').blur();
        // Add close button click handler
        const handleCloseClick = (event) => {
            // Check if click is on the close button (pseudo-element area)
            const closeButtonArea = {
                top: 16, // 1rem
                right: 16, // 1rem
                width: 40, // 2.5rem
                height: 40 // 2.5rem
            }

            // For RTL, adjust position
            const isRTL = document.documentElement.dir === 'rtl'
            const clickX = event.clientX
            const clickY = event.clientY

            let inCloseButton = false
            if (isRTL) {
                inCloseButton = clickX >= closeButtonArea.top &&
                              clickX <= (closeButtonArea.top + closeButtonArea.width) &&
                              clickY >= closeButtonArea.top &&
                              clickY <= (closeButtonArea.top + closeButtonArea.height)
            } else {
                inCloseButton = clickX >= (window.innerWidth - closeButtonArea.right - closeButtonArea.width) &&
                              clickX <= (window.innerWidth - closeButtonArea.right) &&
                              clickY >= closeButtonArea.top &&
                              clickY <= (closeButtonArea.top + closeButtonArea.height)
            }

            if (inCloseButton) {
                // Find the corresponding SlimSelect instance and close it
                if (slimSelectInstance && slimSelectElement.contains(slimSelectInstance.select.element)) {
                    slimSelectInstance.close()
                } else if (extraPointsSlimSelectInstance && slimSelectElement.contains(extraPointsSlimSelectInstance.select.element)) {
                    extraPointsSlimSelectInstance.close()
                }
            }
        }

        // Add event listener for close button
        ssContent.addEventListener('click', handleCloseClick)

        // Store the handler for cleanup
        ssContent._closeHandler = handleCloseClick
    }
}

const disableMobileFullscreen = (slimSelectElement) => {
    if (!isMobileViewport()) return

    isMobileFullscreen.value = false

    // Restore body overflow
    document.body.style.overflow = originalBodyOverflow.value

    // Remove full-screen class from SlimSelect content
    const ssContent = slimSelectElement.querySelector('.ss-content')
    if (ssContent) {
        ssContent.classList.remove('slim-fullscreen')

    ssContent.querySelector('.ss-list').style.maxHeight = '200px'

        // Clean up event listener
        if (ssContent._closeHandler) {
            ssContent.removeEventListener('click', ssContent._closeHandler)
            delete ssContent._closeHandler
        }
    }
}

const calculateItemPercentage = (itemId) => {
    const achieved = form[itemId + '_achieved']
    const max = form[itemId + '_max']
    if (achieved !== null && max > 0) {
        return ((achieved / max) * 100).toFixed(1)
    }
    return 0
}

const calculateWeightedScore = (itemId) => {
    const achieved = form[itemId + '_achieved']
    const max = form[itemId + '_max']
    const item = props.evaluationItems.find(i => i.id === itemId)
    if (achieved !== null && max > 0 && item) {
        const percentage = (achieved / max) * 100
        return ((percentage * item.weight) / 100).toFixed(1)
    }
    return 0
}

const handleAchievedScoreInput = async (itemId, index) => {
    // Cap the achieved score to the maximum value
    const maxValue = form[itemId + '_max']
    const achievedValue = form[itemId + '_achieved']

    if (achievedValue !== null && achievedValue > maxValue) {
        form[itemId + '_achieved'] = maxValue
    }

    // Auto-scroll to next item after a short delay if value is entered
    if (form[itemId + '_achieved'] !== null && form[itemId + '_achieved'] !== '') {
        setTimeout(() => {
            progressToNext(index)
        }, 300)
    }
}

const handleEnterKey = (itemId, index) => {
    // Immediate progression on Enter key press
    if (form[itemId + '_achieved'] !== null && form[itemId + '_achieved'] !== '') {
        progressToNext(index)
    }
}

const handleExtraPointsChange = () => {
    // Auto-scroll to comments after selecting extra points
    setTimeout(() => {
        focusComments()
    }, 300)
}

const progressToNext = (currentIndex) => {
    if (currentIndex < props.evaluationItems.length - 1) {
        // Move to next evaluation item
        scrollToItemAndFocus(currentIndex + 1)
    } else {
        // All evaluation items completed, move to extra points
        focusExtraPoints()
    }
}

const focusExtraPoints = () => {
    nextTick(() => {
        if (extraPointsSection.value) {
            extraPointsSection.value.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            })
        }

        // Focus on the extra points select after scrolling
        setTimeout(() => {
            if (extraPointsSelect.value) {
                extraPointsSelect.value.focus()
            }
        }, 400)
    })
}

const scrollToItemAndFocus = (index) => {
    currentItemIndex.value = index
    nextTick(() => {
        // Scroll to the item
        if (itemRefs.value[index]) {
            itemRefs.value[index].scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            })
        }

        // Focus on the score input field after scrolling
        setTimeout(() => {
            if (scoreInputRefs.value[index]) {
                scoreInputRefs.value[index].focus()
            }
        }, 400) // Wait for scroll animation to complete
    })
}

const focusComments = () => {
    nextTick(() => {
        if (commentsSection.value) {
            commentsSection.value.scrollIntoView({
                behavior: 'smooth',
                block: 'center'
            })
        }

        // Focus on the comments textarea after scrolling
        setTimeout(() => {
            if (commentsTextarea.value) {
                commentsTextarea.value.focus()
            }
        }, 400)
    })
}

// Photo management methods
const handlePhotoCaptured = (photoData) => {
    // Add the captured photo to our array (for regular evaluations)
    // photoData now contains id (photo ID from server) instead of file
    capturedPhotos.value.push({
        id: photoData.id, // Photo ID from server
        url: photoData.url, // URL to fetch the photo
        preview: photoData.preview,
        timestamp: photoData.timestamp || new Date().toISOString(),
        section_id: photoData.section_id || null, // No section for regular evaluations
        isUploaded: photoData.isUploaded || false // Flag indicating photo is already uploaded
    })
}

const handlePhotoDeleted = (photoId) => {
    // Remove the photo from our array
    const index = capturedPhotos.value.findIndex(photo => photo.id === photoId)
    if (index !== -1) {
        // Revoke the object URL to free memory
        URL.revokeObjectURL(capturedPhotos.value[index].preview)
        capturedPhotos.value.splice(index, 1)
    }
}

// Handle photos from checklist template sections
const handleChecklistPhotosUpdated = (sectionPhotos) => {
    // Replace all section-based photos with the updated list
    const regularPhotos = capturedPhotos.value.filter(p => p.section_id === null)
    capturedPhotos.value = [...regularPhotos, ...sectionPhotos]
}

const submitForm = (event) => {
    processing.value = true
    const status = event.submitter.value
    form.status = status

    // Validate checklist template if present and form_type is CHECKLIST
    if (formType.value === 'CHECKLIST' && currentTemplate.value && checklistTemplateRef.value) {
        if (!checklistTemplateRef.value.validate()) {
            processing.value = false
            return
        }
    }

    // Create FormData object
    const formData = new FormData()

    // Add all form fields to FormData
    Object.keys(form.data()).forEach(key => {
        const value = form[key]
        if (value !== null && value !== undefined) {
            // Handle answers object specially
            if (key === 'answers' && typeof value === 'object') {
                Object.keys(value).forEach(questionId => {
                    const rawAnswer = value[questionId]
                    const answerValue = Array.isArray(rawAnswer) ? JSON.stringify(rawAnswer) : (rawAnswer ?? '')
                    formData.append(`answers[${questionId}]`, answerValue)
                })
            } else {
                formData.append(key, value)
            }
        }
    })

    // Add photo IDs to FormData (photos are already uploaded)
    const photoIds = capturedPhotos.value.map(photo => photo.id)
    if (photoIds.length > 0) {
        formData.append('photo_ids', JSON.stringify(photoIds))
    }

    // Add photo sections if any
    const photoSections = capturedPhotos.value
        .map((photo, index) => photo.section_id ? { index, section_id: photo.section_id } : null)
        .filter(item => item !== null)

    if (photoSections.length > 0) {
        const photoSectionsArray = new Array(capturedPhotos.value.length)
        photoSections.forEach(item => {
            photoSectionsArray[item.index] = item.section_id
        })
        formData.append('photo_sections', JSON.stringify(photoSectionsArray))
    }

    // Debug: Log FormData contents
    console.log('FormData contents:')
    for (let [key, value] of formData.entries()) {
        console.log(key, value)
    }

    console.log('Total photos being sent:', photoIds.length)

    // Use router.post with FormData
    router.post(route('quality-evaluations.store'), formData, {
        onFinish: () => {
            processing.value = false
        },
        onError: (errors) => {
            // Handle validation errors
            form.setError(errors)
        }
    })
}

// Initialize Slim Select for all three dropdowns
const initializeSlimSelect = () => {
    // Initialize Country Select
    if (countrySelect.value && !countrySlimSelectInstance) {
        countrySlimSelectInstance = new SlimSelect({
            select: countrySelect.value,
            settings: {
                searchText: i18n.t('quality.search_countries') || 'Search countries...',
                searchPlaceholder: i18n.t('quality.search_countries') || 'Search countries...',
                searchHighlight: true,
                closeOnSelect: true,
                allowDeselect: false,
                placeholderText: i18n.t('quality.select_country'),
            },
            events: {
                afterChange: (newVal) => {
                    form.country_id = newVal.length > 0 ? newVal[0].value : null
                    // Reset brand and branch when country changes
                    form.brand_id = null
                    form.branch_id = null
                    // Clear validation errors
                    if (form.country_id && form.errors.country_id) {
                        form.clearErrors('country_id')
                    }
                },
                beforeOpen: () => {
                    const slimSelectElement = document.querySelector('.ss-content')
                    if (slimSelectElement) {
                        enableMobileFullscreen(slimSelectElement)
                    }
                },
                afterClose: () => {
                    const slimSelectElement = document.querySelector('.ss-content')
                    if (slimSelectElement) {
                        disableMobileFullscreen(slimSelectElement)
                    }
                }
            }
        })
    }

    // Initialize Brand Select
    if (brandSelect.value && !brandSlimSelectInstance) {
        brandSlimSelectInstance = new SlimSelect({
            select: brandSelect.value,
            settings: {
                searchText: i18n.t('quality.search_brands') || 'Search brands...',
                searchPlaceholder: i18n.t('quality.search_brands') || 'Search brands...',
                searchHighlight: true,
                closeOnSelect: true,
                allowDeselect: false,
                placeholderText: i18n.t('quality.select_brand'),
            },
            events: {
                afterChange: (newVal) => {
                    form.brand_id = newVal.length > 0 ? newVal[0].value : null
                    // Reset branch when brand changes
                    form.branch_id = null
                    // Clear validation errors
                    if (form.brand_id && form.errors.brand_id) {
                        form.clearErrors('brand_id')
                    }
                },
                beforeOpen: () => {
                    const slimSelectElement = document.querySelector('.ss-content')
                    if (slimSelectElement) {
                        enableMobileFullscreen(slimSelectElement)
                    }
                },
                afterClose: () => {
                    const slimSelectElement = document.querySelector('.ss-content')
                    if (slimSelectElement) {
                        disableMobileFullscreen(slimSelectElement)
                    }
                }
            }
        })
    }

    // Initialize Branch Select
    if (branchSelect.value && !slimSelectInstance) {
        slimSelectInstance = new SlimSelect({
            select: branchSelect.value,
            settings: {
                searchText: i18n.t('quality.search_branches') || 'Search branches...',
                searchPlaceholder: i18n.t('quality.search_branches') || 'Search branches...',
                searchHighlight: true,
                closeOnSelect: true,
                allowDeselect: false,
                placeholderText: i18n.t('quality.select_branch'),
            },
            events: {
                afterChange: (newVal) => {
                    form.branch_id = newVal.length > 0 ? newVal[0].value : null
                    // Load template for selected branch
                    if (form.branch_id) {
                        loadTemplate(form.branch_id)
                    }
                    // Clear validation errors
                    if (form.branch_id && form.errors.branch_id) {
                        form.clearErrors('branch_id')
                    }
                },
                beforeOpen: () => {
                    const slimSelectElement = document.querySelector('.ss-content')
                    if (slimSelectElement) {
                        enableMobileFullscreen(slimSelectElement)
                    }
                },
                afterClose: () => {
                    const slimSelectElement = document.querySelector('.ss-content')
                    if (slimSelectElement) {
                        disableMobileFullscreen(slimSelectElement)
                    }
                }
            }
        })
    }

    // Initialize extra points SlimSelect
    if (extraPointsSelect.value && !extraPointsSlimSelectInstance) {
        extraPointsSlimSelectInstance = new SlimSelect({
            select: extraPointsSelect.value,
            settings: {
                closeOnSelect: true,
                allowDeselect: false,
            },
            events: {
                afterChange: () => {
                    handleExtraPointsChange()
                },
                beforeOpen: () => {
                    // Enable mobile full-screen on dropdown open
                    const slimSelectElements = document.querySelectorAll('.ss-main')
                    const extraPointsSlimSelect = Array.from(slimSelectElements).find(el =>
                        el.querySelector('select') === extraPointsSelect.value
                    )
                    if (extraPointsSlimSelect) {
                        enableMobileFullscreen(extraPointsSlimSelect)
                    }
                },
                afterClose: () => {
                    // Disable mobile full-screen on dropdown close
                    const slimSelectElements = document.querySelectorAll('.ss-main')
                    const extraPointsSlimSelect = Array.from(slimSelectElements).find(el =>
                        el.querySelector('select') === extraPointsSelect.value
                    )
                    if (extraPointsSlimSelect) {
                        disableMobileFullscreen(extraPointsSlimSelect)
                    }
                }
            }
        })
    }
}

// Handle window resize to disable fullscreen if viewport becomes desktop
const handleResize = () => {
    if (!isMobileViewport() && isMobileFullscreen.value) {
        // Close any open SlimSelect dropdowns when switching to desktop
        if (countrySlimSelectInstance && countrySlimSelectInstance.open) {
            countrySlimSelectInstance.close()
        }
        if (brandSlimSelectInstance && brandSlimSelectInstance.open) {
            brandSlimSelectInstance.close()
        }
        if (slimSelectInstance && slimSelectInstance.open) {
            slimSelectInstance.close()
        }
        if (extraPointsSlimSelectInstance && extraPointsSlimSelectInstance.open) {
            extraPointsSlimSelectInstance.close()
        }
    }
}

// Handle escape key to close fullscreen dropdown
const handleKeydown = (event) => {
    if (event.key === 'Escape' && isMobileFullscreen.value) {
        // Close any open SlimSelect dropdowns
        if (countrySlimSelectInstance && countrySlimSelectInstance.open) {
            countrySlimSelectInstance.close()
        }
        if (brandSlimSelectInstance && brandSlimSelectInstance.open) {
            brandSlimSelectInstance.close()
        }
        if (slimSelectInstance && slimSelectInstance.open) {
            slimSelectInstance.close()
        }
        if (extraPointsSlimSelectInstance && extraPointsSlimSelectInstance.open) {
            extraPointsSlimSelectInstance.close()
        }
    }
}

// Lifecycle hooks
onMounted(() => {
    nextTick(() => {
        initializeSlimSelect()
    })

    // Add event listeners
    window.addEventListener('resize', handleResize)
    document.addEventListener('keydown', handleKeydown)
})

onUnmounted(() => {
    // Clean up SlimSelect instances
    if (countrySlimSelectInstance) {
        countrySlimSelectInstance.destroy()
        countrySlimSelectInstance = null
    }

    if (brandSlimSelectInstance) {
        brandSlimSelectInstance.destroy()
        brandSlimSelectInstance = null
    }

    if (slimSelectInstance) {
        slimSelectInstance.destroy()
        slimSelectInstance = null
    }

    if (extraPointsSlimSelectInstance) {
        extraPointsSlimSelectInstance.destroy()
        extraPointsSlimSelectInstance = null
    }

    // Restore body overflow if still in fullscreen mode
    if (isMobileFullscreen.value) {
        document.body.style.overflow = originalBodyOverflow.value
        isMobileFullscreen.value = false
    }

    // Remove event listeners
    window.removeEventListener('resize', handleResize)
    document.removeEventListener('keydown', handleKeydown)
})
</script>
