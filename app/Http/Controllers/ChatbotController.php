<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class ChatbotController extends Controller
{
    /**
     * Chatbot configuration
     *
     * @var array
     */
    protected $config = [
        'default' => [
            'chatflowid' => '55f6b7b7-bc93-48b0-b279-32fffbc5adb8',
            'apiHost' => 'https://davidai.kcsoft.dev',
            'theme' => [
                'button' => [
                    'backgroundColor' => '#d25465',
                    'right' => 20,
                    'bottom' => 20,
                    'size' => 48,
                    'dragAndDrop' => true,
                    'iconColor' => 'white',
                    'autoWindowOpen' => [
                        'autoOpen' => true,
                        'openDelay' => 2,
                        'autoOpenOnMobile' => false
                    ]
                ],
                'tooltip' => [
                    'showTooltip' => true,
                    'tooltipMessage' => 'Hi There 👋!',
                    'tooltipBackgroundColor' => 'black',
                    'tooltipTextColor' => 'white',
                    'tooltipFontSize' => 16
                ],
                'customCSS' => '',
                'chatWindow' => [
                    'showTitle' => true,
                    'showAgentMessages' => true,
                    'title' => 'KosmoBot',
                    'welcomeMessage' => 'Mwiriwe , Ubu butumwa nubwanyu',
                    'errorMessage' => 'Habayemo Ikibazo',
                    'backgroundColor' => '#ffffff',
                    'backgroundImage' => 'enter image path or link',
                    'height' => 500,
                    'width' => 400,
                    'fontSize' => 16,
                    'starterPrompts' => [
                        "Menya byinshi kuri Kosmohealth?",
                        "Umeze ute?"
                    ],
                    'starterPromptFontSize' => 15,
                    'clearChatOnReload' => false,
                    'sourceDocsTitle' => 'Sources:',
                    'renderHTML' => true,
                    'botMessage' => [
                        'backgroundColor' => '#f7f8ff',
                        'textColor' => '#303235',
                        'showAvatar' => true,
                        'avatarSrc' => 'https://kosmotive.rw/upsuxooj/2024/12/Kosmotive-icon2-300x300.png'
                    ],
                    'userMessage' => [
                        'backgroundColor' => '#d25465',
                        'textColor' => '#ffffff',
                        'showAvatar' => true,
                        'avatarSrc' => 'https://kosmotive.rw/upsuxooj/2024/12/Kosmotive-icon2-300x300.png'
                    ],
                    'textInput' => [
                        'placeholder' => 'Andika Ikibazo',
                        'backgroundColor' => '#ffffff',
                        'textColor' => '#303235',
                        'sendButtonColor' => '#d25465',
                        'maxChars' => 50,
                        'maxCharsWarningMessage' => 'You exceeded the characters limit. Please input less than 50 characters.',
                        'autoFocus' => true,
                        'sendMessageSound' => true,
                        'sendSoundLocation' => 'send_message.mp3',
                        'receiveMessageSound' => true,
                        'receiveSoundLocation' => 'receive_message.mp3'
                    ],
                    'feedback' => [
                        'color' => '#303235'
                    ],
                    'dateTimeToggle' => [
                        'date' => true,
                        'time' => true
                    ],
                    'footer' => [
                        'textColor' => '#303235',
                        'text' => '',
                        'company' => 'KosmoHealth',
                        'companyLink' => 'https://kosmohealth.com'
                    ]
                ]
            ]
        ],
        'dashboard' => [
            // Dashboard-specific overrides
            'theme' => [
                'button' => [
                    'size' => 50,
                    'bottom' => 30,
                    'right' => 30,
                    'backgroundColor' => '#d25465',
                    'iconColor' => 'white',
                    'dragAndDrop' => true,
                    'autoWindowOpen' => [
                        'autoOpen' => false, // Don't auto-open in dashboard
                        'autoOpenOnMobile' => false
                    ]
                ],
                'chatWindow' => [
                    'height' => 600,
                    'width' => 450,
                    'title' => 'KosmoBot Assistant',
                    'welcomeMessage' => 'Hello! How can I assist you with KosmoHealth today?',
                    'botMessage' => [
                        'backgroundColor' => '#f7f8ff',
                        'textColor' => '#303235',
                        'showAvatar' => true,
                        'avatarSrc' => 'https://kosmotive.rw/upsuxooj/2024/12/Kosmotive-icon2-300x300.png'
                    ]
                ],
                'tooltip' => [
                    'showTooltip' => true,
                    'tooltipMessage' => 'Need help?',
                    'tooltipBackgroundColor' => '#d25465',
                    'tooltipTextColor' => 'white'
                ]
            ]
        ],
        'landing' => [
            // Landing page-specific overrides
            'theme' => [
                'button' => [
                    'size' => 55,
                    'bottom' => 20,
                    'right' => 20,
                    'backgroundColor' => '#d25465',
                    'iconColor' => 'white',
                    'dragAndDrop' => true,
                    'autoWindowOpen' => [
                        'autoOpen' => true, // Auto-open on landing page
                        'openDelay' => 3,
                        'autoOpenOnMobile' => false
                    ]
                ],
                'chatWindow' => [
                    'height' => 550,
                    'width' => 400,
                    'title' => 'Welcome to KosmoHealth',
                    'welcomeMessage' => 'Mwiriwe! I can help you learn more about KosmoHealth services. What would you like to know?',
                    'botMessage' => [
                        'backgroundColor' => '#f7f8ff',
                        'textColor' => '#303235',
                        'showAvatar' => true,
                        'avatarSrc' => 'https://kosmotive.rw/upsuxooj/2024/12/Kosmotive-icon2-300x300.png'
                    ]
                ],
                'tooltip' => [
                    'showTooltip' => true,
                    'tooltipMessage' => 'Hi There 👋! Ask me about KosmoHealth',
                    'tooltipBackgroundColor' => '#d25465',
                    'tooltipTextColor' => 'white'
                ]
            ]
        ]
    ];

    /**
     * Get chatbot configuration for a specific context
     *
     * @param string $context
     * @return array
     */
    public function getConfig($context = 'default')
    {
        $config = $this->config['default'];

        // Merge with context-specific configuration if it exists
        if (isset($this->config[$context])) {
            $config = $this->mergeConfigs($config, $this->config[$context]);
        }

        // Set dynamic values
        $config['theme']['button']['customIconSrc'] = config('config.assets.favicon');
        $config['theme']['chatWindow']['titleAvatarSrc'] = config('config.assets.favicon');

        return $config;
    }

    /**
     * Recursively merge configurations
     *
     * @param array $base
     * @param array $override
     * @return array
     */
    protected function mergeConfigs($base, $override)
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = $this->mergeConfigs($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    /**
     * Render the chatbot for a specific context
     *
     * @param string $context
     * @return \Illuminate\View\View
     */
    public function render($context = 'default')
    {
        $config = $this->getConfig($context);
        return View::make('components.chatbot.kosmobot', ['config' => $config]);
    }

    /**
     * Get the chatbot script as a string
     *
     * @param string $context
     * @return string
     */
    public function getScript($context = 'default')
    {
        $config = $this->getConfig($context);
        return View::make('components.chatbot.kosmobot-script', ['config' => $config])->render();
    }
}
