// Fix for low internet connections
(function() {
  // Network quality monitoring
  let networkQuality = 'good'; // 'good', 'medium', 'poor'
  
  // Monitor network quality
  function monitorNetworkQuality() {
    // Use the Network Information API if available
    if (navigator.connection) {
      const connection = navigator.connection;
      
      // Check connection type
      if (connection.type === 'cellular' && (connection.effectiveType === '2g' || connection.effectiveType === 'slow-2g')) {
        networkQuality = 'poor';
      } else if (connection.effectiveType === '3g') {
        networkQuality = 'medium';
      } else {
        networkQuality = 'good';
      }
      
      // Listen for changes
      connection.addEventListener('change', monitorNetworkQuality);
    } else {
      // Fallback: measure response time
      measureResponseTime();
    }
  }
  
  // Measure response time as a fallback
  function measureResponseTime() {
    const start = performance.now();
    
    // Make a small request to measure response time
    fetch('/favicon.ico', { cache: 'no-store' })
      .then(() => {
        const responseTime = performance.now() - start;
        
        // Classify network quality based on response time
        if (responseTime > 1000) {
          networkQuality = 'poor';
        } else if (responseTime > 300) {
          networkQuality = 'medium';
        } else {
          networkQuality = 'good';
        }
      })
      .catch(() => {
        // If request fails, assume poor connection
        networkQuality = 'poor';
      });
  }
  
  // Optimize processing based on network quality
  function optimizeProcessing() {
    // Adjust face processing FPS
    if (window.FaceProcessingService) {
      const fps = networkQuality === 'poor' ? 2 : 
                 networkQuality === 'medium' ? 3 : 5;
      
      // Update FPS if service is initialized
      if (window.FaceProcessingService.instance) {
        window.FaceProcessingService.instance.setProcessingFps(fps);
      }
    }
    
    // Adjust gesture recognition FPS
    if (window.UnifiedCommunicationService) {
      const fps = networkQuality === 'poor' ? 2 : 
                 networkQuality === 'medium' ? 3 : 5;
      
      // Update FPS if service is initialized
      if (window.UnifiedCommunicationService.instance) {
        window.UnifiedCommunicationService.instance.setProcessingFps(fps);
      }
    }
  }
  
  // Initialize
  monitorNetworkQuality();
  
  // Optimize periodically
  setInterval(() => {
    // Refresh network quality measurement
    if (!navigator.connection) {
      measureResponseTime();
    }
    
    // Apply optimizations
    optimizeProcessing();
  }, 10000); // Check every 10 seconds
})();
