<template>
  <div class="unified-plus-menu" :class="{ 'expanded': isExpanded }">
    <!-- Main plus button -->
    <button class="plus-button" @click="toggleMenu">
      <i class="fas" :class="isExpanded ? 'fa-times' : 'fa-plus'"></i>
    </button>

    <!-- Menu items (only visible when expanded) -->
    <div class="menu-items" v-if="isExpanded">
      <!-- KYC Button removed -->

      <!-- Chat Button -->
      <a href="#" class="menu-item chat-item" @click.prevent="openChat">
        <div class="menu-icon">
          <i class="fas fa-comments"></i>
        </div>
        <span class="menu-label">Chat</span>
      </a>

      <!-- Kosmobot Button -->
      <a href="#" class="menu-item kosmobot-item" @click.prevent="openKosmobot">
        <div class="menu-icon">
          <i class="fas fa-robot"></i>
        </div>
        <span class="menu-label">Kosmobot</span>
      </a>

      <!-- Period Calculator Button -->
      <a href="#" class="menu-item period-calculator-item" @click.prevent="openPeriodCalculator">
        <div class="menu-icon">
          <i class="fas fa-calendar-alt"></i>
        </div>
        <span class="menu-label">Period Calculator</span>
      </a>
    </div>
  </div>
</template>

<script>
export default {
  name: 'UnifiedPlusMenu',

  data() {
    return {
      isExpanded: false,
      kycWindow: null,
      chatWindow: null,
      kosmobotWindow: null,
      periodCalculatorWindow: null
    };
  },

  methods: {
    toggleMenu() {
      this.isExpanded = !this.isExpanded;
    },

    // KYC method removed

    openChat() {
      // Open chat interface
      this.$emit('open-chat');

      // If using a separate chat window/panel
      if (this.chatWindow && !this.chatWindow.closed) {
        this.chatWindow.focus();
      } else {
        // For in-app chat, emit event to parent component
        this.$emit('toggle-chat');
      }

      // Close menu after selection
      this.isExpanded = false;
    },

    openKosmobot() {
      // Emit event to show Kosmobot panel
      this.$root.$emit('open-kosmobot');

      // Close menu after selection
      this.isExpanded = false;
    },

    openPeriodCalculator() {
      // Emit event to show Period Calculator panel
      this.$root.$emit('open-period-calculator');

      // Close menu after selection
      this.isExpanded = false;
    }
  }
};
</script>

<style scoped>
.unified-plus-menu {
  position: fixed;
  bottom: 30px;
  right: 30px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
}

.plus-button {
  width: 60px;
  height: 60px;
  border-radius: 50%;
  background: linear-gradient(135deg, #d15465, #3f37c9);
  color: white;
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  cursor: pointer;
  box-shadow: 0 4px 15px rgba(67, 97, 238, 0.4);
  transition: all 0.3s ease;
  z-index: 10;
}

.plus-button:hover {
  transform: scale(1.1);
  box-shadow: 0 6px 20px rgba(67, 97, 238, 0.6);
}

.menu-items {
  position: absolute;
  bottom: 70px;
  right: 0;
  display: flex;
  flex-direction: column;
  gap: 15px;
  transition: all 0.3s ease;
}

.menu-item {
  display: flex;
  align-items: center;
  padding: 10px 15px;
  border-radius: 30px;
  color: white;
  text-decoration: none;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
  transition: all 0.3s ease;
  animation: slideIn 0.3s ease forwards;
  transform: translateX(50px);
  opacity: 0;
}

.menu-item:nth-child(1) {
  animation-delay: 0s;
}

.menu-item:nth-child(2) {
  animation-delay: 0.05s;
}

.menu-item:nth-child(3) {
  animation-delay: 0.1s;
}

.menu-item:nth-child(4) {
  animation-delay: 0.15s;
}

@keyframes slideIn {
  to {
    transform: translateX(0);
    opacity: 1;
  }
}

.menu-item:hover {
  transform: scale(1.05);
}

.menu-icon {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 10px;
}

.menu-label {
  font-weight: 500;
  white-space: nowrap;
}

/* Item-specific styles */
.chat-item {
  background: linear-gradient(135deg, #4caf50, #2e7d32);
}

.kosmobot-item {
  background: linear-gradient(135deg, #2196f3, #1565c0);
}

.period-calculator-item {
  background: linear-gradient(135deg, #e91e63, #c2185b);
}

/* Animation for the plus button when toggling */
.expanded .plus-button {
  transform: rotate(135deg);
  background: linear-gradient(135deg, #f44336, #d32f2f);
  box-shadow: 0 4px 15px rgba(244, 67, 54, 0.4);
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .menu-label {
    display: none;
  }

  .menu-item {
    padding: 10px;
  }

  .menu-icon {
    margin-right: 0;
  }
}
</style>
