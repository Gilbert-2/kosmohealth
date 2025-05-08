/**
 * Translation Fix
 * This script fixes missing translations in the application
 */

(function() {
  // Wait for the window to load
  window.addEventListener('load', function() {
    // Check if the global translation function exists
    if (typeof window.$t === 'function') {
      // Store the original translation function
      const originalTranslate = window.$t;
      
      // Override the translation function
      window.$t = function(key, replacements) {
        // Get the original translation
        let translation = originalTranslate(key, replacements);
        
        // Fix undefined translations
        if (translation === 'undefined' || translation === undefined) {
          // Map common keys to default values
          const defaultTranslations = {
            'auth.login.props.email_username': 'Email or Username',
            'auth.login.props.password': 'Password',
            'auth.login.props.remember_me': 'Remember Me',
            'auth.login.forgot_password': 'Forgot Password?',
            'auth.login.login': 'Login',
            'auth.login.welcome_back': 'Welcome back!',
            'auth.login.page_title': 'Login',
            'auth.login.no_account': 'Don\'t have an account?',
            'auth.register.register_here': 'Register here',
            'auth.register.props.name': 'Full Name',
            'auth.register.props.email': 'Email Address',
            'auth.register.props.username': 'Username',
            'auth.register.props.password': 'Password',
            'auth.register.props.password_confirmation': 'Confirm Password',
            'auth.register.register': 'Register',
            'auth.register.page_title': 'Create Account'
          };
          
          // Return default translation if available
          if (defaultTranslations[key]) {
            return defaultTranslations[key];
          }
          
          // Try to generate a reasonable default based on the key
          const lastPart = key.split('.').pop();
          if (lastPart) {
            // Convert camelCase or snake_case to Title Case with spaces
            return lastPart
              .replace(/_/g, ' ')
              .replace(/([A-Z])/g, ' $1')
              .replace(/^./, str => str.toUpperCase());
          }
        }
        
        return translation;
      };
      
      console.log('Translation fix applied');
    }
  });
})();
