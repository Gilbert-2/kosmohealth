# AI Emotion Detection and Face Blur for Kosmohealth

This implementation adds AI-powered emotion detection and face blur capabilities to the Kosmohealth video conferencing application.

## Features

- **AI Emotion Detection**: Detects users' emotions during video calls
- **Face Blur**: Blurs faces when users are not comfortable
- **Auto-Blur**: Automatically blurs faces when discomfort is detected
- **Privacy-Focused**: All processing happens locally in the browser

## Implementation

The implementation consists of two main approaches:

1. **Browser Plugin**: A standalone JavaScript file that can be loaded in the browser to add face processing capabilities to the existing application without modifying the source code.
2. **Test Page**: A standalone HTML page to test the face processing features.

## Setup Instructions

### 1. Download Face-API.js Models

The face processing features require pre-trained models for face detection, landmark detection, and emotion recognition. Follow these steps to download the models:

1. Create a directory for the models:
   ```
   mkdir -p public/js/face-api-models
   ```

2. Open the test page in your browser:
   ```
   http://127.0.0.1:8000/face-processing-test.html
   ```

3. Open the browser console (F12) and run the following command:
   ```javascript
   // Load the download script
   const script = document.createElement('script');
   script.src = '/js/download-face-api-models.js';
   document.head.appendChild(script);
   ```

4. This will download the required model files. Move the downloaded files to the `public/js/face-api-models` directory.

### 2. Test the Face Processing Features

1. Open the test page in your browser:
   ```
   http://127.0.0.1:8000/face-processing-test.html
   ```

2. Click the "Start Camera" button to begin.
3. Test the face blur and emotion detection features.

### 3. Integrate with Kosmohealth

#### Option 1: Browser Plugin (No Code Changes)

1. Add the following script tag to your meeting page:
   ```html
   <script src="/js/face-processing-plugin.js"></script>
   ```

2. The plugin will automatically detect video elements and add face processing capabilities.

#### Option 2: Direct Integration (Code Changes Required)

If you want to integrate the face processing features directly into your application, you can:

1. Import the FaceProcessingService in your Vue component:
   ```javascript
   import FaceProcessingService from '../services/FaceProcessingService';
   ```

2. Initialize the service in your component:
   ```javascript
   async mounted() {
     await FaceProcessingService.loadModels();
     // Get the video element
     const videoElement = document.getElementById('localVideo');
     // Start processing
     FaceProcessingService.startProcessing(videoElement);
   }
   ```

3. Add controls to your UI to toggle face blur and emotion detection.

## Configuration

The face processing features can be configured with the following options:

- **processingFps**: Frames per second for processing (default: 3)
- **emotionThreshold**: Confidence threshold for emotion detection (default: 0.7)
- **discomfortEmotions**: Emotions that indicate discomfort (default: ['angry', 'fearful', 'disgusted', 'sad'])
- **autoBlurOnDiscomfort**: Automatically blur faces when discomfort is detected (default: true)

## Files

- `public/js/face-api.min.js`: The face-api.js library
- `public/js/face-processing-plugin.js`: The browser plugin for face processing
- `public/js/download-face-api-models.js`: Script to download the face-api.js models
- `public/face-processing-test.html`: Test page for face processing features
- `resources/js/services/FaceProcessingService.js`: Service for face processing
- `resources/js/components/FaceProcessing.vue`: Vue component for face processing
- `resources/js/components/FaceProcessingWrapper.vue`: Wrapper component for face processing
- `resources/js/components/MeetingLiveWithFaceProcessing.vue`: Integration component for meetings

## Browser Compatibility

The face processing features are compatible with modern browsers that support WebRTC and the Canvas API:

- Chrome 60+
- Firefox 55+
- Safari 11+
- Edge 79+

## Performance Considerations

- Face detection and emotion recognition are CPU-intensive operations
- Lower FPS (1-3) is recommended for most devices
- The implementation automatically throttles processing to avoid performance issues
- All processing happens locally in the browser - no data is sent to servers

## Privacy Considerations

- All processing happens locally in the browser
- No emotion data or video frames are sent to the server
- Face blurring is applied before the video is sent to other participants
- Users have full control to enable/disable these features during meetings
