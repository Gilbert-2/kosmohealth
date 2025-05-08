<x-layouts.master>
    <x-slot name="title">
        Flo Health Inspired Design
    </x-slot>

    <!-- Include Flo-inspired header -->
    <x-layouts.flo-header />

    <!-- Include Flo-inspired hero section -->
    <x-layouts.flo-hero />

    <main id="main">
        <!-- Features Section -->
        <section class="flo-features">
            <div class="container">
                <div class="section-title text-center" data-aos="fade-up">
                    <h2>What can you do with our app?</h2>
                    <p class="text-muted">Our platform offers a comprehensive suite of tools for healthcare communication and management</p>
                </div>

                <div class="row">
                    <!-- Feature 1 -->
                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                        <div class="flo-feature-card">
                            <div class="flo-feature-icon">
                                <i class="fas fa-video"></i>
                            </div>
                            <h3>Secure Video Meetings</h3>
                            <p>Connect with healthcare professionals through encrypted video calls that protect your privacy and data.</p>
                        </div>
                    </div>

                    <!-- Feature 2 -->
                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                        <div class="flo-feature-card">
                            <div class="flo-feature-icon">
                                <i class="fas fa-comments"></i>
                            </div>
                            <h3>Real-time Chat</h3>
                            <p>Communicate instantly with your healthcare team through our secure messaging platform.</p>
                        </div>
                    </div>

                    <!-- Feature 3 -->
                    <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                        <div class="flo-feature-card">
                            <div class="flo-feature-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h3>Health Tracking</h3>
                            <p>Monitor your health metrics over time and share them securely with your healthcare providers.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Testimonials Section -->
        <section class="flo-testimonials">
            <div class="container">
                <div class="section-title text-center" data-aos="fade-up">
                    <h2>What our users say</h2>
                </div>

                <div class="row">
                    <div class="col-lg-8 offset-lg-2">
                        <div class="testimonials-carousel" data-aos="fade-up" data-aos-delay="100">
                            <!-- Testimonial 1 -->
                            <div class="flo-testimonial-item">
                                <p>"This platform has transformed how I connect with my patients. The video quality is excellent, and the health tracking features help me provide better care."</p>
                                <div class="flo-testimonial-author">
                                    <div class="flo-testimonial-avatar">
                                        <img src="/site/img/testimonials/1.jpg" alt="Doctor">
                                    </div>
                                    <div class="flo-testimonial-info">
                                        <h4>Dr. Sarah Johnson</h4>
                                        <span>Cardiologist</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Testimonial 2 -->
                            <div class="flo-testimonial-item">
                                <p>"I love how easy it is to schedule appointments and chat with my doctor. The interface is intuitive, and I feel my data is secure."</p>
                                <div class="flo-testimonial-author">
                                    <div class="flo-testimonial-avatar">
                                        <img src="/site/img/testimonials/2.jpg" alt="Patient">
                                    </div>
                                    <div class="flo-testimonial-info">
                                        <h4>Michael Rodriguez</h4>
                                        <span>Patient</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="flo-cta">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-8" data-aos="fade-right">
                        <h2>Ready to transform your healthcare experience?</h2>
                        <p>Join thousands of healthcare providers and patients who are already using our platform.</p>
                    </div>
                    <div class="col-lg-4 text-center text-lg-right" data-aos="fade-left">
                        <a href="/app/register" class="flo-btn flo-btn-primary">Get Started Today</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <x-layouts.footer />

</x-layouts.master>
