import * as faceapi from 'face-api.js';
import * as tf from '@tensorflow/tfjs';

class GestureRecognitionService {
    constructor() {
        this.modelsLoaded = false;
        this.isProcessing = false;
        this.handLandmarksModel = null;
        this.gestureClassifierModel = null;
        this.lastGesture = null;
        this.gestureConfidenceThreshold = 0.7;
        this.onGestureDetectedCallback = null;
        this.gestureHistory = [];
        this.gestureHistoryMaxLength = 10;
        this.gestureToTextMapping = {
            // Basic gestures
            'thumbs_up': 'Yes / Good',
            'thumbs_down': 'No / Bad',
            'open_palm': 'Stop / Wait',
            'pointing': 'Look at this',
            'fist': 'Attention',
            'victory': 'Peace / Two',
            'ok_sign': 'OK / Good',
            'wave': 'Hello / Goodbye',
            
            // ASL alphabet (simplified subset)
            'asl_a': 'A',
            'asl_b': 'B',
            'asl_c': 'C',
            'asl_d': 'D',
            'asl_e': 'E',
            'asl_f': 'F',
            'asl_g': 'G',
            'asl_h': 'H',
            'asl_i': 'I',
            'asl_j': 'J',
            'asl_k': 'K',
            'asl_l': 'L',
            'asl_m': 'M',
            'asl_n': 'N',
            'asl_o': 'O',
            'asl_p': 'P',
            'asl_q': 'Q',
            'asl_r': 'R',
            'asl_s': 'S',
            'asl_t': 'T',
            'asl_u': 'U',
            'asl_v': 'V',
            'asl_w': 'W',
            'asl_x': 'X',
            'asl_y': 'Y',
            'asl_z': 'Z',
            
            // Medical-specific gestures
            'pain': 'I am in pain',
            'water': 'I need water',
            'medicine': 'I need medicine',
            'bathroom': 'I need to use the bathroom',
            'help': 'I need help',
            'cold': 'I am cold',
            'hot': 'I am hot',
            'tired': 'I am tired',
            'nauseous': 'I feel nauseous',
            'dizzy': 'I feel dizzy',
            'breathing_difficulty': 'I have difficulty breathing'
        };
    }

    /**
     * Initialize TensorFlow.js models for hand gesture recognition
     */
    async loadModels() {
        if (this.modelsLoaded) return true;

        try {
            // Load face-api models first (if not already loaded)
            const MODEL_URL = '/js/face-api-models';
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL)
            ]);

            // Load hand landmark detection model
            this.handLandmarksModel = await tf.loadGraphModel('/js/hand-models/hand_landmark_detection/model.json');
            
            // Load gesture classification model
            this.gestureClassifierModel = await tf.loadLayersModel('/js/hand-models/gesture_classifier/model.json');
            
            this.modelsLoaded = true;
            console.log('Gesture recognition models loaded successfully');
            return true;
        } catch (error) {
            console.error('Error loading gesture recognition models:', error);
            return false;
        }
    }

    /**
     * Set callback for when gestures are detected
     */
    onGestureDetected(callback) {
        this.onGestureDetectedCallback = callback;
    }

    /**
     * Process video frame to detect hand gestures
     * @param {HTMLVideoElement} videoElement - The video element to process
     * @param {HTMLCanvasElement} outputCanvas - Optional canvas to draw the processed frame
     */
    async processFrame(videoElement, outputCanvas = null) {
        if (!this.modelsLoaded || this.isProcessing || !videoElement || videoElement.paused || videoElement.ended) {
            return;
        }

        this.isProcessing = true;

        try {
            // First detect faces to help locate potential hand regions
            const detectionOptions = new faceapi.TinyFaceDetectorOptions({
                inputSize: 320,
                scoreThreshold: 0.3
            });

            const faceDetections = await faceapi.detectAllFaces(
                videoElement,
                detectionOptions
            ).withFaceLandmarks();

            // Process the frame to detect hands
            const videoWidth = videoElement.videoWidth;
            const videoHeight = videoElement.videoHeight;
            
            // Create a tensor from the video frame
            const videoTensor = tf.browser.fromPixels(videoElement);
            
            // Preprocess the tensor for the hand landmark model
            const preprocessedTensor = this.preprocessTensorForHandDetection(videoTensor, videoWidth, videoHeight);
            
            // Run hand landmark detection
            const handLandmarks = await this.detectHandLandmarks(preprocessedTensor);
            
            // If hands are detected, classify the gesture
            if (handLandmarks && handLandmarks.length > 0) {
                const gesture = await this.classifyGesture(handLandmarks[0]);
                
                if (gesture && gesture.confidence > this.gestureConfidenceThreshold) {
                    this.lastGesture = gesture;
                    this.updateGestureHistory(gesture);
                    
                    // Call the gesture callback if set
                    if (this.onGestureDetectedCallback) {
                        this.onGestureDetectedCallback({
                            ...gesture,
                            text: this.gestureToTextMapping[gesture.name] || gesture.name
                        });
                    }
                }
            }
            
            // Draw results on canvas if provided
            if (outputCanvas) {
                this.drawResultsOnCanvas(outputCanvas, videoElement, faceDetections, handLandmarks);
            }
            
            // Clean up tensors
            videoTensor.dispose();
            preprocessedTensor.dispose();
            
        } catch (error) {
            console.error('Error processing video frame for gesture recognition:', error);
        } finally {
            this.isProcessing = false;
        }
    }

    /**
     * Preprocess tensor for hand detection
     */
    preprocessTensorForHandDetection(tensor, width, height) {
        // Resize to the input size expected by the model (typically 256x256)
        return tf.tidy(() => {
            // Normalize pixel values to [0, 1]
            const normalized = tensor.toFloat().div(tf.scalar(255));
            
            // Resize to model input size
            return tf.image.resizeBilinear(normalized, [256, 256]);
        });
    }

    /**
     * Detect hand landmarks in the preprocessed tensor
     */
    async detectHandLandmarks(preprocessedTensor) {
        return tf.tidy(() => {
            // Add batch dimension
            const batchedTensor = preprocessedTensor.expandDims(0);
            
            // Run inference
            const predictions = this.handLandmarksModel.predict(batchedTensor);
            
            // Process predictions to get hand landmarks
            // This is a simplified implementation - actual processing depends on model output format
            const landmarksArray = predictions.arraySync()[0];
            
            // Check if any hand is detected (confidence threshold)
            if (landmarksArray[0] > 0.5) {
                // Extract the 21 hand landmarks (x, y, z coordinates)
                const landmarks = [];
                for (let i = 1; i < landmarksArray.length; i += 3) {
                    landmarks.push({
                        x: landmarksArray[i],
                        y: landmarksArray[i + 1],
                        z: landmarksArray[i + 2]
                    });
                }
                
                return [landmarks]; // Return array of detected hands
            }
            
            return []; // No hands detected
        });
    }

    /**
     * Classify gesture based on hand landmarks
     */
    async classifyGesture(landmarks) {
        return tf.tidy(() => {
            // Convert landmarks to the format expected by the classifier
            const landmarkTensor = this.landmarksToTensor(landmarks);
            
            // Run inference
            const predictions = this.gestureClassifierModel.predict(landmarkTensor);
            const gestureScores = predictions.arraySync()[0];
            
            // Get the gesture with highest confidence
            let maxScore = 0;
            let gestureIndex = -1;
            
            for (let i = 0; i < gestureScores.length; i++) {
                if (gestureScores[i] > maxScore) {
                    maxScore = gestureScores[i];
                    gestureIndex = i;
                }
            }
            
            // Map index to gesture name
            const gestureNames = Object.keys(this.gestureToTextMapping);
            const gestureName = gestureIndex >= 0 && gestureIndex < gestureNames.length 
                ? gestureNames[gestureIndex] 
                : 'unknown';
            
            return {
                name: gestureName,
                confidence: maxScore,
                timestamp: new Date()
            };
        });
    }

    /**
     * Convert hand landmarks to tensor format for classification
     */
    landmarksToTensor(landmarks) {
        // Flatten landmarks into a single array
        const flattenedLandmarks = [];
        
        landmarks.forEach(landmark => {
            flattenedLandmarks.push(landmark.x, landmark.y, landmark.z);
        });
        
        // Create tensor with batch dimension
        return tf.tensor2d([flattenedLandmarks], [1, flattenedLandmarks.length]);
    }

    /**
     * Draw detection results on canvas
     */
    drawResultsOnCanvas(canvas, videoElement, faceDetections, handLandmarks) {
        const ctx = canvas.getContext('2d');
        
        // Clear canvas
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Draw original video frame
        ctx.drawImage(videoElement, 0, 0, canvas.width, canvas.height);
        
        // Draw face detections
        const resizedFaceDetections = faceapi.resizeResults(faceDetections, {
            width: canvas.width,
            height: canvas.height
        });
        
        faceapi.draw.drawDetections(canvas, resizedFaceDetections);
        faceapi.draw.drawFaceLandmarks(canvas, resizedFaceDetections);
        
        // Draw hand landmarks
        if (handLandmarks && handLandmarks.length > 0) {
            this.drawHandLandmarks(ctx, handLandmarks[0], canvas.width, canvas.height);
            
            // Draw gesture text if available
            if (this.lastGesture) {
                const text = this.gestureToTextMapping[this.lastGesture.name] || this.lastGesture.name;
                const confidence = Math.round(this.lastGesture.confidence * 100);
                
                ctx.font = 'bold 24px Arial';
                ctx.fillStyle = '#FF5C8A';
                ctx.textAlign = 'center';
                ctx.fillText(`${text} (${confidence}%)`, canvas.width / 2, 50);
            }
        }
    }

    /**
     * Draw hand landmarks on canvas
     */
    drawHandLandmarks(ctx, landmarks, canvasWidth, canvasHeight) {
        // Draw connections between landmarks
        const connections = [
            // Thumb
            [0, 1], [1, 2], [2, 3], [3, 4],
            // Index finger
            [0, 5], [5, 6], [6, 7], [7, 8],
            // Middle finger
            [0, 9], [9, 10], [10, 11], [11, 12],
            // Ring finger
            [0, 13], [13, 14], [14, 15], [15, 16],
            // Pinky
            [0, 17], [17, 18], [18, 19], [19, 20],
            // Palm
            [0, 5], [5, 9], [9, 13], [13, 17]
        ];
        
        // Scale landmarks to canvas size
        const scaledLandmarks = landmarks.map(landmark => ({
            x: landmark.x * canvasWidth,
            y: landmark.y * canvasHeight,
            z: landmark.z
        }));
        
        // Draw connections
        ctx.strokeStyle = '#FF5C8A';
        ctx.lineWidth = 3;
        
        connections.forEach(([i, j]) => {
            ctx.beginPath();
            ctx.moveTo(scaledLandmarks[i].x, scaledLandmarks[i].y);
            ctx.lineTo(scaledLandmarks[j].x, scaledLandmarks[j].y);
            ctx.stroke();
        });
        
        // Draw landmarks
        ctx.fillStyle = '#FFFFFF';
        
        scaledLandmarks.forEach(landmark => {
            ctx.beginPath();
            ctx.arc(landmark.x, landmark.y, 5, 0, 2 * Math.PI);
            ctx.fill();
            
            ctx.strokeStyle = '#FF5C8A';
            ctx.lineWidth = 2;
            ctx.stroke();
        });
    }

    /**
     * Update gesture history
     */
    updateGestureHistory(gesture) {
        this.gestureHistory.push(gesture);
        
        if (this.gestureHistory.length > this.gestureHistoryMaxLength) {
            this.gestureHistory.shift();
        }
    }

    /**
     * Get the most recent detected gesture
     */
    getLastGesture() {
        return this.lastGesture;
    }

    /**
     * Get gesture history
     */
    getGestureHistory() {
        return this.gestureHistory;
    }

    /**
     * Start continuous processing of a video stream
     * @param {HTMLVideoElement} videoElement - The video element to process
     * @param {HTMLCanvasElement} outputCanvas - Canvas to draw the processed frame
     * @param {number} fps - Frames per second to process (default: 5)
     */
    startProcessing(videoElement, outputCanvas, fps = 5) {
        if (!this.modelsLoaded) {
            console.error('Models not loaded. Call loadModels() first.');
            return;
        }

        const intervalMs = 1000 / fps;

        this.processingInterval = setInterval(() => {
            this.processFrame(videoElement, outputCanvas);
        }, intervalMs);
    }

    /**
     * Stop continuous processing
     */
    stopProcessing() {
        if (this.processingInterval) {
            clearInterval(this.processingInterval);
            this.processingInterval = null;
        }
    }

    /**
     * Get text translation for a gesture
     */
    getGestureText(gestureName) {
        return this.gestureToTextMapping[gestureName] || gestureName;
    }

    /**
     * Get all available gesture-to-text mappings
     */
    getAllGestureTextMappings() {
        return this.gestureToTextMapping;
    }
}

// Create singleton instance
const gestureRecognitionService = new GestureRecognitionService();

export default gestureRecognitionService;
