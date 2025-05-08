# Hand Gesture Recognition Models

This directory contains TensorFlow.js models for hand gesture recognition in KosmoHealth.

## Models

The gesture recognition system uses two main models:

1. **Hand Landmark Detection Model**: Detects hand landmarks (21 points on the hand) from video input.
2. **Gesture Classifier Model**: Classifies the detected hand landmarks into specific gestures.

## Setup Instructions

### Downloading the Models

You can download the required models using the provided script:

```bash
# Navigate to this directory
cd public/js/hand-models

# Install required dependencies if needed
npm install

# Run the download script
node download-models.js
```

This will create two subdirectories:
- `hand_landmark_detection/` - Contains the hand landmark detection model
- `gesture_classifier/` - Contains the gesture classification model

### Manual Download

If the script doesn't work, you can manually download the models:

1. **Hand Landmark Detection Model**:
   - Download from: https://tfhub.dev/mediapipe/tfjs-model/handpose/1/default/1
   - Files needed: `model.json`, `group1-shard1of2.bin`, `group1-shard2of2.bin`
   - Place in the `hand_landmark_detection/` directory

2. **Gesture Classifier Model**:
   - Download from: https://storage.googleapis.com/tfjs-models/tfjs/gesture_classifier_v1/model.json
   - Files needed: `model.json`, `group1-shard1of1.bin`
   - Place in the `gesture_classifier/` directory

## Directory Structure

After setup, your directory structure should look like:

```
public/js/hand-models/
├── README.md
├── download-models.js
├── hand_landmark_detection/
│   ├── model.json
│   ├── group1-shard1of2.bin
│   └── group1-shard2of2.bin
└── gesture_classifier/
    ├── model.json
    └── group1-shard1of1.bin
```

## Usage

These models are automatically loaded by the `GestureRecognitionService` when the gesture recognition feature is enabled in a meeting.

## Supported Gestures

The gesture classifier can recognize the following gestures:

- Basic gestures: thumbs up, thumbs down, open palm, pointing, fist, victory, OK sign, wave
- ASL alphabet: A-Z
- Medical-specific gestures: pain, water, medicine, bathroom, help, cold, hot, tired, nauseous, dizzy, breathing difficulty

## Troubleshooting

If you encounter issues with the models:

1. Check that all model files are correctly downloaded and placed in the appropriate directories
2. Ensure the file permissions allow the web server to read these files
3. Check the browser console for any TensorFlow.js related errors
4. Try clearing your browser cache and reloading the page

## Additional Resources

- [TensorFlow.js Documentation](https://www.tensorflow.org/js)
- [MediaPipe Hand Pose Documentation](https://google.github.io/mediapipe/solutions/hands.html)
- [Hand Gesture Recognition Tutorial](https://www.tensorflow.org/js/tutorials/transfer/handpose_detection)
