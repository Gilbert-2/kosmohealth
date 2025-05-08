/**
 * Load Live Feed Enhancements for KosmoHealth
 * This script loads all the enhancements for the live feed interface
 */

(function() {
  // Check if we're on a meeting page
  function isMeetingPage() {
    return window.location.href.includes('/app/live/meetings/') ||
           window.location.href.includes('/app/live/meetings-gesture/');
  }

  // Load enhancements if on meeting page
  function loadEnhancements() {
    if (!isMeetingPage()) return;

    // Load CSS
    loadCSS('/css/modern-livefeed.css');
    loadCSS('/css/draggable-menu.css');

    // Load JS files
    loadScript('/js/draggable-video.js');
    loadScript('/js/modern-livefeed.js');
    loadScript('/js/draggable-menu.js');

    console.log('KosmoHealth live feed enhancements loaded');
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

  // Load JS file
  function loadScript(src) {
    // Check if already loaded
    const existingScript = document.querySelector(`script[src="${src}"]`);
    if (existingScript) return;

    const script = document.createElement('script');
    script.src = src;
    document.body.appendChild(script);
  }

  // Load on page load
  window.addEventListener('load', loadEnhancements);

  // Check on URL changes (for single-page apps)
  let lastUrl = window.location.href;

  // Create observer to watch for URL changes
  const observer = new MutationObserver(() => {
    if (window.location.href !== lastUrl) {
      lastUrl = window.location.href;
      loadEnhancements();
    }
  });

  // Start observing
  observer.observe(document, { subtree: true, childList: true });
})();
