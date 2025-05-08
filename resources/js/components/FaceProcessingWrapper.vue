<template>
  <div class="face-processing-wrapper" v-if="shouldShowFaceProcessing">
    <face-processing
      v-if="videoElement"
      :videoElement="videoElement"
      :showControls="showControls"
      :showCanvas="showCanvas"
      :showEmotionInfo="showEmotionInfo"
      :autoBlurOnDiscomfort="autoBlurOnDiscomfort"
      :processingFps="processingFps"
      @emotion-detected="onEmotionDetected"
      @discomfort-detected="onDiscomfortDetected"
      @blur-toggled="onBlurToggled"
      @full-page-blur-toggled="onFullPageBlurToggled"
      @glassmorphism-toggled="onGlassmorphismToggled"
      @blur-intensity-changed="onBlurIntensityChanged"
      @face-detection-status="onFaceDetectionStatus"
      @emotion-detection-toggled="onEmotionDetectionToggled"
    />
  </div>
</template>

<script>
import FaceProcessing from './FaceProcessing.vue';
import FaceProcessingVisibilityService from '../services/FaceProcessingVisibilityService';

export default {
  name: 'FaceProcessingWrapper',
  components: {
    FaceProcessing
  },

  props: {
    videoElementId: {
      type: String,
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
      videoElement: null,
      shouldShowFaceProcessing: false
    };
  },

  mounted() {
    // Check if face processing should be visible
    this.shouldShowFaceProcessing = FaceProcessingVisibilityService.shouldShowFaceProcessing();

    // Only find video element if face processing should be visible
    if (this.shouldShowFaceProcessing) {
      this.$nextTick(() => {
        this.findVideoElement();
      });
    }
  },

  methods: {
    findVideoElement() {
      // Try to find the video element by ID
      this.videoElement = document.getElementById(this.videoElementId);

      if (!this.videoElement) {
        console.warn(`Video element with ID "${this.videoElementId}" not found. Retrying in 1 second...`);
        setTimeout(() => {
          this.findVideoElement();
        }, 1000);
      }
    },

    onEmotionDetected(emotion) {
      this.$emit('emotion-detected', emotion);
    },

    onDiscomfortDetected(data) {
      this.$emit('discomfort-detected', data);
    },

    onBlurToggled(enabled) {
      this.$emit('blur-toggled', enabled);
    },

    onEmotionDetectionToggled(enabled) {
      this.$emit('emotion-detection-toggled', enabled);
    },

    onFullPageBlurToggled(enabled) {
      this.$emit('full-page-blur-toggled', enabled);
    },

    onGlassmorphismToggled(enabled) {
      this.$emit('glassmorphism-toggled', enabled);
    },

    onBlurIntensityChanged(intensity) {
      this.$emit('blur-intensity-changed', intensity);
    },

    onFaceDetectionStatus(status) {
      this.$emit('face-detection-status', status);
    }
  }
};
</script>

<style scoped>
.face-processing-wrapper {
  position: relative;
  width: 100%;
  height: 100%;
}
</style>
