<script setup>
import { computed } from 'vue';

const props = defineProps({
    evaluations: { type: Object, default: () => ({}) },
    showPagination: { type: Boolean, default: true },
    emptyText: { type: String, default: 'No evaluations found.' },
});

const emit = defineEmits(['page-change', 'view-follow-up', 'view-details', 'view-email-log']);

const rows = computed(() => props.evaluations?.data ?? []);
const meta = computed(() => ({
    current_page: props.evaluations?.current_page ?? 1,
    last_page: props.evaluations?.last_page ?? 1,
    from: props.evaluations?.from ?? 0,
    to: props.evaluations?.to ?? 0,
    total: props.evaluations?.total ?? 0,
    links: props.evaluations?.links ?? [],
}));

function goToPage(url) {
    if (!url) return;
    emit('page-change', url);
}

function userName(u) {
    if (!u) return '—';
    const full = `${u.first_name ?? ''} ${u.last_name ?? ''}`.trim();
    return full || u.username || '—';
}

function formatDate(value) {
    if (!value) return '—';
    try { return new Date(value).toLocaleString(); } catch (e) { return value; }
}

function formatScore(value) {
    if (value === null || value === undefined || value === '') return '—';
    const n = parseFloat(value);
    if (Number.isNaN(n)) return '—';
    return Number.isInteger(n) ? n : n.toFixed(2);
}

function statusClasses(status) {
    switch (status) {
        case 'completed': return 'bg-emerald-50 text-emerald-700';
        case 'pending': return 'bg-amber-50 text-amber-700';
        case 'draft': return 'bg-gray-100 text-gray-700';
        default: return 'bg-gray-100 text-gray-700';
    }
}

function typeClasses(type) {
    return type === 'checklist'
        ? 'bg-indigo-50 text-indigo-700'
        : 'bg-blue-50 text-blue-700';
}

function locationLabel(branch) {
    if (!branch) return '—';
    const country = branch.country?.localized_name || branch.country?.name || '';
    const brand = branch.brand?.localized_name || branch.brand?.name || '';
    const branchName = branch.localized_name || branch.name || '';
    return [country, brand, branchName].filter(Boolean).join(' › ') || '—';
}
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="border-b border-slate-200 bg-slate-100 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">
                    <tr>
                        <th class="px-5 py-3">Title / ID</th>
                        <th class="px-5 py-3">Evaluator</th>
                        <th class="px-5 py-3">Location</th>
                        <th class="px-5 py-3">Type</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">Total score</th>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    <tr v-for="evaluation in rows" :key="evaluation.id" class="transition-colors hover:bg-indigo-50/40">
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2">
                                <span v-if="evaluation.warning_flag" class="inline-flex h-2 w-2 rounded-full bg-red-500" title="Flagged"></span>
                                <div>
                                    <div class="font-medium text-gray-900">{{ evaluation.title || `Evaluation #${evaluation.id}` }}</div>
                                    <div class="text-xs text-gray-500">#{{ evaluation.id }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3">{{ userName(evaluation.user) }}</td>
                        <td class="px-5 py-3 text-gray-700">{{ locationLabel(evaluation.branch) }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="typeClasses(evaluation.type)">
                                {{ evaluation.type }}
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="statusClasses(evaluation.status)">
                                {{ evaluation.status }}
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <span class="font-semibold text-gray-900">{{ formatScore(evaluation.total_score) }}</span>
                            <span v-if="evaluation.type === 'checklist' && evaluation.max_score" class="text-gray-400"> / {{ formatScore(evaluation.max_score) }}</span>
                        </td>
                        <td class="px-5 py-3 text-gray-500">{{ formatDate(evaluation.created_at) }}</td>
                        <td class="px-5 py-3 text-right">
                            <div class="inline-flex items-center gap-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                    title="View evaluation details"
                                    @click.prevent="emit('view-details', evaluation.id)"
                                >
                                    View
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                                <button
                                    v-if="(evaluation.follow_ups_count ?? 0) > 0"
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100"
                                    title="Follow-up details"
                                    @click="emit('view-follow-up', evaluation.id)"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h6m-6 4h6m-6 4h4m-7 5l3-3h11a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14z"/></svg>
                                    Follow-up
                                </button>
                                <button
                                    v-if="evaluation.email_logs?.length"
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-100"
                                    :title="`View ${evaluation.email_logs.length} email log(s)`"
                                    @click="emit('view-email-log', evaluation)"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8m-16 9h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    {{ evaluation.email_logs.length }}
                                </button>
                                <a
                                    :href="route('quality-evaluations.download-pdf', evaluation.id)"
                                    class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-700 hover:bg-rose-100"
                                    title="Download PDF"
                                >
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                                    PDF
                                </a>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!rows.length">
                        <td colspan="8" class="px-5 py-10 text-center text-gray-500">{{ emptyText }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="showPagination && meta.last_page > 1" class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-5 py-3">
            <p class="text-xs text-gray-500">
                Page <span class="font-semibold text-gray-700">{{ meta.current_page }}</span>
                of <span class="font-semibold text-gray-700">{{ meta.last_page }}</span>
            </p>
            <div class="flex flex-wrap gap-1">
                <button
                    v-for="(link, i) in meta.links"
                    :key="i"
                    type="button"
                    :disabled="!link.url"
                    class="rounded-md border px-3 py-1.5 text-xs font-medium transition-colors"
                    :class="[
                        link.active
                            ? 'border-indigo-600 bg-indigo-600 text-white shadow-sm'
                            : 'border-gray-200 bg-white text-gray-700 hover:border-indigo-200 hover:bg-indigo-50',
                        !link.url ? 'opacity-40 cursor-not-allowed' : '',
                    ]"
                    v-html="link.label"
                    @click="goToPage(link.url)"
                />
            </div>
        </div>
    </div>
</template>
