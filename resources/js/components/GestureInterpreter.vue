<template>
  <div
    ref="container"
    class="gesture-interpreter"
    v-if="isVisible"
    :style="containerStyle"
  >
    <div
      class="gesture-interpreter-header"
      @mousedown="startDrag"
      @touchstart="startDrag"
    >
      <h3 class="gesture-interpreter-title">Gesture Interpreter</h3>
      <div class="gesture-interpreter-controls">
        <button
          class="gesture-mode-btn"
          :class="{ active: isGestureMode }"
          @click="toggleGestureMode"
          title="Toggle gesture mode"
        >
          <i class="fas fa-sign-language"></i>
        </button>
        <button class="gesture-interpreter-close" @click="toggleVisibility">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>

    <div class="gesture-interpreter-content">
      <div v-if="currentGesture" class="current-gesture">
        <div class="gesture-label">Current Gesture:</div>
        <div class="gesture-value">
          {{ currentGesture.text }}
          <span class="gesture-confidence">
            {{ Math.round(currentGesture.confidence * 100) }}%
          </span>
        </div>
      </div>

      <div class="gesture-history">
        <div class="gesture-label">Gesture History:</div>
        <div class="gesture-messages">
          <div
            v-for="(message, index) in messageHistory"
            :key="index"
            class="gesture-message"
          >
            <div class="gesture-message-content">{{ message.content }}</div>
            <div class="gesture-message-time">{{ formatTime(message.timestamp) }}</div>
          </div>
        </div>
      </div>

      <div class="gesture-quick-reference">
        <div class="gesture-label">Quick Reference:</div>
        <div class="gesture-reference-grid">
          <div
            v-for="(text, gesture) in commonGestures"
            :key="gesture"
            class="gesture-reference-item"
          >
            <div class="gesture-reference-name">{{ formatGestureName(gesture) }}</div>
            <div class="gesture-reference-text">{{ text }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import UnifiedCommunicationService from '../services/UnifiedCommunicationService';

export default {
  name: 'GestureInterpreter',

  data() {
    return {
      isVisible: true,
      isGestureMode: false,
      currentGesture: null,
      messageHistory: [],
      commonGestures: {
        'thumbs_up': 'Yes / Good',
        'thumbs_down': 'No / Bad',
        'open_palm': 'Stop / Wait',
        'pointing': 'Look at this',
        'fist': 'Attention',
        'victory': 'Peace / Two',
        'ok_sign': 'OK / Good',
        'wave': 'Hello / Goodbye',
        'pain': 'I am in pain',
        'water': 'I need water',
        'medicine': 'I need medicine',
        'bathroom': 'I need to use the bathroom',
        'help': 'I need help'
      },

      // Draggable functionality
      isDragging: false,
      dragStartX: 0,
      dragStartY: 0,
      positionX: 20,
      positionY: 20
    };
  },

  computed: {
    containerStyle() {
      return {
        top: `${this.positionY}px`,
        left: `${this.positionX}px`
      };
    }
  },

  mounted() {
    // Initialize with all available gesture mappings
    this.commonGestures = UnifiedCommunicationService.getAllGestureTextMappings();

    // Set up callback for gesture detection
    UnifiedCommunicationService.onGestureDetected(this.handleGestureDetected);

    // Check if we're already in gesture mode
    this.isGestureMode = UnifiedCommunicationService.getCommunicationMode() === 'gesture';

    // Get initial message history
    this.messageHistory = UnifiedCommunicationService.getMessageHistory();

    // Add event listeners for drag
    document.addEventListener('mousemove', this.onDrag);
    document.addEventListener('mouseup', this.stopDrag);
    document.addEventListener('touchmove', this.onDrag);
    document.addEventListener('touchend', this.stopDrag);
  },

  beforeDestroy() {
    // Clean up event listeners
    document.removeEventListener('mousemove', this.onDrag);
    document.removeEventListener('mouseup', this.stopDrag);
    document.removeEventListener('touchmove', this.onDrag);
    document.removeEventListener('touchend', this.stopDrag);
  },

  methods: {
    toggleVisibility() {
      this.isVisible = !this.isVisible;
    },

    toggleGestureMode() {
      this.isGestureMode = !this.isGestureMode;

      // Update communication mode
      const newMode = this.isGestureMode ? 'gesture' : 'standard';
      UnifiedCommunicationService.setCommunicationMode(newMode);

      // Emit event to notify parent components
      this.$emit('gesture-mode-changed', this.isGestureMode);
    },

    handleGestureDetected(gesture) {
      this.currentGesture = gesture;

      // Update message history
      this.messageHistory = UnifiedCommunicationService.getMessageHistory();
    },

    formatTime(timestamp) {
      if (!timestamp) return '';

      const date = new Date(timestamp);
      return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    },

    formatGestureName(name) {
      return name
        .replace(/_/g, ' ')
        .replace(/\b\w/g, l => l.toUpperCase());
    },

    // Draggable functionality
    startDrag(event) {
      this.isDragging = true;

      // Get initial position
      if (event.type === 'mousedown') {
        this.dragStartX = event.clientX;
        this.dragStartY = event.clientY;
      } else if (event.type === 'touchstart') {
        this.dragStartX = event.touches[0].clientX;
        this.dragStartY = event.touches[0].clientY;
      }

      // Prevent default to avoid text selection during drag
      event.preventDefault();
    },

    onDrag(event) {
      if (!this.isDragging) return;

      let clientX, clientY;

      if (event.type === 'mousemove') {
        clientX = event.clientX;
        clientY = event.clientY;
      } else if (event.type === 'touchmove') {
        clientX = event.touches[0].clientX;
        clientY = event.touches[0].clientY;
      } else {
        return;
      }

      // Calculate new position
      const deltaX = clientX - this.dragStartX;
      const deltaY = clientY - this.dragStartY;

      // Update position
      this.positionX = Math.max(20, this.positionX + deltaX);
      this.positionY = Math.max(20, this.positionY + deltaY);

      // Update drag start position
      this.dragStartX = clientX;
      this.dragStartY = clientY;
    },

    stopDrag() {
      this.isDragging = false;
    }
  }
};
</script>

<style scoped>
.gesture-interpreter {
  position: fixed;
  width: 300px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  overflow: hidden;
  transition: transform 0.3s ease;
  user-select: none;
}

.gesture-interpreter-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px;
  background: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
  cursor: move;
}

.gesture-interpreter-title {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
}

.gesture-interpreter-controls {
  display: flex;
  align-items: center;
  gap: 10px;
}

.gesture-mode-btn {
  background: none;
  border: none;
  border-radius: 50%;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  color: #6c757d;
}

.gesture-mode-btn.active {
  background: #ff5c8a;
  color: white;
  box-shadow: 0 2px 5px rgba(255, 92, 138, 0.3);
}

.gesture-interpreter-close {
  background: none;
  border: none;
  color: #6c757d;
  cursor: pointer;
  font-size: 16px;
  padding: 0;
  transition: all 0.2s ease;
}

.gesture-interpreter-close:hover {
  color: #343a40;
}

.gesture-interpreter-content {
  padding: 15px;
}

.current-gesture {
  margin-bottom: 20px;
}

.gesture-label {
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 5px;
  color: #495057;
}

.gesture-value {
  font-size: 18px;
  font-weight: 700;
  padding: 8px 12px;
  border-radius: 6px;
  background: #f8f9fa;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  color: #ff5c8a;
}

.gesture-confidence {
  font-size: 14px;
  opacity: 0.7;
}

.gesture-history {
  margin-bottom: 20px;
}

.gesture-messages {
  max-height: 200px;
  overflow-y: auto;
  background: #f8f9fa;
  border-radius: 6px;
  padding: 10px;
}

.gesture-message {
  margin-bottom: 10px;
  padding-bottom: 10px;
  border-bottom: 1px solid #e9ecef;
}

.gesture-message:last-child {
  margin-bottom: 0;
  padding-bottom: 0;
  border-bottom: none;
}

.gesture-message-content {
  font-size: 14px;
  margin-bottom: 5px;
}

.gesture-message-time {
  font-size: 12px;
  color: #6c757d;
}

.gesture-quick-reference {
  margin-top: 20px;
}

.gesture-reference-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
  background: #f8f9fa;
  border-radius: 6px;
  padding: 10px;
  max-height: 200px;
  overflow-y: auto;
}

.gesture-reference-item {
  padding: 8px;
  background: white;
  border-radius: 4px;
  border: 1px solid #e9ecef;
}

.gesture-reference-name {
  font-size: 12px;
  font-weight: 600;
  margin-bottom: 2px;
  color: #ff5c8a;
}

.gesture-reference-text {
  font-size: 12px;
  color: #495057;
}
</style>
