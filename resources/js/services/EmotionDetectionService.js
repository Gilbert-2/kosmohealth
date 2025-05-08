/**
 * EmotionDetectionService - Unified service for emotion detection from both face and audio
 *
 * This service combines FaceProcessingService and AudioEmotionService to provide
 * comprehensive emotion detection capabilities.
 *
 * Security features:
 * - Only meeting creators can see emotion recognition data
 * - All participants can use face blur functionality
 */

import FaceProcessingService from './FaceProcessingService';
import AudioEmotionService from './AudioEmotionService';
import MeetingPermissionService from './MeetingPermissionService';

class EmotionDetectionService {
    constructor() {
        this.faceProcessingService = FaceProcessingService;
        this.audioEmotionService = AudioEmotionService;
        this.permissionService = MeetingPermissionService;
        this.detectionMode = 'face'; // 'face', 'audio', or 'both'
        this.isInitialized = false;
        this.lastEmotions = null;
        this.onEmotionDetectedCallback = null;
        this.onDiscomfortDetectedCallback = null;
        this.emotionConfidenceThreshold = 0.7;
        this.discomfortEmotions = ['angry', 'fearful', 'disgusted', 'sad'];

        // For combined mode
        this.faceEmotions = null;
        this.audioEmotions = null;
        this.combinedEmotionWeights = {
            face: 0.7,
            audio: 0.3
        };

        // Security settings
        this.emotionRecognitionEnabled = true; // Will be filtered by permissions
        this.blurEnabled = true; // Available to all users
    }

    /**
     * Initialize the emotion detection service
     */
    async initialize() {
        if (this.isInitialized) return true;

        try {
            // Initialize face processing
            await this.faceProcessingService.loadModels();

            // Initialize audio processing
            await this.audioEmotionService.initialize();

            // Set up callbacks
            this.faceProcessingService.onEmotionDetected(this.handleFaceEmotion.bind(this));
            this.faceProcessingService.onDiscomfortDetected(this.handleFaceDiscomfort.bind(this));

            this.audioEmotionService.onEmotionDetected(this.handleAudioEmotion.bind(this));
            this.audioEmotionService.onDiscomfortDetected(this.handleAudioDiscomfort.bind(this));

            this.isInitialized = true;
            console.log('Emotion detection service initialized successfully');
            return true;
        } catch (error) {
            console.error('Error initializing emotion detection service:', error);
            return false;
        }
    }

    /**
     * Set the detection mode
     * @param {string} mode - 'face', 'audio', or 'both'
     */
    setDetectionMode(mode) {
        if (!['face', 'audio', 'both'].includes(mode)) {
            console.error('Invalid detection mode. Must be "face", "audio", or "both".');
            return;
        }

        this.detectionMode = mode;

        // Update services based on mode
        this.faceProcessingService.setEmotionDetectionEnabled(mode === 'face' || mode === 'both');
        this.audioEmotionService.setEmotionDetectionEnabled(mode === 'audio' || mode === 'both');

        console.log(`Detection mode set to: ${mode}`);
    }

    /**
     * Start emotion detection
     * @param {HTMLVideoElement} videoElement - Video element for face detection (optional)
     * @param {HTMLCanvasElement} outputCanvas - Canvas for rendering face detection (optional)
     * @param {number} fps - Frames per second for face detection (optional)
     */
    async startDetection(videoElement = null, outputCanvas = null, fps = 3) {
        if (!this.isInitialized) {
            await this.initialize();
        }

        if (this.detectionMode === 'face' || this.detectionMode === 'both') {
            if (!videoElement) {
                console.error('Video element is required for face detection mode');
                return false;
            }

            this.faceProcessingService.startProcessing(videoElement, outputCanvas, fps);
        }

        if (this.detectionMode === 'audio' || this.detectionMode === 'both') {
            await this.audioEmotionService.startProcessing();
        }

        return true;
    }

    /**
     * Stop emotion detection
     */
    stopDetection() {
        this.faceProcessingService.stopProcessing();
        this.audioEmotionService.stopProcessing();
    }

    /**
     * Handle emotion detected from face
     */
    handleFaceEmotion(emotion) {
        this.faceEmotions = emotion;

        // Only process emotions if user has permission to see them
        if (!this.permissionService.canSeeEmotions()) {
            return;
        }

        if (this.detectionMode === 'face') {
            this.lastEmotions = emotion;

            if (this.onEmotionDetectedCallback) {
                this.onEmotionDetectedCallback({
                    ...emotion,
                    source: 'face'
                });
            }
        } else if (this.detectionMode === 'both') {
            this.combineEmotions();
        }
    }

    /**
     * Handle emotion detected from audio
     */
    handleAudioEmotion(emotion) {
        this.audioEmotions = emotion;

        // Only process emotions if user has permission to see them
        if (!this.permissionService.canSeeEmotions()) {
            return;
        }

        if (this.detectionMode === 'audio') {
            this.lastEmotions = emotion;

            if (this.onEmotionDetectedCallback) {
                this.onEmotionDetectedCallback({
                    ...emotion,
                    source: 'audio'
                });
            }
        } else if (this.detectionMode === 'both') {
            this.combineEmotions();
        }
    }

    /**
     * Combine emotions from face and audio
     */
    combineEmotions() {
        // Only process emotions if user has permission to see them
        if (!this.permissionService.canSeeEmotions()) {
            return;
        }

        if (!this.faceEmotions || !this.audioEmotions) return;

        // Combine all emotion scores
        const combinedScores = {};

        // Initialize with all emotions
        const allEmotions = [...new Set([
            ...Object.keys(this.faceEmotions.all),
            ...Object.keys(this.audioEmotions.all)
        ])];

        // Calculate weighted scores
        for (const emotion of allEmotions) {
            const faceScore = this.faceEmotions.all[emotion] || 0;
            const audioScore = this.audioEmotions.all[emotion] || 0;

            combinedScores[emotion] = (
                faceScore * this.combinedEmotionWeights.face +
                audioScore * this.combinedEmotionWeights.audio
            );
        }

        // Find dominant emotion
        let dominantEmotion = null;
        let highestScore = 0;

        for (const [emotion, score] of Object.entries(combinedScores)) {
            if (score > highestScore) {
                highestScore = score;
                dominantEmotion = emotion;
            }
        }

        // Create combined emotion object
        const combinedEmotion = {
            dominant: dominantEmotion,
            score: highestScore,
            all: combinedScores,
            sources: {
                face: this.faceEmotions,
                audio: this.audioEmotions
            },
            source: 'combined'
        };

        this.lastEmotions = combinedEmotion;

        if (this.onEmotionDetectedCallback) {
            this.onEmotionDetectedCallback(combinedEmotion);
        }

        // Check for discomfort - this is allowed for all users to enable auto-blur
        if (this.discomfortEmotions.includes(dominantEmotion) && highestScore > this.emotionConfidenceThreshold) {
            if (this.onDiscomfortDetectedCallback) {
                this.onDiscomfortDetectedCallback(dominantEmotion, highestScore);
            }
        }
    }

    /**
     * Handle discomfort detected from face
     * Note: Discomfort detection is allowed for all users to enable auto-blur
     */
    handleFaceDiscomfort(emotion, score) {
        // Discomfort detection is allowed for all users (for auto-blur)
        if (this.detectionMode === 'face' && this.onDiscomfortDetectedCallback) {
            this.onDiscomfortDetectedCallback(emotion, score, 'face');
        }
    }

    /**
     * Handle discomfort detected from audio
     * Note: Discomfort detection is allowed for all users to enable auto-blur
     */
    handleAudioDiscomfort(emotion, score) {
        // Discomfort detection is allowed for all users (for auto-blur)
        if (this.detectionMode === 'audio' && this.onDiscomfortDetectedCallback) {
            this.onDiscomfortDetectedCallback(emotion, score, 'audio');
        }
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

    /**
     * Set weights for combined emotion detection
     */
    setCombinedWeights(faceWeight, audioWeight) {
        const total = faceWeight + audioWeight;
        this.combinedEmotionWeights = {
            face: faceWeight / total,
            audio: audioWeight / total
        };
    }

    /**
     * Enable or disable face blur
     */
    setBlurEnabled(enabled) {
        this.faceProcessingService.setBlurEnabled(enabled);
    }

    /**
     * Set blur intensity
     */
    setBlurIntensity(intensity) {
        this.faceProcessingService.setBlurIntensity(intensity);
    }

    /**
     * Get the current detection mode
     */
    getDetectionMode() {
        return this.detectionMode;
    }

    /**
     * Get the last detected emotions
     */
    getLastEmotions() {
        // Only return emotions if user has permission to see them
        if (!this.permissionService.canSeeEmotions()) {
            return null;
        }
        return this.lastEmotions;
    }

    /**
     * Set meeting data to update permissions
     * @param {Object} meetingData - Meeting data from the API
     * @param {Object} userData - Current user data
     */
    setMeetingData(meetingData, userData) {
        // Update permissions based on meeting data
        this.permissionService.initialize(meetingData, userData);

        console.log('Emotion detection permissions updated:', {
            canSeeEmotions: this.permissionService.canSeeEmotions(),
            canBlurFace: this.permissionService.canBlurFace(),
            isMeetingCreator: this.permissionService.isMeetingCreator()
        });
    }
}

// Create singleton instance
const emotionDetectionService = new EmotionDetectionService();

export default emotionDetectionService;
