/**
 * Base Input Fix
 * This script fixes the base-input component to handle undefined labels
 */

(function() {
  // Function to fix base-input components
  function fixBaseInputs() {
    // Find all base-input components
    const baseInputs = document.querySelectorAll('.form-group');
    
    baseInputs.forEach(formGroup => {
      // Find the label
      const label = formGroup.querySelector('label');
      
      // Find the input
      const input = formGroup.querySelector('input');
      
      // Fix undefined labels
      if (label && (label.textContent === 'undefined' || label.textContent.trim() === '')) {
        // Try to determine a reasonable label from the input
        if (input) {
          if (input.type === 'text' && input.autocomplete === 'username') {
            label.textContent = 'Email or Username';
          } else if (input.type === 'password') {
            label.textContent = 'Password';
          } else if (input.name) {
            // Convert camelCase or snake_case to Title Case with spaces
            label.textContent = input.name
              .replace(/_/g, ' ')
              .replace(/([A-Z])/g, ' $1')
              .replace(/^./, str => str.toUpperCase());
          }
        }
      }
      
      // Fix undefined placeholders
      if (input && (input.placeholder === 'undefined' || input.placeholder === '')) {
        if (input.type === 'text' && input.autocomplete === 'username') {
          input.placeholder = 'Enter your email or username';
        } else if (input.type === 'password') {
          input.placeholder = 'Enter your password';
        } else if (label && label.textContent) {
          input.placeholder = 'Enter your ' + label.textContent.toLowerCase();
        }
      }
    });
  }
  
  // Apply the fix when the DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', fixBaseInputs);
  } else {
    fixBaseInputs();
  }
  
  // Also apply the fix periodically to catch dynamically loaded components
  setInterval(fixBaseInputs, 1000);
  
  // Apply the fix when the route changes
  window.addEventListener('popstate', fixBaseInputs);
  
  console.log('Base input fix loaded');
})();
