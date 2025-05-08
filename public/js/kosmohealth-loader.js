/**
 * Kosmohealth Enhancement Loader
 * This script loads all the enhancements for the Kosmohealth platform
 */

(function() {
  // Configuration
  const config = {
    debug: true,
    loadFaceProcessing: true,
    loadMeetingUI: true,
    retryInterval: 1000,
    maxRetries: 10
  };
  
  // Load a script
  function loadScript(url) {
    return new Promise((resolve, reject) => {
      const script = document.createElement('script');
      script.src = url;
      script.onload = resolve;
      script.onerror = reject;
      document.head.appendChild(script);
    });
  }
  
  // Load a stylesheet
  function loadStylesheet(url) {
    return new Promise((resolve, reject) => {
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.type = 'text/css';
      link.href = url;
      link.onload = resolve;
      link.onerror = reject;
      document.head.appendChild(link);
    });
  }
  
  // Log with prefix
  function log(message) {
    if (config.debug) {
      console.log('[Kosmohealth Loader]', message);
    }
  }
  
  // Check if we're in a meeting
  function isInMeeting() {
    // Check for video elements or meeting container
    return document.querySelector('video') !== null || 
           document.querySelector('.meeting-container, .video-container, .main-content') !== null;
  }
  
  // Load face processing enhancement
  async function loadFaceProcessing() {
    try {
      log('Loading face-api.js...');
      await loadScript('/js/face-api.min.js');
      
      log('Loading face processing plugin...');
      await loadScript('/js/face-processing-plugin.js');
      
      log('Face processing enhancements loaded successfully');
      return true;
    } catch (error) {
      console.error('[Kosmohealth Loader] Error loading face processing:', error);
      return false;
    }
  }
  
  // Load meeting UI enhancements
  async function loadMeetingUI() {
    try {
      log('Loading meeting UI stylesheet...');
      await loadStylesheet('/css/kosmohealth-meeting.css');
      
      log('Loading meeting UI script...');
      await loadScript('/js/kosmohealth-meeting-ui.js');
      
      log('Meeting UI enhancements loaded successfully');
      return true;
    } catch (error) {
      console.error('[Kosmohealth Loader] Error loading meeting UI:', error);
      return false;
    }
  }
  
  // Initialize the loader
  async function init() {
    log('Initializing Kosmohealth enhancements...');
    
    // Wait for meeting to be detected
    let retries = 0;
    
    async function checkAndLoad() {
      if (isInMeeting()) {
        log('Meeting detected, loading enhancements...');
        
        // Load face processing if enabled
        if (config.loadFaceProcessing) {
          await loadFaceProcessing();
        }
        
        // Load meeting UI if enabled
        if (config.loadMeetingUI) {
          await loadMeetingUI();
        }
        
        log('All enhancements loaded successfully');
      } else {
        retries++;
        
        if (retries < config.maxRetries) {
          log(`Meeting not detected, retrying in ${config.retryInterval}ms (${retries}/${config.maxRetries})...`);
          setTimeout(checkAndLoad, config.retryInterval);
        } else {
          log('Max retries reached, giving up');
        }
      }
    }
    
    // Start checking
    checkAndLoad();
  }
  
  // Initialize when the page is loaded
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
