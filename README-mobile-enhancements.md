# Kosmohealth Meeting Mobile Enhancements

This package provides a modern, mobile-friendly UI enhancement for the Kosmohealth meeting interface. It includes responsive design, touch gestures, and improved usability for mobile devices.

## Features

- **Responsive Design**: Optimized for all screen sizes, especially mobile devices
- **Glassmorphism UI**: Modern, translucent interface elements with blur effects
- **Touch Gestures**: Swipe, pinch, and tap gestures for intuitive navigation
- **Mobile-Optimized Controls**: Bottom navigation bar and floating action buttons
- **Dark Mode**: Automatic and manual dark mode support
- **Improved Face Processing UI**: Enhanced controls for face blur and emotion detection
- **Accessibility Improvements**: Better contrast, focus states, and screen reader support

## Installation

1. Add the CSS and JavaScript files to your project:

```
public/css/kosmohealth-meeting-enhanced.css
public/js/kosmohealth-meeting-mobile.js
```

2. Include the files in your meeting view:

```html
<!-- In your HTML head -->
<link rel="stylesheet" href="{{ asset('css/kosmohealth-meeting-enhanced.css') }}">

<!-- At the end of your body -->
<script src="{{ asset('js/kosmohealth-meeting-mobile.js') }}"></script>
```

## Usage

The enhancements are automatically applied when the page loads on a mobile device. No additional configuration is required.

### Manual Initialization

If you need to manually initialize the enhancements:

```javascript
// Initialize mobile enhancements
if (typeof KosmoMeetingMobile !== 'undefined') {
    KosmoMeetingMobile.init({
        enableSwipeGestures: true,
        enableDarkMode: true
    });
}
```

## Configuration Options

You can customize the behavior by modifying the configuration in `kosmohealth-meeting-mobile.js`:

```javascript
const config = {
    enableSwipeGestures: true,      // Enable swipe gestures for navigation
    enableTapToToggleControls: true, // Show/hide controls on tap
    enablePinchToZoom: true,        // Zoom in/out with pinch gesture
    enableDoubleTapToSwitchCamera: true, // Switch camera on double tap
    enableShakeToReport: false,     // Shake device to report an issue
    enableDarkMode: true,           // Enable dark mode support
    enableAccessibility: true       // Enable accessibility features
};
```

## Integration with Face Processing

The mobile enhancements integrate with the existing face processing features:

1. A floating action button provides quick access to face processing controls
2. The face processing controls are styled with glassmorphism for a consistent look
3. Notifications for face detection status and emotions are displayed in a mobile-friendly way

## Mobile-Specific Features

### Swipe Gestures

- **Swipe left**: Open sidebar (participants, chat)
- **Swipe right**: Close sidebar
- **Swipe up**: Show controls
- **Swipe down**: Hide controls

### Double Tap

- **Double tap on video**: Switch between front and rear camera

### Bottom Navigation

The bottom navigation bar provides quick access to common actions:

- Toggle microphone
- Toggle camera
- Open chat
- View participants
- End call

### Floating Action Button (FAB)

The FAB provides access to additional actions:

- Toggle face blur
- Toggle emotion detection
- Raise hand
- More options

## Customization

### Colors and Themes

You can customize the colors by modifying the CSS variables in `kosmohealth-meeting-enhanced.css`:

```css
:root {
  --primary-color: #d15465;
  --secondary-color: #3f37c9;
  --accent-color: #4cc9f0;
  /* More variables... */
}
```

### Dark Mode

Dark mode is automatically enabled based on user preference, but can also be toggled manually in the settings panel.

## Browser Compatibility

- Chrome for Android 80+
- Safari for iOS 13+
- Samsung Internet 12+
- Firefox for Android 68+

## Testing

A test template is provided at `public/meeting-mobile-template.html` to demonstrate the enhancements.

## Troubleshooting

### Controls Not Showing

If the controls are not visible:

1. Tap anywhere on the screen to show them
2. Check if the JavaScript file is properly loaded
3. Verify that the device is detected as mobile

### Video Not Displaying Correctly

If videos are not displaying correctly:

1. Ensure the video elements have the correct classes
2. Check browser permissions for camera and microphone
3. Verify that the video streams are properly connected

## License

This package is part of the Kosmohealth platform and is subject to the same license terms.

## Credits

Developed by Kosmohealth Team
