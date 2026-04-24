<?php
session_start();
session_destroy();

// Set client-side auth state to indicate logout
// This helps other tabs detect the logout via storage event
?>
<!DOCTYPE html>
<html>
<head>
  <title>Logging out...</title>
</head>
<body>
  <script>
    // Clear auth state from localStorage so other tabs detect logout
    try {
      localStorage.setItem('employee_auth_state', JSON.stringify({
        isLoggedIn: false,
        employeeName: '',
        employeeId: 0
      }));
    } catch (e) {
      // Ignore if localStorage is unavailable
    }
  </script>
</body>
</html>
<?php
header('Location: ./index.php');
exit;
?>
