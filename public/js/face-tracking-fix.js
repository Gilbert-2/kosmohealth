// Fix for face tracking
(function() {
  // Load CSS fix
  const link = document.createElement('link');
  link.rel = 'stylesheet';
  link.href = '/css/face-tracking-fix.css';
  document.head.appendChild(link);
  
  // Fix canvas positioning
  setInterval(function() {
    const canvases = document.querySelectorAll('.face-processing-canvas');
    canvases.forEach(canvas => {
      canvas.style.zIndex = '30';
      canvas.style.position = 'absolute';
      canvas.style.top = '0';
      canvas.style.left = '0';
      canvas.style.width = '100%';
      canvas.style.height = '100%';
    });
  }, 1000);
})();
