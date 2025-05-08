<template>
  <div class="kosmobot-container">
    <div class="kosmobot-header">
      <h3>Kosmobot</h3>
      <button class="close-button" @click="$emit('close')">
        <i class="fas fa-times"></i>
      </button>
    </div>
    
    <div class="kosmobot-messages" ref="messagesContainer">
      <div v-for="(message, index) in messages" :key="index" 
           :class="['message', message.sender === 'bot' ? 'bot-message' : 'user-message']">
        <div class="message-avatar" v-if="message.sender === 'bot'">
          <i class="fas fa-robot"></i>
        </div>
        <div class="message-content">
          <p>{{ message.text }}</p>
          <span class="message-time">{{ formatTime(message.timestamp) }}</span>
        </div>
      </div>
      
      <div class="typing-indicator" v-if="isTyping">
        <span></span>
        <span></span>
        <span></span>
      </div>
    </div>
    
    <div class="kosmobot-input">
      <input 
        type="text" 
        v-model="userInput" 
        @keyup.enter="sendMessage" 
        placeholder="Ask Kosmobot anything..."
        ref="inputField"
      >
      <button @click="sendMessage" :disabled="!userInput.trim()">
        <i class="fas fa-paper-plane"></i>
      </button>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Kosmobot',
  
  data() {
    return {
      userInput: '',
      messages: [
        {
          sender: 'bot',
          text: 'Hello! I\'m Kosmobot, your health assistant. How can I help you today?',
          timestamp: new Date()
        }
      ],
      isTyping: false
    };
  },
  
  mounted() {
    this.$refs.inputField.focus();
    this.scrollToBottom();
  },
  
  updated() {
    this.scrollToBottom();
  },
  
  methods: {
    sendMessage() {
      const userMessage = this.userInput.trim();
      if (!userMessage) return;
      
      // Add user message
      this.messages.push({
        sender: 'user',
        text: userMessage,
        timestamp: new Date()
      });
      
      // Clear input
      this.userInput = '';
      
      // Show typing indicator
      this.isTyping = true;
      
      // Simulate bot response after a delay
      setTimeout(() => {
        this.isTyping = false;
        
        // Add bot response
        this.messages.push({
          sender: 'bot',
          text: this.generateBotResponse(userMessage),
          timestamp: new Date()
        });
      }, 1000 + Math.random() * 1000); // Random delay between 1-2 seconds
    },
    
    generateBotResponse(userMessage) {
      // Simple response logic - in a real app, this would call an API
      const userMessageLower = userMessage.toLowerCase();
      
      if (userMessageLower.includes('hello') || userMessageLower.includes('hi')) {
        return 'Hello there! How can I assist you with your health today?';
      } else if (userMessageLower.includes('period') || userMessageLower.includes('cycle')) {
        return 'I can help you track your menstrual cycle. Would you like to use our period calculator?';
      } else if (userMessageLower.includes('appointment') || userMessageLower.includes('doctor')) {
        return 'You can schedule an appointment with a doctor through our app. Would you like me to help you with that?';
      } else if (userMessageLower.includes('symptom') || userMessageLower.includes('pain')) {
        return 'I\'m sorry to hear you\'re not feeling well. Can you tell me more about your symptoms so I can provide better guidance?';
      } else if (userMessageLower.includes('thank')) {
        return 'You\'re welcome! Is there anything else I can help you with?';
      } else {
        return 'I\'m still learning about that topic. Would you like me to connect you with a healthcare professional who can help?';
      }
    },
    
    formatTime(timestamp) {
      const date = new Date(timestamp);
      return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    },
    
    scrollToBottom() {
      if (this.$refs.messagesContainer) {
        this.$nextTick(() => {
          this.$refs.messagesContainer.scrollTop = this.$refs.messagesContainer.scrollHeight;
        });
      }
    }
  }
};
</script>

<style scoped>
.kosmobot-container {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: #f8f9fa;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

.kosmobot-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px 20px;
  background: linear-gradient(135deg, #2196f3, #1565c0);
  color: white;
}

.kosmobot-header h3 {
  margin: 0;
  font-size: 18px;
  font-weight: 600;
}

.close-button {
  background: none;
  border: none;
  color: white;
  font-size: 18px;
  cursor: pointer;
  opacity: 0.8;
  transition: opacity 0.2s;
}

.close-button:hover {
  opacity: 1;
}

.kosmobot-messages {
  flex: 1;
  padding: 20px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.message {
  display: flex;
  max-width: 80%;
}

.bot-message {
  align-self: flex-start;
}

.user-message {
  align-self: flex-end;
  flex-direction: row-reverse;
}

.message-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #1565c0;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 10px;
}

.message-content {
  background: white;
  padding: 12px 15px;
  border-radius: 18px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
  position: relative;
}

.bot-message .message-content {
  border-top-left-radius: 0;
  background: white;
}

.user-message .message-content {
  border-top-right-radius: 0;
  background: #e3f2fd;
  text-align: right;
}

.message-content p {
  margin: 0 0 5px 0;
  line-height: 1.4;
}

.message-time {
  font-size: 11px;
  color: #999;
  display: block;
}

.typing-indicator {
  display: flex;
  align-items: center;
  align-self: flex-start;
  background: #e0e0e0;
  padding: 12px 15px;
  border-radius: 18px;
  margin-top: 5px;
}

.typing-indicator span {
  height: 8px;
  width: 8px;
  background: #999;
  border-radius: 50%;
  display: inline-block;
  margin: 0 2px;
  animation: bounce 1.3s linear infinite;
}

.typing-indicator span:nth-child(2) {
  animation-delay: 0.15s;
}

.typing-indicator span:nth-child(3) {
  animation-delay: 0.3s;
}

@keyframes bounce {
  0%, 60%, 100% {
    transform: translateY(0);
  }
  30% {
    transform: translateY(-4px);
  }
}

.kosmobot-input {
  display: flex;
  padding: 15px;
  background: white;
  border-top: 1px solid #eee;
}

.kosmobot-input input {
  flex: 1;
  padding: 12px 15px;
  border: 1px solid #ddd;
  border-radius: 25px;
  outline: none;
  font-size: 14px;
  transition: border-color 0.3s;
}

.kosmobot-input input:focus {
  border-color: #2196f3;
}

.kosmobot-input button {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #2196f3;
  color: white;
  border: none;
  margin-left: 10px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.3s;
}

.kosmobot-input button:hover {
  background: #1565c0;
}

.kosmobot-input button:disabled {
  background: #ccc;
  cursor: not-allowed;
}
</style>
