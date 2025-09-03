{{--
    KosmoBot Chatbot Component
    This component renders the KosmoBot chatbot with the provided configuration.

    Usage:
    <x-chatbot.kosmobot />
--}}

@props(['context' => 'default'])

<style>
   
    /* Basic animations */
    @keyframes chatButtonPulse {
        0% { box-shadow: 0 4px 12px rgba(59, 129, 246, 0.4); }
        50% { box-shadow: 0 4px 20px rgba(59, 129, 246, 0.65); }
        100% { box-shadow: 0 4px 12px rgba(59, 129, 246, 0.4); }
    }

    @keyframes chatWindowFadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes tooltipFadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<script type="module">
    import Chatbot from "https://cdn.jsdelivr.net/npm/flowise-embed/dist/web.js"
    Chatbot.init({
        chatflowid: "86137998-e419-4680-a762-8398cd426b0d",
        apiHost: "https://davidai.online",
        chatflowConfig: {
            /* Chatflow Config */
        },
        observersConfig: {
            /* Observers Config */
        },
        theme: {
            button: {
                backgroundColor: '#12a399',
                right: 20,
                bottom: 20,
                size: 48,
                dragAndDrop: true,
                iconColor: 'white',
                customIconSrc: '{{ config('config.assets.icon_180') ?? config('config.assets.icon') }}',
                autoWindowOpen: {
                    autoOpen: true,
                    openDelay: 2,
                    autoOpenOnMobile: false
                }
            },
            tooltip: {
                showTooltip: true,
                tooltipMessage: 'Ask /Baza 👋! KomsoHealth',
                tooltipBackgroundColor: 'black',
                tooltipTextColor: 'white',
                tooltipFontSize: 16
            },
           
            customCSS: ``,
            chatWindow: {
                showTitle: true,
                showAgentMessages: true,
                title: 'Kosmohealth Bot',
                titleAvatarSrc: '{{ config('config.assets.icon_180') ?? config('config.assets.icon') }}',
                welcomeMessage: 'Urakaza neza Kuri Kosmohealth.Urubuga rugufasha kumenya amakuru kubijyanye:',
                errorMessage: 'This is a custom error message',
                backgroundColor: '#ffffff',
                backgroundImage: 'enter image path or link',
                height: 600,
                width: 400,
                fontSize: 16,
                starterPrompts: [
                    "Baza amakuru y'ubuzima bw'umwana?",
                    "Menya amakuru ku mihango"
                ],
                starterPromptFontSize: 15,
                clearChatOnReload: false,
                sourceDocsTitle: 'Sources:',
                renderHTML: true,
                botMessage: {
                    backgroundColor: '#f7f8ff',
                    textColor: '#303235',
                    showAvatar: true,
                    avatarSrc: '{{ config('config.assets.icon_180') ?? config('config.assets.icon') }}'
                },
                userMessage: {
                    backgroundColor: '#12a399',
                    textColor: '#ffffff',
                    showAvatar: true,
                    avatarSrc: '{{ config('config.assets.icon_180') ?? config('config.assets.icon') }}'
                },
                textInput: {
                    placeholder: 'Type your question',
                    backgroundColor: '#ffffff',
                    textColor: '#303235',
                    sendButtonColor: '#12a399',
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
                    
                }
            }
        }
    })
</script>

<!-- Fallback script for browsers that might have issues with ES modules -->
<script>
    (function() {
        // Check if the main chatbot script has loaded after 3 seconds
        setTimeout(function() {
            if (typeof Chatbot === 'undefined') {
                console.log('Loading chatbot using fallback method');

                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/flowise-embed/dist/web.js';
                script.onload = function() {
                    console.log('Chatbot script loaded via fallback');

                    // Initialize with the same configuration
                    Chatbot.init({
                        chatflowid: "55f6b7b7-bc93-48b0-b279-32fffbc5adb8",
                        apiHost: "https://davidai.kcsoft.dev",
                        theme: {
                            button: {
                                backgroundColor: '#12a399',
                                right: 20,
                                bottom: 20,
                                size: 48,
                                dragAndDrop: true,
                                iconColor: 'white'
                            },
                            tooltip: {
                                showTooltip: true,
                                tooltipMessage: 'Hi There 👋!',
                                tooltipBackgroundColor: 'black',
                                tooltipTextColor: 'white',
                                tooltipFontSize: 16
                            },
                            chatWindow: {
                                title: 'Kosmo Bot',
                                welcomeMessage: 'Waba ufite ikibazo / Ask Question',
                                backgroundColor: '#ffffff',
                                height: 600,
                                width: 400,
                                fontSize: 16
                            }
                        }
                    });
                };
                document.head.appendChild(script);
            }
        }, 3000); // Wait 3 seconds before trying fallback
    })();
</script>
