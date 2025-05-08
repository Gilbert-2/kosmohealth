<template>
  <div class="meeting-live-container enhanced">
    <!-- Wrap the original live.vue component with our enhanced face processing -->
    <enhanced-meeting-live
      videoElementId="localVideo"
      @emotion-detected="onEmotionDetected"
      @discomfort-detected="onDiscomfortDetected"
      @blur-toggled="onBlurToggled"
      @detection-mode-changed="onDetectionModeChanged"
      @blur-intensity-changed="onBlurIntensityChanged"
    >
      <!-- Original live.vue content will be rendered here -->
      <router-view></router-view>
    </enhanced-meeting-live>
  </div>
</template>

<script>
export default {
  name: 'EnhancedMeetingLive',
  
  data() {
    return {
      currentEmotion: null,
      discomfortDetected: false,
      detectionMode: 'face'
    };
  },
  
  methods: {
    onEmotionDetected(emotion) {
      this.currentEmotion = emotion;
      console.log('Emotion detected:', emotion);
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
    
    onBlurIntensityChanged(intensity) {
      console.log('Blur intensity changed:', intensity);
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
