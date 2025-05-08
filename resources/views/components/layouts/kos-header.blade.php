@props(['activePage' => 'home'])

<!-- ======= KosmoHealth Header ======= -->
<header class="flo-header">
  <div class="container">
    <!-- Logo -->
    <div class="flo-logo">
      <a href="/">
        <img src="{{config('config.assets.logo')}}" alt="KosmoHealth - AI-Powered Healthcare Communication">
      </a>
    </div>

    <!-- Navigation -->
    <nav class="flo-nav">
      <!-- Mobile Menu Toggle -->
      <button class="flo-menu-toggle">
        <span></span>
        <span></span>
        <span></span>
      </button>

      <!-- Menu Items -->
      <ul class="flo-nav-menu">
        <!-- Mobile Close Button -->
        <li class="flo-nav-close d-lg-none">
          <button type="button" class="flo-nav-close-btn">
            <i class="fas fa-times"></i>
          </button>
        </li>
        <li>
          <a href="/" class="{{ $activePage == 'home' ? 'active' : '' }}">Home</a>
        </li>
        <!-- About Us Link -->
        <li>
          <a href="/about" class="{{ $activePage == 'about' ? 'active' : '' }}">About Us</a>
        </li>
        <!-- Resources Dropdown -->
        <li class="flo-dropdown">
          <a href="#">Resources</a>
          <ul class="flo-dropdown-menu">
            @if(config('config.website.enable_faq_page'))
              <li><a href="/faq">FAQs</a></li>
            @endif
            @if(config('config.website.enable_events_page'))
              <li><a href="/events">Events</a></li>
            @endif
            <li><a href="/blog">Health Articles</a></li>
            <li><a href="/help">Help Center</a></li>
          </ul>
        </li>

        @if(config('config.website.enable_contact_page'))
          <li>
            <a href="/contact" class="{{ $activePage == 'contact' ? 'active' : '' }}">Contact</a>
          </li>
        @endif

        
      </ul>
    </nav>

    <!-- Action Buttons -->
    <div class="flo-action-buttons">
      @if (\Auth::check())
        <a href="/app/panel/dashboard" class="flo-btn flo-btn-primary">Dashboard</a>
      @else
        <a href="/app/login" class="flo-btn flo-btn-outline">Log in</a>
        <a href="/app/register" class="flo-btn flo-btn-primary">Sign up free</a>
      @endif
    </div>
  </div>
</header><!-- End KosmoHealth Header -->
