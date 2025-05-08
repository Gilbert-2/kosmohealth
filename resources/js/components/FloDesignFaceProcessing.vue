<template>
  <div
    ref="container"
    class="face-processing-controls draggable"
    :style="containerStyle"
    @mousedown="startDrag"
    @touchstart="startDrag"
  >
    <div class="face-processing-header">
      <h3 class="face-processing-title">Face Processing</h3>
      <button class="face-processing-close" @click="$emit('close')">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <div class="face-processing-section">
      <h4 class="face-processing-section-title">Privacy Controls</h4>

      <div class="face-processing-toggle">
        <input
          type="checkbox"
          id="face-blur-toggle"
          v-model="blurEnabled"
          @change="toggleBlur"
        >
        <label for="face-blur-toggle" class="face-processing-toggle-label">
          Face Blur
        </label>
      </div>

      <div class="face-processing-toggle">
        <input
          type="checkbox"
          id="full-page-blur-toggle"
          v-model="fullPageBlurEnabled"
          @change="toggleFullPageBlur"
        >
        <label for="full-page-blur-toggle" class="face-processing-toggle-label">
          Auto-blur when face not detected
        </label>
      </div>

      <div class="face-processing-toggle">
        <input
          type="checkbox"
          id="glassmorphism-toggle"
          v-model="glassmorphismEnabled"
          @change="toggleGlassmorphism"
        >
        <label for="glassmorphism-toggle" class="face-processing-toggle-label">
          Glassmorphism Effect
        </label>
      </div>

      <div v-if="blurEnabled">
        <label for="blur-intensity" class="face-processing-toggle-label">
          Blur Intensity: {{ blurIntensity }}
        </label>
        <input
          type="range"
          id="blur-intensity"
          min="5"
          max="30"
          step="1"
          v-model.number="blurIntensity"
          @input="changeBlurIntensity"
          class="face-processing-slider"
        >
      </div>
    </div>

    <div class="face-processing-section">
      <h4 class="face-processing-section-title">Emotion Detection</h4>

      <div class="face-processing-toggle">
        <input
          type="checkbox"
          id="emotion-detection-toggle"
          v-model="emotionDetectionEnabled"
          @change="toggleEmotionDetection"
        >
        <label for="emotion-detection-toggle" class="face-processing-toggle-label">
          Enable Emotion Detection
        </label>
      </div>

      <div v-if="emotionDetectionEnabled && currentEmotion">
        <div class="face-processing-emotion">
          <span class="face-processing-emotion-name">
            {{ currentEmotion.dominant }}
          </span>
          <span class="face-processing-emotion-value">
            {{ Math.round(currentEmotion.score * 100) }}%
          </span>
        </div>

        <div
          v-for="(score, emotion) in currentEmotion.scores"
          :key="emotion"
          class="face-processing-emotion"
        >
          <span class="face-processing-emotion-name">{{ emotion }}</span>
          <span class="face-processing-emotion-value">
            {{ Math.round(score * 100) }}%
          </span>
        </div>
      </div>
    </div>

    <div class="face-processing-footer">
      <small v-if="faceDetectionStatus">
        {{ faceDetectionStatus.faceDetected ? 'Face detected' : 'No face detected' }}
      </small>
    </div>
  </div>
</template>

<script>
import FaceProcessingService from '../services/FaceProcessingService';

export default {
  name: 'FloDesignFaceProcessing',

  props: {
    videoElement: {
      type: HTMLVideoElement,
      required: true
    },
    showControls: {
      type: Boolean,
      default: true
    },
    showCanvas: {
      type: Boolean,
      default: true
    },
    showEmotionInfo: {
      type: Boolean,
      default: true
    },
    autoBlurOnDiscomfort: {
      type: Boolean,
      default: true
    },
    processingFps: {
      type: Number,
      default: 3
    }
  },

  data() {
    return {
      isInitialized: false,
      blurEnabled: true,
      fullPageBlurEnabled: true,
      glassmorphismEnabled: true,
      emotionDetectionEnabled: true,
      currentEmotion: null,
      discomfortDetected: false,
      faceDetectionStatus: null,
      blurIntensity: 15,

      // Draggable functionality
      isDragging: false,
      dragStartX: 0,
      dragStartY: 0,
      positionX: 20,
      positionY: 20
    };
  },

  computed: {
    containerStyle() {
      return {
        position: 'fixed',
        top: `${this.positionY}px`,
        right: `${this.positionX}px`,
        zIndex: 1000
      };
    }
  },

  mounted() {
    this.initialize();

    // Add event listeners for drag
    document.addEventListener('mousemove', this.onDrag);
    document.addEventListener('mouseup', this.stopDrag);
    document.addEventListener('touchmove', this.onDrag);
    document.addEventListener('touchend', this.stopDrag);
  },

  beforeDestroy() {
    // Clean up event listeners
    document.removeEventListener('mousemove', this.onDrag);
    document.removeEventListener('mouseup', this.stopDrag);
    document.removeEventListener('touchmove', this.onDrag);
    document.removeEventListener('touchend', this.stopDrag);

    // Stop processing
    if (this.isInitialized) {
      FaceProcessingService.stopProcessing();
    }
  },

  methods: {
    async initialize() {
      try {
        // Create canvas for face processing
        const canvas = document.createElement('canvas');
        canvas.className = 'face-processing-canvas';
        canvas.style.position = 'absolute';
        canvas.style.top = '0';
        canvas.style.left = '0';
        canvas.style.width = '100%';
        canvas.style.height = '100%';
        canvas.style.zIndex = '10';

        // Add canvas to video container
        const videoContainer = this.videoElement.parentElement;
        if (videoContainer) {
          videoContainer.style.position = 'relative';
          videoContainer.appendChild(canvas);

          // Load face-api.js models
          await FaceProcessingService.loadModels();

          // Set up emotion detection callback
          FaceProcessingService.onEmotionDetected(emotion => {
            this.currentEmotion = emotion;
            this.$emit('emotion-detected', emotion);
          });

          // Set up discomfort detection callback
          FaceProcessingService.onDiscomfortDetected((emotion, score) => {
            this.discomfortDetected = true;
            this.$emit('discomfort-detected', { emotion, score });

            // Auto-enable blur if discomfort is detected and auto-blur is enabled
            if (this.autoBlurOnDiscomfort && !this.blurEnabled) {
              this.blurEnabled = true;
              FaceProcessingService.setBlurEnabled(true);
            }
          });

          // Set up face detection status callback
          FaceProcessingService.onFaceDetectionStatus(status => {
            this.faceDetectionStatus = status;
            this.$emit('face-detection-status', status);
          });

          // Initialize service with current settings
          FaceProcessingService.setBlurEnabled(this.blurEnabled);
          FaceProcessingService.setFullPageBlurEnabled(this.fullPageBlurEnabled);
          FaceProcessingService.setGlassmorphismEnabled(this.glassmorphismEnabled);
          FaceProcessingService.setEmotionDetectionEnabled(this.emotionDetectionEnabled);
          FaceProcessingService.setBlurIntensity(this.blurIntensity);

          // Start processing
          FaceProcessingService.startProcessing(
            this.videoElement,
            canvas,
            this.processingFps
          );

          this.isInitialized = true;
        }
      } catch (error) {
        console.error('Error initializing face processing:', error);
      }
    },

    toggleBlur() {
      FaceProcessingService.setBlurEnabled(this.blurEnabled);
      this.$emit('blur-toggled', this.blurEnabled);
    },

    toggleFullPageBlur() {
      FaceProcessingService.setFullPageBlurEnabled(this.fullPageBlurEnabled);
      this.$emit('full-page-blur-toggled', this.fullPageBlurEnabled);
    },

    toggleGlassmorphism() {
      FaceProcessingService.setGlassmorphismEnabled(this.glassmorphismEnabled);
      this.$emit('glassmorphism-toggled', this.glassmorphismEnabled);
    },

    toggleEmotionDetection() {
      FaceProcessingService.setEmotionDetectionEnabled(this.emotionDetectionEnabled);
      this.$emit('emotion-detection-toggled', this.emotionDetectionEnabled);
    },

    changeBlurIntensity() {
      FaceProcessingService.setBlurIntensity(this.blurIntensity);
      this.$emit('blur-intensity-changed', this.blurIntensity);
    },

    // Draggable functionality
    startDrag(event) {
      // Only start drag on header
      const target = event.target;
      const header = this.$refs.container.querySelector('.face-processing-header');

      if (header.contains(target) || target === this.$refs.container) {
        this.isDragging = true;

        // Get initial position
        if (event.type === 'mousedown') {
          this.dragStartX = event.clientX;
          this.dragStartY = event.clientY;
        } else if (event.type === 'touchstart') {
          this.dragStartX = event.touches[0].clientX;
          this.dragStartY = event.touches[0].clientY;
        }

        // Prevent default to avoid text selection during drag
        event.preventDefault();
      }
    },

    onDrag(event) {
      if (!this.isDragging) return;

      let clientX, clientY;

      if (event.type === 'mousemove') {
        clientX = event.clientX;
        clientY = event.clientY;
      } else if (event.type === 'touchmove') {
        clientX = event.touches[0].clientX;
        clientY = event.touches[0].clientY;
      }

      // Calculate new position
      const deltaX = clientX - this.dragStartX;
      const deltaY = clientY - this.dragStartY;

      // Update position (right position decreases as X increases)
      this.positionX = Math.max(20, this.positionX - deltaX);
      this.positionY = Math.max(20, this.positionY + deltaY);

      // Update drag start position
      this.dragStartX = clientX;
      this.dragStartY = clientY;
    },

    stopDrag() {
      this.isDragging = false;
    }
  }
};
</script>
