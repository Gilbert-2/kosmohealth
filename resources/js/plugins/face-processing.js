import Vue from 'vue';
import FaceProcessing from '../components/FaceProcessing.vue';
import FaceProcessingWrapper from '../components/FaceProcessingWrapper.vue';
import MeetingLiveWithFaceProcessing from '../components/MeetingLiveWithFaceProcessing.vue';

// Import Flo design components
import FloDesignFaceProcessing from '../components/FloDesignFaceProcessing.vue';
import FloDesignFaceProcessingWrapper from '../components/FloDesignFaceProcessingWrapper.vue';
import FloDesignMeetingLiveWithFaceProcessing from '../components/FloDesignMeetingLiveWithFaceProcessing.vue';

const FaceProcessingPlugin = {
  install(Vue) {
    // Register components globally
    Vue.component('face-processing', FaceProcessing);
    Vue.component('face-processing-wrapper', FaceProcessingWrapper);
    Vue.component('meeting-live-with-face-processing', MeetingLiveWithFaceProcessing);

    // Register Flo design components
    Vue.component('flo-design-face-processing', FloDesignFaceProcessing);
    Vue.component('flo-design-face-processing-wrapper', FloDesignFaceProcessingWrapper);
    Vue.component('flo-design-meeting-live-with-face-processing', FloDesignMeetingLiveWithFaceProcessing);
  }
};

// Auto-install when Vue is found (e.g. in browser via <script> tag)
let GlobalVue = null;
if (typeof window !== 'undefined') {
  GlobalVue = window.Vue;
} else if (typeof global !== 'undefined') {
  GlobalVue = global.Vue;
}
if (GlobalVue) {
  GlobalVue.use(FaceProcessingPlugin);
}

export default FaceProcessingPlugin;

// Export components individually
export {
  FaceProcessing,
  FaceProcessingWrapper,
  MeetingLiveWithFaceProcessing,
  FloDesignFaceProcessing,
  FloDesignFaceProcessingWrapper,
  FloDesignMeetingLiveWithFaceProcessing
};
