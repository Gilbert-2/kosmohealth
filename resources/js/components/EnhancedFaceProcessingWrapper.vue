<template>
  <div class="enhanced-face-processing-wrapper" v-if="shouldShowFaceProcessing">
    <div class="enhanced-face-processing-controls" v-if="showControls">
      <!-- Emotion detection mode toggle - only for meeting creators -->
      <div class="detection-mode-toggle" v-if="canSeeEmotions">
        <button
          class="detection-mode-btn"
          :class="{ active: detectionMode === 'face' }"
          @click="setDetectionMode('face')"
          title="Face detection"
        >
          <i class="fas fa-video"></i>
        </button>
        <button
          class="detection-mode-btn"
          :class="{ active: detectionMode === 'audio' }"
          @click="setDetectionMode('audio')"
          title="Audio detection"
        >
          <i class="fas fa-microphone"></i>
        </button>
        <button
          class="detection-mode-btn"
          :class="{ active: detectionMode === 'both' }"
          @click="setDetectionMode('both')"
          title="Combined detection"
        >
          <i class="fas fa-video"></i>+<i class="fas fa-microphone"></i>
        </button>
      </div>

      <!-- Blur controls - available to all users -->
      <div class="blur-controls">
        <button
          class="blur-toggle-btn"
          :class="{ active: blurEnabled }"
          @click="toggleBlur"
          title="Toggle face blur"
        >
          <i class="fas fa-eye-slash"></i>
        </button>

        <input
          v-if="blurEnabled"
          type="range"
          min="1"
          max="20"
          v-model.number="blurIntensity"
          @input="onBlurIntensityInput"
          class="blur-intensity-slider"
        />
      </div>

      <!-- Security indicator for non-creators -->
      <div class="security-indicator" v-if="!isMeetingCreator()">
        <i class="fas fa-shield-alt"></i>
        <span class="security-text">Emotion Data Hidden</span>
        <span class="security-subtext">Only meeting creators can see emotions</span>
      </div>

      <!-- Creator indicator -->
      <div class="creator-indicator" v-if="isMeetingCreator()">
        <i class="fas fa-crown"></i>
        <span class="creator-text">Meeting Creator</span>
        <span class="creator-subtext">You can see emotion data</span>
      </div>
    </div>

    <canvas
      ref="outputCanvas"
      class="face-processing-canvas"
      v-show="showCanvas && detectionMode !== 'audio'"
    ></canvas>

    <!-- Emotion info - only visible to meeting creators -->
    <div
      v-if="showEmotionInfo && currentEmotion && canSeeEmotions"
      class="emotion-info"
      :class="[`emotion-${currentEmotion.dominant}`, `source-${currentEmotion.source || 'face'}`]"
    >
      <span class="emotion-name">{{ currentEmotion.dominant }}</span>
      <span class="emotion-score">{{ Math.round(currentEmotion.score * 100) }}%</span>
      <span class="emotion-source">
        <i :class="sourceIcon"></i>
      </span>
    </div>

    <!-- Privacy indicator for non-creators -->
    <div
      v-if="!canSeeEmotions"
      class="privacy-indicator"
      :class="{ 'privacy-warning': !blurEnabled }"
    >
      <i :class="blurEnabled ? 'fas fa-user-shield' : 'fas fa-exclamation-triangle'"></i>
      <span>{{ blurEnabled ? 'Privacy Protected' : 'Face Visible - Blur Disabled' }}</span>
    </div>
  </div>
</template>

<script>
import EmotionDetectionService from '../services/EmotionDetectionService';
import MeetingPermissionService from '../services/MeetingPermissionService';

export default {
  name: 'EnhancedFaceProcessingWrapper',

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
    },
    meetingData: {
      type: Object,
      default: null
    },
    userData: {
      type: Object,
      default: null
    }
  },

  data() {
    return {
      videoElement: null,
      currentEmotion: null,
      blurEnabled: false,
      blurIntensity: 10,
      detectionMode: 'face', // 'face', 'audio', or 'both'
      isProcessing: false
    };
  },

  computed: {
    shouldShowFaceProcessing() {
      return this.videoElement !== null;
    },

    sourceIcon() {
      if (!this.currentEmotion || !this.currentEmotion.source) return 'fas fa-video';

      switch (this.currentEmotion.source) {
        case 'face':
          return 'fas fa-video';
        case 'audio':
          return 'fas fa-microphone';
        case 'combined':
          return 'fas fa-layer-group';
        default:
          return 'fas fa-video';
      }
    },

    // Check if user can see emotions
    canSeeEmotions() {
      return MeetingPermissionService.canSeeEmotions();
    },

    // Check if user can blur face
    canBlurFace() {
      return MeetingPermissionService.canBlurFace();
    },

    // Check if user is meeting creator
    isMeetingCreator() {
      return MeetingPermissionService.isMeetingCreator();
    },

    // Determine which controls to show based on permissions
    showEmotionControls() {
      return this.showControls && this.canSeeEmotions;
    },

    // Always show blur controls for all users
    showBlurControls() {
      return this.showControls && this.canBlurFace;
    }
  },

  mounted() {
    // Find the video element
    this.findVideoElement();

    // Initialize meeting permissions if meeting data is provided
    if (this.meetingData) {
      console.log('Initializing with meeting data:', this.meetingData);
      MeetingPermissionService.initialize(this.meetingData, this.userData);
      EmotionDetectionService.setMeetingData(this.meetingData, this.userData);
    } else {
      console.warn('No meeting data provided, using default permissions');
      // Default to non-creator for security
      MeetingPermissionService.setIsCreator(false);
    }

    // Initialize the emotion detection service
    EmotionDetectionService.initialize().then(() => {
      // Set up callbacks
      EmotionDetectionService.onEmotionDetected(this.handleEmotionDetected);
      EmotionDetectionService.onDiscomfortDetected(this.handleDiscomfortDetected);

      // Set initial detection mode
      this.setDetectionMode(this.detectionMode);

      // Blur is optional for all users - they can choose to enable/disable it
      // Default is disabled, but they can enable it if they want privacy
      this.blurEnabled = false;
      EmotionDetectionService.setBlurEnabled(false);

      console.log('Face processing initialized with permissions:', {
        canSeeEmotions: this.canSeeEmotions,
        isMeetingCreator: this.isMeetingCreator(),
        blurEnabled: this.blurEnabled
      });
    });
  },

  beforeDestroy() {
    // Stop processing when component is destroyed
    EmotionDetectionService.stopDetection();

    // Remove event listeners
    if (this.videoElement) {
      this.videoElement.removeEventListener('resize', this.handleVideoResize);
      this.videoElement.removeEventListener('loadedmetadata', this.handleVideoResize);
    }

    window.removeEventListener('resize', this.handleResize);
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
      } else {
        // Start detection when video element is found
        this.startDetection();

        // Add resize event listeners
        this.videoElement.addEventListener('resize', this.handleVideoResize);
        this.videoElement.addEventListener('loadedmetadata', this.handleVideoResize);
        window.addEventListener('resize', this.handleResize);
      }
    },

    startDetection() {
      if (!this.videoElement) return;

      const canvas = this.$refs.outputCanvas;
      if (canvas) {
        // Set canvas dimensions to match video
        canvas.width = this.videoElement.videoWidth || 640;
        canvas.height = this.videoElement.videoHeight || 480;
      }

      // Start emotion detection
      EmotionDetectionService.startDetection(
        this.videoElement,
        canvas,
        this.processingFps
      );

      this.isProcessing = true;
    },

    setDetectionMode(mode) {
      this.detectionMode = mode;
      EmotionDetectionService.setDetectionMode(mode);

      // Emit event
      this.$emit('detection-mode-changed', mode);
    },

    toggleBlur() {
      this.blurEnabled = !this.blurEnabled;
      EmotionDetectionService.setBlurEnabled(this.blurEnabled);

      // Emit event
      this.$emit('blur-toggled', this.blurEnabled);
    },

    onBlurIntensityInput() {
      EmotionDetectionService.setBlurIntensity(this.blurIntensity);

      // Emit event
      this.$emit('blur-intensity-changed', this.blurIntensity);
    },

    handleEmotionDetected(emotion) {
      // Only process emotions if user is the meeting creator
      if (!this.canSeeEmotions) {
        return;
      }

      this.currentEmotion = emotion;

      // Emit event only for meeting creators
      this.$emit('emotion-detected', emotion);

      // Log emotion detection (only visible to meeting creators)
      console.log('Emotion detected:', {
        emotion: emotion.dominant,
        score: emotion.score,
        source: emotion.source
      });
    },

    handleDiscomfortDetected(emotion, score, source) {
      // Emit event
      this.$emit('discomfort-detected', { emotion, score, source });

      // Auto-blur if enabled and user has chosen to allow auto-blur
      if (this.autoBlurOnDiscomfort && !this.blurEnabled) {
        // Show a notification to the user that discomfort was detected
        this.showDiscomfortNotification(emotion);

        // Only auto-enable blur for meeting creators
        // For participants, just show the notification but respect their choice
        if (this.isMeetingCreator()) {
          this.blurEnabled = true;
          EmotionDetectionService.setBlurEnabled(true);
          this.$emit('blur-toggled', true);
        }
      }
    },

    // Show a notification when discomfort is detected
    showDiscomfortNotification(emotion) {
      // Create notification element
      const notification = document.createElement('div');
      notification.className = 'discomfort-notification';
      notification.innerHTML = `
        <i class="fas fa-exclamation-circle"></i>
        <div class="notification-content">
          <div class="notification-title">Discomfort Detected</div>
          <div class="notification-text">Consider enabling blur for privacy</div>
        </div>
        <button class="notification-close"><i class="fas fa-times"></i></button>
      `;

      // Add to document
      document.body.appendChild(notification);

      // Add event listener to close button
      const closeButton = notification.querySelector('.notification-close');
      closeButton.addEventListener('click', () => {
        notification.classList.add('notification-hiding');
        setTimeout(() => {
          document.body.removeChild(notification);
        }, 300);
      });

      // Auto-remove after 5 seconds
      setTimeout(() => {
        if (document.body.contains(notification)) {
          notification.classList.add('notification-hiding');
          setTimeout(() => {
            if (document.body.contains(notification)) {
              document.body.removeChild(notification);
            }
          }, 300);
        }
      }, 5000);

      // Animate in
      setTimeout(() => {
        notification.classList.add('notification-visible');
      }, 10);
    },

    handleVideoResize() {
      if (!this.videoElement || !this.$refs.outputCanvas) return;

      // Update canvas dimensions
      this.$refs.outputCanvas.width = this.videoElement.videoWidth || 640;
      this.$refs.outputCanvas.height = this.videoElement.videoHeight || 480;
    },

    handleResize() {
      this.handleVideoResize();
    }
  }
};
</script>

<style scoped>
.enhanced-face-processing-wrapper {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 10;
}

.enhanced-face-processing-controls {
  position: absolute;
  top: 20px;
  right: 20px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  pointer-events: auto;
  z-index: 20;
}

.detection-mode-toggle {
  display: flex;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  padding: 3px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.detection-mode-btn {
  background: none;
  border: none;
  border-radius: 17px;
  padding: 8px 12px;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
  color: rgba(255, 255, 255, 0.7);
}

.detection-mode-btn.active {
  background: rgba(255, 255, 255, 0.9);
  color: #ff5c8a;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.blur-controls {
  display: flex;
  align-items: center;
  gap: 10px;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  padding: 5px 10px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.blur-toggle-btn {
  background: none;
  border: none;
  border-radius: 50%;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  color: rgba(255, 255, 255, 0.7);
}

.blur-toggle-btn.active {
  background: rgba(255, 255, 255, 0.9);
  color: #ff5c8a;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.blur-intensity-slider {
  width: 100px;
  height: 6px;
  -webkit-appearance: none;
  appearance: none;
  background: rgba(255, 255, 255, 0.3);
  border-radius: 3px;
  outline: none;
}

.blur-intensity-slider::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: #ffffff;
  cursor: pointer;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
}

.face-processing-canvas {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
}

.emotion-info {
  position: absolute;
  bottom: 20px;
  left: 20px;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  padding: 8px 16px;
  display: flex;
  align-items: center;
  gap: 8px;
  color: white;
  font-weight: 600;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
  pointer-events: auto;
}

.emotion-name {
  text-transform: capitalize;
}

.emotion-score {
  opacity: 0.8;
  font-size: 0.9em;
}

.emotion-source {
  margin-left: 5px;
  opacity: 0.7;
  font-size: 0.8em;
}

/* Emotion colors */
.emotion-happy {
  background: linear-gradient(135deg, rgba(76, 175, 80, 0.7), rgba(76, 175, 80, 0.3));
}

.emotion-sad {
  background: linear-gradient(135deg, rgba(33, 150, 243, 0.7), rgba(33, 150, 243, 0.3));
}

.emotion-angry {
  background: linear-gradient(135deg, rgba(244, 67, 54, 0.7), rgba(244, 67, 54, 0.3));
}

.emotion-fearful {
  background: linear-gradient(135deg, rgba(156, 39, 176, 0.7), rgba(156, 39, 176, 0.3));
}

.emotion-disgusted {
  background: linear-gradient(135deg, rgba(121, 85, 72, 0.7), rgba(121, 85, 72, 0.3));
}

.emotion-surprised {
  background: linear-gradient(135deg, rgba(255, 152, 0, 0.7), rgba(255, 152, 0, 0.3));
}

.emotion-neutral {
  background: linear-gradient(135deg, rgba(158, 158, 158, 0.7), rgba(158, 158, 158, 0.3));
}

/* Source indicators */
.source-face::after {
  content: '';
  display: inline-block;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: #4361ee;
  margin-left: 5px;
}

.source-audio::after {
  content: '';
  display: inline-block;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: #ff6b6b;
  margin-left: 5px;
}

.source-combined::after {
  content: '';
  display: inline-block;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background-color: #7950f2;
  margin-left: 5px;
}

/* Security indicator */
.security-indicator {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(76, 175, 80, 0.2);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  padding: 8px 12px;
  margin-top: 10px;
  color: white;
  font-weight: 500;
  font-size: 14px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.security-indicator i {
  color: #4CAF50;
  font-size: 16px;
}

.security-text {
  font-weight: 600;
}

.security-subtext {
  font-size: 12px;
  opacity: 0.8;
  margin-left: 4px;
}

/* Creator indicator */
.creator-indicator {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(255, 193, 7, 0.2);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  padding: 8px 12px;
  margin-top: 10px;
  color: white;
  font-weight: 500;
  font-size: 14px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.creator-indicator i {
  color: #FFC107;
  font-size: 16px;
}

.creator-text {
  font-weight: 600;
}

.creator-subtext {
  font-size: 12px;
  opacity: 0.8;
  margin-left: 4px;
}

/* Privacy indicator */
.privacy-indicator {
  position: absolute;
  bottom: 20px;
  left: 20px;
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(76, 175, 80, 0.2);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  padding: 8px 16px;
  color: white;
  font-weight: 600;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.privacy-indicator i {
  color: #4CAF50;
}

/* Privacy warning styles */
.privacy-indicator.privacy-warning {
  background: rgba(255, 87, 34, 0.2);
  animation: pulse 2s infinite;
}

.privacy-indicator.privacy-warning i {
  color: #FF5722;
}

@keyframes pulse {
  0% {
    box-shadow: 0 0 0 0 rgba(255, 87, 34, 0.4);
  }
  70% {
    box-shadow: 0 0 0 10px rgba(255, 87, 34, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(255, 87, 34, 0);
  }
}

/* Discomfort notification */
.discomfort-notification {
  position: fixed;
  bottom: 30px;
  right: 30px;
  background: rgba(255, 87, 34, 0.9);
  backdrop-filter: blur(10px);
  border-radius: 8px;
  padding: 15px;
  display: flex;
  align-items: center;
  gap: 12px;
  color: white;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
  z-index: 9999;
  transform: translateY(100px);
  opacity: 0;
  transition: transform 0.3s ease, opacity 0.3s ease;
  max-width: 350px;
}

.discomfort-notification.notification-visible {
  transform: translateY(0);
  opacity: 1;
}

.discomfort-notification.notification-hiding {
  transform: translateY(100px);
  opacity: 0;
}

.discomfort-notification i {
  font-size: 24px;
}

.notification-content {
  flex: 1;
}

.notification-title {
  font-weight: 600;
  font-size: 16px;
  margin-bottom: 4px;
}

.notification-text {
  font-size: 14px;
  opacity: 0.9;
}

.notification-close {
  background: none;
  border: none;
  color: white;
  opacity: 0.7;
  cursor: pointer;
  padding: 5px;
  transition: opacity 0.2s ease;
}

.notification-close:hover {
  opacity: 1;
}
</style>
