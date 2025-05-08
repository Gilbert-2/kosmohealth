import axios from 'axios';
import * as faceapi from 'face-api.js';

class KycService {
    constructor() {
        this.modelsLoaded = false;
        this.isProcessing = false;
        this.verificationStatus = null; // null, 'pending', 'verified', 'failed'
        this.verificationDetails = null;
        this.documentVerificationStatus = null;
        this.livenessStatus = null;
        this.faceDescriptor = null;
        this.idCardImage = null;
        this.selfieImage = null;
        this.onStatusChangeCallback = null;
        this.verificationSession = null;
        this.verificationSteps = {
            documentUpload: false,
            documentVerification: false,
            livenessCheck: false,
            faceMatch: false
        };
    }

    /**
     * Initialize face-api.js models and start verification session
     */
    async initialize() {
        if (this.modelsLoaded) return true;

        try {
            // Load models from public directory
            const MODEL_URL = '/js/face-api-models';
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
                faceapi.nets.faceExpressionNet.loadFromUri(MODEL_URL)
            ]);

            this.modelsLoaded = true;
            console.log('KYC: Face-api models loaded successfully');

            // Start verification session
            await this.startVerificationSession();
            return true;
        } catch (error) {
            console.error('KYC: Error initializing:', error);
            this.updateStatus('failed', {
                step: 'initialization',
                message: 'Failed to initialize verification system.'
            });
            return false;
        }
    }

    /**
     * Start a new verification session
     */
    async startVerificationSession() {
        try {
            const response = await axios.post('/api/kyc/session/start');
            this.verificationSession = response.data;
            this.updateStatus('pending', {
                step: 'session_started',
                message: 'Verification session started.',
                sessionId: this.verificationSession.id
            });
            
            // Start polling for status updates
            this.startStatusPolling();
            return true;
        } catch (error) {
            console.error('KYC: Error starting session:', error);
            throw new Error('Failed to start verification session');
        }
    }

    /**
     * Upload and verify identity document
     * @param {File} documentFile - The identity document image file
     */
    async verifyDocument(documentFile) {
        try {
            this.updateStatus('pending', {
                step: 'document_verification',
                message: 'Uploading and verifying document...'
            });

            const formData = new FormData();
            formData.append('document', documentFile);
            formData.append('session_id', this.verificationSession.id);

            const response = await axios.post('/api/kyc/document/verify', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });

            this.documentVerificationStatus = response.data.status;
            this.verificationSteps.documentUpload = true;
            this.verificationSteps.documentVerification = response.data.status === 'verified';

            this.updateStatus('pending', {
                step: 'document_verification',
                message: 'Document verification completed.',
                documentStatus: response.data.status
            });

            return response.data;
        } catch (error) {
            console.error('KYC: Document verification error:', error);
            this.updateStatus('failed', {
                step: 'document_verification',
                message: 'Failed to verify document. Please try again.'
            });
            throw error;
        }
    }

    /**
     * Perform liveness detection check
     * @param {HTMLVideoElement} videoElement - Live video feed element
     */
    async performLivenessCheck(videoElement) {
        try {
            this.updateStatus('pending', {
                step: 'liveness_check',
                message: 'Performing liveness check...'
            });

            // Perform random gesture checks
            const gestures = ['blink', 'turn_left', 'turn_right', 'smile'];
            const requiredGestures = this.getRandomGestures(gestures, 2);
            const results = [];
            
            for (const gesture of requiredGestures) {
                const isValid = await this.verifyGesture(videoElement, gesture);
                if (isValid) {
                    results.push({ gesture, success: true });
                }
            }

            this.livenessStatus = results.length === requiredGestures.length ? 'verified' : 'failed';
            this.verificationSteps.livenessCheck = this.livenessStatus === 'verified';

            this.updateStatus(this.livenessStatus === 'verified' ? 'pending' : 'failed', {
                step: 'liveness_check',
                message: this.livenessStatus === 'verified' ? 
                    'Liveness check completed successfully.' : 
                    'Liveness check failed. Please try again.'
            });

            return this.livenessStatus === 'verified';
        } catch (error) {
            console.error('KYC: Liveness check error:', error);
            this.updateStatus('failed', {
                step: 'liveness_check',
                message: 'Liveness check failed. Please try again.'
            });
            throw error;
        }
    }

    /**
     * Verify specific gesture for liveness check
     */
    async verifyGesture(videoElement, gesture) {
        return new Promise((resolve, reject) => {
            let attempts = 0;
            const maxAttempts = 50; // 5 seconds at 10 fps
            
            const checkGesture = async () => {
                if (attempts >= maxAttempts) {
                    reject(new Error(`Failed to detect ${gesture} gesture`));
                    return;
                }

                try {
                    const detection = await faceapi
                        .detectSingleFace(videoElement, new faceapi.TinyFaceDetectorOptions())
                        .withFaceLandmarks()
                        .withFaceExpressions();

                    if (detection) {
                        const isValid = this.validateGesture(detection, gesture);
                        if (isValid) {
                            resolve(true);
                            return;
                        }
                    }

                    attempts++;
                    requestAnimationFrame(checkGesture);
                } catch (error) {
                    reject(error);
                }
            };

            checkGesture();
        });
    }

    /**
     * Validate detected gesture against required gesture
     */
    validateGesture(detection, gesture) {
        switch (gesture) {
            case 'blink':
                // Check for eye aspect ratio
                const landmarks = detection.landmarks;
                const leftEye = landmarks.getLeftEye();
                const rightEye = landmarks.getRightEye();
                const jawline = detection.landmarks.getJawOutline();
                const rotation = this.calculateFaceRotation(jawline);
                return gesture === 'turn_left' ? rotation < -15 : rotation > 15;

            default:
                return false;
        }
    }

    /**
     * Helper method to get random gestures for liveness check
     */
    getRandomGestures(gestures, count) {
        const shuffled = [...gestures].sort(() => 0.5 - Math.random());
        return shuffled.slice(0, count);
    }

    /**
     * Initialize face-api.js models
     */
    async loadModels() {
        if (this.modelsLoaded) return true;

        try {
            // Load models from public directory
            const MODEL_URL = '/js/face-api-models';
            
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL),
                faceapi.nets.faceRecognitionNet.loadFromUri(MODEL_URL),
                faceapi.nets.faceExpressionNet.loadFromUri(MODEL_URL)
            ]);
            
            this.modelsLoaded = true;
            console.log('KYC: Face-api models loaded successfully');
            return true;
        } catch (error) {
            console.error('KYC: Error loading face-api models:', error);
            return false;
        }
    }

    /**
     * Set callback for status changes
     * @param {Function} callback - Function to call with status updates
     */
    onStatusChange(callback) {
        this.onStatusChangeCallback = callback;
    }

    /**
     * Update verification status
     * @param {string} status - New status
     * @param {Object} details - Additional details
     */
    updateStatus(status, details = {}) {
        this.verificationStatus = status;
        this.verificationDetails = details;
        
        if (this.onStatusChangeCallback) {
            this.onStatusChangeCallback({
                status,
                details,
                steps: this.verificationSteps
            });
        }
    }

    /**
     * Capture ID card image
     * @param {HTMLImageElement|File|Blob|string} image - ID card image
     */
    async captureIdCard(image) {
        try {
            this.updateStatus('pending', { step: 'id_card', message: 'Processing ID card...' });
            
            // Convert image to usable format if needed
            const processedImage = await this.processImage(image);
            this.idCardImage = processedImage;
            
            // Extract face from ID card
            const idCardFace = await this.extractFaceFromImage(processedImage);
            
            if (!idCardFace) {
                this.updateStatus('failed', { 
                    step: 'id_card', 
                    message: 'No face detected in ID card. Please try again with a clearer image.' 
                });
                return false;
            }
            
            // Store face descriptor for later comparison
            this.idCardFaceDescriptor = idCardFace.descriptor;
            
            this.updateStatus('pending', { 
                step: 'id_card_complete', 
                message: 'ID card processed successfully. Please proceed to selfie verification.' 
            });
            
            return true;
        } catch (error) {
            console.error('KYC: Error processing ID card:', error);
            this.updateStatus('failed', { 
                step: 'id_card', 
                message: 'Error processing ID card. Please try again.' 
            });
            return false;
        }
    }

    /**
     * Capture selfie image
     * @param {HTMLImageElement|File|Blob|string} image - Selfie image
     */
    async captureSelfie(image) {
        try {
            this.updateStatus('pending', { step: 'selfie', message: 'Processing selfie...' });
            
            // Convert image to usable format if needed
            const processedImage = await this.processImage(image);
            this.selfieImage = processedImage;
            
            // Extract face from selfie
            const selfieFace = await this.extractFaceFromImage(processedImage);
            
            if (!selfieFace) {
                this.updateStatus('failed', { 
                    step: 'selfie', 
                    message: 'No face detected in selfie. Please try again with a clearer image.' 
                });
                return false;
            }
            
            // Store face descriptor
            this.selfieFaceDescriptor = selfieFace.descriptor;
            
            // Compare faces if ID card face is available
            if (this.idCardFaceDescriptor) {
                const matchResult = await this.compareFaces(this.idCardFaceDescriptor, this.selfieFaceDescriptor);
                
                if (matchResult.isMatch) {
                    this.updateStatus('pending', { 
                        step: 'selfie_complete', 
                        message: 'Selfie verification successful. Proceeding to final verification.',
                        similarity: matchResult.similarity
                    });
                    return true;
                } else {
                    this.updateStatus('failed', { 
                        step: 'selfie', 
                        message: 'Face in selfie does not match ID card. Please try again.',
                        similarity: matchResult.similarity
                    });
                    return false;
                }
            } else {
                this.updateStatus('pending', { 
                    step: 'selfie_complete', 
                    message: 'Selfie processed successfully. Please complete ID card verification.' 
                });
                return true;
            }
        } catch (error) {
            console.error('KYC: Error processing selfie:', error);
            this.updateStatus('failed', { 
                step: 'selfie', 
                message: 'Error processing selfie. Please try again.' 
            });
            return false;
        }
    }

    /**
     * Process image to usable format
     * @param {HTMLImageElement|File|Blob|string} image - Image to process
     * @returns {HTMLImageElement} - Processed image
     */
    async processImage(image) {
        // If image is already an HTMLImageElement, return it
        if (image instanceof HTMLImageElement) {
            return image;
        }
        
        // If image is a File or Blob, convert to data URL
        if (image instanceof File || image instanceof Blob) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const img = new Image();
                    img.onload = () => resolve(img);
                    img.onerror = reject;
                    img.src = e.target.result;
                };
                reader.onerror = reject;
                reader.readAsDataURL(image);
            });
        }
        
        // If image is a string (URL or data URL), load it
        if (typeof image === 'string') {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.crossOrigin = 'anonymous';
                img.onload = () => resolve(img);
                img.onerror = reject;
                img.src = image;
            });
        }
        
        throw new Error('Unsupported image format');
    }

    /**
     * Extract face from image
     * @param {HTMLImageElement} image - Image to extract face from
     * @returns {Object|null} - Face data or null if no face detected
     */
    async extractFaceFromImage(image) {
        if (!this.modelsLoaded) {
            await this.loadModels();
        }
        
        try {
            // Detect face with landmarks and descriptors
            const detections = await faceapi.detectSingleFace(image, new faceapi.TinyFaceDetectorOptions())
                .withFaceLandmarks()
                .withFaceDescriptor();
            
            if (!detections) {
                return null;
            }
            
            return {
                detection: detections.detection,
                landmarks: detections.landmarks,
                descriptor: detections.descriptor
            };
        } catch (error) {
            console.error('KYC: Error extracting face:', error);
            return null;
        }
    }

    /**
     * Compare two face descriptors
     * @param {Float32Array} descriptor1 - First face descriptor
     * @param {Float32Array} descriptor2 - Second face descriptor
     * @returns {Object} - Comparison result
     */
    async compareFaces(descriptor1, descriptor2) {
        // Calculate Euclidean distance between descriptors
        const distance = faceapi.euclideanDistance(descriptor1, descriptor2);
        
        // Convert distance to similarity (0-100%)
        const similarity = Math.max(0, Math.min(100, (1 - distance) * 100));
        
        // Determine if faces match (threshold can be adjusted)
        const threshold = 0.6; // 60% similarity threshold
        const isMatch = similarity / 100 >= threshold;
        
        return {
            distance,
            similarity: Math.round(similarity),
            isMatch
        };
    }

    /**
     * Complete KYC verification
     * @param {Object} userData - User data for verification
     */
    async completeVerification(userData) {
        try {
            this.updateStatus('pending', { 
                step: 'verification', 
                message: 'Submitting verification data...' 
            });
            
            // Ensure we have both images
            if (!this.idCardImage || !this.selfieImage) {
                this.updateStatus('failed', { 
                    step: 'verification', 
                    message: 'Missing required verification images. Please complete all steps.' 
                });
                return false;
            }
            
            // Prepare verification data
            const verificationData = {
                user_data: userData,
                face_match: {
                    is_match: true,
                    similarity: 85 // Example value
                }
            };
            
            // In a real implementation, you would upload images to server
            // For this example, we'll simulate a server response
            
            // Simulate API call
            await new Promise(resolve => setTimeout(resolve, 2000));
            
            // Simulate successful verification
            this.updateStatus('verified', { 
                step: 'complete', 
                message: 'Verification completed successfully!',
                verification_id: 'KYC-' + Math.random().toString(36).substr(2, 9).toUpperCase()
            });
            
            return true;
        } catch (error) {
            console.error('KYC: Error completing verification:', error);
            this.updateStatus('failed', { 
                step: 'verification', 
                message: 'Error submitting verification. Please try again.' 
            });
            return false;
        }
    }

    /**
     * Reset verification process
     */
    reset() {
        this.verificationStatus = null;
        this.verificationDetails = null;
        this.idCardImage = null;
        this.selfieImage = null;
        this.idCardFaceDescriptor = null;
        this.selfieFaceDescriptor = null;
        
        this.updateStatus(null, { message: 'Verification reset' });
    }

    /**
     * Get current verification status
     */
    getStatus() {
        return {
            status: this.verificationStatus,
            details: this.verificationDetails
        };
    }
}

export default new KycService();
