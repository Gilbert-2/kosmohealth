@props(['activePage' => 'home'])

  <!-- ======= Flo Health Inspired Header ======= -->
  <header class="flo-header">
    <div class="container">
      <!-- Logo -->
      <div class="flo-logo">
        <a href="/">
          <img src="{{config('config.assets.logo')}}" alt="{{config('config.basic.seo_desc')}}">
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
          <li>
            <a href="/flo" class="{{ $activePage == 'home' ? 'active' : '' }}">Home</a>
          </li>

          <!-- Product Dropdown -->
          <li class="flo-dropdown">
            <a href="#">Product</a>
            <ul class="flo-dropdown-menu">
              <li><a href="/product/features">Features</a></li>
              <li><a href="/product/video-meetings">Video Meetings</a></li>
              <li><a href="/product/chat">Chat</a></li>
              @if(config('config.website.enable_about_page'))
                <li><a href="/about">About Us</a></li>
              @endif
            </ul>
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
              <li><a href="/blog">Blog</a></li>
              <li><a href="/help">Help Center</a></li>
            </ul>
          </li>

          @if(config('config.website.enable_contact_page'))
            <li>
              <a href="/contact" class="{{ $activePage == 'contact' ? 'active' : '' }}">Contact</a>
            </li>
          @endif

          <!-- Link to Flo-inspired design -->
          
        </ul>
      </nav>

      <!-- Action Buttons -->
      <div class="flo-action-buttons">
        @if (\Auth::check())
          <a href="/app/panel/dashboard" class="flo-btn flo-btn-primary">Dashboard</a>
        @else
          <a href="/app/register" class="flo-btn flo-btn-outline">Sign Up</a>
          <a href="/app/login" class="flo-btn flo-btn-primary">Login</a>
        @endif
      </div>
    </div>
  </header><!-- End Flo Health Inspired Header -->
