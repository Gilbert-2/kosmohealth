<![CDATA[<template>
  <div class="kyc-verification">
    <div class="kyc-steps">
      <b-steps
        v-model="currentStep"
        :steps="steps"
        :validated="true"
        :variant="stepVariant"
      >
        <!-- Document Upload Step -->
        <b-step
          id="document-upload"
          title="Document Upload"
          :valid="verificationSteps.documentUpload"
        >
          <div class="step-content">
            <h4>Upload Identity Document</h4>
            <p>Please upload a clear photo of your government-issued ID.</p>
            
            <div class="document-upload-area">
              <b-form-file
                v-model="documentFile"
                accept="image/*"
                placeholder="Choose a file or drop it here..."
                drop-placeholder="Drop file here..."
                @change="onDocumentSelected"
              ></b-form-file>
              
              <div v-if="documentFile" class="preview-area">
                <img :src="documentPreview" alt="Document preview" />
              </div>
            </div>

            <b-button
              :disabled="!documentFile || processingDocument"
              @click="uploadDocument"
              variant="primary"
            >
              {{ processingDocument ? 'Uploading...' : 'Upload Document' }}
            </b-button>
          </div>
        </b-step>

        <!-- Liveness Check Step -->
        <b-step
          id="liveness-check"
          title="Liveness Check"
          :valid="verificationSteps.livenessCheck"
        >
          <div class="step-content">
            <h4>Liveness Check</h4>
            <p>Please follow the instructions to complete the liveness verification.</p>

            <div class="video-container">
              <video
                ref="videoElement"
                :width="videoWidth"
                :height="videoHeight"
                autoplay
                muted
              ></video>
              <canvas
                ref="overlayCanvas"
                :width="videoWidth"
                :height="videoHeight"
              ></canvas>
            </div>

            <div class="gesture-instructions" v-if="currentGesture">
              <h5>Please {{ formatGestureInstruction(currentGesture) }}</h5>
              <b-progress
                :value="gestureProgress"
                :max="100"
                :variant="gestureProgressVariant"
                animated
              ></b-progress>
            </div>

            <b-button
              :disabled="!cameraReady || processingLiveness"
              @click="startLivenessCheck"
              variant="primary"
            >
              {{ processingLiveness ? 'Verifying...' : 'Start Liveness Check' }}
            </b-button>
          </div>
        </b-step>

        <!-- Face Match Step -->
        <b-step
          id="face-match"
          title="Face Match"
          :valid="verificationSteps.faceMatch"
        >
          <div class="step-content">
            <h4>Face Matching</h4>
            <p>Take a selfie to verify it matches your ID document.</p>

            <div class="video-container">
              <video
                ref="selfieVideo"
                :width="videoWidth"
                :height="videoHeight"
                autoplay
                muted
              ></video>
              <canvas
                ref="selfieCanvas"
                :width="videoWidth"
                :height="videoHeight"
              ></canvas>
            </div>

            <b-button
              :disabled="!cameraReady || processingSelfie"
              @click="captureSelfie"
              variant="primary"
            >
              {{ processingSelfie ? 'Processing...' : 'Capture Selfie' }}
            </b-button>
          </div>
        </b-step>

        <!-- Final Verification Step -->
        <b-step
          id="verification"
          title="Verification"
          :valid="verificationComplete"
        >
          <div class="step-content">
            <h4>Final Verification</h4>
            <div v-if="verificationComplete">
              <b-alert show variant="success">
                <h5>Verification Successful!</h5>
                <p>Your identity has been verified successfully.</p>
                <p>Verification ID: {{ verificationDetails.verification_id }}</p>
              </b-alert>
            </div>
            <div v-else>
              <p>Please review your information and complete the verification.</p>
              <b-button
                :disabled="!canCompleteVerification || processingVerification"
                @click="completeVerification"
                variant="success"
              >
                {{ processingVerification ? 'Verifying...' : 'Complete Verification' }}
              </b-button>
            </div>
          </div>
        </b-step>
      </b-steps>
    </div>

    <!-- Status Messages -->
    <b-alert
      v-model="showAlert"
      :variant="alertVariant"
      dismissible
      fade
      class="mt-3"
    >
      {{ alertMessage }}
    </b-alert>
  </div>
</template>

<script>
import KycService from '../services/KycService';

export default {
  name: 'KycVerification',
  
  data() {
    return {
      currentStep: 0,
      documentFile: null,
      documentPreview: null,
      videoWidth: 640,
      videoHeight: 480,
      cameraReady: false,
      currentGesture: null,
      gestureProgress: 0,
      verificationSteps: {
        documentUpload: false,
        documentVerification: false,
        livenessCheck: false,
        faceMatch: false
      },
      processingDocument: false,
      processingLiveness: false,
      processingSelfie: false,
      processingVerification: false,
      verificationComplete: false,
      verificationDetails: null,
      showAlert: false,
      alertVariant: 'info',
      alertMessage: '',
      videoStream: null
    };
  },

  computed: {
    stepVariant() {
      return this.verificationComplete ? 'success' : 'primary';
    },
    gestureProgressVariant() {
      return this.gestureProgress >= 100 ? 'success' : 'primary';
    },
    canCompleteVerification() {
      return (
        this.verificationSteps.documentVerification &&
        this.verificationSteps.livenessCheck &&
        this.verificationSteps.faceMatch
      );
    }
  },

  async mounted() {
    try {
      await KycService.initialize();
      KycService.onStatusChange(this.handleStatusChange);
    } catch (error) {
      this.showError('Failed to initialize KYC system');
    }
  },

  beforeDestroy() {
    this.stopVideoStream();
  },

  methods: {
    async onDocumentSelected(file) {
      if (!file) return;
      
      try {
        this.documentPreview = URL.createObjectURL(file);
      } catch (error) {
        this.showError('Error loading document preview');
      }
    },

    async uploadDocument() {
      if (!this.documentFile) return;

      this.processingDocument = true;
      try {
        const result = await KycService.verifyDocument(this.documentFile);
        if (result.status === 'verified') {
          this.verificationSteps.documentUpload = true;
          this.verificationSteps.documentVerification = true;
          this.currentStep++;
        }
      } catch (error) {
        this.showError('Document verification failed');
      } finally {
        this.processingDocument = false;
      }
    },

    async startLivenessCheck() {
      this.processingLiveness = true;
      try {
        await this.initializeCamera('videoElement');
        await KycService.performLivenessCheck(this.$refs.videoElement);
        this.verificationSteps.livenessCheck = true;
        this.currentStep++;
      } catch (error) {
        this.showError('Liveness check failed');
      } finally {
        this.processingLiveness = false;
        this.stopVideoStream();
      }
    },

    async captureSelfie() {
      this.processingSelfie = true;
      try {
        await this.initializeCamera('selfieVideo');
        const canvas = this.$refs.selfieCanvas;
        const context = canvas.getContext('2d');
        context.drawImage(this.$refs.selfieVideo, 0, 0, this.videoWidth, this.videoHeight);
        
        const selfieBlob = await new Promise(resolve => {
          canvas.toBlob(resolve, 'image/jpeg', 0.9);
        });

        const result = await KycService.captureSelfie(selfieBlob);
        if (result) {
          this.verificationSteps.faceMatch = true;
          this.currentStep++;
        }
      } catch (error) {
        this.showError('Selfie capture failed');
      } finally {
        this.processingSelfie = false;
        this.stopVideoStream();
      }
    },

    async completeVerification() {
      this.processingVerification = true;
      try {
        const success = await KycService.completeVerification({
          // Add any additional user data needed for verification
        });

        if (success) {
          this.verificationComplete = true;
          this.showSuccess('Verification completed successfully!');
          this.$emit('verification-complete', this.verificationDetails);
        }
      } catch (error) {
        this.showError('Verification failed');
      } finally {
        this.processingVerification = false;
      }
    },

    async initializeCamera(videoRef) {
      try {
        const stream = await navigator.mediaDevices.getUserMedia({
          video: {
            width: this.videoWidth,
            height: this.videoHeight,
            facingMode: 'user'
          }
        });
        
        this.videoStream = stream;
        this.$refs[videoRef].srcObject = stream;
        this.cameraReady = true;
        
        return new Promise(resolve => {
          this.$refs[videoRef].onloadedmetadata = () => {
            resolve();
          };
        });
      } catch (error) {
        this.showError('Failed to access camera');
        throw error;
      }
    },

    stopVideoStream() {
      if (this.videoStream) {
        this.videoStream.getTracks().forEach(track => track.stop());
        this.videoStream = null;
      }
      this.cameraReady = false;
    },

    handleStatusChange(status) {
      this.verificationDetails = status.details;
      if (status.details.message) {
        this.showAlert = true;
        this.alertVariant = status.status === 'failed' ? 'danger' : 'info';
        this.alertMessage = status.details.message;
      }
    },

    formatGestureInstruction(gesture) {
      switch (gesture) {
        case 'blink': return 'blink your eyes';
        case 'smile': return 'smile naturally';
        case 'turn_left': return 'turn your head slightly left';
        case 'turn_right': return 'turn your head slightly right';
        default: return '';
      }
    },

    showError(message) {
      this.showAlert = true;
      this.alertVariant = 'danger';
      this.alertMessage = message;
    },

    showSuccess(message) {
      this.showAlert = true;
      this.alertVariant = 'success';
      this.alertMessage = message;
    }
  }
};
</script>

<style scoped>
.kyc-verification {
  max-width: 800px;
  margin: 0 auto;
  padding: 20px;
}

.step-content {
  padding: 20px;
  text-align: center;
}

.video-container {
  position: relative;
  width: 640px;
  height: 480px;
  margin: 20px auto;
  background: #000;
}

.video-container video,
.video-container canvas {
  position: absolute;
  top: 0;
  left: 0;
}

.preview-area {
  margin: 20px 0;
  max-width: 100%;
  overflow: hidden;
}

.preview-area img {
  max-width: 100%;
  height: auto;
}

.gesture-instructions {
  margin: 20px 0;
  padding: 15px;
  background: #f8f9fa;
  border-radius: 4px;
}

.kyc-steps {
  margin-bottom: 30px;
}
</style>]]>