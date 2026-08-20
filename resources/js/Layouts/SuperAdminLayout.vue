<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

// Layout based on TailAdmin Vue Tailwind Admin Dashboard
// (https://github.com/TailAdmin/vue-tailwind-admin-dashboard)

const page = usePage();
const sidebarOpen = ref(false);
const userMenuOpen = ref(false);

const user = computed(() => page.props.auth?.user ?? {});
const userInitial = computed(() => {
    const name = user.value?.username || user.value?.first_name || 'A';
    return name.charAt(0).toUpperCase();
});

function toggleSidebar() {
    sidebarOpen.value = !sidebarOpen.value;
}

function toggleUserMenu() {
    userMenuOpen.value = !userMenuOpen.value;
}
</script>

<template>
    <div class="min-h-screen bg-gray-50 text-gray-800">
        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-gray-900 text-gray-100 transition-transform duration-300 lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <!-- Brand -->
            <div class="flex h-16 items-center justify-between border-b border-white/10 px-5">
                <Link :href="route('super-admin.dashboard')" class="flex items-center gap-2">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-500 text-base font-bold text-white" style="background-color:#3641f5">SA</span>
                    <span class="text-lg font-semibold tracking-tight">Super Admin</span>
                </Link>
                <button
                    type="button"
                    class="rounded p-1 text-gray-400 hover:bg-white/10 lg:hidden"
                    aria-label="Close sidebar"
                    @click="sidebarOpen = false"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Nav -->
            <nav class="flex-1 overflow-y-auto px-3 py-4">
                <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wider text-gray-500">Menu</p>
                <ul class="space-y-1">
                    <li>
                        <Link
                            :href="route('super-admin.dashboard')"
                            class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
                            :class="route().current('super-admin.dashboard') ? 'bg-white/10 text-white' : 'text-gray-300 hover:bg-white/5 hover:text-white'"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7m-9 2v8a1 1 0 001 1h4a1 1 0 001-1v-3a1 1 0 011-1h2a1 1 0 011 1v3a1 1 0 001 1h4a1 1 0 001-1v-8m-9-2h.01"/></svg>
                            Dashboard
                        </Link>
                    </li>
                    <li>
                        <Link
                            :href="route('super-admin.quality-evaluations.index')"
                            class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
                            :class="route().current('super-admin.quality-evaluations.*') ? 'bg-white/10 text-white' : 'text-gray-300 hover:bg-white/5 hover:text-white'"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Quality Evaluations
                        </Link>
                    </li>
                    <li>
                        <Link
                            :href="route('super-admin.follow-ups.index')"
                            class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
                            :class="route().current('super-admin.follow-ups.*') ? 'bg-white/10 text-white' : 'text-gray-300 hover:bg-white/5 hover:text-white'"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h6m-6 4h6m-6 4h4m-7 5l3-3h11a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14z"/></svg>
                            Follow-Up Report
                        </Link>
                    </li>
                    <li>
                        <Link
                            :href="route('super-admin.template-setup.index')"
                            class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
                            :class="route().current('super-admin.template-setup.*') ? 'bg-white/10 text-white' : 'text-gray-300 hover:bg-white/5 hover:text-white'"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M7 14h10M9 18h6"/></svg>
                            Template Setup
                        </Link>
                    </li>
                    <li>
                        <Link
                            :href="route('super-admin.qc-report-email-settings.index')"
                            class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
                            :class="route().current('super-admin.qc-report-email-settings.*') ? 'bg-white/10 text-white' : 'text-gray-300 hover:bg-white/5 hover:text-white'"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8m-16 9h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            QC Report Email
                        </Link>
                    </li>
                    <li>
                        <Link
                            :href="route('super-admin.reports.top-evaluators.index')"
                            class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
                            :class="route().current('super-admin.reports.top-evaluators.*') ? 'bg-white/10 text-white' : 'text-gray-300 hover:bg-white/5 hover:text-white'"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18M7 14l4-4 3 3 5-6"/></svg>
                            Top Evaluators
                        </Link>
                    </li>
                    <li>
                        <Link
                            :href="route('super-admin.reports.branch-visits.index')"
                            class="group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors"
                            :class="route().current('super-admin.reports.branch-visits.*') ? 'bg-white/10 text-white' : 'text-gray-300 hover:bg-white/5 hover:text-white'"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5V10h-5v10zm-7 0h4V4h-4v16zM3 20h4v-6H3v6z"/></svg>
                            Branch Visits
                        </Link>
                    </li>
                </ul>
            </nav>

            <!-- Footer user card -->
            <div class="border-t border-white/10 p-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-sm font-semibold">
                        {{ userInitial }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-white">{{ user.first_name }} {{ user.last_name }}</p>
                        <p class="truncate text-xs text-gray-400">{{ user.username }}</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Sidebar overlay (mobile) -->
        <div
            v-show="sidebarOpen"
            class="fixed inset-0 z-30 bg-black/40 lg:hidden"
            aria-hidden="true"
            @click="sidebarOpen = false"
        ></div>

        <!-- Main column -->
        <div class="lg:pl-64">
            <!-- Header -->
            <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-gray-200 bg-white px-4 sm:px-6">
                <button
                    type="button"
                    class="rounded-md p-2 text-gray-600 hover:bg-gray-100 lg:hidden"
                    aria-label="Open sidebar"
                    @click="toggleSidebar"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>

                <h1 class="text-base font-semibold text-gray-900">
                    <slot name="header">Super Admin Panel</slot>
                </h1>

                <div class="ml-auto flex items-center gap-3">
                    <div class="relative">
                        <button
                            type="button"
                            class="flex items-center gap-2 rounded-full border border-gray-200 bg-white px-2.5 py-1.5 text-sm hover:bg-gray-50"
                            @click="toggleUserMenu"
                        >
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-gray-900 text-xs font-semibold text-white">
                                {{ userInitial }}
                            </span>
                            <span class="hidden text-gray-700 sm:inline">{{ user.first_name }}</span>
                            <svg class="h-4 w-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div
                            v-show="userMenuOpen"
                            class="absolute right-0 mt-2 w-44 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg"
                        >
                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="block w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50"
                            >
                                Sign out
                            </Link>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <main class="px-4 py-6 sm:px-6 lg:px-8">
                <slot />
            </main>
        </div>
    </div>
</template>
