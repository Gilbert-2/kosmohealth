/**
 * Kosmohealth Enhancement Injector
 * This script injects the enhancements into the Kosmohealth application
 */

(function() {
  // Load the enhancements script
  const script = document.createElement('script');
  script.src = '/js/kosmohealth-enhancements.js';
  document.head.appendChild(script);
  
  // Add a notification to let the user know enhancements are loaded
  script.onload = function() {
    // Create notification element
    const notification = document.createElement('div');
    notification.style.cssText = `
      position: fixed;
      top: 20px;
      left: 50%;
      transform: translateX(-50%);
      background-color: rgba(76, 175, 80, 0.9);
      color: white;
      padding: 10px 20px;
      border-radius: 4px;
      font-family: Arial, sans-serif;
      font-size: 14px;
      z-index: 10000;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
      animation: fadeInOut 5s forwards;
    `;
    notification.textContent = 'Kosmohealth Enhancements Loaded';
    
    // Add animation
    const style = document.createElement('style');
    style.textContent = `
      @keyframes fadeInOut {
        0% { opacity: 0; transform: translate(-50%, -20px); }
        10% { opacity: 1; transform: translate(-50%, 0); }
        80% { opacity: 1; transform: translate(-50%, 0); }
        100% { opacity: 0; transform: translate(-50%, -20px); }
      }
    `;
    document.head.appendChild(style);
    
    // Add to document
    document.body.appendChild(notification);
    
    // Remove after animation completes
    setTimeout(() => {
      notification.remove();
    }, 5000);
  };
})();
