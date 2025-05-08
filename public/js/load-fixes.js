// Load all fixes
(function() {
  // Load CSS fixes
  const cssFiles = [
    '/css/face-tracking-fix.css',
    '/css/gesture-info-fix.css',
    '/css/gesture-message-fix.css'
  ];

  cssFiles.forEach(file => {
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = file;
    document.head.appendChild(link);
  });

  // Load JS fixes
  const jsFiles = [
    '/js/face-tracking-fix.js',
    '/js/face-blur-fix.js',
    '/js/green-rectangle-fix.js',
    '/js/draggable-fix.js',
    '/js/gesture-controls-fix.js',
    '/js/low-internet-fix.js'
  ];

  jsFiles.forEach(file => {
    const script = document.createElement('script');
    script.src = file;
    document.body.appendChild(script);
  });
})();
