/**
 * Meeting Fixes
 * This script applies fixes to the meeting interface
 */

(function() {
  // Load the CSS fixes
  loadCSS('/css/meeting-fixes.css');
  
  // Function to load CSS
  function loadCSS(url) {
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.type = 'text/css';
    link.href = url;
    document.head.appendChild(link);
  }
  
  // Wait for DOM to be ready
  document.addEventListener('DOMContentLoaded', function() {
    // Apply fixes to meeting footer
    fixMeetingFooter();
    
    // Improve face tracking
    improveFaceTracking();
  });
  
  // Fix meeting footer
  function fixMeetingFooter() {
    const footer = document.querySelector('.meeting-footer');
    if (footer) {
      // Ensure the footer has the highest z-index
      footer.style.zIndex = '1050';
      
      // Add a class to indicate the fix has been applied
      footer.classList.add('fixed-z-index');
    }
  }
  
  // Improve face tracking
  function improveFaceTracking() {
    // This function will be called when the face processing service is initialized
    // It will be used to monitor and improve face tracking
    
    // Check if we're in a meeting page
    if (window.location.href.includes('/app/live/meetings/')) {
      // Set an interval to check and fix face tracking issues
      setInterval(function() {
        // Find the face processing canvas
        const canvas = document.querySelector('.face-processing-canvas');
        if (canvas) {
          // Ensure the canvas is properly positioned
          canvas.style.position = 'absolute';
          canvas.style.top = '0';
          canvas.style.left = '0';
          canvas.style.width = '100%';
          canvas.style.height = '100%';
          canvas.style.zIndex = '20';
        }
        
        // Find the face processing controls
        const controls = document.querySelector('.face-processing-controls');
        if (controls) {
          // Ensure the controls are above the footer
          controls.style.zIndex = '1040';
        }
      }, 1000); // Check every second
    }
  }
})();
