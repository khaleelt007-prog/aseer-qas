<script setup>
import { Head, Link } from '@inertiajs/vue3';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';

const props = defineProps({
    evaluation: { type: Object, required: true },
    followUpData: { type: Object, required: true },
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
    <Head title="Follow-Up Details - Super Admin" />

    <SuperAdminLayout>
        <template #header>Follow-Up Details</template>

        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm text-gray-500">{{ locationLabel(evaluation.branch) }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Link
                    :href="route('super-admin.follow-ups.index')"
                    class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                >
                    Back to list
                </Link>
                <Link
                    :href="route('quality-evaluations.show', evaluation.id)"
                    class="inline-flex items-center gap-1 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100"
                >
                    View evaluation
                </Link>
            </div>
        </div>

        <div
            class="mb-4 rounded-2xl border bg-white p-5 shadow-sm"
            :class="evaluation.warning_flag ? 'border-red-300 bg-red-50/50' : 'border-gray-200'"
        >
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="text-lg font-semibold text-gray-900">
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
            <div v-for="section in followUpData.sections" :key="section.id" class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">{{ section.localized_name }}</h3>
                        <p class="text-xs text-gray-500">{{ section.bad_questions.length }} bad question(s)</p>
                    </div>
                </div>

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

            <div v-if="!followUpData.sections.length" class="rounded-2xl border border-gray-200 bg-white p-10 text-center text-gray-500">
                No follow-ups recorded for this evaluation.
            </div>
        </div>
    </SuperAdminLayout>
</template>
