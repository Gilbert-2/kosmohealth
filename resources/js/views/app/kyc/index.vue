<template>
  <div class="kyc-wrapper">
    <base-container>
      <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
          <card>
            <template slot="header">
              <h5 class="card-title">KYC Verification</h5>
            </template>

            <!-- Initial State -->
            <div v-if="!session" class="text-center p-4">
              <h4>Verify Your Identity</h4>
              <p class="text-muted">Please complete the verification process to access all features</p>
              <base-button @click="startVerification" variant="primary" :loading="isLoading">
                Start Verification
              </base-button>
            </div>

            <!-- Document Upload -->
            <div v-else-if="!documentUploaded" class="p-4">
              <h4>Upload Identification Document</h4>
              <p class="text-muted mb-4">Please upload a clear photo of your government-issued ID</p>

              <div class="document-upload mb-4">
                <file-uploader
                  v-model="documentFile"
                  accept="image/*"
                  :max-size="5242880"
                  @input="handleDocumentUpload"
                >
                  <template slot="placeholder">
                    <div class="text-center p-4 border rounded">
                      <i class="fas fa-upload fa-2x mb-2"></i>
                      <p>Drop your ID here or click to upload</p>
                      <small class="text-muted">Supported formats: JPEG, PNG. Max size: 5MB</small>
                    </div>
                  </template>
                </file-uploader>
              </div>
            </div>

            <!-- Verification Status -->
            <div v-else class="p-4">
              <div class="text-center">
                <template v-if="verificationStatus === 'pending'">
                  <i class="fas fa-spinner fa-spin fa-3x mb-3"></i>
                  <h4>Verifying Your Document</h4>
                  <p class="text-muted">Please wait while we verify your document</p>
                </template>

                <template v-else-if="verificationStatus === 'approved'">
                  <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                  <h4>Verification Successful</h4>
                  <p class="text-success">Your identity has been verified successfully</p>
                </template>

                <template v-else-if="verificationStatus === 'rejected'">
                  <i class="fas fa-times-circle fa-3x text-danger mb-3"></i>
                  <h4>Verification Failed</h4>
                  <p class="text-danger">{{ verificationError }}</p>
                  <base-button @click="resetVerification" variant="primary">
                    Try Again
                  </base-button>
                </template>
              </div>
            </div>
          </card>
        </div>
      </div>
    </base-container>
  </div>
</template>

<script>
export default {
  data() {
    return {
      session: null,
      documentFile: null,
      documentUploaded: false,
      verificationStatus: null,
      verificationError: null,
      isLoading: false
    }
  },

  mounted() {
    this.checkStatus()
  },

  methods: {
    async startVerification() {
      this.isLoading = true
      try {
        const response = await this.$http.post('/api/kyc/session/start')
        this.session = response.data.session_id
      } catch (error) {
        this.$toasted.error(error.response?.data?.message || 'Failed to start verification')
      } finally {
        this.isLoading = false
      }
    },

    async handleDocumentUpload(file) {
      if (!file) return

      this.isLoading = true
      try {
        const formData = new FormData()
        formData.append('document', file)
        formData.append('session_id', this.session)

        await this.$http.post('/api/kyc/document/verify', formData)
        this.documentUploaded = true
        this.verifyIdentity()
      } catch (error) {
        this.$toasted.error(error.response?.data?.message || 'Failed to upload document')
      } finally {
        this.isLoading = false
      }
    },

    async verifyIdentity() {
      try {
        await this.$http.post('/api/kyc/verify/complete', {
          session_id: this.session
        })
        this.checkStatus()
      } catch (error) {
        this.verificationStatus = 'rejected'
        this.verificationError = error.response?.data?.message || 'Verification failed'
      }
    },

    async checkStatus() {
      if (!this.session) return

      try {
        const response = await this.$http.get(`/api/kyc/status/${this.session}`)
        this.verificationStatus = response.data.status
        if (response.data.error) {
          this.verificationError = response.data.error
        }
      } catch (error) {
        this.$toasted.error(error.response?.data?.message || 'Failed to check status')
      }
    },

    resetVerification() {
      this.session = null
      this.documentFile = null
      this.documentUploaded = false
      this.verificationStatus = null
      this.verificationError = null
    }
  }
}
</script>

<style lang="scss" scoped>
.kyc-wrapper {
  .document-upload {
    max-width: 500px;
    margin: 0 auto;
  }

  .fa-spinner {
    color: var(--primary);
  }
}
</style>