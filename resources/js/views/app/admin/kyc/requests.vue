<template>
  <div class="kyc-admin-wrapper">
    <base-container>
      <div class="row">
        <div class="col-12">
          <card>
            <template slot="header">
              <h5 class="card-title">KYC Verification Requests</h5>
              <div class="card-actions">
                <base-button size="sm" @click="fetchRequests" :loading="isLoading">
                  <i class="fas fa-sync-alt mr-1"></i> Refresh
                </base-button>
              </div>
            </template>

            <!-- Loading State -->
            <div v-if="isLoading" class="text-center p-5">
              <animated-loader :is-loading="isLoading" :loader-color="'#d15465'"></animated-loader>
              <p class="mt-3">Loading verification requests...</p>
            </div>

            <!-- Empty State -->
            <div v-else-if="!requests.length" class="text-center p-5">
              <i class="fas fa-id-card fa-4x text-muted mb-3"></i>
              <h4>No Verification Requests</h4>
              <p class="text-muted">There are no pending KYC verification requests at this time.</p>
            </div>

            <!-- Requests Table -->
            <div v-else class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>User</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Document</th>
                    <th>Face Match</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="request in requests" :key="request.id">
                    <td>
                      <div class="d-flex align-items-center">
                        <div class="avatar avatar-sm mr-2">
                          <img v-if="request.user.avatar" :src="request.user.avatar" alt="User Avatar">
                          <div v-else class="avatar-initials">{{ getInitials(request.user.name) }}</div>
                        </div>
                        <div>
                          <div class="font-weight-bold">{{ request.user.name }}</div>
                          <div class="text-muted small">{{ request.user.email }}</div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <span class="badge" :class="getStatusClass(request.status)">
                        {{ formatStatus(request.status) }}
                      </span>
                    </td>
                    <td>
                      <div>{{ formatDate(request.created_at) }}</div>
                      <div class="text-muted small">{{ timeAgo(request.created_at) }}</div>
                    </td>
                    <td>
                      <button 
                        v-if="request.document_path" 
                        class="btn btn-sm btn-outline-primary"
                        @click="viewDocument(request)"
                      >
                        <i class="fas fa-file-image mr-1"></i> View
                      </button>
                      <span v-else class="text-muted">No document</span>
                    </td>
                    <td>
                      <div v-if="request.face_match_score !== null">
                        <div class="progress" style="height: 6px; width: 100px;">
                          <div 
                            class="progress-bar" 
                            :class="getFaceMatchScoreClass(request.face_match_score)"
                            :style="{width: `${request.face_match_score}%`}"
                          ></div>
                        </div>
                        <div class="small mt-1">{{ request.face_match_score }}% match</div>
                      </div>
                      <span v-else class="text-muted">Not verified</span>
                    </td>
                    <td>
                      <div class="btn-group">
                        <button 
                          class="btn btn-sm btn-success" 
                          @click="approveRequest(request)"
                          :disabled="request.status === 'approved'"
                        >
                          <i class="fas fa-check mr-1"></i> Approve
                        </button>
                        <button 
                          class="btn btn-sm btn-danger" 
                          @click="rejectRequest(request)"
                          :disabled="request.status === 'rejected'"
                        >
                          <i class="fas fa-times mr-1"></i> Reject
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </card>
        </div>
      </div>
    </base-container>

    <!-- Document Preview Modal -->
    <modal v-if="selectedRequest" @close="closeDocumentModal">
      <template slot="header">
        <h5 class="modal-title">Document Preview</h5>
      </template>
      <div class="document-preview">
        <img 
          v-if="documentUrl" 
          :src="documentUrl" 
          alt="Document Preview" 
          class="img-fluid"
        >
        <div v-else class="text-center p-5">
          <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
          <p>Loading document...</p>
        </div>
      </div>
      <template slot="footer">
        <button class="btn btn-secondary" @click="closeDocumentModal">Close</button>
      </template>
    </modal>
  </div>
</template>

<script>
import { format, formatDistance } from 'date-fns';

export default {
  data() {
    return {
      requests: [],
      isLoading: false,
      selectedRequest: null,
      documentUrl: null
    };
  },

  mounted() {
    this.fetchRequests();
  },

  methods: {
    async fetchRequests() {
      this.isLoading = true;
      try {
        // Simulate API call for now
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        // Mock data - in a real app, this would be an API call
        this.requests = [
          {
            id: 1,
            user: {
              id: 101,
              name: 'John Doe',
              email: 'john@example.com',
              avatar: null
            },
            status: 'pending',
            created_at: '2023-06-15T10:30:00Z',
            document_path: '/documents/id_card.jpg',
            face_match_score: 85
          },
          {
            id: 2,
            user: {
              id: 102,
              name: 'Jane Smith',
              email: 'jane@example.com',
              avatar: null
            },
            status: 'approved',
            created_at: '2023-06-14T14:20:00Z',
            document_path: '/documents/passport.jpg',
            face_match_score: 92
          },
          {
            id: 3,
            user: {
              id: 103,
              name: 'Robert Johnson',
              email: 'robert@example.com',
              avatar: null
            },
            status: 'rejected',
            created_at: '2023-06-13T09:15:00Z',
            document_path: '/documents/license.jpg',
            face_match_score: 45
          },
          {
            id: 4,
            user: {
              id: 104,
              name: 'Emily Davis',
              email: 'emily@example.com',
              avatar: null
            },
            status: 'pending',
            created_at: '2023-06-12T16:40:00Z',
            document_path: null,
            face_match_score: null
          }
        ];
        
        // In a real app, you would fetch from API:
        // const response = await this.$http.get('/api/admin/kyc/requests');
        // this.requests = response.data;
      } catch (error) {
        this.$toasted.error('Failed to load KYC requests');
        console.error('Error fetching KYC requests:', error);
      } finally {
        this.isLoading = false;
      }
    },

    getInitials(name) {
      if (!name) return '';
      return name
        .split(' ')
        .map(part => part.charAt(0))
        .join('')
        .toUpperCase()
        .substring(0, 2);
    },

    formatDate(dateString) {
      try {
        return format(new Date(dateString), 'MMM d, yyyy');
      } catch (error) {
        return dateString;
      }
    },

    timeAgo(dateString) {
      try {
        return formatDistance(new Date(dateString), new Date(), { addSuffix: true });
      } catch (error) {
        return '';
      }
    },

    formatStatus(status) {
      const statusMap = {
        'pending': 'Pending Review',
        'document_verified': 'Document Verified',
        'approved': 'Approved',
        'rejected': 'Rejected'
      };
      return statusMap[status] || status;
    },

    getStatusClass(status) {
      const classMap = {
        'pending': 'badge-warning',
        'document_verified': 'badge-info',
        'approved': 'badge-success',
        'rejected': 'badge-danger'
      };
      return classMap[status] || 'badge-secondary';
    },

    getFaceMatchScoreClass(score) {
      if (score >= 80) return 'bg-success';
      if (score >= 60) return 'bg-warning';
      return 'bg-danger';
    },

    viewDocument(request) {
      this.selectedRequest = request;
      this.documentUrl = null;
      
      // In a real app, you would fetch the document URL from the API
      setTimeout(() => {
        // Mock document URL - in a real app, this would come from the API
        this.documentUrl = 'https://via.placeholder.com/800x600?text=Sample+Document';
      }, 1000);
    },

    closeDocumentModal() {
      this.selectedRequest = null;
      this.documentUrl = null;
    },

    async approveRequest(request) {
      try {
        // In a real app, you would call the API to approve the request
        // await this.$http.post(`/api/admin/kyc/requests/${request.id}/approve`);
        
        // Update the local state
        request.status = 'approved';
        this.$toasted.success('KYC request approved successfully');
      } catch (error) {
        this.$toasted.error('Failed to approve KYC request');
        console.error('Error approving KYC request:', error);
      }
    },

    async rejectRequest(request) {
      try {
        // In a real app, you would call the API to reject the request
        // await this.$http.post(`/api/admin/kyc/requests/${request.id}/reject`);
        
        // Update the local state
        request.status = 'rejected';
        this.$toasted.success('KYC request rejected successfully');
      } catch (error) {
        this.$toasted.error('Failed to reject KYC request');
        console.error('Error rejecting KYC request:', error);
      }
    }
  }
};
</script>

<style scoped>
.kyc-admin-wrapper {
  padding: 20px 0;
}

.card-actions {
  display: flex;
  align-items: center;
}

.avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  overflow: hidden;
  background-color: #e9ecef;
  display: flex;
  align-items: center;
  justify-content: center;
}

.avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-initials {
  font-size: 14px;
  font-weight: bold;
  color: #495057;
}

.document-preview {
  max-height: 500px;
  overflow-y: auto;
  text-align: center;
  padding: 10px;
}

.document-preview img {
  max-width: 100%;
  border: 1px solid #dee2e6;
  border-radius: 4px;
}
</style>
