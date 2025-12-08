<?php
include 'config.php';

// Alleen ingelogde gebruikers
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'header.php';

// Totaal gebruikers
$total_users = 0;
$result = mysqli_query($db, "SELECT COUNT(*) AS c FROM users");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $total_users = (int)$row['c'];
}

// Verzekerden
$insured_users = 0;
$result = mysqli_query($db, "SELECT COUNT(*) AS c FROM users WHERE insured = 1");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $insured_users = (int)$row['c'];
}

$uninsured_users = $total_users - $insured_users;

// Reserveringen vandaag
$today = date('Y-m-d');
$safe_today = mysqli_real_escape_string($db, $today);
$today_reservations = 0;

$result = mysqli_query($db, "SELECT COUNT(*) AS c FROM reservations WHERE DATE(start_time) = '" . $safe_today . "'");
if ($result) {
    $row = mysqli_fetch_assoc($result);
    $today_reservations = (int)$row['c'];
}

// Volgende event
$next_event = null;
$result = mysqli_query($db, "SELECT title, event_date FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 1");
if ($result && mysqli_num_rows($result) === 1) {
    $next_event = mysqli_fetch_assoc($result);
}
?>
<div class="grid">
    <div class="card">
        <h2>Overzicht</h2>
          <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') { ?>
        <p><strong>Totaal gebruikers:</strong> <?php echo $total_users; ?></p>
        <p><strong>Verzekerden:</strong> <?php echo $insured_users; ?></p>
        <p><strong>Niet-verzekerden:</strong> <?php echo $uninsured_users; ?></p><?php } ?>
        <p><strong>Reserveringen vandaag:</strong> <?php echo $today_reservations; ?></p>
    </div>

    <div class="card">
        <h2>Volgend evenement</h2>
        <?php if ($next_event !== null) { ?>
            <p><strong><?php echo $next_event['title']; ?></strong></p>
            <p>Datum: <?php echo $next_event['event_date']; ?></p>
        <?php } else { ?>
            <p>Er zijn geen toekomstige evenementen gevonden.</p>
        <?php } ?>
    </div>

    <div class="card">
        <h2>Snel naar</h2>
        <ul>
            <li><a href="profile.php">Mijn profiel</a></li>
            <li><a href="change_password.php">Wachtwoord wijzigen</a></li>
            <li><a href="calendar.php">Kalender</a></li>
            <?php if ($_SESSION['role'] === 'admin') { ?>
                <li><a href="admin_users.php">Gebruikers beheren</a></li>
                <li><a href="admin_reservations.php">Reserveringen beheren</a></li>
                <li><a href="admin_email_uninsured.php">Mail niet-verzekerden</a></li>
            <?php } ?>
        </ul>
    </div>
</div>
<?php
include 'footer.php';
