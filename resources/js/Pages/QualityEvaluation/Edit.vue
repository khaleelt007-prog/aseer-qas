<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center" :class="{ 'flex-row-reverse': isRTL }">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight" :class="{ 'text-right': isRTL }">
                    {{ t('quality.edit_quality_evaluation') }}
                </h2>
                <Link
                    :href="route('quality-evaluations.show', evaluation.id)"
                    class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors"
                >
                    {{ t('quality.cancel') }}
                </Link>
            </div>
        </template>

        <div class="qc-container">
            <form @submit.prevent="submitForm" class="max-w-4xl mx-auto">
                <!-- Header Card -->
                <div class="qc-card" :class="{ 'text-right': isRTL }">
                    <h1 class="qc-title">{{ t('quality.edit_quality_control_evaluation') }}</h1>
                    <p class="qc-subtitle">
                        {{ t('quality.update_evaluation_description') }}
                    </p>

                    <!-- Progress Bar (for regular evaluations only) -->
                    <div v-if="evaluationType === 'REGULAR'" class="qc-progress-bar">
                        <div
                            class="qc-progress-fill"
                            :style="{ width: progressPercentage + '%' }"
                        ></div>
                    </div>
                    <p v-if="evaluationType === 'REGULAR'" class="text-sm text-gray-600 text-center">
                        {{ completedItems }}/{{ $props.evaluationItems.length }} {{ t('quality.items_completed') }}
                    </p>
                </div>

                <!-- Branch Selection (Read-only for checklist evaluations) -->
                <div class="qc-card" :class="{ 'text-right': isRTL }">
                    <h3 class="qc-title text-lg mb-4">{{ t('quality.branch') }}</h3>
                    <div v-if="evaluationType === 'CHECKLIST'" class="space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg">
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">{{ t('quality.country') || 'Country' }}</p>
                                <p class="text-gray-800 font-medium">{{ evaluation.branch?.country?.name || '—' }}</p>
                            </div>
                            <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg">
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">{{ t('quality.brand') || 'Brand' }}</p>
                                <p class="text-gray-800 font-medium">{{ evaluation.branch?.brand?.name || '—' }}</p>
                            </div>
                            <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg">
                                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">{{ t('quality.branch') || 'Branch' }}</p>
                                <p class="text-gray-800 font-medium">{{ selectedBranchName || '—' }}</p>
                            </div>
                        </div>
                    </div>
                    <template v-else>
                        <select
                            v-model="form.branch_id"
                            class="qc-select"
                            :class="{ 'text-right': isRTL }"
                            required
                        >
                            <option value="" disabled>{{ t('quality.select_branch') }}</option>
                            <option
                                v-for="branch in $props.branches"
                                :key="branch.id"
                                :value="branch.id"
                            >
                                {{ branch.localized_name }}
                            </option>
                        </select>
                        <div v-if="form.errors.branch_id" class="text-red-600 text-sm mt-2" :class="{ 'text-right': isRTL }">
                            {{ form.errors.branch_id }}
                        </div>
                        <!-- Branch Info Display -->
                        <div v-if="selectedBranchName" class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <p class="text-sm text-blue-700" :class="{ 'text-right': isRTL }">
                                <span class="font-medium">{{ t('quality.selected_branch') }}:</span> {{ selectedBranchName }}
                            </p>
                        </div>
                    </template>
                </div>

                <!-- Checklist Template Form (shown only if evaluation type is CHECKLIST) -->
                <QcChecklistTemplate
                    v-if="evaluationType === 'CHECKLIST' && currentTemplate"
                    ref="checklistTemplateRef"
                    :template="currentTemplate"
                    :disabled="processing"
                    :initial-answers="checklistAnswers"
                    :existing-photos="existingPhotos"
                    @update:answers="(answers) => form.answers = answers"
                    @photos-updated="handleChecklistPhotosUpdated"
                />

                <!-- Evaluation Items (shown only if evaluation type is REGULAR) -->
                <template v-if="evaluationType === 'REGULAR'">
                <div
                    v-for="(item, index) in $props.evaluationItems"
                    :key="item.id"
                    :ref="el => itemRefs[index] = el"
                    class="qc-card"
                    :class="{ 'active': currentItemIndex === index, 'text-right': isRTL }"
                >
                    <div class="flex justify-between items-start mb-4" :class="{ 'flex-row-reverse': isRTL }">
                        <h3 class="qc-title text-lg">{{ item.localized_title }}</h3>
                        <span class="qc-weight-badge">{{ item.weight }}%</span>
                    </div>

                    <div class="qc-score-input" :class="{ 'flex-row-reverse': isRTL }">
                        <input
                            v-model.number="form[item.id + '_achieved']"
                            type="number"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            min="0"
                            :max="form[item.id + '_max']"
                            placeholder="0"
                            :class="{ 'text-right': isRTL }"
                            @input="handleAchievedScoreInput(item.id, index)"
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
                            class="bg-gray-100 cursor-not-allowed"
                            :class="{ 'text-right': isRTL }"
                        />
                        <span class="text-sm text-gray-600" :class="{ 'mr-2 ml-0': isRTL, 'ml-2 mr-0': !isRTL }">{{ t('quality.points') }}</span>
                    </div>

                    <div v-if="form[item.id + '_achieved'] !== null && form[item.id + '_max']" class="mt-2" :class="{ 'text-right': isRTL }">
                        <div class="text-sm text-gray-600">
                            {{ t('quality.score') }}: {{ calculateItemPercentage(item.id) }}%
                            ({{ calculateWeightedScore(item.id) }}/{{ item.weight }} {{ t('quality.weighted_contribution') }})
                        </div>
                    </div>

                    <div v-if="form.errors[item.id + '_achieved']" class="text-red-600 text-sm mt-2" :class="{ 'text-right': isRTL }">
                        {{ form.errors[item.id + '_achieved'] }}
                    </div>
                </div>
                </template>

                <!-- Total Score Display (for regular evaluations only) -->
                <div v-if="evaluationType === 'REGULAR' && totalScore > 0" class="qc-total-score" :class="{ 'text-right': isRTL }">
                    <h3>{{ finalTotalScore.toFixed(1) }}/100</h3>
                    <p>{{ t('quality.total_score') }}</p>
                    <div v-if="form.extra_points !== 0" class="text-sm mt-1 opacity-75">
                        Base: {{ totalScore.toFixed(1) }} {{ form.extra_points > 0 ? '+' : '' }}{{ form.extra_points }}
                    </div>
                </div>

                <!-- Extra Points Section (for regular evaluations only) -->
                <div v-if="evaluationType === 'REGULAR'" class="qc-card" ref="extraPointsSection" :class="{ 'text-right': isRTL }">
                    <h3 class="qc-title text-lg mb-4">{{ t('quality.extra_points') }}</h3>
                    <p class="text-sm text-gray-600 mb-4">{{ t('quality.extra_points_description') }}</p>

                    <div class="qc-score-input" :class="{ 'flex-row-reverse': isRTL }">
                        <label for="extra_points" class="block text-sm font-medium text-gray-700 mb-2" :class="{ 'text-right': isRTL }">
                            {{ t('quality.bonus_penalty_points') }}
                        </label>
                        <select
                            id="extra_points"
                            ref="extraPointsSelect"
                            v-model.number="form.extra_points"
                            class="qc-input w-full max-w-xs"
                            :class="{ 'text-right': isRTL }"
                            @change="handleExtraPointsChange"
                        >
                            <option value="5">+5</option>
                            <option value="4">+4</option>
                            <option value="3">+3</option>
                            <option value="2">+2</option>
                            <option value="1">+1</option>
                            <option value="0">0</option>
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
                            'text-red-700': form.extra_points < 0,
                            'text-right': isRTL
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

                <!-- Photo Documentation Section -->
                <div v-if="form.branch_id" class="qc-card">
                    <h3 class="qc-title text-lg mb-4">{{ $t('quality.photo_documentation') || 'Photo Documentation' }}</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        {{ $t('quality.photo_description') || 'Capture photos to document your evaluation findings' }}
                    </p>

                    <PhotoCapture
                        @photo-captured="handlePhotoCaptured"
                        :disabled="processing"
                    />

                    <!-- Combined gallery showing both existing and newly captured photos -->
                    <PhotoGallery
                        :photos="[...existingPhotos, ...capturedPhotos]"
                        @photo-deleted="handlePhotoDeleted"
                        :disabled="processing"
                    />
                </div>

                <!-- Comments Section -->
                <div class="qc-card" ref="commentsSection" :class="{ 'text-right': isRTL }">
                    <h3 class="qc-title text-lg mb-4">{{ t('quality.additional_comments') }}</h3>
                    <textarea
                        v-model="form.comments"
                        class="qc-textarea"
                        :class="{ 'text-right': isRTL }"
                        :placeholder="t('quality.add_observations_placeholder')"
                        maxlength="2000"
                    ></textarea>
                    <div class="text-sm text-gray-500 mt-2" :class="{ 'text-left': isRTL, 'text-right': !isRTL }">
                        {{ form.comments?.length || 0 }}/2000 {{ t('quality.characters') }}
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="qc-card" :class="{ 'text-right': isRTL }">
                    <div class="flex flex-col sm:flex-row gap-4" :class="{ 'sm:flex-row-reverse': isRTL }">
                       
                        <button
                            type="submit"
                            name="status"
                            value="completed"
                            class="qc-button bg-green-600 hover:bg-green-700"
                            :disabled="processing || !canComplete"
                        >
                            {{ processing ? t('quality.updating') : t('quality.update_complete') }}
                        </button>
                        <Link
                            :href="route('quality-evaluations.show', evaluation.id)"
                            class="qc-button bg-red-500 hover:bg-red-600"
                        >
                            {{ t('quality.cancel') }}
                        </Link>
                    </div>

                    <div v-if="!canComplete" class="text-sm text-gray-600 mt-2 text-center">
                        {{ t('quality.complete_all_items_to_complete') }}
                    </div>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, nextTick, onMounted } from 'vue'
import { useForm, Link, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { useRTL } from '@/composables/useRTL'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import QcChecklistTemplate from '@/Components/QcChecklistTemplate.vue'
import PhotoCapture from '@/Components/PhotoCapture.vue'
import PhotoGallery from '@/Components/PhotoGallery.vue'

// Composables
const { t } = useI18n()
const { isRTL } = useRTL()

// Props
const props = defineProps({
    evaluation: Object,
    evaluationItems: Array,
    branches: Array,
    checklistData: Object
})

// Track existing photos (mutable ref instead of computed)
const existingPhotos = ref([])

// Determine evaluation type
const evaluationType = ref(props.evaluation.type === 'checklist' ? 'CHECKLIST' : 'REGULAR')

// Form data - dynamically populate with existing evaluation data
const createFormData = () => {
    const formData = {
        branch_id: props.evaluation.branch_id,
        title: props.evaluation.title,
        comments: props.evaluation.comments || '',
        status: props.evaluation.status,
        extra_points: props.evaluation.extra_points || 0,
        answers: {}
    }

    // Add dynamic fields for each evaluation item (for regular evaluations)
    if (evaluationType.value === 'REGULAR') {
        props.evaluationItems.forEach(item => {
            // Find the corresponding response data from the evaluation
            const evaluationItem = props.evaluation.evaluation_items?.find(ei => ei.id === item.id)

            formData[item.id + '_achieved'] = evaluationItem?.achieved || null
            formData[item.id + '_max'] = evaluationItem?.max || item.weight
        })
    }

    return formData
}

const form = useForm(createFormData())

// Reactive data
const currentItemIndex = ref(0)
const itemRefs = ref([])
const processing = ref(false)
const extraPointsSection = ref(null)
const extraPointsSelect = ref(null)
const checklistTemplateRef = ref(null)
const currentTemplate = ref(null)
const checklistAnswers = ref({})
const capturedPhotos = ref([])
const photosToDelete = ref([])

// Computed properties
const selectedBranchName = computed(() => {
    const branch = props.branches.find(b => b.id === form.branch_id)
    return branch?.localized_name || ''
})

const completedItems = computed(() => {
    if (evaluationType.value === 'CHECKLIST') {
        return 0 // Not applicable for checklist
    }
    return props.evaluationItems.filter(item =>
        form[item.id + '_achieved'] !== null &&
        form[item.id + '_achieved'] !== ''
    ).length
})

const progressPercentage = computed(() => {
    if (evaluationType.value === 'CHECKLIST' || props.evaluationItems.length === 0) {
        return 0
    }
    return (completedItems.value / props.evaluationItems.length) * 100
})

const canComplete = computed(() => {
    // For checklist evaluations, only require branch selection
    if (evaluationType.value === 'CHECKLIST') {
        return form.branch_id && currentTemplate.value
    }

    // For regular evaluations, require all items to be scored
    return form.branch_id && props.evaluationItems.every(item =>
        form[item.id + '_achieved'] !== null &&
        form[item.id + '_achieved'] !== ''
    )
})

const totalScore = computed(() => {
    // Not applicable for checklist evaluations
    if (evaluationType.value === 'CHECKLIST') {
        return 0
    }

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
    // Not applicable for checklist evaluations
    if (evaluationType.value === 'CHECKLIST') {
        return 0
    }

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

// Methods
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

const handleExtraPointsChange = () => {
    // Auto-scroll to comments after selecting extra points
    setTimeout(() => {
        scrollToComments()
    }, 300)
}

const handleAchievedScoreInput = async (itemId, index) => {
    // Cap the achieved score to the maximum value
    const maxValue = form[itemId + '_max']
    const achievedValue = form[itemId + '_achieved']

    if (achievedValue !== null && achievedValue > maxValue) {
        form[itemId + '_achieved'] = maxValue
    }

    // Auto-scroll to next item after a short delay
    if (form[itemId + '_achieved'] !== null && form[itemId + '_achieved'] !== '') {
        setTimeout(() => {
            if (index < props.evaluationItems.length - 1) {
                scrollToItem(index + 1)
            } else {
                // Scroll to extra points section if all items are filled
                scrollToExtraPoints()
            }
        }, 500)
    }
}

const scrollToExtraPoints = () => {
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

const scrollToItem = (index) => {
    currentItemIndex.value = index
    nextTick(() => {
        if (itemRefs.value[index]) {
            itemRefs.value[index].scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            })
        }
    })
}

const scrollToComments = () => {
    nextTick(() => {
        const commentsSection = document.querySelector('[ref="commentsSection"]')
        if (commentsSection) {
            commentsSection.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            })
        }
    })
}

// Photo management methods
const handlePhotoCaptured = (photoData) => {
    // Add the captured photo to our array
    // photoData now contains id (photo ID from server) instead of file
    capturedPhotos.value.push({
        id: photoData.id, // Photo ID from server
        url: photoData.url, // URL to fetch the photo
        preview: photoData.preview,
        timestamp: photoData.timestamp || new Date().toISOString(),
        section_id: photoData.section_id || null, // No section for regular evaluations
        isUploaded: photoData.isUploaded || false, // Flag indicating photo is already uploaded
        isNew: true // Flag to indicate this is a newly captured photo
    })
}

const handlePhotoDeleted = (photoId) => {
    // Check if it's an existing photo (has numeric ID from database)
    const existingPhoto = existingPhotos.value.find(p => p.id === photoId)

    if (existingPhoto) {
        // Mark existing photo for deletion
        photosToDelete.value.push(photoId)
        // Remove from display
        existingPhotos.value = existingPhotos.value.filter(p => p.id !== photoId)
    } else {
        // Remove newly captured photo from array
        const index = capturedPhotos.value.findIndex(photo => photo.id === photoId)
        if (index !== -1) {
            // Revoke the object URL to free memory
            URL.revokeObjectURL(capturedPhotos.value[index].preview)
            capturedPhotos.value.splice(index, 1)
        }
    }
}

// Handle photos from checklist template sections
const handleChecklistPhotosUpdated = (sectionPhotos) => {
    // Update capturedPhotos with section-based photos
    // Keep regular photos (section_id === null) and replace section photos
    const regularPhotos = capturedPhotos.value.filter(p => p.section_id === null)

    // Mark section photos as new if they don't have isExisting flag
    const markedSectionPhotos = sectionPhotos.map(photo => ({
        ...photo,
        isNew: !photo.isExisting // Mark as new if not an existing photo
    }))

    capturedPhotos.value = [...regularPhotos, ...markedSectionPhotos]
}

const submitForm = (event) => {
    processing.value = true
    const status = event.submitter.value
    form.status = status

    // Validate checklist template if present
    if (evaluationType.value === 'CHECKLIST' && checklistTemplateRef.value) {
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

    // Add newly captured photo IDs to FormData (photos are already uploaded)
    const newPhotoIds = capturedPhotos.value
        .filter(photo => photo.isNew) // Only newly captured photos
        .map(photo => photo.id)

    if (newPhotoIds.length > 0) {
        formData.append('photo_ids', JSON.stringify(newPhotoIds))
    }

    // Add photo sections if any
    const newPhotos = capturedPhotos.value.filter(photo => photo.isNew)
    const photoSections = newPhotos
        .map((photo, index) => photo.section_id ? { index, section_id: photo.section_id } : null)
        .filter(item => item !== null)

    if (photoSections.length > 0) {
        const photoSectionsArray = new Array(newPhotos.length)
        photoSections.forEach(item => {
            photoSectionsArray[item.index] = item.section_id
        })
        formData.append('photo_sections', JSON.stringify(photoSectionsArray))
    }

    // Add photos to delete
    if (photosToDelete.value.length > 0) {
        formData.append('photos_to_delete', JSON.stringify(photosToDelete.value))
    }

    // Debug: Log FormData contents
    console.log('FormData contents:')
    for (let [key, value] of formData.entries()) {
        console.log(key, value)
    }

    console.log('Total new photos being sent:', newPhotoIds.length)
    console.log('Photos to delete:', photosToDelete.value)

    // Use router.post with FormData
    router.post(route('quality-evaluations.update', props.evaluation.id), formData, {
        onFinish: () => {
            processing.value = false
        },
        onError: (errors) => {
            form.setError(errors)
        }
    })
}

// Initialize checklist template on mount
onMounted(() => {
    // Initialize existing photos
    existingPhotos.value = props.evaluation?.photos || []

    if (evaluationType.value === 'CHECKLIST' && props.checklistData) {
        // Build template object from checklistData (include answer_type so radio options render)
        currentTemplate.value = {
            id: props.evaluation.template_id,
            localized_name: props.checklistData.template_name,
            answer_type: props.checklistData.answer_type || 'Points',
            sections: props.checklistData.sections || []
        }

        // Initialize form answers from existing checklist data
        if (props.checklistData.sections) {
            const answers = {}
            props.checklistData.sections.forEach(section => {
                section.questions?.forEach(question => {
                    // Find existing answer for this question
                    const existingAnswer = props.checklistData.answers?.find(a => a.question_id === question.id)
                    answers[question.id] = existingAnswer?.answer_value || ''
                })
            })
            // Set both form.answers and checklistAnswers to ensure component receives pre-populated data
            form.answers = answers
            checklistAnswers.value = answers
        }
    }
})
</script>
