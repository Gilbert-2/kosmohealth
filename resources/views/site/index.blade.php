<x-layouts.master>
    <x-slot name="title">
        {{$title ?? 'Home' }}
    </x-slot>

    <x-layouts.kos-header />
    <x-layouts.kos-hero />

    <main id="main">
        <!-- Features Section -->
        <x-pages.kos-features />

        <!-- Benefits Section -->
        <x-pages.kos-benefits />

        <!-- Testimonials Section -->
        <x-pages.kos-testimonials />

        <!-- Download Section -->
        <x-pages.kos-download />

        <!-- Project Section -->
        <x-pages.kos-project />

        <!-- Articles Section -->
        <x-pages.kos-articles />

        <!-- CTA Section -->
        <x-pages.kos-cta />
    </main><!-- End #main -->

    <x-layouts.kos-footer />

    <!-- Include the inline chatbot component -->
    <x-chatbot.kosmobot-inline />

</x-layouts.master>