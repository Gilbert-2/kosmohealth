<template>
  <div class="enhanced-meeting-live-with-face-processing">
    <!-- Slot for the original meeting content -->
    <slot></slot>

    <!-- Enhanced face processing wrapper that supports both face and audio emotion detection -->
    <enhanced-face-processing-wrapper
      v-if="shouldShowFaceProcessing && isEnabled && videoElementId"
      :videoElementId="videoElementId"
      :showControls="showControls"
      :showCanvas="showCanvas"
      :showEmotionInfo="showEmotionInfo"
      :autoBlurOnDiscomfort="autoBlurOnDiscomfort"
      :processingFps="processingFps"
      :meetingData="currentMeeting"
      :userData="currentUser"
      @emotion-detected="onEmotionDetected"
      @discomfort-detected="onDiscomfortDetected"
      @blur-toggled="onBlurToggled"
      @detection-mode-changed="onDetectionModeChanged"
      @blur-intensity-changed="onBlurIntensityChanged"
    />

    <!-- Discomfort notification -->
    <div class="notification discomfort-notification" v-if="showDiscomfortNotification">
      <i class="fas fa-exclamation-triangle"></i>
      <span>Discomfort detected. Face blur has been activated.</span>
      <button class="notification-close" @click="showDiscomfortNotification = false">
        <i class="fas fa-times"></i>
      </button>
    </div>
  </div>
</template>

<script>
import { mapGetters } from 'vuex';
import FaceProcessingVisibilityService from '../services/FaceProcessingVisibilityService';
import EmotionDetectionService from '../services/EmotionDetectionService';
import MeetingPermissionService from '../services/MeetingPermissionService';

export default {
  name: 'EnhancedMeetingLiveWithFaceProcessing',

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
      detectionMode: 'face', // 'face', 'audio', or 'both'
      shouldShowFaceProcessing: false,
      currentMeeting: null,
      currentUser: null
    };
  },

  computed: {
    ...mapGetters('config', ['configs']),
    ...mapGetters('user', ['user']),
    ...mapGetters('meeting', ['meeting']),

    meetingConfig() {
      return this.configs.meeting || {};
    }
  },

  mounted() {
    // Initialize from config
    this.initializeFromConfig();

    // Check if we're in a meeting
    this.shouldShowFaceProcessing = FaceProcessingVisibilityService.isInMeeting();

    // Listen for meeting status changes
    FaceProcessingVisibilityService.onMeetingStatusChanged(isInMeeting => {
      this.shouldShowFaceProcessing = isInMeeting;
    });

    // Get current meeting and user data
    this.getCurrentMeetingData();

    // Initialize emotion detection service
    EmotionDetectionService.initialize().then(() => {
      console.log('Emotion detection service initialized in meeting component');
    });
  },

  beforeDestroy() {
    // Clear any pending timeouts
    if (this.discomfortNotificationTimeout) {
      clearTimeout(this.discomfortNotificationTimeout);
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

    onDetectionModeChanged(mode) {
      this.detectionMode = mode;
      this.$emit('detection-mode-changed', mode);
    },

    onBlurIntensityChanged(intensity) {
      this.$emit('blur-intensity-changed', intensity);
    },

    getCurrentMeetingData() {
      // Get current meeting data from store
      if (this.meeting) {
        this.currentMeeting = this.meeting;

        // Check if the current user is the meeting creator
        if (this.currentMeeting) {
          // Set is_host property based on meeting data
          // The creator's user ID should match the host_id in the meeting data
          if (this.user && this.user.id) {
            this.currentMeeting.is_host = this.currentMeeting.host_id === this.user.id;
          }

          console.log('Meeting data loaded:', {
            meetingId: this.currentMeeting.uuid,
            isHost: this.currentMeeting.is_host
          });
        }
      }

      // Get current user data from store
      if (this.user) {
        this.currentUser = this.user;
        console.log('User data loaded:', {
          userId: this.currentUser.id,
          name: this.currentUser.name
        });
      }

      // Initialize permission service with meeting data
      if (this.currentMeeting && this.currentUser) {
        MeetingPermissionService.initialize(this.currentMeeting, this.currentUser);
        EmotionDetectionService.setMeetingData(this.currentMeeting, this.currentUser);
      }
    }
  }
};
</script>

<style scoped>
.enhanced-meeting-live-with-face-processing {
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

.discomfort-notification i {
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
