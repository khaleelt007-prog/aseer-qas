<script setup>
import { ref, watch } from 'vue';
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
const followUpData = ref(null);

async function fetchDetails(id) {
    if (!id) return;
    loading.value = true;
    error.value = null;
    evaluation.value = null;
    followUpData.value = null;

    try {
        const response = await axios.get(route('super-admin.follow-ups.show', id), {
            headers: {
                'X-Inertia': true,
                'X-Inertia-Version': page.version,
                'Accept': 'text/html, application/xhtml+xml',
            },
        });

        const propsPayload = response.data?.props ?? {};
        evaluation.value = propsPayload.evaluation ?? null;
        followUpData.value = propsPayload.followUpData ?? null;
    } catch (e) {
        error.value = 'Unable to load follow-up details.';
    } finally {
        loading.value = false;
    }
}

function close() {
    emit('update:open', false);
}

watch(() => props.open, (isOpen) => {
    if (isOpen && props.evaluationId) {
        fetchDetails(props.evaluationId);
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

function formatScore(score) {
    if (score === null || score === undefined || score === '') return 0;
    const n = Number(score);
    return Number.isInteger(n) ? n : n.toFixed(1);
}

function locationLabel(branch) {
    if (!branch) return '';
    const country = branch.country?.localized_name || branch.country?.name || '';
    const brand = branch.brand?.localized_name || branch.brand?.name || '';
    const branchName = branch.localized_name || branch.name || '';
    return [country, brand, branchName].filter(Boolean).join(' › ');
}

function statusLabel(status) {
    if (status === 'solved') return 'Solved';
    if (status === 'skipped') return 'Skipped';
    return 'Open';
}

function statusBadgeClass(status) {
    if (status === 'solved') return 'bg-emerald-50 text-emerald-700';
    if (status === 'skipped') return 'bg-gray-100 text-gray-700';
    return 'bg-orange-50 text-orange-700';
}

function isDeadlineOverdue(deadline) {
    if (!deadline) return false;
    return new Date(deadline) < new Date(new Date().toDateString());
}
</script>

<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-start justify-center bg-black/60 p-4 sm:p-6"
        @click.self="close"
    >
        <div class="my-6 flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
            <!-- Header -->
            <div class="flex items-center justify-between gap-3 border-b border-indigo-100 bg-gradient-to-r from-indigo-50 to-blue-50 px-6 py-4">
                <div>
                    <h2 class="text-lg font-semibold text-indigo-900">Follow-Up Details</h2>
                    <p v-if="evaluation?.branch" class="text-xs text-indigo-700/80">{{ locationLabel(evaluation.branch) }}</p>
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
            <div class="flex-1 overflow-y-auto bg-gray-50 px-6 py-5">
                <div v-if="loading" class="flex items-center justify-center py-20 text-sm text-gray-500">
                    <svg class="mr-2 h-5 w-5 animate-spin text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Loading follow-up details…
                </div>

                <div v-else-if="error" class="rounded-2xl border border-red-200 bg-red-50 px-5 py-10 text-center text-sm text-red-700">
                    {{ error }}
                </div>

                <div v-else-if="evaluation && followUpData">
                    <div
                        class="mb-4 rounded-2xl border bg-white p-5 shadow-sm"
                        :class="evaluation.warning_flag ? 'border-red-300 bg-red-50/50' : 'border-gray-200'"
                    >
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <h1 class="text-base font-semibold text-gray-900">
                                    {{ followUpData.template_name }} — {{ evaluation.branch?.localized_name || evaluation.branch?.name }}
                                </h1>
                                <div class="mt-1 flex flex-wrap items-center gap-3 text-xs text-gray-500">
                                    <span>Created: {{ formatDate(evaluation.created_at) }}</span>
                                    <span v-if="evaluation.completed_at">Completed: {{ formatDate(evaluation.completed_at) }}</span>
                                    <span>Score: {{ formatScore(evaluation.total_score) }}/{{ formatScore(evaluation.max_score) }}</span>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <span class="inline-flex rounded-full bg-orange-50 px-3 py-1 text-xs font-semibold text-orange-700">Bad answers only</span>
                                <span v-if="evaluation.warning_flag" class="inline-flex rounded-full bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">Overdue warning</span>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div v-for="section in followUpData.sections" :key="section.id" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                            <div class="flex items-center justify-between border-b border-indigo-100 bg-indigo-50/70 px-5 py-3">
                                <div>
                                    <h3 class="text-base font-semibold text-indigo-900">{{ section.localized_name }}</h3>
                                    <p class="text-xs text-indigo-700/80">{{ section.bad_questions.length }} bad question(s)</p>
                                </div>
                            </div>

                            <div class="p-5">
                            <div v-if="section.comment_questions.length" class="mb-4 rounded-xl border border-blue-100 bg-blue-50/60 p-3">
                                <h4 class="mb-2 text-xs font-semibold uppercase tracking-wide text-blue-900">Section comments</h4>
                                <div class="space-y-2">
                                    <div v-for="cq in section.comment_questions" :key="cq.answer_id" class="rounded-lg border border-blue-100 bg-white p-3">
                                        <p class="mb-1 text-xs font-medium text-gray-700">{{ cq.question }}</p>
                                        <p class="whitespace-pre-wrap text-sm text-gray-600">{{ cq.answer_value || '—' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-3">
                                <div
                                    v-for="question in section.bad_questions"
                                    :key="question.answer_id"
                                    class="rounded-xl border p-4"
                                    :class="question.severity === 'high' ? 'border-red-200 bg-red-50/60' : 'border-yellow-200 bg-yellow-50/60'"
                                >
                                    <div class="mb-2 flex flex-wrap items-center gap-2">
                                        <h4 class="text-sm font-semibold text-gray-900">{{ question.question }}</h4>
                                        <span
                                            class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                                            :class="question.severity === 'high' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-800'"
                                        >{{ question.answer_label }}</span>
                                        <span
                                            v-if="question.follow_up?.status"
                                            class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold"
                                            :class="statusBadgeClass(question.follow_up.status)"
                                        >{{ statusLabel(question.follow_up.status) }}</span>
                                    </div>

                                    <div class="mb-3 flex flex-wrap items-center gap-3 text-xs text-gray-600">
                                        <span class="text-gray-500">Deadline:</span>
                                        <span
                                            v-if="question.follow_up?.expected_deadline"
                                            class="inline-flex rounded-full px-2 py-0.5 font-semibold"
                                            :class="isDeadlineOverdue(question.follow_up.expected_deadline) ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'"
                                        >{{ question.follow_up.expected_deadline }}</span>
                                        <span v-else class="italic text-gray-400">Not set</span>
                                        <span v-if="question.follow_up?.solved_at">Solved: <span class="font-semibold text-emerald-700">{{ formatDate(question.follow_up.solved_at) }}</span></span>
                                        <span v-if="question.follow_up?.skipped_at">Skipped: <span class="font-semibold text-gray-700">{{ formatDate(question.follow_up.skipped_at) }}</span></span>
                                    </div>

                                    <div v-if="question.follow_up?.comments?.length" class="space-y-2">
                                        <div
                                            v-for="comment in question.follow_up.comments"
                                            :key="comment.id"
                                            class="rounded-lg border border-gray-200 bg-white p-3"
                                        >
                                            <div class="mb-1 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                                <span class="rounded-full border border-gray-200 bg-gray-50 px-2 py-0.5 font-semibold text-gray-700">
                                                    {{ comment.comment_type === 'branch_reply' ? 'Branch reply' : 'QC comment' }}
                                                </span>
                                                <span>{{ formatDate(comment.comment_date) }}</span>
                                                <span v-if="comment.author_name">• {{ comment.author_name }}</span>
                                            </div>
                                            <p class="whitespace-pre-wrap text-sm text-gray-700">{{ comment.comment_text }}</p>
                                        </div>
                                    </div>
                                    <p v-else class="text-xs italic text-gray-400">No follow-up comments yet.</p>
                                </div>
                            </div>
                            </div>
                        </div>

                        <div v-if="!followUpData.sections.length" class="rounded-2xl border border-gray-200 bg-white p-10 text-center text-gray-500">
                            No follow-ups recorded for this evaluation.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex items-center justify-end gap-2 border-t border-gray-200 bg-gray-50 px-6 py-3">
                <button
                    type="button"
                    class="rounded-lg border border-gray-200 bg-white px-4 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    @click="close"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</template>
