<?php
session_start();
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Logging out...</title>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0;url=login.php">
</head>

<body>
    <script>
        // Clear the theme from localStorage on logout to prevent it from persisting
        localStorage.removeItem('theme');
        // This is a backup to ensure the user is redirected even if header() fails
        window.location.href = 'login.php';
    </script>
    <p>If you are not redirected automatically, <a href="login.php">click here</a>.</p>
</body>

</html>