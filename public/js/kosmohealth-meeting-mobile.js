/**
 * Kosmohealth Meeting Mobile Enhancements
 * This script adds mobile-specific features and UI improvements to the meeting interface
 */

(function() {
  // Configuration
  const config = {
    enableSwipeGestures: true,
    enableTapToToggleControls: true,
    enablePinchToZoom: true,
    enableDoubleTapToSwitchCamera: true,
    enableShakeToReport: false,
    enableDarkMode: true,
    enableAccessibility: true
  };

  // State
  let state = {
    isSidebarOpen: false,
    isControlsVisible: true,
    isFullScreen: false,
    activeTab: 'participants', // participants, chat, settings
    currentLayout: 'grid', // grid, spotlight, sidebar
    controlsHideTimeout: null,
    touchStartX: 0,
    touchStartY: 0,
    lastTapTime: 0,
    darkMode: false,
    participants: [],
    localVideoElement: null,
    remoteVideoElements: []
  };

  // Initialize the mobile enhancements
  function init() {
    console.log('Initializing Kosmohealth Meeting Mobile Enhancements...');
    
    // Load the enhanced CSS
    loadCSS('/css/kosmohealth-meeting-enhanced.css');
    
    // Find video elements
    findVideoElements();
    
    // Create mobile UI
    createMobileUI();
    
    // Setup event listeners
    setupEventListeners();
    
    // Setup swipe gestures
    if (config.enableSwipeGestures) {
      setupSwipeGestures();
    }
    
    // Auto-hide controls after inactivity
    setupAutoHideControls();
    
    // Check for dark mode preference
    checkDarkModePreference();
    
    // Create floating action button for face processing
    createFaceProcessingFAB();
    
    console.log('Kosmohealth Meeting Mobile Enhancements initialized');
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
    // Find local video
    state.localVideoElement = document.getElementById('localVideo');
    
    // Find remote videos
    const remoteVideos = document.querySelectorAll('.remote-video');
    state.remoteVideoElements = Array.from(remoteVideos);
    
    console.log(`Found ${state.remoteVideoElements.length + (state.localVideoElement ? 1 : 0)} video elements`);
  }
  
  // Create mobile UI
  function createMobileUI() {
    // Create mobile container
    const mobileContainer = document.createElement('div');
    mobileContainer.className = 'meeting-container';
    
    // Create header
    const header = document.createElement('div');
    header.className = 'meeting-header';
    header.innerHTML = `
      <h1 class="meeting-title">Meeting</h1>
      <div class="meeting-actions">
        <button class="toggle-sidebar-button">
          <i class="fas fa-users"></i>
        </button>
        <button class="toggle-settings-button">
          <i class="fas fa-ellipsis-v"></i>
        </button>
      </div>
    `;
    
    // Create content area
    const content = document.createElement('div');
    content.className = 'meeting-content';
    
    // Create video container for mobile
    const videoContainer = document.createElement('div');
    videoContainer.className = 'mobile-video-container';
    
    // Create self view
    const selfView = document.createElement('div');
    selfView.className = 'self-view';
    
    // Create mobile navigation
    const mobileNav = document.createElement('div');
    mobileNav.className = 'mobile-nav';
    mobileNav.innerHTML = `
      <div class="mobile-nav-item" data-action="toggle-audio">
        <i class="fas fa-microphone"></i>
        <span>Mic</span>
      </div>
      <div class="mobile-nav-item" data-action="toggle-video">
        <i class="fas fa-video"></i>
        <span>Camera</span>
      </div>
      <div class="mobile-nav-item" data-action="toggle-chat">
        <i class="fas fa-comment-alt"></i>
        <span>Chat</span>
      </div>
      <div class="mobile-nav-item" data-action="toggle-participants">
        <i class="fas fa-users"></i>
        <span>People</span>
      </div>
      <div class="mobile-nav-item danger" data-action="end-call">
        <i class="fas fa-phone-slash"></i>
        <span>End</span>
      </div>
    `;
    
    // Create sidebar
    const sidebar = document.createElement('div');
    sidebar.className = 'meeting-sidebar';
    sidebar.innerHTML = `
      <div class="sidebar-header">
        <h2 class="sidebar-title">Participants</h2>
        <button class="sidebar-close">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="sidebar-tabs">
        <div class="sidebar-tab active" data-tab="participants">Participants</div>
        <div class="sidebar-tab" data-tab="chat">Chat</div>
        <div class="sidebar-tab" data-tab="settings">Settings</div>
      </div>
      <div class="sidebar-content">
        <div class="tab-content active" data-tab-content="participants">
          <ul class="participants-list"></ul>
        </div>
        <div class="tab-content" data-tab-content="chat">
          <div class="chat-messages"></div>
          <div class="chat-input">
            <input type="text" placeholder="Type a message...">
            <button><i class="fas fa-paper-plane"></i></button>
          </div>
        </div>
        <div class="tab-content" data-tab-content="settings">
          <div class="settings-list">
            <div class="setting-item">
              <span>Dark Mode</span>
              <label class="switch">
                <input type="checkbox" id="darkModeToggle">
                <span class="slider round"></span>
              </label>
            </div>
            <div class="setting-item">
              <span>Switch Camera</span>
              <button class="switch-camera-btn">
                <i class="fas fa-sync-alt"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    `;
    
    // Create swipe area
    const swipeArea = document.createElement('div');
    swipeArea.className = 'swipe-area';
    
    // Create floating action button
    const fab = document.createElement('div');
    fab.className = 'floating-action-button';
    fab.innerHTML = '<i class="fas fa-plus"></i>';
    
    // Append elements to the DOM
    content.appendChild(videoContainer);
    content.appendChild(selfView);
    content.appendChild(swipeArea);
    
    mobileContainer.appendChild(header);
    mobileContainer.appendChild(content);
    mobileContainer.appendChild(sidebar);
    mobileContainer.appendChild(mobileNav);
    mobileContainer.appendChild(fab);
    
    // Replace or append to the existing container
    const existingContainer = document.querySelector('.meeting-container, .rtc-container, .video-container');
    if (existingContainer) {
      existingContainer.parentNode.replaceChild(mobileContainer, existingContainer);
    } else {
      document.body.appendChild(mobileContainer);
    }
    
    // Move existing video elements to the new container
    if (state.localVideoElement) {
      selfView.appendChild(state.localVideoElement);
    }
    
    if (state.remoteVideoElements.length > 0) {
      state.remoteVideoElements.forEach(video => {
        videoContainer.appendChild(video);
      });
    }
  }
  
  // Setup event listeners
  function setupEventListeners() {
    // Toggle sidebar
    const toggleSidebarButton = document.querySelector('.toggle-sidebar-button');
    if (toggleSidebarButton) {
      toggleSidebarButton.addEventListener('click', toggleSidebar);
    }
    
    // Close sidebar
    const sidebarCloseButton = document.querySelector('.sidebar-close');
    if (sidebarCloseButton) {
      sidebarCloseButton.addEventListener('click', closeSidebar);
    }
    
    // Tab switching
    const sidebarTabs = document.querySelectorAll('.sidebar-tab');
    sidebarTabs.forEach(tab => {
      tab.addEventListener('click', () => {
        const tabName = tab.getAttribute('data-tab');
        switchTab(tabName);
      });
    });
    
    // Mobile navigation actions
    const navItems = document.querySelectorAll('.mobile-nav-item');
    navItems.forEach(item => {
      item.addEventListener('click', () => {
        const action = item.getAttribute('data-action');
        handleNavAction(action);
      });
    });
    
    // Dark mode toggle
    const darkModeToggle = document.getElementById('darkModeToggle');
    if (darkModeToggle) {
      darkModeToggle.addEventListener('change', toggleDarkMode);
    }
    
    // Switch camera button
    const switchCameraBtn = document.querySelector('.switch-camera-btn');
    if (switchCameraBtn) {
      switchCameraBtn.addEventListener('click', switchCamera);
    }
    
    // Floating action button
    const fab = document.querySelector('.floating-action-button');
    if (fab) {
      fab.addEventListener('click', toggleFabMenu);
    }
    
    // Document click to hide controls
    document.addEventListener('click', () => {
      resetControlsHideTimeout();
    });
    
    // Document touch to hide controls
    document.addEventListener('touchstart', () => {
      resetControlsHideTimeout();
    });
  }
  
  // Setup swipe gestures
  function setupSwipeGestures() {
    const swipeArea = document.querySelector('.swipe-area');
    if (!swipeArea) return;
    
    swipeArea.addEventListener('touchstart', handleTouchStart, false);
    swipeArea.addEventListener('touchmove', handleTouchMove, false);
    swipeArea.addEventListener('touchend', handleTouchEnd, false);
  }
  
  // Handle touch start
  function handleTouchStart(e) {
    const touch = e.touches[0];
    state.touchStartX = touch.clientX;
    state.touchStartY = touch.clientY;
    
    // Check for double tap
    const currentTime = new Date().getTime();
    const tapLength = currentTime - state.lastTapTime;
    
    if (tapLength < 300 && tapLength > 0 && config.enableDoubleTapToSwitchCamera) {
      switchCamera();
      e.preventDefault();
    }
    
    state.lastTapTime = currentTime;
  }
  
  // Handle touch move
  function handleTouchMove(e) {
    if (!state.touchStartX || !state.touchStartY) return;
    
    const touch = e.touches[0];
    const diffX = state.touchStartX - touch.clientX;
    const diffY = state.touchStartY - touch.clientY;
    
    // Horizontal swipe detection
    if (Math.abs(diffX) > Math.abs(diffY)) {
      if (diffX > 50) {
        // Swipe left - open sidebar
        openSidebar();
      } else if (diffX < -50) {
        // Swipe right - close sidebar
        closeSidebar();
      }
    } else {
      // Vertical swipe detection
      if (diffY > 50) {
        // Swipe up - show controls
        showControls();
      } else if (diffY < -50) {
        // Swipe down - hide controls
        hideControls();
      }
    }
    
    state.touchStartX = 0;
    state.touchStartY = 0;
  }
  
  // Handle touch end
  function handleTouchEnd(e) {
    state.touchStartX = 0;
    state.touchStartY = 0;
  }
  
  // Toggle sidebar
  function toggleSidebar() {
    const sidebar = document.querySelector('.meeting-sidebar');
    if (!sidebar) return;
    
    if (state.isSidebarOpen) {
      closeSidebar();
    } else {
      openSidebar();
    }
  }
  
  // Open sidebar
  function openSidebar() {
    const sidebar = document.querySelector('.meeting-sidebar');
    if (!sidebar) return;
    
    sidebar.classList.add('open');
    state.isSidebarOpen = true;
  }
  
  // Close sidebar
  function closeSidebar() {
    const sidebar = document.querySelector('.meeting-sidebar');
    if (!sidebar) return;
    
    sidebar.classList.remove('open');
    state.isSidebarOpen = false;
  }
  
  // Switch tab
  function switchTab(tabName) {
    // Update active tab
    state.activeTab = tabName;
    
    // Update tab UI
    const tabs = document.querySelectorAll('.sidebar-tab');
    tabs.forEach(tab => {
      if (tab.getAttribute('data-tab') === tabName) {
        tab.classList.add('active');
      } else {
        tab.classList.remove('active');
      }
    });
    
    // Update content UI
    const contents = document.querySelectorAll('.tab-content');
    contents.forEach(content => {
      if (content.getAttribute('data-tab-content') === tabName) {
        content.classList.add('active');
      } else {
        content.classList.remove('active');
      }
    });
    
    // Update sidebar title
    const sidebarTitle = document.querySelector('.sidebar-title');
    if (sidebarTitle) {
      sidebarTitle.textContent = tabName.charAt(0).toUpperCase() + tabName.slice(1);
    }
  }
  
  // Handle mobile navigation actions
  function handleNavAction(action) {
    switch (action) {
      case 'toggle-audio':
        toggleAudio();
        break;
      case 'toggle-video':
        toggleVideo();
        break;
      case 'toggle-chat':
        switchTab('chat');
        openSidebar();
        break;
      case 'toggle-participants':
        switchTab('participants');
        openSidebar();
        break;
      case 'end-call':
        endCall();
        break;
      default:
        console.log('Unknown action:', action);
    }
  }
  
  // Toggle audio
  function toggleAudio() {
    const audioButton = document.querySelector('[data-action="toggle-audio"]');
    if (!audioButton) return;
    
    const icon = audioButton.querySelector('i');
    if (icon.classList.contains('fa-microphone')) {
      icon.classList.remove('fa-microphone');
      icon.classList.add('fa-microphone-slash');
      // Actual mute logic would go here
    } else {
      icon.classList.remove('fa-microphone-slash');
      icon.classList.add('fa-microphone');
      // Actual unmute logic would go here
    }
  }
  
  // Toggle video
  function toggleVideo() {
    const videoButton = document.querySelector('[data-action="toggle-video"]');
    if (!videoButton) return;
    
    const icon = videoButton.querySelector('i');
    if (icon.classList.contains('fa-video')) {
      icon.classList.remove('fa-video');
      icon.classList.add('fa-video-slash');
      // Actual video off logic would go here
    } else {
      icon.classList.remove('fa-video-slash');
      icon.classList.add('fa-video');
      // Actual video on logic would go here
    }
  }
  
  // End call
  function endCall() {
    // This would typically navigate away or call an API
    if (confirm('Are you sure you want to leave this meeting?')) {
      window.location.href = '/meetings';
    }
  }
  
  // Toggle dark mode
  function toggleDarkMode() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    if (!darkModeToggle) return;
    
    state.darkMode = darkModeToggle.checked;
    
    if (state.darkMode) {
      document.body.classList.add('dark-mode');
      localStorage.setItem('darkMode', 'enabled');
    } else {
      document.body.classList.remove('dark-mode');
      localStorage.setItem('darkMode', 'disabled');
    }
  }
  
  // Check dark mode preference
  function checkDarkModePreference() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    if (!darkModeToggle) return;
    
    // Check local storage
    const darkMode = localStorage.getItem('darkMode');
    
    // Check system preference
    const prefersDarkMode = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (darkMode === 'enabled' || (darkMode === null && prefersDarkMode && config.enableDarkMode)) {
      darkModeToggle.checked = true;
      document.body.classList.add('dark-mode');
      state.darkMode = true;
    }
  }
  
  // Switch camera
  function switchCamera() {
    // This would typically call the WebRTC API to switch cameras
    console.log('Switching camera');
    
    // Show notification
    showNotification('Switching camera...', 'fa-camera');
  }
  
  // Toggle FAB menu
  function toggleFabMenu() {
    const fab = document.querySelector('.floating-action-button');
    if (!fab) return;
    
    fab.classList.toggle('active');
    
    // Show/hide FAB menu items
    const fabMenu = document.querySelector('.fab-menu');
    if (fabMenu) {
      fabMenu.classList.toggle('active');
    } else {
      createFabMenu();
    }
  }
  
  // Create FAB menu
  function createFabMenu() {
    const fabMenu = document.createElement('div');
    fabMenu.className = 'fab-menu active';
    
    fabMenu.innerHTML = `
      <div class="fab-menu-item" data-action="share-screen">
        <i class="fas fa-desktop"></i>
        <span>Share Screen</span>
      </div>
      <div class="fab-menu-item" data-action="blur-background">
        <i class="fas fa-portrait"></i>
        <span>Blur Background</span>
      </div>
      <div class="fab-menu-item" data-action="raise-hand">
        <i class="fas fa-hand-paper"></i>
        <span>Raise Hand</span>
      </div>
    `;
    
    document.body.appendChild(fabMenu);
    
    // Add event listeners
    const menuItems = fabMenu.querySelectorAll('.fab-menu-item');
    menuItems.forEach(item => {
      item.addEventListener('click', () => {
        const action = item.getAttribute('data-action');
        handleFabAction(action);
        toggleFabMenu();
      });
    });
  }
  
  // Handle FAB actions
  function handleFabAction(action) {
    switch (action) {
      case 'share-screen':
        // Share screen logic
        showNotification('Screen sharing is not available on mobile', 'fa-info-circle');
        break;
      case 'blur-background':
        // Toggle background blur
        toggleBackgroundBlur();
        break;
      case 'raise-hand':
        // Raise hand logic
        raiseHand();
        break;
      default:
        console.log('Unknown FAB action:', action);
    }
  }
  
  // Toggle background blur
  function toggleBackgroundBlur() {
    // This would integrate with the face processing service
    showNotification('Background blur toggled', 'fa-portrait');
  }
  
  // Raise hand
  function raiseHand() {
    showNotification('You raised your hand', 'fa-hand-paper');
  }
  
  // Show notification
  function showNotification(message, iconClass) {
    // Remove any existing notification
    const existingNotification = document.querySelector('.notification');
    if (existingNotification) {
      existingNotification.remove();
    }
    
    // Create notification
    const notification = document.createElement('div');
    notification.className = 'notification';
    notification.innerHTML = `
      <div class="notification-icon">
        <i class="fas ${iconClass}"></i>
      </div>
      <div class="notification-text">${message}</div>
    `;
    
    document.body.appendChild(notification);
    
    // Remove after 5 seconds
    setTimeout(() => {
      notification.remove();
    }, 5000);
  }
  
  // Setup auto-hide controls
  function setupAutoHideControls() {
    resetControlsHideTimeout();
  }
  
  // Reset controls hide timeout
  function resetControlsHideTimeout() {
    if (state.controlsHideTimeout) {
      clearTimeout(state.controlsHideTimeout);
    }
    
    showControls();
    
    state.controlsHideTimeout = setTimeout(() => {
      hideControls();
    }, 5000);
  }
  
  // Show controls
  function showControls() {
    const controls = document.querySelector('.mobile-nav');
    const header = document.querySelector('.meeting-header');
    
    if (controls) {
      controls.style.transform = 'translateY(0)';
      controls.style.opacity = '1';
    }
    
    if (header) {
      header.style.transform = 'translateY(0)';
      header.style.opacity = '1';
    }
    
    state.isControlsVisible = true;
  }
  
  // Hide controls
  function hideControls() {
    if (state.isSidebarOpen) return;
    
    const controls = document.querySelector('.mobile-nav');
    const header = document.querySelector('.meeting-header');
    
    if (controls) {
      controls.style.transform = 'translateY(100%)';
      controls.style.opacity = '0';
    }
    
    if (header) {
      header.style.transform = 'translateY(-100%)';
      header.style.opacity = '0';
    }
    
    state.isControlsVisible = false;
  }
  
  // Create floating action button for face processing
  function createFaceProcessingFAB() {
    const fab = document.createElement('div');
    fab.className = 'face-processing-fab tooltip';
    fab.setAttribute('data-tooltip', 'Face Processing');
    fab.innerHTML = '<i class="fas fa-user-shield"></i>';
    
    fab.addEventListener('click', () => {
      // Toggle face processing panel
      const faceProcessingContainer = document.querySelector('.face-processing-controls');
      if (faceProcessingContainer) {
        faceProcessingContainer.style.display = faceProcessingContainer.style.display === 'none' ? 'block' : 'none';
      }
    });
    
    document.body.appendChild(fab);
  }
  
  // Check if we're on a mobile device
  function isMobileDevice() {
    return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
  }
  
  // Initialize only on mobile devices
  if (isMobileDevice()) {
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', init);
    } else {
      init();
    }
  }
})();
