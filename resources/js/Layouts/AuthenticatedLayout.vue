<script setup>
import { ref, computed } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import LanguageSwitcher from '@/Components/LanguageSwitcher.vue';
import { Link } from '@inertiajs/vue3';
import { useRTL } from '@/composables/useRTL';
import { useI18n } from 'vue-i18n';

const showingNavigationDropdown = ref(false);
const showDropdown = ref(false);
const { isRTL, direction } = useRTL();
const { locale, t } = useI18n();

const currentLocale = computed(() => locale.value);
</script>

<template>
    <div>
        <div class="min-h-screen" :class="{ 'bg-gray-50': !isRTL, 'bg-gray-50': isRTL }" style="background-color: var(--background);">
            <!-- Modern Header Navigation -->
            <nav
                class="header-nav bg-white shadow-sm border-b"
                :class="{ 'rtl-nav': isRTL }"
                style="border-color: var(--stroke); box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);"
            >
                <!-- Primary Navigation Menu -->
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="flex h-20 justify-between items-center">
                        <!-- Left Section: Logo & Navigation -->
                        <div class="flex items-center space-x-8" :class="{ 'space-x-reverse': isRTL }">
                            <!-- Logo -->
                            <div class="flex shrink-0 items-center">
                                <Link
                                    :href="route('dashboard')"
                                    class="logo-link flex items-center space-x-3 transition-all duration-200 hover:opacity-80"
                                    :class="{ 'space-x-reverse': isRTL }"
                                >
                                    <ApplicationLogo
                                        class="block h-12 w-auto transition-transform duration-200 hover:scale-105"
                                        style="color: var(--button);"
                                    />
                                    <div class="hidden sm:block">
                                        <div class="flex items-center gap-2">
                                            <h1 class="text-xl font-bold tracking-tight"
                                                style="color: var(--headline); font-family: 'Cairo', sans-serif;"
                                                :class="{ 'font-cairo': isRTL }">
                                                Aseer QAS
                                            </h1>
                                            <span class="text-xs px-1.5 py-0.5 rounded-full font-medium"
                                                  style="color: var(--paragraph); background-color: var(--background); border: 1px solid var(--stroke);">
                                                v{{ $page.props.app_version }}
                                            </span>
                                        </div>
                                        <p class="text-sm font-medium"
                                           style="color: var(--paragraph);"
                                           :class="{ 'font-cairo': isRTL }">
                                            {{ $t('navigation.quality_control') }}
                                        </p>
                                    </div>
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div
                                class="hidden lg:flex items-center space-x-1"
                                :class="{ 'space-x-reverse': isRTL }"
                            >
                                <NavLink
                                    :href="route('dashboard')"
                                    :active="route().current('dashboard')"
                                    class="nav-link-modern"
                                >
                                    <svg class="w-5 h-5 mr-2" :class="{ 'ml-2 mr-0': isRTL }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
                                    </svg>
                                    {{ $t('navigation.dashboard') }}
                                </NavLink>
                            </div>
                        </div>

                        <!-- Right Section: Language Switcher & User Menu (Desktop) -->
                        <div class="hidden lg:flex items-center space-x-4" :class="{ 'space-x-reverse': isRTL }">
                            <!-- Language Switcher -->
                            <div class="relative">
                                <LanguageSwitcher />
                            </div>

                            <!-- User Profile Dropdown -->
                            <div class="relative">
                                <Dropdown :align="isRTL ? 'left' : 'right'" width="56">
                                    <template #trigger>
                                        <button
                                            type="button"
                                            class="user-menu-trigger flex items-center space-x-3 px-4 py-2 rounded-xl transition-all duration-200 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2"
                                            :class="{ 'space-x-reverse': isRTL }"
                                            style="background-color: var(--secondary); border: 1px solid var(--stroke); color: var(--paragraph); focus:ring-color: var(--button);"
                                        >
                                            <!-- User Avatar -->
                                            <div class="flex-shrink-0">
                                                <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold"
                                                     style="background-color: var(--button); color: var(--button-text);">
                                                    {{ $page.props.auth.user.username.charAt(0).toUpperCase() }}
                                                </div>
                                            </div>

                                            <!-- User Info -->
                                            <div class="hidden md:block text-left" :class="{ 'text-right': isRTL }">
                                                <p class="text-sm font-medium leading-none"
                                                   style="color: var(--headline);"
                                                   :class="{ 'font-cairo': isRTL }">
                                                    {{ $page.props.auth.user.first_name }} {{ $page.props.auth.user.last_name }}
                                                </p>
                                                <p class="text-xs mt-1"
                                                   style="color: var(--paragraph);"
                                                   :class="{ 'font-cairo': isRTL }">
                                                    {{ $page.props.auth.user.username }}
                                                </p>
                                            </div>

                                            <!-- Dropdown Arrow -->
                                            <svg
                                                class="w-4 h-4 transition-transform duration-200"
                                                :class="{ 'rotate-180': showingNavigationDropdown }"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>
                                    </template>

                                    <template #content>
                                        <div class="py-1">
                                            
                                            <div class="border-t border-gray-100 my-1"></div>
                                            <DropdownLink
                                                :href="route('logout')"
                                                method="post"
                                                as="button"
                                                class="dropdown-link-modern text-red-600 hover:text-red-700 hover:bg-red-50"
                                            >
                                                <svg class="w-4 h-4 mr-3" :class="{ 'ml-3 mr-0': isRTL }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                                </svg>
                                                {{ $t('navigation.logout') }}
                                            </DropdownLink>
                                        </div>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Mobile Header Controls -->
                        <div class="flex items-center space-x-2 lg:hidden" :class="{ 'space-x-reverse': isRTL }">
                            <!-- Mobile Language Switcher -->
                            <div class="relative">
                                <button
                                    @click="showDropdown = !showDropdown"
                                    class="mobile-language-button inline-flex items-center justify-center p-2 rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2"
                                    style="background-color: var(--secondary); border: 1px solid var(--stroke); color: var(--paragraph); focus:ring-color: var(--button);"
                                    :aria-expanded="showDropdown"
                                    aria-label="Language switcher"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"></path>
                                    </svg>
                                </button>

                                <div
                                    v-show="showDropdown"
                                    class="absolute z-50 mt-2 w-40 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5"
                                    :class="{ 'right-0': !isRTL, 'left-0': isRTL }"
                                >
                                    <div class="py-1">
                                        <Link
                                            :href="route('language.switch', 'en')"
                                            class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                            :class="{ 'active-language': currentLocale === 'en' }"
                                            @click="showDropdown = false"
                                        >
                                            <span class="fi fi-us mr-2" :class="{ 'ml-2 mr-0': isRTL }"></span>
                                            {{ $t('navigation.english') }}
                                        </Link>
                                        <Link
                                            :href="route('language.switch', 'ar')"
                                            class="flex items-center px-3 py-2 text-sm text-gray-700 hover:bg-gray-100"
                                            :class="{ 'active-language': currentLocale === 'ar' }"
                                            @click="showDropdown = false"
                                        >
                                            <span class="fi fi-sa mr-2" :class="{ 'ml-2 mr-0': isRTL }"></span>
                                            {{ $t('navigation.arabic') }}
                                        </Link>
                                    </div>
                                </div>
                            </div>

                            <!-- Mobile User Dropdown -->
                            <div class="relative">
                                <Dropdown :align="isRTL ? 'left' : 'right'" width="48">
                                    <template #trigger>
                                        <button
                                            type="button"
                                            class="mobile-user-button inline-flex items-center justify-center p-2 rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2"
                                            style="background-color: var(--secondary); border: 1px solid var(--stroke); color: var(--paragraph); focus:ring-color: var(--button);"
                                        >
                                            <!-- User Avatar -->
                                            <div class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-semibold"
                                                 style="background-color: var(--button); color: var(--button-text);">
                                                {{ $page.props.auth.user.username.charAt(0).toUpperCase() }}
                                            </div>
                                        </button>
                                    </template>

                                    <template #content>
                                        <div class="py-1">
                                            <!-- User Info Header -->
                                            <div class="px-4 py-2 border-b border-gray-100">
                                                <p class="text-sm font-medium text-gray-900"
                                                   :class="{ 'font-cairo text-right': isRTL }">
                                                    {{ $page.props.auth.user.first_name }} {{ $page.props.auth.user.last_name }}
                                                </p>
                                                <p class="text-xs text-gray-500 truncate"
                                                   :class="{ 'font-cairo text-right': isRTL }">
                                                    {{ $page.props.auth.user.email }}
                                                </p>
                                            </div>

                                         
                                            <div class="border-t border-gray-100 my-1"></div>
                                            <DropdownLink
                                                :href="route('logout')"
                                                method="post"
                                                as="button"
                                                class="dropdown-link-modern text-red-600 hover:text-red-700 hover:bg-red-50"
                                            >
                                                <svg class="w-4 h-4 mr-3" :class="{ 'ml-3 mr-0': isRTL }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                                </svg>
                                                {{ $t('navigation.logout') }}
                                            </DropdownLink>
                                        </div>
                                    </template>
                                </Dropdown>
                            </div>

                            <!-- Mobile Menu Button -->
                            <button
                                @click="showingNavigationDropdown = !showingNavigationDropdown"
                                class="mobile-menu-button inline-flex items-center justify-center p-2 rounded-xl transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2"
                                style="color: var(--paragraph); hover:background-color: var(--background); focus:ring-color: var(--button);"
                                :aria-expanded="showingNavigationDropdown"
                                aria-label="Toggle navigation menu"
                            >
                                <svg
                                    class="h-6 w-6 transition-transform duration-200"
                                    :class="{ 'rotate-90': showingNavigationDropdown }"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{
                                            'opacity-0 scale-75': showingNavigationDropdown,
                                            'opacity-100 scale-100': !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                        class="transition-all duration-200"
                                    />
                                    <path
                                        :class="{
                                            'opacity-100 scale-100': showingNavigationDropdown,
                                            'opacity-0 scale-75': !showingNavigationDropdown,
                                        }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                        class="transition-all duration-200 absolute"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Modern Responsive Navigation Menu -->
                <div
                    :class="{
                        'max-h-screen opacity-100': showingNavigationDropdown,
                        'max-h-0 opacity-0': !showingNavigationDropdown,
                    }"
                    class="lg:hidden overflow-hidden transition-all duration-300 ease-in-out"
                    style="background-color: var(--secondary); border-top: 1px solid var(--stroke);"
                >
                    <!-- Navigation Links -->
                    <div class="px-4 py-4 space-y-2">
                        <ResponsiveNavLink
                            :href="route('dashboard')"
                            :active="route().current('dashboard')"
                            class="responsive-nav-link-modern"
                        >
                            <svg class="w-5 h-5 mr-3" :class="{ 'ml-3 mr-0': isRTL }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
                            </svg>
                            {{ $t('navigation.dashboard') }}
                        </ResponsiveNavLink>
                    </div>
                </div>
            </nav>

            <!-- Modern Page Heading -->
            <header
                class="page-header shadow-sm"
                v-if="$slots.header"
                style="background-color: var(--secondary); border-bottom: 1px solid var(--stroke);"
            >
                <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                    <div class="page-header-content" :class="{ 'text-right': isRTL }">
                        <slot name="header" />
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>


        </div>
    </div>
</template>

<style scoped>
/* Flag icons for mobile language switcher */
.fi {
    width: 16px;
    height: 12px;
    background-size: cover;
    display: inline-block;
}

.fi-us {
    background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTIiIHZpZXdCb3g9IjAgMCAxNiAxMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjE2IiBoZWlnaHQ9IjEyIiBmaWxsPSIjQjIyMjM0Ii8+CjxyZWN0IHdpZHRoPSIxNiIgaGVpZ2h0PSIxIiBmaWxsPSJ3aGl0ZSIvPgo8L3N2Zz4K');
}

.fi-sa {
    background-image: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTYiIGhlaWdodD0iMTIiIHZpZXdCb3g9IjAgMCAxNiAxMiIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjE2IiBoZWlnaHQ9IjEyIiBmaWxsPSIjMDA2QzM1Ii8+Cjx0ZXh0IHg9IjgiIHk9IjciIGZpbGw9IndoaXRlIiBmb250LXNpemU9IjgiIHRleHQtYW5jaG9yPSJtaWRkbGUiPtmE2KcgYWxs2KfZhyDYpdmE2Kcg2KfZhNmE2YfYjDwvdGV4dD4KPC9zdmc+Cg==');
}

.active-language {
    @apply bg-gray-100 font-medium;
}
</style>
