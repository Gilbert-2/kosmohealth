/**
 * Kosmohealth Meeting UI Enhancements
 * This script improves the meeting interface with modern UI elements and interactions
 */

(function() {
  // Configuration
  const config = {
    enableDraggableVideos: true,
    enableAnimations: true,
    enableTooltips: true,
    enableDarkMode: false,
    enableAccessibility: true
  };

  // State
  let state = {
    isChatOpen: false,
    isFullScreen: false,
    activeLayout: 'grid', // grid, spotlight, sidebar
    participants: [],
    localVideoElement: null,
    remoteVideoElements: []
  };

  // Initialize the UI enhancements
  function init() {
    console.log('Initializing Kosmohealth Meeting UI Enhancements...');
    
    // Load the CSS
    loadCSS('/css/kosmohealth-meeting.css');
    
    // Find video elements
    findVideoElements();
    
    // Apply initial styling
    applyStyles();
    
    // Setup event listeners
    setupEventListeners();
    
    // Create floating action button for face processing
    createFaceProcessingFAB();
    
    // Add tooltips
    if (config.enableTooltips) {
      addTooltips();
    }
    
    // Make videos draggable
    if (config.enableDraggableVideos) {
      makeVideosDraggable();
    }
    
    console.log('Kosmohealth Meeting UI Enhancements initialized');
  }
  
  // Load CSS file
  function loadCSS(url) {
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.type = 'text/css';
    link.href = url;
    document.head.appendChild(link);
  }
  
  // Find video elements
  function findVideoElements() {
    // Find all video elements
    const videos = document.querySelectorAll('video');
    
    // Categorize videos as local or remote
    videos.forEach(video => {
      const container = video.parentElement;
      
      // Create wrapper if needed
      if (!container.classList.contains('video-container')) {
        const wrapper = document.createElement('div');
        wrapper.className = 'video-container';
        video.parentNode.insertBefore(wrapper, video);
        wrapper.appendChild(video);
      }
      
      // Add to state
      state.remoteVideoElements.push(video);
    });
    
    // Try to identify local video
    const localVideoSelectors = [
      '#localVideo',
      '.local-video',
      'video[id*="local"]',
      'video.local',
      'video[id*="self"]',
      'video.self'
    ];
    
    for (const selector of localVideoSelectors) {
      const localVideo = document.querySelector(selector);
      if (localVideo) {
        state.localVideoElement = localVideo;
        // Remove from remote videos array
        state.remoteVideoElements = state.remoteVideoElements.filter(v => v !== localVideo);
        break;
      }
    }
  }
  
  // Apply styles to meeting elements
  function applyStyles() {
    // Find meeting container
    const meetingContainer = document.querySelector('.meeting-container, .video-container, .main-content');
    if (meetingContainer) {
      meetingContainer.classList.add('meeting-container');
    }
    
    // Style video containers
    document.querySelectorAll('.video-container').forEach(container => {
      // Add user info if not present
      if (!container.querySelector('.user-info')) {
        const userInfo = document.createElement('div');
        userInfo.className = 'user-info';
        
        const userName = document.createElement('div');
        userName.className = 'user-name';
        userName.textContent = container.contains(state.localVideoElement) ? 'You' : 'Participant';
        
        userInfo.appendChild(userName);
        container.appendChild(userInfo);
      }
      
      // Add fade-in animation
      if (config.enableAnimations) {
        container.classList.add('fade-in');
      }
    });
    
    // Style controls
    const controlsBar = document.querySelector('.controls, .control-bar, .meeting-controls');
    if (controlsBar) {
      controlsBar.classList.add('controls-bar');
      
      // Style buttons
      controlsBar.querySelectorAll('button').forEach(button => {
        button.classList.add('control-button');
        
        // Add danger class to end call button
        if (button.textContent.toLowerCase().includes('end') || 
            button.textContent.toLowerCase().includes('hang up')) {
          button.classList.add('danger');
        }
      });
    }
  }
  
  // Setup event listeners
  function setupEventListeners() {
    // Listen for new video elements
    const observer = new MutationObserver(mutations => {
      let needsUpdate = false;
      
      mutations.forEach(mutation => {
        if (mutation.addedNodes.length) {
          mutation.addedNodes.forEach(node => {
            if (node.nodeName === 'VIDEO' || node.querySelector('video')) {
              needsUpdate = true;
            }
          });
        }
      });
      
      if (needsUpdate) {
        findVideoElements();
        applyStyles();
        
        if (config.enableDraggableVideos) {
          makeVideosDraggable();
        }
      }
    });
    
    observer.observe(document.body, { childList: true, subtree: true });
    
    // Listen for window resize
    window.addEventListener('resize', () => {
      // Adjust layout if needed
      adjustLayout();
    });
  }
  
  // Create floating action button for face processing
  function createFaceProcessingFAB() {
    const fab = document.createElement('div');
    fab.className = 'face-processing-fab tooltip';
    fab.setAttribute('data-tooltip', 'Face Processing');
    fab.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline></svg>';
    
    fab.addEventListener('click', () => {
      // Toggle face processing panel
      const faceProcessingContainer = document.getElementById('face-processing-container');
      if (faceProcessingContainer) {
        faceProcessingContainer.style.display = faceProcessingContainer.style.display === 'none' ? 'block' : 'none';
      }
    });
    
    document.body.appendChild(fab);
  }
  
  // Add tooltips to controls
  function addTooltips() {
    const controlButtons = document.querySelectorAll('.control-button');
    
    controlButtons.forEach(button => {
      button.classList.add('tooltip');
      
      // Try to determine tooltip text from button content
      let tooltipText = '';
      
      if (button.title) {
        tooltipText = button.title;
      } else if (button.textContent.trim()) {
        tooltipText = button.textContent.trim();
      } else {
        // Try to determine from icon
        const iconClasses = Array.from(button.classList);
        
        if (iconClasses.some(c => c.includes('mic'))) {
          tooltipText = 'Microphone';
        } else if (iconClasses.some(c => c.includes('cam'))) {
          tooltipText = 'Camera';
        } else if (iconClasses.some(c => c.includes('screen'))) {
          tooltipText = 'Share Screen';
        } else if (iconClasses.some(c => c.includes('chat'))) {
          tooltipText = 'Chat';
        } else if (iconClasses.some(c => c.includes('end'))) {
          tooltipText = 'End Call';
        }
      }
      
      if (tooltipText) {
        button.setAttribute('data-tooltip', tooltipText);
      }
    });
  }
  
  // Make videos draggable
  function makeVideosDraggable() {
    document.querySelectorAll('.video-container').forEach(container => {
      // Skip if already draggable
      if (container.dataset.draggable === 'true') {
        return;
      }
      
      container.dataset.draggable = 'true';
      
      let isDragging = false;
      let startX, startY;
      let startLeft, startTop;
      
      container.addEventListener('mousedown', (e) => {
        // Only allow dragging from the user info bar
        if (!e.target.closest('.user-info')) {
          return;
        }
        
        isDragging = true;
        
        // Get initial positions
        startX = e.clientX;
        startY = e.clientY;
        
        // Get container's current position
        const rect = container.getBoundingClientRect();
        startLeft = rect.left;
        startTop = rect.top;
        
        // Set position to absolute if not already
        const computedStyle = window.getComputedStyle(container);
        if (computedStyle.position !== 'absolute') {
          container.style.width = rect.width + 'px';
          container.style.height = rect.height + 'px';
          container.style.position = 'absolute';
          container.style.left = rect.left + 'px';
          container.style.top = rect.top + 'px';
          container.style.zIndex = '1000';
        }
        
        e.preventDefault();
      });
      
      document.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        
        // Calculate new position
        const deltaX = e.clientX - startX;
        const deltaY = e.clientY - startY;
        
        container.style.left = (startLeft + deltaX) + 'px';
        container.style.top = (startTop + deltaY) + 'px';
      });
      
      document.addEventListener('mouseup', () => {
        isDragging = false;
      });
    });
  }
  
  // Adjust layout based on window size
  function adjustLayout() {
    const width = window.innerWidth;
    
    if (width < 768) {
      // Mobile layout
      state.activeLayout = 'spotlight';
    } else {
      // Desktop layout
      state.activeLayout = 'grid';
    }
    
    // Apply layout
    applyLayout();
  }
  
  // Apply the current layout
  function applyLayout() {
    const videoContainers = document.querySelectorAll('.video-container');
    
    if (state.activeLayout === 'grid') {
      // Grid layout
      videoContainers.forEach(container => {
        container.style.position = '';
        container.style.width = '';
        container.style.height = '';
        container.style.left = '';
        container.style.top = '';
        container.style.zIndex = '';
      });
    } else if (state.activeLayout === 'spotlight') {
      // Spotlight layout - local video small, remote video large
      if (state.localVideoElement) {
        const localContainer = state.localVideoElement.closest('.video-container');
        if (localContainer) {
          localContainer.style.position = 'absolute';
          localContainer.style.width = '150px';
          localContainer.style.height = '100px';
          localContainer.style.right = '20px';
          localContainer.style.bottom = '20px';
          localContainer.style.zIndex = '1000';
        }
      }
    }
  }
  
  // Initialize when the page is loaded
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
