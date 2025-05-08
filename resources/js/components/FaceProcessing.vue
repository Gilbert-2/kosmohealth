<template>
  <div class="face-processing-container">
    <canvas
      ref="outputCanvas"
      class="face-processing-canvas"
      :class="{ 'hidden': !showCanvas }"
    ></canvas>

    <div v-if="showControls" class="face-processing-controls glassmorphism">
      <div class="controls-header">
        <span class="controls-title">Privacy Controls</span>
      </div>

      <div class="form-check form-switch">
        <input
          class="form-check-input"
          type="checkbox"
          id="blurFaceToggle"
          v-model="blurEnabled"
          @change="toggleBlur"
        >
        <label class="form-check-label" for="blurFaceToggle">
          Blur Face
        </label>
      </div>

      <div class="form-check form-switch">
        <input
          class="form-check-input"
          type="checkbox"
          id="fullPageBlurToggle"
          v-model="fullPageBlurEnabled"
          @change="toggleFullPageBlur"
        >
        <label class="form-check-label" for="fullPageBlurToggle">
          Auto-Blur When No Face
        </label>
      </div>

      <div class="form-check form-switch">
        <input
          class="form-check-input"
          type="checkbox"
          id="glassmorphismToggle"
          v-model="glassmorphismEnabled"
          @change="toggleGlassmorphism"
        >
        <label class="form-check-label" for="glassmorphismToggle">
          Glassmorphism Effect
        </label>
      </div>

      <div class="form-check form-switch">
        <input
          class="form-check-input"
          type="checkbox"
          id="emotionDetectionToggle"
          v-model="emotionDetectionEnabled"
          @change="toggleEmotionDetection"
        >
        <label class="form-check-label" for="emotionDetectionToggle">
          Emotion Detection
        </label>
      </div>

      <div class="blur-intensity-control" v-if="blurEnabled">
        <label for="blurIntensity">Blur Intensity: {{ blurIntensity }}</label>
        <input
          type="range"
          id="blurIntensity"
          v-model.number="blurIntensity"
          min="1"
          max="30"
          @change="updateBlurIntensity"
          class="form-range"
        >
      </div>
    </div>

    <div v-if="showEmotionInfo && currentEmotion" class="emotion-info glassmorphism">
      <div class="emotion-badge" :class="emotionClass">
        {{ currentEmotion.dominant }}
      </div>
    </div>

    <div v-if="faceDetectionStatus && !faceDetectionStatus.faceDetected" class="face-detection-status glassmorphism">
      <div class="status-icon">
        <i class="fas fa-user-slash"></i>
      </div>
      <div class="status-text">Face not detected</div>
    </div>
  </div>
</template>

<script>
import FaceProcessingService from '../services/FaceProcessingService';

export default {
  name: 'FaceProcessing',

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
      blurIntensity: 15
    };
  },

  computed: {
    emotionClass() {
      if (!this.currentEmotion) return '';

      const emotion = this.currentEmotion.dominant;

      // Map emotions to CSS classes
      const emotionClasses = {
        'happy': 'emotion-happy',
        'sad': 'emotion-sad',
        'angry': 'emotion-angry',
        'fearful': 'emotion-fearful',
        'disgusted': 'emotion-disgusted',
        'surprised': 'emotion-surprised',
        'neutral': 'emotion-neutral'
      };

      return emotionClasses[emotion] || '';
    }
  },

  async mounted() {
    await this.initFaceProcessing();
  },

  beforeDestroy() {
    this.stopProcessing();

    // Remove event listeners
    window.removeEventListener('resize', this.handleResize);

    if (this.videoElement) {
      this.videoElement.removeEventListener('resize', this.handleVideoResize);
      this.videoElement.removeEventListener('loadedmetadata', this.handleVideoResize);
    }
  },

  methods: {
    async initFaceProcessing() {
      try {
        // Load face-api models
        const modelsLoaded = await FaceProcessingService.loadModels();

        if (!modelsLoaded) {
          console.error('Failed to load face-api models');
          return;
        }

        // Set up canvas
        const canvas = this.$refs.outputCanvas;
        if (canvas && this.videoElement) {
          // Set canvas dimensions to match video
          canvas.width = this.videoElement.videoWidth || 640;
          canvas.height = this.videoElement.videoHeight || 480;

          // Add resize event listener to handle video size changes
          window.addEventListener('resize', this.handleResize);

          // Handle video resize events
          this.videoElement.addEventListener('resize', this.handleVideoResize);
          this.videoElement.addEventListener('loadedmetadata', this.handleVideoResize);

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

    updateBlurIntensity() {
      FaceProcessingService.setBlurIntensity(this.blurIntensity);
      this.$emit('blur-intensity-changed', this.blurIntensity);
    },

    toggleEmotionDetection() {
      FaceProcessingService.setEmotionDetectionEnabled(this.emotionDetectionEnabled);
      this.$emit('emotion-detection-toggled', this.emotionDetectionEnabled);
    },

    stopProcessing() {
      if (this.isInitialized) {
        FaceProcessingService.stopProcessing();
      }
    },

    restartProcessing() {
      this.stopProcessing();

      if (this.$refs.outputCanvas && this.videoElement) {
        FaceProcessingService.startProcessing(
          this.videoElement,
          this.$refs.outputCanvas,
          this.processingFps
        );
      }
    },

    handleResize() {
      // Update canvas dimensions when window is resized
      this.updateCanvasDimensions();
    },

    handleVideoResize() {
      // Update canvas dimensions when video size changes
      this.updateCanvasDimensions();
    },

    updateCanvasDimensions() {
      const canvas = this.$refs.outputCanvas;
      if (canvas && this.videoElement) {
        // Get current video dimensions
        const videoWidth = this.videoElement.videoWidth || 640;
        const videoHeight = this.videoElement.videoHeight || 480;

        // Update canvas size
        canvas.width = videoWidth;
        canvas.height = videoHeight;

        // Restart processing to apply new dimensions
        this.restartProcessing();
      }
    }
  }
};
</script>

<style scoped>
.face-processing-container {
  position: relative;
  width: 100%;
  height: 100%;
}

.face-processing-canvas {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: 10;
  pointer-events: none;
}

/* Glassmorphism effect */
.glassmorphism {
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.18);
  box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.2);
  transition: all 0.3s ease;
}

.face-processing-controls {
  position: absolute;
  bottom: 20px;
  right: 20px;
  padding: 15px;
  z-index: 20;
  width: 250px;
  color: white;
  transition: all 0.3s ease;
}

.face-processing-controls:hover {
  box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.3);
}

.controls-header {
  margin-bottom: 15px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
  padding-bottom: 8px;
}

.controls-title {
  font-weight: bold;
  font-size: 16px;
}

.form-check {
  margin-bottom: 10px;
}

.blur-intensity-control {
  margin-top: 15px;
}

.emotion-info {
  position: absolute;
  top: 20px;
  right: 20px;
  padding: 10px;
  z-index: 20;
  transition: all 0.3s ease;
}

.emotion-badge {
  padding: 5px 10px;
  border-radius: 20px;
  font-weight: bold;
  text-transform: capitalize;
}

.face-detection-status {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  padding: 15px 20px;
  z-index: 15;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: white;
  animation: pulse 2s infinite;
}

.status-icon {
  font-size: 24px;
  margin-bottom: 10px;
}

.status-text {
  font-weight: bold;
}

@keyframes pulse {
  0% {
    opacity: 0.7;
    transform: translate(-50%, -50%) scale(1);
  }
  50% {
    opacity: 0.9;
    transform: translate(-50%, -50%) scale(1.05);
  }
  100% {
    opacity: 0.7;
    transform: translate(-50%, -50%) scale(1);
  }
}

.face-processing-canvas.hidden {
  display: none;
}

.face-processing-controls {
  position: absolute;
  bottom: 10px;
  right: 10px;
  background-color: rgba(0, 0, 0, 0.5);
  padding: 8px;
  border-radius: 4px;
  z-index: 20;
  color: white;
}

.emotion-info {
  position: absolute;
  top: 10px;
  right: 10px;
  z-index: 20;
}

.emotion-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-weight: bold;
  text-transform: capitalize;
  background-color: rgba(0, 0, 0, 0.5);
  color: white;
}

.emotion-happy {
  background-color: rgba(76, 175, 80, 0.7);
}

.emotion-sad {
  background-color: rgba(33, 150, 243, 0.7);
}

.emotion-angry {
  background-color: rgba(244, 67, 54, 0.7);
}

.emotion-fearful {
  background-color: rgba(156, 39, 176, 0.7);
}

.emotion-disgusted {
  background-color: rgba(121, 85, 72, 0.7);
}

.emotion-surprised {
  background-color: rgba(255, 193, 7, 0.7);
}

.emotion-neutral {
  background-color: rgba(158, 158, 158, 0.7);
}
</style>
