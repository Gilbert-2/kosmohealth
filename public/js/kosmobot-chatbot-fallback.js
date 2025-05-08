/**
 * KosmoBot Chatbot Fallback Implementation
 * This script provides a fallback for browsers that might have issues with ES modules
 */

(function() {
  // Check if the main chatbot script has loaded
  function checkChatbotLoaded() {
    if (typeof Chatbot !== 'undefined') {
      console.log('Chatbot already loaded, no need for fallback');
      return true;
    }
    return false;
  }

  // Load the Flowise chatbot script using a traditional script tag
  function loadChatbotScriptFallback() {
    if (checkChatbotLoaded()) return;
    
    console.log('Loading chatbot using fallback method');
    
    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/flowise-embed/dist/web.js';
    script.onload = function() {
      console.log('Chatbot script loaded via fallback');
      initChatbotFallback();
    };
    document.head.appendChild(script);
  }

  // Initialize the chatbot
  function initChatbotFallback() {
    if (typeof Chatbot === 'undefined') {
      console.error('Chatbot library still not available after fallback');
      return;
    }
    
    console.log('Initializing chatbot via fallback');
    
    Chatbot.init({
      chatflowid: "55f6b7b7-bc93-48b0-b279-32fffbc5adb8",
      apiHost: "https://davidai.kcsoft.dev",
      theme: {
        button: {
          backgroundColor: '#d25465',
          right: 20,
          bottom: 20,
          size: 48,
          iconColor: 'white',
          customIconSrc: window.location.origin + '/storage/favicon/duEqbCaa90Tu6przcXAJHA4TBxEK4zGuu0XxhYLp.png',
        },
        chatWindow: {
          title: 'KosmoBot',
          titleAvatarSrc: window.location.origin + '/storage/favicon/duEqbCaa90Tu6przcXAJHA4TBxEK4zGuu0XxhYLp.png',
          welcomeMessage: 'Mwiriwe , Ubu butumwa nubwanyu',
          backgroundColor: '#ffffff',
          height: 500,
          width: 400,
          fontSize: 16,
          botMessage: {
            backgroundColor: '#f7f8ff',
            textColor: '#303235',
            showAvatar: true,
            avatarSrc: 'https://kosmotive.rw/upsuxooj/2024/12/Kosmotive-icon2-300x300.png'
          },
          userMessage: {
            backgroundColor: '#d25465',
            textColor: '#ffffff',
            showAvatar: true,
            avatarSrc: 'https://kosmotive.rw/upsuxooj/2024/12/Kosmotive-icon2-300x300.png'
          },
          textInput: {
            placeholder: 'Andika Ikibazo',
            backgroundColor: '#ffffff',
            textColor: '#303235',
            sendButtonColor: '#d25465',
          }
        }
      }
    });
  }

  // Wait a bit to see if the main script loads, then try the fallback
  setTimeout(function() {
    if (!checkChatbotLoaded()) {
      loadChatbotScriptFallback();
    }
  }, 3000); // Wait 3 seconds before trying fallback
})();
