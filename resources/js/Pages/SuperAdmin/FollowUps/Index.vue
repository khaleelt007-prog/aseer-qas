<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';
import QualityFilter from '@/Components/QualityFilter.vue';
import FollowUpDetailsModal from '@/Components/SuperAdmin/FollowUpDetailsModal.vue';

const props = defineProps({
    evaluations: { type: Object, required: true },
    countries: { type: Array, default: () => [] },
    brands: { type: Array, default: () => [] },
    branches: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const localFilters = ref({
    country_id: props.filters.country_id || '',
    brand_id: props.filters.brand_id || '',
    branch_id: props.filters.branch_id || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

const rows = computed(() => props.evaluations?.data ?? []);
const meta = computed(() => ({
    current_page: props.evaluations?.current_page ?? 1,
    last_page: props.evaluations?.last_page ?? 1,
    from: props.evaluations?.from ?? 0,
    to: props.evaluations?.to ?? 0,
    total: props.evaluations?.total ?? 0,
    links: props.evaluations?.links ?? [],
}));

function applyFilters() {
    const params = { ...localFilters.value };
    Object.keys(params).forEach((k) => { if (!params[k]) delete params[k]; });
    router.get(route('super-admin.follow-ups.index'), params, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
}

function handleFilterChanged(payload) {
    localFilters.value.country_id = payload.country_id ?? '';
    localFilters.value.brand_id = payload.brand_id ?? '';
    localFilters.value.branch_id = payload.branch_id ?? '';
    localFilters.value.start_date = payload.start_date ?? '';
    localFilters.value.end_date = payload.end_date ?? '';
    applyFilters();
}

function goToPage(url) {
    if (!url) return;
    router.get(url, {}, { preserveScroll: true, preserveState: true });
}

const modalOpen = ref(false);
const selectedEvaluationId = ref(null);

function openFollowUpModal(id) {
    selectedEvaluationId.value = id;
    modalOpen.value = true;
}

function formatDate(value) {
    if (!value) return '—';
    try { return new Date(value).toLocaleString(); } catch (e) { return value; }
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
    <Head title="Follow-Up Report - Super Admin" />

    <SuperAdminLayout>
        <template #header>Follow-Up Report</template>

        <!-- Filters -->
        <div class="sa-filter-highlight mb-4">
            <QualityFilter
                :countries="countries"
                :brands="brands"
                :branches="branches"
                :initial-filters="filters"
                @filter-changed="handleFilterChanged"
            />
        </div>

        <div class="mb-4 flex flex-wrap items-end gap-3 rounded-2xl border border-indigo-100 bg-indigo-50/50 p-4 shadow-sm ring-1 ring-indigo-100">
            <div class="ml-auto text-xs text-gray-600">
                Showing <span class="font-semibold text-indigo-700">{{ meta.from }}</span>–<span class="font-semibold text-indigo-700">{{ meta.to }}</span>
                of <span class="font-semibold text-indigo-700">{{ meta.total }}</span>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="border-b border-slate-200 bg-slate-100 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">
                        <tr>
                            <th class="px-5 py-3">Title / ID</th>
                            <th class="px-5 py-3">Location</th>
                            <th class="px-5 py-3">Open</th>
                            <th class="px-5 py-3">Solved</th>
                            <th class="px-5 py-3">Skipped</th>
                            <th class="px-5 py-3">Overdue</th>
                            <th class="px-5 py-3">Date</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        <tr v-for="evaluation in rows" :key="evaluation.id" class="transition-colors hover:bg-indigo-50/40">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <span v-if="evaluation.warning_flag" class="inline-flex h-2 w-2 rounded-full bg-red-500" title="Overdue follow-up"></span>
                                    <div>
                                        <div class="font-medium text-gray-900">{{ evaluation.title || `Evaluation #${evaluation.id}` }}</div>
                                        <div class="text-xs text-gray-500">#{{ evaluation.id }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-gray-700">{{ locationLabel(evaluation.branch) }}</td>
                            <td class="px-5 py-3"><span class="inline-flex rounded-full bg-orange-50 px-2 py-0.5 text-xs font-semibold text-orange-700">{{ evaluation.open_follow_ups_count || 0 }}</span></td>
                            <td class="px-5 py-3"><span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">{{ evaluation.solved_follow_ups_count || 0 }}</span></td>
                            <td class="px-5 py-3"><span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700">{{ evaluation.skipped_follow_ups_count || 0 }}</span></td>
                            <td class="px-5 py-3"><span class="inline-flex rounded-full bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700">{{ evaluation.overdue_follow_ups_count || 0 }}</span></td>
                            <td class="px-5 py-3 text-gray-500">{{ formatDate(evaluation.created_at) }}</td>
                            <td class="px-5 py-3 text-right">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100"
                                    @click="openFollowUpModal(evaluation.id)"
                                >
                                    View follow-ups
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!rows.length">
                            <td colspan="8" class="px-5 py-10 text-center text-gray-500">No evaluations with follow-ups found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="meta.last_page > 1" class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-5 py-3">
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

        <FollowUpDetailsModal
            v-model:open="modalOpen"
            :evaluation-id="selectedEvaluationId"
        />
    </SuperAdminLayout>
</template>
