<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'header.php';

$user_id = (int)$_SESSION['user_id'];
$error_text = '';
$info_text = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['current_password'])) {
        $current = $_POST['current_password'];
    } else {
        $current = '';
    }

    if (isset($_POST['new_password'])) {
        $new = $_POST['new_password'];
    } else {
        $new = '';
    }

    if (isset($_POST['repeat_password'])) {
        $repeat = $_POST['repeat_password'];
    } else {
        $repeat = '';
    }

    if ($new === '' || $repeat === '') {
        $error_text = 'Vul alle velden in.';
    } elseif ($new !== $repeat) {
        $error_text = 'Nieuwe wachtwoorden zijn niet gelijk.';
    } elseif (strlen($new) < 4) {
        $error_text = 'Nieuwe wachtwoord is te kort.';
    } else {
        $result = mysqli_query($db, "SELECT password FROM users WHERE id = " . $user_id . " LIMIT 1");
        if ($result && mysqli_num_rows($result) === 1) {
            $row = mysqli_fetch_assoc($result);
            if ($row['password'] !== $current) {
                $error_text = 'Huidig wachtwoord klopt niet.';
            } else {
                $safe_pass = mysqli_real_escape_string($db, $new);
                mysqli_query($db, "UPDATE users SET password = '" . $safe_pass . "' WHERE id = " . $user_id);
                $info_text = 'Wachtwoord bijgewerkt.';
            }
        } else {
            $error_text = 'Gebruiker niet gevonden.';
        }
    }
}
?>
<div class="card">
    <h2>Wachtwoord wijzigen</h2>

    <?php if ($error_text !== '') { ?>
        <div class="error"><?php echo $error_text; ?></div>
    <?php } ?>

    <?php if ($info_text !== '') { ?>
        <div class="success"><?php echo $info_text; ?></div>
    <?php } ?>

    <form method="post">
        <label for="current_password">Huidig wachtwoord</label>
        <input type="password" id="current_password" name="current_password">

        <label for="new_password">Nieuw wachtwoord</label>
        <input type="password" id="new_password" name="new_password">

        <label for="repeat_password">Herhaal nieuw wachtwoord</label>
        <input type="password" id="repeat_password" name="repeat_password">

        <button type="submit">Opslaan</button>
    </form>
</div>
<?php
include 'footer.php';
