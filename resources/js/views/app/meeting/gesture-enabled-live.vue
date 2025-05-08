<template>
  <div class="meeting-live-container gesture-enabled">
    <!-- Wrap the original live.vue component with our gesture-enabled meeting component -->
    <gesture-enabled-meeting-live
      videoElementId="localVideo"
      @emotion-detected="onEmotionDetected"
      @gesture-detected="onGestureDetected"
      @discomfort-detected="onDiscomfortDetected"
      @blur-toggled="onBlurToggled"
      @detection-mode-changed="onDetectionModeChanged"
      @gesture-mode-changed="onGestureModeChanged"
    >
      <!-- Original live.vue content will be rendered here -->
      <router-view></router-view>
    </gesture-enabled-meeting-live>
  </div>
</template>

<script>
export default {
  name: 'GestureEnabledMeetingLiveView',
  
  data() {
    return {
      currentEmotion: null,
      currentGesture: null,
      discomfortDetected: false,
      detectionMode: 'face',
      isGestureMode: false
    };
  },
  
  methods: {
    onEmotionDetected(emotion) {
      this.currentEmotion = emotion;
      console.log('Emotion detected:', emotion);
    },
    
    onGestureDetected(gesture) {
      this.currentGesture = gesture;
      console.log('Gesture detected:', gesture);
    },
    
    onDiscomfortDetected(data) {
      this.discomfortDetected = true;
      console.log('Discomfort detected:', data);
    },
    
    onBlurToggled(enabled) {
      console.log('Blur toggled:', enabled);
    },
    
    onDetectionModeChanged(mode) {
      this.detectionMode = mode;
      console.log('Detection mode changed:', mode);
    },
    
    onGestureModeChanged(enabled) {
      this.isGestureMode = enabled;
      console.log('Gesture mode changed:', enabled);
    }
  }
};
</script>

<style scoped>
.meeting-live-container {
  position: relative;
  width: 100%;
  height: 100vh;
  overflow: hidden;
}
</style>
