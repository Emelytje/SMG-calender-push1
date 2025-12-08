<?php


$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>SMG Stables</title>
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body>



<div class="app-container">
<?php if (isset($_SESSION['user_id'])) { ?>
    <aside class="sidebar">
        <div class="sidebar-logo">
            <img src="assets/img/smg-logo.png" alt="SMG Stables">
            <span>SMG Stables</span>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php"<?php if ($current_page === 'index.php') { echo ' class="active"'; } ?>>Dashboard</a>
            <a href="calendar.php"<?php if ($current_page === 'calendar.php') { echo ' class="active"'; } ?>>Kalender</a>
            <a href="profile.php"<?php if ($current_page === 'profile.php') { echo ' class="active"'; } ?>>Mijn profiel</a>
            <a href="change_password.php"<?php if ($current_page === 'change_password.php') { echo ' class="active"'; } ?>>Wachtwoord wijzigen</a>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') { ?>
                <span class="sidebar-section-title">Beheer</span>
                <a href="admin_users.php"<?php if ($current_page === 'admin_users.php' || $current_page === 'admin_user_edit.php') { echo ' class="active"'; } ?>>Gebruikers beheren</a>
                <a href="admin_reservations.php"<?php if ($current_page === 'admin_reservations.php') { echo ' class="active"'; } ?>>Reserveringen (per dag)</a>
                <a href="admin_email_uninsured.php"<?php if ($current_page === 'admin_email_uninsured.php') { echo ' class="active"'; } ?>>Mail niet-verzekerden</a>
            <?php } ?>
        </nav>
        <div class="sidebar-bottom">
            <span>
                Ingelogd als<br>
                <?php if (isset($_SESSION['username'])) { echo $_SESSION['username']; } ?>
            </span><br>
            <a class="btn btn-small" href="logout.php">Uitloggen</a>
        </div>
    </aside>
<?php } ?>

    <main class="main-content<?php if (isset($_SESSION['user_id'])) { echo ' with-sidebar'; } ?>">
        <header class="topbar">
            <h1>SMG Stables reserveringssysteem</h1>
        </header>
        <section class="content">
