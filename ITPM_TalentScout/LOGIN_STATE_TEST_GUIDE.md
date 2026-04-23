<!-- 
  TEST GUIDE FOR LOGIN STATE FIX
  
  This file documents how to manually test the login state behavior fixes.
  It covers all scenarios mentioned in the issue.
-->

# Login State Behavior - Test Guide

## Test Environment
- Browser: Any modern browser supporting localStorage (Chrome, Firefox, Safari, Edge)
- URL: `http://localhost/ITPM_TalentScoutAI/ITPM_TalentScout/employees/`

## Prerequisite
- Ensure XAMPP is running
- Database is properly configured
- At least one test employee account exists in the database

## Test Cases

### Test 1: Resume Builder - Show User Name When Logged In
**Objective:** Verify navbar displays "Welcome, [Name]" instead of "Log In" when user is logged in

**Steps:**
1. Open job-postings tab: `/employees/modules/job-postings/index.php`
2. Login with valid credentials
3. Navigate to Resume Builder tab
4. **Expected Result:** 
   - "Welcome, [Your Name]" appears in navbar
   - "Logout" button shown
   - No "Log In" button shown

**Pass/Fail:** _____

---

### Test 2: Consistent Login State Across All Tabs
**Objective:** All tabs should show the same login state after login

**Steps:**
1. Open 3 tabs:
   - Tab A: Job Postings (`/employees/modules/job-postings/index.php`)
   - Tab B: Resume Builder (`/employees/modules/resume-builder/index.php`)
   - Tab C: AI Matching (`/employees/modules/ai-matching/index.php`)
2. In Tab A, login with valid credentials
3. Switch to Tab B and Tab C
4. **Expected Result:**
   - Tab B shows "Welcome, [Name]" + Logout
   - Tab C shows "Welcome, [Name]" + Logout
   - No outdated "Log In" button visible

**Pass/Fail:** _____

---

### Test 3: Apply Now - Redirect to Login When Not Logged In
**Objective:** Verify "Apply Now" redirects to login page for unauthenticated users

**Steps:**
1. Logout from all tabs (if currently logged in)
2. Click "Apply Now" on any job card
3. **Expected Result:**
   - Redirected to login page (`/employees/login.php`)
   - User is NOT taken to application tracking page

**Pass/Fail:** _____

---

### Test 4: Apply Now - Redirect to Applications When Logged In
**Objective:** Verify "Apply Now" redirects to application tracking for authenticated users

**Steps:**
1. Login with valid credentials
2. Go to Job Postings page
3. Click "Apply Now" on any job card
4. **Expected Result:**
   - Redirected to Application Tracker (`/employees/modules/applicant-tracking/index.php`)
   - User can see their applications

**Pass/Fail:** _____

---

### Test 5: Apply Now - Modal Button (Logged Out)
**Objective:** Verify modal Apply Now button redirects to login when not logged in

**Steps:**
1. Logout from all tabs
2. Go to Job Postings page
3. Click "View Details" on any job
4. In the modal, click "Apply Now"
5. **Expected Result:**
   - Redirected to login page
   - Job modal disappears

**Pass/Fail:** _____

---

### Test 6: Apply Now - Modal Button (Logged In)
**Objective:** Verify modal Apply Now button redirects correctly when logged in

**Steps:**
1. Login with valid credentials
2. Go to Job Postings page
3. Click "View Details" on any job
4. In the modal, click "Apply Now"
5. **Expected Result:**
   - Redirected to Application Tracker
   - Job modal disappears
   - Can view applications

**Pass/Fail:** _____

---

### Test 7: Cross-Tab Login Detection
**Objective:** Verify login in one tab is detected by other tabs automatically

**Steps:**
1. Open 2 tabs side-by-side:
   - Tab A: Job Postings (keep it visible)
   - Tab B: Login page (in background)
2. In Tab B, login with valid credentials
3. Immediately switch to Tab A
4. **Expected Result:**
   - Tab A navbar automatically updates within 1-2 seconds
   - Shows "Welcome, [Name]" + Logout
   - No page reload needed

**Pass/Fail:** _____

---

### Test 8: Cross-Tab Logout Detection
**Objective:** Verify logout in one tab is detected by other tabs automatically

**Steps:**
1. Login and open 2 tabs side-by-side:
   - Tab A: Job Postings
   - Tab B: Any other page
2. In Tab A, click Logout
3. Immediately switch to Tab B
4. **Expected Result:**
   - Tab B navbar updates to show "Log In" + "Get Started" buttons
   - "Welcome" message and Logout button disappear
   - No page reload needed

**Pass/Fail:** _____

---

### Test 9: No Outdated UI in Resume Builder
**Objective:** Verify Resume Builder never shows outdated login state

**Steps:**
1. Open Resume Builder while logged out
2. Resume Builder should show correct navbar
3. Login in DIFFERENT tab
4. Switch back to Resume Builder tab
5. **Expected Result:**
   - Login UI updates automatically
   - Can save/load resume without issues
   - No console errors

**Pass/Fail:** _____

---

### Test 10: Page Reload Persistence
**Objective:** Verify login state persists after page reload

**Steps:**
1. Login with valid credentials
2. Go to Job Postings page
3. Refresh the page (Ctrl+R / Cmd+R)
4. **Expected Result:**
   - User remains logged in
   - "Welcome, [Name]" still shown
   - Session not lost

**Pass/Fail:** _____

---

### Test 11: Navigation Between All Tabs
**Objective:** Verify navbar is consistent when navigating between all module tabs

**Steps:**
1. Login and navigate through all tabs in sequence:
   - Home
   - Browse Jobs
   - AI Matching
   - Resume Builder
   - Skills (Gap Analysis)
   - Applications
2. **Expected Result:**
   - Every page shows "Welcome, [Name]" + Logout
   - No inconsistencies
   - All "Apply Now" buttons work correctly

**Pass/Fail:** _____

---

### Test 12: Browser Back Button
**Objective:** Verify login state is correct when using browser back button

**Steps:**
1. Login and click several "Apply Now" buttons
2. Use browser back button to navigate back
3. **Expected Result:**
   - Login state remains consistent
   - Navbar still shows "Welcome, [Name]"
   - No "Log In" button appears

**Pass/Fail:** _____

---

## Browser Console Check

**How to check for errors:**
1. Open DevTools (F12 / Cmd+Option+I)
2. Go to Console tab
3. Navigate through the application
4. **Expected Result:**
   - No red error messages
   - No warnings about undefined variables
   - No "Uncaught" errors

**Pass/Fail:** _____

---

## Summary

| Test # | Name | Pass/Fail | Notes |
|--------|------|-----------|-------|
| 1 | Resume Builder Name Display | ___ | |
| 2 | Consistent State Across Tabs | ___ | |
| 3 | Apply Now - Not Logged In | ___ | |
| 4 | Apply Now - Logged In | ___ | |
| 5 | Modal Apply - Logged Out | ___ | |
| 6 | Modal Apply - Logged In | ___ | |
| 7 | Cross-Tab Login Detection | ___ | |
| 8 | Cross-Tab Logout Detection | ___ | |
| 9 | Resume Builder No Outdated UI | ___ | |
| 10 | Page Reload Persistence | ___ | |
| 11 | Navigation Consistency | ___ | |
| 12 | Browser Back Button | ___ | |
| Browser Console | No Errors | ___ | |

---

## Troubleshooting

### Issue: Login state not updating across tabs
**Solution:** 
- Ensure browser supports localStorage
- Check browser privacy settings (localStorage might be disabled)
- Clear browser cache and reload

### Issue: "Apply Now" always redirects to login
**Solution:**
- Verify `session_start()` is in job-postings/index.php
- Check PHP session is being created on login
- Verify `window.isLoggedIn` is set correctly (check page source)

### Issue: Console errors about undefined variables
**Solution:**
- Verify employee-auth.js is being loaded
- Check file path is correct: `/employees/employee-auth.js`
- Ensure no JavaScript syntax errors in that file

### Issue: Navbar doesn't update automatically in other tabs
**Solution:**
- Verify localStorage is enabled in browser
- Check server time is synchronized
- Try manual page refresh (should update)
- Note: Auto-update requires ~1-2 second delay

---

## Additional Notes

- All tests should pass for the fix to be considered complete
- Cross-tab detection relies on localStorage (might not work in private browsing)
- Tests 7 and 8 require manual timing - 1-2 second delay is normal
- If any test fails, check the console for errors before reporting

