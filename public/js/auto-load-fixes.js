// Automatically load fixes on meeting pages
(function() {
  // Check if we're on a meeting page
  function isMeetingPage() {
    return window.location.href.includes('/app/live/meetings/') || 
           window.location.href.includes('/app/live/meetings-gesture/');
  }
  
  // Load fixes if on meeting page
  function checkAndLoadFixes() {
    if (isMeetingPage()) {
      // Load the fixes script
      const script = document.createElement('script');
      script.src = '/js/load-fixes.js';
      document.body.appendChild(script);
      
      console.log('KosmoHealth meeting fixes loaded');
    }
  }
  
  // Check on page load
  checkAndLoadFixes();
  
  // Check on URL changes (for single-page apps)
  let lastUrl = window.location.href;
  
  // Create observer to watch for URL changes
  const observer = new MutationObserver(() => {
    if (window.location.href !== lastUrl) {
      lastUrl = window.location.href;
      checkAndLoadFixes();
    }
  });
  
  // Start observing
  observer.observe(document, { subtree: true, childList: true });
})();
