<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';
import QualityFilter from '@/Components/QualityFilter.vue';

const props = defineProps({
    stats: {
        type: Object,
        required: true,
    },
    top_evaluators: {
        type: Array,
        default: () => [],
    },
    branch_visits: {
        type: Object,
        default: () => ({
            total_branches: 0,
            visited_branches: 0,
            coverage_percentage: 0,
            needs_attention_count: 0,
            needs_attention_top: [],
        }),
    },
    follow_ups: {
        type: Object,
        default: () => ({
            open: 0,
            solved: 0,
            skipped: 0,
            total: 0,
            overdue: 0,
            evaluations_with_follow_ups: 0,
        }),
    },
    attention_threshold_days: {
        type: Number,
        default: 30,
    },
    recent_warnings: {
        type: Array,
        default: () => [],
    },
    countries: { type: Array, default: () => [] },
    brands: { type: Array, default: () => [] },
    branches: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const filterModalOpen = ref(false);

const localFilters = ref({
    country_id: props.filters.country_id || '',
    brand_id: props.filters.brand_id || '',
    branch_id: props.filters.branch_id || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

function applyFilters() {
    const params = { ...localFilters.value };
    Object.keys(params).forEach((k) => {
        if (params[k] === '' || params[k] === null) delete params[k];
    });
    router.get(route('super-admin.dashboard'), params, {
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

function resetFilters() {
    localFilters.value = {
        country_id: '', brand_id: '', branch_id: '',
        start_date: '', end_date: '',
    };
    router.get(route('super-admin.dashboard'), {}, {
        preserveScroll: true,
        preserveState: true,
        replace: true,
    });
}

function removeFilter(key) {
    if (key === 'date_range') {
        localFilters.value.start_date = '';
        localFilters.value.end_date = '';
    } else if (key in localFilters.value) {
        localFilters.value[key] = '';
        if (key === 'country_id') {
            localFilters.value.brand_id = '';
            localFilters.value.branch_id = '';
        } else if (key === 'brand_id') {
            localFilters.value.branch_id = '';
        }
    }
    applyFilters();
}

function lookup(list, id) {
    if (!id) return null;
    return list.find((item) => String(item.id) === String(id)) || null;
}

const activeFilters = computed(() => {
    const chips = [];
    const country = lookup(props.countries, props.filters.country_id);
    if (country) chips.push({ key: 'country_id', label: 'Country', value: country.localized_name || country.name });
    const brand = lookup(props.brands, props.filters.brand_id);
    if (brand) chips.push({ key: 'brand_id', label: 'Brand', value: brand.localized_name || brand.name });
    const branch = lookup(props.branches, props.filters.branch_id);
    if (branch) chips.push({ key: 'branch_id', label: 'Branch', value: branch.localized_name || branch.name });
    if (props.filters.start_date && props.filters.end_date) {
        chips.push({ key: 'date_range', label: 'Date', value: `${props.filters.start_date} → ${props.filters.end_date}` });
    } else if (props.filters.start_date) {
        chips.push({ key: 'date_range', label: 'From', value: props.filters.start_date });
    } else if (props.filters.end_date) {
        chips.push({ key: 'date_range', label: 'Until', value: props.filters.end_date });
    }
    return chips;
});

const activeFilterCount = computed(() => activeFilters.value.length);

const total = computed(() => props.stats.total_evaluations || 0);

const completionRate = computed(() => {
    if (!total.value) return 0;
    return Math.round((props.stats.status.completed / total.value) * 100);
});

const checklistRate = computed(() => {
    if (!total.value) return 0;
    return Math.round((props.stats.types.checklist / total.value) * 100);
});

const regularRate = computed(() => {
    if (!total.value) return 0;
    return Math.round((props.stats.types.regular / total.value) * 100);
});

const pendingRate = computed(() => {
    if (!total.value) return 0;
    return Math.round((props.stats.status.pending / total.value) * 100);
});

const topEvaluatorMax = computed(() => {
    const totals = props.top_evaluators.map((e) => e.total || 0);
    return totals.length ? Math.max(...totals) : 0;
});

function evaluatorBarWidth(value) {
    if (!topEvaluatorMax.value) return 0;
    return Math.round((value / topEvaluatorMax.value) * 100);
}

const followUpTotal = computed(() => props.follow_ups.total || 0);

function followUpRate(value) {
    if (!followUpTotal.value) return 0;
    return Math.round((value / followUpTotal.value) * 100);
}

function formatDate(value) {
    if (!value) return '—';
    try {
        return new Date(value).toLocaleString();
    } catch (e) {
        return value;
    }
}

function formatDateShort(value) {
    if (!value) return '—';
    try {
        return new Date(value).toLocaleDateString();
    } catch (e) {
        return value;
    }
}

function userName(u) {
    if (!u) return '—';
    const full = `${u.first_name ?? ''} ${u.last_name ?? ''}`.trim();
    return full || u.username || '—';
}

function evaluatorDisplayName(e) {
    return e.full_name || e.username || `#${e.user_id}`;
}

function branchLocation(branch) {
    if (!branch) return '—';
    return [branch.country_name, branch.brand_name, branch.localized_name]
        .filter(Boolean)
        .join(' › ');
}
</script>

<template>
    <Head title="Super Admin Dashboard" />

    <SuperAdminLayout>
        <template #header>Super Admin Dashboard</template>

        <!-- Global filter bar (Country / Brand / Branch / Date range) -->
        <div class="mb-4 flex flex-wrap items-center gap-3 rounded-2xl border border-indigo-100 bg-indigo-50/50 p-3 shadow-sm ring-1 ring-indigo-100">
            <button
                type="button"
                class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-white px-3 py-2 text-sm font-medium text-indigo-700 shadow-sm hover:bg-indigo-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                @click="filterModalOpen = true"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L15 12.414V19a1 1 0 01-.553.894l-4 2A1 1 0 019 21v-8.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
                <span>Filters</span>
                <span
                    v-if="activeFilterCount"
                    class="ml-1 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-indigo-600 px-1.5 text-xs font-semibold text-white"
                >{{ activeFilterCount }}</span>
            </button>

            <div v-if="activeFilters.length" class="flex flex-wrap items-center gap-2">
                <span
                    v-for="chip in activeFilters"
                    :key="chip.key"
                    class="inline-flex items-center gap-1.5 rounded-full bg-white px-2.5 py-1 text-xs font-medium text-indigo-700 ring-1 ring-indigo-200"
                >
                    <span class="text-indigo-400">{{ chip.label }}:</span>
                    <span>{{ chip.value }}</span>
                    <button
                        type="button"
                        class="ml-0.5 rounded-full p-0.5 text-indigo-400 hover:bg-indigo-100 hover:text-indigo-700"
                        :aria-label="`Remove ${chip.label} filter`"
                        @click="removeFilter(chip.key)"
                    >
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </span>
                <button
                    type="button"
                    class="text-xs font-medium text-indigo-600 underline-offset-2 hover:underline"
                    @click="resetFilters"
                >Clear all</button>
            </div>
            <span v-else class="text-xs text-gray-500">No filters applied — showing all data.</span>
        </div>

        <!-- Metric cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                </div>
                <p class="mt-4 text-sm text-gray-500">Total evaluations</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ total }}</p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">{{ completionRate }}%</span>
                </div>
                <p class="mt-4 text-sm text-gray-500">Completed</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ stats.status.completed }}</p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">{{ pendingRate }}%</span>
                </div>
                <p class="mt-4 text-sm text-gray-500">Pending</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ stats.status.pending }}</p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17a4 4 0 100-8 4 4 0 000 8zm6 4l-3-3"/></svg>
                    </div>
                </div>
                <p class="mt-4 text-sm text-gray-500">Average total score</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ stats.average_total_score }}</p>
            </div>
        </div>

        <!-- Operational KPI cards: branch coverage + follow-up activity -->
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0zM15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700">{{ branch_visits.coverage_percentage }}%</span>
                </div>
                <p class="mt-4 text-sm text-gray-500">Branch visit coverage</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">
                    {{ branch_visits.visited_branches }}<span class="text-base font-medium text-gray-400"> / {{ branch_visits.total_branches }}</span>
                </p>
                <div class="mt-3 h-2 w-full overflow-hidden rounded-full bg-gray-100">
                    <div class="h-full rounded-full bg-indigo-500" :style="{ width: branch_visits.coverage_percentage + '%' }"></div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M4.93 19h14.14a2 2 0 001.74-2.99l-7.07-12a2 2 0 00-3.48 0l-7.07 12A2 2 0 004.93 19z"/></svg>
                    </div>
                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">&gt; {{ attention_threshold_days }}d</span>
                </div>
                <p class="mt-4 text-sm text-gray-500">Branches needing attention</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ branch_visits.needs_attention_count }}</p>
                <p class="mt-2 text-xs text-gray-500">No completed evaluation in the last {{ attention_threshold_days }} days.</p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h6m-6 4h6m-6 4h4m-7 5l3-3h11a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14z"/></svg>
                    </div>
                    <span
                        v-if="follow_ups.overdue > 0"
                        class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700 ring-1 ring-red-200"
                    >
                        {{ follow_ups.overdue }} overdue
                    </span>
                </div>
                <p class="mt-4 text-sm text-gray-500">Open follow-ups</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ follow_ups.open }}</p>
                <p class="mt-2 text-xs text-gray-500">{{ follow_ups.evaluations_with_follow_ups }} evaluations have follow-up activity.</p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">{{ followUpRate(follow_ups.solved) }}%</span>
                </div>
                <p class="mt-4 text-sm text-gray-500">Solved follow-ups</p>
                <p class="mt-1 text-3xl font-bold text-gray-900">{{ follow_ups.solved }}</p>
                <p class="mt-2 text-xs text-gray-500">{{ follow_ups.total }} total follow-up records.</p>
            </div>
        </div>

        <!-- Top evaluators + status breakdown -->
        <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-900">Top 5 evaluators</h2>
                    <Link
                        :href="route('super-admin.reports.top-evaluators.index')"
                        class="text-xs font-medium text-indigo-600 hover:text-indigo-800"
                    >View full report →</Link>
                </div>

                <ul v-if="top_evaluators.length" class="space-y-3">
                    <li v-for="(evaluator, idx) in top_evaluators" :key="evaluator.user_id">
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="flex items-center gap-2 font-medium text-gray-700">
                                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-indigo-50 text-xs font-semibold text-indigo-700">{{ idx + 1 }}</span>
                                {{ evaluatorDisplayName(evaluator) }}
                            </span>
                            <span class="text-gray-500">{{ evaluator.total }}</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full bg-indigo-500" :style="{ width: evaluatorBarWidth(evaluator.total) + '%' }"></div>
                        </div>
                    </li>
                </ul>
                <p v-else class="text-sm text-gray-500">No completed evaluations yet.</p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-base font-semibold text-gray-900">Status breakdown</h2>
                <ul class="space-y-3 text-sm">
                    <li class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-gray-700">
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                            Completed
                        </span>
                        <span class="font-semibold text-gray-900">{{ stats.status.completed }}</span>
                    </li>
                    <li class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-gray-700">
                            <span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span>
                            Pending
                        </span>
                        <span class="font-semibold text-gray-900">{{ stats.status.pending }}</span>
                    </li>
                    <li v-if="stats.status.other > 0" class="flex items-center justify-between">
                        <span class="flex items-center gap-2 text-gray-700">
                            <span class="h-2.5 w-2.5 rounded-full bg-gray-400"></span>
                            Other
                        </span>
                        <span class="font-semibold text-gray-900">{{ stats.status.other }}</span>
                    </li>
                </ul>
            </div>
        </div>


        <!-- Distribution + breakdown row -->
        <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm lg:col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-900">Evaluation type distribution</h2>
                    <span class="text-xs text-gray-500">Total: {{ total }}</span>
                </div>

                <div class="space-y-4">
                    <div>
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-700">Checklist</span>
                            <span class="text-gray-500">{{ stats.types.checklist }} ({{ checklistRate }}%)</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full bg-indigo-500" :style="{ width: checklistRate + '%' }"></div>
                        </div>
                    </div>

                    <div>
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-700">Regular</span>
                            <span class="text-gray-500">{{ stats.types.regular }} ({{ regularRate }}%)</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full bg-emerald-500" :style="{ width: regularRate + '%' }"></div>
                        </div>
                    </div>

                    <div v-if="stats.types.other > 0">
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-700">Other</span>
                            <span class="text-gray-500">{{ stats.types.other }}</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full bg-gray-400" :style="{ width: (total ? Math.round(stats.types.other / total * 100) : 0) + '%' }"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-base font-semibold text-gray-900">Follow-up activity</h2>
                    <Link
                        :href="route('super-admin.follow-ups.index')"
                        class="text-xs font-medium text-indigo-600 hover:text-indigo-800"
                    >View →</Link>
                </div>

                <div v-if="follow_ups.total > 0" class="space-y-4">
                    <div>
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-700">Open</span>
                            <span class="text-gray-500">{{ follow_ups.open }} ({{ followUpRate(follow_ups.open) }}%)</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full bg-amber-500" :style="{ width: followUpRate(follow_ups.open) + '%' }"></div>
                        </div>
                    </div>

                    <div>
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-700">Solved</span>
                            <span class="text-gray-500">{{ follow_ups.solved }} ({{ followUpRate(follow_ups.solved) }}%)</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full bg-emerald-500" :style="{ width: followUpRate(follow_ups.solved) + '%' }"></div>
                        </div>
                    </div>

                    <div>
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-700">Skipped</span>
                            <span class="text-gray-500">{{ follow_ups.skipped }} ({{ followUpRate(follow_ups.skipped) }}%)</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full bg-gray-400" :style="{ width: followUpRate(follow_ups.skipped) + '%' }"></div>
                        </div>
                    </div>

                    <div
                        v-if="follow_ups.overdue > 0"
                        class="flex items-center justify-between rounded-lg bg-red-100 px-3 py-2 text-xs font-medium text-red-700 ring-1 ring-red-200"
                    >
                        <span>{{ follow_ups.overdue }} overdue (open past deadline)</span>
                    </div>
                </div>
                <p v-else class="text-sm text-gray-500">No follow-up activity recorded.</p>
            </div>
        </div>

        <!-- Branches needing attention -->
        <div class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-indigo-100 bg-gradient-to-r from-indigo-50 to-blue-50 px-5 py-4">
                <div>
                    <h2 class="text-base font-semibold text-indigo-900">Branches needing attention</h2>
                    <p class="text-xs text-indigo-700/80">Top 5 branches with no completed evaluation in the last {{ attention_threshold_days }} days.</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                        {{ branch_visits.needs_attention_count }} total
                    </span>
                    <Link
                        :href="route('super-admin.reports.branch-visits.index')"
                        class="text-xs font-medium text-indigo-600 hover:text-indigo-800"
                    >View report →</Link>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="border-b border-slate-200 bg-slate-100 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">
                        <tr>
                            <th class="px-5 py-3">Branch</th>
                            <th class="px-5 py-3">Location</th>
                            <th class="px-5 py-3">Last visit</th>
                            <th class="px-5 py-3">Days since</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        <tr
                            v-for="row in branch_visits.needs_attention_top"
                            :key="row.branch_id"
                            class="transition-colors hover:bg-indigo-50/40"
                        >
                            <td class="px-5 py-3 font-medium text-gray-900">{{ row.branch?.localized_name ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ branchLocation(row.branch) }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ formatDateShort(row.last_completed_at) }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700">
                                    {{ row.days_since ?? '—' }} days
                                </span>
                            </td>
                        </tr>
                        <tr v-if="!branch_visits.needs_attention_top.length">
                            <td colspan="4" class="px-5 py-6 text-center text-gray-500">All branches are up to date.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent warnings table -->
        <div class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-indigo-100 bg-gradient-to-r from-indigo-50 to-blue-50 px-5 py-4">
                <h2 class="text-base font-semibold text-indigo-900">Recent flagged evaluations</h2>
                <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700 ring-1 ring-red-200">
                    {{ recent_warnings.length }} flagged
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="border-b border-slate-200 bg-slate-100 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">
                        <tr>
                            <th class="px-5 py-3">Title</th>
                            <th class="px-5 py-3">User</th>
                            <th class="px-5 py-3">Branch</th>
                            <th class="px-5 py-3">Type</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Score</th>
                            <th class="px-5 py-3">Flagged at</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-gray-700">
                        <tr v-for="evaluation in recent_warnings" :key="evaluation.id" class="transition-colors hover:bg-indigo-50/40">
                            <td class="px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex h-2 w-2 rounded-full bg-red-500"></span>
                                    <span class="font-medium text-gray-900">{{ evaluation.title || `#${evaluation.id}` }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3">{{ userName(evaluation.user) }}</td>
                            <td class="px-5 py-3">{{ evaluation.branch?.name ?? '—' }}</td>
                            <td class="px-5 py-3 capitalize">{{ evaluation.type }}</td>
                            <td class="px-5 py-3">
                                <span
                                    class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="evaluation.status === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700'"
                                >
                                    {{ evaluation.status }}
                                </span>
                            </td>
                            <td class="px-5 py-3">{{ evaluation.total_score ?? '—' }}</td>
                            <td class="px-5 py-3 text-gray-500">{{ formatDate(evaluation.warning_flagged_at || evaluation.completed_at || evaluation.created_at) }}</td>
                        </tr>
                        <tr v-if="!recent_warnings.length">
                            <td colspan="7" class="px-5 py-6 text-center text-gray-500">No flagged evaluations.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Filter modal -->
        <div
            v-if="filterModalOpen"
            class="fixed inset-0 z-50 flex items-start justify-center bg-black/60 p-4 sm:p-6"
            @click.self="filterModalOpen = false"
        >
            <div class="my-6 flex max-h-[92vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-3 border-b border-indigo-100 bg-gradient-to-r from-indigo-50 to-blue-50 px-6 py-4">
                    <div>
                        <h2 class="text-lg font-semibold text-indigo-900">Dashboard filters</h2>
                        <p class="mt-0.5 text-xs text-indigo-700/80">Country, Brand, Branch and date range — applied to every panel below.</p>
                    </div>
                    <button
                        type="button"
                        class="rounded-lg p-1.5 text-indigo-700 hover:bg-white/60"
                        aria-label="Close"
                        @click="filterModalOpen = false"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto px-6 py-5">
                    <div class="sa-filter-highlight">
                        <QualityFilter
                            :countries="countries"
                            :brands="brands"
                            :branches="branches"
                            :initial-filters="filters"
                            @filter-changed="handleFilterChanged"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-between gap-3 border-t border-gray-100 bg-gray-50 px-6 py-3">
                    <button
                        type="button"
                        class="text-sm font-medium text-gray-600 hover:text-gray-800"
                        @click="resetFilters"
                    >Reset all</button>
                    <button
                        type="button"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        @click="filterModalOpen = false"
                    >Done</button>
                </div>
            </div>
        </div>
    </SuperAdminLayout>
</template>
