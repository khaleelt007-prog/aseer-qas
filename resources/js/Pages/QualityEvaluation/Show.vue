<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $t('quality.quality_evaluation_details') }}
                </h2>
                <div class="flex gap-3">
                    <!-- Download PDF button (if PDF exists) -->
                    <a
                        v-if="evaluation.pdf_filename"
                        :href="route('quality-evaluations.download-pdf', evaluation.id)"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors flex items-center gap-2"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        {{ $t('quality.download_pdf') }}
                    </a>

                    <!-- Generate/Export PDF button -->
                    <button
                        @click="exportPdf"
                        :disabled="isExportingPdf"
                        class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                    >
                        <svg v-if="isExportingPdf" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        {{ isExportingPdf ? $t('quality.exporting_pdf') : (evaluation.pdf_filename ? $t('quality.regenerate_pdf') : $t('quality.export_pdf')) }}
                    </button>
                    <Link
                        v-if="canEdit"
                        :href="route('quality-evaluations.edit', evaluation.id)"
                        class="px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors"
                    >
                        {{ $t('quality.edit') }}
                    </Link>
                    <Link
                        :href="route('quality-evaluations.index')"
                        class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition-colors"
                    >
                        {{ $t('quality.back_to_list') }}
                    </Link>
                </div>
            </div>
        </template>

        <div class="qc-container">
            <div class="max-w-4xl mx-auto">
                <!-- Header Information -->
                <div class="qc-card">
                    <div class="flex flex-col md:flex-row md:items-center justify-between mb-4">
                        <div>
                            <h1 class="qc-title">{{ $t('quality.control_evaluation') }} {{ evaluation.branch?.localized_name || evaluation.branch?.name }}</h1>
                            <div class="flex items-center gap-3 mt-2">
                                <span
                                    class="px-3 py-1 rounded-full text-sm font-medium"
                                    :class="evaluation.status === 'completed'
                                        ? 'bg-green-100 text-green-800'
                                        : 'bg-yellow-100 text-yellow-800'"
                                >
                                    {{ evaluation.status === 'completed' ? $t('quality.completed') : $t('quality.draft') }}
                                </span>
                                <span class="text-sm text-gray-600">
                                    {{ $t('quality.created') }}: {{ formatDate(evaluation.created_at) }}
                                </span>
                            </div>
                            <div v-if="evaluation.completed_at" class="text-sm text-gray-600 mt-1">
                                {{ $t('quality.completed_on') }}: {{ formatDate(evaluation.completed_at) }}
                            </div>
                        </div>
                    </div>

                    <!-- Total Score Display (for regular evaluations only) -->
                    <div v-if="evaluation.total_score !== null && evaluation.type !== 'checklist'" class="qc-total-score">
                        <h3 v-if="lang === 'ar'" dir="ltr">100/{{ formatScore(evaluation.total_score) }}</h3>
                        <h3 v-else>{{ formatScore(evaluation.total_score) }}/100</h3>

                        <p>{{ $t('quality.total_score') }}</p>
                        <div v-if="evaluation.extra_points !== 0" class="text-sm mt-1 opacity-75">
                            {{ $t('quality.base') }}: {{ baseScore.toFixed(0) }} {{ evaluation.extra_points > 0 ? '+' : '' }}{{ evaluation.extra_points }}
                        </div>
                        <div class="mt-3">
                            <div class="w-full bg-white bg-opacity-30 rounded-full h-3">
                                <div
                                    class="h-3 rounded-full bg-white transition-all duration-1000"
                                    :style="{ width: evaluation.total_score + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <!-- Checklist Template Info (for checklist evaluations) -->
                    <div v-if="evaluation.type === 'checklist' && checklistData" class="mt-4 p-4 rounded-lg bg-blue-50 border border-blue-200">
                        <h3 class="font-semibold text-blue-900 mb-2">{{ $t('quality.checklist_template') || 'Checklist Template' }}</h3>
                        <p class="text-blue-800 text-sm">{{ checklistData.template_name }}</p>
                    </div>

                    <!-- Checklist Score Display (for checklist evaluations) -->
                    <div v-if="evaluation.type === 'checklist' && evaluation.total_score !== null && evaluation.max_score !== null" class="qc-total-score mt-4">
                        <h3 v-if="lang === 'ar'" dir="ltr">{{ formatScore(evaluation.max_score) }}/{{ formatScore(evaluation.total_score) }}</h3>
                        <h3 v-else>{{ formatScore(evaluation.total_score) }}/{{ formatScore(evaluation.max_score) }}</h3>

                        <p>{{ $t('quality.checklist_score') || 'Checklist Score' }}</p>
                        <div class="mt-3">
                            <div class="w-full bg-white bg-opacity-30 rounded-full h-3">
                                <div
                                    class="h-3 rounded-full bg-white transition-all duration-1000"
                                    :style="{ width: (parseInt(evaluation.max_score) > 0 ? (parseInt(evaluation.total_score) / parseInt(evaluation.max_score)) * 100 : 0) + '%' }"
                                ></div>
                            </div>
                        </div>
                    </div>

                    <!-- Extra Points Section -->
                    <div v-if="evaluation.extra_points !== 0" class="mt-4 p-4 rounded-lg" :class="{
                        'bg-green-50 border border-green-200': evaluation.extra_points > 0,
                        'bg-red-50 border border-red-200': evaluation.extra_points < 0
                    }">
                        <h4 class="font-medium mb-2" :class="{
                            'text-green-700': evaluation.extra_points > 0,
                            'text-red-700': evaluation.extra_points < 0
                        }">
                            {{ $t('quality.extra_points') }}
                        </h4>
                        <div class="text-sm" :class="{
                            'text-green-600': evaluation.extra_points > 0,
                            'text-red-600': evaluation.extra_points < 0
                        }">
                            <span v-if="evaluation.extra_points > 0">
                                ✓ {{ evaluation.extra_points }} {{ $t('quality.bonus_points_added') }}
                            </span>
                            <span v-else>
                                ⚠ {{ Math.abs(evaluation.extra_points) }} points deducted from final score
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Checklist Sections and Questions (for checklist evaluations) -->
                <template v-if="evaluation.type === 'checklist' && checklistData">
                    <div
                        v-for="section in checklistData.sections"
                        :key="section.id"
                        class="qc-card"
                    >
                        <h3 class="qc-title text-lg mb-4">{{ section.localized_name }}</h3>

                        <div class="space-y-4">
                            <div
                                v-for="question in section.questions"
                                :key="question.id"
                                class="p-4 border border-gray-200 rounded-lg"
                            >
                                <div class="flex justify-between items-start mb-2">
                                    <h4 class="font-medium text-gray-800">{{ question.localized_name }}</h4>
                                    <div class="flex items-center gap-2">
                                        <!-- Question Score Display -->
                                        <span v-if="question.achieved_score !== null && question.max_score !== null" class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-1 rounded">
                                            <span v-if="lang === 'ar'" dir="ltr">{{ formatScore(question.max_score) }}/{{ formatScore(question.achieved_score) }}</span>
                                            <span v-else>{{ formatScore(question.achieved_score) }}/{{ formatScore(question.max_score) }}</span>
                                        </span>
                                        <span v-if="question.is_required" class="text-red-500 text-sm font-semibold">*</span>
                                    </div>
                                </div>

                                <!-- Point-Based Question (q_type === 1) -->
                                <div v-if="question.q_type === 1" class="mt-2">
                                    <!-- Points Answer Type (also default when answer_type is empty/missing) -->
                                    <div v-if="!checklistData.answer_type || checklistData.answer_type === 'Points'" class="inline-block px-3 py-1 rounded-full text-sm font-medium" :class="{
                                        'bg-green-100 text-green-800': question.answer_value === '1' || question.answer_value === 1,
                                        'bg-yellow-100 text-yellow-800': question.answer_value === '0.5' || question.answer_value === 0.5,
                                        'bg-red-100 text-red-800': question.answer_value === '0' || question.answer_value === 0,
                                        'bg-gray-100 text-gray-800': !question.answer_value
                                    }">
                                        {{ question.answer_value === '1' || question.answer_value === 1 ? ($t('quality.one_point') || '1 PT') : (question.answer_value === '0.5' || question.answer_value === 0.5 ? ($t('quality.half_point') || '0.5 PT') : (question.answer_value === '0' || question.answer_value === 0 ? ($t('quality.zero_point') || '0 PT') : ($t('quality.not_answered') || 'Not Answered'))) }}
                                    </div>

                                    <!-- Yes/No Answer Type -->
                                    <div v-else-if="checklistData.answer_type === 'Yes/No'" class="inline-block px-3 py-1 rounded-full text-sm font-medium" :class="{
                                        'bg-green-100 text-green-800': question.answer_value === '1' || question.answer_value === 1,
                                        'bg-red-100 text-red-800': question.answer_value === '0' || question.answer_value === 0,
                                        'bg-gray-100 text-gray-800': !question.answer_value
                                    }">
                                        {{ question.answer_value === '1' || question.answer_value === 1 ? ($t('quality.yes') || 'Yes') : (question.answer_value === '0' || question.answer_value === 0 ? ($t('quality.no') || 'No') : ($t('quality.not_answered') || 'Not Answered')) }}
                                    </div>
                                </div>

                                <!-- Text Question -->
                                <div v-else-if="question.q_type === 2" class="mt-2">
                                    <div v-if="question.answer_value" class="bg-gray-50 rounded-lg p-3 text-gray-700 whitespace-pre-wrap text-sm">
                                        {{ question.answer_value }}
                                    </div>
                                    <div v-else class="text-gray-500 italic text-sm">
                                        {{ $t('quality.no_answer_provided') || 'No answer provided' }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Section Photos (collapsible) -->
                        <div v-if="getSectionPhotos(section.id).length > 0" class="mt-6 pt-6 border-t border-gray-200">
                            <button
                                type="button"
                                @click="toggleSectionPhotos(section.id)"
                                class="flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors"
                            >
                                <svg
                                    class="w-5 h-5 transition-transform"
                                    :class="{ 'rotate-180': expandedSections[section.id] }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                                </svg>
                                <span>{{ $t('quality.section_photos') || 'Section Photos' }}</span>
                                <span class="ml-2 px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold">
                                    {{ getSectionPhotos(section.id).length }}
                                </span>
                            </button>

                            <!-- Section Photos Grid -->
                            <div v-if="expandedSections[section.id]" class="mt-4 photo-grid grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                <div
                                    v-for="photo in getSectionPhotos(section.id)"
                                    :key="photo.id"
                                    class="photo-item"
                                >
                                    <div class="photo-container relative group cursor-pointer" @click="openPhotoModal(photo)">
                                        <img
                                            :src="photo.url"
                                            :alt="photo.original_filename || 'Section photo'"
                                            class="photo-thumbnail w-full h-32 object-cover rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
                                            loading="lazy"
                                        />

                                        <!-- Overlay with info -->
                                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-200 rounded-lg flex items-center justify-center">
                                            <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                                                </svg>
                                            </div>
                                        </div>

                                        <!-- Photo info -->
                                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent p-2 rounded-b-lg">
                                            <p class="text-white text-xs truncate">{{ photo.original_filename }}</p>
                                            <p class="text-white text-xs opacity-75">{{ photo.formatted_file_size || formatFileSize(photo.file_size) }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Evaluation Items Details (for regular evaluations) -->
                <div v-if="evaluation.type !== 'checklist'" class="space-y-4">
                    <div
                        v-for="item in evaluationItems"
                        :key="item.id"
                        class="qc-card"
                    >
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="qc-title text-lg">{{ item.localized_title }}</h3>
                            <span class="qc-weight-badge">{{ Math.round(item.weight) }}%</span>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Score Information -->
                            <div>
                                <div class="mb-3">
                                    <label class="block text-sm font-medium text text-gray-700 mb-2">
                                        {{ $t('quality.score') }}
                                    </label>
                                    <div class="text-2xl font-bold text-gray-800" dir="ltr">
                                        <span v-if="lang === 'ar'" dir="ltr">
                                            {{ Math.round(item.max) }}/{{ Math.round(item.achieved || 0) }}
                                        </span>
                                        <span v-else>
                                            {{ Math.round(item.achieved || 0) }}/{{ Math.round(item.max) }}
                                        </span>

                                    </div>
                                    <div class="text-sm text-gray-600">
                                        <span dir="ltr">{{ Math.round(calculateItemPercentage(item)) }}%</span> {{ $t('quality.achieved') }}
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="block text-sm font-medium text-left text-gray-700 mb-2">
                                        {{ $t('quality.weighted_contribution') }}
                                    </label>
                                    <div class="text-lg font-semibold text-blue-600" dir="ltr">
                                        {{ Math.round(calculateWeightedScore(item)) }}/{{ Math.round(item.weight) }} {{ $t('quality.points') }}
                                    </div>
                                </div>
                            </div>

                            <!-- Visual Progress -->
                            <div>
                                <label class="block text-sm font-medium text-center text-gray-700 mb-2">
                                    {{ $t('quality.performance') }}
                                </label>
                                <div class="relative">
                                    <!-- Circular Progress -->
                                    <div class="relative w-24 h-24 mx-auto mb-3">
                                        <svg class="w-24 h-24 transform -rotate-90" viewBox="0 0 100 100">
                                            <!-- Background circle -->
                                            <circle
                                                cx="50"
                                                cy="50"
                                                r="40"
                                                stroke="#e5e7eb"
                                                stroke-width="8"
                                                fill="none"
                                            />
                                            <!-- Progress circle -->
                                            <circle
                                                cx="50"
                                                cy="50"
                                                r="40"
                                                :stroke="getScoreColor(calculateItemPercentage(item))"
                                                stroke-width="8"
                                                fill="none"
                                                stroke-linecap="round"
                                                :stroke-dasharray="251.2"
                                                :stroke-dashoffset="251.2 - (251.2 * calculateItemPercentage(item)) / 100"
                                                class="transition-all duration-1000 ease-out"
                                            />
                                        </svg>
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <span class="text-lg font-bold text-gray-800 text-center leading-none" dir="ltr">
                                                {{ Math.round(calculateItemPercentage(item)) }}%
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <!-- Performance Label -->
                                    <div class="text-center">
                                        <span 
                                            class="px-3 py-1 rounded-full text-sm font-medium"
                                            :class="getPerformanceBadgeClass(calculateItemPercentage(item))"
                                        >
                                            {{ getPerformanceLabel(calculateItemPercentage(item)) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Comments Section -->
                <div v-if="evaluation.comments" class="qc-card">
                    <h3 class="qc-title text-lg mb-4">{{ $t('quality.additional_comments') }}</h3>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-gray-700 whitespace-pre-wrap">{{ evaluation.comments }}</p>
                    </div>
                </div>

                <!-- Photo Documentation Section -->
                <div v-if="evaluation.photos && evaluation.photos.length > 0" class="qc-card">
                    <h3 class="qc-title text-lg mb-4">{{ $t('quality.photo_documentation') || 'Photo Documentation' }}</h3>
                    <div class="photo-count mb-4">
                        <span class="text-sm font-medium text-gray-700">
                            {{ $t('quality.photos_attached', { count: evaluation.photos.length }) || `${evaluation.photos.length} photo(s) attached` }}
                        </span>
                    </div>

                    <!-- Photo Grid -->
                    <div class="photo-grid grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                        <div
                            v-for="photo in evaluation.photos"
                            :key="photo.id"
                            class="photo-item"
                        >
                            <div class="photo-container relative group cursor-pointer" @click="openPhotoModal(photo)">
                                <img
                                    :src="photo.url"
                                    :alt="photo.original_filename || 'Evaluation photo'"
                                    class="photo-thumbnail w-full h-32 object-cover rounded-lg border border-gray-200 hover:border-gray-300 transition-all duration-200"
                                    loading="lazy"
                                />

                                <!-- Overlay with info -->
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-200 rounded-lg flex items-center justify-center">
                                    <div class="opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Photo info -->
                                <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent p-2 rounded-b-lg">
                                    <p class="text-white text-xs truncate">{{ photo.original_filename }}</p>
                                    <p class="text-white text-xs opacity-75">{{ photo.formatted_file_size || formatFileSize(photo.file_size) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Statistics (for regular evaluations) -->
                <div v-if="evaluation.type !== 'checklist'" class="qc-card">
                    <h3 class="qc-title text-lg mb-4">{{ $t('quality.evaluation_summary') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600">
                                {{ evaluationItems.filter(item => item.achieved !== null).length }}
                            </div>
                            <div class="text-sm text-blue-800">{{ $t('quality.items_evaluated') }}</div>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <div class="text-2xl font-bold text-green-600" dir="ltr">
                                {{ formatScore(evaluation.total_score) }}%
                            </div>
                            <div class="text-sm text-green-800">{{ $t('quality.average_score') }}</div>
                        </div>
                        <div class="text-center p-4 bg-purple-50 rounded-lg">
                            <div class="text-2xl font-bold text-purple-600" dir="ltr">
                                {{ formatScore(evaluation.total_score) || 0 }}
                            </div>
                            <div class="text-sm text-purple-800">{{ $t('quality.weighted_total') }}</div>
                        </div>
                    </div>
                </div>

                <!-- Checklist Summary (for checklist evaluations) -->
                <div v-if="evaluation.type === 'checklist' && checklistData" class="qc-card">
                    <h3 class="qc-title text-lg mb-4">{{ $t('quality.evaluation_summary') }}</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600">
                                {{ checklistData.sections.length }}
                            </div>
                            <div class="text-sm text-blue-800">{{ $t('quality.sections') || 'Sections' }}</div>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <div class="text-2xl font-bold text-green-600">
                                {{ totalChecklistQuestions }}
                            </div>
                            <div class="text-sm text-green-800">{{ $t('quality.questions') || 'Questions' }}</div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="qc-card">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <Link
                            v-if="canEdit"
                            :href="route('quality-evaluations.edit', evaluation.id)"
                            class="qc-button bg-yellow-500 hover:bg-yellow-600"
                        >
                            {{ $t('quality.edit_evaluation') }}
                        </Link>
                        <Link
                            v-if="canCreate"
                            :href="route('quality-evaluations.create')"
                            class="qc-button"
                        >
                            {{ $t('quality.create_new_evaluation') }}
                        </Link>
                        <button
                            v-if="canDelete"
                            @click="confirmDelete"
                            class="qc-button bg-red-500 hover:bg-red-600"
                        >
                            {{ $t('quality.delete_evaluation') }}
                        </button>

                        <!-- Show message if no actions are available -->
                        <div v-if="!canEdit && !canCreate && !canDelete" class="text-center py-4">
                            <div class="text-gray-500 text-sm">
                                <svg class="w-5 h-5 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m4-6V9a2 2 0 00-2-2H8a2 2 0 00-2 2v2m8 0V9a2 2 0 00-2-2H8a2 2 0 00-2 2v2m8 0h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2a2 2 0 012-2h2"></path>
                                </svg>
                                {{ $t('quality.no_actions_available') || 'No actions available based on your permissions.' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Photo Modal -->
        <div v-if="showPhotoModal" class="fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center z-50 p-4" @click="closePhotoModal">
            <div class="relative max-w-4xl max-h-full w-full h-full flex items-center justify-center">
                <!-- Close Button -->
                <button
                    @click="closePhotoModal"
                    class="absolute top-4 right-4 z-10 bg-black bg-opacity-50 text-white rounded-full p-2 hover:bg-opacity-70 transition-all"
                >
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>

                <!-- Photo -->
                <div class="relative max-w-full max-h-full" @click.stop>
                    <img
                        v-if="selectedPhoto"
                        :src="selectedPhoto.url"
                        :alt="selectedPhoto.original_filename || 'Evaluation photo'"
                        class="max-w-full max-h-full object-contain rounded-lg"
                    />

                    <!-- Photo Info -->
                    <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent p-4 rounded-b-lg">
                        <h4 class="text-white font-medium">{{ selectedPhoto?.original_filename }}</h4>
                        <div class="text-white text-sm opacity-75 mt-1">
                            <span>{{ selectedPhoto?.formatted_file_size || formatFileSize(selectedPhoto?.file_size) }}</span>
                            <span class="mx-2">•</span>
                            <span>{{ formatDate(selectedPhoto?.uploaded_at) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-lg p-6 max-w-md w-full">
                <h3 class="text-lg font-semibold mb-4">{{ $t('quality.confirm_delete_title') }}</h3>
                <p class="text-gray-600 mb-6">
                    {{ $t('quality.confirm_delete_message') }}
                </p>
                <div class="flex gap-3">
                    <button
                        @click="showDeleteModal = false"
                        class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors"
                    >
                        {{ $t('quality.cancel') }}
                    </button>
                    <button
                        @click="deleteEvaluation"
                        class="flex-1 px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors"
                    >
                        {{ $t('quality.delete') }}
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

// Props
const props = defineProps({
    evaluation: Object,
    evaluationItems: Array,
    checklistData: Object
})

// i18n
const { t } = useI18n()

const lang = useI18n().locale.value;

// Get auth data from Inertia page props
const page = usePage()
const auth = computed(() => page.props.auth || {})
const permissions = computed(() => auth.value.permissions || {})
const qualityPermissions = computed(() => permissions.value.quality_evaluations || [])

// Permission computed properties
const canEdit = computed(() => {
    return qualityPermissions.value.includes('edit')
})

const canDelete = computed(() => {
    return qualityPermissions.value.includes('delete')
})

const canCreate = computed(() => {
    return qualityPermissions.value.includes('create')
})

// Reactive data
const showDeleteModal = ref(false)
const showPhotoModal = ref(false)
const selectedPhoto = ref(null)
const isExportingPdf = ref(false)
const expandedSections = ref({})

// Computed properties
const baseScore = computed(() => {
    // Calculate the base score without extra points
    return (props.evaluation.total_score || 0) - (props.evaluation.extra_points || 0)
})

const totalChecklistQuestions = computed(() => {
    if (!props.checklistData || !props.checklistData.sections) {
        return 0
    }
    return props.checklistData.sections.reduce((total, section) => {
        return total + (section.questions ? section.questions.length : 0)
    }, 0)
})

// Methods
const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const calculateItemPercentage = (item) => {
    if (item.achieved !== null && item.max > 0) {
        return ((item.achieved / item.max) * 100).toFixed(1)
    }
    return 0
}

const calculateWeightedScore = (item) => {
    if (item.achieved !== null && item.max > 0) {
        const percentage = (item.achieved / item.max) * 100
        return ((percentage * item.weight) / 100).toFixed(1)
    }
    return 0
}

const formatScore = (score) => {
    if (score === null || score === undefined) return 0
    // Check if score has a decimal part
    const num = parseFloat(score)
    if (Number.isInteger(num)) {
        return num
    }
    // Return with decimal places if it's a fraction
    return num.toFixed(1)
}

const getScoreColor = (score) => {
    if (score >= 90) return '#10b981'
    if (score >= 80) return '#f59e0b'
    if (score >= 70) return '#f97316'
    return '#ef4444'
}

const getPerformanceLabel = (score) => {
    if (score >= 90) return t('quality.excellent')
    if (score >= 80) return t('quality.good')
    if (score >= 70) return t('quality.fair')
    return t('quality.needs_improvement')
}

const getPerformanceBadgeClass = (score) => {
    if (score >= 90) return 'bg-green-100 text-green-800'
    if (score >= 80) return 'bg-yellow-100 text-yellow-800'
    if (score >= 70) return 'bg-orange-100 text-orange-800'
    return 'bg-red-100 text-red-800'
}

const confirmDelete = () => {
    showDeleteModal.value = true
}

const deleteEvaluation = () => {
    router.delete(route('quality-evaluations.destroy', props.evaluation.id))
    showDeleteModal.value = false
}

// Photo modal methods
const openPhotoModal = (photo) => {
    selectedPhoto.value = photo
    showPhotoModal.value = true
}

const closePhotoModal = () => {
    showPhotoModal.value = false
    selectedPhoto.value = null
}

// PDF export method
const exportPdf = async () => {
    if (isExportingPdf.value) return

    try {
        isExportingPdf.value = true

        // Create a temporary link to trigger download
        const url = route('quality-evaluations.export-pdf', props.evaluation.id)
        const link = document.createElement('a')
        link.href = url
        link.download = `evaluation_${props.evaluation.id}.pdf`
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)

    } catch (error) {
        console.error('PDF export failed:', error)
        // You could add a toast notification here
    } finally {
        isExportingPdf.value = false
    }
}

// File size formatting
const formatFileSize = (bytes) => {
    if (!bytes) return '0 bytes'

    if (bytes >= 1048576) {
        return (bytes / 1048576).toFixed(2) + ' MB'
    } else if (bytes >= 1024) {
        return (bytes / 1024).toFixed(2) + ' KB'
    } else {
        return bytes + ' bytes'
    }
}

// Section photo methods
const getSectionPhotos = (sectionId) => {
    if (!props.evaluation.photos) return []
    return props.evaluation.photos.filter(photo => photo.section_id === sectionId)
}

const toggleSectionPhotos = (sectionId) => {
    expandedSections.value[sectionId] = !expandedSections.value[sectionId]
}
</script>
