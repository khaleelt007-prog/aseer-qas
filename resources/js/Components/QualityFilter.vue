<template>
    <div class="quality-filter">
        <!-- Mobile Toggle Button -->
        <button
            class="md:hidden w-full flex items-center justify-between px-4 py-3 bg-white rounded-lg shadow-sm border border-gray-200 mb-2"
            @click="filtersOpen = !filtersOpen"
        >
            <div class="flex flex-col items-start gap-1 flex-1 min-w-0">
                <span class="flex items-center gap-2 text-gray-700 font-medium">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    {{ $t('quality.filters') }}
                </span>
                <span v-if="activeFilterSummary" class="text-xs text-orange-600 truncate w-full">
                    {{ activeFilterSummary }}
                </span>
            </div>
            <svg class="w-5 h-5 text-gray-500 transition-transform flex-shrink-0" :class="{ 'rotate-180': filtersOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <!-- Filter Panel -->
        <div
            :class="['qc-card quality-filter-panel transition-all duration-300 overflow-hidden', { 'max-h-0 p-0 m-0 border-0 shadow-none': !filtersOpen && isMobile, '!mb-4': filtersOpen || !isMobile }]"
        >
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                <!-- Country Select -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('quality.country') }}</label>
                    <select ref="countrySelectEl" class="qc-select">
                        <option value="">{{ $t('quality.all_countries') }}</option>
                    </select>
                </div>

                <!-- Brand Select -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('quality.brand') }}</label>
                    <select ref="brandSelectEl" class="qc-select">
                        <option value="">{{ $t('quality.all_brands') }}</option>
                    </select>
                </div>

                <!-- Branch Select -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ $t('quality.branch') }}</label>
                    <select ref="branchSelectEl" class="qc-select">
                        <option value="">{{ $t('quality.all_branches') }}</option>
                    </select>
                </div>

                <!-- Date Range -->
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        {{ $t('quality.start_date') }} – {{ $t('quality.end_date') }}
                    </label>
                    <input
                        ref="dateRangeEl"
                        type="text"
                        class="qc-input w-full"
                        :placeholder="$t('quality.start_date') + ' – ' + $t('quality.end_date')"
                        readonly
                    />
                </div>
            </div>

            <!-- Clear Filters -->
            <div v-if="activeFilterCount > 0" class="mt-3 flex justify-end">
                <button @click="clearFilters" class="text-sm text-gray-500 hover:text-gray-700 underline">
                    {{ $t('quality.clear_filters') || 'Clear Filters' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import SlimSelect from 'slim-select'
import 'slim-select/styles'
import flatpickr from 'flatpickr'
import 'flatpickr/dist/flatpickr.min.css'

const props = defineProps({
    countries: { type: Array, default: () => [] },
    brands: { type: Array, default: () => [] },
    branches: { type: Array, default: () => [] },
    initialFilters: { type: Object, default: () => ({}) }
})

const emit = defineEmits(['filter-changed'])
const i18n = useI18n()

const filtersOpen = ref(false)
const isMobile = ref(false)

const filters = ref({
    country_id: props.initialFilters.country_id || '',
    brand_id: props.initialFilters.brand_id || '',
    branch_id: props.initialFilters.branch_id || '',
    start_date: props.initialFilters.start_date || '',
    end_date: props.initialFilters.end_date || '',
})

// Refs for SlimSelect
const countrySelectEl = ref(null)
const brandSelectEl = ref(null)
const branchSelectEl = ref(null)
let countrySSInstance = null
let brandSSInstance = null
let branchSSInstance = null

// Ref + instance for flatpickr range picker
const dateRangeEl = ref(null)
let dateRangeInstance = null

// Mobile full-screen helpers (mirrors Create.vue)
const isMobileFullscreen = ref(false)
const originalBodyOverflow = ref('')

const isMobileViewport = () => window.innerWidth < 768

const enableMobileFullscreen = (slimSelectElement) => {
    if (!isMobileViewport()) return
    isMobileFullscreen.value = true
    originalBodyOverflow.value = document.body.style.overflow
    document.body.style.overflow = 'hidden'
    const ssContent = slimSelectElement
    if (ssContent) {
        ssContent.classList.add('slim-fullscreen')
        const ssList = ssContent.querySelector('.ss-list')
        if (ssList) ssList.style.maxHeight = 'none'
        const searchInput = document.querySelector('.ss-search > input')
        if (searchInput) searchInput.blur()
    }
}

const disableMobileFullscreen = (slimSelectElement) => {
    if (!isMobileViewport()) return
    isMobileFullscreen.value = false
    document.body.style.overflow = originalBodyOverflow.value
    const ssContent = slimSelectElement.querySelector('.ss-content')
    if (ssContent) {
        ssContent.classList.remove('slim-fullscreen')
        const ssList = ssContent.querySelector('.ss-list')
        if (ssList) ssList.style.maxHeight = '200px'
    }
}

// Progressive filtering computed
const filteredBrands = computed(() => {
    if (!filters.value.country_id) return props.brands
    const countryId = Number(filters.value.country_id)
    const branchesForCountry = props.branches.filter(b => Number(b.country_id) === countryId)
    const brandIds = [...new Set(branchesForCountry.map(b => b.brand_id))]
    return props.brands.filter(brand => brandIds.includes(brand.id))
})

const filteredBranches = computed(() => {
    if (!filters.value.brand_id) return []
    const countryId = Number(filters.value.country_id)
    const brandId = Number(filters.value.brand_id)
    let result = props.branches.filter(b => Number(b.brand_id) === brandId)
    if (filters.value.country_id) {
        result = result.filter(b => Number(b.country_id) === countryId)
    }
    return result
})

const activeFilterCount = computed(() => {
    let count = 0
    if (filters.value.country_id) count++
    if (filters.value.brand_id) count++
    if (filters.value.branch_id) count++
    if (filters.value.start_date) count++
    if (filters.value.end_date) count++
    return count
})

const activeFilterSummary = computed(() => {
    const parts = []
    if (filters.value.country_id) {
        const country = props.countries.find(c => String(c.id) === String(filters.value.country_id))
        if (country) parts.push(country.localized_name)
    }
    if (filters.value.brand_id) {
        const brand = props.brands.find(b => String(b.id) === String(filters.value.brand_id))
        if (brand) parts.push(brand.localized_name)
    }
    if (filters.value.branch_id) {
        const branch = props.branches.find(b => String(b.id) === String(filters.value.branch_id))
        if (branch) parts.push(branch.localized_name)
    }
    if (filters.value.start_date) parts.push(filters.value.start_date)
    if (filters.value.end_date) parts.push(filters.value.end_date)
    return parts.length > 0 ? parts.join(' - ') : ''
})

// Debounced emit
let debounceTimer = null
const emitFilterChanged = () => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
        emit('filter-changed', { ...filters.value })
    }, 300)
}

// Watchers for cascading resets and emit
watch(() => filters.value.country_id, () => {
    filters.value.brand_id = ''
    filters.value.branch_id = ''
    rebuildBrandSS()
    rebuildBranchSS()
    emitFilterChanged()
})

watch(() => filters.value.brand_id, () => {
    filters.value.branch_id = ''
    rebuildBranchSS()
    emitFilterChanged()
})

watch(() => filters.value.branch_id, () => emitFilterChanged())
watch(() => filters.value.start_date, () => emitFilterChanged())
watch(() => filters.value.end_date, () => emitFilterChanged())

const clearFilters = () => {
    filters.value.country_id = ''
    filters.value.brand_id = ''
    filters.value.branch_id = ''
    filters.value.start_date = ''
    filters.value.end_date = ''
    if (countrySSInstance) countrySSInstance.setSelected('')
    rebuildBrandSS()
    rebuildBranchSS()
    if (dateRangeInstance) dateRangeInstance.clear()
    emitFilterChanged()
}

const initDateRangePicker = () => {
    if (!dateRangeEl.value || dateRangeInstance) return
    const initial = []
    if (filters.value.start_date) initial.push(filters.value.start_date)
    if (filters.value.end_date) initial.push(filters.value.end_date)

    dateRangeInstance = flatpickr(dateRangeEl.value, {
        mode: 'range',
        dateFormat: 'Y-m-d',
        allowInput: false,
        defaultDate: initial.length ? initial : null,
        onChange: (selectedDates) => {
            if (selectedDates.length === 2) {
                filters.value.start_date = formatYmd(selectedDates[0])
                filters.value.end_date = formatYmd(selectedDates[1])
            } else if (selectedDates.length === 1) {
                // User picked only the start; wait for the end before emitting.
                filters.value.start_date = formatYmd(selectedDates[0])
                filters.value.end_date = ''
            } else {
                filters.value.start_date = ''
                filters.value.end_date = ''
            }
        },
    })
}

const formatYmd = (date) => {
    const y = date.getFullYear()
    const m = String(date.getMonth() + 1).padStart(2, '0')
    const d = String(date.getDate()).padStart(2, '0')
    return `${y}-${m}-${d}`
}

// SlimSelect helpers
const createSSEvents = () => ({
    beforeOpen: () => {
        const el = document.querySelector('.ss-content')
        if (el) enableMobileFullscreen(el)
    },
    afterClose: () => {
        const el = document.querySelector('.ss-content')
        if (el) disableMobileFullscreen(el)
    }
})

const rebuildBrandSS = () => {
    if (!brandSSInstance) return
    nextTick(() => {
        const data = [{ text: i18n.t('quality.all_brands'), value: '' }]
        filteredBrands.value.forEach(b => data.push({ text: b.localized_name, value: String(b.id) }))
        brandSSInstance.setData(data)
        brandSSInstance.setSelected('')
    })
}

const rebuildBranchSS = () => {
    if (!branchSSInstance) return
    nextTick(() => {
        const data = [{ text: i18n.t('quality.all_branches'), value: '' }]
        filteredBranches.value.forEach(b => data.push({ text: b.localized_name, value: String(b.id) }))
        branchSSInstance.setData(data)
        branchSSInstance.setSelected('')
    })
}

const buildCountryData = () => {
    const data = [{ text: i18n.t('quality.all_countries'), value: '' }]
    props.countries.forEach(c => data.push({ text: c.localized_name, value: String(c.id) }))
    return data
}

const initSlimSelects = () => {
    const ssEvents = createSSEvents()

    if (countrySelectEl.value && !countrySSInstance) {
        countrySSInstance = new SlimSelect({
            select: countrySelectEl.value,
            settings: {
                searchText: i18n.t('quality.search_countries') || 'Search...',
                searchPlaceholder: i18n.t('quality.search_countries') || 'Search...',
                searchHighlight: true, closeOnSelect: true, allowDeselect: false,
            },
            events: {
                afterChange: (val) => { filters.value.country_id = val.length > 0 ? val[0].value : '' },
                ...ssEvents
            }
        })
        countrySSInstance.setData(buildCountryData())
        if (filters.value.country_id) countrySSInstance.setSelected(String(filters.value.country_id))
    }

    if (brandSelectEl.value && !brandSSInstance) {
        brandSSInstance = new SlimSelect({
            select: brandSelectEl.value,
            settings: {
                searchText: i18n.t('quality.search_brands') || 'Search...',
                searchPlaceholder: i18n.t('quality.search_brands') || 'Search...',
                searchHighlight: true, closeOnSelect: true, allowDeselect: false,
            },
            events: {
                afterChange: (val) => { filters.value.brand_id = val.length > 0 ? val[0].value : '' },
                ...ssEvents
            }
        })
        rebuildBrandSS()
        if (filters.value.brand_id) brandSSInstance.setSelected(String(filters.value.brand_id))
    }

    if (branchSelectEl.value && !branchSSInstance) {
        branchSSInstance = new SlimSelect({
            select: branchSelectEl.value,
            settings: {
                searchText: i18n.t('quality.search_branches') || 'Search...',
                searchPlaceholder: i18n.t('quality.search_branches') || 'Search...',
                searchHighlight: true, closeOnSelect: true, allowDeselect: false,
            },
            events: {
                afterChange: (val) => { filters.value.branch_id = val.length > 0 ? val[0].value : '' },
                ...ssEvents
            }
        })
        rebuildBranchSS()
        if (filters.value.branch_id) branchSSInstance.setSelected(String(filters.value.branch_id))
    }
}

const checkMobile = () => { isMobile.value = isMobileViewport() }

onMounted(() => {
    checkMobile()
    // Default open on desktop
    filtersOpen.value = !isMobile.value
    window.addEventListener('resize', checkMobile)
    nextTick(() => {
        initSlimSelects()
        initDateRangePicker()
    })
})

onUnmounted(() => {
    window.removeEventListener('resize', checkMobile)
    clearTimeout(debounceTimer)
    if (countrySSInstance) { countrySSInstance.destroy(); countrySSInstance = null }
    if (brandSSInstance) { brandSSInstance.destroy(); brandSSInstance = null }
    if (branchSSInstance) { branchSSInstance.destroy(); branchSSInstance = null }
    if (dateRangeInstance) { dateRangeInstance.destroy(); dateRangeInstance = null }
    if (isMobileFullscreen.value) {
        document.body.style.overflow = originalBodyOverflow.value
    }
})
</script>

<style scoped>
.quality-filter-panel {
    transform: none !important;
}
.quality-filter-panel:hover {
    border-color: transparent !important;
    transform: none !important;
}
</style>
