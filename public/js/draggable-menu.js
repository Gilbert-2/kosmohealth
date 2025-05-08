/**
 * Draggable Menu for KosmoHealth Live Feed
 * This script creates a draggable menu that appears in front of the live feed canvas
 */

(function() {
  // Configuration
  const config = {
    defaultPosition: { x: 20, y: 20 },
    defaultSize: { width: 250, height: 'auto' },
    minWidth: 200,
    snapThreshold: 20,
    snapToEdges: true,
    savePosition: true
  };
  
  // State
  let isDragging = false;
  let startX, startY, startLeft, startTop;
  let menuContainer = null;
  
  // Initialize
  function initialize() {
    // Load CSS
    loadCSS('/css/draggable-menu.css');
    
    // Check if we're on a meeting page
    if (isMeetingPage()) {
      // Create the menu if it doesn't exist
      if (!document.querySelector('.draggable-menu-container')) {
        createDraggableMenu();
      }
    }
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
  
  // Check if we're on a meeting page
  function isMeetingPage() {
    return window.location.href.includes('/app/live/meetings/') || 
           window.location.href.includes('/app/live/meetings-gesture/');
  }
  
  // Create the draggable menu
  function createDraggableMenu() {
    // Create container
    menuContainer = document.createElement('div');
    menuContainer.className = 'draggable-menu-container';
    
    // Set initial position (from localStorage or default)
    const savedPosition = getSavedPosition();
    menuContainer.style.left = `${savedPosition.x}px`;
    menuContainer.style.top = `${savedPosition.y}px`;
    menuContainer.style.width = `${config.defaultSize.width}px`;
    menuContainer.style.height = config.defaultSize.height;
    
    // Create header with drag handle
    const header = document.createElement('div');
    header.className = 'draggable-menu-header';
    
    const title = document.createElement('h3');
    title.className = 'draggable-menu-title';
    title.textContent = 'Menu';
    
    const controls = document.createElement('div');
    controls.className = 'draggable-menu-controls';
    
    // Minimize button
    const minimizeBtn = document.createElement('button');
    minimizeBtn.className = 'draggable-menu-btn draggable-menu-minimize';
    minimizeBtn.innerHTML = '<i class="fas fa-minus"></i>';
    minimizeBtn.title = 'Minimize';
    minimizeBtn.addEventListener('click', () => {
      menuContainer.classList.toggle('minimized');
      minimizeBtn.innerHTML = menuContainer.classList.contains('minimized') 
        ? '<i class="fas fa-expand"></i>' 
        : '<i class="fas fa-minus"></i>';
    });
    
    // Close button
    const closeBtn = document.createElement('button');
    closeBtn.className = 'draggable-menu-btn draggable-menu-close';
    closeBtn.innerHTML = '<i class="fas fa-times"></i>';
    closeBtn.title = 'Close';
    closeBtn.addEventListener('click', () => {
      menuContainer.style.display = 'none';
    });
    
    controls.appendChild(minimizeBtn);
    controls.appendChild(closeBtn);
    
    header.appendChild(title);
    header.appendChild(controls);
    
    // Create content
    const content = document.createElement('div');
    content.className = 'draggable-menu-content';
    
    // Create menu items
    const menuItems = document.createElement('div');
    menuItems.className = 'draggable-menu-items';
    
    // Add menu items
    const items = [
      {
        name: 'Chat',
        icon: 'fa-comments',
        class: 'chat-item',
        action: () => {
          // Emit event to open chat
          const event = new CustomEvent('open-chat');
          document.dispatchEvent(event);
        }
      },
      {
        name: 'Kosmobot',
        icon: 'fa-robot',
        class: 'kosmobot-item',
        action: () => {
          // Emit event to open Kosmobot
          const event = new CustomEvent('open-kosmobot');
          document.dispatchEvent(event);
        }
      },
      {
        name: 'Period Calculator',
        icon: 'fa-calendar-alt',
        class: 'period-calculator-item',
        action: () => {
          // Emit event to open Period Calculator
          const event = new CustomEvent('open-period-calculator');
          document.dispatchEvent(event);
        }
      },
      {
        name: 'Face Processing',
        icon: 'fa-user-shield',
        class: 'face-processing-item',
        action: () => {
          // Toggle face processing
          const faceProcessingControls = document.querySelector('.face-processing-controls');
          if (faceProcessingControls) {
            faceProcessingControls.style.display = 
              faceProcessingControls.style.display === 'none' ? 'block' : 'none';
          }
        }
      },
      {
        name: 'Gesture Recognition',
        icon: 'fa-sign-language',
        class: 'gesture-recognition-item',
        action: () => {
          // Toggle gesture recognition
          const gestureBtn = document.querySelector('.gesture-mode-btn');
          if (gestureBtn) {
            gestureBtn.click();
          }
        }
      }
    ];
    
    items.forEach(item => {
      const menuItem = document.createElement('a');
      menuItem.href = '#';
      menuItem.className = `draggable-menu-item ${item.class}`;
      
      const icon = document.createElement('div');
      icon.className = 'draggable-menu-item-icon';
      icon.innerHTML = `<i class="fas ${item.icon}"></i>`;
      
      const label = document.createElement('span');
      label.className = 'draggable-menu-item-label';
      label.textContent = item.name;
      
      menuItem.appendChild(icon);
      menuItem.appendChild(label);
      
      menuItem.addEventListener('click', (e) => {
        e.preventDefault();
        if (item.action) {
          item.action();
        }
      });
      
      menuItems.appendChild(menuItem);
    });
    
    content.appendChild(menuItems);
    
    // Assemble menu
    menuContainer.appendChild(header);
    menuContainer.appendChild(content);
    
    // Add to document
    document.body.appendChild(menuContainer);
    
    // Set up drag functionality
    setupDrag(menuContainer, header);
    
    return menuContainer;
  }
  
  // Set up drag functionality
  function setupDrag(container, handle) {
    handle.addEventListener('mousedown', startDrag);
    handle.addEventListener('touchstart', startDrag);
    
    function startDrag(e) {
      e.preventDefault();
      
      isDragging = true;
      
      // Get initial position
      const rect = container.getBoundingClientRect();
      startLeft = rect.left;
      startTop = rect.top;
      
      if (e.type === 'mousedown') {
        startX = e.clientX;
        startY = e.clientY;
      } else if (e.type === 'touchstart') {
        startX = e.touches[0].clientX;
        startY = e.touches[0].clientY;
      }
      
      // Add active class
      container.classList.add('active');
      
      // Bring to front
      container.style.zIndex = '1100';
      
      // Add event listeners for drag and end
      document.addEventListener('mousemove', drag);
      document.addEventListener('touchmove', drag);
      document.addEventListener('mouseup', endDrag);
      document.addEventListener('touchend', endDrag);
    }
    
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
      let newLeft = startLeft + (clientX - startX);
      let newTop = startTop + (clientY - startY);
      
      // Apply snap to edges if enabled
      if (config.snapToEdges) {
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        const elementWidth = container.offsetWidth;
        const elementHeight = container.offsetHeight;
        
        // Snap to left edge
        if (newLeft < config.snapThreshold) {
          newLeft = 0;
        }
        
        // Snap to right edge
        if (newLeft + elementWidth > viewportWidth - config.snapThreshold) {
          newLeft = viewportWidth - elementWidth;
        }
        
        // Snap to top edge
        if (newTop < config.snapThreshold) {
          newTop = 0;
        }
        
        // Snap to bottom edge
        if (newTop + elementHeight > viewportHeight - config.snapThreshold) {
          newTop = viewportHeight - elementHeight;
        }
      }
      
      // Update position
      container.style.left = `${newLeft}px`;
      container.style.top = `${newTop}px`;
    }
    
    function endDrag() {
      if (!isDragging) return;
      
      isDragging = false;
      
      // Remove active class
      container.classList.remove('active');
      
      // Save position
      if (config.savePosition) {
        savePosition(container);
      }
      
      // Remove event listeners
      document.removeEventListener('mousemove', drag);
      document.removeEventListener('touchmove', drag);
      document.removeEventListener('mouseup', endDrag);
      document.removeEventListener('touchend', endDrag);
    }
  }
  
  // Save position to localStorage
  function savePosition(container) {
    const rect = container.getBoundingClientRect();
    const position = {
      x: rect.left,
      y: rect.top
    };
    
    try {
      localStorage.setItem('draggableMenuPosition', JSON.stringify(position));
    } catch (e) {
      console.error('Could not save menu position to localStorage', e);
    }
  }
  
  // Get saved position from localStorage
  function getSavedPosition() {
    try {
      const saved = localStorage.getItem('draggableMenuPosition');
      if (saved) {
        return JSON.parse(saved);
      }
    } catch (e) {
      console.error('Could not get menu position from localStorage', e);
    }
    
    return config.defaultPosition;
  }
  
  // Show the menu
  function showMenu() {
    if (menuContainer) {
      menuContainer.style.display = 'block';
    } else {
      createDraggableMenu();
    }
  }
  
  // Hide the menu
  function hideMenu() {
    if (menuContainer) {
      menuContainer.style.display = 'none';
    }
  }
  
  // Toggle the menu
  function toggleMenu() {
    if (menuContainer && menuContainer.style.display === 'none') {
      showMenu();
    } else if (menuContainer) {
      hideMenu();
    } else {
      createDraggableMenu();
    }
  }
  
  // Add menu toggle button to meeting controls
  function addMenuToggleButton() {
    // Find meeting controls
    const controls = document.querySelector('.meeting-footer') || 
                    document.querySelector('.rtc-controls') ||
                    document.querySelector('.meeting-controls');
    
    if (!controls) return;
    
    // Check if button already exists
    if (controls.querySelector('.menu-toggle-btn')) return;
    
    // Create button
    const button = document.createElement('button');
    button.className = 'video-control-btn menu-toggle-btn';
    button.innerHTML = '<i class="fas fa-bars"></i>';
    button.title = 'Toggle Menu';
    
    // Add click handler
    button.addEventListener('click', toggleMenu);
    
    // Add to controls
    controls.appendChild(button);
  }
  
  // Initialize on load and periodically check for changes
  window.addEventListener('load', () => {
    initialize();
    addMenuToggleButton();
  });
  
  // Check periodically for meeting page and controls
  setInterval(() => {
    if (isMeetingPage()) {
      addMenuToggleButton();
    }
  }, 2000);
  
  // Expose API
  window.DraggableMenu = {
    show: showMenu,
    hide: hideMenu,
    toggle: toggleMenu
  };
})();
