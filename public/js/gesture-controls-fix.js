// Fix for hand gesture detection controls
(function() {
  // Add gesture controls to the meeting UI
  function addGestureControls() {
    // Check if controls already exist
    if (document.querySelector('.gesture-controls-fix')) return;
    
    // Create controls container
    const controls = document.createElement('div');
    controls.className = 'gesture-controls-fix';
    controls.style.position = 'absolute';
    controls.style.bottom = '20px';
    controls.style.right = '20px';
    controls.style.zIndex = '1050';
    controls.style.display = 'flex';
    controls.style.gap = '10px';
    
    // Create gesture toggle button
    const gestureBtn = document.createElement('button');
    gestureBtn.className = 'gesture-toggle-btn';
    gestureBtn.innerHTML = '<i class="fas fa-sign-language"></i>';
    gestureBtn.title = 'Toggle Hand Gesture Detection';
    gestureBtn.style.width = '50px';
    gestureBtn.style.height = '50px';
    gestureBtn.style.borderRadius = '50%';
    gestureBtn.style.background = 'rgba(255, 255, 255, 0.2)';
    gestureBtn.style.backdropFilter = 'blur(10px)';
    gestureBtn.style.border = 'none';
    gestureBtn.style.color = 'white';
    gestureBtn.style.fontSize = '20px';
    gestureBtn.style.cursor = 'pointer';
    gestureBtn.style.boxShadow = '0 4px 10px rgba(0, 0, 0, 0.2)';
    
    // Add click handler
    gestureBtn.addEventListener('click', function() {
      // Find the gesture mode button in the gesture recognition wrapper
      const modeBtn = document.querySelector('.gesture-mode-btn');
      if (modeBtn) {
        // Simulate a click on the button
        modeBtn.click();
        
        // Update button state
        if (modeBtn.classList.contains('active')) {
          gestureBtn.style.background = '#ff5c8a';
        } else {
          gestureBtn.style.background = 'rgba(255, 255, 255, 0.2)';
        }
      }
    });
    
    // Add to controls
    controls.appendChild(gestureBtn);
    
    // Find the video container
    const videoContainer = document.querySelector('.video-container') || 
                          document.querySelector('.meeting-video-container') ||
                          document.querySelector('.rtc-video-container');
    
    if (videoContainer) {
      // Make sure container has position relative
      videoContainer.style.position = 'relative';
      
      // Add controls to container
      videoContainer.appendChild(controls);
    } else {
      // If no container found, add to body
      document.body.appendChild(controls);
    }
  }
  
  // Run periodically to ensure controls are added
  setInterval(addGestureControls, 2000);
})();
