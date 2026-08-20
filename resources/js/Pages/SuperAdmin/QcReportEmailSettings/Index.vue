<script setup>
import { computed, nextTick, onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import SlimSelect from 'slim-select';
import 'slim-select/styles';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';

const props = defineProps({
    companies: { type: Array, default: () => [] },
});

const page = usePage();
const errors = computed(() => page.props.errors || {});
const flash = computed(() => page.props.flash || {});
const processing = ref(false);
const companySelect = ref(null);
const statusSearch = ref('');
const statusFilter = ref('all');
let companySlimSelect = null;
const selectedCompanyId = ref(props.companies[0]?.id ? String(props.companies[0].id) : '');
const selectedCompany = computed(() => props.companies.find(
    company => Number(company.id) === Number(selectedCompanyId.value),
));

const form = reactive({
    to_emails: [''],
    cc_emails: [],
    is_active: false,
});

const matchingStatusCompanies = computed(() => {
    const search = statusSearch.value.trim().toLowerCase();

    return props.companies.filter((company) => {
        const isActive = Boolean(company.email_setting?.is_active);
        const matchesStatus = statusFilter.value === 'all'
            || (statusFilter.value === 'active' && isActive)
            || (statusFilter.value === 'inactive' && !isActive);
        const matchesSearch = !search || company.name.toLowerCase().includes(search);

        return matchesStatus && matchesSearch;
    });
});

const visibleStatusCompanies = computed(() => matchingStatusCompanies.value.slice(0, 10));

function loadSetting() {
    const setting = selectedCompany.value?.email_setting;
    form.to_emails = setting?.to_emails?.length ? [...setting.to_emails] : [''];
    form.cc_emails = setting?.cc_emails?.length ? [...setting.cc_emails] : [];
    form.is_active = Boolean(setting?.is_active);
}

watch(selectedCompanyId, (value) => {
    loadSetting();
    nextTick(() => companySlimSelect?.setSelected(String(value)));
}, { immediate: true });

onMounted(() => {
    if (!companySelect.value) return;

    companySlimSelect = new SlimSelect({
        select: companySelect.value,
        settings: {
            searchHighlight: true,
            searchPlaceholder: 'Search companies...',
            searchText: 'No companies found',
            placeholderText: 'Select a company',
        },
        events: {
            afterChange: (selected) => {
                selectedCompanyId.value = selected[0]?.value ?? '';
            },
        },
    });

    companySlimSelect.setSelected(String(selectedCompanyId.value));
});

onUnmounted(() => {
    companySlimSelect?.destroy();
    companySlimSelect = null;
});

function addToEmail() {
    form.to_emails.push('');
}

function addCcEmail() {
    form.cc_emails.push('');
}

function removeEmail(list, index) {
    list.splice(index, 1);
    if (list === form.to_emails && list.length === 0) list.push('');
}

function submit() {
    if (!selectedCompany.value) return;

    processing.value = true;
    router.post(route('super-admin.qc-report-email-settings.store'), {
        company_id: selectedCompany.value.id,
        to_emails: form.to_emails.map(email => email.trim()).filter(Boolean),
        cc_emails: form.cc_emails.map(email => email.trim()).filter(Boolean),
        is_active: form.is_active,
    }, {
        preserveScroll: true,
        onFinish: () => { processing.value = false; },
    });
}
</script>

<template>
    <Head title="QC Report Email Settings" />

    <SuperAdminLayout>
        <template #header>QC Report Email Settings</template>

        <div v-if="flash.success" class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ flash.success }}
        </div>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,2fr)_minmax(280px,1fr)]">
            <form class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm" @submit.prevent="submit">
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-900">Company recipients</h2>
                    <p class="mt-1 text-sm text-gray-500">Completed QC reports are sent with the generated PDF attached.</p>
                </div>

                <div class="mb-6">
                    <InputLabel for="company_id" value="Company" />
                    <select
                        id="company_id"
                        ref="companySelect"
                        v-model="selectedCompanyId"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option disabled value="">Select a company</option>
                        <option v-for="company in companies" :key="company.id" :value="String(company.id)">
                            {{ company.name }}
                        </option>
                    </select>
                    <InputError :message="errors.company_id" class="mt-2" />
                </div>

                <fieldset class="mb-6">
                    <legend class="text-sm font-medium text-gray-700">To recipients</legend>
                    <p class="mb-3 mt-1 text-xs text-gray-500">At least one address is required when delivery is active.</p>
                    <div v-for="(_, index) in form.to_emails" :key="`to-${index}`" class="mb-2">
                        <div class="flex gap-2">
                            <TextInput v-model="form.to_emails[index]" type="email" class="block w-full" placeholder="franchise@example.com" />
                            <button type="button" class="rounded-md border border-red-200 px-3 text-sm text-red-600 hover:bg-red-50" @click="removeEmail(form.to_emails, index)">
                                Remove
                            </button>
                        </div>
                        <InputError :message="errors[`to_emails.${index}`]" class="mt-1" />
                    </div>
                    <InputError :message="errors.to_emails" class="mb-2" />
                    <button type="button" class="text-sm font-medium text-indigo-600 hover:text-indigo-800" @click="addToEmail">
                        + Add TO email
                    </button>
                </fieldset>

                <fieldset class="mb-6">
                    <legend class="text-sm font-medium text-gray-700">CC recipients</legend>
                    <p class="mb-3 mt-1 text-xs text-gray-500">Optional copy recipients.</p>
                    <div v-for="(_, index) in form.cc_emails" :key="`cc-${index}`" class="mb-2">
                        <div class="flex gap-2">
                            <TextInput v-model="form.cc_emails[index]" type="email" class="block w-full" placeholder="manager@example.com" />
                            <button type="button" class="rounded-md border border-red-200 px-3 text-sm text-red-600 hover:bg-red-50" @click="removeEmail(form.cc_emails, index)">
                                Remove
                            </button>
                        </div>
                        <InputError :message="errors[`cc_emails.${index}`]" class="mt-1" />
                    </div>
                    <InputError :message="errors.cc_emails" class="mb-2" />
                    <button type="button" class="text-sm font-medium text-indigo-600 hover:text-indigo-800" @click="addCcEmail">
                        + Add CC email
                    </button>
                </fieldset>

                <label class="mb-6 flex items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <input v-model="form.is_active" type="checkbox" class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    <span>
                        <span class="block text-sm font-medium text-gray-900">Active email delivery</span>
                        <span class="block text-xs text-gray-500">Send completed QC reports for branches belonging to this company.</span>
                    </span>
                </label>

                <button
                    type="submit"
                    :disabled="processing || !selectedCompany"
                    class="rounded-md bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ processing ? 'Saving...' : 'Save settings' }}
                </button>
            </form>

            <section class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 class="text-base font-semibold text-gray-900">Company status</h2>
                <div class="mt-4 space-y-3">
                    <input
                        v-model="statusSearch"
                        type="search"
                        placeholder="Search companies..."
                        class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                    <select
                        v-model="statusFilter"
                        class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="all">All statuses</option>
                        <option value="active">Active only</option>
                        <option value="inactive">Inactive only</option>
                    </select>
                    <p class="text-xs text-gray-500">
                        Showing {{ visibleStatusCompanies.length }} of {{ matchingStatusCompanies.length }} matching companies
                    </p>
                </div>
                <div class="mt-4 space-y-3">
                    <button
                        v-for="company in visibleStatusCompanies"
                        :key="company.id"
                        type="button"
                        class="flex w-full items-center justify-between rounded-lg border px-3 py-3 text-left"
                        :class="Number(company.id) === Number(selectedCompanyId) ? 'border-indigo-300 bg-indigo-50' : 'border-gray-200 hover:bg-gray-50'"
                        @click="selectedCompanyId = String(company.id)"
                    >
                        <span class="truncate text-sm font-medium text-gray-800">{{ company.name }}</span>
                        <span
                            class="ml-3 rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="company.email_setting?.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600'"
                        >
                            {{ company.email_setting?.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </button>
                    <p v-if="matchingStatusCompanies.length === 0" class="text-sm text-gray-500">No companies match the selected filters.</p>
                </div>
            </section>
        </div>
    </SuperAdminLayout>
</template>