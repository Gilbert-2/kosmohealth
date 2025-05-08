<template>
  <div class="gesture-recognition-wrapper" v-if="shouldShowGestureRecognition">
    <!-- Gesture mode toggle button -->
    <div class="gesture-recognition-controls" v-if="showControls">
      <button 
        class="gesture-mode-btn" 
        :class="{ active: isGestureMode }" 
        @click="toggleGestureMode"
        title="Toggle gesture mode"
      >
        <i class="fas fa-sign-language"></i>
      </button>
    </div>
    
    <!-- Canvas for gesture recognition -->
    <canvas 
      ref="outputCanvas" 
      class="gesture-recognition-canvas" 
      v-show="showCanvas"
    ></canvas>
    
    <!-- Draggable gesture info panel -->
    <div 
      ref="gestureInfoPanel"
      v-if="showGestureInfo && currentGesture" 
      class="gesture-info draggable"
      :style="gestureInfoStyle"
      @mousedown="startDragGestureInfo"
      @touchstart="startDragGestureInfo"
    >
      <div class="gesture-info-handle"></div>
      <span class="gesture-text">{{ currentGesture.text }}</span>
      <span class="gesture-confidence">{{ Math.round(currentGesture.confidence * 100) }}%</span>
    </div>
    
    <!-- Draggable message display -->
    <div 
      ref="messageDisplay"
      class="gesture-message-display draggable" 
      v-if="isGestureMode && messageHistory.length > 0"
      :style="messageDisplayStyle"
      @mousedown="startDragMessageDisplay"
      @touchstart="startDragMessageDisplay"
    >
      <div class="gesture-message-handle"></div>
      <div class="gesture-message-container">
        <div 
          v-for="(message, index) in recentMessages" 
          :key="index"
          class="gesture-message-item"
          :class="{ 'fade-out': message.isExpiring }"
        >
          <div class="gesture-message-content">{{ message.content }}</div>
        </div>
      </div>
    </div>
    
    <!-- Floating action button for mobile -->
    <div class="gesture-fab" v-if="isMobile">
      <button 
        class="gesture-fab-btn"
        :class="{ active: isGestureMode }"
        @click="toggleGestureMode"
      >
        <i class="fas fa-sign-language"></i>
      </button>
    </div>
  </div>
</template>

<script>
import UnifiedCommunicationService from '../services/UnifiedCommunicationService';

export default {
  name: 'FixedGestureRecognitionWrapper',
  
  props: {
    videoElementId: {
      type: String,
      required: true
    },
    showControls: {
      type: Boolean,
      default: true
    },
    showCanvas: {
      type: Boolean,
      default: true
    },
    showGestureInfo: {
      type: Boolean,
      default: true
    },
    processingFps: {
      type: Number,
      default: 5
    }
  },
  
  data() {
    return {
      videoElement: null,
      currentGesture: null,
      isGestureMode: false,
      isProcessing: false,
      shouldShowGestureRecognition: false,
      messageHistory: [],
      messageDisplayDuration: 5000, // 5 seconds
      messageExpirationTimers: {},
      
      // Draggable functionality for gesture info panel
      isDraggingGestureInfo: false,
      gestureInfoDragStartX: 0,
      gestureInfoDragStartY: 0,
      gestureInfoPositionX: 20,
      gestureInfoPositionY: null, // null means use the default bottom position
      
      // Draggable functionality for message display
      isDraggingMessageDisplay: false,
      messageDisplayDragStartX: 0,
      messageDisplayDragStartY: 0,
      messageDisplayPositionX: null, // null means use the default center position
      messageDisplayPositionY: null, // null means use the default bottom position
      
      // Mobile detection
      isMobile: false
    };
  },
  
  computed: {
    recentMessages() {
      // Return the 3 most recent messages
      return this.messageHistory.slice(-3);
    },
    
    gestureInfoStyle() {
      const style = {};
      
      if (this.gestureInfoPositionX !== null) {
        style.left = `${this.gestureInfoPositionX}px`;
        // Remove the default left position
        style.right = 'auto';
      }
      
      if (this.gestureInfoPositionY !== null) {
        style.top = `${this.gestureInfoPositionY}px`;
        // Remove the default bottom position
        style.bottom = 'auto';
      }
      
      return style;
    },
    
    messageDisplayStyle() {
      const style = {};
      
      if (this.messageDisplayPositionX !== null) {
        style.left = `${this.messageDisplayPositionX}px`;
        // Remove the default center positioning
        style.right = 'auto';
        style.transform = 'none';
      }
      
      if (this.messageDisplayPositionY !== null) {
        style.top = `${this.messageDisplayPositionY}px`;
        // Remove the default bottom position
        style.bottom = 'auto';
      }
      
      return style;
    }
  },
  
  mounted() {
    // Detect mobile devices
    this.detectMobile();
    
    // Find the video element
    this.findVideoElement();
    
    // Initialize the unified communication service
    UnifiedCommunicationService.initialize().then(() => {
      // Set up callbacks
      UnifiedCommunicationService.onGestureDetected(this.handleGestureDetected);
      
      // Check if we're already in gesture mode
      this.isGestureMode = UnifiedCommunicationService.getCommunicationMode() === 'gesture';
      
      // Get initial message history
      this.messageHistory = UnifiedCommunicationService.getMessageHistory();
    });
    
    // Add event listeners for dragging gesture info panel and message display
    document.addEventListener('mousemove', this.onDragGestureInfo);
    document.addEventListener('mouseup', this.stopDragGestureInfo);
    document.addEventListener('touchmove', this.onDragGestureInfo);
    document.addEventListener('touchend', this.stopDragGestureInfo);
    
    document.addEventListener('mousemove', this.onDragMessageDisplay);
    document.addEventListener('mouseup', this.stopDragMessageDisplay);
    document.addEventListener('touchmove', this.onDragMessageDisplay);
    document.addEventListener('touchend', this.stopDragMessageDisplay);
    
    // Listen for window resize events
    window.addEventListener('resize', this.handleResize);
  },
  
  beforeDestroy() {
    // Stop processing when component is destroyed
    UnifiedCommunicationService.stopProcessing();
    
    // Clear all message expiration timers
    Object.values(this.messageExpirationTimers).forEach(timer => {
      clearTimeout(timer);
    });
    
    // Remove event listeners
    if (this.videoElement) {
      this.videoElement.removeEventListener('resize', this.handleVideoResize);
      this.videoElement.removeEventListener('loadedmetadata', this.handleVideoResize);
    }
    
    window.removeEventListener('resize', this.handleResize);
    
    // Remove drag event listeners
    document.removeEventListener('mousemove', this.onDragGestureInfo);
    document.removeEventListener('mouseup', this.stopDragGestureInfo);
    document.removeEventListener('touchmove', this.onDragGestureInfo);
    document.removeEventListener('touchend', this.stopDragGestureInfo);
    
    document.removeEventListener('mousemove', this.onDragMessageDisplay);
    document.removeEventListener('mouseup', this.stopDragMessageDisplay);
    document.removeEventListener('touchmove', this.onDragMessageDisplay);
    document.removeEventListener('touchend', this.stopDragMessageDisplay);
  },
  
  methods: {
    detectMobile() {
      // Simple mobile detection
      this.isMobile = window.innerWidth <= 768;
    },
    
    findVideoElement() {
      // Try to find the video element by ID
      this.videoElement = document.getElementById(this.videoElementId);
      
      if (!this.videoElement) {
        console.warn(`Video element with ID "${this.videoElementId}" not found. Retrying in 1 second...`);
        setTimeout(() => {
          this.findVideoElement();
        }, 1000);
      } else {
        // Video element found, show gesture recognition
        this.shouldShowGestureRecognition = true;
        
        // Start processing if in gesture mode
        if (this.isGestureMode) {
          this.startGestureRecognition();
        }
        
        // Add resize event listeners
        this.videoElement.addEventListener('resize', this.handleVideoResize);
        this.videoElement.addEventListener('loadedmetadata', this.handleVideoResize);
        window.addEventListener('resize', this.handleResize);
      }
    },
    
    startGestureRecognition() {
      if (!this.videoElement) return;
      
      const canvas = this.$refs.outputCanvas;
      if (canvas) {
        // Set canvas dimensions to match video
        canvas.width = this.videoElement.videoWidth || 640;
        canvas.height = this.videoElement.videoHeight || 480;
        
        // Ensure canvas is visible
        canvas.style.display = 'block';
        canvas.style.zIndex = '31';
      }
      
      // Set communication mode to gesture
      UnifiedCommunicationService.setCommunicationMode('gesture');
      
      // Start processing
      UnifiedCommunicationService.startProcessing(
        this.videoElement,
        canvas,
        this.processingFps
      );
      
      this.isProcessing = true;
    },
    
    stopGestureRecognition() {
      UnifiedCommunicationService.stopProcessing();
      this.isProcessing = false;
    },
    
    toggleGestureMode() {
      this.isGestureMode = !this.isGestureMode;
      
      if (this.isGestureMode) {
        this.startGestureRecognition();
      } else {
        this.stopGestureRecognition();
        
        // Set communication mode back to standard
        UnifiedCommunicationService.setCommunicationMode('standard');
      }
      
      // Emit event
      this.$emit('gesture-mode-changed', this.isGestureMode);
    },
    
    handleGestureDetected(gesture) {
      this.currentGesture = gesture;
      
      // Update message history
      this.messageHistory = UnifiedCommunicationService.getMessageHistory();
      
      // Set up expiration timer for the new message
      const messageIndex = this.messageHistory.length - 1;
      if (messageIndex >= 0) {
        const message = this.messageHistory[messageIndex];
        
        // Clear any existing timer for this message
        if (this.messageExpirationTimers[messageIndex]) {
          clearTimeout(this.messageExpirationTimers[messageIndex]);
        }
        
        // Set expiration timer
        this.messageExpirationTimers[messageIndex] = setTimeout(() => {
          // Mark message as expiring to trigger fade-out animation
          if (this.messageHistory[messageIndex]) {
            this.$set(this.messageHistory[messageIndex], 'isExpiring', true);
            
            // Remove message after animation completes
            setTimeout(() => {
              this.messageHistory.splice(messageIndex, 1);
            }, 1000); // Animation duration
          }
        }, this.messageDisplayDuration);
      }
      
      // Emit event
      this.$emit('gesture-detected', gesture);
    },
    
    handleVideoResize() {
      if (!this.videoElement || !this.$refs.outputCanvas) return;
      
      // Update canvas dimensions
      this.$refs.outputCanvas.width = this.videoElement.videoWidth || 640;
      this.$refs.outputCanvas.height = this.videoElement.videoHeight || 480;
    },
    
    handleResize() {
      this.handleVideoResize();
      this.detectMobile();
    },
    
    // Draggable functionality for gesture info panel
    startDragGestureInfo(event) {
      this.isDraggingGestureInfo = true;
      
      // Get initial position
      if (event.type === 'mousedown') {
        this.gestureInfoDragStartX = event.clientX;
        this.gestureInfoDragStartY = event.clientY;
      } else if (event.type === 'touchstart') {
        this.gestureInfoDragStartX = event.touches[0].clientX;
        this.gestureInfoDragStartY = event.touches[0].clientY;
      }
      
      // If this is the first drag and we're using default position,
      // initialize the Y position based on the current position
      if (this.gestureInfoPositionY === null) {
        const rect = this.$refs.gestureInfoPanel.getBoundingClientRect();
        this.gestureInfoPositionY = rect.top;
      }
      
      // Prevent default to avoid text selection during drag
      event.preventDefault();
    },
    
    onDragGestureInfo(event) {
      if (!this.isDraggingGestureInfo) return;
      
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
      const deltaX = clientX - this.gestureInfoDragStartX;
      const deltaY = clientY - this.gestureInfoDragStartY;
      
      // Update position
      this.gestureInfoPositionX = Math.max(20, this.gestureInfoPositionX + deltaX);
      this.gestureInfoPositionY = Math.max(20, this.gestureInfoPositionY + deltaY);
      
      // Update drag start position
      this.gestureInfoDragStartX = clientX;
      this.gestureInfoDragStartY = clientY;
    },
    
    stopDragGestureInfo() {
      this.isDraggingGestureInfo = false;
    },
    
    // Draggable functionality for message display
    startDragMessageDisplay(event) {
      // Only start drag on the handle or container itself, not on message items
      const target = event.target;
      if (!target.classList.contains('gesture-message-handle') && 
          !target.classList.contains('gesture-message-display')) {
        return;
      }
      
      this.isDraggingMessageDisplay = true;
      
      // Get initial position
      if (event.type === 'mousedown') {
        this.messageDisplayDragStartX = event.clientX;
        this.messageDisplayDragStartY = event.clientY;
      } else if (event.type === 'touchstart') {
        this.messageDisplayDragStartX = event.touches[0].clientX;
        this.messageDisplayDragStartY = event.touches[0].clientY;
      }
      
      // If this is the first drag and we're using default position,
      // initialize the positions based on the current position
      if (this.messageDisplayPositionX === null || this.messageDisplayPositionY === null) {
        const rect = this.$refs.messageDisplay.getBoundingClientRect();
        this.messageDisplayPositionX = rect.left;
        this.messageDisplayPositionY = rect.top;
      }
      
      // Prevent default to avoid text selection during drag
      event.preventDefault();
    },
    
    onDragMessageDisplay(event) {
      if (!this.isDraggingMessageDisplay) return;
      
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
      const deltaX = clientX - this.messageDisplayDragStartX;
      const deltaY = clientY - this.messageDisplayDragStartY;
      
      // Update position
      this.messageDisplayPositionX = Math.max(20, this.messageDisplayPositionX + deltaX);
      this.messageDisplayPositionY = Math.max(20, this.messageDisplayPositionY + deltaY);
      
      // Update drag start position
      this.messageDisplayDragStartX = clientX;
      this.messageDisplayDragStartY = clientY;
    },
    
    stopDragMessageDisplay() {
      this.isDraggingMessageDisplay = false;
    }
  }
};
</script>

<style scoped>
.gesture-recognition-wrapper {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 30;
}

.gesture-recognition-controls {
  position: absolute;
  top: 20px;
  right: 20px;
  display: flex;
  gap: 10px;
  pointer-events: auto;
  z-index: 1050;
}

.gesture-mode-btn {
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  border: none;
  border-radius: 50%;
  width: 40px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
  color: rgba(255, 255, 255, 0.7);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.gesture-mode-btn.active {
  background: #ff5c8a;
  color: white;
  box-shadow: 0 4px 10px rgba(255, 92, 138, 0.3);
}

.gesture-recognition-canvas {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 31;
}

.gesture-info {
  position: absolute;
  bottom: 20px;
  left: 20px;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  padding: 8px 16px;
  display: flex;
  align-items: center;
  gap: 8px;
  color: white;
  font-weight: 600;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
  pointer-events: auto;
  cursor: move;
  user-select: none;
  transition: box-shadow 0.2s ease;
  z-index: 1050;
}

.gesture-info:hover {
  box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
}

.gesture-info:active {
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}

.gesture-info-handle {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 8px;
  cursor: move;
}

.gesture-text {
  font-size: 16px;
}

.gesture-confidence {
  opacity: 0.8;
  font-size: 14px;
}

.gesture-message-display {
  position: absolute;
  bottom: 80px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  flex-direction: column;
  align-items: center;
  pointer-events: auto;
  z-index: 1050;
  cursor: move;
  user-select: none;
  padding: 10px;
  border-radius: 10px;
  transition: background-color 0.2s ease;
}

.gesture-message-display:hover {
  background-color: rgba(0, 0, 0, 0.05);
}

.gesture-message-handle {
  width: 40px;
  height: 5px;
  background-color: rgba(255, 255, 255, 0.5);
  border-radius: 3px;
  margin-bottom: 10px;
  cursor: move;
}

.gesture-message-container {
  max-width: 80%;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
}

.gesture-message-item {
  background: rgba(255, 92, 138, 0.8);
  backdrop-filter: blur(10px);
  border-radius: 20px;
  padding: 10px 20px;
  color: white;
  font-weight: 600;
  font-size: 18px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
  animation: message-fade-in 0.5s ease forwards;
  opacity: 1;
  transform: translateY(0);
  transition: opacity 1s ease, transform 1s ease;
}

.gesture-message-item.fade-out {
  opacity: 0;
  transform: translateY(20px);
}

/* Floating action button for mobile */
.gesture-fab {
  position: fixed;
  bottom: 80px;
  right: 20px;
  z-index: 1050;
  pointer-events: auto;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.gesture-fab-btn {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
  backdrop-filter: blur(10px);
  border: none;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 24px;
  color: white;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
  transition: all 0.2s ease;
}

.gesture-fab-btn.active {
  background: #ff5c8a;
  box-shadow: 0 4px 10px rgba(255, 92, 138, 0.3);
}

@keyframes message-fade-in {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Mobile adjustments */
@media (max-width: 768px) {
  .gesture-info {
    bottom: 80px;
  }
  
  .gesture-message-display {
    bottom: 140px;
  }
}
</style>
