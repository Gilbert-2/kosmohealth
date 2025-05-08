<template>
  <div id="app">
    <!-- Global components -->
    <emotion-monitor v-if="isInMeeting && isDoctor" :currentEmotion="currentEmotion" />

    <!-- Unified Plus Menu - Available on all pages except auth pages -->
    <unified-plus-menu
      v-if="isLoggedIn && !isAuthPage"
      @toggle-chat="toggleChat"
    />

    <!-- Kosmobot Panel -->
    <div class="floating-panel kosmobot-panel" v-if="showKosmobot">
      <kosmobot @close="showKosmobot = false" />
    </div>

    <!-- Period Calculator Panel -->
    <div class="floating-panel period-calculator-panel" v-if="showPeriodCalculator">
      <period-calculator @close="showPeriodCalculator = false" />
    </div>

    <!-- Main app content -->
    <router-view />
  </div>
</template>

<script>
import { mapGetters } from 'vuex';
import FaceProcessingVisibilityService from './services/FaceProcessingVisibilityService';
import EmotionDetectionService from './services/EmotionDetectionService';
import UnifiedPlusMenu from './components/UnifiedPlusMenu.vue';
import Kosmobot from './components/Kosmobot.vue';
import PeriodCalculator from './components/PeriodCalculator.vue';

export default {
  name: 'App',

  components: {
    UnifiedPlusMenu,
    Kosmobot,
    PeriodCalculator
  },

  data() {
    return {
      currentEmotion: null,
      isDoctor: false,
      showKosmobot: false,
      showPeriodCalculator: false,
      showChat: false
    };
  },

  computed: {
    ...mapGetters('user', ['isLoggedIn']),

    isAuthPage() {
      const currentPath = this.$route.path;
      return currentPath.includes('/auth/') ||
             currentPath === '/login' ||
             currentPath === '/register' ||
             currentPath.includes('/password') ||
             currentPath.includes('/verify');
    },

    isAdminPage() {
      const currentPath = this.$route.path;
      return currentPath.includes('/app/admin/') ||
             currentPath.includes('/app/panel/') ||
             currentPath.includes('/app/dashboard');
    },

    isInMeeting() {
      return FaceProcessingVisibilityService.isInMeeting();
    },

    isAdmin() {
      // Check if user has admin role
      return this.$store.getters['user/hasRole'] && this.$store.getters['user/hasRole']('admin');
    }
  },

  mounted() {
    // Check if user is a doctor
    this.checkUserRole();

    // Initialize emotion detection service
    EmotionDetectionService.initialize().then(() => {
      console.log('Emotion detection service initialized');

      // Set up callback for emotion detection
      EmotionDetectionService.onEmotionDetected(this.onEmotionDetected);
    });

    // Listen for emotion detection events
    this.$root.$on('emotion-detected', this.onEmotionDetected);

    // Listen for plus menu events
    this.$root.$on('open-kosmobot', this.openKosmobot);
    this.$root.$on('open-period-calculator', this.openPeriodCalculator);
    this.$root.$on('open-chat', this.openChat);
  },

  beforeDestroy() {
    this.$root.$off('emotion-detected', this.onEmotionDetected);
    this.$root.$off('open-kosmobot', this.openKosmobot);
    this.$root.$off('open-period-calculator', this.openPeriodCalculator);
    this.$root.$off('open-chat', this.openChat);
  },

  methods: {
    checkUserRole() {
      // Check if user has doctor role
      if (this.$store.getters['user/hasRole']) {
        this.isDoctor = this.$store.getters['user/hasRole']('doctor');
      }
    },

    onEmotionDetected(emotion) {
      this.currentEmotion = emotion;
    },

    openKosmobot() {
      this.showKosmobot = true;
      this.showPeriodCalculator = false; // Close other panels
    },

    openPeriodCalculator() {
      this.showPeriodCalculator = true;
      this.showKosmobot = false; // Close other panels
    },

    openChat() {
      this.showChat = true;
      // Emit event to chat component or navigate to chat page
      this.$root.$emit('show-chat');
    },

    toggleChat() {
      this.showChat = !this.showChat;
      this.$root.$emit(this.showChat ? 'show-chat' : 'hide-chat');
    }
  }
};
</script>

<style>
#app {
  font-family: var(--flo-font-family, 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif);
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  color: var(--flo-text, #333333);
}

/* Floating panels */
.floating-panel {
  position: fixed;
  bottom: 100px;
  right: 30px;
  width: 350px;
  height: 500px;
  z-index: 9998;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 5px 25px rgba(0, 0, 0, 0.15);
  animation: panel-slide-in 0.3s ease forwards;
}

@keyframes panel-slide-in {
  from {
    transform: translateY(20px);
    opacity: 0;
  }
  to {
    transform: translateY(0);
    opacity: 1;
  }
}

/* Kosmobot panel */
.kosmobot-panel {
  right: 100px;
}

/* Period calculator panel */
.period-calculator-panel {
  width: 400px;
  height: 600px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .floating-panel {
    width: 90%;
    height: 80%;
    bottom: 80px;
    right: 5%;
    left: 5%;
  }
}

@media (max-width: 480px) {
  .floating-panel {
    width: 100%;
    height: 100%;
    bottom: 0;
    right: 0;
    left: 0;
    border-radius: 0;
  }
}
</style>
