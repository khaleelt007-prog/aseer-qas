<script setup>
import { computed, nextTick, onMounted, onUnmounted, reactive, ref } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import SuperAdminLayout from '@/Layouts/SuperAdminLayout.vue';
import SlimSelect from 'slim-select';
import 'slim-select/styles';

const props = defineProps({
    templates: { type: Array, default: () => [] },
    countries: { type: Array, default: () => [] },
    brands: { type: Array, default: () => [] },
});

const page = usePage();
const errors = computed(() => page.props.errors || {});
const flash = computed(() => page.props.flash || {});
const errorMessages = computed(() => Object.values(errors.value).flat().filter(Boolean));
const editingId = ref(null);
const processing = ref(false);
const countrySelect = ref(null);
const brandSelect = ref(null);
let countrySlimSelect = null;
let brandSlimSelect = null;

const questionTypeLabels = {
    points: 'Points',
    yes_no: 'Yes / No',
    multi_select: 'Multi-Select',
    score: 'Score',
    comment: 'Section Comment',
};

const blankQuestion = (sortOrder = 1) => ({
    id: null,
    name: '',
    name_ar: '',
    question_type: 'points',
    options: [''],
    score_value: 1,
    allow_manual_score: false,
    sort_order: sortOrder,
    is_required: true,
});

const blankCommentQuestion = (sortOrder = 2) => ({
    id: null,
    name: 'Section Comment',
    name_ar: 'تعليق القسم',
    question_type: 'comment',
    options: [''],
    score_value: null,
    allow_manual_score: false,
    sort_order: sortOrder,
    is_required: false,
});

const blankSection = (sortOrder = 1) => ({
    id: null,
    name: '',
    name_ar: '',
    sort_order: sortOrder,
    questions: [blankQuestion(1), blankCommentQuestion(2)],
});

function inferQuestionType(question, template = null) {
    if (question.question_type) return question.question_type;
    if (Number(question.q_type) === 2) return 'comment';
    if ((template?.answer_type || 'Points') === 'Yes/No') return 'yes_no';
    return 'points';
}

function reorderQuestions(section) {
    section.questions.forEach((question, idx) => { question.sort_order = idx + 1; });
}

function ensureSectionCommentQuestion(section) {
    if (!section.questions.some(question => question.question_type === 'comment')) {
        section.questions.push(blankCommentQuestion(section.questions.length + 1));
    }
    reorderQuestions(section);
}

const blankForm = () => ({
    name_en: '',
    name_ar: '',
    is_active: true,
    country_ids: [],
    brand_ids: [],
    sections: [blankSection(1)],
});

const form = reactive(blankForm());

function resetForm() {
    Object.assign(form, blankForm());
    editingId.value = null;
    syncSlimSelectValues();
}

function editTemplate(template) {
    editingId.value = template.id;
    Object.assign(form, JSON.parse(JSON.stringify({
        name_en: template.name_en,
        name_ar: template.name_ar,
        is_active: Boolean(template.is_active),
        country_ids: template.country_ids || [],
        brand_ids: template.brand_ids || [],
        sections: template.sections?.length ? template.sections.map(section => ({
            id: section.id,
            name: section.name,
            name_ar: section.name_ar,
            sort_order: section.sort_order,
            questions: section.questions.map(question => ({
                id: question.id,
                name: question.name,
                name_ar: question.name_ar,
                question_type: inferQuestionType(question, template),
                options: question.options?.length ? question.options : [''],
                score_value: question.score_value ?? 1,
                allow_manual_score: Boolean(question.allow_manual_score),
                sort_order: question.sort_order,
                is_required: Boolean(question.is_required),
            })),
        })) : [blankSection(1)],
    })));
    form.sections.forEach(ensureSectionCommentQuestion);
    syncSlimSelectValues();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function selectedValues(values) {
    return values.map((value) => String(value));
}

function syncSlimSelectValues() {
    nextTick(() => {
        countrySlimSelect?.setSelected(selectedValues(form.country_ids));
        brandSlimSelect?.setSelected(selectedValues(form.brand_ids));
    });
}

function initializeSlimSelects() {
    if (countrySelect.value && !countrySlimSelect) {
        countrySlimSelect = new SlimSelect({
            select: countrySelect.value,
            settings: {
                searchHighlight: true,
                searchPlaceholder: 'Search countries...',
                searchText: 'No countries found',
                placeholderText: 'Select countries',
                closeOnSelect: false,
            },
            events: {
                afterChange: (selected) => {
                    form.country_ids = selected.map((item) => Number(item.value));
                },
            },
        });
    }

    if (brandSelect.value && !brandSlimSelect) {
        brandSlimSelect = new SlimSelect({
            select: brandSelect.value,
            settings: {
                searchHighlight: true,
                searchPlaceholder: 'Search brands...',
                searchText: 'No brands found',
                placeholderText: 'Select brands',
                closeOnSelect: false,
            },
            events: {
                afterChange: (selected) => {
                    form.brand_ids = selected.map((item) => Number(item.value));
                },
            },
        });
    }

    syncSlimSelectValues();
}

onMounted(() => {
    initializeSlimSelects();
});

onUnmounted(() => {
    countrySlimSelect?.destroy();
    brandSlimSelect?.destroy();
    countrySlimSelect = null;
    brandSlimSelect = null;
});

function addSection() {
    form.sections.push(blankSection(form.sections.length + 1));
}

function removeSection(index) {
    if (form.sections.length === 1) return;
    form.sections.splice(index, 1);
    form.sections.forEach((section, idx) => { section.sort_order = idx + 1; });
}

function addQuestion(section) {
    const commentIndex = section.questions.findIndex(question => question.question_type === 'comment');
    if (commentIndex === -1) {
        section.questions.push(blankQuestion(section.questions.length + 1));
        ensureSectionCommentQuestion(section);
    } else {
        section.questions.splice(commentIndex, 0, blankQuestion(commentIndex + 1));
        reorderQuestions(section);
    }
}

function removeQuestion(section, index) {
    if (section.questions.length === 1 || section.questions[index]?.question_type === 'comment') return;
    section.questions.splice(index, 1);
    reorderQuestions(section);
}

function addOption(question) {
    question.options.push('');
}

function removeOption(question, index) {
    if (question.options.length === 1) return;
    question.options.splice(index, 1);
}

function submit() {
    processing.value = true;
    const payload = JSON.parse(JSON.stringify(form));
    payload.sections.forEach(ensureSectionCommentQuestion);
    payload.sections.forEach(section => {
        section.questions.forEach(question => {
            if (question.question_type !== 'multi_select') question.options = [];
            if (question.question_type !== 'score') question.allow_manual_score = false;
        });
    });

    const options = {
        preserveScroll: true,
        onFinish: () => { processing.value = false; },
        onSuccess: () => resetForm(),
    };

    if (editingId.value) {
        router.put(route('super-admin.template-setup.update', editingId.value), payload, options);
    } else {
        router.post(route('super-admin.template-setup.store'), payload, options);
    }
}

function deleteTemplate(template) {
    if (!confirm(`Delete template "${template.localized_name}"?`)) return;
    router.delete(route('super-admin.template-setup.destroy', template.id), { preserveScroll: true });
}

const activeTemplateCount = computed(() => props.templates.filter(template => template.is_active).length);
</script>

<template>
    <Head title="Template Setup - Super Admin" />

    <SuperAdminLayout>
        <template #header>Template Setup</template>

        <div v-if="flash.success" class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            {{ flash.success }}
        </div>

        <div v-if="flash.error" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
            {{ flash.error }}
        </div>

        <div v-if="errorMessages.length" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <p class="font-semibold">Please fix the following before saving:</p>
            <ul class="mt-2 list-inside list-disc space-y-1">
                <li v-for="(message, index) in errorMessages" :key="index">{{ message }}</li>
            </ul>
        </div>

        <div class="mb-5 grid gap-4 md:grid-cols-3">
            <div class="rounded-2xl border border-indigo-100 bg-indigo-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-indigo-700">Templates</p>
                <p class="mt-1 text-2xl font-bold text-indigo-950">{{ templates.length }}</p>
            </div>
            <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Active</p>
                <p class="mt-1 text-2xl font-bold text-emerald-900">{{ activeTemplateCount }}</p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Assignment model</p>
                <p class="mt-1 text-sm text-slate-700">Many countries and many brands per template.</p>
            </div>
        </div>

        <form class="mb-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm" @submit.prevent="submit">
            <div class="border-b border-indigo-100 bg-gradient-to-r from-indigo-50 to-blue-50 px-6 py-4">
                <h2 class="text-lg font-semibold text-indigo-900">{{ editingId ? 'Edit Template' : 'Create Template' }}</h2>
                <p class="text-xs text-indigo-700/80">Assign countries/brands, then build sections and questions.</p>
            </div>

            <div class="space-y-6 p-6">
                <div class="grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Name (English)</label>
                        <input v-model="form.name_en" type="text" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <p v-if="errors.name_en" class="mt-1 text-xs text-red-600">{{ errors.name_en }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Name (Arabic)</label>
                        <input v-model="form.name_ar" type="text" class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        <p v-if="errors.name_ar" class="mt-1 text-xs text-red-600">{{ errors.name_ar }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Countries</label>
                        <select ref="countrySelect" v-model="form.country_ids" multiple class="h-32 w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option v-for="country in countries" :key="country.id" :value="country.id">{{ country.localized_name }}</option>
                        </select>
                        <p v-if="errors.country_ids" class="mt-1 text-xs text-red-600">{{ errors.country_ids }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Brands</label>
                        <select ref="brandSelect" v-model="form.brand_ids" multiple class="h-32 w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option v-for="brand in brands" :key="brand.id" :value="brand.id">{{ brand.localized_name }}</option>
                        </select>
                        <p v-if="errors.brand_ids" class="mt-1 text-xs text-red-600">{{ errors.brand_ids }}</p>
                    </div>
                </div>

                <label class="inline-flex items-center gap-2 text-sm font-medium text-gray-700">
                    <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                    Active template
                </label>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-semibold text-gray-900">Sections & Questions</h3>
                        <button type="button" class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100" @click="addSection">Add Section</button>
                    </div>

                    <div v-for="(section, sectionIndex) in form.sections" :key="sectionIndex" class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="mb-4 flex flex-wrap items-end gap-3">
                            <div class="min-w-[220px] flex-1">
                                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Section English</label>
                                <input v-model="section.name" type="text" class="w-full rounded-lg border-gray-300 text-sm" />
                            </div>
                            <div class="min-w-[220px] flex-1">
                                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Section Arabic</label>
                                <input v-model="section.name_ar" type="text" class="w-full rounded-lg border-gray-300 text-sm" />
                            </div>
                            <button type="button" class="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-medium text-red-700 disabled:opacity-40" :disabled="form.sections.length === 1" @click="removeSection(sectionIndex)">Remove</button>
                        </div>

                        <div class="space-y-3">
                            <div v-for="(question, questionIndex) in section.questions" :key="questionIndex" class="rounded-xl border border-gray-200 bg-white p-4">
                                <div class="grid gap-3 lg:grid-cols-2">
                                    <input v-model="question.name" type="text" placeholder="Question (English)" class="rounded-lg border-gray-300 text-sm" />
                                    <input v-model="question.name_ar" type="text" placeholder="Question (Arabic)" class="rounded-lg border-gray-300 text-sm" />
                                    <select v-model="question.question_type" class="rounded-lg border-gray-300 text-sm">
                                        <option v-for="(label, value) in questionTypeLabels" :key="value" :value="value">{{ label }}</option>
                                    </select>
                                    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-700">
                                        <label class="inline-flex items-center gap-2"><input v-model="question.is_required" type="checkbox" class="rounded border-gray-300 text-indigo-600" /> Required</label>
                                        <label v-if="question.question_type === 'score'" class="inline-flex items-center gap-2"><input v-model="question.allow_manual_score" type="checkbox" class="rounded border-gray-300 text-indigo-600" /> Manual score checkbox</label>
                                    </div>
                                </div>

                                <div v-if="question.question_type === 'multi_select'" class="mt-3 space-y-2">
                                    <label class="text-xs font-semibold uppercase text-slate-500">Options</label>
                                    <div v-for="(option, optionIndex) in question.options" :key="optionIndex" class="flex gap-2">
                                        <input v-model="question.options[optionIndex]" type="text" class="flex-1 rounded-lg border-gray-300 text-sm" placeholder="Option label" />
                                        <button type="button" class="rounded-md px-2 text-xs text-red-600 hover:bg-red-50" @click="removeOption(question, optionIndex)">Remove</button>
                                    </div>
                                    <button type="button" class="text-xs font-medium text-indigo-700" @click="addOption(question)">+ Add option</button>
                                </div>

                                <div v-if="['yes_no', 'score'].includes(question.question_type)" class="mt-3 max-w-xs">
                                    <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Max / Yes Score</label>
                                    <input v-model.number="question.score_value" type="number" min="0" step="0.01" class="w-full rounded-lg border-gray-300 text-sm" />
                                </div>

                                <p v-if="question.question_type === 'points'" class="mt-3 text-xs text-slate-500">
                                    Displays 1 PT / 0.5 PT / 0 PT / N/A in the quality evaluation form.
                                </p>

                                <p v-if="question.question_type === 'comment'" class="mt-3 text-xs text-slate-500">
                                    Optional section comment field shown after this section's scored questions.
                                </p>

                                <div class="mt-3 flex justify-between">
                                    <span class="text-xs text-slate-500">Question {{ questionIndex + 1 }}</span>
                                    <button type="button" class="text-xs font-medium text-red-600 disabled:opacity-40" :disabled="section.questions.length === 1 || question.question_type === 'comment'" @click="removeQuestion(section, questionIndex)">Remove question</button>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="mt-3 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50" @click="addQuestion(section)">Add Question</button>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-6 py-3">
                <button type="button" class="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="resetForm">Reset</button>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-60" :disabled="processing">
                    {{ processing ? 'Saving...' : (editingId ? 'Update Template' : 'Create Template') }}
                </button>
            </div>
        </form>

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="border-b border-slate-200 bg-slate-100 text-left text-xs font-semibold uppercase tracking-wider text-slate-700">
                    <tr>
                        <th class="px-5 py-3">Template</th>
                        <th class="px-5 py-3">Assignments</th>
                        <th class="px-5 py-3">Builder</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    <tr v-for="template in templates" :key="template.id" class="transition-colors hover:bg-indigo-50/40">
                        <td class="px-5 py-4">
                            <p class="font-semibold text-gray-900">{{ template.localized_name }}</p>
                            <p class="text-xs text-gray-500">#{{ template.id }}</p>
                        </td>
                        <td class="px-5 py-4 text-sm">
                            <p><span class="font-medium">Countries:</span> {{ template.countries.map(c => c.localized_name).join(', ') || '—' }}</p>
                            <p><span class="font-medium">Brands:</span> {{ template.brands.map(b => b.localized_name).join(', ') || '—' }}</p>
                        </td>
                        <td class="px-5 py-4 text-sm">
                            {{ template.sections.length }} sections / {{ template.sections.reduce((total, section) => total + section.questions.length, 0) }} questions
                        </td>
                        <td class="px-5 py-4">
                            <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="template.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-700'">
                                {{ template.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <button type="button" class="mr-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100" @click="editTemplate(template)">Edit</button>
                            <button type="button" class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-100" @click="deleteTemplate(template)">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="templates.length === 0">
                        <td colspan="5" class="px-5 py-8 text-center text-sm text-gray-500">No templates have been created yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </SuperAdminLayout>
</template>