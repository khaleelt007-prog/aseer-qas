<script setup>
import { computed } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    evaluation: { type: Object, default: null },
});

const emit = defineEmits(['update:open']);
const logs = computed(() => props.evaluation?.email_logs ?? []);

function close() {
    emit('update:open', false);
}

function formatDate(value) {
    if (!value) return '—';
    try { return new Date(value).toLocaleString(); } catch (e) { return value; }
}
</script>

<template>
    <div
        v-if="open"
        class="fixed inset-0 z-50 flex items-start justify-center bg-black/60 p-4 sm:p-6"
        @click.self="close"
    >
        <div class="my-6 flex max-h-[88vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-3 border-b border-emerald-100 bg-emerald-50 px-6 py-4">
                <div>
                    <h2 class="text-lg font-semibold text-emerald-900">QC Report Email Log</h2>
                    <p class="mt-0.5 text-xs text-emerald-700">
                        {{ evaluation?.title || `Evaluation #${evaluation?.id}` }} · #{{ evaluation?.id }}
                    </p>
                </div>
                <button type="button" class="rounded-lg p-2 text-emerald-700 hover:bg-white" aria-label="Close" @click="close">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto bg-gray-50 p-6">
                <div v-if="logs.length" class="space-y-4">
                    <article v-for="log in logs" :key="log.id" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="mb-4 flex items-center gap-2 text-sm font-semibold text-gray-900">
                            <svg class="h-5 w-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            Sent {{ formatDate(log.sent_at) }}
                        </div>

                        <div class="space-y-4 text-sm">
                            <div>
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">To</p>
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="email in log.to_emails" :key="`to-${log.id}-${email}`" class="rounded-full bg-indigo-50 px-3 py-1 text-xs text-indigo-700">
                                        {{ email }}
                                    </span>
                                </div>
                            </div>

                            <div v-if="log.cc_emails?.length">
                                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500">CC</p>
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="email in log.cc_emails" :key="`cc-${log.id}-${email}`" class="rounded-full bg-gray-100 px-3 py-1 text-xs text-gray-700">
                                        {{ email }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <div v-else class="rounded-xl border border-gray-200 bg-white px-5 py-12 text-center text-sm text-gray-500">
                    No successful QC report emails have been logged for this evaluation.
                </div>
            </div>
        </div>
    </div>
</template>