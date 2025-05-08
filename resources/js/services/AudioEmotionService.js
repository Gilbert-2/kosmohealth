/**
 * AudioEmotionService - Detects emotions from audio input
 * 
 * This service uses the Web Audio API to analyze audio input and detect emotions
 * based on speech patterns, tone, and other audio features.
 */

class AudioEmotionService {
    constructor() {
        this.isInitialized = false;
        this.isProcessing = false;
        this.audioContext = null;
        this.analyzer = null;
        this.microphone = null;
        this.audioData = null;
        this.emotionDetectionEnabled = false;
        this.lastEmotions = null;
        this.emotionThreshold = 0.7;
        this.discomfortEmotions = ['angry', 'fearful', 'disgusted', 'sad'];
        this.onEmotionDetectedCallback = null;
        this.onDiscomfortDetectedCallback = null;
        this.processingInterval = null;
        
        // Audio features
        this.volumeThresholds = {
            low: 0.1,
            medium: 0.3,
            high: 0.6
        };
        
        this.pitchThresholds = {
            low: 100,
            medium: 250,
            high: 400
        };
        
        // Simple emotion mapping based on volume and pitch patterns
        this.emotionPatterns = {
            happy: { volume: 'medium-high', pitch: 'medium-high', variability: 'high' },
            sad: { volume: 'low-medium', pitch: 'low', variability: 'low' },
            angry: { volume: 'high', pitch: 'high', variability: 'high' },
            fearful: { volume: 'low-medium', pitch: 'high', variability: 'high' },
            disgusted: { volume: 'medium', pitch: 'medium-low', variability: 'medium' },
            surprised: { volume: 'high', pitch: 'high', variability: 'high' },
            neutral: { volume: 'medium', pitch: 'medium', variability: 'low' }
        };
    }

    /**
     * Initialize the audio context and analyzer
     */
    async initialize() {
        if (this.isInitialized) return true;

        try {
            // Create audio context
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
            
            // Create analyzer node
            this.analyzer = this.audioContext.createAnalyser();
            this.analyzer.fftSize = 2048;
            this.bufferLength = this.analyzer.frequencyBinCount;
            this.audioData = new Uint8Array(this.bufferLength);
            
            this.isInitialized = true;
            console.log('Audio emotion service initialized successfully');
            return true;
        } catch (error) {
            console.error('Error initializing audio emotion service:', error);
            return false;
        }
    }

    /**
     * Request microphone access and connect to analyzer
     */
    async requestMicrophoneAccess() {
        if (!this.isInitialized) {
            await this.initialize();
        }

        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            this.microphone = this.audioContext.createMediaStreamSource(stream);
            this.microphone.connect(this.analyzer);
            console.log('Microphone connected successfully');
            return true;
        } catch (error) {
            console.error('Error accessing microphone:', error);
            return false;
        }
    }

    /**
     * Enable or disable emotion detection
     */
    setEmotionDetectionEnabled(enabled) {
        this.emotionDetectionEnabled = enabled;
        
        if (enabled && !this.isProcessing) {
            this.startProcessing();
        } else if (!enabled && this.isProcessing) {
            this.stopProcessing();
        }
    }

    /**
     * Start processing audio for emotion detection
     */
    async startProcessing() {
        if (this.isProcessing) return;
        
        if (!this.isInitialized) {
            await this.initialize();
        }
        
        if (!this.microphone) {
            const micAccessGranted = await this.requestMicrophoneAccess();
            if (!micAccessGranted) return;
        }
        
        this.isProcessing = true;
        
        // Process audio at regular intervals
        this.processingInterval = setInterval(() => {
            this.processAudioFrame();
        }, 500); // Process every 500ms
        
        console.log('Audio emotion processing started');
    }

    /**
     * Stop processing audio
     */
    stopProcessing() {
        if (!this.isProcessing) return;
        
        clearInterval(this.processingInterval);
        this.isProcessing = false;
        console.log('Audio emotion processing stopped');
    }

    /**
     * Process a single audio frame for emotion detection
     */
    processAudioFrame() {
        if (!this.isProcessing || !this.analyzer) return;
        
        // Get audio data
        this.analyzer.getByteFrequencyData(this.audioData);
        
        // Extract audio features
        const features = this.extractAudioFeatures();
        
        // Detect emotion based on features
        const emotion = this.detectEmotion(features);
        
        // Process detected emotion
        if (emotion && emotion.score > this.emotionThreshold) {
            this.lastEmotions = emotion;
            
            // Call the emotion callback if set
            if (this.onEmotionDetectedCallback) {
                this.onEmotionDetectedCallback(this.lastEmotions);
            }
            
            // Check if the emotion indicates discomfort
            if (this.discomfortEmotions.includes(emotion.dominant)) {
                if (this.onDiscomfortDetectedCallback) {
                    this.onDiscomfortDetectedCallback(emotion.dominant, emotion.score);
                }
            }
        }
    }

    /**
     * Extract audio features from the current audio frame
     */
    extractAudioFeatures() {
        // Calculate average volume
        let sum = 0;
        let volumeVariability = 0;
        let previousValue = this.audioData[0];
        
        for (let i = 0; i < this.bufferLength; i++) {
            sum += this.audioData[i];
            volumeVariability += Math.abs(this.audioData[i] - previousValue);
            previousValue = this.audioData[i];
        }
        
        const averageVolume = sum / this.bufferLength / 255; // Normalize to 0-1
        volumeVariability = volumeVariability / this.bufferLength / 255;
        
        // Estimate pitch (simplified)
        let maxValue = 0;
        let maxIndex = 0;
        
        for (let i = 0; i < this.bufferLength; i++) {
            if (this.audioData[i] > maxValue) {
                maxValue = this.audioData[i];
                maxIndex = i;
            }
        }
        
        // Convert index to frequency (simplified)
        const estimatedPitch = maxIndex * (this.audioContext.sampleRate / this.analyzer.fftSize);
        
        // Calculate pitch variability (simplified)
        let pitchVariability = 0;
        // This would require tracking pitch over time, simplified for this example
        
        return {
            volume: averageVolume,
            volumeVariability: volumeVariability,
            pitch: estimatedPitch,
            pitchVariability: pitchVariability
        };
    }

    /**
     * Detect emotion based on audio features
     */
    detectEmotion(features) {
        // Categorize volume
        let volumeCategory;
        if (features.volume < this.volumeThresholds.low) {
            volumeCategory = 'low';
        } else if (features.volume < this.volumeThresholds.medium) {
            volumeCategory = 'low-medium';
        } else if (features.volume < this.volumeThresholds.high) {
            volumeCategory = 'medium';
        } else {
            volumeCategory = 'medium-high';
        }
        
        // Categorize pitch
        let pitchCategory;
        if (features.pitch < this.pitchThresholds.low) {
            pitchCategory = 'low';
        } else if (features.pitch < this.pitchThresholds.medium) {
            pitchCategory = 'medium-low';
        } else if (features.pitch < this.pitchThresholds.high) {
            pitchCategory = 'medium';
        } else {
            pitchCategory = 'medium-high';
        }
        
        // Categorize variability
        let variabilityCategory;
        if (features.volumeVariability < 0.1) {
            variabilityCategory = 'low';
        } else if (features.volumeVariability < 0.3) {
            variabilityCategory = 'medium';
        } else {
            variabilityCategory = 'high';
        }
        
        // Match to emotion patterns
        const emotionScores = {};
        
        for (const [emotion, pattern] of Object.entries(this.emotionPatterns)) {
            let score = 0;
            
            // Volume match
            if (pattern.volume.includes(volumeCategory)) {
                score += 0.4;
            }
            
            // Pitch match
            if (pattern.pitch.includes(pitchCategory)) {
                score += 0.4;
            }
            
            // Variability match
            if (pattern.variability === variabilityCategory) {
                score += 0.2;
            }
            
            emotionScores[emotion] = score;
        }
        
        // Find dominant emotion
        let dominantEmotion = null;
        let highestScore = 0;
        
        for (const [emotion, score] of Object.entries(emotionScores)) {
            if (score > highestScore) {
                highestScore = score;
                dominantEmotion = emotion;
            }
        }
        
        return {
            dominant: dominantEmotion,
            score: highestScore,
            all: emotionScores
        };
    }

    /**
     * Set callback for emotion detection
     */
    onEmotionDetected(callback) {
        this.onEmotionDetectedCallback = callback;
    }

    /**
     * Set callback for discomfort detection
     */
    onDiscomfortDetected(callback) {
        this.onDiscomfortDetectedCallback = callback;
    }
}

// Create singleton instance
const audioEmotionService = new AudioEmotionService();

export default audioEmotionService;
