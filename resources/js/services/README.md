# Face Processing Service

This service provides AI-powered face detection, emotion recognition, and face blurring capabilities for Kosmohealth's video conferencing features.

## Overview

The face processing service uses [face-api.js](https://github.com/justadudewhohacks/face-api.js), a JavaScript library built on top of TensorFlow.js, to perform real-time face detection and emotion recognition directly in the browser.

## Features

- **Face Detection**: Detects faces in video streams
- **Emotion Recognition**: Identifies emotions (happy, sad, angry, fearful, disgusted, surprised, neutral)
- **Face Blurring**: Applies blur effect to detected faces for privacy
- **Discomfort Detection**: Identifies when a user might be uncomfortable based on emotions

## Architecture

The implementation consists of:

1. **FaceProcessingService.js**: Core service that handles face detection, emotion recognition, and face blurring
2. **FaceProcessing.vue**: Vue component that wraps the service and provides UI controls
3. **FaceProcessingWrapper.vue**: Wrapper component that finds video elements and applies processing
4. **MeetingLiveWithFaceProcessing.vue**: Integration component for the meeting interface

## Usage

### Basic Usage

```javascript
import FaceProcessingService from '../services/FaceProcessingService';

// Load models (do this once at app startup)
await FaceProcessingService.loadModels();

// Process a single video frame
const videoElement = document.getElementById('myVideo');
const outputCanvas = document.getElementById('myCanvas');
await FaceProcessingService.processFrame(videoElement, outputCanvas);

// Start continuous processing
FaceProcessingService.startProcessing(videoElement, outputCanvas, 3); // 3 FPS

// Stop processing
FaceProcessingService.stopProcessing();
```

### Event Callbacks

```javascript
// Set callback for emotion detection
FaceProcessingService.onEmotionDetected(emotion => {
  console.log('Detected emotion:', emotion.dominant);
});

// Set callback for discomfort detection
FaceProcessingService.onDiscomfortDetected((emotion, score) => {
  console.log(`Discomfort detected: ${emotion} (${score})`);
});
```

## Configuration

The service can be configured with the following options:

- **Blur Enable/Disable**: `setBlurEnabled(true/false)`
- **Emotion Detection Enable/Disable**: `setEmotionDetectionEnabled(true/false)`
- **Processing FPS**: Set when calling `startProcessing(video, canvas, fps)`
- **Emotion Threshold**: Internal threshold for emotion confidence (default: 0.7)
- **Discomfort Emotions**: Emotions that indicate discomfort (default: angry, fearful, disgusted, sad)

## Performance Considerations

- Face detection and emotion recognition are CPU-intensive operations
- Lower FPS (1-3) is recommended for most devices
- The service automatically throttles processing to avoid performance issues
- All processing happens locally in the browser - no data is sent to servers

## Extending

To add new features:

1. Modify `FaceProcessingService.js` to add new detection or processing capabilities
2. Update the Vue components to expose new features in the UI
3. Add any new configuration options to the meeting configuration page

## Dependencies

- face-api.js: For face detection and emotion recognition
- TensorFlow.js: Underlying ML framework (included with face-api.js)

## Models

The face-api.js models are loaded from `/js/face-api-models/` and include:

- Tiny Face Detector: Lightweight face detection model
- Face Landmark Detection: For identifying facial features
- Face Expression Recognition: For emotion detection

Models are downloaded during build using the `download-face-api-models.js` script.
