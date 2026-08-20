<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';
import QualityFilter from '@/Components/QualityFilter.vue';
import EvaluationTable from '@/Components/SuperAdmin/EvaluationTable.vue';
import FollowUpDetailsModal from '@/Components/SuperAdmin/FollowUpDetailsModal.vue';
import EvaluationDetailsModal from '@/Components/SuperAdmin/EvaluationDetailsModal.vue';
import QcEmailLogModal from '@/Components/SuperAdmin/QcEmailLogModal.vue';

const props = defineProps({
    evaluations: { type: Object, required: true },
    countries: { type: Array, default: () => [] },
    brands: { type: Array, default: () => [] },
    branches: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const STATUS_OPTIONS = [
    { value: '', label: 'All statuses' },
    { value: 'completed', label: 'Completed' },
    { value: 'draft', label: 'Draft' },
    { value: 'pending', label: 'Pending' },
];

const TYPE_OPTIONS = [
    { value: '', label: 'All types' },
    { value: 'checklist', label: 'Checklist' },
    { value: 'regular', label: 'Regular' },
];

const localFilters = ref({
    country_id: props.filters.country_id || '',
    brand_id: props.filters.brand_id || '',
    branch_id: props.filters.branch_id || '',
    status: props.filters.status || '',
    type: props.filters.type || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

const meta = computed(() => ({
    from: props.evaluations?.from ?? 0,
    to: props.evaluations?.to ?? 0,
    total: props.evaluations?.total ?? 0,
}));

function applyFilters() {
    const params = { ...localFilters.value };
    Object.keys(params).forEach((k) => { if (!params[k]) delete params[k]; });
    router.get(route('super-admin.quality-evaluations.index'), params, {
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

watch(() => localFilters.value.status, applyFilters);
watch(() => localFilters.value.type, applyFilters);

function goToPage(url) {
    if (!url) return;
    router.get(url, {}, { preserveScroll: true, preserveState: true });
}

const followUpModalOpen = ref(false);
const followUpEvaluationId = ref(null);

function openFollowUpModal(id) {
    followUpEvaluationId.value = id;
    followUpModalOpen.value = true;
}

const isDetailsModalOpen = ref(false);
const selectedEvaluationId = ref(null);

function openDetailsModal(id) {
    selectedEvaluationId.value = id;
    isDetailsModalOpen.value = true;
}

const emailLogModalOpen = ref(false);
const selectedEmailLogEvaluation = ref(null);

function openEmailLogModal(evaluation) {
    selectedEmailLogEvaluation.value = evaluation;
    emailLogModalOpen.value = true;
}

const exportUrl = computed(() => {
    const params = { ...localFilters.value };
    Object.keys(params).forEach((k) => { if (!params[k]) delete params[k]; });
    const qs = new URLSearchParams(params).toString();
    const base = route('super-admin.quality-evaluations.export');
    return qs ? `${base}?${qs}` : base;
});
</script>

<template>
    <Head title="Quality Evaluations - Super Admin" />

    <SuperAdminLayout>
        <template #header>Quality Evaluations</template>

        <!-- Page actions -->
        <div class="mb-4 flex flex-wrap items-center justify-end gap-3">
            <a
                :href="exportUrl"
                class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-100"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/></svg>
                Export to Excel
            </a>
        </div>

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

        <!-- Status / Type quick filters -->
        <div class="mb-4 flex flex-wrap items-end gap-3 rounded-2xl border border-indigo-100 bg-indigo-50/50 p-4 shadow-sm ring-1 ring-indigo-100">
            <div class="min-w-[180px]">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-indigo-700">Status</label>
                <select v-model="localFilters.status" class="w-full rounded-lg border border-indigo-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <option v-for="opt in STATUS_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
            </div>
            <div class="min-w-[180px]">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-indigo-700">Type</label>
                <select v-model="localFilters.type" class="w-full rounded-lg border border-indigo-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <option v-for="opt in TYPE_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                </select>
            </div>
            <div class="ml-auto text-xs text-gray-600">
                Showing <span class="font-semibold text-indigo-700">{{ meta.from }}</span>–<span class="font-semibold text-indigo-700">{{ meta.to }}</span>
                of <span class="font-semibold text-indigo-700">{{ meta.total }}</span>
            </div>
        </div>

        <!-- Table card -->
        <EvaluationTable
            :evaluations="evaluations"
            @page-change="goToPage"
            @view-follow-up="openFollowUpModal"
            @view-details="openDetailsModal"
            @view-email-log="openEmailLogModal"
        />

        <FollowUpDetailsModal
            v-model:open="followUpModalOpen"
            :evaluation-id="followUpEvaluationId"
        />

        <EvaluationDetailsModal
            v-model:open="isDetailsModalOpen"
            :evaluation-id="selectedEvaluationId"
        />

        <QcEmailLogModal
            v-model:open="emailLogModalOpen"
            :evaluation="selectedEmailLogEvaluation"
        />
    </SuperAdminLayout>
</template>
