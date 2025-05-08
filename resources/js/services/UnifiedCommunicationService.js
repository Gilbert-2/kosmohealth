import FaceProcessingService from './FaceProcessingService';
import AudioEmotionService from './AudioEmotionService';
import GestureRecognitionService from './GestureRecognitionService';
import EmotionDetectionService from './EmotionDetectionService';

class UnifiedCommunicationService {
    constructor() {
        this.faceProcessingService = FaceProcessingService;
        this.audioEmotionService = AudioEmotionService;
        this.gestureRecognitionService = GestureRecognitionService;
        this.emotionDetectionService = EmotionDetectionService;
        
        this.isInitialized = false;
        this.communicationMode = 'standard'; // 'standard', 'gesture', 'audio-only'
        this.isProcessing = false;
        this.processingInterval = null;
        
        // Callbacks
        this.onEmotionDetectedCallback = null;
        this.onGestureDetectedCallback = null;
        this.onDiscomfortDetectedCallback = null;
        this.onCommunicationModeChangedCallback = null;
        
        // Message history for the current session
        this.messageHistory = [];
        this.messageHistoryMaxLength = 50;
    }

    /**
     * Initialize all services
     */
    async initialize() {
        if (this.isInitialized) return true;

        try {
            // Initialize all services in parallel
            const [
                faceProcessingInitialized,
                audioEmotionInitialized,
                gestureRecognitionInitialized,
                emotionDetectionInitialized
            ] = await Promise.all([
                this.faceProcessingService.loadModels(),
                this.audioEmotionService.initialize(),
                this.gestureRecognitionService.loadModels(),
                this.emotionDetectionService.initialize()
            ]);
            
            // Set up callbacks
            this.faceProcessingService.onEmotionDetected(this.handleEmotionDetected.bind(this));
            this.faceProcessingService.onDiscomfortDetected(this.handleDiscomfortDetected.bind(this));
            
            this.audioEmotionService.onEmotionDetected(this.handleEmotionDetected.bind(this));
            this.audioEmotionService.onDiscomfortDetected(this.handleDiscomfortDetected.bind(this));
            
            this.gestureRecognitionService.onGestureDetected(this.handleGestureDetected.bind(this));
            
            this.emotionDetectionService.onEmotionDetected(this.handleEmotionDetected.bind(this));
            this.emotionDetectionService.onDiscomfortDetected(this.handleDiscomfortDetected.bind(this));
            
            this.isInitialized = faceProcessingInitialized && 
                                audioEmotionInitialized && 
                                gestureRecognitionInitialized &&
                                emotionDetectionInitialized;
            
            console.log('Unified Communication Service initialized successfully');
            return this.isInitialized;
        } catch (error) {
            console.error('Error initializing Unified Communication Service:', error);
            return false;
        }
    }

    /**
     * Set the communication mode
     * @param {string} mode - 'standard', 'gesture', or 'audio-only'
     */
    setCommunicationMode(mode) {
        if (!['standard', 'gesture', 'audio-only'].includes(mode)) {
            console.error('Invalid communication mode. Must be "standard", "gesture", or "audio-only".');
            return;
        }
        
        this.communicationMode = mode;
        
        // Update services based on mode
        switch (mode) {
            case 'standard':
                this.emotionDetectionService.setDetectionMode('both'); // Use both face and audio
                break;
                
            case 'gesture':
                this.emotionDetectionService.setDetectionMode('face'); // Focus on face for gestures
                break;
                
            case 'audio-only':
                this.emotionDetectionService.setDetectionMode('audio'); // Audio only
                break;
        }
        
        // Notify about mode change
        if (this.onCommunicationModeChangedCallback) {
            this.onCommunicationModeChangedCallback(mode);
        }
        
        console.log(`Communication mode set to: ${mode}`);
    }

    /**
     * Start processing with all enabled services
     * @param {HTMLVideoElement} videoElement - Video element for processing
     * @param {HTMLCanvasElement} outputCanvas - Canvas for rendering
     * @param {number} fps - Frames per second
     */
    async startProcessing(videoElement, outputCanvas, fps = 5) {
        if (!this.isInitialized) {
            await this.initialize();
        }
        
        if (this.isProcessing) return;
        
        this.isProcessing = true;
        
        // Start appropriate services based on communication mode
        switch (this.communicationMode) {
            case 'standard':
                this.emotionDetectionService.startDetection(videoElement, outputCanvas, fps);
                break;
                
            case 'gesture':
                this.gestureRecognitionService.startProcessing(videoElement, outputCanvas, fps);
                this.emotionDetectionService.startDetection(videoElement, null, fps);
                break;
                
            case 'audio-only':
                this.audioEmotionService.startProcessing();
                break;
        }
        
        console.log(`Started processing in ${this.communicationMode} mode`);
    }

    /**
     * Stop all processing
     */
    stopProcessing() {
        this.faceProcessingService.stopProcessing();
        this.audioEmotionService.stopProcessing();
        this.gestureRecognitionService.stopProcessing();
        this.emotionDetectionService.stopDetection();
        
        this.isProcessing = false;
        console.log('Stopped all processing');
    }

    /**
     * Handle emotion detection from any source
     */
    handleEmotionDetected(emotion) {
        if (this.onEmotionDetectedCallback) {
            this.onEmotionDetectedCallback(emotion);
        }
    }

    /**
     * Handle gesture detection
     */
    handleGestureDetected(gesture) {
        // Add gesture to message history
        this.addToMessageHistory({
            type: 'gesture',
            content: gesture.text,
            gesture: gesture.name,
            confidence: gesture.confidence,
            timestamp: new Date()
        });
        
        if (this.onGestureDetectedCallback) {
            this.onGestureDetectedCallback(gesture);
        }
    }

    /**
     * Handle discomfort detection from any source
     */
    handleDiscomfortDetected(emotion, score, source) {
        if (this.onDiscomfortDetectedCallback) {
            this.onDiscomfortDetectedCallback(emotion, score, source);
        }
    }

    /**
     * Add a message to the history
     */
    addToMessageHistory(message) {
        this.messageHistory.push(message);
        
        if (this.messageHistory.length > this.messageHistoryMaxLength) {
            this.messageHistory.shift();
        }
    }

    /**
     * Get the message history
     */
    getMessageHistory() {
        return this.messageHistory;
    }

    /**
     * Clear the message history
     */
    clearMessageHistory() {
        this.messageHistory = [];
    }

    /**
     * Set callback for emotion detection
     */
    onEmotionDetected(callback) {
        this.onEmotionDetectedCallback = callback;
    }

    /**
     * Set callback for gesture detection
     */
    onGestureDetected(callback) {
        this.onGestureDetectedCallback = callback;
    }

    /**
     * Set callback for discomfort detection
     */
    onDiscomfortDetected(callback) {
        this.onDiscomfortDetectedCallback = callback;
    }

    /**
     * Set callback for communication mode changes
     */
    onCommunicationModeChanged(callback) {
        this.onCommunicationModeChangedCallback = callback;
    }

    /**
     * Get all available gesture-to-text mappings
     */
    getAllGestureTextMappings() {
        return this.gestureRecognitionService.getAllGestureTextMappings();
    }

    /**
     * Enable or disable face blur
     */
    setBlurEnabled(enabled) {
        this.faceProcessingService.setBlurEnabled(enabled);
        this.emotionDetectionService.setBlurEnabled(enabled);
    }

    /**
     * Set blur intensity
     */
    setBlurIntensity(intensity) {
        this.faceProcessingService.setBlurIntensity(intensity);
        this.emotionDetectionService.setBlurIntensity(intensity);
    }

    /**
     * Get the current communication mode
     */
    getCommunicationMode() {
        return this.communicationMode;
    }
}

// Create singleton instance
const unifiedCommunicationService = new UnifiedCommunicationService();

export default unifiedCommunicationService;
