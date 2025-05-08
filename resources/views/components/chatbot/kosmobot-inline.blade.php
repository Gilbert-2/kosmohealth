<script type="module">
    import Chatbot from "https://cdn.jsdelivr.net/npm/flowise-embed/dist/web.js"
    Chatbot.init({
        chatflowid: "55f6b7b7-bc93-48b0-b279-32fffbc5adb8",
        apiHost: "https://davidai.kcsoft.dev",
        chatflowConfig: {
            /* Chatflow Config */
        },
        observersConfig: {
            /* Observers Config */
        },
        theme: {
            button: {
                backgroundColor: '#d25465',
                right: 20,
                bottom: 20,
                size: 48,
                dragAndDrop: true,
                iconColor: 'white',
                customIconSrc: '{{ config('config.assets.favicon') }}',
                autoWindowOpen: {
                    autoOpen: true,
                    openDelay: 2,
                    autoOpenOnMobile: false
                }
            },
            tooltip: {
                showTooltip: true,
                tooltipMessage: 'Hi There 👋!',
                tooltipBackgroundColor: 'black',
                tooltipTextColor: 'white',
                tooltipFontSize: 16
            },
           
            customCSS: ``,
            chatWindow: {
                showTitle: true,
                showAgentMessages: true,
                title: 'KosmoBot',
                titleAvatarSrc: '{{ config('config.assets.favicon') }}',
                welcomeMessage: 'Mwiriwe , Ubu butumwa nubwanyu',
                errorMessage: 'Habayemo Ikibazo',
                backgroundColor: '#ffffff',
                backgroundImage: 'enter image path or link',
                height: 500,
                width: 400,
                fontSize: 16,
                starterPrompts: [
                    "Menya byinshi kuri Kosmohealth?",
                    "Umeze ute?"
                ],
                starterPromptFontSize: 15,
                clearChatOnReload: false,
                sourceDocsTitle: 'Sources:',
                renderHTML: true,
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
                    maxChars: 50,
                    maxCharsWarningMessage: 'You exceeded the characters limit. Please input less than 50 characters.',
                    autoFocus: true,
                    sendMessageSound: true,
                    sendSoundLocation: 'send_message.mp3',
                    receiveMessageSound: true,
                    receiveSoundLocation: 'receive_message.mp3'
                },
                feedback: {
                    color: '#303235'
                },
                dateTimeToggle: {
                    date: true,
                    time: true
                },
                footer: {
                    textColor: '#303235',
                    text: 'Powered by',
                    company: 'Kosmotive', 
                    companyLink: 'https://kosmotive.rw' 
                }
            }
        }
    })
</script>
