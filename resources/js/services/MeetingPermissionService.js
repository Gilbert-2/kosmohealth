/**
 * MeetingPermissionService - Service to handle meeting permissions
 *
 * This service checks if the current user is the meeting creator or has specific permissions
 * to access certain features like emotion recognition.
 */

class MeetingPermissionService {
    constructor() {
        this.currentMeeting = null;
        this.isCreator = false;
        this.userRoles = [];
        this.permissions = {
            canSeeEmotions: false,
            canBlurFace: true,
            canBlurFullScreen: true,
            canUseGestureRecognition: true
        };
    }

    /**
     * Initialize the service with the current meeting data
     * @param {Object} meetingData - Meeting data from the API
     * @param {Object} userData - Current user data
     */
    initialize(meetingData, userData) {
        if (!meetingData) return;

        this.currentMeeting = meetingData;

        // Check if current user is the meeting creator
        // is_host property indicates if the current user is the meeting creator
        this.isCreator = meetingData.is_host === true;

        // Get user roles if available
        if (userData && userData.roles) {
            this.userRoles = userData.roles.map(role => role.name);
        }

        // Set permissions based on role and creator status
        this.updatePermissions();

        console.log('MeetingPermissionService initialized', {
            isCreator: this.isCreator,
            permissions: this.permissions,
            meetingData: {
                is_host: meetingData.is_host,
                uuid: meetingData.uuid
            }
        });
    }

    /**
     * Update permissions based on user role and creator status
     */
    updatePermissions() {
        // STRICT RULE: Only the meeting creator can see emotions
        this.permissions.canSeeEmotions = this.isCreator;

        // Everyone can toggle blur on/off for their face
        this.permissions.canBlurFace = true;

        // Everyone can blur the full screen
        this.permissions.canBlurFullScreen = true;

        // Everyone can use gesture recognition
        this.permissions.canUseGestureRecognition = true;

        console.log('Permissions updated:', {
            canSeeEmotions: this.permissions.canSeeEmotions,
            canBlurFace: this.permissions.canBlurFace,
            isCreator: this.isCreator
        });
    }

    /**
     * Check if the current user can see emotions
     * @returns {boolean} - Whether the user can see emotions
     */
    canSeeEmotions() {
        return this.permissions.canSeeEmotions;
    }

    /**
     * Check if the current user can blur their face
     * @returns {boolean} - Whether the user can blur their face
     */
    canBlurFace() {
        return this.permissions.canBlurFace;
    }

    /**
     * Check if the current user can blur the full screen
     * @returns {boolean} - Whether the user can blur the full screen
     */
    canBlurFullScreen() {
        return this.permissions.canBlurFullScreen;
    }

    /**
     * Check if the current user can use gesture recognition
     * @returns {boolean} - Whether the user can use gesture recognition
     */
    canUseGestureRecognition() {
        return this.permissions.canUseGestureRecognition;
    }

    /**
     * Check if the current user is the meeting creator
     * @returns {boolean} - Whether the user is the meeting creator
     */
    isMeetingCreator() {
        return this.isCreator;
    }

    /**
     * Set the meeting creator status manually
     * @param {boolean} isCreator - Whether the user is the meeting creator
     */
    setIsCreator(isCreator) {
        this.isCreator = isCreator;
        this.updatePermissions();
    }
}

// Create singleton instance
const meetingPermissionService = new MeetingPermissionService();

export default meetingPermissionService;
