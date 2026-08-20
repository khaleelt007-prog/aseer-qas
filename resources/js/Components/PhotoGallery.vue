<template>
    <div class="photo-gallery">
        <!-- Photo Count Indicator -->
        <div v-if="photos.length > 0" class="photo-count mb-4">
            <div class="flex items-center justify-between">
                <span class="text-sm font-medium text-gray-700">
                    {{ $t('quality.photos_attached', { count: photos.length }) || `${photos.length} photo(s) attached` }}
                </span>
                <span class="text-xs text-gray-500">
                    {{ $t('quality.tap_to_delete') || 'Tap photo to delete' }}
                </span>
            </div>
        </div>

        <!-- Photo Grid -->
        <div v-if="photos.length > 0" class="photo-grid">
            <div
                v-for="photo in photos"
                :key="photo.id"
                class="photo-item"
                :class="{ 'disabled': disabled }"
            >
                <div class="photo-container">
                    <img
                        :src="photo.url || photo.preview"
                        :alt="$t('quality.captured_photo') || 'Captured photo'"
                        class="photo-thumbnail"
                        @click="!disabled && confirmDelete(photo)"
                    />
                    
                    <!-- Delete Button Overlay -->
                    <button
                        v-if="!disabled"
                        type="button"
                        @click="confirmDelete(photo)"
                        class="delete-button"
                        :aria-label="$t('quality.delete_photo') || 'Delete photo'"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    
                    <!-- Photo Info -->
                    <div class="photo-info">
                        <span class="photo-timestamp">
                            {{ formatTimestamp(photo.timestamp) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div v-else class="empty-state">
            <div class="empty-state-content">
                <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <p class="empty-text">
                    {{ $t('quality.no_photos') || 'No photos captured yet' }}
                </p>
                <p class="empty-subtext">
                    {{ $t('quality.photos_help') || 'Use the camera or upload button above to add photos' }}
                </p>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div v-if="showDeleteModal" class="modal-overlay" @click="cancelDelete">
            <div class="modal-content" @click.stop>
                <h3 class="modal-title">
                    {{ $t('quality.confirm_delete') || 'Delete Photo?' }}
                </h3>
                <p class="modal-text">
                    {{ $t('quality.delete_photo_warning') || 'This action cannot be undone. Are you sure you want to delete this photo?' }}
                </p>
                
                <div class="modal-actions">
                    <button
                        type="button"
                        @click="cancelDelete"
                        class="qc-button bg-gray-500 hover:bg-gray-600"
                    >
                        {{ $t('quality.cancel') || 'Cancel' }}
                    </button>
                    <button
                        type="button"
                        @click="deletePhoto"
                        class="qc-button bg-red-600 hover:bg-red-700 text-white"
                    >
                        {{ $t('quality.delete') || 'Delete' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'

// Props
const props = defineProps({
    photos: {
        type: Array,
        default: () => []
    },
    disabled: {
        type: Boolean,
        default: false
    }
})

// Emits
const emit = defineEmits(['photo-deleted'])

// i18n
const { t } = useI18n()

// Reactive data
const showDeleteModal = ref(false)
const photoToDelete = ref(null)

// Methods
const confirmDelete = (photo) => {
    photoToDelete.value = photo
    showDeleteModal.value = true
}

const deletePhoto = () => {
    if (photoToDelete.value) {
        emit('photo-deleted', photoToDelete.value.id)
    }
    cancelDelete()
}

const cancelDelete = () => {
    showDeleteModal.value = false
    photoToDelete.value = null
}

const formatTimestamp = (timestamp) => {
    const date = new Date(timestamp)
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}
</script>

<style scoped>
.photo-gallery {
    width: 100%;
}

.photo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

@media (min-width: 768px) {
    .photo-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }
}

.photo-item {
    position: relative;
    transition: all 0.3s ease;
}

.photo-item.disabled {
    opacity: 0.6;
    pointer-events: none;
}

.photo-container {
    position: relative;
    border-radius: 0.5rem;
    overflow: hidden;
    border: 2px solid var(--stroke);
    transition: all 0.3s ease;
}

.photo-container:hover {
    border-color: var(--button);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.photo-thumbnail {
    width: 100%;
    height: 120px;
    object-fit: cover;
    cursor: pointer;
    transition: all 0.3s ease;
}

.photo-thumbnail:hover {
    opacity: 0.8;
}

.delete-button {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    background-color: rgba(239, 68, 68, 0.9);
    color: white;
    border: none;
    border-radius: 50%;
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    opacity: 0;
}

.photo-container:hover .delete-button {
    opacity: 1;
}

.delete-button:hover {
    background-color: rgba(220, 38, 38, 0.9);
    transform: scale(1.1);
}

.photo-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(transparent, rgba(0, 0, 0, 0.7));
    padding: 0.5rem;
    color: white;
}

.photo-timestamp {
    font-size: 0.75rem;
    font-weight: 500;
}

.empty-state {
    text-align: center;
    padding: 2rem;
    border: 2px dashed #d1d5db;
    border-radius: 0.5rem;
    background-color: #f9fafb;
}

.empty-state-content {
    max-width: 300px;
    margin: 0 auto;
}

.empty-icon {
    width: 3rem;
    height: 3rem;
    color: #9ca3af;
    margin: 0 auto 1rem;
}

.empty-text {
    font-size: 1rem;
    font-weight: 500;
    color: #6b7280;
    margin-bottom: 0.5rem;
}

.empty-subtext {
    font-size: 0.875rem;
    color: #9ca3af;
}

/* Modal Styles */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
}

.modal-content {
    background-color: var(--secondary);
    border-radius: 0.5rem;
    padding: 1.5rem;
    max-width: 400px;
    width: 100%;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.modal-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--headline);
    margin-bottom: 0.5rem;
}

.modal-text {
    color: var(--paragraph);
    margin-bottom: 1.5rem;
    line-height: 1.5;
}

.modal-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
}

/* RTL Support */
[dir="rtl"] .delete-button {
    right: auto;
    left: 0.5rem;
}

[dir="rtl"] .modal-actions {
    justify-content: flex-start;
}

[dir="rtl"] .photo-grid {
    direction: rtl;
}
</style>
