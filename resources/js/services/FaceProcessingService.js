import * as faceapi from 'face-api.js';

class FaceProcessingService {
    constructor() {
        this.modelsLoaded = false;
        this.isProcessing = false;
        this.blurEnabled = false;
        this.emotionDetectionEnabled = false;
        this.lastEmotions = null;
        this.emotionThreshold = 0.7; // Threshold for emotion confidence
        this.discomfortEmotions = ['angry', 'fearful', 'disgusted', 'sad']; // Emotions that might indicate discomfort
        this.onEmotionDetectedCallback = null;
        this.onDiscomfortDetectedCallback = null;
        this.onFaceDetectionStatusCallback = null;
        this.onNetworkConditionChangeCallback = null;
        this.lastDetections = null;
        this.noFaceDetectedCount = 0;
        this.noFaceThreshold = 10; // Increased threshold for better tracking during interruptions
        this.fullPageBlurEnabled = true; // Enable full page blur when no face is detected
        this.blurIntensity = 15; // Default blur intensity in pixels
        this.glassmorphismEnabled = true; // Enable glassmorphism effect

        // Enhanced tracking properties
        this.faceTrackingHistory = []; // Store recent face positions
        this.faceTrackingHistoryMaxLength = 60; // Store up to 60 frames of history for better tracking
        this.lastValidFaceBox = null; // Last valid face detection box
        this.predictionEnabled = true; // Enable face position prediction
        this.predictionStrength = 1.2; // Prediction strength factor (higher = more aggressive prediction)
        this.motionVelocity = { x: 0, y: 0, width: 0, height: 0 }; // Current motion velocity vector

        // Network and performance optimization
        this.networkCondition = 'normal'; // 'low', 'normal', 'high'
        this.lastProcessingTime = 0;
        this.processingTimeHistory = [];
        this.adaptiveMode = true; // Enable adaptive processing based on network conditions
    }

    /**
     * Initialize face-api.js models
     */
    async loadModels() {
        if (this.modelsLoaded) return;

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
            console.log('Face-api models loaded successfully');
            return true;
        } catch (error) {
            console.error('Error loading face-api models:', error);
            return false;
        }
    }

    /**
     * Enable or disable face blur
     */
    setBlurEnabled(enabled) {
        this.blurEnabled = enabled;
    }

    /**
     * Enable or disable full page blur when no face is detected
     */
    setFullPageBlurEnabled(enabled) {
        this.fullPageBlurEnabled = enabled;
    }

    /**
     * Set blur intensity (in pixels)
     */
    setBlurIntensity(intensity) {
        this.blurIntensity = Math.max(1, Math.min(30, intensity));
    }

    /**
     * Enable or disable glassmorphism effect
     */
    setGlassmorphismEnabled(enabled) {
        this.glassmorphismEnabled = enabled;
    }

    /**
     * Enable or disable emotion detection
     */
    setEmotionDetectionEnabled(enabled) {
        this.emotionDetectionEnabled = enabled;
    }

    /**
     * Set callback for when emotions are detected
     */
    onEmotionDetected(callback) {
        this.onEmotionDetectedCallback = callback;
    }

    /**
     * Set callback for when discomfort is detected
     */
    onDiscomfortDetected(callback) {
        this.onDiscomfortDetectedCallback = callback;
    }

    /**
     * Set callback for face detection status changes
     * @param {Function} callback - Function to call with detection status
     */
    onFaceDetectionStatus(callback) {
        this.onFaceDetectionStatusCallback = callback;
    }

    /**
     * Set callback for network condition changes
     * @param {Function} callback - Function to call with network condition
     */
    onNetworkConditionChange(callback) {
        this.onNetworkConditionChangeCallback = callback;
    }

    /**
     * Process video frame to detect faces and emotions
     * @param {HTMLVideoElement} videoElement - The video element to process
     * @param {HTMLCanvasElement} outputCanvas - Optional canvas to draw the processed frame
     */
    async processFrame(videoElement, outputCanvas = null) {
        if (!this.modelsLoaded || this.isProcessing || !videoElement || videoElement.paused || videoElement.ended) {
            return;
        }

        this.isProcessing = true;
        const startTime = performance.now();

        try {
            // Detect all faces with expressions using optimized options for low bandwidth
            const detectionOptions = new faceapi.TinyFaceDetectorOptions({
                inputSize: 320,     // Smaller input size for better performance on low bandwidth
                scoreThreshold: 0.25  // Balanced threshold for accuracy vs performance
            });

            const detections = await faceapi.detectAllFaces(
                videoElement,
                detectionOptions
            )
            .withFaceLandmarks()
            .withFaceExpressions();

            // Track face detection status
            const faceDetected = detections.length > 0;

            // Get the primary face detection (usually the largest or most centered)
            let currentFaceBox = null;
            let resizedDetections = [];

            if (outputCanvas) {
                const dims = faceapi.matchDimensions(outputCanvas, videoElement, true);
                resizedDetections = faceapi.resizeResults(detections, dims);
            }

            if (faceDetected) {
                // Update face tracking history with current detection
                if (resizedDetections.length > 0) {
                    currentFaceBox = resizedDetections[0].detection.box;
                } else if (detections.length > 0) {
                    currentFaceBox = detections[0].detection.box;
                }

                if (currentFaceBox) {
                    this.lastValidFaceBox = { ...currentFaceBox };
                    this.updateFaceTrackingHistory(currentFaceBox);
                    this.noFaceDetectedCount = 0;
                }
            } else {
                // Face not detected in this frame
                this.noFaceDetectedCount++;

                // Try to predict face position if we have tracking history
                if (this.predictionEnabled && this.faceTrackingHistory.length > 0 && this.lastValidFaceBox) {
                    currentFaceBox = this.predictFacePosition();

                    // Create a synthetic detection with the predicted box
                    if (currentFaceBox && resizedDetections.length === 0) {
                        const syntheticDetection = {
                            detection: {
                                box: currentFaceBox
                            }
                        };
                        resizedDetections = [syntheticDetection];
                    }
                }
            }

            // Determine if we should show full page blur
            const shouldShowFullPageBlur = this.fullPageBlurEnabled &&
                                          this.noFaceDetectedCount >= this.noFaceThreshold;

            // Notify about face detection status
            if (this.onFaceDetectionStatusCallback) {
                this.onFaceDetectionStatusCallback({
                    faceDetected: faceDetected || (currentFaceBox !== null),
                    noFaceCount: this.noFaceDetectedCount,
                    fullPageBlurActive: shouldShowFullPageBlur,
                    isPredicted: !faceDetected && currentFaceBox !== null
                });
            }

            // Store last detections for tracking
            this.lastDetections = detections;

            // Process emotions if enabled
            if (this.emotionDetectionEnabled && faceDetected) {
                this.processEmotions(detections);
            }

            // Draw results on canvas if provided
            if (outputCanvas) {
                // Clear canvas
                const ctx = outputCanvas.getContext('2d');
                ctx.clearRect(0, 0, outputCanvas.width, outputCanvas.height);

                // Draw original video frame
                ctx.drawImage(videoElement, 0, 0, outputCanvas.width, outputCanvas.height);

                // Apply blur if enabled
                if (this.blurEnabled) {
                    if (shouldShowFullPageBlur) {
                        this.applyFullPageBlur(ctx, outputCanvas.width, outputCanvas.height);
                    } else {
                        this.applyFaceBlur(ctx, resizedDetections);
                    }
                } else {
                    // Draw detection boxes with green rectangles
                    ctx.strokeStyle = '#00FF00'; // Bright green color
                    ctx.lineWidth = 3;

                    // Draw detected or predicted faces
                    if (resizedDetections.length > 0) {
                        resizedDetections.forEach(detection => {
                            const { box } = detection.detection;
                            ctx.strokeRect(box.x, box.y, box.width, box.height);

                            // If this is a predicted face, add an indicator
                            if (!faceDetected && this.predictionEnabled) {
                                ctx.fillStyle = 'rgba(0, 255, 0, 0.3)';
                                ctx.fillRect(box.x, box.y, box.width, box.height);

                                // Add 'Tracking' text
                                ctx.font = '12px Arial';
                                ctx.fillStyle = '#00FF00';
                                ctx.fillText('Tracking', box.x, box.y - 5);
                            }
                        });
                    }

                    // Draw face landmarks and expressions if not blurring and face is detected (not predicted)
                    if (!shouldShowFullPageBlur && faceDetected) {
                        faceapi.draw.drawFaceLandmarks(outputCanvas, resizedDetections);
                        faceapi.draw.drawFaceExpressions(outputCanvas, resizedDetections);
                    }
                }
            }
        } catch (error) {
            console.error('Error processing video frame:', error);
        } finally {
            // Calculate processing time
            const endTime = performance.now();
            const processingTime = endTime - startTime;

            // Keep track of processing times for performance measurement
            this.processingTimeHistory.push(processingTime);
            if (this.processingTimeHistory.length > 10) {
                this.processingTimeHistory.shift(); // Keep only the last 10 measurements
            }

            this.lastProcessingTime = processingTime;
            this.isProcessing = false;

            // Adjust processing parameters based on performance if in adaptive mode
            if (this.adaptiveMode && this.processingTimeHistory.length >= 5) {
                this.measurePerformance();
            }
        }
    }

    /**
     * Process emotions from detections
     */
    processEmotions(detections) {
        if (!detections || detections.length === 0) return;

        // Get the primary face (usually the largest or most centered)
        const primaryFace = detections[0];
        const expressions = primaryFace.expressions;

        // Find the dominant emotion
        let dominantEmotion = null;
        let highestScore = 0;

        for (const [emotion, score] of Object.entries(expressions)) {
            if (score > highestScore) {
                highestScore = score;
                dominantEmotion = emotion;
            }
        }

        // Only report if we have a confident detection
        if (highestScore > this.emotionThreshold) {
            this.lastEmotions = {
                dominant: dominantEmotion,
                score: highestScore,
                all: expressions
            };

            // Call the emotion callback if set
            if (this.onEmotionDetectedCallback) {
                this.onEmotionDetectedCallback(this.lastEmotions);
            }

            // Check if the emotion indicates discomfort
            if (this.discomfortEmotions.includes(dominantEmotion)) {
                if (this.onDiscomfortDetectedCallback) {
                    this.onDiscomfortDetectedCallback(dominantEmotion, highestScore);
                }
            }
        }
    }

    /**
     * Apply blur effect to faces in the frame with optional glassmorphism
     */
    applyFaceBlur(ctx, detections) {
        if (!detections || detections.length === 0) {
            // If no detections but we have a predicted face, use that
            if (this.predictionEnabled && this.lastValidFaceBox) {
                this.applyBlurToFaceBox(ctx, this.lastValidFaceBox, true);
            }
            return;
        }

        detections.forEach(detection => {
            const { box } = detection.detection;
            this.applyBlurToFaceBox(ctx, box, false);
        });
    }

    /**
     * Apply blur to a specific face box
     * @param {CanvasRenderingContext2D} ctx - Canvas context
     * @param {Object} box - Face box coordinates
     * @param {boolean} isPredicted - Whether this is a predicted face position
     */
    applyBlurToFaceBox(ctx, box, isPredicted) {
        // Expand the box more generously to ensure full face coverage even during movement
        // Use a larger expansion factor for predicted faces to account for uncertainty
        const expandFactor = isPredicted ? 0.25 : 0.15;

        // Calculate expanded box with smoothing for predicted positions
        const expandedBox = {
            x: Math.max(0, box.x - box.width * expandFactor),
            y: Math.max(0, box.y - box.height * expandFactor),
            width: box.width * (1 + expandFactor * 2),
            height: box.height * (1 + expandFactor * 2)
        };

        // Save the current state
        ctx.save();

        // Draw green rectangle around the face before applying blur
        // Use different styles for detected vs predicted faces
        if (isPredicted) {
            // Dashed line for predicted faces
            ctx.strokeStyle = '#00FF00'; // Bright green color
            ctx.lineWidth = 2;
            ctx.setLineDash([5, 3]); // Dashed line pattern
            ctx.strokeRect(box.x, box.y, box.width, box.height);

            // Add 'Tracking' text with better visibility
            ctx.font = 'bold 12px Arial';
            ctx.fillStyle = 'rgba(0, 0, 0, 0.7)'; // Text shadow for better visibility
            ctx.fillText('Tracking', box.x + 1, box.y - 6);
            ctx.fillStyle = '#00FF00';
            ctx.fillText('Tracking', box.x, box.y - 7);

            // Add subtle highlight to show it's being tracked
            ctx.fillStyle = 'rgba(0, 255, 0, 0.15)';
            ctx.fillRect(box.x, box.y, box.width, box.height);
        } else {
            // Solid line for detected faces
            ctx.strokeStyle = '#00FF00'; // Bright green color
            ctx.lineWidth = 3;
            ctx.setLineDash([]); // Solid line
            ctx.strokeRect(box.x, box.y, box.width, box.height);
        }

        if (this.glassmorphismEnabled) {
            // Create a rounded rectangle for glassmorphism effect with larger radius for smoother look
            const cornerRadius = isPredicted ? 15 : 10; // Larger radius for predicted faces
            this.roundRect(ctx, expandedBox.x, expandedBox.y, expandedBox.width, expandedBox.height, cornerRadius);
            ctx.clip();

            // Apply a blur filter with motion blur for predicted faces
            if (isPredicted && this.motionVelocity) {
                // Calculate motion magnitude
                const motionMagnitude = Math.sqrt(
                    this.motionVelocity.x * this.motionVelocity.x +
                    this.motionVelocity.y * this.motionVelocity.y
                ) * 1000; // Scale up for visibility

                // Apply stronger blur in the direction of movement for moving faces
                if (motionMagnitude > 0.5) {
                    const blurAmount = this.blurIntensity + Math.min(5, motionMagnitude);
                    ctx.filter = `blur(${blurAmount}px)`;
                } else {
                    // Standard blur for stationary faces
                    ctx.filter = `blur(${this.blurIntensity}px)`;
                }
            } else {
                // Standard blur for detected faces
                ctx.filter = `blur(${this.blurIntensity}px)`;
            }

            // Redraw the video frame in the clipped region with blur
            ctx.drawImage(
                ctx.canvas,
                expandedBox.x, expandedBox.y, expandedBox.width, expandedBox.height,
                expandedBox.x, expandedBox.y, expandedBox.width, expandedBox.height
            );

            // Add enhanced glassmorphism effect
            // More subtle for predicted faces to avoid drawing attention
            if (isPredicted) {
                ctx.fillStyle = 'rgba(255, 255, 255, 0.08)';
                ctx.fill();
                ctx.strokeStyle = 'rgba(255, 255, 255, 0.15)';
                ctx.lineWidth = 1;
                ctx.stroke();
            } else {
                ctx.fillStyle = 'rgba(255, 255, 255, 0.1)';
                ctx.fill();
                ctx.strokeStyle = 'rgba(255, 255, 255, 0.2)';
                ctx.lineWidth = 2;
                ctx.stroke();
            }
        } else {
            // Create a clipping region for the face
            ctx.beginPath();
            ctx.rect(expandedBox.x, expandedBox.y, expandedBox.width, expandedBox.height);
            ctx.clip();

            // Apply a blur filter
            ctx.filter = `blur(${this.blurIntensity}px)`;

            // Redraw the video frame in the clipped region with blur
            ctx.drawImage(
                ctx.canvas,
                expandedBox.x, expandedBox.y, expandedBox.width, expandedBox.height,
                expandedBox.x, expandedBox.y, expandedBox.width, expandedBox.height
            );
        }

        // Restore the context
        ctx.restore();
    }

    /**
     * Apply full page blur effect
     */
    applyFullPageBlur(ctx, width, height) {
        // Save the current state
        ctx.save();

        // Apply a blur filter to the entire canvas
        ctx.filter = `blur(${this.blurIntensity * 1.5}px)`;

        // Redraw the entire canvas with blur
        ctx.drawImage(ctx.canvas, 0, 0, width, height, 0, 0, width, height);

        if (this.glassmorphismEnabled) {
            // Add glassmorphism overlay
            ctx.fillStyle = 'rgba(255, 255, 255, 0.1)';
            ctx.fillRect(0, 0, width, height);

            // Add a subtle border
            ctx.strokeStyle = 'rgba(255, 255, 255, 0.2)';
            ctx.lineWidth = 2;
            ctx.strokeRect(5, 5, width - 10, height - 10);

            // Add a message in the center
            ctx.font = 'bold 24px Arial';
            ctx.fillStyle = 'rgba(255, 255, 255, 0.8)';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText('No face detected', width / 2, height / 2);
        }

        // Restore the context
        ctx.restore();
    }

    /**
     * Helper function to create rounded rectangles for glassmorphism
     */
    roundRect(ctx, x, y, width, height, radius) {
        ctx.beginPath();
        ctx.moveTo(x + radius, y);
        ctx.lineTo(x + width - radius, y);
        ctx.quadraticCurveTo(x + width, y, x + width, y + radius);
        ctx.lineTo(x + width, y + height - radius);
        ctx.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
        ctx.lineTo(x + radius, y + height);
        ctx.quadraticCurveTo(x, y + height, x, y + height - radius);
        ctx.lineTo(x, y + radius);
        ctx.quadraticCurveTo(x, y, x + radius, y);
        ctx.closePath();
    }

    /**
     * Start continuous processing of a video stream
     * @param {HTMLVideoElement} videoElement - The video element to process
     * @param {HTMLCanvasElement} outputCanvas - Canvas to draw the processed frame
     * @param {number} fps - Frames per second to process (default: 5)
     */
    startProcessing(videoElement, outputCanvas, fps = 5) {
        // Detect network conditions and adjust processing parameters
        this.checkNetworkConditions();
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
     * Check network conditions and adjust processing parameters
     */
    checkNetworkConditions() {
        // Use Navigator API to check connection type if available
        if (navigator.connection) {
            const connection = navigator.connection;

            if (connection.effectiveType === '2g' || connection.effectiveType === 'slow-2g') {
                this.setLowBandwidthMode();
            } else if (connection.effectiveType === '3g') {
                this.setMediumBandwidthMode();
            } else {
                this.setHighBandwidthMode();
            }

            // Listen for connection changes
            if (!this._hasConnectionListener) {
                connection.addEventListener('change', () => {
                    this.checkNetworkConditions();
                });
                this._hasConnectionListener = true;
            }
        } else {
            // Fallback to performance measurement
            this.measurePerformance();
        }
    }

    /**
     * Measure performance to determine optimal settings
     */
    measurePerformance() {
        // Calculate average processing time
        if (this.processingTimeHistory.length > 0) {
            const avgTime = this.processingTimeHistory.reduce((a, b) => a + b, 0) / this.processingTimeHistory.length;

            if (avgTime > 200) { // If processing takes more than 200ms
                this.setLowBandwidthMode();
            } else if (avgTime > 100) { // If processing takes more than 100ms
                this.setMediumBandwidthMode();
            } else {
                this.setHighBandwidthMode();
            }
        }
    }

    /**
     * Set low bandwidth mode
     */
    setLowBandwidthMode() {
        if (this.networkCondition === 'low') return;

        this.networkCondition = 'low';
        this.blurIntensity = 10; // Lower blur intensity

        // Notify about network condition change
        if (this.onNetworkConditionChangeCallback) {
            this.onNetworkConditionChangeCallback({
                condition: 'low',
                message: 'Low bandwidth detected. Optimizing for performance.'
            });
        }

        console.log('Face processing: Low bandwidth mode activated');
    }

    /**
     * Set medium bandwidth mode
     */
    setMediumBandwidthMode() {
        if (this.networkCondition === 'medium') return;

        this.networkCondition = 'medium';
        this.blurIntensity = 15; // Default blur intensity

        // Notify about network condition change
        if (this.onNetworkConditionChangeCallback) {
            this.onNetworkConditionChangeCallback({
                condition: 'medium',
                message: 'Medium bandwidth detected. Using balanced settings.'
            });
        }

        console.log('Face processing: Medium bandwidth mode activated');
    }

    /**
     * Set high bandwidth mode
     */
    setHighBandwidthMode() {
        if (this.networkCondition === 'high') return;

        this.networkCondition = 'high';
        this.blurIntensity = 20; // Higher blur intensity for better quality

        // Notify about network condition change
        if (this.onNetworkConditionChangeCallback) {
            this.onNetworkConditionChangeCallback({
                condition: 'high',
                message: 'Good connection detected. Using high quality settings.'
            });
        }

        console.log('Face processing: High bandwidth mode activated');
    }

    /**
     * Update face tracking history with new face position
     * @param {Object} faceBox - The face detection box
     */
    updateFaceTrackingHistory(faceBox) {
        if (!faceBox) return;

        // Add current face position to history
        this.faceTrackingHistory.push({
            x: faceBox.x,
            y: faceBox.y,
            width: faceBox.width,
            height: faceBox.height,
            timestamp: performance.now()
        });

        // Limit history length
        if (this.faceTrackingHistory.length > this.faceTrackingHistoryMaxLength) {
            this.faceTrackingHistory.shift();
        }
    }

    /**
     * Predict face position based on tracking history
     * @returns {Object} Predicted face box
     */
    predictFacePosition() {
        if (!this.lastValidFaceBox || this.faceTrackingHistory.length < 2) {
            return this.lastValidFaceBox;
        }

        try {
            // Get the last few positions to calculate movement vector
            const historyLength = this.faceTrackingHistory.length;

            // Use more history points for better prediction of motion
            const recentHistory = this.faceTrackingHistory.slice(Math.max(0, historyLength - 10));

            if (recentHistory.length < 2) return this.lastValidFaceBox;

            // Calculate average movement vector with time-weighted average
            // More recent movements have higher weight
            let totalDx = 0;
            let totalDy = 0;
            let totalDw = 0;
            let totalDh = 0;
            let totalWeight = 0;

            for (let i = 1; i < recentHistory.length; i++) {
                const prev = recentHistory[i - 1];
                const curr = recentHistory[i];

                // Calculate time difference between frames
                const timeDiff = curr.timestamp - prev.timestamp;
                if (timeDiff <= 0) continue;

                // Calculate velocity (movement per millisecond)
                const dx = (curr.x - prev.x) / timeDiff;
                const dy = (curr.y - prev.y) / timeDiff;
                const dw = (curr.width - prev.width) / timeDiff;
                const dh = (curr.height - prev.height) / timeDiff;

                // Weight more recent movements higher (recency bias)
                const weight = Math.pow(i / recentHistory.length, 2);

                totalDx += dx * weight;
                totalDy += dy * weight;
                totalDw += dw * weight;
                totalDh += dh * weight;
                totalWeight += weight;
            }

            if (totalWeight === 0) return this.lastValidFaceBox;

            // Calculate weighted average velocity
            const avgDx = totalDx / totalWeight;
            const avgDy = totalDy / totalWeight;
            const avgDw = totalDw / totalWeight;
            const avgDh = totalDh / totalWeight;

            // Update motion velocity with exponential smoothing
            // This helps create smoother predictions by blending new and old velocities
            const alpha = 0.7; // Smoothing factor (higher = more responsive to new data)
            this.motionVelocity = {
                x: alpha * avgDx + (1 - alpha) * this.motionVelocity.x,
                y: alpha * avgDy + (1 - alpha) * this.motionVelocity.y,
                width: alpha * avgDw + (1 - alpha) * this.motionVelocity.width,
                height: alpha * avgDh + (1 - alpha) * this.motionVelocity.height
            };

            // Calculate time since last valid detection
            const now = performance.now();
            const lastValidTime = this.faceTrackingHistory[historyLength - 1].timestamp;
            const timeSinceLastDetection = now - lastValidTime;

            // Apply adaptive damping factor based on time since last detection
            // Damping reduces the prediction strength over time to prevent wild movements
            // For longer periods without detection, we reduce prediction strength
            const maxPredictionTime = 2000; // Max time to predict in ms (2 seconds)
            const damping = Math.max(0, 1 - (timeSinceLastDetection / maxPredictionTime));

            // Apply prediction strength factor (adjustable parameter)
            const predictionFactor = this.predictionStrength * damping;

            // Calculate predicted position based on velocity and time
            return {
                x: this.lastValidFaceBox.x + (this.motionVelocity.x * timeSinceLastDetection * predictionFactor),
                y: this.lastValidFaceBox.y + (this.motionVelocity.y * timeSinceLastDetection * predictionFactor),
                width: this.lastValidFaceBox.width + (this.motionVelocity.width * timeSinceLastDetection * predictionFactor),
                height: this.lastValidFaceBox.height + (this.motionVelocity.height * timeSinceLastDetection * predictionFactor)
            };
        } catch (error) {
            console.error('Error predicting face position:', error);
            return this.lastValidFaceBox;
        }
    }
}

export default new FaceProcessingService();
