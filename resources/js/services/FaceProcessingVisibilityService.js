/**
 * Face Processing Visibility Service
 * Controls when and where the face processing box should be visible
 */

class FaceProcessingVisibilityService {
    /**
     * Check if face processing should be visible on the current page
     * @returns {boolean}
     */
    static shouldShowFaceProcessing() {
        // Get current route
        const currentPath = window.location.pathname;

        // Check if user is in a meeting via shared link
        const isInSharedMeeting = currentPath.includes('/app/live/meetings/') &&
                                  window.location.search.includes('token=');

        // Check if user is in dashboard (logged in)
        const isInDashboard = currentPath.includes('/app/') &&
                             !currentPath.includes('/app/auth/');

        // Don't show on auth pages or login pages
        const isAuthPage = currentPath.includes('/app/auth/') ||
                          currentPath === '/login' ||
                          currentPath === '/register' ||
                          currentPath.includes('/password') ||
                          currentPath.includes('/verify') ||
                          currentPath.includes('/auth/') ||
                          document.querySelector('.login-card') !== null ||
                          document.querySelector('.glassmorphism-login') !== null;

        // Return true if in dashboard or shared meeting, and not on auth page
        return (isInDashboard || isInSharedMeeting) && !isAuthPage;
    }

    /**
     * Check if the current page is a meeting page
     * @returns {boolean}
     */
    static isInMeeting() {
        const currentPath = window.location.pathname;
        return currentPath.includes('/app/live/meetings/');
    }

    /**
     * Check if the current page is a dashboard page
     * @returns {boolean}
     */
    static isInDashboard() {
        const currentPath = window.location.pathname;
        return currentPath.includes('/app/') &&
              !currentPath.includes('/app/auth/') &&
              !currentPath.includes('/app/live/meetings/');
    }
}

export default FaceProcessingVisibilityService;
