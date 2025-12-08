<?php
include 'config.php';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error_text = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error_text = 'Vul gebruikersnaam en wachtwoord in.';
    } else {

        $sql = "
            SELECT id, username, password, role
            FROM users
            WHERE username = '$username'
            LIMIT 1
        ";

        $result = mysqli_query($db, $sql);

        if ($result && mysqli_num_rows($result) === 1) {
            $row = mysqli_fetch_assoc($result);

            if ($row['password'] === $password) {

                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];

                header('Location: index.php');
                exit;

            } else {
                $error_text = 'Onjuist wachtwoord.';
            }

        } else {
            $error_text = 'Onbekende gebruiker.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Inloggen - SMG Stables</title>
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
<div class="login-container">
    <h2>Inloggen</h2>

    <?php if ($error_text !== '') { ?>
        <div class="error"><?php echo $error_text; ?></div>
    <?php } ?>

    <form method="post">
        <label for="username">Gebruikersnaam</label>
        <input type="text" id="username" name="username">

        <label for="password">Wachtwoord</label>
        <input type="password" id="password" name="password">

        <button type="submit">Inloggen</button>
    </form>
</div>
</body>
</html>
