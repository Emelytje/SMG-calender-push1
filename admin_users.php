<?php
include 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

include 'header.php';

$error_text = '';
$info_text = '';

if (isset($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];

    if ($delete_id === (int)$_SESSION['user_id']) {
        $error_text = 'Je kunt je eigen account niet verwijderen.';
    } else {
        mysqli_query($db, "DELETE FROM users WHERE id = " . $delete_id);
        $info_text = 'Gebruiker verwijderd.';
    }
}

$user_list = array();
$result = mysqli_query($db, "SELECT * FROM users ORDER BY username");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $user_list[] = $row;
    }
}
?>
<div class="card">
    <h2>Gebruikers</h2>

    <?php if ($error_text !== '') { ?>
        <div class="error"><?php echo $error_text; ?></div>
    <?php } ?>

    <?php if ($info_text !== '') { ?>
        <div class="success"><?php echo $info_text; ?></div>
    <?php } ?>

    <p><a class="btn" href="admin_user_edit.php?id=0">Nieuwe gebruiker</a></p>

    <table class="table">
        <thead>
        <tr>
            <th>ID</th>
            <th>Gebruikersnaam</th>
            <th>Naam</th>
            <th>E-mail</th>
            <th>Rol</th>
            <th>Verzekerd</th>
            <th>Acties</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($user_list as $u) { ?>
            <tr>
                <td><?php echo $u['id']; ?></td>
                <td><?php echo $u['username']; ?></td>
                <td><?php echo $u['first_name'] . ' ' . $u['last_name']; ?></td>
                <td><?php echo $u['email']; ?></td>
                <td><?php echo $u['role']; ?></td>
                <td><?php if ($u['insured']) { echo 'Ja'; } else { echo 'Nee'; } ?></td>
                <td>
                    <a class="btn btn-small" href="admin_user_edit.php?id=<?php echo $u['id']; ?>">Bewerken</a>
                    <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']) { ?>
                        <a class="btn btn-small" href="admin_users.php?delete=<?php echo $u['id']; ?>" onclick="return confirm('Weet je het zeker?');">Verwijderen</a>
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>
<?php
include 'footer.php';
