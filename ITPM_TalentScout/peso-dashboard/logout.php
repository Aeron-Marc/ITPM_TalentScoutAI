<?php
session_start();
// Clear server-side session
session_unset();
session_destroy();

// Render a tiny page that clears client localStorage then redirects to login
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Logging out...</title>
    <meta http-equiv="refresh" content="0;url=login.php">
    <script>
        try {
            localStorage.setItem('peso_admin_auth', JSON.stringify({
                isLoggedIn: false,
                username: ''
            }));
        } catch (e) {}
        window.location.replace('login.php');
    </script>
</head>

<body></body>

</html>