<?php
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
            <a href="index.php">Dashboard</a>
            <a href="calendar.php">Kalender</a>
            <a href="profile.php">Mijn profiel</a>
            <a href="change_password.php">Wachtwoord wijzigen</a>

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') { ?>
                <span class="sidebar-section-title">Beheer</span>
                <a href="admin_users.php">Gebruikers beheren</a>
                <a href="admin_reservations.php">Reserveringen (per dag)</a>
                <a href="admin_email_uninsured.php">Mail niet-verzekerden</a>
            <?php } ?>
        </nav>
        <div class="sidebar-bottom">
            <span>
                Ingelogd als<br>
                <?php
                if (isset($_SESSION['username'])) {
                    echo $_SESSION['username'];
                }
                ?>
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
