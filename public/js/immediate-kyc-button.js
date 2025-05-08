/**
 * Immediate KYC Button
 * This script immediately injects a KYC button into the page
 */

// Immediately create and inject the KYC button
(function() {
  // Create button element
  const button = document.createElement('a');
  button.id = 'immediate-kyc-button';
  button.href = '/app/admin/kyc/requests';
  
  // Set button HTML
  button.innerHTML = `
    <i class="fas fa-id-card"></i>
    <span>KYC Verification</span>
  `;
  
  // Add inline styles
  button.style.position = 'fixed';
  button.style.top = '20px';
  button.style.right = '20px';
  button.style.zIndex = '99999';
  button.style.display = 'flex';
  button.style.alignItems = 'center';
  button.style.justifyContent = 'center';
  button.style.gap = '8px';
  button.style.background = 'linear-gradient(135deg, #ff9800, #ff5722)';
  button.style.color = 'white';
  button.style.textDecoration = 'none';
  button.style.padding = '10px 20px';
  button.style.borderRadius = '50px';
  button.style.fontWeight = 'bold';
  button.style.boxShadow = '0 4px 15px rgba(255, 87, 34, 0.4)';
  button.style.transition = 'all 0.3s ease';
  
  // Add to document
  document.write('<div id="immediate-kyc-button-container"></div>');
  setTimeout(function() {
    const container = document.getElementById('immediate-kyc-button-container');
    if (container) {
      container.appendChild(button);
    } else {
      document.body.appendChild(button);
    }
  }, 0);
})();
