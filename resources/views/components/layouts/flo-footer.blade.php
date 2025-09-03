@props(['darkMode' => false, 'compact' => false])

@php
    // Security: Sanitize and validate configuration values
    $orgName = htmlspecialchars(config('config.basic.org_name', 'KosmoHealth'), ENT_QUOTES, 'UTF-8');
    $logoPath = filter_var(config('config.assets.logo', '/images/icons/icon-192x192.png'), FILTER_SANITIZE_URL);
    $currentYear = date('Y');
    
    // Privacy-focused social links - only show if explicitly configured
    $socialLinks = [
        'twitter' => config('config.social.twitter_link'),
        'facebook' => config('config.social.facebook_link'),
        'linkedin' => config('config.social.linkedin_link'),
        'instagram' => config('config.social.instagram_link'),
        'youtube' => config('config.social.youtube_link')
    ];
    
    // Filter out empty social links for security
    $socialLinks = array_filter($socialLinks, function($link) {
        return !empty($link) && filter_var($link, FILTER_VALIDATE_URL);
    });
    
    // Helper function to safely get routes
    function safeRoute($routeName, $fallback = '#') {
        try {
            return Route::has($routeName) ? route($routeName) : $fallback;
        } catch (\Exception $e) {
            return $fallback;
        }
    }
@endphp

<!-- ======= Modern Flo Footer ======= -->
<footer 
    class="flo-footer {{ $darkMode ? 'flo-footer--dark' : '' }} {{ $compact ? 'flo-footer--compact' : '' }}"
    role="contentinfo"
    aria-label="Site Footer"
>
    <div class="container">
        @if(!$compact)
            <!-- Main Footer Content -->
            <div class="flo-footer__main">
                <div class="row g-4">
                    <!-- Brand Section -->
                    <div class="col-lg-4 col-md-6">
                        <div class="flo-footer__brand">
                            <div class="flo-footer__logo">
                                <img 
                                    src="{{ $logoPath }}" 
                                    alt="{{ $orgName }} Logo"
                                    class="img-fluid"
                                    loading="lazy"
                                    width="150"
                                    height="60"
                                >
                            </div>
                            <p class="flo-footer__description">
                                {{ config('config.basic.seo_desc', 
                                    'AI-powered healthcare communication platform with advanced emotion recognition, 
                                    privacy-focused face blurring, and comprehensive multilingual support for 
                                    secure patient-provider interactions.') }}
                            </p>
                            
                            @if(!empty($socialLinks))
                                <div class="flo-footer__social">
                                    <h5 class="flo-footer__social-title visually-hidden">Follow Us</h5>
                                    <div class="flo-social-links" role="list">
                                        @foreach($socialLinks as $platform => $url)
                                            <a 
                                                href="{{ $url }}" 
                                                class="flo-social-links__item flo-social-links__item--{{ $platform }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                aria-label="Follow us on {{ ucfirst($platform) }}"
                                                role="listitem"
                                            >
                                                <i class="fab fa-{{ $platform === 'facebook' ? 'facebook-f' : ($platform === 'linkedin' ? 'linkedin-in' : $platform) }}" aria-hidden="true"></i>
                                                <span class="visually-hidden">{{ ucfirst($platform) }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Healthcare Features -->
                    <div class="col-lg-2 col-md-6">
                        <div class="flo-footer__section">
                            <h4 class="flo-footer__title">Healthcare Features</h4>
                            <nav class="flo-footer__nav" aria-label="Healthcare features navigation">
                                <ul class="flo-footer__links">
                                    <li><a href="#features-emotion" class="flo-footer__link">AI Emotion Recognition</a></li>
                                    <li><a href="#features-privacy" class="flo-footer__link">Privacy Face Blurring</a></li>
                                    <li><a href="#features-chatbot" class="flo-footer__link">Multilingual AI Chatbot</a></li>
                                    <li><a href="#features-meetings" class="flo-footer__link">Secure Video Meetings</a></li>
                                    <li><a href="#features-tracking" class="flo-footer__link">Period Tracking</a></li>
                                </ul>
                            </nav>
                        </div>
                    </div>

                    <!-- Professional Services -->
                    <div class="col-lg-2 col-md-6">
                        <div class="flo-footer__section">
                            <h4 class="flo-footer__title">For Professionals</h4>
                            <nav class="flo-footer__nav" aria-label="Professional services navigation">
                                <ul class="flo-footer__links">
                                    <li><a href="#professionals-support" class="flo-footer__link">Emotion-Triggered Support</a></li>
                                    <li><a href="#professionals-management" class="flo-footer__link">Patient Management</a></li>
                                    <li><a href="#professionals-kyc" class="flo-footer__link">KYC Verification</a></li>
                                    <li><a href="#professionals-chat" class="flo-footer__link">HIPAA-Compliant Chat</a></li>
                                </ul>
                            </nav>
                        </div>
                    </div>

                    <!-- Company -->
                    <div class="col-lg-2 col-md-6">
                        <div class="flo-footer__section">
                            <h4 class="flo-footer__title">Company</h4>
                            <nav class="flo-footer__nav" aria-label="Company navigation">
                                <ul class="flo-footer__links">
                                    <li><a href="/about" class="flo-footer__link">About Us</a></li>
                                    <li><a href="#mission" class="flo-footer__link">Our Mission</a></li>
                                    <li><a href="#team" class="flo-footer__link">Team</a></li>
                                    <li><a href="#careers" class="flo-footer__link">Careers</a></li>
                                </ul>
                            </nav>
                        </div>
                    </div>

                    <!-- Support -->
                    <div class="col-lg-2 col-md-6">
                        <div class="flo-footer__section">
                            <h4 class="flo-footer__title">Support</h4>
                            <nav class="flo-footer__nav" aria-label="Support navigation">
                                <ul class="flo-footer__links">
                                    <li><a href="#help" class="flo-footer__link">Help Center</a></li>
                                    <li><a href="/faq" class="flo-footer__link">FAQs</a></li>
                                    <li><a href="/contact" class="flo-footer__link">Contact Us</a></li>
                                    <li><a href="#api-docs" class="flo-footer__link">API Documentation</a></li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Footer Bottom -->
        <div class="flo-footer__bottom">
            <div class="row align-items-center">
                <div class="col-lg-6 col-md-12">
                    <div class="flo-footer__copyright">
                        <p class="mb-0">
                            &copy; {{ $currentYear }} 
                            <strong>{{ $orgName }}</strong>. 
                            All Rights Reserved
                            @if(config('app.env') === 'production')
                                <span class="flo-footer__security-badge" title="SSL Secured">
                                    <i class="fas fa-lock" aria-hidden="true"></i>
                                    <span class="visually-hidden">SSL Secured</span>
                                </span>
                            @endif
                        </p>
                    </div>
                </div>
                <div class="col-lg-6 col-md-12">
                    <nav class="flo-footer__legal" aria-label="Legal navigation">
                        <ul class="flo-footer__legal-links">
                            <li><a href="/pages/terms" class="flo-footer__legal-link">Terms of Service</a></li>
                            <li><a href="/pages/privacy" class="flo-footer__legal-link">Privacy Policy</a></li>
                            <li><a href="/pages/cookies" class="flo-footer__legal-link">Cookie Policy</a></li>
                            @if(config('config.compliance.hipaa_enabled'))
                                <li><a href="/pages/hipaa" class="flo-footer__legal-link">HIPAA Compliance</a></li>
                            @endif
                        </ul>
                    </nav>
                </div>
            </div>
        </div>

        @if(config('app.env') === 'production')
            <!-- Security and Performance Badges -->
            <div class="flo-footer__badges d-none d-lg-block">
                <div class="row justify-content-center">
                    <div class="col-auto">
                        <div class="flo-footer__badge-group">
                            <span class="flo-footer__badge" title="GDPR Compliant">
                                <i class="fas fa-shield-alt" aria-hidden="true"></i>
                                GDPR
                            </span>
                            <span class="flo-footer__badge" title="SOC 2 Compliant">
                                <i class="fas fa-certificate" aria-hidden="true"></i>
                                SOC 2
                            </span>
                            @if(config('config.compliance.hipaa_enabled'))
                                <span class="flo-footer__badge" title="HIPAA Compliant">
                                    <i class="fas fa-user-shield" aria-hidden="true"></i>
                                    HIPAA
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Scroll to Top Button -->
    <button 
        class="flo-footer__scroll-top"
        id="scrollToTop"
        aria-label="Scroll to top"
        title="Scroll to top"
        type="button"
    >
        <i class="fas fa-chevron-up" aria-hidden="true"></i>
    </button>
</footer><!-- End Modern Flo Footer -->

@push('styles')
<style>
/* Modern Flo Footer Styles */
.flo-footer {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    color: #ffffff;
    padding: 4rem 0 2rem;
    position: relative;
    overflow: hidden;
}

.flo-footer::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 1px;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
}

.flo-footer--dark {
    background: linear-gradient(135deg, #0d1117 0%, #161b22 50%, #21262d 100%);
}

.flo-footer--compact .flo-footer__main {
    display: none;
}

.flo-footer__main {
    margin-bottom: 3rem;
}

.flo-footer__brand {
    padding-right: 2rem;
}

.flo-footer__logo img {
    max-width: 150px;
    height: auto;
    margin-bottom: 1rem;
    filter: brightness(1.1);
}

.flo-footer__description {
    color: rgba(255, 255, 255, 0.8);
    line-height: 1.6;
    margin-bottom: 1.5rem;
    font-size: 0.95rem;
}

.flo-footer__title {
    color: #ffffff;
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    position: relative;
    padding-bottom: 0.5rem;
}

.flo-footer__title::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 30px;
    height: 2px;
    background: linear-gradient(90deg, #4285f4, #34a853);
    border-radius: 1px;
}

.flo-footer__links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.flo-footer__links li {
    margin-bottom: 0.75rem;
}

.flo-footer__link {
    color: rgba(255, 255, 255, 0.75);
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    position: relative;
    padding-left: 1rem;
}

.flo-footer__link::before {
    content: '▸';
    position: absolute;
    left: 0;
    color: #4285f4;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.flo-footer__link:hover {
    color: #ffffff;
    padding-left: 1.25rem;
    text-decoration: none;
}

.flo-footer__link:hover::before {
    opacity: 1;
}

.flo-social-links {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
}

.flo-social-links__item {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    color: rgba(255, 255, 255, 0.8);
    text-decoration: none;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.flo-social-links__item:hover {
    background: rgba(255, 255, 255, 0.2);
    color: #ffffff;
    transform: translateY(-2px);
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.flo-social-links__item--twitter:hover { border-color: #1da1f2; }
.flo-social-links__item--facebook:hover { border-color: #4267b2; }
.flo-social-links__item--linkedin:hover { border-color: #0077b5; }
.flo-social-links__item--instagram:hover { border-color: #e4405f; }
.flo-social-links__item--youtube:hover { border-color: #ff0000; }

.flo-footer__bottom {
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding-top: 2rem;
    margin-top: 2rem;
}

.flo-footer__copyright {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.9rem;
}

.flo-footer__security-badge {
    color: #34a853;
    margin-left: 1rem;
}

.flo-footer__legal-links {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 1.5rem;
}

.flo-footer__legal-link {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    font-size: 0.85rem;
    transition: color 0.3s ease;
}

.flo-footer__legal-link:hover {
    color: #ffffff;
    text-decoration: none;
}

.flo-footer__badges {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.05);
}

.flo-footer__badge-group {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.flo-footer__badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 20px;
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.8);
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.flo-footer__scroll-top {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #4285f4, #34a853);
    border: none;
    border-radius: 50%;
    color: white;
    cursor: pointer;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    z-index: 1000;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.flo-footer__scroll-top.show {
    opacity: 1;
    visibility: visible;
}

.flo-footer__scroll-top:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
}

/* Responsive Design */
@media (max-width: 768px) {
    .flo-footer {
        padding: 3rem 0 1.5rem;
    }
    
    .flo-footer__brand {
        padding-right: 0;
        margin-bottom: 2rem;
    }
    
    .flo-footer__legal-links {
        justify-content: center;
        margin-top: 1rem;
    }
    
    .flo-footer__scroll-top {
        bottom: 1rem;
        right: 1rem;
        width: 45px;
        height: 45px;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .flo-footer:not(.flo-footer--dark) {
        background: linear-gradient(135deg, #0d1117 0%, #161b22 50%, #21262d 100%);
    }
}

/* Accessibility */
.visually-hidden {
    position: absolute !important;
    width: 1px !important;
    height: 1px !important;
    padding: 0 !important;
    margin: -1px !important;
    overflow: hidden !important;
    clip: rect(0, 0, 0, 0) !important;
    white-space: nowrap !important;
    border: 0 !important;
}

@media (prefers-reduced-motion: reduce) {
    .flo-footer *,
    .flo-footer *::before,
    .flo-footer *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Scroll to top functionality with security considerations
    const scrollButton = document.getElementById('scrollToTop');
    
    if (scrollButton) {
        // Show/hide scroll button based on scroll position
        function toggleScrollButton() {
            if (window.pageYOffset > 300) {
                scrollButton.classList.add('show');
            } else {
                scrollButton.classList.remove('show');
            }
        }
        
        // Throttle scroll events for performance
        let ticking = false;
        function handleScroll() {
            if (!ticking) {
                requestAnimationFrame(function() {
                    toggleScrollButton();
                    ticking = false;
                });
                ticking = true;
            }
        }
        
        window.addEventListener('scroll', handleScroll, { passive: true });
        
        // Smooth scroll to top
        scrollButton.addEventListener('click', function(e) {
            e.preventDefault();
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
        
        // Keyboard accessibility
        scrollButton.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    }
    
    // Enhanced link security - add noopener to external links
    const externalLinks = document.querySelectorAll('.flo-footer a[href^="http"]:not([href^="' + window.location.origin + '"])');
    externalLinks.forEach(link => {
        const rel = link.getAttribute('rel') || '';
        if (!rel.includes('noopener')) {
            link.setAttribute('rel', (rel + ' noopener noreferrer').trim());
        }
    });
});
</script>
@endpush