import Vue from 'vue';
import FaceProcessing from './FaceProcessing.vue';
import FaceProcessingWrapper from './FaceProcessingWrapper.vue';

// Register components globally
Vue.component('face-processing', FaceProcessing);
Vue.component('face-processing-wrapper', FaceProcessingWrapper);

export {
  FaceProcessing,
  FaceProcessingWrapper
};
