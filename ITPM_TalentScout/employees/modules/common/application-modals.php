<!-- Confirmation Modal -->
<div id="confirmModal" class="modal-overlay" aria-hidden="true">
  <div class="modal-content modal-confirm">
    <div class="modal-header">
      <div class="modal-icon confirm-icon">📝</div>
      <h3 id="confirmTitle">Confirm Your Application</h3>
    </div>
    <div class="modal-body">
      <p id="confirmMessage">You are about to apply for this position:</p>
      <div class="confirm-details" id="confirmDetails">
        <div class="confirm-detail-row">
          <span class="confirm-label">Position:</span>
          <span class="confirm-value" id="confirmJobTitle">-</span>
        </div>
        <div class="confirm-detail-row">
          <span class="confirm-label">Company:</span>
          <span class="confirm-value" id="confirmCompany">-</span>
        </div>
        <div class="confirm-detail-row">
          <span class="confirm-label">Location:</span>
          <span class="confirm-value" id="confirmLocation">-</span>
        </div>
      </div>
      <div class="anonymous-option">
        <label class="anonymous-checkbox">
          <input type="checkbox" id="applyAnonymously">
          <span class="checkbox-label">Apply Anonymously</span>
        </label>
        <p class="anonymous-note">Your identity will be hidden from the employer</p>
      </div>
      <p class="confirm-warning">⚠️ Once submitted, you cannot withdraw your application.</p>
    </div>
    <div class="modal-actions">
      <button type="button" class="btn-modal btn-cancel" id="confirmCancelBtn">Cancel</button>
      <button type="button" class="btn-modal btn-confirm" id="confirmApplyBtn" onclick="if(window.executeApplication) window.executeApplication(); else alert('Function not loaded')">Submit Application</button>
    </div>
  </div>
</div>

<!-- Message Modal (for already applied, errors, etc) -->
<div id="messageModal" class="modal-overlay" aria-hidden="true">
  <div class="modal-content modal-message">
    <div class="modal-header">
      <div class="modal-icon" id="messageIcon">ℹ️</div>
      <h3 id="messageTitle">Notification</h3>
    </div>
    <div class="modal-body">
      <p id="messageText">Message goes here.</p>
      <div class="message-actions" id="messageActions"></div>
    </div>
    <div class="modal-actions">
      <button type="button" class="btn-modal btn-primary" id="messageOkBtn">OK</button>
    </div>
  </div>
</div>

<style>
/* Modal Overlay Base */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(42, 42, 34, 0.5);
  backdrop-filter: blur(4px);
  display: none;
  align-items: center;
  justify-content: center;
  padding: 16px;
  z-index: 2000;
}

.modal-overlay.active {
  display: flex;
}

/* Modal Content */
.modal-content {
  background: #fff;
  border-radius: 16px;
  border: 1px solid #ece5d5;
  box-shadow: 0 20px 50px rgba(42, 42, 34, 0.2);
  padding: 0;
  text-align: center;
  max-width: 420px;
  width: 100%;
  animation: modalPopIn 0.2s ease-out;
}

@keyframes modalPopIn {
  from { opacity: 0; transform: scale(0.95) translateY(10px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}

/* Modal Header */
.modal-header {
  padding: 1.5rem 1.5rem 0.75rem;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.modal-icon {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  margin-bottom: 0.75rem;
}

.confirm-icon {
  background: #f0e4c8;
  color: #7a6230;
}

.modal-icon.info-icon {
  background: #e3f2fd;
  color: #1976d2;
}

.modal-icon.success-icon {
  background: #e8f5e9;
  color: #388e3c;
}

.modal-icon.warning-icon {
  background: #fff3e0;
  color: #e65100;
}

.modal-icon.error-icon {
  background: #ffebee;
  color: #d32f2f;
}

.modal-header h3 {
  font-family: 'Playfair Display', serif;
  font-size: 1.25rem;
  font-weight: 700;
  color: #2a2a22;
  margin: 0;
}

/* Modal Body */
.modal-body {
  padding: 0.5rem 1.5rem 1.25rem;
}

.modal-body p {
  font-size: 0.95rem;
  color: #5a5448;
  line-height: 1.6;
  margin: 0 0 1rem;
}

/* Confirm Details */
.confirm-details {
  background: #faf8f3;
  border-radius: 12px;
  padding: 1rem;
  text-align: left;
  border: 1px solid #ece5d5;
}

.confirm-detail-row {
  display: flex;
  justify-content: space-between;
  padding: 0.4rem 0;
  border-bottom: 1px solid #ece5d5;
}

.confirm-detail-row:last-child {
  border-bottom: none;
}

.confirm-label {
  font-size: 0.85rem;
  color: #8a8070;
  font-weight: 500;
}

.confirm-value {
  font-size: 0.85rem;
  color: #2a2a22;
  font-weight: 600;
  text-align: right;
  max-width: 60%;
}

.confirm-warning {
  font-size: 0.8rem;
  color: #d32f2f;
  background: #ffebee;
  padding: 0.6rem 0.85rem;
  border-radius: 8px;
  margin-top: 1rem;
  text-align: center;
}

.anonymous-option {
  margin-top: 1rem;
  padding: 0.85rem;
  background: #f5f0e8;
  border-radius: 8px;
}
.anonymous-checkbox {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}
.anonymous-checkbox input {
  width: 18px;
  height: 18px;
  accent-color: #4a6b50;
}
.checkbox-label {
  font-weight: 600;
  color: #2a2a22;
}
.anonymous-note {
  font-size: 0.75rem;
  color: #8a8070;
  margin: 0.25rem 0 0 1.75rem;
}

/* Modal Actions */
.modal-actions {
  padding: 0.5rem 1.5rem 1.5rem;
  display: flex;
  gap: 0.75rem;
  justify-content: center;
}

.btn-modal {
  padding: 0.65rem 1.5rem;
  border-radius: 999px;
  font-family: 'DM Sans', sans-serif;
  font-size: 0.9rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
  border: none;
}

.btn-cancel {
  background: #fff;
  border: 1px solid #c4b9a8;
  color: #5a5448;
}

.btn-cancel:hover {
  background: #f5f0e8;
  border-color: #8a8070;
}

.btn-confirm {
  background: #4a6b50;
  color: #fff;
}

.btn-confirm:hover {
  background: #6b8f71;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(74, 107, 80, 0.3);
}

.btn-modal.btn-primary {
  background: #4a6b50;
  color: #fff;
}

.btn-modal.btn-primary:hover {
  background: #6b8f71;
}

/* Message Actions (for links in message modal) */
.message-actions {
  margin-top: 1rem;
  display: flex;
  justify-content: center;
  gap: 0.75rem;
}

.message-actions a {
  color: #4a6b50;
  font-weight: 600;
  text-decoration: none;
}

.message-actions a:hover {
  text-decoration: underline;
}
</style>

<script>
// Application Modals JavaScript - Immediate execution
(function() {
  console.log('Application modals loaded');
  
  // Pending job application for confirmation
  let pendingApplication = null;

  // Show confirmation modal - defined immediately
  window.showConfirmModal = function(jobData) {
    console.log('showConfirmModal called', jobData);
    try {
      let jobTitle, companyName, jobPostId, location;
      
      if (typeof jobData === 'object' && jobData !== null) {
        jobTitle = jobData.title || 'Unknown Position';
        companyName = jobData.company || 'Unknown Company';
        jobPostId = jobData.jobPostId;
        location = jobData.location || 'Not specified';
      } else {
        jobTitle = 'Unknown Position';
        companyName = 'Unknown Company';
        location = 'Not specified';
      }
      
      const modal = document.getElementById('confirmModal');
      if (!modal) {
        console.error('Confirm modal not found');
        alert('Error: Modal not found');
        return;
      }
      
      const jobTitleEl = document.getElementById('confirmJobTitle');
      const companyEl = document.getElementById('confirmCompany');
      const locationEl = document.getElementById('confirmLocation');
      
      if (jobTitleEl) jobTitleEl.textContent = jobTitle;
      if (companyEl) companyEl.textContent = companyName;
      if (locationEl) locationEl.textContent = location;
      
// Store pending application
    const isAnonymous = document.getElementById('applyAnonymously')?.checked || false;
    pendingApplication = { jobTitle, companyName, jobPostId, location, isAnonymous };
      
      modal.classList.add('active');
      modal.setAttribute('aria-hidden', 'false');
      console.log('Modal shown successfully');
    } catch (e) {
      console.error('Error showing modal:', e);
      alert('Error: ' + e.message);
    }
  };

  // Close confirmation modal
  window.closeConfirmModal = function() {
    const modal = document.getElementById('confirmModal');
    if (modal) {
      modal.classList.remove('active');
      modal.setAttribute('aria-hidden', 'true');
    }
    // Reset anonymous checkbox
    const checkbox = document.getElementById('applyAnonymously');
    if (checkbox) checkbox.checked = false;
    pendingApplication = null;
  };

  // Execute the pending application
  window.executeApplication = async function() {
    console.log('executeApplication called', pendingApplication);
    if (!pendingApplication) {
      console.error('No pending application');
      alert('Error: No application pending');
      return;
    }
    
    const jobPostId = pendingApplication.jobPostId;
    const isAnonymous = pendingApplication.isAnonymous;
    console.log('Submitting application for job:', jobPostId, 'anonymous:', isAnonymous);
    closeConfirmModal();
    
    // Determine the correct path to submit-application.php
    const currentPath = window.location.pathname;
    let submitPath = './submit-application.php';
    if (currentPath.includes('/ai-matching/') || currentPath.includes('/job-postings/')) {
      submitPath = '../job-postings/submit-application.php';
    }
    
    try {
      const formData = new FormData();
      formData.append('job_post_id', jobPostId);
      formData.append('is_anonymous', isAnonymous ? '1' : '0');

      console.log('Submitting to:', submitPath);
      const response = await fetch(submitPath, {
        method: 'POST',
        body: formData
      });

      console.log('Response status:', response.status);
      const data = await response.json();
      console.log('Response data:', data);

      if (response.ok && data.success) {
        showSuccessAnimation(data.application);
      } else if (response.status === 409) {
        console.log('Handling 409 - already applied');
        // Already applied
        showMessageModal(
          'Already Applied',
          data.message || 'You have already applied for this job.',
          'warning',
          '<a href="../applicant-tracking/index.php">View Applications</a> | <a href="../messages/index.php">Go to Messages</a>'
        );
      } else {
        showMessageModal(
          'Application Error',
          data.message || 'Failed to submit application. Please try again.',
          'error'
        );
      }
    } catch (error) {
      console.error('Application submission error:', error);
      showMessageModal(
        'Error',
        'An error occurred. Please try again.',
        'error'
      );
    }
  };

  // Show message modal
  window.showMessageModal = function(title, message, type = 'info', actions = '') {
    const modal = document.getElementById('messageModal');
    const titleEl = document.getElementById('messageTitle');
    const messageEl = document.getElementById('messageText');
    const iconEl = document.getElementById('messageIcon');
    const actionsEl = document.getElementById('messageActions');
    
    if (titleEl) titleEl.textContent = title;
    if (messageEl) messageEl.textContent = message;
    if (actionsEl) actionsEl.innerHTML = actions || '';
    
    // Set icon based on type
    const iconMap = {
      'info': 'ℹ️',
      'success': '✅',
      'warning': '⚠️',
      'error': '❌'
    };
    if (iconEl) iconEl.textContent = iconMap[type] || 'ℹ️';
    
    // Set icon class
    const iconClassMap = {
      'info': 'info-icon',
      'success': 'success-icon',
      'warning': 'warning-icon',
      'error': 'error-icon'
    };
    if (iconEl) {
      iconEl.className = 'modal-icon ' + (iconClassMap[type] || 'info-icon');
    }
    
    if (modal) {
      modal.classList.add('active');
      modal.setAttribute('aria-hidden', 'false');
    }
  };

  // Close message modal
  window.closeMessageModal = function() {
    const modal = document.getElementById('messageModal');
    if (modal) {
      modal.classList.remove('active');
      modal.setAttribute('aria-hidden', 'true');
    }
  };

  // Show success animation and message
  window.showSuccessAnimation = function(application) {
    showMessageModal(
      'Application Submitted!',
      'Your application has been successfully submitted. The employer will review your profile and may contact you through the messaging system.',
      'success',
      '<a href="../applicant-tracking/index.php">View Applications</a> | <a href="../messages/index.php">Go to Messages</a>'
    );
  };

  // Initialize modal event listeners when DOM is ready
  document.addEventListener('DOMContentLoaded', function() {
    // Confirmation modal buttons
    const confirmCancelBtn = document.getElementById('confirmCancelBtn');
    const confirmApplyBtn = document.getElementById('confirmApplyBtn');
    
    if (confirmCancelBtn) {
      confirmCancelBtn.addEventListener('click', closeConfirmModal);
    }
    
    if (confirmApplyBtn) {
      confirmApplyBtn.addEventListener('click', window.executeApplication);
    }

    // Message modal button
    const messageOkBtn = document.getElementById('messageOkBtn');
    if (messageOkBtn) {
      messageOkBtn.addEventListener('click', closeMessageModal);
    }

    // Close modals when clicking overlay
    const confirmModal = document.getElementById('confirmModal');
    const messageModal = document.getElementById('messageModal');
    
    if (confirmModal) {
      confirmModal.addEventListener('click', function(e) {
        if (e.target === confirmModal) {
          closeConfirmModal();
        }
      });
    }
    
    if (messageModal) {
      messageModal.addEventListener('click', function(e) {
        if (e.target === messageModal) {
          closeMessageModal();
        }
      });
    }

    // Close modals on Escape key
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') {
        closeConfirmModal();
        closeMessageModal();
      }
    });
  });
})();
</script>