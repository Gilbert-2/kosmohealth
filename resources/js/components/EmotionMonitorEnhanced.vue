<template>
  <div class="emotion-monitor" v-if="isDoctor && showMonitor">
    <div class="emotion-monitor-header">
      <h3 class="emotion-monitor-title">Patient Emotion Monitor</h3>
      <div class="emotion-monitor-controls">
        <div class="emotion-source-toggle">
          <button 
            class="emotion-source-btn" 
            :class="{ active: detectionMode === 'face' }" 
            @click="setDetectionMode('face')"
            title="Face detection"
          >
            <i class="fas fa-video"></i>
          </button>
          <button 
            class="emotion-source-btn" 
            :class="{ active: detectionMode === 'audio' }" 
            @click="setDetectionMode('audio')"
            title="Audio detection"
          >
            <i class="fas fa-microphone"></i>
          </button>
          <button 
            class="emotion-source-btn" 
            :class="{ active: detectionMode === 'both' }" 
            @click="setDetectionMode('both')"
            title="Combined detection"
          >
            <i class="fas fa-video"></i>+<i class="fas fa-microphone"></i>
          </button>
        </div>
        <button class="emotion-monitor-close" @click="toggleMonitor">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>
    
    <div class="emotion-monitor-content">
      <div class="emotion-current">
        <div class="emotion-label">Current Emotion:</div>
        <div class="emotion-value" :class="emotionClass">
          {{ currentEmotion ? currentEmotion.dominant : 'Neutral' }}
          <span v-if="currentEmotion" class="emotion-score">
            {{ Math.round(currentEmotion.score * 100) }}%
          </span>
          <span v-if="currentEmotion && currentEmotion.source" class="emotion-source">
            <i :class="sourceIcon"></i>
          </span>
        </div>
      </div>
      
      <div class="emotion-history">
        <div class="emotion-label">Emotion History:</div>
        <div class="emotion-chart">
          <div 
            v-for="(emotion, index) in emotionHistory" 
            :key="index"
            class="emotion-bar"
            :class="getEmotionClass(emotion.dominant)"
            :style="{ height: `${emotion.score * 100}%` }"
            :title="`${emotion.dominant} (${Math.round(emotion.score * 100)}%) - ${emotion.source || 'face'}`"
          >
            <div v-if="emotion.source" class="emotion-bar-source" :class="`source-${emotion.source}`"></div>
          </div>
        </div>
      </div>
      
      <div class="emotion-stats">
        <div class="emotion-stat" v-for="(value, emotion) in emotionStats" :key="emotion">
          <div class="emotion-stat-label">{{ emotion }}:</div>
          <div class="emotion-stat-value">{{ value }}%</div>
          <div class="emotion-stat-bar" :style="{ width: `${value}%` }"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import EmotionDetectionService from '../services/EmotionDetectionService';

export default {
  name: 'EmotionMonitorEnhanced',
  
  props: {
    currentEmotion: {
      type: Object,
      default: null
    }
  },
  
  data() {
    return {
      isDoctor: false,
      showMonitor: true,
      emotionHistory: [],
      emotionHistoryMaxLength: 20,
      emotionStats: {
        happy: 0,
        sad: 0,
        angry: 0,
        fearful: 0,
        disgusted: 0,
        surprised: 0,
        neutral: 0
      },
      detectionMode: 'face' // 'face', 'audio', or 'both'
    };
  },
  
  computed: {
    emotionClass() {
      if (!this.currentEmotion) return '';
      
      return `emotion-${this.currentEmotion.dominant}`;
    },
    
    sourceIcon() {
      if (!this.currentEmotion || !this.currentEmotion.source) return '';
      
      switch (this.currentEmotion.source) {
        case 'face':
          return 'fas fa-video';
        case 'audio':
          return 'fas fa-microphone';
        case 'combined':
          return 'fas fa-layer-group';
        default:
          return '';
      }
    }
  },
  
  watch: {
    currentEmotion(newEmotion) {
      if (newEmotion) {
        this.updateEmotionHistory(newEmotion);
        this.updateEmotionStats();
      }
    }
  },
  
  mounted() {
    // Check if user is a doctor
    this.checkUserRole();
    
    // Initialize with current detection mode
    this.detectionMode = EmotionDetectionService.getDetectionMode();
  },
  
  methods: {
    checkUserRole() {
      // Check if user has doctor role
      if (this.$store.getters['user/hasRole']) {
        this.isDoctor = this.$store.getters['user/hasRole']('doctor');
      }
    },
    
    toggleMonitor() {
      this.showMonitor = !this.showMonitor;
    },
    
    setDetectionMode(mode) {
      this.detectionMode = mode;
      EmotionDetectionService.setDetectionMode(mode);
      
      // Emit event to notify parent components
      this.$emit('detection-mode-changed', mode);
    },
    
    updateEmotionHistory(emotion) {
      // Add current emotion to history
      this.emotionHistory.push({
        dominant: emotion.dominant,
        score: emotion.score,
        source: emotion.source || 'face',
        timestamp: new Date()
      });
      
      // Limit history length
      if (this.emotionHistory.length > this.emotionHistoryMaxLength) {
        this.emotionHistory.shift();
      }
    },
    
    updateEmotionStats() {
      if (this.emotionHistory.length === 0) return;
      
      // Reset stats
      for (const emotion in this.emotionStats) {
        this.emotionStats[emotion] = 0;
      }
      
      // Count occurrences of each emotion
      const counts = {};
      this.emotionHistory.forEach(entry => {
        counts[entry.dominant] = (counts[entry.dominant] || 0) + 1;
      });
      
      // Calculate percentages
      const total = this.emotionHistory.length;
      for (const emotion in counts) {
        this.emotionStats[emotion] = Math.round((counts[emotion] / total) * 100);
      }
    },
    
    getEmotionClass(emotion) {
      return `emotion-${emotion}`;
    }
  }
};
</script>

<style scoped>
.emotion-monitor {
  position: fixed;
  top: 20px;
  right: 20px;
  width: 300px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  overflow: hidden;
  transition: all 0.3s ease;
}

.emotion-monitor-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px;
  background: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
}

.emotion-monitor-title {
  margin: 0;
  font-size: 16px;
  font-weight: 600;
}

.emotion-monitor-controls {
  display: flex;
  align-items: center;
  gap: 10px;
}

.emotion-source-toggle {
  display: flex;
  background: #e9ecef;
  border-radius: 20px;
  padding: 3px;
}

.emotion-source-btn {
  background: none;
  border: none;
  border-radius: 17px;
  padding: 5px 8px;
  font-size: 12px;
  cursor: pointer;
  transition: all 0.2s ease;
  color: #6c757d;
}

.emotion-source-btn.active {
  background: white;
  color: #4361ee;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.emotion-monitor-close {
  background: none;
  border: none;
  color: #6c757d;
  cursor: pointer;
  font-size: 16px;
  padding: 0;
  transition: all 0.2s ease;
}

.emotion-monitor-close:hover {
  color: #343a40;
}

.emotion-monitor-content {
  padding: 15px;
}

.emotion-current {
  margin-bottom: 20px;
}

.emotion-label {
  font-size: 14px;
  font-weight: 600;
  margin-bottom: 5px;
  color: #495057;
}

.emotion-value {
  font-size: 18px;
  font-weight: 700;
  padding: 8px 12px;
  border-radius: 6px;
  background: #f8f9fa;
  display: inline-flex;
  align-items: center;
  gap: 5px;
}

.emotion-score {
  font-size: 14px;
  opacity: 0.7;
}

.emotion-source {
  font-size: 12px;
  opacity: 0.7;
  margin-left: 5px;
}

.emotion-history {
  margin-bottom: 20px;
}

.emotion-chart {
  height: 100px;
  display: flex;
  align-items: flex-end;
  gap: 2px;
  background: #f8f9fa;
  border-radius: 6px;
  padding: 10px;
}

.emotion-bar {
  flex: 1;
  background: #adb5bd;
  border-radius: 2px 2px 0 0;
  min-height: 1px;
  transition: height 0.3s ease;
  position: relative;
}

.emotion-bar-source {
  position: absolute;
  top: -5px;
  left: 50%;
  transform: translateX(-50%);
  width: 6px;
  height: 6px;
  border-radius: 50%;
}

.source-face {
  background-color: #4361ee;
}

.source-audio {
  background-color: #ff6b6b;
}

.source-combined {
  background-color: #7950f2;
}

.emotion-stats {
  background: #f8f9fa;
  border-radius: 6px;
  padding: 10px;
}

.emotion-stat {
  margin-bottom: 8px;
  position: relative;
}

.emotion-stat-label {
  font-size: 12px;
  font-weight: 600;
  text-transform: capitalize;
  margin-bottom: 2px;
}

.emotion-stat-value {
  font-size: 12px;
  position: absolute;
  right: 0;
  top: 0;
}

.emotion-stat-bar {
  height: 6px;
  background: #adb5bd;
  border-radius: 3px;
  transition: width 0.3s ease;
}

/* Emotion colors */
.emotion-happy {
  color: #4caf50;
}
.emotion-happy .emotion-stat-bar,
.emotion-bar.emotion-happy {
  background-color: #4caf50;
}

.emotion-sad {
  color: #2196f3;
}
.emotion-sad .emotion-stat-bar,
.emotion-bar.emotion-sad {
  background-color: #2196f3;
}

.emotion-angry {
  color: #f44336;
}
.emotion-angry .emotion-stat-bar,
.emotion-bar.emotion-angry {
  background-color: #f44336;
}

.emotion-fearful {
  color: #9c27b0;
}
.emotion-fearful .emotion-stat-bar,
.emotion-bar.emotion-fearful {
  background-color: #9c27b0;
}

.emotion-disgusted {
  color: #795548;
}
.emotion-disgusted .emotion-stat-bar,
.emotion-bar.emotion-disgusted {
  background-color: #795548;
}

.emotion-surprised {
  color: #ff9800;
}
.emotion-surprised .emotion-stat-bar,
.emotion-bar.emotion-surprised {
  background-color: #ff9800;
}

.emotion-neutral {
  color: #9e9e9e;
}
.emotion-neutral .emotion-stat-bar,
.emotion-bar.emotion-neutral {
  background-color: #9e9e9e;
}
</style>
