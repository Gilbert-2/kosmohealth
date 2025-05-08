/**
 * KYC 404 Handler
 * This script detects 404 errors on KYC pages and redirects to the correct URL
 */

(function() {
  // Check if we're on a 404 page
  function is404Page() {
    return document.title.includes('404') || 
           document.title.includes('Not Found') ||
           document.querySelector('.error-page') ||
           document.querySelector('.not-found');
  }
  
  // Check if the URL contains KYC-related paths
  function isKycUrl() {
    const path = window.location.pathname;
    return path.includes('/admin/kyc') || 
           path.includes('/app/admin/kyc') ||
           path.includes('/kyc/admin');
  }
  
  // Redirect to the correct KYC admin URL
  function redirectToKycAdmin() {
    console.log('Redirecting to KYC admin page');
    window.location.replace('/app/admin/kyc/requests');
  }
  
  // Check if we need to redirect
  function checkAndRedirect() {
    if (is404Page() && isKycUrl()) {
      redirectToKycAdmin();
    }
  }
  
  // Run the check when the page loads
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', checkAndRedirect);
  } else {
    checkAndRedirect();
  }
  
  // Also add a global error handler for navigation errors
  window.addEventListener('error', function(event) {
    if (isKycUrl()) {
      // If there's an error on a KYC page, try redirecting
      redirectToKycAdmin();
    }
  }, true);
  
  console.log('KYC 404 handler loaded');
})();
