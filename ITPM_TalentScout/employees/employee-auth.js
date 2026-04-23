/**
 * Employee Authentication State Manager
 * 
 * This module provides global login state detection and persistence across tabs.
 * It detects session state and updates the navbar consistently across all pages.
 */

(function() {
  'use strict';

  /**
   * Initialize login state from window variables set by PHP
   * These are set via PHP in each page:
   *   window.isLoggedIn
   *   window.employeeName
   *   window.employeeId
   */
  const authState = {
    isLoggedIn: typeof window.isLoggedIn !== 'undefined' ? window.isLoggedIn : false,
    employeeName: typeof window.employeeName !== 'undefined' ? window.employeeName : '',
    employeeId: typeof window.employeeId !== 'undefined' ? window.employeeId : 0,
  };

  /**
   * Public API: Check if user is logged in
   */
  window.isUserLoggedIn = function() {
    return authState.isLoggedIn;
  };

  /**
   * Public API: Get current user name
   */
  window.getUserInfo = function() {
    return {
      isLoggedIn: authState.isLoggedIn,
      name: authState.employeeName,
      id: authState.employeeId,
    };
  };

  /**
   * Redirect to login if not authenticated
   */
  window.requireLogin = function(redirectTo = null) {
    if (!authState.isLoggedIn) {
      const loginPath = typeof window.location.pathname !== 'undefined' ? 
        window.location.pathname.includes('/modules/') ?
        '../../login.php' : './login.php' : '../../login.php';
      
      if (redirectTo) {
        window.location.href = loginPath + '?redirect=' + encodeURIComponent(redirectTo);
      } else {
        window.location.href = loginPath;
      }
    }
  };

  /**
   * Get appropriate redirect URL based on login status
   * If logged in: redirect to application page
   * If not: redirect to login page
   */
  window.getApplyButtonHref = function() {
    if (authState.isLoggedIn) {
      return '../applicant-tracking/index.php';
    } else {
      return '../../login.php';
    }
  };

  /**
   * Update navbar dynamically (in case navbar wasn't rendered with session vars)
   * This is a fallback utility for any edge cases
   */
  window.updateNavbarWithAuthState = function() {
    const navActions = document.querySelector('.nav-actions');
    if (!navActions) return;

    // Clear existing content
    navActions.innerHTML = '';

    if (authState.isLoggedIn) {
      // Logged in state
      const userSpan = document.createElement('span');
      userSpan.className = 'nav-user';
      userSpan.textContent = 'Welcome, ' + (authState.employeeName || 'User');
      navActions.appendChild(userSpan);

      const logoutBtn = document.createElement('a');
      logoutBtn.href = '../../logout.php';
      logoutBtn.className = 'btn btn-outline';
      logoutBtn.textContent = 'Logout';
      navActions.appendChild(logoutBtn);
    } else {
      // Not logged in state
      const loginBtn = document.createElement('a');
      loginBtn.href = '../../login.php';
      loginBtn.className = 'btn btn-outline';
      loginBtn.textContent = 'Login';
      navActions.appendChild(loginBtn);

      const signupBtn = document.createElement('a');
      signupBtn.href = '../../signup.php';
      signupBtn.className = 'btn btn-primary';
      signupBtn.textContent = 'Get Started';
      navActions.appendChild(signupBtn);
    }
  };

  /**
   * Listen for storage changes from other tabs
   * This allows us to detect login/logout in one tab and update others
   */
  window.addEventListener('storage', function(e) {
    if (e.key === 'employee_auth_state' && e.newValue) {
      try {
        const newState = JSON.parse(e.newValue);
        authState.isLoggedIn = newState.isLoggedIn;
        authState.employeeName = newState.employeeName;
        authState.employeeId = newState.employeeId;
        
        // Update navbar if needed
        window.updateNavbarWithAuthState();
        
        // Trigger custom event for other scripts to listen to
        window.dispatchEvent(new CustomEvent('authStateChanged', { detail: authState }));
      } catch (err) {
        console.warn('Failed to parse auth state from storage:', err);
      }
    }
  });

  /**
   * Set auth state in localStorage for cross-tab communication
   * Called by logout/login pages
   */
  window.setAuthState = function(loginState, name = '', id = 0) {
    authState.isLoggedIn = loginState;
    authState.employeeName = name;
    authState.employeeId = id;
    
    try {
      localStorage.setItem('employee_auth_state', JSON.stringify(authState));
    } catch (err) {
      console.warn('Failed to save auth state to localStorage:', err);
    }
    
    // Dispatch event for other scripts on same page
    window.dispatchEvent(new CustomEvent('authStateChanged', { detail: authState }));
  };

  // Debug mode: Log current auth state
  if (typeof window.DEBUG_AUTH !== 'undefined' && window.DEBUG_AUTH) {
    console.log('[AuthState]', authState);
  }

  // Expose state for debugging (optional)
  if (typeof window.DEBUG_AUTH !== 'undefined' && window.DEBUG_AUTH) {
    window.__authState = authState;
  }
})();
