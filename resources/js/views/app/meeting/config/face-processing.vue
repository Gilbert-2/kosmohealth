<template>
  <div class="face-processing-config">
    <base-container boxed>
      <template slot="heading">
        <h5 class="mb-0">{{ $t('meeting.config.face_processing') }}</h5>
      </template>

      <form @submit.prevent="submit" class="p-3 p-lg-4">
        <div class="row">
          <div class="col-12 col-md-6">
            <div class="form-group">
              <switch-wrapper
                v-model="formData.enableEmotionDetection"
                :label="$t('meeting.config.enable_emotion_detection')"
                :error="formErrors.enableEmotionDetection"
              ></switch-wrapper>
              <div class="form-text text-muted">
                {{ $t('meeting.config.enable_emotion_detection_help') }}
              </div>
            </div>
          </div>

          <div class="col-12 col-md-6">
            <div class="form-group">
              <switch-wrapper
                v-model="formData.enableFaceBlur"
                :label="$t('meeting.config.enable_face_blur')"
                :error="formErrors.enableFaceBlur"
              ></switch-wrapper>
              <div class="form-text text-muted">
                {{ $t('meeting.config.enable_face_blur_help') }}
              </div>
            </div>
          </div>

          <div class="col-12 col-md-6">
            <div class="form-group">
              <switch-wrapper
                v-model="formData.autoBlurOnDiscomfort"
                :label="$t('meeting.config.auto_blur_on_discomfort')"
                :error="formErrors.autoBlurOnDiscomfort"
              ></switch-wrapper>
              <div class="form-text text-muted">
                {{ $t('meeting.config.auto_blur_on_discomfort_help') }}
              </div>
            </div>
          </div>

          <div class="col-12 col-md-6">
            <div class="form-group">
              <switch-wrapper
                v-model="formData.showEmotionInfo"
                :label="$t('meeting.config.show_emotion_info')"
                :error="formErrors.showEmotionInfo"
              ></switch-wrapper>
              <div class="form-text text-muted">
                {{ $t('meeting.config.show_emotion_info_help') }}
              </div>
            </div>
          </div>

          <div class="col-12 col-md-6">
            <div class="form-group">
              <switch-wrapper
                v-model="formData.enableFullPageBlur"
                :label="$t('meeting.config.enable_full_page_blur')"
                :error="formErrors.enableFullPageBlur"
              ></switch-wrapper>
              <div class="form-text text-muted">
                {{ $t('meeting.config.enable_full_page_blur_help') }}
              </div>
            </div>
          </div>

          <div class="col-12 col-md-6">
            <div class="form-group">
              <switch-wrapper
                v-model="formData.enableGlassmorphism"
                :label="$t('meeting.config.enable_glassmorphism')"
                :error="formErrors.enableGlassmorphism"
              ></switch-wrapper>
              <div class="form-text text-muted">
                {{ $t('meeting.config.enable_glassmorphism_help') }}
              </div>
            </div>
          </div>

          <div class="col-12 col-md-6">
            <div class="form-group">
              <base-input
                v-model="formData.blurIntensity"
                type="number"
                :label="$t('meeting.config.blur_intensity')"
                :error="formErrors.blurIntensity"
                min="1"
                max="30"
              ></base-input>
              <div class="form-text text-muted">
                {{ $t('meeting.config.blur_intensity_help') }}
              </div>
            </div>
          </div>

          <div class="col-12 col-md-6">
            <div class="form-group">
              <base-input
                v-model="formData.processingFps"
                type="number"
                :label="$t('meeting.config.processing_fps')"
                :error="formErrors.processingFps"
                min="1"
                max="10"
              ></base-input>
              <div class="form-text text-muted">
                {{ $t('meeting.config.processing_fps_help') }}
              </div>
            </div>
          </div>
        </div>

        <div class="form-footer mt-3">
          <base-button type="submit" design="primary" block :loading="isLoading">
            {{ $t('general.save') }}
          </base-button>
        </div>
      </form>
    </base-container>
  </div>
</template>

<script>
export default {
  name: 'FaceProcessingConfig',

  data() {
    return {
      formData: {
        enableEmotionDetection: false,
        enableFaceBlur: false,
        autoBlurOnDiscomfort: true,
        showEmotionInfo: true,
        enableFullPageBlur: true,
        enableGlassmorphism: true,
        blurIntensity: 15,
        processingFps: 5
      },
      formErrors: {},
      isLoading: false
    };
  },

  mounted() {
    this.getInitialData();
  },

  methods: {
    getInitialData() {
      this.isLoading = true;

      // Get config from API or local storage
      const config = this.$store.getters['config/configs'];

      if (config && config.meeting) {
        this.formData.enableEmotionDetection = config.meeting.enable_emotion_detection || false;
        this.formData.enableFaceBlur = config.meeting.enable_face_blur || false;
        this.formData.autoBlurOnDiscomfort = config.meeting.auto_blur_on_discomfort !== undefined
          ? config.meeting.auto_blur_on_discomfort
          : true;
        this.formData.showEmotionInfo = config.meeting.show_emotion_info !== undefined
          ? config.meeting.show_emotion_info
          : true;
        this.formData.enableFullPageBlur = config.meeting.enable_full_page_blur !== undefined
          ? config.meeting.enable_full_page_blur
          : true;
        this.formData.enableGlassmorphism = config.meeting.enable_glassmorphism !== undefined
          ? config.meeting.enable_glassmorphism
          : true;
        this.formData.blurIntensity = config.meeting.blur_intensity || 15;
        this.formData.processingFps = config.meeting.processing_fps || 5;
      }

      this.isLoading = false;
    },

    submit() {
      this.isLoading = true;
      this.formErrors = {};

      // Validate form
      if (this.formData.processingFps < 1 || this.formData.processingFps > 10) {
        this.formErrors.processingFps = this.$t('meeting.config.processing_fps_range_error');
        this.isLoading = false;
        return;
      }

      if (this.formData.blurIntensity < 1 || this.formData.blurIntensity > 30) {
        this.formErrors.blurIntensity = this.$t('meeting.config.blur_intensity_range_error');
        this.isLoading = false;
        return;
      }

      // Save config to API
      this.$store.dispatch('config/updateMeetingConfig', {
        enable_emotion_detection: this.formData.enableEmotionDetection,
        enable_face_blur: this.formData.enableFaceBlur,
        auto_blur_on_discomfort: this.formData.autoBlurOnDiscomfort,
        show_emotion_info: this.formData.showEmotionInfo,
        enable_full_page_blur: this.formData.enableFullPageBlur,
        enable_glassmorphism: this.formData.enableGlassmorphism,
        blur_intensity: this.formData.blurIntensity,
        processing_fps: this.formData.processingFps
      })
      .then(response => {
        this.$toasted.success(this.$t('config.saved'));
        this.isLoading = false;
      })
      .catch(error => {
        this.isLoading = false;
        this.formErrors = this.getErrors(error);
        this.$toasted.error(this.$t('general.error_occurred'));
      });
    }
  }
};
</script>

<style scoped>
.face-processing-config {
  margin-bottom: 2rem;
}
</style>
