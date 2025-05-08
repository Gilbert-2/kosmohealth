<template>
  <div class="flo-meeting-live-with-face-processing">
    <!-- This component will be used as a wrapper around the existing live.vue component -->
    <slot></slot>

    <!-- Face processing wrapper that will be positioned over the video elements -->
    <flo-design-face-processing-wrapper
      v-if="shouldShowFaceProcessing && isEnabled && videoElementId"
      :videoElementId="videoElementId"
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
      @close="onClose"
    />

    <!-- Face detection status notification -->
    <div v-if="showFaceDetectionNotification" class="face-detection-notification glassmorphism">
      <div class="notification-icon">
        <i class="fas fa-user-slash"></i>
      </div>
      <div class="notification-text">Face not detected - applying full blur</div>
    </div>

    <!-- Notification for discomfort detection -->
    <div v-if="showDiscomfortNotification" class="discomfort-notification">
      <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        {{ $t('meeting.face_processing.discomfort_detected') }}
      </div>
    </div>
  </div>
</template>

<script>
import { mapGetters } from 'vuex';
import FaceProcessingVisibilityService from '../services/FaceProcessingVisibilityService';
import FloDesignFaceProcessingWrapper from './FloDesignFaceProcessingWrapper.vue';

export default {
  name: 'FloDesignMeetingLiveWithFaceProcessing',

  components: {
    FloDesignFaceProcessingWrapper
  },

  props: {
    videoElementId: {
      type: String,
      default: 'localVideo' // Default ID of the local video element
    }
  },

  data() {
    return {
      isEnabled: false,
      showControls: true,
      showCanvas: true,
      showEmotionInfo: true,
      autoBlurOnDiscomfort: true,
      processingFps: 5,
      currentEmotion: null,
      discomfortDetected: false,
      showDiscomfortNotification: false,
      discomfortNotificationTimeout: null,
      faceDetectionStatus: null,
      showFaceDetectionNotification: false,
      faceDetectionNotificationTimeout: null,
      shouldShowFaceProcessing: false,
      isVisible: true
    };
  },

  computed: {
    ...mapGetters('config', ['configs']),

    meetingConfig() {
      return this.configs.meeting || {};
    }
  },

  mounted() {
    // Check if face processing should be visible
    this.shouldShowFaceProcessing = FaceProcessingVisibilityService.shouldShowFaceProcessing();

    // Only initialize if face processing should be visible
    if (this.shouldShowFaceProcessing) {
      this.initializeFromConfig();
    }
  },

  beforeDestroy() {
    if (this.discomfortNotificationTimeout) {
      clearTimeout(this.discomfortNotificationTimeout);
    }
    if (this.faceDetectionNotificationTimeout) {
      clearTimeout(this.faceDetectionNotificationTimeout);
    }
  },

  methods: {
    initializeFromConfig() {
      // Initialize from config
      this.isEnabled = this.meetingConfig.enable_emotion_detection || this.meetingConfig.enable_face_blur || false;
      this.showControls = true;
      this.showCanvas = this.meetingConfig.enable_face_blur || false;
      this.showEmotionInfo = this.meetingConfig.show_emotion_info !== undefined
        ? this.meetingConfig.show_emotion_info
        : true;
      this.autoBlurOnDiscomfort = this.meetingConfig.auto_blur_on_discomfort !== undefined
        ? this.meetingConfig.auto_blur_on_discomfort
        : true;
      this.processingFps = this.meetingConfig.processing_fps || 3;
    },

    onEmotionDetected(emotion) {
      this.currentEmotion = emotion;
      this.$emit('emotion-detected', emotion);

      // Also emit to root for global components like EmotionMonitor
      this.$root.$emit('emotion-detected', emotion);
    },

    onDiscomfortDetected(data) {
      this.discomfortDetected = true;
      this.$emit('discomfort-detected', data);

      // Show notification
      this.showDiscomfortNotification = true;

      // Clear previous timeout
      if (this.discomfortNotificationTimeout) {
        clearTimeout(this.discomfortNotificationTimeout);
      }

      // Hide notification after 5 seconds
      this.discomfortNotificationTimeout = setTimeout(() => {
        this.showDiscomfortNotification = false;
      }, 5000);
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
      this.faceDetectionStatus = status;
      this.$emit('face-detection-status', status);

      // Show notification when full page blur is active
      if (status.fullPageBlurActive) {
        this.showFaceDetectionNotification = true;

        // Clear previous timeout
        if (this.faceDetectionNotificationTimeout) {
          clearTimeout(this.faceDetectionNotificationTimeout);
        }

        // Hide notification after 5 seconds
        this.faceDetectionNotificationTimeout = setTimeout(() => {
          this.showFaceDetectionNotification = false;
        }, 5000);
      } else {
        this.showFaceDetectionNotification = false;
      }
    },

    onClose() {
      this.isVisible = false;
    }
  }
};
</script>

<style scoped>
.flo-meeting-live-with-face-processing {
  position: relative;
  width: 100%;
  height: 100%;
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

.face-detection-notification {
  position: fixed;
  top: 20px;
  left: 50%;
  transform: translateX(-50%);
  padding: 15px 20px;
  z-index: 1000;
  display: flex;
  align-items: center;
  color: white;
  animation: fadeInOut 5s ease-in-out;
}

.notification-icon {
  font-size: 20px;
  margin-right: 10px;
}

.notification-text {
  font-weight: bold;
}

@keyframes fadeInOut {
  0% {
    opacity: 0;
    transform: translateX(-50%) translateY(-20px);
  }
  10% {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
  }
  90% {
    opacity: 1;
    transform: translateX(-50%) translateY(0);
  }
  100% {
    opacity: 0;
    transform: translateX(-50%) translateY(-20px);
  }
}

.discomfort-notification {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 9999;
  max-width: 300px;
  animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-20px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
