// Fix for draggable elements
(function() {
  // Make elements draggable
  function makeDraggable(element) {
    if (!element) return;
    
    let isDragging = false;
    let startX, startY;
    let elementX = 0, elementY = 0;
    
    // Add draggable class
    element.classList.add('draggable');
    
    // Create handle if it doesn't exist
    let handle = element.querySelector('.drag-handle');
    if (!handle) {
      handle = document.createElement('div');
      handle.className = 'drag-handle';
      handle.style.position = 'absolute';
      handle.style.top = '0';
      handle.style.left = '0';
      handle.style.width = '100%';
      handle.style.height = '10px';
      handle.style.cursor = 'move';
      element.insertBefore(handle, element.firstChild);
    }
    
    // Start drag
    function startDrag(e) {
      isDragging = true;
      
      // Get initial position
      if (e.type === 'mousedown') {
        startX = e.clientX;
        startY = e.clientY;
      } else if (e.type === 'touchstart') {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
      }
      
      // Get current element position
      const rect = element.getBoundingClientRect();
      elementX = rect.left;
      elementY = rect.top;
      
      // Prevent default
      e.preventDefault();
    }
    
    // Drag
    function drag(e) {
      if (!isDragging) return;
      
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
      
      // Calculate new position
      const deltaX = clientX - startX;
      const deltaY = clientY - startY;
      
      // Update position
      element.style.left = (elementX + deltaX) + 'px';
      element.style.top = (elementY + deltaY) + 'px';
      
      // Remove default positioning
      element.style.bottom = 'auto';
      element.style.right = 'auto';
      element.style.transform = 'none';
    }
    
    // End drag
    function endDrag() {
      isDragging = false;
    }
    
    // Add event listeners
    handle.addEventListener('mousedown', startDrag);
    handle.addEventListener('touchstart', startDrag);
    document.addEventListener('mousemove', drag);
    document.addEventListener('touchmove', drag);
    document.addEventListener('mouseup', endDrag);
    document.addEventListener('touchend', endDrag);
  }
  
  // Find and make elements draggable
  function findAndMakeDraggable() {
    // Gesture info panel
    const gestureInfo = document.querySelector('.gesture-info');
    if (gestureInfo) makeDraggable(gestureInfo);
    
    // Gesture message display
    const messageDisplay = document.querySelector('.gesture-message-display');
    if (messageDisplay) makeDraggable(messageDisplay);
    
    // Gesture interpreter
    const interpreter = document.querySelector('.gesture-interpreter');
    if (interpreter) makeDraggable(interpreter);
  }
  
  // Run periodically to catch newly added elements
  setInterval(findAndMakeDraggable, 2000);
})();
