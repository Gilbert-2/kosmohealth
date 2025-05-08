/**
 * Face Processing Plugin for Kosmohealth
 *
 * This plugin adds AI emotion detection and face blur capabilities
 * to the Kosmohealth video conferencing system.
 */

(function() {
  // Configuration
  const config = {
    enabled: true,
    showControls: true,
    showCanvas: true,
    showEmotionInfo: true,
    autoBlurOnDiscomfort: true,
    processingFps: 5,
    videoElementSelectors: [
      '#localVideo',                // Common ID for local video
      '.local-video',               // Common class for local video
      'video[id*="local"]',         // Any video with "local" in the ID
      'video.local',                // Any video with "local" class
      'video[id*="self"]',          // Any video with "self" in the ID
      'video.self',                 // Any video with "self" class
      'video[id*="my-video"]',      // Any video with "my-video" in the ID
      'video.my-video',             // Any video with "my-video" class
      'video',                      // Any video element (fallback)
      'video.video-element',        // Kosmohealth specific class
      '.video-container video',     // Video inside container
      '.video-wrapper video',       // Video inside wrapper
      '.user-video-container video' // User video container
    ],
    discomfortEmotions: ['angry', 'fearful', 'disgusted', 'sad'],
    emotionThreshold: 0.7,
    debug: true // Enable debug mode
  };

  // State
  let state = {
    modelsLoaded: false,
    isProcessing: false,
    blurEnabled: false,
    emotionDetectionEnabled: false,
    lastEmotions: null,
    videoElement: null,
    canvas: null,
    processingInterval: null,
    faceapi: null,
    discomfortDetected: false,
    notificationElement: null
  };

  // Load face-api.js
  function loadScript(url) {
    return new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.src = url;
      script.onload = resolve;
      script.onerror = reject;
      document.head.appendChild(script);
    });
  }

  // Create UI elements
  function createUI() {
    // Create container
    const container = document.createElement('div');
    container.id = 'face-processing-container';
    container.style.cssText = `
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 9999;
      background-color: rgba(0, 0, 0, 0.7);
      border-radius: 8px;
      padding: 10px;
      color: white;
      font-family: Arial, sans-serif;
      width: 200px;
      cursor: move;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
    `;

    // Create title with minimize button
    const titleBar = document.createElement('div');
    titleBar.style.cssText = `
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 10px;
      padding-bottom: 5px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    `;

    const title = document.createElement('div');
    title.textContent = 'Face Processing';
    title.style.cssText = `
      font-weight: bold;
    `;

    const minimizeBtn = document.createElement('button');
    minimizeBtn.textContent = '-';
    minimizeBtn.style.cssText = `
      background: none;
      border: none;
      color: white;
      font-size: 16px;
      cursor: pointer;
      padding: 0 5px;
    `;

    let minimized = false;
    const contentDiv = document.createElement('div');
    contentDiv.id = 'face-processing-content';

    minimizeBtn.addEventListener('click', () => {
      minimized = !minimized;
      contentDiv.style.display = minimized ? 'none' : 'block';
      minimizeBtn.textContent = minimized ? '+' : '-';
      container.style.width = minimized ? 'auto' : '200px';
    });

    titleBar.appendChild(title);
    titleBar.appendChild(minimizeBtn);
    container.appendChild(titleBar);
    container.appendChild(contentDiv);

    // Make the container draggable
    let isDragging = false;
    let offsetX, offsetY;

    titleBar.addEventListener('mousedown', (e) => {
      isDragging = true;
      offsetX = e.clientX - container.getBoundingClientRect().left;
      offsetY = e.clientY - container.getBoundingClientRect().top;
    });

    document.addEventListener('mousemove', (e) => {
      if (isDragging) {
        container.style.left = (e.clientX - offsetX) + 'px';
        container.style.top = (e.clientY - offsetY) + 'px';
        container.style.right = 'auto';
        container.style.bottom = 'auto';
      }
    });

    document.addEventListener('mouseup', () => {
      isDragging = false;
    });

    // Create blur toggle
    const blurToggle = document.createElement('div');
    blurToggle.style.cssText = `
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 8px;
    `;

    const blurLabel = document.createElement('label');
    blurLabel.textContent = 'Blur Face';
    blurLabel.style.cssText = `
      margin-right: 10px;
    `;

    const blurCheckbox = document.createElement('input');
    blurCheckbox.type = 'checkbox';
    blurCheckbox.id = 'face-blur-toggle';
    blurCheckbox.checked = state.blurEnabled;
    blurCheckbox.addEventListener('change', (e) => {
      state.blurEnabled = e.target.checked;
      if (state.canvas) {
        state.canvas.style.display = state.blurEnabled ? 'block' : 'none';
      }
    });

    blurToggle.appendChild(blurLabel);
    blurToggle.appendChild(blurCheckbox);
    contentDiv.appendChild(blurToggle);

    // Create emotion detection toggle
    const emotionToggle = document.createElement('div');
    emotionToggle.style.cssText = `
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 8px;
    `;

    const emotionLabel = document.createElement('label');
    emotionLabel.textContent = 'Emotion Detection';
    emotionLabel.style.cssText = `
      margin-right: 10px;
    `;

    const emotionCheckbox = document.createElement('input');
    emotionCheckbox.type = 'checkbox';
    emotionCheckbox.id = 'emotion-detection-toggle';
    emotionCheckbox.checked = state.emotionDetectionEnabled;
    emotionCheckbox.addEventListener('change', (e) => {
      state.emotionDetectionEnabled = e.target.checked;

      // Show/hide emotion info
      const emotionInfo = document.getElementById('emotion-info');
      if (emotionInfo) {
        emotionInfo.style.display = state.emotionDetectionEnabled ? 'block' : 'none';
      }
    });

    emotionToggle.appendChild(emotionLabel);
    emotionToggle.appendChild(emotionCheckbox);
    contentDiv.appendChild(emotionToggle);

    // Create show detection box toggle
    const detectionBoxToggle = document.createElement('div');
    detectionBoxToggle.style.cssText = `
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 8px;
    `;

    const detectionBoxLabel = document.createElement('label');
    detectionBoxLabel.textContent = 'Show Detection Box';
    detectionBoxLabel.style.cssText = `
      margin-right: 10px;
    `;

    const detectionBoxCheckbox = document.createElement('input');
    detectionBoxCheckbox.type = 'checkbox';
    detectionBoxCheckbox.id = 'detection-box-toggle';
    detectionBoxCheckbox.checked = true;
    detectionBoxCheckbox.addEventListener('change', (e) => {
      state.showDetectionBox = e.target.checked;
    });

    detectionBoxToggle.appendChild(detectionBoxLabel);
    detectionBoxToggle.appendChild(detectionBoxCheckbox);
    contentDiv.appendChild(detectionBoxToggle);

    // Create emotion info display
    const emotionInfo = document.createElement('div');
    emotionInfo.id = 'emotion-info';
    emotionInfo.style.cssText = `
      position: fixed;
      top: 70px;
      right: 20px;
      z-index: 9999;
      background-color: rgba(0, 0, 0, 0.7);
      border-radius: 4px;
      padding: 8px 12px;
      color: white;
      font-family: Arial, sans-serif;
      font-weight: bold;
      display: none;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    `;

    // Create notification element
    const notification = document.createElement('div');
    notification.id = 'discomfort-notification';
    notification.style.cssText = `
      position: fixed;
      top: 20px;
      left: 50%;
      transform: translateX(-50%);
      z-index: 9999;
      background-color: rgba(255, 193, 7, 0.9);
      border-radius: 4px;
      padding: 10px 15px;
      color: black;
      font-family: Arial, sans-serif;
      font-weight: bold;
      display: none;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    `;
    notification.textContent = 'Discomfort detected';
    state.notificationElement = notification;

    // Add elements to the document
    document.body.appendChild(container);
    document.body.appendChild(emotionInfo);
    document.body.appendChild(notification);

    // Create canvas for processing
    const canvas = document.createElement('canvas');
    canvas.id = 'face-processing-canvas';
    canvas.style.cssText = `
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 10;
      pointer-events: none;
      display: none;
    `;
    state.canvas = canvas;

    // Set initial state
    state.showDetectionBox = true;

    // Find the video element container and add the canvas
    findVideoElement().then(videoElement => {
      if (videoElement) {
        const videoContainer = videoElement.parentElement;
        videoContainer.style.position = 'relative';
        videoContainer.appendChild(canvas);

        // Set canvas dimensions to match video
        canvas.width = videoElement.videoWidth || 640;
        canvas.height = videoElement.videoHeight || 480;

        // Update canvas dimensions when video dimensions change
        videoElement.addEventListener('loadedmetadata', () => {
          canvas.width = videoElement.videoWidth;
          canvas.height = videoElement.videoHeight;
        });
      }
    });
  }

  // Find the video element using the selectors
  function findVideoElement() {
    return new Promise((resolve) => {
      // Try to find the video element
      function tryFind() {
        if (config.debug) {
          console.log('Searching for video element...');
        }

        for (const selector of config.videoElementSelectors) {
          const element = document.querySelector(selector);
          if (config.debug) {
            console.log(`Trying selector: ${selector}`, element ? 'FOUND' : 'not found');
          }

          if (element && element.tagName === 'VIDEO') {
            if (config.debug) {
              console.log('Video element found:', element);
              console.log('Video dimensions:', element.videoWidth, 'x', element.videoHeight);
              console.log('Video ready state:', element.readyState);
            }

            // Check if the video has valid dimensions
            if (element.videoWidth > 0 && element.videoHeight > 0) {
              state.videoElement = element;
              resolve(element);
              return;
            } else if (element.readyState >= 1) {
              // If video metadata is loaded but dimensions are not available yet
              state.videoElement = element;

              // Wait for video to have dimensions
              element.addEventListener('loadedmetadata', () => {
                if (config.debug) {
                  console.log('Video metadata loaded, dimensions:', element.videoWidth, 'x', element.videoHeight);
                }
                resolve(element);
              }, { once: true });
              return;
            } else {
              // Wait for metadata to load
              element.addEventListener('loadedmetadata', () => {
                if (config.debug) {
                  console.log('Video metadata loaded, dimensions:', element.videoWidth, 'x', element.videoHeight);
                }
                state.videoElement = element;
                resolve(element);
              }, { once: true });
              return;
            }
          }
        }

        // Try all videos as a last resort
        const allVideos = document.querySelectorAll('video');
        if (config.debug) {
          console.log('Found', allVideos.length, 'video elements in total');
        }

        if (allVideos.length > 0) {
          // Use the first video that's playing or has dimensions
          for (const video of allVideos) {
            if (!video.paused || (video.videoWidth > 0 && video.videoHeight > 0)) {
              if (config.debug) {
                console.log('Using video element:', video);
                console.log('Video dimensions:', video.videoWidth, 'x', video.videoHeight);
              }
              state.videoElement = video;
              resolve(video);
              return;
            }
          }

          // If no playing videos, use the first one
          if (config.debug) {
            console.log('Using first available video element:', allVideos[0]);
          }
          state.videoElement = allVideos[0];
          resolve(allVideos[0]);
          return;
        }

        // If not found, try again after a delay
        if (config.debug) {
          console.log('No video element found, retrying in 1 second...');
        }

        setTimeout(() => {
          tryFind();
        }, 1000);
      }

      tryFind();
    });
  }

  // Load face-api.js models
  async function loadModels() {
    try {
      // Set the models path
      await Promise.all([
        state.faceapi.nets.tinyFaceDetector.loadFromUri('/js/face-api-models'),
        state.faceapi.nets.faceLandmark68Net.loadFromUri('/js/face-api-models'),
        state.faceapi.nets.faceRecognitionNet.loadFromUri('/js/face-api-models'),
        state.faceapi.nets.faceExpressionNet.loadFromUri('/js/face-api-models')
      ]);

      state.modelsLoaded = true;
      console.log('Face-api models loaded successfully');
      return true;
    } catch (error) {
      console.error('Error loading face-api models:', error);

      // Show error message with link to download models
      const errorMessage = document.createElement('div');
      errorMessage.style.cssText = `
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        z-index: 10000;
        max-width: 400px;
        text-align: center;
      `;

      errorMessage.innerHTML = `
        <h3 style="margin-top: 0;">Face Processing Error</h3>
        <p>Could not load face-api.js models. Please download the models first.</p>
        <a href="/download-models.html" target="_blank" style="
          display: inline-block;
          padding: 8px 16px;
          background-color: #007bff;
          color: white;
          text-decoration: none;
          border-radius: 4px;
          margin-top: 10px;
        ">Download Models</a>
        <button id="close-error-message" style="
          display: block;
          width: 100%;
          padding: 8px 16px;
          background-color: #f0f0f0;
          border: none;
          border-radius: 4px;
          margin-top: 10px;
          cursor: pointer;
        ">Close</button>
      `;

      document.body.appendChild(errorMessage);

      document.getElementById('close-error-message').addEventListener('click', () => {
        document.body.removeChild(errorMessage);
      });

      return false;
    }
  }

  // Process video frame
  async function processFrame() {
    // Check if video element is still valid, if not try to find it again
    if (!state.videoElement || state.videoElement.paused || state.videoElement.ended) {
      if (config.debug) {
        console.log('Video element not ready, attempting to find it again...');
      }
      await findVideoElement();
      if (!state.videoElement) {
        if (config.debug) {
          console.log('Still no valid video element found, skipping frame processing');
        }
        return;
      }
    }

    // Check if models are loaded and not already processing
    if (!state.modelsLoaded || state.isProcessing) {
      return;
    }

    state.isProcessing = true;

    try {
      if (config.debug) {
        console.log('Processing video frame, video dimensions:',
                   state.videoElement.videoWidth, 'x', state.videoElement.videoHeight);
      }

      // Ensure canvas dimensions match video
      if (state.canvas) {
        if (state.canvas.width !== state.videoElement.videoWidth ||
            state.canvas.height !== state.videoElement.videoHeight) {
          state.canvas.width = state.videoElement.videoWidth || 640;
          state.canvas.height = state.videoElement.videoHeight || 480;
          if (config.debug) {
            console.log('Updated canvas dimensions to match video:',
                       state.canvas.width, 'x', state.canvas.height);
          }
        }
      }

      // Detect all faces with expressions
      const detections = await state.faceapi.detectAllFaces(
        state.videoElement,
        new state.faceapi.TinyFaceDetectorOptions()
      )
      .withFaceLandmarks()
      .withFaceExpressions();

      if (config.debug) {
        console.log('Detected faces:', detections.length);
      }

      // Process emotions if enabled
      if (state.emotionDetectionEnabled && detections.length > 0) {
        processEmotions(detections);
      }

      // Draw results on canvas if provided
      if (state.canvas && (state.blurEnabled || state.showDetectionBox)) {
        const dims = state.faceapi.matchDimensions(state.canvas, state.videoElement, true);
        const resizedDetections = state.faceapi.resizeResults(detections, dims);

        // Clear canvas
        const ctx = state.canvas.getContext('2d');
        ctx.clearRect(0, 0, state.canvas.width, state.canvas.height);

        // Always show canvas when features are enabled
        state.canvas.style.display = 'block';

        // Draw the video frame first
        ctx.drawImage(state.videoElement, 0, 0, state.canvas.width, state.canvas.height);

        // If we have faces to process
        if (detections.length > 0) {
          // Apply blur if enabled
          if (state.blurEnabled) {
            applyFaceBlur(ctx, resizedDetections);
          }

          // Draw detection boxes on top if enabled
          if (state.showDetectionBox) {
            drawDetectionBoxes(ctx, resizedDetections);
          }
        }
      } else if (state.canvas) {
        // Hide canvas if no features are enabled
        state.canvas.style.display = 'none';
      }
    } catch (error) {
      console.error('Error processing video frame:', error);
      if (config.debug) {
        console.error('Error details:', error.message);
        console.error('Stack trace:', error.stack);
      }
    } finally {
      state.isProcessing = false;
    }
  }

  // Draw detection boxes around faces
  function drawDetectionBoxes(ctx, detections) {
    if (!detections || detections.length === 0) return;

    detections.forEach(detection => {
      const { box } = detection.detection;

      // Draw rectangle around face
      ctx.strokeStyle = '#00FF00'; // Green color
      ctx.lineWidth = 3;
      ctx.strokeRect(box.x, box.y, box.width, box.height);

      // Add label if emotion detection is enabled
      if (state.emotionDetectionEnabled && detection.expressions) {
        // Find dominant emotion
        let dominantEmotion = null;
        let highestScore = 0;

        for (const [emotion, score] of Object.entries(detection.expressions)) {
          if (score > highestScore) {
            highestScore = score;
            dominantEmotion = emotion;
          }
        }

        if (dominantEmotion && highestScore > 0.5) {
          // Format emotion text
          const emotionText = dominantEmotion.charAt(0).toUpperCase() + dominantEmotion.slice(1);
          const scoreText = Math.round(highestScore * 100) + '%';
          const text = `${emotionText}: ${scoreText}`;

          // Draw background for text
          ctx.fillStyle = 'rgba(0, 0, 0, 0.7)';
          ctx.fillRect(box.x, box.y - 30, ctx.measureText(text).width + 10, 30);

          // Draw text
          ctx.fillStyle = '#FFFFFF';
          ctx.font = '16px Arial';
          ctx.fillText(text, box.x + 5, box.y - 10);
        }
      }
    });
  }

  // Process emotions from detections
  function processEmotions(detections) {
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
    if (highestScore > config.emotionThreshold) {
      state.lastEmotions = {
        dominant: dominantEmotion,
        score: highestScore,
        all: expressions
      };

      // Update emotion info display
      updateEmotionDisplay(dominantEmotion, highestScore);

      // Check if the emotion indicates discomfort
      if (config.discomfortEmotions.includes(dominantEmotion)) {
        handleDiscomfort(dominantEmotion, highestScore);
      }
    }
  }

  // Update emotion display
  function updateEmotionDisplay(emotion, score) {
    const emotionInfo = document.getElementById('emotion-info');
    if (emotionInfo) {
      // Format the emotion text with score percentage
      const formattedEmotion = emotion.charAt(0).toUpperCase() + emotion.slice(1);
      const scorePercentage = Math.round(score * 100);
      emotionInfo.innerHTML = `<strong>${formattedEmotion}</strong> <span>${scorePercentage}%</span>`;
      emotionInfo.style.display = 'block';

      // Set color based on emotion
      const emotionColors = {
        'happy': 'rgba(76, 175, 80, 0.9)',
        'sad': 'rgba(33, 150, 243, 0.9)',
        'angry': 'rgba(244, 67, 54, 0.9)',
        'fearful': 'rgba(156, 39, 176, 0.9)',
        'disgusted': 'rgba(121, 85, 72, 0.9)',
        'surprised': 'rgba(255, 193, 7, 0.9)',
        'neutral': 'rgba(158, 158, 158, 0.9)'
      };

      // Add emoji based on emotion
      const emotionEmojis = {
        'happy': '😊',
        'sad': '😢',
        'angry': '😠',
        'fearful': '😨',
        'disgusted': '🤢',
        'surprised': '😲',
        'neutral': '😐'
      };

      // Add emoji to display
      const emoji = emotionEmojis[emotion] || '';
      if (emoji) {
        emotionInfo.innerHTML = `${emoji} ${emotionInfo.innerHTML}`;
      }

      // Set background color
      emotionInfo.style.backgroundColor = emotionColors[emotion] || 'rgba(0, 0, 0, 0.7)';

      // Add a subtle border
      emotionInfo.style.border = '1px solid rgba(255, 255, 255, 0.3)';

      // Add discomfort indicator if applicable
      if (config.discomfortEmotions.includes(emotion)) {
        emotionInfo.style.border = '2px solid #ff5722';
        emotionInfo.style.boxShadow = '0 0 10px rgba(255, 87, 34, 0.5)';
      } else {
        emotionInfo.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.2)';
      }
    }
  }

  // Handle discomfort detection
  function handleDiscomfort(emotion, score) {
    state.discomfortDetected = true;

    // Auto-enable blur if configured
    if (config.autoBlurOnDiscomfort && !state.blurEnabled) {
      state.blurEnabled = true;
      const blurToggle = document.getElementById('face-blur-toggle');
      if (blurToggle) {
        blurToggle.checked = true;
      }
      if (state.canvas) {
        state.canvas.style.display = 'block';
      }
    }

    // Show notification
    if (state.notificationElement) {
      // Format the emotion text
      const formattedEmotion = emotion.charAt(0).toUpperCase() + emotion.slice(1);
      const scorePercentage = Math.round(score * 100);

      // Set notification text with more details
      state.notificationElement.innerHTML = `
        <div style="display: flex; align-items: center;">
          <div style="margin-right: 10px; font-size: 24px;">⚠️</div>
          <div>
            <div style="font-weight: bold; margin-bottom: 3px;">Discomfort Detected</div>
            <div style="font-size: 12px;">${formattedEmotion} (${scorePercentage}%)</div>
          </div>
        </div>
      `;

      // Add close button
      const closeButton = document.createElement('div');
      closeButton.innerHTML = '×';
      closeButton.style.cssText = `
        position: absolute;
        top: 5px;
        right: 8px;
        font-size: 16px;
        cursor: pointer;
        opacity: 0.7;
      `;
      closeButton.addEventListener('click', () => {
        state.notificationElement.style.display = 'none';
      });
      state.notificationElement.appendChild(closeButton);

      // Set notification style
      state.notificationElement.style.cssText = `
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 9999;
        background-color: rgba(255, 193, 7, 0.9);
        border-radius: 4px;
        padding: 10px 15px;
        color: black;
        font-family: Arial, sans-serif;
        display: block;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        border-left: 4px solid #ff5722;
        min-width: 250px;
        position: relative;
        animation: fadeInDown 0.5s;
      `;

      // Add animation style
      const style = document.createElement('style');
      style.textContent = `
        @keyframes fadeInDown {
          from {
            opacity: 0;
            transform: translate(-50%, -20px);
          }
          to {
            opacity: 1;
            transform: translate(-50%, 0);
          }
        }
        @keyframes fadeOut {
          from {
            opacity: 1;
          }
          to {
            opacity: 0;
          }
        }
      `;
      document.head.appendChild(style);

      // Hide after 8 seconds
      setTimeout(() => {
        if (state.notificationElement.style.display !== 'none') {
          state.notificationElement.style.animation = 'fadeOut 0.5s';
          setTimeout(() => {
            state.notificationElement.style.display = 'none';
          }, 500);
        }
      }, 8000);
    }
  }

  // Apply blur effect to faces
  function applyFaceBlur(ctx, detections) {
    if (!detections || detections.length === 0) return;

    // Video frame is already drawn to canvas in processFrame

    detections.forEach(detection => {
      const { box } = detection.detection;

      // Ensure box coordinates are valid
      const x = Math.max(0, Math.floor(box.x));
      const y = Math.max(0, Math.floor(box.y));
      const width = Math.min(Math.floor(box.width), state.canvas.width - x);
      const height = Math.min(Math.floor(box.height), state.canvas.height - y);

      if (width <= 0 || height <= 0) {
        if (config.debug) {
          console.log('Invalid face box dimensions, skipping blur');
        }
        return;
      }

      try {
        // Expand the area slightly for better blur edges
        const expandedX = Math.max(0, x - 5);
        const expandedY = Math.max(0, y - 5);
        const expandedWidth = Math.min(width + 10, state.canvas.width - expandedX);
        const expandedHeight = Math.min(height + 10, state.canvas.height - expandedY);

        // Get the face region
        let faceImageData;
        try {
          faceImageData = ctx.getImageData(expandedX, expandedY, expandedWidth, expandedHeight);
        } catch (e) {
          if (config.debug) {
            console.error('Error getting image data:', e);
            console.log('Coordinates:', expandedX, expandedY, expandedWidth, expandedHeight);
            console.log('Canvas dimensions:', state.canvas.width, 'x', state.canvas.height);
          }
          return;
        }

        // Apply pixelation blur (more efficient than Gaussian)
        const pixelSize = Math.max(10, Math.floor(Math.min(width, height) / 10));
        pixelateImageData(faceImageData, pixelSize);

        // Put the blurred face back
        ctx.putImageData(faceImageData, expandedX, expandedY);

        // Draw border around blurred area if detection box is enabled
        if (state.showDetectionBox) {
          ctx.strokeStyle = 'rgba(255, 0, 0, 0.5)';
          ctx.lineWidth = 2;
          ctx.strokeRect(x, y, width, height);
        }
      } catch (error) {
        console.error('Error applying face blur:', error);
        if (config.debug) {
          console.error('Error details:', error.message);
        }
      }
    });
  }

  // Pixelate image data for efficient blurring
  function pixelateImageData(imageData, pixelSize) {
    const { data, width, height } = imageData;

    // For each pixel block
    for (let y = 0; y < height; y += pixelSize) {
      for (let x = 0; x < width; x += pixelSize) {
        // Calculate the size of this pixel block (handle edge cases)
        const blockWidth = Math.min(pixelSize, width - x);
        const blockHeight = Math.min(pixelSize, height - y);

        // Calculate average color of the block
        let r = 0, g = 0, b = 0, a = 0, count = 0;

        for (let by = 0; by < blockHeight; by++) {
          for (let bx = 0; bx < blockWidth; bx++) {
            const i = ((y + by) * width + (x + bx)) * 4;
            r += data[i];
            g += data[i + 1];
            b += data[i + 2];
            a += data[i + 3];
            count++;
          }
        }

        // Calculate average
        r = Math.round(r / count);
        g = Math.round(g / count);
        b = Math.round(b / count);
        a = Math.round(a / count);

        // Apply average color to all pixels in the block
        for (let by = 0; by < blockHeight; by++) {
          for (let bx = 0; bx < blockWidth; bx++) {
            const i = ((y + by) * width + (x + bx)) * 4;
            data[i] = r;
            data[i + 1] = g;
            data[i + 2] = b;
            data[i + 3] = a;
          }
        }
      }
    }
  }

  // Start continuous processing
  function startProcessing() {
    if (!state.modelsLoaded) {
      console.error('Models not loaded. Call loadModels() first.');
      return;
    }

    const intervalMs = 1000 / config.processingFps;

    state.processingInterval = setInterval(() => {
      processFrame();
    }, intervalMs);
  }

  // Stop continuous processing
  function stopProcessing() {
    if (state.processingInterval) {
      clearInterval(state.processingInterval);
      state.processingInterval = null;
    }
  }

  // Initialize the plugin
  async function init() {
    console.log('Initializing Face Processing Plugin...');

    try {
      // Check if face-api.js is already loaded
      if (!window.faceapi) {
        // Load face-api.js if not already loaded
        await loadScript('/js/face-api.min.js');
      }

      state.faceapi = window.faceapi;

      // Handle WebRTC signaling errors
      window.addEventListener('unhandledrejection', function(event) {
        if (event.reason && event.reason.message &&
            (event.reason.message.includes('signalv2.kodemint.in') ||
             event.reason.message.includes('socket.io'))) {
          console.warn('WebRTC signaling error detected and suppressed:', event.reason);
          event.preventDefault(); // Prevent the error from appearing in console
          event.stopPropagation();
        }
      });

      // Load models
      const modelsLoaded = await loadModels();
      if (!modelsLoaded) {
        console.error('Failed to load face-api models');
        return;
      }

      // Create UI
      createUI();

      // Enable features by default for testing
      state.blurEnabled = true;
      state.emotionDetectionEnabled = true;
      state.showDetectionBox = true;

      // Update UI checkboxes
      const blurToggle = document.getElementById('face-blur-toggle');
      if (blurToggle) blurToggle.checked = true;

      const emotionToggle = document.getElementById('emotion-detection-toggle');
      if (emotionToggle) emotionToggle.checked = true;

      const detectionBoxToggle = document.getElementById('detection-box-toggle');
      if (detectionBoxToggle) detectionBoxToggle.checked = true;

      // Start processing
      startProcessing();

      console.log('Face Processing Plugin initialized successfully');
    } catch (error) {
      console.error('Error initializing Face Processing Plugin:', error);
    }
  }

  // Initialize when the page is loaded
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
