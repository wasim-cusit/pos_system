<?php
session_start();
session_unset();
session_destroy();
header('Location: ../index.php');
exit();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="refresh" content="2;url=../index.php">
    <title>Logging out...</title>
</head>
<body>
    <div style="text-align:center;margin-top:50px;">
        <h2>You have been logged out.</h2>
        <p><a href="../index.php">Go to Login</a></p>
    </div>
</body>
</html> 