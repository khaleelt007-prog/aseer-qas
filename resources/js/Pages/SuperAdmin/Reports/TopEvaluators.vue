<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';
import QualityFilter from '@/Components/QualityFilter.vue';
import EvaluationTable from '@/Components/SuperAdmin/EvaluationTable.vue';
import EvaluationDetailsModal from '@/Components/SuperAdmin/EvaluationDetailsModal.vue';

const props = defineProps({
    evaluators: { type: Array, default: () => [] },
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

const totalEvaluations = computed(() =>
    props.evaluators.reduce((sum, e) => sum + (Number(e.total) || 0), 0)
);

const maxCount = computed(() =>
    props.evaluators.reduce((max, e) => Math.max(max, Number(e.total) || 0), 0)
);

function applyFilters() {
    const params = { ...localFilters.value };
    Object.keys(params).forEach((k) => { if (!params[k]) delete params[k]; });
    router.get(route('super-admin.reports.top-evaluators.index'), params, {
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

function evaluatorName(row) {
    const full = (row.full_name || '').trim();
    return full || row.username || `User #${row.user_id}`;
}

function evaluatorInitial(row) {
    const name = evaluatorName(row);
    return name.charAt(0).toUpperCase();
}

function percentOfMax(value) {
    if (!maxCount.value) return 0;
    return Math.round((Number(value) / maxCount.value) * 100);
}

// Drilldown modal state
const modalOpen = ref(false);
const modalEvaluator = ref(null);
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

async function loadEvaluations(userId, url = null) {
    modalLoading.value = true;
    modalError.value = '';
    try {
        const params = { ...localFilters.value };
        Object.keys(params).forEach((k) => { if (!params[k]) delete params[k]; });
        const target = url || route('super-admin.reports.top-evaluators.evaluations', userId);
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
    modalEvaluator.value = row;
    modalEvaluations.value = {};
    modalOpen.value = true;
    loadEvaluations(row.user_id);
}

function closeDrilldown() {
    modalOpen.value = false;
    modalEvaluator.value = null;
    modalEvaluations.value = {};
    modalError.value = '';
}

function handleModalPageChange(url) {
    if (!modalEvaluator.value) return;
    loadEvaluations(modalEvaluator.value.user_id, url);
}

const isDetailsModalOpen = ref(false);
const selectedEvaluationId = ref(null);

function openDetailsModal(id) {
    selectedEvaluationId.value = id;
    isDetailsModalOpen.value = true;
}
</script>

<template>
    <Head title="Top Evaluators - Super Admin" />

    <SuperAdminLayout>
        <template #header>Top Evaluators</template>

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

        <!-- Summary stats -->
        <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Evaluators</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">{{ evaluators.length }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Completed evaluations</p>
                <p class="mt-1 text-2xl font-semibold text-gray-900">{{ totalEvaluations }}</p>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Top performer</p>
                <p class="mt-1 truncate text-2xl font-semibold text-gray-900">
                    {{ evaluators.length ? evaluatorName(evaluators[0]) : '—' }}
                </p>
            </div>
        </div>

        <!-- Ranking table -->
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="border-b border-slate-200 bg-slate-100 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">
                        <tr>
                            <th class="px-5 py-3">#</th>
                            <th class="px-5 py-3">Evaluator</th>
                            <th class="px-5 py-3">Username</th>
                            <th class="px-5 py-3">Completed evaluations</th>
                            <th class="px-5 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        <tr
                            v-for="(row, idx) in evaluators"
                            :key="row.user_id"
                            class="cursor-pointer transition-colors hover:bg-indigo-50/40"
                            @click="openDrilldown(row)"
                        >
                            <td class="px-5 py-3 font-semibold text-gray-500">{{ idx + 1 }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-50 text-xs font-semibold text-indigo-700">
                                        {{ evaluatorInitial(row) }}
                                    </span>
                                    <span class="font-medium text-gray-900">{{ evaluatorName(row) }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-gray-500">{{ row.username || '—' }}</td>
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="font-semibold text-gray-900">{{ row.total }}</span>
                                    <div class="h-1.5 w-32 overflow-hidden rounded-full bg-gray-100">
                                        <div class="h-full rounded-full bg-indigo-500" :style="{ width: percentOfMax(row.total) + '%' }"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                    @click.stop="openDrilldown(row)"
                                >
                                    View evaluations
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!evaluators.length">
                            <td colspan="5" class="px-5 py-10 text-center text-gray-500">No completed evaluations found for the current filters.</td>
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
                <!-- Sticky header -->
                <div class="flex shrink-0 items-start justify-between gap-4 border-b border-indigo-100 bg-gradient-to-r from-indigo-50 to-blue-50 px-6 py-4">
                    <div>
                        <h2 class="text-lg font-semibold text-indigo-900">
                            Evaluations by {{ modalEvaluator ? evaluatorName(modalEvaluator) : '' }}
                        </h2>
                        <p class="text-xs text-indigo-700/80">
                            <span v-if="modalEvaluator?.username">{{ modalEvaluator.username }} · </span>
                            <span>{{ modalEvaluator?.total ?? 0 }} completed evaluation(s)</span>
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

                <!-- Scrollable table area -->
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
                        empty-text="No evaluations found for this evaluator."
                        @view-details="openDetailsModal"
                    />
                </div>

                <!-- Sticky pagination footer -->
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
