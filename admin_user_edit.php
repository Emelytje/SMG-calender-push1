<?php
include 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

include 'header.php';

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_new = ($user_id === 0);

$error_text = "";
$info_text  = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username    = $_POST['username']    ?? '';
    $email       = $_POST['email']       ?? '';
    $first_name  = $_POST['first_name']  ?? '';
    $last_name   = $_POST['last_name']   ?? '';
    $role        = $_POST['role']        ?? 'user';
    $insured     = isset($_POST['insured']) ? 1 : 0;
    $password    = $_POST['password']    ?? '';

    if ($username === "") {
        $error_text = "Gebruikersnaam is verplicht.";
    }

    if ($is_new && $password === "") {
        $error_text = "Wachtwoord is verplicht voor nieuwe gebruiker.";
    }

    if ($error_text === "") {

        if ($is_new) {
            $sql = "
                INSERT INTO users (username, email, first_name, last_name, role, insured, password)
                VALUES (
                    '$username',
                    '$email',
                    '$first_name',
                    '$last_name',
                    '$role',
                    $insured,
                    '$password'
                )
            ";
            mysqli_query($db, $sql);

            $user_id = mysqli_insert_id($db);
            $is_new = false;
            $info_text = "Gebruiker aangemaakt.";

        } else {

            $sql = "
                UPDATE users SET
                    username   = '$username',
                    email      = '$email',
                    first_name = '$first_name',
                    last_name  = '$last_name',
                    role       = '$role',
                    insured    = $insured
                WHERE id = $user_id
            ";
            mysqli_query($db, $sql);

            $info_text = "Gebruiker opgeslagen.";
        }
    }
}

$user_row = [
    'username'   => '',
    'email'      => '',
    'first_name' => '',
    'last_name'  => '',
    'role'       => 'user',
    'insured'    => 0
];

if (!$is_new) {
    $result = mysqli_query($db, "SELECT * FROM users WHERE id = $user_id LIMIT 1");
    if ($result && mysqli_num_rows($result) === 1) {
        $user_row = mysqli_fetch_assoc($result);
    }
}
?>
<div class="card">
    <h2><?php echo $is_new ? "Nieuwe gebruiker" : "Gebruiker bewerken"; ?></h2>

    <?php if ($error_text !== "") { ?>
        <div class="error"><?php echo $error_text; ?></div>
    <?php } ?>

    <?php if ($info_text !== "") { ?>
        <div class="success"><?php echo $info_text; ?></div>
    <?php } ?>

    <form method="post">

        <label for="username">Gebruikersnaam</label>
        <input type="text" id="username" name="username" value="<?php echo $user_row['username']; ?>">

        <label for="first_name">Voornaam</label>
        <input type="text" id="first_name" name="first_name" value="<?php echo $user_row['first_name']; ?>">

        <label for="last_name">Achternaam</label>
        <input type="text" id="last_name" name="last_name" value="<?php echo $user_row['last_name']; ?>">

        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" value="<?php echo $user_row['email']; ?>">

        <label for="role">Rol</label>
        <select id="role" name="role">
            <option value="user"  <?php if ($user_row['role'] === 'user') echo "selected"; ?>>Gebruiker</option>
            <option value="admin" <?php if ($user_row['role'] === 'admin') echo "selected"; ?>>Admin</option>
        </select>

        <label>
            <input type="checkbox" name="insured" <?php if ($user_row['insured']) echo "checked"; ?>>
            Verzekerd
        </label>

        <?php if ($is_new) { ?>
            <label for="password">Wachtwoord</label>
            <input type="password" id="password" name="password">
        <?php } else { ?>
            <p><em>Wachtwoord kan niet worden gewijzigd.</em></p>
        <?php } ?>

        <button type="submit">Opslaan</button>
        <a class="btn btn-small" href="admin_users.php">Terug</a>
    </form>
</div>

<?php include 'footer.php'; ?>
