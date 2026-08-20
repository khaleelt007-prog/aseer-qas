<script setup>
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    open: { type: Boolean, default: false },
    evaluationId: { type: [Number, String, null], default: null },
});

const emit = defineEmits(['update:open']);

const page = usePage();
const loading = ref(false);
const error = ref(null);
const evaluation = ref(null);
const evaluationItems = ref([]);
const checklistData = ref(null);
const selectedPhoto = ref(null);
const expandedSections = ref({});

async function fetchDetails(id) {
    if (!id) return;
    loading.value = true;
    error.value = null;
    evaluation.value = null;
    evaluationItems.value = [];
    checklistData.value = null;

    try {
        const response = await axios.get(route('quality-evaluations.show', id), {
            headers: {
                'X-Inertia': true,
                'X-Inertia-Version': page.version,
                'Accept': 'text/html, application/xhtml+xml',
            },
        });

        const propsPayload = response.data?.props ?? {};
        evaluation.value = propsPayload.evaluation ?? null;
        evaluationItems.value = propsPayload.evaluationItems ?? [];
        checklistData.value = propsPayload.checklistData ?? null;
    } catch (e) {
        error.value = 'Unable to load evaluation details.';
    } finally {
        loading.value = false;
    }
}

function close() {
    if (selectedPhoto.value) {
        selectedPhoto.value = null;
        return;
    }
    emit('update:open', false);
}

watch(() => props.open, (isOpen) => {
    if (isOpen && props.evaluationId) {
        fetchDetails(props.evaluationId);
    }
    if (!isOpen) {
        selectedPhoto.value = null;
        expandedSections.value = {};
    }
});

watch(() => props.evaluationId, (newId) => {
    if (props.open && newId) {
        fetchDetails(newId);
    }
});

function formatDate(value) {
    if (!value) return '—';
    try { return new Date(value).toLocaleString(); } catch (e) { return value; }
}

function formatScore(value) {
    if (value === null || value === undefined || value === '') return 0;
    const n = Number(value);
    if (Number.isNaN(n)) return 0;
    return Number.isInteger(n) ? n : n.toFixed(1);
}

function formatFileSize(bytes) {
    if (!bytes) return '0 bytes';
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
    if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
    return bytes + ' bytes';
}

function userName(u) {
    if (!u) return '—';
    const full = `${u.first_name ?? ''} ${u.last_name ?? ''}`.trim();
    return full || u.username || '—';
}

function locationLabel(branch) {
    if (!branch) return '';
    const country = branch.country?.localized_name || branch.country?.name || '';
    const brand = branch.brand?.localized_name || branch.brand?.name || '';
    const branchName = branch.localized_name || branch.name || '';
    return [country, brand, branchName].filter(Boolean).join(' › ');
}

function statusClasses(status) {
    if (status === 'completed') return 'bg-emerald-50 text-emerald-700';
    if (status === 'pending') return 'bg-amber-50 text-amber-700';
    return 'bg-gray-100 text-gray-700';
}

function calculateItemPercentage(item) {
    if (item.achieved !== null && item.achieved !== undefined && item.max > 0) {
        return ((item.achieved / item.max) * 100).toFixed(1);
    }
    return 0;
}

function getPerformanceBadgeClass(score) {
    const n = Number(score);
    if (n >= 90) return 'bg-emerald-100 text-emerald-800';
    if (n >= 80) return 'bg-yellow-100 text-yellow-800';
    if (n >= 70) return 'bg-orange-100 text-orange-800';
    return 'bg-red-100 text-red-800';
}

function getPerformanceLabel(score) {
    const n = Number(score);
    if (n >= 90) return 'Excellent';
    if (n >= 80) return 'Good';
    if (n >= 70) return 'Fair';
    return 'Needs improvement';
}

const totalChecklistQuestions = computed(() => {
    if (!checklistData.value || !checklistData.value.sections) return 0;
    return checklistData.value.sections.reduce((sum, s) => sum + (s.questions?.length || 0), 0);
});

const overallPercentage = computed(() => {
    const ev = evaluation.value;
    if (!ev) return 0;
    if (ev.type === 'checklist') {
        const max = parseFloat(ev.max_score);
        const total = parseFloat(ev.total_score);
        if (!max || Number.isNaN(max) || max <= 0) return 0;
        return Math.min(100, Math.round((total / max) * 100));
    }
    return Math.min(100, Math.round(parseFloat(ev.total_score) || 0));
});

function getSectionPhotos(sectionId) {
    if (!evaluation.value?.photos) return [];
    return evaluation.value.photos.filter((p) => p.section_id === sectionId);
}

function toggleSectionPhotos(sectionId) {
    expandedSections.value[sectionId] = !expandedSections.value[sectionId];
}

function openPhoto(photo) {
    selectedPhoto.value = photo;
}

function closePhoto() {
    selectedPhoto.value = null;
}
</script>


<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-start justify-center bg-black/60 p-4 sm:p-6"
        @click.self="close"
    >
        <div class="my-6 flex max-h-[92vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
            <!-- Header -->
            <div class="flex items-start justify-between gap-3 border-b border-indigo-100 bg-gradient-to-r from-indigo-50 to-blue-50 px-6 py-4">
                <div class="min-w-0">
                    <h2 class="truncate text-lg font-semibold text-indigo-900">
                        <span v-if="evaluation">{{ evaluation.title || `Evaluation #${evaluation.id}` }}</span>
                        <span v-else>Evaluation Details</span>
                    </h2>
                    <p v-if="evaluation" class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-indigo-700/80">
                        <span>#{{ evaluation.id }}</span>
                        <span>·</span>
                        <span>{{ formatDate(evaluation.created_at) }}</span>
                        <span>·</span>
                        <span>By {{ userName(evaluation.user) }}</span>
                    </p>
                </div>
                <button
                    type="button"
                    class="rounded-lg p-2 text-indigo-600 hover:bg-white/70 hover:text-indigo-800"
                    aria-label="Close"
                    @click="close"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Body -->
            <div class="flex-1 overflow-y-auto bg-gray-50 px-4 py-5 sm:px-6">
                <div v-if="loading" class="flex items-center justify-center py-20 text-sm text-gray-500">
                    <svg class="mr-2 h-5 w-5 animate-spin text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Loading evaluation details…
                </div>

                <div v-else-if="error" class="rounded-2xl border border-red-200 bg-red-50 px-5 py-10 text-center text-sm text-red-700">
                    {{ error }}
                </div>

                <div v-else-if="evaluation" class="space-y-4">
                    <!-- Header card: location, status, score -->
                    <div
                        class="rounded-2xl border bg-white p-5 shadow-sm"
                        :class="evaluation.warning_flag ? 'border-red-300 bg-red-50/50' : 'border-gray-200'"
                    >
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Location</p>
                                <p class="mt-1 break-words text-sm font-medium text-gray-900">{{ locationLabel(evaluation.branch) || '—' }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                    <span v-if="evaluation.completed_at">Completed: {{ formatDate(evaluation.completed_at) }}</span>
                                    <span v-if="evaluation.type === 'checklist' && checklistData?.template_name">· {{ checklistData.template_name }}</span>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold capitalize" :class="statusClasses(evaluation.status)">
                                    {{ evaluation.status }}
                                </span>
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold capitalize" :class="evaluation.type === 'checklist' ? 'bg-indigo-50 text-indigo-700' : 'bg-blue-50 text-blue-700'">
                                    {{ evaluation.type }}
                                </span>
                                <span v-if="evaluation.warning_flag" class="inline-flex rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">
                                    Flagged
                                </span>
                            </div>
                        </div>

                        <!-- Score block -->
                        <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Total score</p>
                                <p class="mt-1 text-2xl font-semibold text-gray-900">
                                    <span dir="ltr">{{ formatScore(evaluation.total_score) }}</span>
                                    <span v-if="evaluation.type === 'checklist' && evaluation.max_score" class="text-base font-normal text-gray-400"> / {{ formatScore(evaluation.max_score) }}</span>
                                    <span v-else-if="evaluation.type !== 'checklist'" class="text-base font-normal text-gray-400"> / 100</span>
                                </p>
                                <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-white">
                                    <div class="h-full rounded-full bg-indigo-500 transition-all duration-500" :style="{ width: overallPercentage + '%' }"></div>
                                </div>
                            </div>
                            <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Performance</p>
                                <span class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-semibold" :class="getPerformanceBadgeClass(overallPercentage)">
                                    {{ getPerformanceLabel(overallPercentage) }} ({{ overallPercentage }}%)
                                </span>
                            </div>
                            <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Evaluator</p>
                                <p class="mt-1 truncate text-sm font-semibold text-gray-900">{{ userName(evaluation.user) }}</p>
                                <p v-if="evaluation.user?.username" class="text-xs text-gray-500">{{ evaluation.user.username }}</p>
                            </div>
                        </div>
                    </div>


                    <!-- Checklist sections -->
                    <template v-if="evaluation.type === 'checklist' && checklistData">
                        <div
                            v-for="section in checklistData.sections"
                            :key="section.id"
                            class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm"
                        >
                            <div class="border-b border-indigo-100 bg-indigo-50/70 px-5 py-3">
                                <h3 class="text-base font-semibold text-indigo-900">{{ section.localized_name }}</h3>
                            </div>
                            <div class="p-5">

                            <div class="space-y-3">
                                <div
                                    v-for="question in section.questions"
                                    :key="question.id"
                                    class="rounded-xl border border-gray-200 bg-gray-50 p-3"
                                >
                                    <div class="mb-2 flex flex-wrap items-start justify-between gap-2">
                                        <h4 class="text-sm font-medium text-gray-800">{{ question.localized_name }}</h4>
                                        <div class="flex items-center gap-2">
                                            <span
                                                v-if="question.achieved_score !== null && question.achieved_score !== undefined && question.max_score !== null && question.max_score !== undefined"
                                                class="rounded bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-700"
                                                dir="ltr"
                                            >
                                                {{ formatScore(question.achieved_score) }}/{{ formatScore(question.max_score) }}
                                            </span>
                                            <span v-if="question.is_required" class="text-xs font-semibold text-red-500">*</span>
                                        </div>
                                    </div>

                                    <!-- Point-based question -->
                                    <div v-if="question.q_type === 1">
                                        <span
                                            v-if="!checklistData.answer_type || checklistData.answer_type === 'Points'"
                                            class="inline-block rounded-full px-3 py-1 text-xs font-medium"
                                            :class="{
                                                'bg-emerald-100 text-emerald-800': question.answer_value === '1' || question.answer_value === 1,
                                                'bg-yellow-100 text-yellow-800': question.answer_value === '0.5' || question.answer_value === 0.5,
                                                'bg-red-100 text-red-800': question.answer_value === '0' || question.answer_value === 0,
                                                'bg-gray-100 text-gray-700': !question.answer_value && question.answer_value !== 0 && question.answer_value !== '0',
                                            }"
                                        >
                                            {{ question.answer_value === '1' || question.answer_value === 1 ? '1 PT' : (question.answer_value === '0.5' || question.answer_value === 0.5 ? '0.5 PT' : (question.answer_value === '0' || question.answer_value === 0 ? '0 PT' : 'Not answered')) }}
                                        </span>
                                        <span
                                            v-else-if="checklistData.answer_type === 'Yes/No'"
                                            class="inline-block rounded-full px-3 py-1 text-xs font-medium"
                                            :class="{
                                                'bg-emerald-100 text-emerald-800': question.answer_value === '1' || question.answer_value === 1,
                                                'bg-red-100 text-red-800': question.answer_value === '0' || question.answer_value === 0,
                                                'bg-gray-100 text-gray-700': !question.answer_value && question.answer_value !== 0 && question.answer_value !== '0',
                                            }"
                                        >
                                            {{ question.answer_value === '1' || question.answer_value === 1 ? 'Yes' : (question.answer_value === '0' || question.answer_value === 0 ? 'No' : 'Not answered') }}
                                        </span>
                                    </div>

                                    <!-- Text question -->
                                    <div v-else-if="question.q_type === 2">
                                        <div v-if="question.answer_value" class="whitespace-pre-wrap rounded-lg bg-white p-3 text-sm text-gray-700 ring-1 ring-gray-200">
                                            {{ question.answer_value }}
                                        </div>
                                        <p v-else class="text-xs italic text-gray-400">No answer provided</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Section photos -->
                            <div v-if="getSectionPhotos(section.id).length > 0" class="mt-5 border-t border-gray-200 pt-4">
                                <button
                                    type="button"
                                    class="flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-gray-900"
                                    @click="toggleSectionPhotos(section.id)"
                                >
                                    <svg
                                        class="h-4 w-4 transition-transform"
                                        :class="{ 'rotate-180': expandedSections[section.id] }"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                    </svg>
                                    <span>Section photos</span>
                                    <span class="rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-700">
                                        {{ getSectionPhotos(section.id).length }}
                                    </span>
                                </button>

                                <div v-if="expandedSections[section.id]" class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                                    <button
                                        v-for="photo in getSectionPhotos(section.id)"
                                        :key="photo.id"
                                        type="button"
                                        class="group relative overflow-hidden rounded-lg border border-gray-200 bg-white"
                                        @click="openPhoto(photo)"
                                    >
                                        <img :src="photo.url" :alt="photo.original_filename || 'Section photo'" class="h-28 w-full object-cover transition-transform group-hover:scale-105" loading="lazy" />
                                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-2 text-left">
                                            <p class="truncate text-xs text-white">{{ photo.original_filename }}</p>
                                        </div>
                                    </button>
                                </div>
                            </div>
                            </div>
                        </div>

                        <div v-if="!checklistData.sections.length" class="rounded-2xl border border-gray-200 bg-white p-10 text-center text-gray-500">
                            No sections recorded for this evaluation.
                        </div>
                    </template>

                    <!-- Evaluation items (regular evaluations) -->
                    <template v-if="evaluation.type !== 'checklist' && evaluationItems.length">
                        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                            <div class="border-b border-indigo-100 bg-indigo-50/70 px-5 py-3">
                                <h3 class="text-base font-semibold text-indigo-900">Evaluation items</h3>
                            </div>
                            <div class="p-5">
                            <div class="space-y-3">
                                <div
                                    v-for="item in evaluationItems"
                                    :key="item.id"
                                    class="rounded-xl border border-gray-200 bg-gray-50 p-4"
                                >
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <h4 class="text-sm font-semibold text-gray-900">{{ item.localized_title || item.title }}</h4>
                                            <p class="mt-1 text-xs text-gray-500">Weight: {{ Math.round(item.weight) }}%</p>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="rounded bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700" dir="ltr">
                                                {{ Math.round(item.achieved || 0) }}/{{ Math.round(item.max) }}
                                            </span>
                                            <span class="rounded-full px-3 py-1 text-xs font-medium" :class="getPerformanceBadgeClass(calculateItemPercentage(item))">
                                                {{ Math.round(calculateItemPercentage(item)) }}%
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-white">
                                        <div class="h-full rounded-full bg-indigo-500" :style="{ width: calculateItemPercentage(item) + '%' }"></div>
                                    </div>
                                </div>
                            </div>
                            </div>
                        </div>
                    </template>

                    <!-- Comments -->
                    <div v-if="evaluation.comments" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-indigo-100 bg-indigo-50/70 px-5 py-3">
                            <h3 class="text-base font-semibold text-indigo-900">Additional comments</h3>
                        </div>
                        <p class="whitespace-pre-wrap p-5 text-sm text-gray-700">{{ evaluation.comments }}</p>
                    </div>

                    <!-- General photo gallery -->
                    <div v-if="evaluation.photos && evaluation.photos.length > 0" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-indigo-100 bg-indigo-50/70 px-5 py-3">
                            <h3 class="text-base font-semibold text-indigo-900">Photo documentation</h3>
                            <span class="rounded-full bg-white/80 px-3 py-0.5 text-xs font-semibold text-indigo-700 ring-1 ring-indigo-200">
                                {{ evaluation.photos.length }} photo(s)
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-3 p-5 sm:grid-cols-3 lg:grid-cols-4">
                            <button
                                v-for="photo in evaluation.photos"
                                :key="photo.id"
                                type="button"
                                class="group relative overflow-hidden rounded-lg border border-gray-200 bg-white"
                                @click="openPhoto(photo)"
                            >
                                <img :src="photo.url" :alt="photo.original_filename || 'Evaluation photo'" class="h-32 w-full object-cover transition-transform group-hover:scale-105" loading="lazy" />
                                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/70 to-transparent p-2 text-left">
                                    <p class="truncate text-xs text-white">{{ photo.original_filename }}</p>
                                    <p class="truncate text-xs text-white/75">{{ photo.formatted_file_size || formatFileSize(photo.file_size) }}</p>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div v-if="evaluation.type === 'checklist' && checklistData" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-indigo-100 bg-indigo-50/70 px-5 py-3">
                            <h3 class="text-base font-semibold text-indigo-900">Evaluation summary</h3>
                        </div>
                        <div class="grid grid-cols-2 gap-4 p-5">
                            <div class="rounded-xl bg-blue-50 p-4 text-center">
                                <p class="text-2xl font-bold text-blue-700">{{ checklistData.sections.length }}</p>
                                <p class="text-xs text-blue-800">Sections</p>
                            </div>
                            <div class="rounded-xl bg-emerald-50 p-4 text-center">
                                <p class="text-2xl font-bold text-emerald-700">{{ totalChecklistQuestions }}</p>
                                <p class="text-xs text-emerald-800">Questions</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-3">
                <a
                    v-if="evaluation"
                    :href="route('quality-evaluations.download-pdf', evaluation.id)"
                    class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100"
                    title="Download PDF"
                >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                    PDF
                </a>
                <button
                    type="button"
                    class="rounded-lg border border-gray-200 bg-white px-4 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    @click="emit('update:open', false)"
                >
                    Close
                </button>
            </div>
        </div>

        <!-- Photo lightbox -->
        <div
            v-if="selectedPhoto"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-black/80 p-4"
            @click.self="closePhoto"
        >
            <div class="relative flex h-full max-h-full w-full max-w-4xl items-center justify-center">
                <button
                    type="button"
                    class="absolute right-2 top-2 rounded-full bg-black/60 p-2 text-white hover:bg-black/80"
                    aria-label="Close photo"
                    @click="closePhoto"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                <img :src="selectedPhoto.url" :alt="selectedPhoto.original_filename || 'Photo'" class="max-h-full max-w-full rounded-lg object-contain" />
                <div class="absolute inset-x-0 bottom-0 rounded-b-lg bg-gradient-to-t from-black/80 to-transparent p-4 text-white">
                    <p class="font-medium">{{ selectedPhoto.original_filename }}</p>
                    <p class="text-xs opacity-75">
                        <span>{{ selectedPhoto.formatted_file_size || formatFileSize(selectedPhoto.file_size) }}</span>
                        <span v-if="selectedPhoto.uploaded_at" class="mx-1">·</span>
                        <span v-if="selectedPhoto.uploaded_at">{{ formatDate(selectedPhoto.uploaded_at) }}</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

