# Face Processing Features

This document explains how to use the AI emotion detection and face blur features in Kosmohealth.

## Overview

Kosmohealth now includes two new privacy and comfort-enhancing features:

1. **AI Emotion Detection**: Detects participants' emotions during video calls
2. **Face Blur**: Allows participants to blur their faces for privacy

## How It Works

### Emotion Detection

The emotion detection feature uses face-api.js, a JavaScript library that implements face recognition algorithms in the browser. It can detect the following emotions:

- Happy
- Sad
- Angry
- Fearful
- Disgusted
- Surprised
- Neutral

When enabled, the system will analyze the video stream in real-time and display the detected emotion. If a "discomfort" emotion is detected (angry, fearful, disgusted, or sad), the system can automatically blur the face if the auto-blur feature is enabled.

### Face Blur

The face blur feature allows participants to blur their faces during video calls for privacy. This can be:

- Manually toggled by the user
- Automatically activated when discomfort is detected (if enabled)

## Configuration

Administrators can configure these features in the Meeting Configuration section:

1. Go to **App Settings** > **Meeting** > **Face Processing**
2. Configure the following options:
   - **Enable Emotion Detection**: Turn emotion detection on/off
   - **Enable Face Blur**: Allow participants to blur their faces
   - **Auto Blur on Discomfort**: Automatically blur faces when discomfort is detected
   - **Show Emotion Info**: Display detected emotions during meetings
   - **Processing FPS**: Frames per second for face processing (1-10)

## Using the Features

During a meeting:

1. Look for the face processing controls in the meeting interface
2. Toggle "Blur Face" to manually blur your face
3. Toggle "Emotion Detection" to enable/disable emotion detection
4. When emotions are detected, they will appear in the top-right corner of your video
5. If discomfort is detected, a notification will appear and your face may be automatically blurred (if enabled)

## Privacy Considerations

- All processing happens locally in the browser - no emotion data is sent to the server
- Face blurring is applied before the video is sent to other participants
- Users have full control to enable/disable these features during meetings

## Technical Requirements

- Modern browser with WebRTC support (Chrome, Firefox, Safari, Edge)
- Sufficient CPU resources for real-time processing
- Camera access permissions

## Troubleshooting

If you experience issues with face processing:

1. Ensure your browser is up to date
2. Check that you have granted camera permissions
3. Try reducing the Processing FPS in settings if performance is slow
4. Ensure adequate lighting for better face detection
5. Try disabling other CPU-intensive applications

For further assistance, contact your system administrator.
