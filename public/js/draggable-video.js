/**
 * Draggable and Resizable Video for KosmoHealth
 * This script makes video elements draggable and resizable in the meeting interface
 */

(function() {
  // Configuration
  const config = {
    minWidth: 200,
    minHeight: 150,
    defaultWidth: 320,
    defaultHeight: 240,
    snapThreshold: 20,
    snapToEdges: true
  };
  
  // Track active elements
  let activeElement = null;
  let isDragging = false;
  let isResizing = false;
  let resizeHandle = null;
  let initialX, initialY, initialWidth, initialHeight;
  let offsetX, offsetY;
  
  // Initialize
  function initialize() {
    // Find all video containers
    const videoContainers = document.querySelectorAll('.video-container, .meeting-video-container, .rtc-video-container');
    
    videoContainers.forEach(container => {
      // Skip if already processed
      if (container.classList.contains('processed-for-drag')) return;
      
      // Mark as processed
      container.classList.add('processed-for-drag');
      
      // Find video element
      const videoElement = container.querySelector('video');
      if (!videoElement) return;
      
      // Create draggable container
      createDraggableContainer(container, videoElement);
    });
  }
  
  // Create draggable container for a video
  function createDraggableContainer(originalContainer, videoElement) {
    // Get original dimensions
    const rect = originalContainer.getBoundingClientRect();
    
    // Create draggable container
    const draggableContainer = document.createElement('div');
    draggableContainer.className = 'draggable-video-container';
    draggableContainer.style.width = `${config.defaultWidth}px`;
    draggableContainer.style.height = `${config.defaultHeight}px`;
    draggableContainer.style.top = `${rect.top + 20}px`;
    draggableContainer.style.left = `${rect.left + 20}px`;
    
    // Create drag handle
    const dragHandle = document.createElement('div');
    dragHandle.className = 'video-drag-handle';
    draggableContainer.appendChild(dragHandle);
    
    // Create resize handles
    const resizeHandles = {
      se: document.createElement('div'),
      sw: document.createElement('div'),
      ne: document.createElement('div'),
      nw: document.createElement('div')
    };
    
    Object.entries(resizeHandles).forEach(([position, handle]) => {
      handle.className = `resize-handle resize-handle-${position}`;
      draggableContainer.appendChild(handle);
    });
    
    // Create video controls
    const videoControls = document.createElement('div');
    videoControls.className = 'video-controls';
    
    // Mute button
    const muteBtn = document.createElement('button');
    muteBtn.className = 'video-control-btn';
    muteBtn.innerHTML = '<i class="fas fa-microphone"></i>';
    muteBtn.title = 'Mute/Unmute';
    muteBtn.addEventListener('click', () => {
      if (videoElement.muted) {
        videoElement.muted = false;
        muteBtn.innerHTML = '<i class="fas fa-microphone"></i>';
      } else {
        videoElement.muted = true;
        muteBtn.innerHTML = '<i class="fas fa-microphone-slash"></i>';
      }
    });
    videoControls.appendChild(muteBtn);
    
    // Video toggle button
    const videoBtn = document.createElement('button');
    videoBtn.className = 'video-control-btn';
    videoBtn.innerHTML = '<i class="fas fa-video"></i>';
    videoBtn.title = 'Enable/Disable Video';
    videoBtn.addEventListener('click', () => {
      if (videoElement.style.display === 'none') {
        videoElement.style.display = 'block';
        videoBtn.innerHTML = '<i class="fas fa-video"></i>';
      } else {
        videoElement.style.display = 'none';
        videoBtn.innerHTML = '<i class="fas fa-video-slash"></i>';
      }
    });
    videoControls.appendChild(videoBtn);
    
    // Fullscreen button
    const fullscreenBtn = document.createElement('button');
    fullscreenBtn.className = 'video-control-btn';
    fullscreenBtn.innerHTML = '<i class="fas fa-expand"></i>';
    fullscreenBtn.title = 'Fullscreen';
    fullscreenBtn.addEventListener('click', () => {
      if (videoElement.requestFullscreen) {
        videoElement.requestFullscreen();
      } else if (videoElement.webkitRequestFullscreen) {
        videoElement.webkitRequestFullscreen();
      } else if (videoElement.msRequestFullscreen) {
        videoElement.msRequestFullscreen();
      }
    });
    videoControls.appendChild(fullscreenBtn);
    
    // Pin button
    const pinBtn = document.createElement('button');
    pinBtn.className = 'video-control-btn';
    pinBtn.innerHTML = '<i class="fas fa-thumbtack"></i>';
    pinBtn.title = 'Pin/Unpin';
    pinBtn.addEventListener('click', () => {
      if (draggableContainer.classList.contains('pinned')) {
        draggableContainer.classList.remove('pinned');
        pinBtn.classList.remove('active');
      } else {
        draggableContainer.classList.add('pinned');
        pinBtn.classList.add('active');
      }
    });
    videoControls.appendChild(pinBtn);
    
    // Add controls to container
    draggableContainer.appendChild(videoControls);
    
    // Add participant name if available
    const participantName = document.createElement('div');
    participantName.className = 'participant-name';
    participantName.textContent = 'You';
    draggableContainer.appendChild(participantName);
    
    // Add network status indicator
    const networkStatus = document.createElement('div');
    networkStatus.className = 'network-status';
    networkStatus.innerHTML = `
      <div class="network-status-icon network-status-good"></div>
      <span>Good</span>
    `;
    draggableContainer.appendChild(networkStatus);
    
    // Clone video element
    const clonedVideo = videoElement.cloneNode(true);
    clonedVideo.className = 'modern-video';
    draggableContainer.appendChild(clonedVideo);
    
    // Add to document
    document.body.appendChild(draggableContainer);
    
    // Set up event listeners
    setupDragListeners(draggableContainer, dragHandle);
    setupResizeListeners(draggableContainer, resizeHandles);
    
    // Make original container invisible if needed
    // originalContainer.style.visibility = 'hidden';
    
    return draggableContainer;
  }
  
  // Set up drag listeners
  function setupDragListeners(container, handle) {
    handle.addEventListener('mousedown', startDrag);
    handle.addEventListener('touchstart', startDrag);
    
    container.addEventListener('mousedown', (e) => {
      if (e.target === container) {
        setActiveElement(container);
      }
    });
    
    function startDrag(e) {
      e.preventDefault();
      
      // Set as active element
      setActiveElement(container);
      
      isDragging = true;
      isResizing = false;
      
      // Get initial position
      const rect = container.getBoundingClientRect();
      initialX = rect.left;
      initialY = rect.top;
      
      if (e.type === 'mousedown') {
        offsetX = e.clientX - initialX;
        offsetY = e.clientY - initialY;
      } else if (e.type === 'touchstart') {
        offsetX = e.touches[0].clientX - initialX;
        offsetY = e.touches[0].clientY - initialY;
      }
    }
  }
  
  // Set up resize listeners
  function setupResizeListeners(container, handles) {
    Object.entries(handles).forEach(([position, handle]) => {
      handle.addEventListener('mousedown', (e) => startResize(e, position, container));
      handle.addEventListener('touchstart', (e) => startResize(e, position, container));
    });
    
    function startResize(e, position, container) {
      e.preventDefault();
      
      // Set as active element
      setActiveElement(container);
      
      isDragging = false;
      isResizing = true;
      resizeHandle = position;
      
      // Get initial dimensions
      const rect = container.getBoundingClientRect();
      initialX = rect.left;
      initialY = rect.top;
      initialWidth = rect.width;
      initialHeight = rect.height;
      
      if (e.type === 'mousedown') {
        offsetX = e.clientX;
        offsetY = e.clientY;
      } else if (e.type === 'touchstart') {
        offsetX = e.touches[0].clientX;
        offsetY = e.touches[0].clientY;
      }
    }
  }
  
  // Set active element
  function setActiveElement(element) {
    // Remove active class from previous element
    if (activeElement && activeElement !== element) {
      activeElement.classList.remove('active');
    }
    
    // Set new active element
    activeElement = element;
    activeElement.classList.add('active');
    
    // Bring to front
    activeElement.style.zIndex = getHighestZIndex() + 1;
  }
  
  // Get highest z-index
  function getHighestZIndex() {
    const elements = document.querySelectorAll('.draggable-video-container');
    let highest = 100; // Start at 100
    
    elements.forEach(el => {
      const zIndex = parseInt(window.getComputedStyle(el).zIndex, 10);
      if (!isNaN(zIndex) && zIndex > highest) {
        highest = zIndex;
      }
    });
    
    return highest;
  }
  
  // Handle mouse/touch move
  function handleMove(e) {
    if (!isDragging && !isResizing) return;
    
    let clientX, clientY;
    
    if (e.type === 'mousemove') {
      clientX = e.clientX;
      clientY = e.clientY;
    } else if (e.type === 'touchmove') {
      clientX = e.touches[0].clientX;
      clientY = e.touches[0].clientY;
    } else {
      return;
    }
    
    if (isDragging && activeElement) {
      // Calculate new position
      let newX = clientX - offsetX;
      let newY = clientY - offsetY;
      
      // Apply snap to edges if enabled
      if (config.snapToEdges) {
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        const elementWidth = activeElement.offsetWidth;
        const elementHeight = activeElement.offsetHeight;
        
        // Snap to left edge
        if (newX < config.snapThreshold) {
          newX = 0;
        }
        
        // Snap to right edge
        if (newX + elementWidth > viewportWidth - config.snapThreshold) {
          newX = viewportWidth - elementWidth;
        }
        
        // Snap to top edge
        if (newY < config.snapThreshold) {
          newY = 0;
        }
        
        // Snap to bottom edge
        if (newY + elementHeight > viewportHeight - config.snapThreshold) {
          newY = viewportHeight - elementHeight;
        }
      }
      
      // Update position
      activeElement.style.left = `${newX}px`;
      activeElement.style.top = `${newY}px`;
    } else if (isResizing && activeElement) {
      // Calculate deltas
      const deltaX = clientX - offsetX;
      const deltaY = clientY - offsetY;
      
      // Calculate new dimensions based on resize handle
      let newWidth = initialWidth;
      let newHeight = initialHeight;
      let newX = initialX;
      let newY = initialY;
      
      switch (resizeHandle) {
        case 'se':
          newWidth = Math.max(config.minWidth, initialWidth + deltaX);
          newHeight = Math.max(config.minHeight, initialHeight + deltaY);
          break;
        case 'sw':
          newWidth = Math.max(config.minWidth, initialWidth - deltaX);
          newHeight = Math.max(config.minHeight, initialHeight + deltaY);
          newX = initialX + initialWidth - newWidth;
          break;
        case 'ne':
          newWidth = Math.max(config.minWidth, initialWidth + deltaX);
          newHeight = Math.max(config.minHeight, initialHeight - deltaY);
          newY = initialY + initialHeight - newHeight;
          break;
        case 'nw':
          newWidth = Math.max(config.minWidth, initialWidth - deltaX);
          newHeight = Math.max(config.minHeight, initialHeight - deltaY);
          newX = initialX + initialWidth - newWidth;
          newY = initialY + initialHeight - newHeight;
          break;
      }
      
      // Update dimensions and position
      activeElement.style.width = `${newWidth}px`;
      activeElement.style.height = `${newHeight}px`;
      activeElement.style.left = `${newX}px`;
      activeElement.style.top = `${newY}px`;
    }
  }
  
  // Handle mouse/touch end
  function handleEnd() {
    isDragging = false;
    isResizing = false;
    resizeHandle = null;
  }
  
  // Add global event listeners
  document.addEventListener('mousemove', handleMove);
  document.addEventListener('touchmove', handleMove);
  document.addEventListener('mouseup', handleEnd);
  document.addEventListener('touchend', handleEnd);
  
  // Initialize on load and periodically check for new videos
  window.addEventListener('load', initialize);
  setInterval(initialize, 2000);
})();
