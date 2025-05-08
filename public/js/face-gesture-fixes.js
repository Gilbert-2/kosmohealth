/**
 * Face Processing and Gesture Recognition Fixes
 * This script fixes issues with face tracking, blur, and gesture recognition
 */

(function() {
  // Wait for DOM to be ready
  document.addEventListener('DOMContentLoaded', function() {
    // Load our CSS fixes
    loadCSS('/css/face-gesture-fixes.css');
    
    // Initialize fixes when in a meeting
    if (window.location.href.includes('/app/live/meetings/')) {
      initializeFixes();
    }
  });
  
  // Load CSS file
  function loadCSS(href) {
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = href;
    document.head.appendChild(link);
  }
  
  // Initialize all fixes
  function initializeFixes() {
    // Set an interval to continuously check and fix issues
    setInterval(fixFaceProcessing, 1000);
    setInterval(fixGestureRecognition, 1000);
    
    // Add gesture controls to the meeting UI
    addGestureControls();
    
    console.log('Face and gesture processing fixes initialized');
  }
  
  // Fix face processing issues
  function fixFaceProcessing() {
    // Find all face processing canvases
    const canvases = document.querySelectorAll('.face-processing-canvas');
    canvases.forEach(canvas => {
      // Ensure the canvas is properly positioned
      canvas.style.position = 'absolute';
      canvas.style.top = '0';
      canvas.style.left = '0';
      canvas.style.width = '100%';
      canvas.style.height = '100%';
      canvas.style.zIndex = '30';
      canvas.style.pointerEvents = 'none';
      
      // Make sure the canvas is visible
      canvas.style.display = 'block';
      
      // Ensure the parent container has position relative
      const container = canvas.parentElement;
      if (container) {
        container.style.position = 'relative';
      }
    });
    
    // Find face processing controls
    const controls = document.querySelectorAll('.face-processing-controls');
    controls.forEach(control => {
      // Ensure controls are above other elements
      control.style.zIndex = '1050';
    });
  }
  
  // Fix gesture recognition issues
  function fixGestureRecognition() {
    // Find all gesture recognition canvases
    const canvases = document.querySelectorAll('.gesture-recognition-canvas');
    canvases.forEach(canvas => {
      // Ensure the canvas is properly positioned
      canvas.style.position = 'absolute';
      canvas.style.top = '0';
      canvas.style.left = '0';
      canvas.style.width = '100%';
      canvas.style.height = '100%';
      canvas.style.zIndex = '31';
      canvas.style.pointerEvents = 'none';
      
      // Make sure the canvas is visible
      canvas.style.display = 'block';
    });
    
    // Find gesture interpreter panels
    const interpreters = document.querySelectorAll('.gesture-interpreter');
    interpreters.forEach(interpreter => {
      // Ensure interpreter is above other elements
      interpreter.style.zIndex = '1050';
    });
    
    // Find gesture info panels
    const infoPanels = document.querySelectorAll('.gesture-info');
    infoPanels.forEach(panel => {
      // Ensure panel is above other elements
      panel.style.zIndex = '1050';
    });
  }
  
  // Add gesture controls to the meeting UI
  function addGestureControls() {
    // Wait for the meeting footer to be available
    const checkFooter = setInterval(() => {
      const footer = document.querySelector('.meeting-footer');
      if (footer) {
        clearInterval(checkFooter);
        
        // Create gesture toggle button
        const gestureButton = document.createElement('button');
        gestureButton.className = 'btn btn-icon gesture-toggle-btn';
        gestureButton.innerHTML = '<i class="fas fa-sign-language"></i>';
        gestureButton.title = 'Toggle Gesture Recognition';
        
        // Add click handler
        gestureButton.addEventListener('click', toggleGestureMode);
        
        // Add to footer
        footer.appendChild(gestureButton);
      }
    }, 1000);
  }
  
  // Toggle gesture mode
  function toggleGestureMode() {
    // Find the gesture mode button in the gesture recognition wrapper
    const gestureBtn = document.querySelector('.gesture-recognition-controls .gesture-mode-btn');
    if (gestureBtn) {
      // Simulate a click on the button
      gestureBtn.click();
    }
  }
})();
