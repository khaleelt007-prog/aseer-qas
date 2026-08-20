<template>
    <div class="photo-capture">
        <!-- Camera Controls -->
        <div class="camera-controls mb-4">
            <button
                type="button"
                @click="startCamera"
                v-if="!cameraActive && !capturing && !uploading"
                class="qc-button camera-button"
                :disabled="disabled"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                {{ $t('quality.start_camera') || 'Start Camera' }}
            </button>

            <!-- Stop Camera Button (when camera is active) -->
            <button
                v-if="cameraActive"
                type="button"
                @click="stopCamera"
                class="qc-button bg-gray-500 hover:bg-gray-600"
                :disabled="disabled"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                {{ $t('quality.stop_camera') || 'Stop Camera' }}
            </button>

            <!-- Uploading State -->
            <button
                v-if="uploading"
                type="button"
                disabled
                class="qc-button bg-blue-500 cursor-not-allowed"
            >
                <svg class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ $t('quality.uploading') || 'Uploading...' }}
            </button>

            <!-- File Upload Alternative -->
            <div class="file-upload-alternative mt-2">
                <label class="qc-button bg-secondary border-2 border-dashed border-gray-300 hover:border-gray-400 cursor-pointer" :class="{ 'opacity-50 cursor-not-allowed': uploading }">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    {{ $t('quality.upload_photo') || 'Upload Photo' }}
                    <input
                        type="file"
                        accept="image/*"
                        multiple
                        @change="handleFileUpload"
                        class="hidden"
                        :disabled="disabled || uploading"
                    />
                </label>
            </div>
        </div>

        <!-- Camera Preview -->
        <div v-if="cameraActive" class="camera-preview mb-4">
            <div class="camera-container">
                <video
                    ref="videoElement"
                    autoplay
                    playsinline
                    class="camera-video"
                    :class="{ 'capturing': capturing }"
                ></video>

                <!-- Camera Controls Overlay -->
                <div class="camera-overlay">
                    <!-- Capture Button -->
                    <button
                        type="button"
                        @click="capturePhoto"
                        class="capture-button-overlay"
                        :class="{ 'capturing': capturing }"
                        :disabled="disabled || capturing"
                        :aria-label="capturing ? ($t('quality.capturing') || 'Capturing...') : ($t('quality.capture_photo') || 'Capture Photo')"
                    >
                        <div class="capture-button-inner">
                            <svg v-if="!capturing" class="capture-icon" fill="currentColor" stroke="none" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="8" fill="none" stroke="currentColor" stroke-width="2"></circle>
                                <circle cx="12" cy="12" r="3" fill="currentColor"></circle>
                            </svg>
                            <div v-else class="capturing-spinner">
                                <svg class="animate-spin capture-icon" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                    </button>
                </div>

                <canvas ref="canvasElement" class="hidden"></canvas>
            </div>
        </div>

        <!-- Error Messages -->
        <div v-if="error" class="error-message mb-4">
            <div class="bg-red-50 border border-red-200 rounded-md p-3">
                <div class="flex">
                    <svg class="w-5 h-5 text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <p class="text-sm text-red-700">{{ error }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onUnmounted, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import axios from 'axios'

// Props
const props = defineProps({
    disabled: {
        type: Boolean,
        default: false
    }
})

// Emits
const emit = defineEmits(['photo-captured'])

// i18n
const { t } = useI18n()

// Reactive data
const videoElement = ref(null)
const canvasElement = ref(null)
const cameraActive = ref(false)
const capturing = ref(false)
const uploading = ref(false)
const error = ref('')
const stream = ref(null)

// Methods
const startCamera = async () => {
    try {
        error.value = ''

        // Request camera access with rear camera preference
        const constraints = {
            video: {
                facingMode: { ideal: 'environment' }, // Prefer rear camera
                width: { ideal: 1920 },
                height: { ideal: 1080 }
            }
        }

        stream.value = await navigator.mediaDevices.getUserMedia(constraints)

        // Set cameraActive to true first to render the video element
        cameraActive.value = true

        // Wait for the DOM to update and the video element to be rendered
        await nextTick()

        // Now set the stream to the video element
        if (videoElement.value) {
            videoElement.value.srcObject = stream.value
        } else {
            // If video element is still not available, stop the stream and show error
            if (stream.value) {
                stream.value.getTracks().forEach(track => track.stop())
                stream.value = null
            }
            cameraActive.value = false
            throw new Error('Video element not available')
        }
    } catch (err) {
        console.error('Error accessing camera:', err)
        error.value = t('quality.camera_error') || 'Unable to access camera. Please check permissions or try uploading a photo instead.'
        cameraActive.value = false
    }
}

const stopCamera = () => {
    if (stream.value) {
        stream.value.getTracks().forEach(track => track.stop())
        stream.value = null
    }
    cameraActive.value = false
    error.value = ''
}

const capturePhoto = async () => {
    if (!videoElement.value || !canvasElement.value) return

    try {
        capturing.value = true

        const video = videoElement.value
        const canvas = canvasElement.value
        const context = canvas.getContext('2d')

        // Set canvas dimensions to match video
        canvas.width = video.videoWidth
        canvas.height = video.videoHeight

        // Draw the video frame to canvas
        context.drawImage(video, 0, 0, canvas.width, canvas.height)

        // Convert to blob
        canvas.toBlob(async (blob) => {
            if (blob) {
                try {
                    // Upload the photo immediately
                    uploading.value = true
                    const formData = new FormData()
                    formData.append('photo', blob, `photo_${Date.now()}.jpg`)

                    const response = await axios.post(route('quality-evaluations.api.photos.upload'), formData, {
                        headers: {
                            'Content-Type': 'multipart/form-data'
                        }
                    })

                    const data = response.data

                    if (data.success) {
                        // Emit the photo ID and URL instead of file
                        emit('photo-captured', {
                            id: data.photo_id,
                            url: data.url,
                            preview: URL.createObjectURL(blob),
                            timestamp: new Date().toISOString(),
                            section_id: null,
                            isUploaded: true
                        })

                        // Stop camera after successful upload
                        stopCamera()
                    } else {
                        error.value = data.error || (t('quality.capture_error') || 'Failed to upload photo.')
                    }
                } catch (uploadErr) {
                    console.error('Error uploading photo:', uploadErr)
                    error.value = t('quality.upload_error') || 'Failed to upload photo. Please try again.'
                } finally {
                    uploading.value = false
                }
            }
            capturing.value = false
        }, 'image/jpeg', 0.8)

    } catch (err) {
        console.error('Error capturing photo:', err)
        error.value = t('quality.capture_error') || 'Failed to capture photo. Please try again.'
        capturing.value = false
    }
}

const handleFileUpload = async (event) => {
    const files = Array.from(event.target.files)

    for (const file of files) {
        if (file.type.startsWith('image/')) {
            try {
                uploading.value = true
                const formData = new FormData()
                formData.append('photo', file)

                const response = await axios.post(route('quality-evaluations.api.photos.upload'), formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data'
                    }
                })

                const data = response.data

                if (data.success) {
                    const preview = URL.createObjectURL(file)
                    emit('photo-captured', {
                        id: data.photo_id,
                        url: data.url,
                        preview,
                        timestamp: new Date().toISOString(),
                        section_id: null,
                        isUploaded: true
                    })
                } else {
                    error.value = data.error || (t('quality.upload_error') || 'Failed to upload photo.')
                }
            } catch (err) {
                console.error('Error uploading file:', err)
                error.value = t('quality.upload_error') || 'Failed to upload photo. Please try again.'
            } finally {
                uploading.value = false
            }
        }
    }

    // Clear the input
    event.target.value = ''
}

// Cleanup on unmount
onUnmounted(() => {
    stopCamera()
})
</script>

<style scoped>
.photo-capture {
    width: 100%;
}

.camera-container {
    position: relative;
    display: inline-block;
    max-width: 400px;
    width: 100%;
}

.camera-video {
    width: 100%;
    height: auto;
    border-radius: 0.5rem;
    border: 2px solid var(--stroke);
    transition: all 0.3s ease;
    display: block;
}

.camera-video.capturing {
    border-color: var(--button);
    box-shadow: 0 0 0 3px rgba(255, 142, 60, 0.3);
}

.camera-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    display: flex;
    justify-content: center;
    align-items: flex-end;
    padding: 1rem;
    background: linear-gradient(transparent, rgba(0, 0, 0, 0.3));
    border-radius: 0 0 0.5rem 0.5rem;
}

.capture-button-overlay {
    width: 4rem;
    height: 4rem;
    border-radius: 50%;
    background-color: rgba(255, 255, 255, 0.9);
    border: 3px solid var(--button);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.capture-button-overlay:hover {
    background-color: rgba(255, 255, 255, 1);
    border-color: var(--highlight);
    transform: scale(1.05);
}

.capture-button-overlay:active {
    transform: scale(0.95);
}

.capture-button-overlay.capturing {
    background-color: var(--tertiary);
    border-color: var(--tertiary);
    animation: pulse 1.5s infinite;
}

.capture-button-overlay:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}

.capture-button-inner {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}

.capture-icon {
    width: 1.5rem;
    height: 1.5rem;
    color: var(--button);
    stroke-width: 2.5;
}

.capture-button-overlay.capturing .capture-icon {
    color: white;
}

.capturing-spinner {
    display: flex;
    align-items: center;
    justify-content: center;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}

.camera-button {
    background-color: var(--button);
    color: var(--button-text);
}

.camera-button:hover {
    background-color: var(--highlight);
}

@media (max-width: 767px) {
    .camera-container {
        max-width: 100%;
    }

    .camera-overlay {
        padding: 0.75rem;
    }

    .capture-button-overlay {
        width: 3.5rem;
        height: 3.5rem;
    }

    .capture-icon {
        width: 1.25rem;
        height: 1.25rem;
    }
}

/* Touch device optimizations */
@media (hover: none) and (pointer: coarse) {
    .capture-button-overlay:hover {
        transform: none;
        background-color: rgba(255, 255, 255, 0.9);
        border-color: var(--button);
    }

    .capture-button-overlay:active {
        transform: scale(0.95);
        background-color: rgba(255, 255, 255, 1);
    }
}

/* RTL Support */
[dir="rtl"] .camera-overlay {
    direction: ltr; /* Keep camera controls in standard orientation */
}
</style>
