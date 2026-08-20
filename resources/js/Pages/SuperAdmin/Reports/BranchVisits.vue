<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';
import QualityFilter from '@/Components/QualityFilter.vue';
import EvaluationTable from '@/Components/SuperAdmin/EvaluationTable.vue';
import EvaluationDetailsModal from '@/Components/SuperAdmin/EvaluationDetailsModal.vue';

const props = defineProps({
    topBranches: { type: Array, default: () => [] },
    notVisited: { type: Array, default: () => [] },
    needsAttention: { type: Array, default: () => [] },
    countries: { type: Array, default: () => [] },
    brands: { type: Array, default: () => [] },
    branches: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    days_threshold: { type: Number, default: 30 },
});

const TABS = [
    { id: 'top', label: 'Top Branches' },
    { id: 'not-visited', label: 'Not Visited' },
    { id: 'needs-attention', label: 'Needs Attention' },
];
const activeTab = ref('top');

const localFilters = ref({
    country_id: props.filters.country_id || '',
    brand_id: props.filters.brand_id || '',
    branch_id: props.filters.branch_id || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    days_threshold: props.filters.days_threshold || props.days_threshold || 30,
});

function applyFilters() {
    const params = { ...localFilters.value };
    Object.keys(params).forEach((k) => { if (params[k] === '' || params[k] === null) delete params[k]; });
    router.get(route('super-admin.reports.branch-visits.index'), params, {
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

function handleThresholdChange() {
    const v = parseInt(localFilters.value.days_threshold, 10);
    if (!Number.isFinite(v) || v < 1) return;
    applyFilters();
}

function locationLabel(branch) {
    if (!branch) return '—';
    const country = branch.country?.localized_name || branch.country?.name || '';
    const brand = branch.brand?.localized_name || branch.brand?.name || '';
    return [country, brand].filter(Boolean).join(' › ') || '—';
}

function branchName(branch) {
    if (!branch) return '—';
    return branch.localized_name || branch.name || `#${branch.id}`;
}

function formatDate(value) {
    if (!value) return '—';
    try { return new Date(value).toLocaleDateString(); } catch (e) { return value; }
}

function formatAvgWeeks(value) {
    if (value === null || value === undefined) return 'N/A';
    const n = Number(value);
    if (!Number.isFinite(n)) return 'N/A';
    return `${n.toFixed(2)} wk`;
}

const totalTop = computed(() => props.topBranches.length);
const totalNotVisited = computed(() => props.notVisited.length);
const totalNeedsAttention = computed(() => props.needsAttention.length);

// Drilldown modal state
const modalOpen = ref(false);
const modalBranch = ref(null);
const modalRow = ref(null);
const modalLoading = ref(false);
const modalError = ref('');
const modalEvaluations = ref({});

const modalMeta = computed(() => ({
    current_page: modalEvaluations.value?.current_page ?? 1,
    last_page: modalEvaluations.value?.last_page ?? 1,
    from: modalEvaluations.value?.from ?? 0,
    to: modalEvaluations.value?.to ?? 0,
    total: modalEvaluations.value?.total ?? 0,
    links: modalEvaluations.value?.links ?? [],
}));

async function loadEvaluations(branchId, url = null) {
    modalLoading.value = true;
    modalError.value = '';
    try {
        const params = { ...localFilters.value };
        delete params.days_threshold;
        Object.keys(params).forEach((k) => { if (!params[k]) delete params[k]; });
        const target = url || route('super-admin.reports.branch-visits.evaluations', branchId);
        const response = await window.axios.get(target, { params: url ? {} : params });
        modalEvaluations.value = response.data || {};
    } catch (e) {
        modalError.value = 'Failed to load evaluations.';
        modalEvaluations.value = {};
    } finally {
        modalLoading.value = false;
    }
}

function openDrilldown(row) {
    modalRow.value = row;
    modalBranch.value = row.branch;
    modalEvaluations.value = {};
    modalOpen.value = true;
    loadEvaluations(row.branch_id);
}

function closeDrilldown() {
    modalOpen.value = false;
    modalBranch.value = null;
    modalRow.value = null;
    modalEvaluations.value = {};
    modalError.value = '';
}

function handleModalPageChange(url) {
    if (!modalRow.value) return;
    loadEvaluations(modalRow.value.branch_id, url);
}

const isDetailsModalOpen = ref(false);
const selectedEvaluationId = ref(null);

function openDetailsModal(id) {
    selectedEvaluationId.value = id;
    isDetailsModalOpen.value = true;
}
</script>

<template>
    <Head title="Branch Visits - Super Admin" />

    <SuperAdminLayout>
        <template #header>Branch Visits Report</template>

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

        <!-- Tabs -->
        <div class="mb-4 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex flex-wrap gap-1 border-b border-indigo-100 bg-indigo-50/60 px-3 pt-3">
                <button
                    v-for="tab in TABS"
                    :key="tab.id"
                    type="button"
                    class="rounded-t-lg px-4 py-2 text-sm font-medium transition-colors"
                    :class="activeTab === tab.id
                        ? 'border border-indigo-200 border-b-white bg-white text-indigo-700 shadow-sm'
                        : 'text-indigo-700/70 hover:bg-white/60 hover:text-indigo-800'"
                    @click="activeTab = tab.id"
                >
                    {{ tab.label }}
                    <span
                        class="ml-1 inline-flex min-w-[1.5rem] justify-center rounded-full px-1.5 py-0.5 text-xs font-semibold"
                        :class="activeTab === tab.id ? 'bg-indigo-100 text-indigo-700' : 'bg-white/70 text-indigo-700/80'"
                    >
                        <template v-if="tab.id === 'top'">{{ totalTop }}</template>
                        <template v-else-if="tab.id === 'not-visited'">{{ totalNotVisited }}</template>
                        <template v-else>{{ totalNeedsAttention }}</template>
                    </span>
                </button>
            </div>

            <!-- Top Branches -->
            <div v-show="activeTab === 'top'" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="border-b border-slate-200 bg-slate-100 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">
                        <tr>
                            <th class="px-5 py-3">#</th>
                            <th class="px-5 py-3">Branch</th>
                            <th class="px-5 py-3">Location</th>
                            <th class="px-5 py-3">Total evaluations</th>
                            <th class="px-5 py-3">Average weeks</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        <tr
                            v-for="(row, idx) in topBranches"
                            :key="row.branch_id"
                            class="cursor-pointer transition-colors hover:bg-indigo-50/40"
                            @click="openDrilldown(row)"
                        >
                            <td class="px-5 py-3 font-semibold text-gray-500">{{ idx + 1 }}</td>
                            <td class="px-5 py-3 font-medium text-gray-900">{{ branchName(row.branch) }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ locationLabel(row.branch) }}</td>
                            <td class="px-5 py-3 font-semibold text-gray-900">{{ row.total }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ formatAvgWeeks(row.average_weeks) }}</td>
                            <td class="px-5 py-3 text-right">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                    @click.stop="openDrilldown(row)"
                                >
                                    View
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!topBranches.length">
                            <td colspan="6" class="px-5 py-10 text-center text-gray-500">No completed evaluations found for the current filters.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Not Visited -->
            <div v-show="activeTab === 'not-visited'" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="border-b border-slate-200 bg-slate-100 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">
                        <tr>
                            <th class="px-5 py-3">#</th>
                            <th class="px-5 py-3">Branch</th>
                            <th class="px-5 py-3">Location</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        <tr v-for="(branch, idx) in notVisited" :key="branch.id" class="transition-colors hover:bg-indigo-50/40">
                            <td class="px-5 py-3 font-semibold text-gray-500">{{ idx + 1 }}</td>
                            <td class="px-5 py-3 font-medium text-gray-900">{{ branchName(branch) }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ locationLabel(branch) }}</td>
                        </tr>
                        <tr v-if="!notVisited.length">
                            <td colspan="3" class="px-5 py-10 text-center text-gray-500">All branches have been visited within the current filters.</td>
                        </tr>
                    </tbody>
                </table>
            </div>


            <!-- Needs Attention -->
            <div v-show="activeTab === 'needs-attention'" class="overflow-x-auto">
                <div class="flex flex-wrap items-center gap-3 border-b border-indigo-100 bg-indigo-50/60 px-5 py-3 text-xs text-gray-600">
                    <label for="days_threshold" class="font-semibold text-indigo-700">Days threshold</label>
                    <input
                        id="days_threshold"
                        v-model="localFilters.days_threshold"
                        type="number"
                        min="1"
                        max="365"
                        class="w-24 rounded-md border-indigo-200 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        @change="handleThresholdChange"
                    />
                    <span class="text-gray-600">Branches whose latest completed evaluation is older than this many days.</span>
                </div>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="border-b border-slate-200 bg-slate-100 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">
                        <tr>
                            <th class="px-5 py-3">#</th>
                            <th class="px-5 py-3">Branch</th>
                            <th class="px-5 py-3">Location</th>
                            <th class="px-5 py-3">Last visit</th>
                            <th class="px-5 py-3">Days since</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        <tr v-for="(row, idx) in needsAttention" :key="row.branch_id" class="transition-colors hover:bg-indigo-50/40">
                            <td class="px-5 py-3 font-semibold text-gray-500">{{ idx + 1 }}</td>
                            <td class="px-5 py-3 font-medium text-gray-900">{{ branchName(row.branch) }}</td>
                            <td class="px-5 py-3 text-gray-600">{{ locationLabel(row.branch) }}</td>
                            <td class="px-5 py-3 text-gray-700">{{ formatDate(row.last_completed_at) }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">
                                    {{ row.last_visit_human || `${row.days_since} days ago` }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!needsAttention.length">
                            <td colspan="5" class="px-5 py-10 text-center text-gray-500">No branches exceed the current threshold.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Drilldown modal -->
        <div
            v-if="modalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4 py-8"
            @click.self="closeDrilldown"
        >
            <div class="flex max-h-[90vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
                <div class="flex shrink-0 items-start justify-between gap-4 border-b border-indigo-100 bg-gradient-to-r from-indigo-50 to-blue-50 px-6 py-4">
                    <div>
                        <h2 class="text-lg font-semibold text-indigo-900">
                            Evaluations at {{ branchName(modalBranch) }}
                        </h2>
                        <p class="text-xs text-indigo-700/80">
                            <span>{{ locationLabel(modalBranch) }}</span>
                            <span v-if="modalRow"> · {{ modalRow.total }} completed evaluation(s)</span>
                        </p>
                    </div>
                    <button
                        type="button"
                        class="rounded-md p-1 text-indigo-600 hover:bg-white/70 hover:text-indigo-800"
                        aria-label="Close"
                        @click="closeDrilldown"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto bg-gray-50 px-6 py-4">
                    <div v-if="modalLoading" class="flex items-center justify-center py-12 text-sm text-gray-500">
                        Loading evaluations…
                    </div>
                    <div v-else-if="modalError" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        {{ modalError }}
                    </div>
                    <EvaluationTable
                        v-else
                        :evaluations="modalEvaluations"
                        :show-pagination="false"
                        empty-text="No evaluations found for this branch."
                        @view-details="openDetailsModal"
                    />
                </div>

                <div
                    v-if="!modalLoading && !modalError"
                    class="flex shrink-0 flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-6 py-3"
                >
                    <p class="text-xs text-gray-500">
                        <template v-if="modalMeta.total">
                            Showing <span class="font-semibold text-gray-700">{{ modalMeta.from }}</span>–<span class="font-semibold text-gray-700">{{ modalMeta.to }}</span>
                            of <span class="font-semibold text-gray-700">{{ modalMeta.total }}</span>
                            <span class="ml-2">· Page <span class="font-semibold text-gray-700">{{ modalMeta.current_page }}</span> of <span class="font-semibold text-gray-700">{{ modalMeta.last_page }}</span></span>
                        </template>
                        <template v-else>No results</template>
                    </p>
                    <div v-if="modalMeta.last_page > 1" class="flex flex-wrap gap-1">
                        <button
                            v-for="(link, i) in modalMeta.links"
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
                            @click="handleModalPageChange(link.url)"
                        />
                    </div>
                </div>
            </div>
        </div>

        <EvaluationDetailsModal
            v-model:open="isDetailsModalOpen"
            :evaluation-id="selectedEvaluationId"
        />
    </SuperAdminLayout>
</template>
