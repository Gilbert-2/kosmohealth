<template>
  <div class="meeting-live-container flo-design">
    <!-- Wrap the original live.vue component with our Flo-designed face processing -->
    <flo-design-meeting-live-with-face-processing
      videoElementId="localVideo"
      @emotion-detected="onEmotionDetected"
      @discomfort-detected="onDiscomfortDetected"
      @blur-toggled="onBlurToggled"
      @full-page-blur-toggled="onFullPageBlurToggled"
      @glassmorphism-toggled="onGlassmorphismToggled"
      @blur-intensity-changed="onBlurIntensityChanged"
      @face-detection-status="onFaceDetectionStatus"
      @emotion-detection-toggled="onEmotionDetectionToggled"
    >
      <!-- Original live.vue content will be rendered here -->
      <router-view></router-view>
    </flo-design-meeting-live-with-face-processing>
  </div>
</template>

<script>
import { mapGetters } from 'vuex';
import FloDesignMeetingLiveWithFaceProcessing from '../../../components/FloDesignMeetingLiveWithFaceProcessing.vue';

export default {
  name: 'MeetingLiveWithFloDesign',
  
  components: {
    FloDesignMeetingLiveWithFaceProcessing
  },
  
  data() {
    return {
      currentEmotion: null,
      discomfortDetected: false
    };
  },
  
  computed: {
    ...mapGetters('config', ['configs']),
    
    meetingConfig() {
      return this.configs.meeting || {};
    }
  },
  
  methods: {
    onEmotionDetected(emotion) {
      this.currentEmotion = emotion;
      // You can add additional logic here if needed
    },
    
    onDiscomfortDetected(data) {
      this.discomfortDetected = true;
      // You can add additional logic here if needed
    },
    
    onBlurToggled(enabled) {
      // You can add additional logic here if needed
    },
    
    onFullPageBlurToggled(enabled) {
      // You can add additional logic here if needed
    },
    
    onGlassmorphismToggled(enabled) {
      // You can add additional logic here if needed
    },
    
    onBlurIntensityChanged(intensity) {
      // You can add additional logic here if needed
    },
    
    onFaceDetectionStatus(status) {
      // You can add additional logic here if needed
    },
    
    onEmotionDetectionToggled(enabled) {
      // You can add additional logic here if needed
    }
  }
};
</script>

<style scoped>
.meeting-live-container {
  position: relative;
  width: 100%;
  height: 100%;
}
</style>
