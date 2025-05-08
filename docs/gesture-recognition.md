# Gesture Recognition Feature

KosmoHealth now supports gesture recognition to help people who can't speak communicate with doctors during meetings. This feature uses TensorFlow.js to detect hand gestures and interpret them into meaningful text.

## How to Use

### Accessing a Gesture-Enabled Meeting

To join a meeting with gesture recognition enabled, use the following URL format:

```
https://your-domain.com/app/live/meetings-gesture/{meeting_uuid}
```

Replace `{meeting_uuid}` with the actual meeting ID.

### For Patients

1. Join the meeting using the gesture-enabled link
2. Click the hand gesture icon in the top right corner to enable gesture mode
3. Make hand gestures that will be interpreted into text
4. The interpreted text will appear on the doctor's screen

### For Doctors

1. Join the meeting using the gesture-enabled link
2. A "Gesture Interpreter" panel will appear when a patient enables gesture mode
3. This panel shows:
   - The current gesture being made
   - A history of recent gestures
   - A quick reference guide for common gestures

## Supported Gestures

The system can recognize the following types of gestures:

### Basic Gestures
- Thumbs up: "Yes / Good"
- Thumbs down: "No / Bad"
- Open palm: "Stop / Wait"
- Pointing: "Look at this"
- Fist: "Attention"
- Victory sign: "Peace / Two"
- OK sign: "OK / Good"
- Wave: "Hello / Goodbye"

### Medical-Specific Gestures
- Pain gesture: "I am in pain"
- Water gesture: "I need water"
- Medicine gesture: "I need medicine"
- Bathroom gesture: "I need to use the bathroom"
- Help gesture: "I need help"
- Cold gesture: "I am cold"
- Hot gesture: "I am hot"
- Tired gesture: "I am tired"
- Nauseous gesture: "I feel nauseous"
- Dizzy gesture: "I feel dizzy"
- Breathing difficulty gesture: "I have difficulty breathing"

### ASL Alphabet
The system also supports American Sign Language (ASL) alphabet gestures (A-Z).

## Technical Requirements

- Modern web browser (Chrome, Firefox, Safari, Edge)
- Webcam access
- Sufficient lighting for clear hand detection
- Stable internet connection

## Privacy Considerations

- All gesture processing happens locally in the browser
- No video or gesture data is sent to external servers
- The feature can be disabled at any time

## Troubleshooting

If gesture recognition is not working properly:

1. Ensure your hand is clearly visible in the camera view
2. Improve lighting conditions if the room is too dark
3. Position your hand at a comfortable distance from the camera
4. Try making gestures more deliberately and hold them for a moment
5. Refresh the browser if the feature stops responding

## Combining with Other Features

Gesture recognition can be used alongside other KosmoHealth features:

- **Emotion Detection**: Works simultaneously with gesture recognition
- **Face Blurring**: Can be enabled while using gesture recognition
- **Audio Communication**: Can be used as a supplement to gesture recognition

## Feedback

We're continuously improving our gesture recognition system. If you have suggestions or encounter issues, please contact support or provide feedback through the app.
