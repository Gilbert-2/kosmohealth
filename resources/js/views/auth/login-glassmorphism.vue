<template>
  <div class="guest-page glassmorphism-login flo-design">
    <div class="login-container">
      <div class="login-background">
        <div class="login-shape shape-1"></div>
        <div class="login-shape shape-2"></div>
        <div class="login-shape shape-3"></div>
      </div>

      <div class="login-card">
        <animated-loader :is-loading="isLoading" :loader-color="vars.loaderColor"></animated-loader>

        <div class="login-header">
          <app-logo :height="60"></app-logo>
          <h4 class="login-title">{{ $t('auth.login.page_title') }}</h4>
          <p class="login-subtitle">{{ $t('auth.login.welcome_back') }}</p>
        </div>

        <div class="login-body">
          <form @submit.prevent="submit">
            <base-input
              class="mb-4"
              auto-focus
              :label="$t('auth.login.props.email_username')"
              type="text"
              addon-left-icon="fas fa-user"
              :error="formErrors.email"
              @update:error="formErrors.email = $event"
              v-model="formData.email"
              autocomplete="username"
            ></base-input>

            <base-input
              class="mb-4"
              :label="$t('auth.login.props.password')"
              type="password"
              addon-left-icon="fas fa-lock"
              :error="formErrors.password"
              @update:error="formErrors.password = $event"
              v-model="formData.password"
              autocomplete="current-password"
              :show-password-toggle="true"
            ></base-input>

            <div class="d-flex justify-content-between align-items-center mb-4">
              <div class="form-check">
                <input
                  class="form-check-input"
                  type="checkbox"
                  id="rememberMe"
                  v-model="formData.remember"
                >
                <label class="form-check-label" for="rememberMe">
                  {{ $t('auth.login.props.remember_me') }}
                </label>
              </div>

              <router-link
                v-if="configs && configs.auth && configs.auth.resetPassword"
                :to="withQuery({ name: 'password' })"
                class="forgot-password"
              >
                {{ $t('auth.login.forgot_password') }}
              </router-link>
            </div>

            <base-button
              type="submit"
              design="primary"
              block
              class="login-button mb-4"
            >
              {{ $t('auth.login.login') }}
            </base-button>

            <!-- Social Login Options -->
            <div v-if="configs && configs.auth" class="social-login-section">
              <div v-if="hasSocialLoginOptions" class="social-login-container">
                <div class="social-login-divider">
                  <span>{{ $t('auth.login.or_login_with') }}</span>
                </div>

                <div class="social-buttons">
                  <!-- Email OTP Login -->
                  <router-link
                    v-if="configs.auth.emailOtpLogin"
                    :to="withQuery({ name: 'login-email-otp' })"
                    class="social-button email-otp"
                    v-b-tooltip.hover
                    :title="$t('auth.login.login_using_email_otp')"
                  >
                    <i class="fas fa-envelope-open-text"></i>
                  </router-link>

                  <!-- Mobile OTP Login -->
                  <router-link
                    v-if="configs.auth.mobileOtpLogin"
                    :to="withQuery({ name: 'login-sms-otp' })"
                    class="social-button mobile-otp"
                    v-b-tooltip.hover
                    :title="$t('auth.login.login_using_sms_otp')"
                  >
                    <i class="fas fa-mobile-alt"></i>
                  </router-link>

                  <!-- Social Login Providers -->
                  <a
                    v-for="provider in socialLoginProviders"
                    :key="provider"
                    :href="`/auth/login/${provider}`"
                    :class="`social-button ${provider}`"
                    v-b-tooltip.hover
                    :title="$t('auth.login.login_with', { attribute: provider })"
                  >
                    <i :class="icons[provider]"></i>
                  </a>
                </div>
              </div>

              <!-- Registration Link -->
              <div v-if="configs.auth.registration" class="register-link-container">
                <p>{{ $t('auth.login.no_account') }}
                  <router-link :to="withQuery({ name: 'register' })" class="register-link">
                    {{ $t('auth.register.register_here') }}
                  </router-link>
                </p>
              </div>
            </div>

            <!-- PWA Install Button -->
            <div v-if="showPwaInstallButton" class="pwa-install-container">
              <base-button
                design="secondary"
                block
                class="pwa-install-button"
                @click="installPwa"
              >
                {{ $t('auth.login.install_pwa') }}
              </base-button>
            </div>
          </form>
        </div>
      </div>

      <guest-footer
        v-if="configs.system"
        :footer-credit="configs.system.footerCredit"
        :version="configs.system.version"
        class="glassmorphism-footer"
      ></guest-footer>
    </div>
  </div>
</template>

<script>
import { mapGetters, mapActions } from 'vuex';
import GuestFooter from '@components/GuestFooter';
import AppLogo from '@components/AppLogo';

export default {
  components: {
    GuestFooter,
    AppLogo
  },

  data() {
    return {
      formData: {
        email: '',
        password: '',
        remember: false
      },
      formErrors: {},
      isLoading: false,
      icons: {
        facebook: 'fab fa-facebook-f',
        twitter: 'fab fa-twitter',
        github: 'fab fa-github',
        google: 'fab fa-google'
      },
      vars: {
        loaderColor: '#d15465'
      },
      deferredPrompt: null,
      showPwaInstallButton: false
    };
  },

  computed: {
    ...mapGetters('config', ['configs']),
    ...mapGetters('user', ['twoFactorSet', 'hasRole']),

    hasSocialLoginOptions() {
      return this.configs && this.configs.auth && (
        this.configs.auth.emailOtpLogin ||
        this.configs.auth.mobileOtpLogin ||
        (this.configs.auth.socialLogin && this.socialLoginProviders.length)
      );
    },

    socialLoginProviders() {
      if (this.configs && this.configs.auth && this.configs.auth.socialLogin && this.configs.auth.socialLoginProviders) {
        return this.configs.auth.socialLoginProviders;
      }
      return [];
    }
  },

  methods: {
    ...mapActions('user', ['Login', 'ResetTwoFactorSet']),
    ...mapActions('config', ['SetCSRF']),

    submit() {
      this.isLoading = true;
      const query = this.$route.query;

      // Track login attempt
      this.$gaEvent('engagement', 'login', 'Glassmorphism');

      this.Login(this.formData)
        .then(response => {
          this.$toasted.success(response.message, this.$toastConfig);
          this.$gaEvent('activity', 'loggedin', 'Glassmorphism');

          // Handle two-factor authentication if enabled
          if (this.configs.auth.twoFactorSecurity && this.twoFactorSet) {
            this.$router.push({ name: 'authSecurity', query });
            this.isLoading = false;
          } else {
            // Determine redirect path
            let redirectTo = response.reload
              ? { name: 'appDashboard', query: { reload: 1 } }
              : { name: 'appDashboard' };

            // Check for referrer in query
            if (query && query.ref && this.$router.resolve(query.ref)) {
              redirectTo = this.$router.resolve(query.ref).route;
            }

            // Check for admin setup wizard
            if (this.hasRole('admin') && this.configs.system && this.configs.system.setupWizard) {
              redirectTo = { name: 'setup' };
            }

            // Clear locale from query
            redirectTo = { ...redirectTo, query: { locale: undefined } };

            // Reset two-factor and redirect
            this.ResetTwoFactorSet()
              .then(() => {
                this.$router.push(redirectTo);
              })
              .catch(error => {
                this.isLoading = false;
                this.formErrors = formUtil.handleErrors(error);
              });
          }
        })
        .catch(error => {
          this.isLoading = false;
          this.formErrors = formUtil.handleErrors(error);
        });
    },

    installPwa() {
      if (this.deferredPrompt) {
        this.deferredPrompt.prompt();
        this.deferredPrompt.userChoice.then(choiceResult => {
          if (choiceResult.outcome === 'accepted') {
            this.showPwaInstallButton = false;
          }
          this.deferredPrompt = null;
        });
      }
    }
  },

  mounted() {
    this.SetCSRF();

    // Check for CSRF token expiration
    const cause = this.$route.query && this.$route.query.cause ? this.$route.query.cause : null;
    if (cause && cause === 'csrf_token_expired') {
      this.$toasted.error(this.$t('general.csrf_token_expired'), this.$toastConfig.error);
      this.$router.push(this.withQuery({ name: this.$route.name, replace: true }));
    }

    // Handle PWA install prompt
    window.addEventListener('beforeinstallprompt', e => {
      e.preventDefault();
      this.deferredPrompt = e;
      this.showPwaInstallButton = true;
    });

    window.addEventListener('appinstalled', () => {
      this.showPwaInstallButton = false;
    });
  }
};
</script>

<style scoped>
.glassmorphism-login {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #d15465, #3a0ca3);
  position: relative;
  overflow: hidden;
  padding: 20px;
}

.login-container {
  width: 100%;
  max-width: 1200px;
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100vh;
}

.login-background {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  z-index: -1;
}

.login-shape {
  position: absolute;
  border-radius: 50%;
  filter: blur(40px);
}

.shape-1 {
  top: -100px;
  right: -100px;
  width: 300px;
  height: 300px;
  background: rgba(76, 201, 240, 0.3);
}

.shape-2 {
  bottom: -150px;
  left: -100px;
  width: 400px;
  height: 400px;
  background: rgba(247, 37, 133, 0.2);
}

.shape-3 {
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  width: 500px;
  height: 500px;
  background: rgba(58, 12, 163, 0.1);
}

.login-card {
  width: 100%;
  max-width: 450px;
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border-radius: 20px;
  border: 1px solid rgba(255, 255, 255, 0.18);
  box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.2);
  padding: 40px;
  color: white;
  position: relative;
  overflow: hidden;
  transition: all 0.3s ease;
}

.login-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 15px 35px 0 rgba(31, 38, 135, 0.3);
}

.login-header {
  text-align: center;
  margin-bottom: 30px;
}

.login-title {
  font-size: 24px;
  font-weight: 700;
  margin: 15px 0 5px;
}

.login-subtitle {
  font-size: 16px;
  opacity: 0.8;
  margin-bottom: 0;
}

.login-body {
  margin-bottom: 20px;
}

.login-button {
  height: 48px;
  font-size: 16px;
  font-weight: 600;
  border-radius: 10px;
  background: linear-gradient(45deg, #d15465, #3a0ca3);
  border: none;
  transition: all 0.3s ease;
}

.login-button:hover {
  background: linear-gradient(45deg, #3a0ca3, #d15465);
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.forgot-password {
  color: rgba(255, 255, 255, 0.8);
  font-size: 14px;
  text-decoration: none;
  transition: all 0.3s ease;
}

.forgot-password:hover {
  color: white;
  text-decoration: underline;
}

.form-check-input {
  background-color: rgba(255, 255, 255, 0.2);
  border-color: rgba(255, 255, 255, 0.3);
}

.form-check-input:checked {
  background-color: #d15465;
  border-color: #d15465;
}

.form-check-label {
  font-size: 14px;
  color: rgba(255, 255, 255, 0.8);
}

.social-login-section {
  margin-top: 20px;
}

.social-login-divider {
  display: flex;
  align-items: center;
  text-align: center;
  margin-bottom: 20px;
}

.social-login-divider::before,
.social-login-divider::after {
  content: '';
  flex: 1;
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.social-login-divider span {
  padding: 0 10px;
  font-size: 14px;
  color: rgba(255, 255, 255, 0.7);
}

.social-buttons {
  display: flex;
  justify-content: center;
  gap: 15px;
  margin-bottom: 25px;
}

.social-button {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 16px;
  transition: all 0.3s ease;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.social-button:hover {
  transform: translateY(-3px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.social-button.facebook {
  background: rgba(66, 103, 178, 0.8);
}

.social-button.twitter {
  background: rgba(29, 161, 242, 0.8);
}

.social-button.github {
  background: rgba(51, 51, 51, 0.8);
}

.social-button.google {
  background: rgba(219, 68, 55, 0.8);
}

.social-button.email-otp {
  background: rgba(76, 201, 240, 0.8);
}

.social-button.mobile-otp {
  background: rgba(247, 37, 133, 0.8);
}

.register-link-container {
  text-align: center;
  margin-top: 10px;
  font-size: 14px;
  color: rgba(255, 255, 255, 0.7);
}

.register-link {
  color: white;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s ease;
}

.register-link:hover {
  text-decoration: underline;
}

.glassmorphism-footer {
  margin-top: 30px;
  color: rgba(255, 255, 255, 0.7);
}

.pwa-install-container {
  margin-top: 20px;
  text-align: center;
}

.pwa-install-button {
  background: linear-gradient(45deg, #3a0ca3, #d15465);
  color: white;
  font-weight: bold;
  border: none;
  border-radius: 10px;
  padding: 10px 20px;
  transition: all 0.3s ease;
}

.pwa-install-button:hover {
  background: linear-gradient(45deg, #d15465, #3a0ca3);
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

/* Responsive adjustments */
@media (max-width: 576px) {
  .login-card {
    padding: 30px 20px;
  }

  .login-title {
    font-size: 20px;
  }

  .social-buttons {
    flex-wrap: wrap;
  }
}
</style>
