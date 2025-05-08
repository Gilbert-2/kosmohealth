/**
 * Login Form Fix
 * This script fixes the "undefined" text in the login form
 */

(function() {
  // Function to fix the login form
  function fixLoginForm() {
    // Wait for the DOM to be ready
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', applyFix);
    } else {
      applyFix();
    }
    
    // Also apply the fix when the route changes (for SPA)
    window.addEventListener('popstate', applyFix);
  }
  
  // Apply the fix to the login form
  function applyFix() {
    // Check if we're on the login page
    if (window.location.pathname.includes('/login') || 
        window.location.hash.includes('/login')) {
      
      // Wait for Vue to render the form
      setTimeout(() => {
        // Find all elements that might contain "undefined"
        const elements = document.querySelectorAll('*');
        
        elements.forEach(el => {
          // Check text content
          if (el.textContent === 'undefined') {
            el.textContent = '';
          }
          
          // Check placeholder
          if (el.placeholder === 'undefined') {
            el.placeholder = '';
          }
          
          // Check value
          if (el.value === 'undefined') {
            el.value = '';
          }
          
          // Check for labels with undefined
          if (el.tagName === 'LABEL' && el.textContent === 'undefined') {
            el.textContent = '';
          }
        });
        
        // Fix specific input fields
        const emailInput = document.querySelector('input[type="text"][autocomplete="username"]');
        if (emailInput) {
          // Fix label
          const emailLabel = emailInput.closest('.form-group')?.querySelector('label');
          if (emailLabel && emailLabel.textContent === 'undefined') {
            emailLabel.textContent = 'Email or Username';
          }
          
          // Fix placeholder
          if (emailInput.placeholder === 'undefined') {
            emailInput.placeholder = 'Enter your email or username';
          }
        }
        
        const passwordInput = document.querySelector('input[type="password"]');
        if (passwordInput) {
          // Fix label
          const passwordLabel = passwordInput.closest('.form-group')?.querySelector('label');
          if (passwordLabel && passwordLabel.textContent === 'undefined') {
            passwordLabel.textContent = 'Password';
          }
          
          // Fix placeholder
          if (passwordInput.placeholder === 'undefined') {
            passwordInput.placeholder = 'Enter your password';
          }
        }
        
        // Fix login button
        const loginButton = document.querySelector('button[type="submit"]');
        if (loginButton && loginButton.textContent === 'undefined') {
          loginButton.textContent = 'Login';
        }
        
        console.log('Login form fixed');
      }, 500); // Wait for Vue to render
    }
  }
  
  // Initialize the fix
  fixLoginForm();
  
  // Also fix when translations are loaded
  const originalFetch = window.fetch;
  window.fetch = function(url, options) {
    // Check if this is a translation request
    if (url.includes('/js/lang') || url.includes('/api/translations')) {
      return originalFetch.apply(this, arguments).then(response => {
        // After translations are loaded, apply the fix
        setTimeout(applyFix, 500);
        return response;
      });
    }
    
    // Continue with the original fetch
    return originalFetch.apply(this, arguments);
  };
  
  // Monitor for Vue router changes
  if (window.Vue) {
    const originalPush = window.Vue.prototype.$router?.push;
    if (originalPush) {
      window.Vue.prototype.$router.push = function(location) {
        const result = originalPush.apply(this, arguments);
        
        // If navigating to login, apply the fix
        if (typeof location === 'object' && location.name === 'login' ||
            typeof location === 'string' && location.includes('login')) {
          setTimeout(applyFix, 500);
        }
        
        return result;
      };
    }
  }
  
  console.log('Login form fix loaded');
})();
