# Login State Flow Diagram

## 1. Page Load Sequence

```
User Visits Page
    ↓
PHP session_start() initializes session
    ↓
Check $_SESSION['employee_id']
    ├─ YES: Render navbar with "Welcome, [Name]" + Logout
    └─ NO: Render navbar with "Log In" + "Get Started"
    ↓
Set window variables (isLoggedIn, employeeName, employeeId)
    ↓
Load employee-auth.js
    ↓
employee-auth.js initializes authState from window variables
    ↓
Page renders with correct UI
```

## 2. Login Flow with Cross-Tab Sync

```
Tab A (Login Page)
    ↓
User enters credentials → POST /login.php
    ↓
Server authenticates → Set $_SESSION
    ↓
Render redirect page with localStorage.setItem()
    ↓
Redirect to /index.php
    ↓
New page renders with "Welcome, [Name]"

    ← Storage Event Fired

Tab B (Job Postings)
    ↓
Listener detects 'employee_auth_state' change in localStorage
    ↓
employee-auth.js updates authState
    ↓
Dispatches 'authStateChanged' event
    ↓
Navbar updates automatically (if updateNavbarWithAuthState() called)
    ↓
Apply buttons refresh with correct href
```

## 3. Logout Flow with Cross-Tab Sync

```
User clicks "Logout" in any tab
    ↓
Redirect to /logout.php
    ↓
session_destroy() clears server session
    ↓
Render redirect page with localStorage.setItem() → {isLoggedIn: false}
    ↓
Redirect to /index.php
    ↓
Page reloads with "Log In" button

    ← Storage Event Fired

All Other Tabs
    ↓
Listener detects 'employee_auth_state' change
    ↓
authState updated to {isLoggedIn: false}
    ↓
'authStateChanged' event dispatched
    ↓
Navbar updates automatically
```

## 4. Apply Now Button Decision Tree

```
User clicks "Apply Now"
    ↓
JavaScript checks window.isLoggedIn
    │
    ├─ TRUE (User is logged in)
    │  ↓
    │  href = '../applicant-tracking/index.php'
    │  ↓
    │  Redirect to Applications page
    │  ↓
    │  User can submit application
    │
    └─ FALSE (User not logged in)
       ↓
       href = '../../login.php'
       ↓
       Redirect to Login page
       ↓
       User must authenticate first
```

## 5. Session State Management

```
┌─────────────────────────────────────────────────────────┐
│                Server-Side (PHP)                         │
│                                                           │
│  $_SESSION['employee_id'] ────────┐                     │
│  $_SESSION['employee_name'] ──────┤                     │
│  $_SESSION['employee_email'] ─────┤                     │
│                                   │                     │
│                    Persists Across─┼──→ Page Reloads    │
│                    HTTP Requests   │                     │
│                                   │                     │
└─────────────────────────────────────────────────────────┘
         ↓ window variables passed on page load
┌─────────────────────────────────────────────────────────┐
│                Client-Side (JavaScript)                  │
│                                                           │
│  window.isLoggedIn ─────────────────┐                   │
│  window.employeeName ───────────────┤─→ Navbar Rendering│
│  window.employeeId ─────────────────┤                   │
│                                     │                   │
│  localStorage['employee_auth_state']─┘                   │
│  (for cross-tab sync)                                   │
│                                                           │
│  employee-auth.js authState                             │
│  (manages state, events, utilities)                     │
│                                                           │
└─────────────────────────────────────────────────────────┘
```

## 6. Navbar Rendering Logic

```
job-postings/index.php (and all other module pages)

<nav class="navbar">
  ...nav content...
  
  <div class="nav-actions">
    <?php if (isset($_SESSION['employee_id'])): ?>
      <!-- LOGGED IN STATE -->
      <span class="nav-user">Welcome, <?php echo htmlspecialchars($_SESSION['employee_name'] ?? 'User'); ?></span>
      <a href="../../logout.php" class="btn btn-outline">Logout</a>
    <?php else: ?>
      <!-- NOT LOGGED IN STATE -->
      <a href="../../login.php" class="btn btn-outline">Login</a>
      <a href="../../signup.php" class="btn btn-primary">Get Started</a>
    <?php endif; ?>
  </div>
</nav>
```

This is rendered ONCE on server (page load), then can be updated dynamically via JavaScript if needed.

## 7. Apply Button Implementation

```
Job Card Template (JavaScript):

<a href="${window.isLoggedIn ? '../applicant-tracking/index.php' : '../../login.php'}" 
   class="btn btn-primary">
  Apply Now
</a>

Job Modal (JavaScript):

document.getElementById('jobDetailsApply').href = 
  window.isLoggedIn ? '../applicant-tracking/index.php' : '../../login.php';
```

Both check the boolean flag set by PHP on page load.

## 8. localStorage Event Flow (Cross-Tab Sync)

```
Tab A changes auth state
    ↓
localStorage.setItem('employee_auth_state', JSON.stringify({...}))
    ↓
Browser fires 'storage' event on ALL OTHER TABS
    ↓
Tab B, Tab C, Tab D...
    ↓
window.addEventListener('storage', function(e) {
  if (e.key === 'employee_auth_state') {
    authState = JSON.parse(e.newValue)
    updateNavbarIfNeeded()
  }
})
    ↓
Other tabs update their UI without page reload
```

**Note:** This only works between tabs in the SAME browser instance, not across different browsers.

## 9. Complete Request Flow (Login → Browse → Apply)

```
1. USER LOGS IN
   └─→ login.php validates
       └─→ Sets $_SESSION
           └─→ Stores in localStorage
               └─→ Redirects to home

2. USER NAVIGATES TO JOB POSTINGS
   └─→ job-postings/index.php loads
       └─→ session_start() checks $_SESSION
            └─→ Sets window.isLoggedIn = true
                └─→ Loads employee-auth.js
                    └─→ Page renders with correct navbar

3. USER CLICKS "APPLY NOW"
   └─→ JavaScript checks window.isLoggedIn
       └─→ true → href = '../applicant-tracking/index.php'
           └─→ Redirects to applications page
               └─→ Can submit application

4. USER OPENS ANOTHER TAB (WHILE ALREADY LOGGED IN)
   └─→ New tab loads
       └─→ session_start() confirms login
            └─→ Sets window.isLoggedIn = true
                └─→ Renders with "Welcome, [Name]"

5. USER OPENS ANOTHER TAB (WHILE NOT LOGGED IN) THEN LOGS IN ELSEWHERE
   └─→ Tab A redirects to login
       └─→ Tab B still shows "Log In" button (not yet updated)
           └─→ User logs in on Tab A
               └─→ localStorage updated by Tab A
                   └─→ storage event fires on Tab B
                       └─→ authState updated
                           └─→ navbar updates (if updateNavbarWithAuthState() called)
```

## 10. Browser Compatibility

```
localStorage Support:
├─ Chrome/Chromium:           ✓ All versions
├─ Firefox:                   ✓ All versions  
├─ Safari:                    ✓ 10+
├─ Edge:                      ✓ All versions
└─ Private/Incognito Mode:    ⚠ May not work (falls back to server-side session)

Fallback Strategy:
├─ If localStorage unavailable:
│  └─ Still works via server-side $_SESSION
│      └─ Cross-tab sync won't work (requires manual page reload)
│          └─ But application functionality remains
└─ If JavaScript disabled:
   └─ Server-side checks still work
       └─ Navbar renders correctly
           └─ Some dynamic features unavailable (but safe)
```

---

This architecture ensures:
- ✅ Server-side session security (primary)
- ✅ Client-side UX optimization (secondary)
- ✅ Cross-tab awareness (bonus)
- ✅ Graceful degradation if features unavailable
- ✅ No security compromises

