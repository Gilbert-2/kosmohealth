import Vue from 'vue';
import App from './App.vue';
import router from './router';
import store from './store';

// Import Components
import EmotionMonitor from './components/EmotionMonitor.vue';
import EmotionMonitorEnhanced from './components/EmotionMonitorEnhanced.vue';
import EnhancedFaceProcessingWrapper from './components/EnhancedFaceProcessingWrapper.vue';
import EnhancedMeetingLiveWithFaceProcessing from './components/EnhancedMeetingLiveWithFaceProcessing.vue';
import GestureEnabledMeetingLive from './components/GestureEnabledMeetingLive.vue';
import GestureInterpreter from './components/GestureInterpreter.vue';
import GestureRecognitionWrapper from './components/GestureRecognitionWrapper.vue';
import UnifiedPlusMenu from './components/UnifiedPlusMenu.vue';
import Kosmobot from './components/Kosmobot.vue';
import PeriodCalculator from './components/PeriodCalculator.vue';

Vue.config.productionTip = false;

// Register Components
Vue.component('emotion-monitor', EmotionMonitorEnhanced); // Use enhanced version by default
Vue.component('emotion-monitor-legacy', EmotionMonitor); // Keep legacy version available
Vue.component('enhanced-face-processing-wrapper', EnhancedFaceProcessingWrapper); // New component with audio support
Vue.component('enhanced-meeting-live', EnhancedMeetingLiveWithFaceProcessing); // New meeting component with audio support
Vue.component('gesture-enabled-meeting-live', GestureEnabledMeetingLive); // Meeting component with gesture support
Vue.component('gesture-interpreter', GestureInterpreter); // Gesture interpretation component
Vue.component('gesture-recognition-wrapper', GestureRecognitionWrapper); // Gesture recognition wrapper
Vue.component('unified-plus-menu', UnifiedPlusMenu);
Vue.component('kosmobot', Kosmobot);
Vue.component('period-calculator', PeriodCalculator);

// Load fixes for face processing and gesture recognition
if (process.env.NODE_ENV === 'production') {
  // In production, load the auto-loader scripts
  const fixesScript = document.createElement('script');
  fixesScript.src = '/js/auto-load-fixes.js';
  document.body.appendChild(fixesScript);

  // Load modern livefeed enhancements
  const livefeedScript = document.createElement('script');
  livefeedScript.src = '/js/load-livefeed-enhancements.js';
  document.body.appendChild(livefeedScript);
}

new Vue({
  router,
  store,
  render: h => h(App)
}).$mount('#app');