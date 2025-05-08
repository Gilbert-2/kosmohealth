<template>
  <div class="gesture-enabled-meeting-live">
    <!-- Slot for the original meeting content -->
    <slot></slot>
    
    <!-- Enhanced face processing wrapper that supports both face and audio emotion detection -->
    <enhanced-face-processing-wrapper
      v-if="shouldShowFaceProcessing && !isGestureMode && videoElementId"
      :videoElementId="videoElementId"
      :showControls="showControls"
      :showCanvas="showCanvas"
      :showEmotionInfo="showEmotionInfo"
      :autoBlurOnDiscomfort="autoBlurOnDiscomfort"
      :processingFps="processingFps"
      @emotion-detected="onEmotionDetected"
      @discomfort-detected="onDiscomfortDetected"
      @blur-toggled="onBlurToggled"
      @detection-mode-changed="onDetectionModeChanged"
      @blur-intensity-changed="onBlurIntensityChanged"
    />
    
    <!-- Gesture recognition wrapper -->
    <gesture-recognition-wrapper
      v-if="shouldShowGestureRecognition && videoElementId"
      :videoElementId="videoElementId"
      :showControls="showControls"
      :showCanvas="showCanvas"
      :showGestureInfo="showGestureInfo"
      :processingFps="processingFps"
      @gesture-detected="onGestureDetected"
      @gesture-mode-changed="onGestureModeChanged"
    />
    
    <!-- Gesture interpreter panel (for doctors) -->
    <gesture-interpreter
      v-if="isDoctor && isGestureMode"
      @gesture-mode-changed="onGestureModeChanged"
    />
    
    <!-- Discomfort notification -->
    <div class="notification discomfort-notification" v-if="showDiscomfortNotification">
      <i class="fas fa-exclamation-triangle"></i>
      <span>Discomfort detected. Face blur has been activated.</span>
      <button class="notification-close" @click="showDiscomfortNotification = false">
        <i class="fas fa-times"></i>
      </button>
    </div>
    
    <!-- Gesture detected notification -->
    <div class="notification gesture-notification" v-if="showGestureNotification">
      <i class="fas fa-sign-language"></i>
      <span>{{ currentGestureText }}</span>
      <button class="notification-close" @click="showGestureNotification = false">
        <i class="fas fa-times"></i>
      </button>
    </div>
  </div>
</template>

<script>
import { mapGetters } from 'vuex';
import FaceProcessingVisibilityService from '../services/FaceProcessingVisibilityService';
import UnifiedCommunicationService from '../services/UnifiedCommunicationService';

export default {
  name: 'GestureEnabledMeetingLive',

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
      showGestureInfo: true,
      autoBlurOnDiscomfort: true,
      processingFps: 5,
      currentEmotion: null,
      currentGesture: null,
      currentGestureText: '',
      discomfortDetected: false,
      showDiscomfortNotification: false,
      showGestureNotification: false,
      discomfortNotificationTimeout: null,
      gestureNotificationTimeout: null,
      detectionMode: 'face', // 'face', 'audio', or 'both'
      isGestureMode: false,
      shouldShowFaceProcessing: false,
      shouldShowGestureRecognition: false,
      isDoctor: false
    };
  },

  computed: {
    ...mapGetters('config', ['configs']),
    ...mapGetters('user', ['user']),

    meetingConfig() {
      return this.configs.meeting || {};
    }
  },

  mounted() {
    // Check if user is a doctor
    this.checkUserRole();
    
    // Initialize from config
    this.initializeFromConfig();
    
    // Check if we're in a meeting
    this.shouldShowFaceProcessing = FaceProcessingVisibilityService.isInMeeting();
    this.shouldShowGestureRecognition = this.shouldShowFaceProcessing;
    
    // Listen for meeting status changes
    FaceProcessingVisibilityService.onMeetingStatusChanged(isInMeeting => {
      this.shouldShowFaceProcessing = isInMeeting;
      this.shouldShowGestureRecognition = isInMeeting;
    });
    
    // Initialize unified communication service
    UnifiedCommunicationService.initialize().then(() => {
      console.log('Unified Communication Service initialized in meeting component');
    });
  },

  beforeDestroy() {
    // Clear any pending timeouts
    if (this.discomfortNotificationTimeout) {
      clearTimeout(this.discomfortNotificationTimeout);
    }
    
    if (this.gestureNotificationTimeout) {
      clearTimeout(this.gestureNotificationTimeout);
    }
  },

  methods: {
    checkUserRole() {
      // Check if user has doctor role
      if (this.$store.getters['user/hasRole']) {
        this.isDoctor = this.$store.getters['user/hasRole']('doctor');
      }
    },
    
    initializeFromConfig() {
      // Initialize from config
      this.isEnabled = this.meetingConfig.enable_emotion_detection || 
                      this.meetingConfig.enable_face_blur || 
                      this.meetingConfig.enable_gesture_recognition || 
                      false;
      
      this.showControls = true;
      this.showCanvas = this.meetingConfig.enable_face_blur || false;
      this.showEmotionInfo = this.meetingConfig.show_emotion_info !== undefined
        ? this.meetingConfig.show_emotion_info
        : true;
      this.showGestureInfo = this.meetingConfig.show_gesture_info !== undefined
        ? this.meetingConfig.show_gesture_info
        : true;
      this.autoBlurOnDiscomfort = this.meetingConfig.auto_blur_on_discomfort !== undefined
        ? this.meetingConfig.auto_blur_on_discomfort
        : true;
      this.processingFps = this.meetingConfig.processing_fps || 5;
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

    onGestureDetected(gesture) {
      this.currentGesture = gesture;
      this.currentGestureText = gesture.text;
      this.$emit('gesture-detected', gesture);

      // Show notification for non-doctors (doctors see the interpreter panel)
      if (!this.isDoctor) {
        this.showGestureNotification = true;

        // Clear previous timeout
        if (this.gestureNotificationTimeout) {
          clearTimeout(this.gestureNotificationTimeout);
        }

        // Hide notification after 3 seconds
        this.gestureNotificationTimeout = setTimeout(() => {
          this.showGestureNotification = false;
        }, 3000);
      }
    },

    onBlurToggled(enabled) {
      this.$emit('blur-toggled', enabled);
    },

    onDetectionModeChanged(mode) {
      this.detectionMode = mode;
      this.$emit('detection-mode-changed', mode);
    },

    onBlurIntensityChanged(intensity) {
      this.$emit('blur-intensity-changed', intensity);
    },
    
    onGestureModeChanged(enabled) {
      this.isGestureMode = enabled;
      this.$emit('gesture-mode-changed', enabled);
      
      // Update communication mode
      const newMode = enabled ? 'gesture' : 'standard';
      UnifiedCommunicationService.setCommunicationMode(newMode);
    }
  }
};
</script>

<style scoped>
.gesture-enabled-meeting-live {
  position: relative;
  width: 100%;
  height: 100%;
}

.notification {
  position: fixed;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  background: rgba(255, 255, 255, 0.9);
  backdrop-filter: blur(10px);
  border-radius: 10px;
  padding: 12px 20px;
  display: flex;
  align-items: center;
  gap: 10px;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
  z-index: 1000;
  animation: notification-slide-up 0.3s ease forwards;
}

.discomfort-notification {
  color: #f44336;
}

.gesture-notification {
  color: #ff5c8a;
}

.notification i {
  font-size: 18px;
}

.notification-close {
  background: none;
  border: none;
  color: #666;
  cursor: pointer;
  margin-left: 10px;
  padding: 5px;
  font-size: 14px;
}

.notification-close:hover {
  color: #333;
}

@keyframes notification-slide-up {
  from {
    transform: translate(-50%, 20px);
    opacity: 0;
  }
  to {
    transform: translate(-50%, 0);
    opacity: 1;
  }
}
</style>
