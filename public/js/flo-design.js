/**
 * Flo Design JavaScript
 * This script handles the functionality for the Flo Health-inspired design
 */

(function() {
  // DOM Elements
  const header = document.querySelector('.flo-header');
  const menuToggle = document.querySelector('.flo-menu-toggle');
  const navMenu = document.querySelector('.flo-nav-menu');
  const navCloseBtn = document.querySelector('.flo-nav-close-btn');
  const dropdowns = document.querySelectorAll('.flo-dropdown');
  const featureNavItems = document.querySelectorAll('.flo-features-nav li');
  const featureItems = document.querySelectorAll('.flo-feature-item');
  const featureScreens = document.querySelectorAll('.flo-phone-screen img');

  // Handle scroll events for header
  function handleScroll() {
    if (window.scrollY > 50) {
      header.classList.add('scrolled');
    } else {
      header.classList.remove('scrolled');
    }
  }

  // Toggle mobile menu
  function toggleMenu() {
    menuToggle.classList.toggle('active');
    navMenu.classList.toggle('active');
  }

  // Handle dropdown menus on mobile
  function setupDropdowns() {
    dropdowns.forEach(dropdown => {
      // Create dropdown toggle for mobile
      const link = dropdown.querySelector('a');
      const menu = dropdown.querySelector('.flo-dropdown-menu');

      // Remove any existing event listeners (to prevent duplicates)
      link.removeEventListener('click', handleDropdownClick);

      // Add event listener based on screen size
      if (window.innerWidth < 992) {
        link.addEventListener('click', handleDropdownClick);
      }
    });
  }

  // Handle dropdown click event
  function handleDropdownClick(e) {
    e.preventDefault();
    const dropdown = this.closest('.flo-dropdown');

    // Close all other dropdowns
    dropdowns.forEach(item => {
      if (item !== dropdown && item.classList.contains('active')) {
        item.classList.remove('active');
      }
    });

    // Toggle current dropdown
    dropdown.classList.toggle('active');
  }

  // Handle feature tabs
  function setupFeatureTabs() {
    if (!featureNavItems.length) return;

    featureNavItems.forEach(item => {
      item.addEventListener('click', function() {
        const featureId = this.getAttribute('data-feature');

        // Update nav items
        featureNavItems.forEach(navItem => {
          navItem.classList.remove('active');
        });
        this.classList.add('active');

        // Update feature items
        featureItems.forEach(featureItem => {
          featureItem.classList.remove('active');
          if (featureItem.id === featureId) {
            featureItem.classList.add('active');
          }
        });

        // Update phone screen
        featureScreens.forEach(screen => {
          screen.classList.remove('active');
          if (screen.getAttribute('data-feature') === featureId) {
            screen.classList.add('active');
          }
        });
      });
    });
  }

  // Initialize testimonials carousel
  function initTestimonialsCarousel() {
    const carousel = document.querySelector('.testimonials-carousel');
    if (carousel && typeof $.fn.slick !== 'undefined') {
      $(carousel).slick({
        dots: true,
        arrows: false,
        infinite: true,
        speed: 300,
        slidesToShow: 1,
        adaptiveHeight: true,
        autoplay: true,
        autoplaySpeed: 5000
      });
    }
  }

  // Initialize
  function init() {
    // Set up event listeners
    window.addEventListener('scroll', handleScroll);

    if (menuToggle) {
      menuToggle.addEventListener('click', toggleMenu);
    }

    if (navCloseBtn) {
      navCloseBtn.addEventListener('click', toggleMenu);
    }

    // Initial setup
    handleScroll();
    setupDropdowns();
    setupFeatureTabs();
    initTestimonialsCarousel();

    // Handle window resize
    window.addEventListener('resize', setupDropdowns);
  }

  // Run initialization when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
