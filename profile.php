<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$info  = "";
$error = "";

if (isset($_POST['email']) || isset($_POST['phone']) || isset($_POST['first_name']) || isset($_POST['last_name'])) {

    $email      = isset($_POST['email']) ? $_POST['email'] : "";
    $phone      = isset($_POST['phone']) ? $_POST['phone'] : "";
    $first_name = isset($_POST['first_name']) ? $_POST['first_name'] : "";
    $last_name  = isset($_POST['last_name']) ? $_POST['last_name'] : "";

    $sql = "UPDATE users SET 
                email = '" . $email . "',
                phone = '" . $phone . "',
                first_name = '" . $first_name . "',
                last_name = '" . $last_name . "'
            WHERE id = " . $user_id;

    if (mysqli_query($db, $sql)) {
        $info = "Gegevens opgeslagen.";
    } else {
        $error = "Er ging iets mis bij het opslaan.";
    }
}

$sql_user = "SELECT username, email, phone, first_name, last_name 
             FROM users 
             WHERE id = " . $user_id . " 
             LIMIT 1";
$res_user = mysqli_query($db, $sql_user);
$user = mysqli_fetch_assoc($res_user);

include 'header.php';
?>

<div class="card">
    <h2>Mijn profiel</h2>

    <?php if ($info !== "") { ?>
        <div class="success"><?php echo $info; ?></div>
    <?php } ?>

    <?php if ($error !== "") { ?>
        <div class="error"><?php echo $error; ?></div>
    <?php } ?>

    <form method="post">
        <label>Gebruikersnaam</label>
        <input type="text" value="<?php echo $user['username']; ?>" disabled>

        <label>Voornaam</label>
        <input type="text" name="first_name" value="<?php echo $user['first_name']; ?>">

        <label>Achternaam</label>
        <input type="text" name="last_name" value="<?php echo $user['last_name']; ?>">

        <label>E-mail</label>
        <input type="email" name="email" value="<?php echo $user['email']; ?>">

        <label>Telefoon</label>
        <input type="text" name="phone" value="<?php echo $user['phone']; ?>">

        <button type="submit" style="margin-top:1rem;">Opslaan</button>
    </form>
</div>

<?php
include 'footer.php';
?>
