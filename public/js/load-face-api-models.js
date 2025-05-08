/**
 * Load Face-API.js Models
 * This script loads the required models for face detection and emotion recognition
 */

(function() {
  // Configuration
  const config = {
    modelsPath: '/js/face-api-models',
    debug: true,
    autoLoad: true
  };
  
  // Log with prefix
  function log(message) {
    if (config.debug) {
      console.log('[Face-API Loader]', message);
    }
  }
  
  // Load face-api.js script
  async function loadFaceApiScript() {
    return new Promise((resolve, reject) => {
      if (window.faceapi) {
        log('face-api.js already loaded');
        resolve(window.faceapi);
        return;
      }
      
      log('Loading face-api.js script...');
      const script = document.createElement('script');
      script.src = '/js/face-api.min.js';
      script.onload = () => {
        log('face-api.js loaded successfully');
        resolve(window.faceapi);
      };
      script.onerror = (error) => {
        console.error('[Face-API Loader] Error loading face-api.js:', error);
        reject(error);
      };
      document.head.appendChild(script);
    });
  }
  
  // Load models
  async function loadModels() {
    try {
      log('Loading face-api.js models...');
      
      // Load models
      await Promise.all([
        faceapi.nets.tinyFaceDetector.loadFromUri(config.modelsPath),
        faceapi.nets.faceLandmark68Net.loadFromUri(config.modelsPath),
        faceapi.nets.faceRecognitionNet.loadFromUri(config.modelsPath),
        faceapi.nets.faceExpressionNet.loadFromUri(config.modelsPath)
      ]);
      
      log('All models loaded successfully');
      return true;
    } catch (error) {
      console.error('[Face-API Loader] Error loading models:', error);
      return false;
    }
  }
  
  // Initialize
  async function init() {
    try {
      // Load face-api.js
      await loadFaceApiScript();
      
      // Load models
      if (config.autoLoad) {
        await loadModels();
      }
      
      // Expose API
      window.faceApiLoader = {
        loadModels,
        isReady: () => {
          return faceapi.nets.tinyFaceDetector.isLoaded && 
                 faceapi.nets.faceLandmark68Net.isLoaded && 
                 faceapi.nets.faceRecognitionNet.isLoaded && 
                 faceapi.nets.faceExpressionNet.isLoaded;
        }
      };
      
      log('Initialization complete');
    } catch (error) {
      console.error('[Face-API Loader] Initialization failed:', error);
    }
  }
  
  // Initialize when the page is loaded
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
