/**
 * Kosmohealth Enhancements
 * This script loads all the enhancements for the Kosmohealth platform
 */

(function() {
  // Configuration
  const config = {
    debug: true,
    autoLoad: true,
    scripts: [
      '/js/load-face-api-models.js',
      '/js/kosmohealth-loader.js'
    ],
    styles: [
      '/css/kosmohealth-meeting.css'
    ]
  };
  
  // Log with prefix
  function log(message) {
    if (config.debug) {
      console.log('[Kosmohealth Enhancements]', message);
    }
  }
  
  // Load a script
  function loadScript(url) {
    return new Promise((resolve, reject) => {
      log(`Loading script: ${url}`);
      const script = document.createElement('script');
      script.src = url;
      script.onload = () => {
        log(`Script loaded: ${url}`);
        resolve();
      };
      script.onerror = (error) => {
        console.error(`[Kosmohealth Enhancements] Error loading script ${url}:`, error);
        reject(error);
      };
      document.head.appendChild(script);
    });
  }
  
  // Load a stylesheet
  function loadStylesheet(url) {
    return new Promise((resolve, reject) => {
      log(`Loading stylesheet: ${url}`);
      const link = document.createElement('link');
      link.rel = 'stylesheet';
      link.type = 'text/css';
      link.href = url;
      link.onload = () => {
        log(`Stylesheet loaded: ${url}`);
        resolve();
      };
      link.onerror = (error) => {
        console.error(`[Kosmohealth Enhancements] Error loading stylesheet ${url}:`, error);
        reject(error);
      };
      document.head.appendChild(link);
    });
  }
  
  // Load all resources
  async function loadResources() {
    try {
      // Load stylesheets
      const stylePromises = config.styles.map(url => loadStylesheet(url));
      await Promise.all(stylePromises);
      
      // Load scripts sequentially to ensure proper initialization order
      for (const url of config.scripts) {
        await loadScript(url);
      }
      
      log('All resources loaded successfully');
      return true;
    } catch (error) {
      console.error('[Kosmohealth Enhancements] Error loading resources:', error);
      return false;
    }
  }
  
  // Initialize
  async function init() {
    log('Initializing Kosmohealth enhancements...');
    
    // Add version info to console
    console.log('%c Kosmohealth Enhancements v1.0.0 ', 'background: #4CAF50; color: white; padding: 5px; border-radius: 3px;');
    
    // Load resources
    if (config.autoLoad) {
      await loadResources();
    }
    
    // Expose API
    window.kosmohealth = window.kosmohealth || {};
    window.kosmohealth.enhancements = {
      loadResources,
      version: '1.0.0'
    };
    
    log('Initialization complete');
  }
  
  // Initialize when the page is loaded
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
