<template>
    <div class="qc-checklist-template">
        <!-- Template Header -->
        <div class="qc-card">
            <h2 class="qc-title">{{ template.localized_name }}</h2>
            <p class="text-sm text-gray-600">
                {{ $t('quality.complete_checklist_description') || 'Please complete all required fields' }}
            </p>
        </div>

        <!-- Sections and Questions -->
        <div
            v-for="section in template.sections"
            :key="section.id"
            class="qc-card"
        >
            <h3 class="qc-title text-lg mb-6">{{ section.localized_name }}</h3>

            <!-- Questions in Section -->
            <div
                v-for="question in section.questions"
                :key="question.id"
                class="mb-6 pb-6 border-b border-gray-200 last:border-b-0"
            >
                <!-- Question Label -->
                <div class="flex items-start justify-between mb-3">
                    <label class="block text-sm font-medium text-gray-700">
                        {{ question.localized_name }}
                        <span v-if="question.is_required" class="text-red-500 ml-1">*</span>
                    </label>
                </div>

                <!-- Points Question Type -->
                <div v-if="isPointsQuestion(question)" class="flex gap-4 flex-wrap">
                    <label class="flex items-center cursor-pointer">
                        <input v-model="answers[question.id]" type="radio" :value="'1'" class="w-4 h-4 text-green-600 border-gray-300 focus:ring-2 focus:ring-green-500" />
                        <span class="ml-2 text-sm text-gray-700">{{ $t('quality.one_point') || '1 PT' }}</span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input v-model="answers[question.id]" type="radio" :value="'0.5'" class="w-4 h-4 text-yellow-600 border-gray-300 focus:ring-2 focus:ring-yellow-500" />
                        <span class="ml-2 text-sm text-gray-700">{{ $t('quality.half_point') || '0.5 PT' }}</span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input v-model="answers[question.id]" type="radio" :value="'0'" class="w-4 h-4 text-red-600 border-gray-300 focus:ring-2 focus:ring-red-500" />
                        <span class="ml-2 text-sm text-gray-700">{{ $t('quality.zero_point') || '0 PT' }}</span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input v-model="answers[question.id]" type="radio" :value="null" class="w-4 h-4 text-gray-600 border-gray-300 focus:ring-2 focus:ring-gray-500" />
                        <span class="ml-2 text-sm text-gray-700">{{ $t('quality.not_applicable') || 'N/A' }}</span>
                    </label>
                </div>

                <!-- Yes / No Question Type -->
                <div v-else-if="question.question_type === 'yes_no'" class="flex gap-4 flex-wrap">
                    <label class="flex items-center cursor-pointer">
                        <input v-model="answers[question.id]" type="radio" :value="'1'" class="w-4 h-4 text-green-600 border-gray-300 focus:ring-2 focus:ring-green-500" />
                        <span class="ml-2 text-sm text-gray-700">{{ $t('quality.yes') || 'Yes' }}</span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input v-model="answers[question.id]" type="radio" :value="'0'" class="w-4 h-4 text-red-600 border-gray-300 focus:ring-2 focus:ring-red-500" />
                        <span class="ml-2 text-sm text-gray-700">{{ $t('quality.no') || 'No' }}</span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input v-model="answers[question.id]" type="radio" :value="null" class="w-4 h-4 text-gray-600 border-gray-300 focus:ring-2 focus:ring-gray-500" />
                        <span class="ml-2 text-sm text-gray-700">{{ $t('quality.not_applicable') || 'N/A' }}</span>
                    </label>
                </div>

                <!-- Multi-Select Question Type -->
                <div v-else-if="question.question_type === 'multi_select'" class="grid gap-2 sm:grid-cols-2">
                    <label v-for="option in question.options" :key="option" class="flex items-center rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">
                        <input v-model="answers[question.id]" type="checkbox" :value="option" class="mr-2 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                        {{ option }}
                    </label>
                </div>

                <!-- Manual Score Question Type -->
                <div v-else-if="question.question_type === 'score'" class="space-y-3">
                    <label class="flex items-center cursor-pointer text-sm text-gray-700">
                        <input v-model="scoreManualEnabled[question.id]" type="checkbox" class="mr-2 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @change="toggleScoreInput(question)" />
                        {{ $t('quality.enable_manual_score') || 'Enable manual score' }}
                    </label>
                    <input
                        v-if="scoreManualEnabled[question.id]"
                        v-model.number="answers[question.id]"
                        type="number"
                        min="0"
                        :max="question.score_value || 1"
                        step="0.01"
                        class="qc-input w-40"
                        :placeholder="`0 - ${question.score_value || 1}`"
                    />
                </div>

                <!-- Legacy Point-Based Question Type (q_type === 1) -->
                <div v-else-if="question.q_type === 1" class="flex gap-4 flex-wrap">
                    <!-- Points Answer Type (also default when answer_type is empty/missing) -->
                    <template v-if="!template.answer_type || template.answer_type === 'Points'">
                        <label class="flex items-center cursor-pointer">
                            <input
                                v-model="answers[question.id]"
                                type="radio"
                                :value="'1'"
                                class="w-4 h-4 text-green-600 border-gray-300 focus:ring-2 focus:ring-green-500"
                            />
                            <span class="ml-2 text-sm text-gray-700">{{ $t('quality.one_point') || '1 PT' }}</span>
                        </label>
                        <label class="flex items-center cursor-pointer">
                            <input
                                v-model="answers[question.id]"
                                type="radio"
                                :value="'0.5'"
                                class="w-4 h-4 text-yellow-600 border-gray-300 focus:ring-2 focus:ring-yellow-500"
                            />
                            <span class="ml-2 text-sm text-gray-700">{{ $t('quality.half_point') || '0.5 PT' }}</span>
                        </label>
                        <label class="flex items-center cursor-pointer">
                            <input
                                v-model="answers[question.id]"
                                type="radio"
                                :value="'0'"
                                class="w-4 h-4 text-red-600 border-gray-300 focus:ring-2 focus:ring-red-500"
                            />
                            <span class="ml-2 text-sm text-gray-700">{{ $t('quality.zero_point') || '0 PT' }}</span>
                        </label>
                        <label class="flex items-center cursor-pointer">
                            <input
                                v-model="answers[question.id]"
                                type="radio"
                                :value="null"
                                class="w-4 h-4 text-gray-600 border-gray-300 focus:ring-2 focus:ring-gray-500"
                            />
                            <span class="ml-2 text-sm text-gray-700">{{ $t('quality.not_applicable') || 'N/A' }}</span>
                        </label>
                    </template>

                    <!-- Yes/No Answer Type -->
                    <template v-else-if="template.answer_type === 'Yes/No'">
                        <label class="flex items-center cursor-pointer">
                            <input
                                v-model="answers[question.id]"
                                type="radio"
                                :value="'1'"
                                class="w-4 h-4 text-green-600 border-gray-300 focus:ring-2 focus:ring-green-500"
                            />
                            <span class="ml-2 text-sm text-gray-700">{{ $t('quality.yes') || 'Yes' }}</span>
                        </label>
                        <label class="flex items-center cursor-pointer">
                            <input
                                v-model="answers[question.id]"
                                type="radio"
                                :value="'0'"
                                class="w-4 h-4 text-red-600 border-gray-300 focus:ring-2 focus:ring-red-500"
                            />
                            <span class="ml-2 text-sm text-gray-700">{{ $t('quality.no') || 'No' }}</span>
                        </label>
                        <label class="flex items-center cursor-pointer">
                            <input
                                v-model="answers[question.id]"
                                type="radio"
                                :value="null"
                                class="w-4 h-4 text-gray-600 border-gray-300 focus:ring-2 focus:ring-gray-500"
                            />
                            <span class="ml-2 text-sm text-gray-700">{{ $t('quality.not_applicable') || 'N/A' }}</span>
                        </label>
                    </template>
                </div>

                <!-- Text Question Type -->
                <div v-else-if="question.question_type === 'comment' || question.q_type === 2">
                    <textarea
                        v-model="answers[question.id]"
                        class="qc-textarea w-full"
                        :placeholder="$t('quality.enter_response') || 'Enter your response'"
                        rows="3"
                        maxlength="1000"
                    ></textarea>
                    <div class="text-xs text-gray-500 mt-1 text-right">
                        {{ (answers[question.id] || '').length }}/1000
                    </div>
                </div>

                <!-- Validation Error -->
                <div v-if="errors[question.id]" class="text-red-600 text-sm mt-2">
                    {{ errors[question.id] }}
                </div>
            </div>

            <!-- Section Photo Upload -->
            <SectionPhotoUpload
                :ref="el => sectionPhotoRefs[section.id] = el"
                :section-id="section.id"
                :existing-photos="props.existingPhotos.filter(p => p.section_id === section.id)"
                @photo-captured="handleSectionPhotoCaptured"
                @photo-deleted="handleSectionPhotoDeleted"
                :disabled="disabled"
            />
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import SectionPhotoUpload from '@/Components/SectionPhotoUpload.vue'

const props = defineProps({
    template: {
        type: Object,
        required: true
    },
    disabled: {
        type: Boolean,
        default: false
    },
    initialAnswers: {
        type: Object,
        default: () => ({})
    },
    existingPhotos: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['update:answers', 'photos-updated'])

const i18n = useI18n()

// Reactive data
const answers = ref({})
const errors = ref({})
const sectionPhotoRefs = ref({})
const scoreManualEnabled = ref({})

// Initialize answers from template and initial answers prop
const initializeAnswers = () => {
    const newAnswers = {}
    const newScoreManualEnabled = {}
    props.template.sections.forEach(section => {
        section.questions.forEach(question => {
            const initialAnswer = normalizeInitialAnswer(question, props.initialAnswers[question.id])
            newAnswers[question.id] = initialAnswer
            newScoreManualEnabled[question.id] = question.question_type === 'score' && initialAnswer !== '' && initialAnswer !== null
        })
    })
    answers.value = newAnswers
    scoreManualEnabled.value = newScoreManualEnabled
}

const normalizeInitialAnswer = (question, answer) => {
    if (question.question_type === 'multi_select') {
        if (Array.isArray(answer)) return answer
        if (!answer) return []
        try {
            const parsed = JSON.parse(answer)
            return Array.isArray(parsed) ? parsed : []
        } catch (error) {
            return String(answer).split(',').filter(Boolean)
        }
    }

    return answer ?? ''
}

const toggleScoreInput = (question) => {
    if (scoreManualEnabled.value[question.id]) {
        answers.value[question.id] = answers.value[question.id] === '' || answers.value[question.id] === null ? 0 : answers.value[question.id]
    } else {
        answers.value[question.id] = ''
    }
}

const isPointsQuestion = (question) => {
    if (question.question_type === 'points') {
        return true
    }

    return !question.question_type && question.q_type === 1 && (!props.template.answer_type || props.template.answer_type === 'Points')
}

// Watch for template or initialAnswers changes
watch(() => [props.template, props.initialAnswers], () => {
    initializeAnswers()
}, { deep: true })

// Validate required fields
const validateAnswers = () => {
    errors.value = {}
    let isValid = true

    props.template.sections.forEach(section => {
        section.questions.forEach(question => {
            const answer = answers.value[question.id]
            const isEmpty = Array.isArray(answer) ? answer.length === 0 : answer === null || answer === ''
            if (question.is_required && isEmpty) {
                errors.value[question.id] = i18n.t('quality.field_required') || 'This field is required'
                isValid = false
            }
        })
    })

    return isValid
}

// Get all photos from all sections
const getAllPhotos = () => {
    const allPhotos = []
    Object.values(sectionPhotoRefs.value).forEach(ref => {
        if (ref && ref.getPhotos) {
            allPhotos.push(...ref.getPhotos())
        }
    })
    return allPhotos
}

// Handle section photo captured
const handleSectionPhotoCaptured = (photo) => {
    emit('photos-updated', getAllPhotos())
}

// Handle section photo deleted
const handleSectionPhotoDeleted = (photoId) => {
    emit('photos-updated', getAllPhotos())
}

// Expose validation method
const validate = () => validateAnswers()

// Emit answers when they change
watch(() => answers.value, (newAnswers) => {
    emit('update:answers', newAnswers)
}, { deep: true })

// Initialize on mount
initializeAnswers()

// Expose methods
defineExpose({
    validate,
    getAnswers: () => answers.value,
    getAllPhotos: () => getAllPhotos()
})
</script>

<style scoped>
.qc-checklist-template {
    /* Component styling handled by parent */
}
</style>

