<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        {{ $t('quality.follow_up_details') }}
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        {{ evaluation.branch?.localized_name || evaluation.branch?.name }}
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Link
                        :href="route('quality-evaluation-follow-ups.index')"
                        class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors"
                    >
                        {{ $t('quality.back_to_follow_up_list') }}
                    </Link>

                    <Link
                        :href="route('quality-evaluations.show', evaluation.id)"
                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
                    >
                        {{ $t('quality.view_evaluation') }}
                    </Link>
                </div>
            </div>
        </template>

        <div class="qc-container" style="max-width: 100%;">
            <div class="max-w-6xl mx-auto space-y-6">
                <div class="qc-card" :class="evaluation.warning_flag ? 'border border-red-300 bg-red-50/70' : ''">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h1 class="qc-title mb-2">{{ followUpData.template_name }} - {{ evaluation.branch?.localized_name || evaluation.branch?.name }}</h1>
                            <div class="flex flex-wrap items-center gap-3 text-sm text-gray-600">
                                <span>{{ $t('quality.created') }}: {{ formatDate(evaluation.created_at) }}</span>
                                <span v-if="evaluation.completed_at">{{ $t('quality.completed_on') }}: {{ formatDate(evaluation.completed_at) }}</span>
                                <span>{{ $t('quality.checklist_score') }}: {{ formatScore(evaluation.total_score) }}/{{ formatScore(evaluation.max_score) }}</span>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <span class="px-3 py-1 rounded-full text-sm font-semibold bg-orange-100 text-orange-700">
                                {{ $t('quality.bad_answers_only') }}
                            </span>
                            <span
                                v-if="evaluation.warning_flag"
                                class="px-3 py-1 rounded-full text-sm font-semibold bg-red-100 text-red-700"
                            >
                                {{ $t('quality.overdue_warning') }}
                            </span>
                        </div>
                    </div>
                </div>

                <div
                    v-for="section in followUpData.sections"
                    :key="section.id"
                    class="qc-card"
                >
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-5">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ section.localized_name }}</h3>
                            <p class="text-sm text-gray-500">
                                {{ section.bad_questions.length }} {{ $t('quality.bad_questions') }}
                            </p>
                        </div>
                    </div>

                    <div v-if="section.comment_questions.length" class="mb-5 rounded-xl border border-blue-100 bg-blue-50/70 p-4">
                        <h4 class="text-sm font-semibold text-blue-900 mb-3">{{ $t('quality.section_comments') }}</h4>

                        <div class="space-y-3">
                            <div v-for="commentQuestion in section.comment_questions" :key="commentQuestion.answer_id" class="rounded-lg bg-white p-3 border border-blue-100">
                                <p class="text-sm font-medium text-gray-700 mb-2">{{ commentQuestion.question }}</p>
                                <p class="text-sm text-gray-600 whitespace-pre-wrap">{{ commentQuestion.answer_value || $t('quality.no_answer_provided') }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="section.photos.length" class="mb-5">
                        <h4 class="text-sm font-semibold text-gray-800 mb-3">{{ $t('quality.section_photos') }}</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div
                                v-for="photo in section.photos"
                                :key="photo.id"
                                class="relative group cursor-pointer rounded-lg overflow-hidden border border-gray-200 bg-gray-50 hover:shadow-md transition-shadow"
                                @click="openPhotoModal(photo)"
                            >
                                <img :src="photo.url" :alt="photo.original_filename || 'Section photo'" class="w-full h-32 object-cover" loading="lazy" />
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-200 flex items-center justify-center">
                                    <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                                        </svg>
                                    </div>
                                </div>
                                <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent p-2">
                                    <p class="text-white text-xs truncate">{{ photo.original_filename || $t('quality.section_photos') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <div
                            v-for="question in section.bad_questions"
                            :key="question.answer_id"
                            class="rounded-xl border p-4"
                            :class="question.severity === 'high' ? 'border-red-200 bg-red-50/60' : 'border-yellow-200 bg-yellow-50/60'"
                        >
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                <div class="flex-1">
                                    <div class="flex flex-wrap items-center gap-2 mb-2">
                                        <h4 class="font-semibold text-gray-900">{{ question.question }}</h4>
                                        <span
                                            class="px-3 py-1 rounded-full text-xs font-semibold"
                                            :class="question.severity === 'high' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-800'"
                                        >
                                            {{ question.answer_label }}
                                        </span>

                                        <span
                                            v-if="question.follow_up?.status"
                                            class="px-3 py-1 rounded-full text-xs font-semibold"
                                            :class="statusBadgeClass(question.follow_up.status)"
                                        >
                                            {{ statusLabel(question.follow_up.status) }}
                                        </span>
                                    </div>

                                    <div class="text-sm text-gray-600 space-y-2 mb-4">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-gray-500">{{ $t('quality.expected_deadline') }}:</span>
                                            <span
                                                v-if="question.follow_up?.expected_deadline"
                                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold"
                                                :class="isDeadlineOverdue(question.follow_up.expected_deadline)
                                                    ? 'bg-red-100 text-red-800 border border-red-200'
                                                    : 'bg-blue-100 text-blue-800 border border-blue-200'"
                                            >
                                                <svg class="w-4 h-4 ltr:mr-1.5 rtl:ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                                {{ formatDeadlineDate(question.follow_up.expected_deadline) }}
                                            </span>
                                            <span v-else class="text-gray-400 italic">{{ $t('quality.no_deadline_set') }}</span>
                                        </div>
                                        <p v-if="question.follow_up?.solved_at">
                                            {{ $t('quality.solved_at') }}:
                                            <span class="font-bold text-green-700">{{ formatDate(question.follow_up.solved_at) }}</span>
                                        </p>
                                        <p v-if="question.follow_up?.skipped_at">
                                            {{ $t('quality.skipped_at') }}:
                                            <span class="font-bold text-gray-700">{{ formatDate(question.follow_up.skipped_at) }}</span>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex flex-col sm:flex-row items-stretch sm:items-end gap-3">
                                    <form @submit.prevent="saveDeadline(question)" class="flex-shrink-0">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">{{ $t('quality.expected_deadline') }}</label>
                                        <div class="flex gap-2">
                                            <input
                                                :data-deadline-picker="question.answer_id"
                                                v-model="deadlineForms[question.answer_id]"
                                                type="text"
                                                class="qc-input w-36 cursor-pointer"
                                                :placeholder="$t('quality.select_date')"
                                                readonly
                                            />
                                            <button type="submit" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 text-sm whitespace-nowrap">
                                                {{ $t('quality.save_deadline') }}
                                            </button>
                                        </div>
                                    </form>

                                    <button
                                        type="button"
                                        @click="markSolved(question)"
                                        class="px-5 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 text-sm whitespace-nowrap"
                                    >
                                        {{ $t('quality.mark_solved') }}
                                    </button>

                                    <button
                                        type="button"
                                        @click="markSkipped(question)"
                                        class="px-5 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 text-sm whitespace-nowrap"
                                    >
                                        {{ $t('quality.skip_warning') }}
                                    </button>
                                </div>
                            </div>

                            <div class="mt-5 rounded-xl border border-gray-200 bg-white">
                                <button
                                    type="button"
                                    @click="toggleComments(question.answer_id)"
                                    class="w-full flex items-center justify-between p-4 hover:bg-gray-50 transition-colors rounded-t-xl"
                                >
                                    <div class="flex items-center gap-2">
                                        <svg
                                            class="w-5 h-5 text-gray-500 transition-transform duration-200"
                                            :class="{ 'rotate-180': expandedComments[question.answer_id] }"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                        <h5 class="font-semibold text-gray-800">{{ $t('quality.follow_up_comments') }}</h5>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                                        {{ question.follow_up?.comments?.length || 0 }} {{ $t('quality.comments') }}
                                    </span>
                                </button>

                                <div
                                    v-show="expandedComments[question.answer_id]"
                                    class="border-t border-gray-200 p-4"
                                >
                                    <div v-if="question.follow_up?.comments?.length" class="space-y-3 mb-4">
                                        <div
                                            v-for="comment in question.follow_up.comments"
                                            :key="comment.id"
                                            class="rounded-lg border border-gray-200 bg-gray-50 p-3"
                                        >
                                            <div class="flex flex-wrap items-center gap-2 mb-2 text-xs text-gray-500">
                                                <span class="px-2 py-1 rounded-full bg-white border border-gray-200 font-semibold text-gray-700">
                                                    {{ comment.comment_type === 'branch_reply' ? $t('quality.branch_reply') : $t('quality.qc_comment') }}
                                                </span>
                                                <span>{{ formatDate(comment.comment_date) }}</span>
                                                <span v-if="comment.author_name">• {{ comment.author_name }}</span>
                                            </div>
                                            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ comment.comment_text }}</p>
                                        </div>
                                    </div>

                                    <div v-else class="text-sm text-gray-500 mb-4">
                                        {{ $t('quality.no_follow_up_comments') }}
                                    </div>

                                    <form class="grid gap-3 md:grid-cols-[180px_1fr_auto]" @submit.prevent="submitComment(question)">
                                        <select v-model="commentForms[question.answer_id].comment_type" class="qc-select">
                                            <option value="qc_comment">{{ $t('quality.qc_comment') }}</option>
                                            <option value="branch_reply">{{ $t('quality.branch_reply') }}</option>
                                        </select>

                                        <textarea
                                            v-model="commentForms[question.answer_id].comment_text"
                                            class="qc-input min-h-[92px]"
                                            :placeholder="$t('quality.enter_follow_up_comment')"
                                        ></textarea>

                                        <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm self-start">
                                            {{ $t('quality.add_comment') }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Photo Modal -->
        <div v-if="showPhotoModal" class="fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center z-50 p-4" @click="closePhotoModal">
            <div class="relative max-w-4xl max-h-full w-full h-full flex items-center justify-center">
                <button
                    @click="closePhotoModal"
                    class="absolute top-4 right-4 z-10 bg-black bg-opacity-50 text-white rounded-full p-2 hover:bg-opacity-70 transition-all"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>

                <div class="relative max-w-full max-h-full" @click.stop>
                    <img
                        v-if="selectedPhoto"
                        :src="selectedPhoto.url"
                        :alt="selectedPhoto.original_filename || 'Section photo'"
                        class="max-w-full max-h-full object-contain rounded-lg"
                    />

                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent p-4 rounded-b-lg">
                        <h4 class="text-white font-medium">{{ selectedPhoto?.original_filename }}</h4>
                        <div class="text-white text-sm opacity-75 mt-1">
                            <span v-if="selectedPhoto?.formatted_file_size">{{ selectedPhoto.formatted_file_size }}</span>
                            <span v-if="selectedPhoto?.uploaded_at" class="mx-2">•</span>
                            <span v-if="selectedPhoto?.uploaded_at">{{ formatDate(selectedPhoto.uploaded_at) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { nextTick, onBeforeUnmount, ref } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import flatpickr from 'flatpickr'
import 'flatpickr/dist/flatpickr.min.css'

const props = defineProps({
    evaluation: { type: Object, required: true },
    followUpData: { type: Object, required: true },
})

const deadlineForms = ref({})
const commentForms = ref({})
const expandedComments = ref({})
const showPhotoModal = ref(false)
const selectedPhoto = ref(null)
const { t } = useI18n()
const flatpickrInstances = ref({})

const initializeForms = () => {
    props.followUpData.sections.forEach((section) => {
        section.bad_questions.forEach((question) => {
            deadlineForms.value[question.answer_id] = question.follow_up?.expected_deadline || ''
            commentForms.value[question.answer_id] = {
                comment_type: 'qc_comment',
                comment_text: '',
            }
            expandedComments.value[question.answer_id] = false
        })
    })

    nextTick(() => {
        initFlatpickrInstances()
    })
}

const initFlatpickrInstances = () => {
    props.followUpData.sections.forEach((section) => {
        section.bad_questions.forEach((question) => {
            const el = document.querySelector(`[data-deadline-picker="${question.answer_id}"]`)
            if (el && !flatpickrInstances.value[question.answer_id]) {
                flatpickrInstances.value[question.answer_id] = flatpickr(el, {
                    dateFormat: 'Y-m-d',
                    defaultDate: deadlineForms.value[question.answer_id] || null,
                    allowInput: true,
                    onChange: (selectedDates, dateStr) => {
                        deadlineForms.value[question.answer_id] = dateStr
                    },
                })
            }
        })
    })
}

onBeforeUnmount(() => {
    Object.values(flatpickrInstances.value).forEach((instance) => {
        if (instance && typeof instance.destroy === 'function') {
            instance.destroy()
        }
    })
})

const toggleComments = (answerId) => {
    expandedComments.value[answerId] = !expandedComments.value[answerId]
}

const openPhotoModal = (photo) => {
    selectedPhoto.value = photo
    showPhotoModal.value = true
}

const closePhotoModal = () => {
    showPhotoModal.value = false
    selectedPhoto.value = null
}

const isDeadlineOverdue = (deadline) => {
    if (!deadline) return false
    return new Date(deadline) < new Date(new Date().toDateString())
}

const formatDeadlineDate = (dateString) => {
    if (!dateString) return '-'
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    })
}

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

const statusLabel = (status) => {
    if (status === 'solved') return t('quality.solved_status')
    if (status === 'skipped') return t('quality.skipped_status')
    return t('quality.open_status')
}

const statusBadgeClass = (status) => {
    if (status === 'solved') return 'bg-green-100 text-green-700'
    if (status === 'skipped') return 'bg-gray-100 text-gray-700'
    return 'bg-orange-100 text-orange-700'
}

const saveDeadline = (question) => {
    router.post(route('quality-evaluation-follow-ups.answers.deadline', [props.evaluation.id, question.answer_id]), {
        expected_deadline: deadlineForms.value[question.answer_id],
    }, {
        preserveScroll: true,
    })
}

const submitComment = (question) => {
    const form = commentForms.value[question.answer_id]

    router.post(route('quality-evaluation-follow-ups.answers.comments.store', [props.evaluation.id, question.answer_id]), form, {
        preserveScroll: true,
        onSuccess: () => {
            commentForms.value[question.answer_id].comment_text = ''
            expandedComments.value[question.answer_id] = true
        },
    })
}

const markSolved = (question) => {
    router.post(route('quality-evaluation-follow-ups.answers.mark-solved', [props.evaluation.id, question.answer_id]), {}, {
        preserveScroll: true,
    })
}

const markSkipped = (question) => {
    router.post(route('quality-evaluation-follow-ups.answers.mark-skipped', [props.evaluation.id, question.answer_id]), {}, {
        preserveScroll: true,
    })
}

initializeForms()
</script>