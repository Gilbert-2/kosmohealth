{{--
    Modern Enhanced Landing Page
    
    This template provides a comprehensive modern landing page with:
    - Modern Enhanced Login System Integration
    - Progressive Web App Support
    - Advanced Analytics & Conversion Tracking
    - Accessibility & SEO Optimization
    - Mobile-First Responsive Design
    - Performance Optimization
    - Security & Privacy Compliance
--}}

<x-layouts.master>
    <x-slot name="title">
        {{ $title ?? 'KosmoHealth - Modern Healthcare Platform' }}
    </x-slot>
    
    <x-slot name="meta">
        <!-- SEO Meta Tags -->
        <meta name="description" content="KosmoHealth - Advanced healthcare platform with modern security, AI-powered assistance, and comprehensive health management tools.">
        <meta name="keywords" content="healthcare, telemedicine, AI health assistant, secure platform, KosmoHealth">
        <meta name="author" content="KosmoHealth Team">
        
        <!-- Open Graph -->
        <meta property="og:title" content="KosmoHealth - Modern Healthcare Platform">
        <meta property="og:description" content="Experience the future of healthcare with our AI-powered platform">
        <meta property="og:image" content="{{ config('config.assets.og_image') ?? config('config.assets.icon_180') }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:type" content="website">
        
        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="KosmoHealth - Modern Healthcare Platform">
        <meta name="twitter:description" content="Experience the future of healthcare with our AI-powered platform">
        <meta name="twitter:image" content="{{ config('config.assets.og_image') ?? config('config.assets.icon_180') }}">
        
        <!-- PWA Meta Tags -->
        <meta name="theme-color" content="#d15465">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="KosmoHealth">
        
        <!-- Preconnect to external domains -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="preconnect" href="https://cdn.jsdelivr.net">
        <link rel="preconnect" href="https://davidai.online">
        
        <!-- Prefetch critical resources -->
        <link rel="prefetch" href="/css/modern-landing-page.css">
        <link rel="prefetch" href="/js/modern-landing-manager.js">
        
        <!-- Structured Data -->
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Organization",
            "name": "KosmoHealth",
            "url": "{{ url('/') }}",
            "logo": "{{ config('config.assets.icon_180') }}",
            "description": "Modern healthcare platform with AI-powered assistance",
            "contactPoint": {
                "@type": "ContactPoint",
                "telephone": "+1-234-567-8900",
                "contactType": "customer service"
            },
            "sameAs": [
                "https://www.facebook.com/kosmohealth",
                "https://www.twitter.com/kosmohealth",
                "https://www.linkedin.com/company/kosmohealth"
            ]
        }
        </script>
    </x-slot>
    
    <x-slot name="styles">
        <!-- Critical CSS inline for performance -->
        <style>
            /* Critical above-the-fold styles */
            body { margin: 0; font-family: 'Inter', sans-serif; }
            .modern-header { position: fixed; top: 0; width: 100%; z-index: 1000; background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); }
            .modern-hero { min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #d15465 100%); display: flex; align-items: center; justify-content: center; color: white; text-align: center; }
            .hero-title { font-size: clamp(2.5rem, 5vw, 4rem); font-weight: 700; margin: 0 0 1.5rem; }
            .hero-subtitle { font-size: 1.25rem; margin: 0 0 2rem; opacity: 0.95; }
        </style>
        
        <!-- Load modern landing page CSS -->
        <link rel="stylesheet" href="/css/modern-landing-page.css">
        
        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
        
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </x-slot>

    <!-- Skip Link for Accessibility -->
    <a href="#main" class="skip-link">Skip to main content</a>
    
    <!-- Scroll Progress Indicator -->
    <div class="scroll-progress"></div>

    <!-- Modern Header -->
    <header class="modern-header">
        <div class="container">
            <div class="header-content">
                <!-- Logo -->
                <a href="{{ url('/') }}" class="header-logo">
                    <img src="{{ config('config.assets.icon') }}" alt="KosmoHealth Logo" width="40" height="40">
                    <span>KosmoHealth</span>
                </a>

                <!-- Navigation -->
                <nav class="header-nav">
                    <ul class="nav-menu">
                        <li class="nav-item">
                            <a href="#home" class="nav-link active">Home</a>
                        </li>
                        <li class="nav-item">
                            <a href="#features" class="nav-link">Features</a>
                        </li>
                        <li class="nav-item">
                            <a href="#about" class="nav-link">About</a>
                        </li>
                        <li class="nav-item">
                            <a href="#testimonials" class="nav-link">Testimonials</a>
                        </li>
                        <li class="nav-item">
                            <a href="#contact" class="nav-link">Contact</a>
                        </li>
                    </ul>
                    
                    <!-- Header CTA -->
                    <div class="header-cta">
                        <a href="/app#/auth/login-modern" class="btn-header btn-header-outline">Login</a>
                        <a href="/app#/auth/register" class="btn-header btn-header-primary">Get Started</a>
                    </div>
                </nav>

                <!-- Mobile Menu Toggle -->
                <button class="menu-toggle" aria-label="Toggle navigation menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section id="home" class="modern-hero">
        <!-- Background Shapes -->
        <div class="hero-shapes">
            <div class="hero-shape"></div>
            <div class="hero-shape"></div>
            <div class="hero-shape"></div>
            <div class="hero-shape"></div>
        </div>

        <div class="container">
            <div class="hero-content fade-in">
                <h1 class="hero-title">
                    Experience the Future of <span style="color: #fbbf24;">Healthcare</span>
                </h1>
                <p class="hero-subtitle">
                    Advanced healthcare platform with AI-powered assistance, secure telemedicine, 
                    and comprehensive health management tools designed for modern healthcare needs.
                </p>
                
                <div class="hero-actions">
                    <a href="/app#/auth/register" class="hero-btn hero-btn-primary">
                        <i class="fas fa-rocket"></i>
                        Get Started Free
                    </a>
                    <a href="#features" class="hero-btn hero-btn-outline">
                        <i class="fas fa-play"></i>
                        Watch Demo
                    </a>
                </div>
            </div>
        </div>

        <!-- Scroll Indicator -->
        <div class="scroll-indicator">
            <svg viewBox="0 0 24 24">
                <path d="M7.41 8.58L12 13.17l4.59-4.59L18 10l-6 6-6-6 1.41-1.42z"/>
            </svg>
        </div>
    </section>

    <!-- Main Content -->
    <main id="main">
        <!-- Features Section -->
        <section id="features" class="features-section section">
            <div class="container">
                <div class="section-header fade-in">
                    <h2 class="section-title">Powerful Features for Modern Healthcare</h2>
                    <p class="section-subtitle">
                        Comprehensive healthcare solutions with cutting-edge technology and user-centric design
                    </p>
                </div>

                <div class="features-grid">
                    <div class="feature-card slide-in" style="--stagger-delay: 0">
                        <div class="feature-icon">
                            <i class="fas fa-video"></i>
                        </div>
                        <h3 class="feature-title">Secure Video Consultations</h3>
                        <p class="feature-description">
                            Connect with healthcare professionals through encrypted video calls that protect your privacy and ensure HIPAA compliance.
                        </p>
                    </div>

                    <div class="feature-card slide-in" style="--stagger-delay: 1">
                        <div class="feature-icon">
                            <i class="fas fa-robot"></i>
                        </div>
                        <h3 class="feature-title">AI Health Assistant</h3>
                        <p class="feature-description">
                            Get instant health advice and symptom checking with our advanced AI assistant trained on medical knowledge.
                        </p>
                    </div>

                    <div class="feature-card slide-in" style="--stagger-delay: 2">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h3 class="feature-title">Enhanced Security</h3>
                        <p class="feature-description">
                            Multi-factor authentication, biometric login, and end-to-end encryption ensure your health data stays secure.
                        </p>
                    </div>

                    <div class="feature-card slide-in" style="--stagger-delay: 3">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="feature-title">Health Analytics</h3>
                        <p class="feature-description">
                            Track your health metrics, get personalized insights, and monitor your wellness journey with detailed analytics.
                        </p>
                    </div>

                    <div class="feature-card slide-in" style="--stagger-delay: 4">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3 class="feature-title">Mobile-First Design</h3>
                        <p class="feature-description">
                            Access your healthcare anywhere with our responsive design and progressive web app capabilities.
                        </p>
                    </div>

                    <div class="feature-card slide-in" style="--stagger-delay: 5">
                        <div class="feature-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h3 class="feature-title">24/7 Availability</h3>
                        <p class="feature-description">
                            Healthcare support when you need it most with round-the-clock availability and emergency features.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonials Section -->
        <section id="testimonials" class="testimonials-section section">
            <div class="container">
                <div class="section-header fade-in">
                    <h2 class="section-title">What Our Users Say</h2>
                    <p class="section-subtitle">
                        Trusted by thousands of users worldwide for their healthcare needs
                    </p>
                </div>

                <div class="testimonials-container">
                    <div class="testimonial-card bounce-in">
                        <div class="testimonial-content">
                            "KosmoHealth has revolutionized how I manage my health. The AI assistant is incredibly helpful, and the secure video consultations make healthcare accessible from anywhere."
                        </div>
                        <div class="testimonial-author">
                            <img src="/images/avatars/user1.jpg" alt="Sarah Johnson" class="author-avatar">
                            <div class="author-info">
                                <h4>Sarah Johnson</h4>
                                <p>Healthcare Professional</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section section">
            <div class="container">
                <div class="cta-content fade-in">
                    <h2 class="cta-title">Ready to Transform Your Healthcare Experience?</h2>
                    <p class="cta-subtitle">
                        Join thousands of users who trust KosmoHealth for their healthcare needs. 
                        Start your journey towards better health today.
                    </p>
                    
                    <div class="cta-actions">
                        <a href="/app#/auth/register" class="cta-btn cta-btn-white">
                            <i class="fas fa-user-plus"></i>
                            Create Free Account
                        </a>
                        <a href="#contact" class="cta-btn" style="background: transparent; border-color: white; color: white;">
                            <i class="fas fa-phone"></i>
                            Contact Sales
                        </a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Modern Footer -->
    <footer class="modern-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>KosmoHealth</h3>
                    <p>Advanced healthcare platform with AI-powered assistance and secure telemedicine solutions.</p>
                    <div class="social-links">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="LinkedIn"><i class="fab fa-linkedin"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h3>Platform</h3>
                    <ul>
                        <li><a href="#features">Features</a></li>
                        <li><a href="/app#/auth/register">Sign Up</a></li>
                        <li><a href="/app#/auth/login-modern">Login</a></li>
                        <li><a href="#pricing">Pricing</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Company</h3>
                    <ul>
                        <li><a href="/about">About Us</a></li>
                        <li><a href="/contact">Contact</a></li>
                        <li><a href="/careers">Careers</a></li>
                        <li><a href="/blog">Blog</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Legal</h3>
                    <ul>
                        <li><a href="/privacy">Privacy Policy</a></li>
                        <li><a href="/terms">Terms of Service</a></li>
                        <li><a href="/hipaa">HIPAA Compliance</a></li>
                        <li><a href="/security">Security</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; {{ date('Y') }} KosmoHealth. All rights reserved. | Made with ❤️ for better healthcare</p>
            </div>
        </div>
    </footer>

    <!-- Enhanced Chatbot Integration -->
    <x-chatbot.kosmobot />

    <!-- Scripts -->
    <x-slot name="scripts">
        <!-- Critical JavaScript -->
        <script>
            // Critical performance optimizations
            (function() {
                // Preload critical resources
                const preloadResources = [
                    '/js/modern-landing-manager.js',
                    '/js/modern-enhanced-login.js'
                ];
                
                preloadResources.forEach(resource => {
                    const link = document.createElement('link');
                    link.rel = 'preload';
                    link.as = 'script';
                    link.href = resource;
                    document.head.appendChild(link);
                });
                
                // Setup early analytics
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'page_view', {
                        page_title: 'Modern Landing Page',
                        page_location: window.location.href
                    });
                }
            })();
        </script>

        <!-- Modern Landing Manager -->
        <script src="/js/modern-landing-manager.js" defer></script>
        
        <!-- Analytics (if configured) -->
        @if(config('services.google_analytics.id'))
        <!-- Google Analytics -->
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('services.google_analytics.id') }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', '{{ config('services.google_analytics.id') }}', {
                anonymize_ip: true,
                cookie_flags: 'SameSite=Strict;Secure'
            });
        </script>
        @endif
        
        <!-- Service Worker Registration -->
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', function() {
                    navigator.serviceWorker.register('/sw.js')
                        .then(function(registration) {
                            console.log('SW registered: ', registration);
                        })
                        .catch(function(registrationError) {
                            console.log('SW registration failed: ', registrationError);
                        });
                });
            }
        </script>
        
        <!-- Performance Monitoring -->
        <script>
            // Monitor Core Web Vitals
            if ('web-vital' in window) {
                import('https://unpkg.com/web-vitals?module').then(({getCLS, getFID, getFCP, getLCP, getTTFB}) => {
                    getCLS(console.log);
                    getFID(console.log);
                    getFCP(console.log);
                    getLCP(console.log);
                    getTTFB(console.log);
                });
            }
        </script>
    </x-slot>

    <!-- Structured Data for Better SEO -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebPage",
        "name": "KosmoHealth - Modern Healthcare Platform",
        "description": "Advanced healthcare platform with AI-powered assistance",
        "url": "{{ url()->current() }}",
        "mainEntity": {
            "@type": "Organization",
            "name": "KosmoHealth",
            "description": "Modern healthcare platform with AI-powered assistance and secure telemedicine solutions"
        }
    }
    </script>
</x-layouts.master>