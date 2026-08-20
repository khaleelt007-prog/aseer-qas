<template>
    <div class="section-photo-upload">
        <!-- Collapsible Photo Section Header -->
        <div class="flex items-center justify-between mt-6 pt-6 border-t border-gray-200">
            <button
                type="button"
                @click="isExpanded = !isExpanded"
                class="flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors"
            >
                <svg
                    class="w-5 h-5 transition-transform"
                    :class="{ 'rotate-180': isExpanded }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                </svg>
                <span>{{ $t('quality.section_photos') || 'Section Photos' }}</span>
                <span v-if="photos.length > 0" class="ml-2 px-2 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold">
                    {{ photos.length }}
                </span>
            </button>
        </div>

        <!-- Expanded Photo Upload Area -->
        <div v-if="isExpanded" class="mt-4 p-4 bg-gray-50 rounded-lg">
            <p class="text-sm text-gray-600 mb-4">
                {{ $t('quality.section_photo_description') || 'Capture photos for this section' }}
            </p>

            <!-- Photo Capture Component -->
            <PhotoCapture
                @photo-captured="handlePhotoCaptured"
                :disabled="disabled"
            />

            <!-- Photo Gallery for This Section -->
            <PhotoGallery
                :photos="photos"
                @photo-deleted="handlePhotoDeleted"
                :disabled="disabled"
            />
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import PhotoCapture from '@/Components/PhotoCapture.vue'
import PhotoGallery from '@/Components/PhotoGallery.vue'

const props = defineProps({
    sectionId: {
        type: Number,
        required: true
    },
    disabled: {
        type: Boolean,
        default: false
    },
    existingPhotos: {
        type: Array,
        default: () => []
    }
})

const emit = defineEmits(['photo-captured', 'photo-deleted'])

const { t } = useI18n()

const isExpanded = ref(false)
const photos = ref(props.existingPhotos.map(photo => ({
    id: photo.id,
    url: photo.url || `/quality-evaluations/photos/${photo.id}`,
    preview: photo.url || `/quality-evaluations/photos/${photo.id}`,
    timestamp: photo.uploaded_at || new Date().toISOString(),
    section_id: photo.section_id,
    isUploaded: true,
    isExisting: true // Flag to indicate this is an existing photo from database
})))

const handlePhotoCaptured = (photoData) => {
    const photo = {
        id: photoData.id, // Photo ID from server (already uploaded)
        url: photoData.url, // URL to fetch the photo
        preview: photoData.preview,
        timestamp: photoData.timestamp || new Date().toISOString(),
        section_id: props.sectionId,
        isUploaded: photoData.isUploaded || true, // Flag indicating photo is already uploaded
        isNew: true, // Flag to indicate this is a newly captured photo
        isExisting: false // Flag to indicate this is not an existing photo from database
    }
    photos.value.push(photo)
    emit('photo-captured', photo)
}

const handlePhotoDeleted = (photoId) => {
    const index = photos.value.findIndex(photo => photo.id === photoId)
    if (index !== -1) {
        const photo = photos.value[index]
        URL.revokeObjectURL(photo.preview)
        photos.value.splice(index, 1)
        emit('photo-deleted', photoId)
    }
}

defineExpose({
    getPhotos: () => photos.value,
    clearPhotos: () => {
        photos.value.forEach(photo => URL.revokeObjectURL(photo.preview))
        photos.value = []
    }
})
</script>

<style scoped>
.section-photo-upload {
    width: 100%;
}
</style>

