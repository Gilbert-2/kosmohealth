/**
 * Modern Live Feed Interface for KosmoHealth
 * This script enhances the meeting interface with modern design and user-friendly features
 */

(function() {
  // Configuration
  const config = {
    enableGlassmorphism: true,
    enableAnimations: true,
    enableDarkMode: true,
    enableNetworkStatus: true,
    enableParticipantInfo: true
  };
  
  // Initialize
  function initialize() {
    // Load CSS
    loadCSS('/css/modern-livefeed.css');
    
    // Apply modern styles to meeting container
    modernizeMeetingContainer();
    
    // Add network status monitoring
    if (config.enableNetworkStatus) {
      monitorNetworkStatus();
    }
    
    // Add participant info
    if (config.enableParticipantInfo) {
      addParticipantInfo();
    }
    
    // Add modern controls
    addModernControls();
    
    // Apply glassmorphism if enabled
    if (config.enableGlassmorphism) {
      applyGlassmorphism();
    }
    
    // Apply dark mode if enabled
    if (config.enableDarkMode) {
      applyDarkMode();
    }
    
    console.log('Modern live feed interface initialized');
  }
  
  // Load CSS file
  function loadCSS(href) {
    // Check if already loaded
    const existingLink = document.querySelector(`link[href="${href}"]`);
    if (existingLink) return;
    
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = href;
    document.head.appendChild(link);
  }
  
  // Modernize meeting container
  function modernizeMeetingContainer() {
    // Find meeting container
    const meetingContainer = document.querySelector('.meeting-container') || 
                            document.querySelector('.rtc-container') ||
                            document.querySelector('.app-meeting');
    
    if (!meetingContainer) return;
    
    // Skip if already modernized
    if (meetingContainer.classList.contains('modernized')) return;
    
    // Add modern class
    meetingContainer.classList.add('modernized');
    
    // Apply modern layout
    meetingContainer.classList.add('modern-meeting-layout');
    
    // Find all video containers
    const videoContainers = meetingContainer.querySelectorAll('.video-container, .meeting-video-container, .rtc-video-container');
    
    videoContainers.forEach(container => {
      // Skip if already modernized
      if (container.classList.contains('modern-video-container')) return;
      
      // Add modern class
      container.classList.add('modern-video-container');
      
      // Find video element
      const videoElement = container.querySelector('video');
      if (videoElement) {
        videoElement.classList.add('modern-video');
      }
    });
  }
  
  // Monitor network status
  function monitorNetworkStatus() {
    // Use the Network Information API if available
    if (navigator.connection) {
      updateNetworkStatus(navigator.connection);
      
      // Listen for changes
      navigator.connection.addEventListener('change', () => {
        updateNetworkStatus(navigator.connection);
      });
    } else {
      // Fallback: measure response time
      measureResponseTime();
      
      // Check periodically
      setInterval(measureResponseTime, 10000);
    }
  }
  
  // Update network status indicators
  function updateNetworkStatus(connection) {
    let status = 'good';
    
    // Determine status based on connection type and speed
    if (connection.type === 'cellular' && (connection.effectiveType === '2g' || connection.effectiveType === 'slow-2g')) {
      status = 'poor';
    } else if (connection.effectiveType === '3g') {
      status = 'medium';
    }
    
    // Update all status indicators
    updateNetworkStatusIndicators(status);
  }
  
  // Measure response time as a fallback
  function measureResponseTime() {
    const start = performance.now();
    
    // Make a small request to measure response time
    fetch('/favicon.ico', { cache: 'no-store' })
      .then(() => {
        const responseTime = performance.now() - start;
        
        // Classify network quality based on response time
        let status = 'good';
        if (responseTime > 1000) {
          status = 'poor';
        } else if (responseTime > 300) {
          status = 'medium';
        }
        
        // Update all status indicators
        updateNetworkStatusIndicators(status);
      })
      .catch(() => {
        // If request fails, assume poor connection
        updateNetworkStatusIndicators('poor');
      });
  }
  
  // Update all network status indicators
  function updateNetworkStatusIndicators(status) {
    const indicators = document.querySelectorAll('.network-status');
    
    indicators.forEach(indicator => {
      // Update icon
      const icon = indicator.querySelector('.network-status-icon');
      if (icon) {
        icon.className = 'network-status-icon';
        icon.classList.add(`network-status-${status}`);
      }
      
      // Update text
      const text = indicator.querySelector('span');
      if (text) {
        text.textContent = status.charAt(0).toUpperCase() + status.slice(1);
      }
    });
  }
  
  // Add participant info
  function addParticipantInfo() {
    // Find all video containers
    const videoContainers = document.querySelectorAll('.video-container, .meeting-video-container, .rtc-video-container');
    
    videoContainers.forEach(container => {
      // Skip if already has participant info
      if (container.querySelector('.participant-name')) return;
      
      // Create participant name element
      const participantName = document.createElement('div');
      participantName.className = 'participant-name';
      
      // Try to find participant name
      const nameElement = container.querySelector('.participant-info .name') || 
                         container.querySelector('.user-name') ||
                         container.querySelector('.participant-name');
      
      if (nameElement) {
        participantName.textContent = nameElement.textContent;
      } else {
        // Default name based on position
        const isLocal = container.classList.contains('local-video-container') || 
                       container.classList.contains('local-video');
        participantName.textContent = isLocal ? 'You' : 'Participant';
      }
      
      // Add to container
      container.appendChild(participantName);
    });
  }
  
  // Add modern controls
  function addModernControls() {
    // Find meeting footer
    const footer = document.querySelector('.meeting-footer') || 
                  document.querySelector('.rtc-controls') ||
                  document.querySelector('.meeting-controls');
    
    if (!footer) return;
    
    // Skip if already modernized
    if (footer.classList.contains('modernized')) return;
    
    // Add modern class
    footer.classList.add('modernized');
    
    // Apply glassmorphism to footer
    if (config.enableGlassmorphism) {
      footer.classList.add('glassmorphism-controls');
    }
    
    // Find all control buttons
    const buttons = footer.querySelectorAll('button');
    
    buttons.forEach(button => {
      // Add modern styling
      button.classList.add('video-control-btn');
    });
  }
  
  // Apply glassmorphism
  function applyGlassmorphism() {
    // Find elements to apply glassmorphism
    const elements = document.querySelectorAll('.meeting-footer, .rtc-controls, .meeting-controls, .video-controls');
    
    elements.forEach(element => {
      element.classList.add('glassmorphism-controls');
    });
  }
  
  // Apply dark mode
  function applyDarkMode() {
    // Add dark mode class to body
    document.body.classList.add('dark-mode');
    
    // Add dark background to meeting container
    const meetingContainer = document.querySelector('.meeting-container') || 
                            document.querySelector('.rtc-container') ||
                            document.querySelector('.app-meeting');
    
    if (meetingContainer) {
      meetingContainer.style.backgroundColor = '#121212';
    }
  }
  
  // Initialize on load and periodically check for changes
  window.addEventListener('load', initialize);
  setInterval(initialize, 5000);
})();
