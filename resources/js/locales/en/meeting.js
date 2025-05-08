export default {
  meeting: 'Meeting',
  meetings: 'Meetings',
  meeting_list: 'Meeting List',
  add_meeting: 'Add Meeting',
  edit_meeting: 'Edit Meeting',
  view_meeting: 'View Meeting',
  meeting_detail: 'Meeting Detail',
  
  config: {
    face_processing: 'Face Processing',
    enable_emotion_detection: 'Enable Emotion Detection',
    enable_emotion_detection_help: 'Detect emotions of participants during meetings',
    enable_face_blur: 'Enable Face Blur',
    enable_face_blur_help: 'Allow participants to blur their faces during meetings',
    auto_blur_on_discomfort: 'Auto Blur on Discomfort',
    auto_blur_on_discomfort_help: 'Automatically blur faces when discomfort is detected',
    show_emotion_info: 'Show Emotion Info',
    show_emotion_info_help: 'Display detected emotions during meetings',
    processing_fps: 'Processing FPS',
    processing_fps_help: 'Frames per second for face processing (1-10)',
    processing_fps_range_error: 'FPS must be between 1 and 10'
  },
  
  face_processing: {
    blur_face: 'Blur Face',
    emotion_detection: 'Emotion Detection',
    emotions: {
      happy: 'Happy',
      sad: 'Sad',
      angry: 'Angry',
      fearful: 'Fearful',
      disgusted: 'Disgusted',
      surprised: 'Surprised',
      neutral: 'Neutral'
    },
    discomfort_detected: 'Discomfort detected',
    face_blurred: 'Face blurred for privacy'
  }
}
