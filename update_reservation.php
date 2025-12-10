<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}


include 'header.php';

if (isset($_GET['remove'])) {
    $remove_id = (int)$_GET['remove'];

    $res = mysqli_query($db, "SELECT user_id FROM reservations WHERE id = $remove_id LIMIT 1");
    if ($res && mysqli_num_rows($res) === 1) {
        $row = mysqli_fetch_assoc($res);

        $is_owner = ($row['user_id'] == $user_id);
        $is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

        if ($is_owner || $is_admin) {

            mysqli_query($db, "
                UPDATE reservations 
                SET status = 'inactive' 
                WHERE id = $remove_id
            ");

            header("Location: admin_reservations.php?date=" . urlencode($filter_date));
            exit;
        }
    }
}


$user_id = (int)$_SESSION['user_id'];
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

$filter_type = isset($_GET['type']) ? $_GET['type'] : 'all';

$safe_date = mysqli_real_escape_string($db, $filter_date);

$list = [];



$where = "DATE(r.start_time) = '" . $safe_date . "'";

if ($filter_type === 'piste') {
    $where .= " AND r.type = 'piste'";
} elseif ($filter_type === 'lesson') {
    $where .= " AND r.type = 'lesson'";
}

$sql = "SELECT r.*,
               t.name AS track_name,
               u.username AS user_name,
               i.username AS instructor_name
        FROM reservations r
        LEFT JOIN tracks t ON r.track_id = t.id
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN users i ON r.instructor_id = i.id
        WHERE r.status = 'active' AND $where
        ORDER BY r.start_time";

$res = mysqli_query($db, $sql);
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $row['kind'] = 'reservation';
        $list[] = $row;
    }
}


if ($filter_type === 'all' || $filter_type === 'blocked') {

    $sql_b = "SELECT b.*, t.name AS track_name
              FROM blocked_times b
              LEFT JOIN tracks t ON b.track_id = t.id
              WHERE DATE(b.start_time) = '" . $safe_date . "'
              ORDER BY b.start_time";

    $res_b = mysqli_query($db, $sql_b);
    if ($res_b) {
        while ($row = mysqli_fetch_assoc($res_b)) {
            $row['kind'] = 'blocked';
            $list[] = $row;
        }
    }
}

usort($list, function($a, $b) {
    return strcmp($a['start_time'], $b['start_time']);
});
?>
<div class="card">
    <h2>Reserveringen per dag</h2>

    <form method="get" style="display:flex;flex-wrap:wrap;gap:0.5rem;align-items:flex-end;">
        <div>
            <label>Datum</label><br>
            <input type="date" name="date" value="<?php echo $filter_date; ?>">
        </div>
        <div>
            <label>Type</label><br>
            <select name="type">
                <option value="all"<?php if ($filter_type === 'all') echo ' selected'; ?>>Alles</option>
                <option value="piste"<?php if ($filter_type === 'piste') echo ' selected'; ?>>Piste</option>
                <option value="lesson"<?php if ($filter_type === 'lesson') echo ' selected'; ?>>Les</option>
                <option value="blocked"<?php if ($filter_type === 'blocked') echo ' selected'; ?>>Geblokkeerd</option>
            </select>
        </div>
        <div>
            <button type="submit">Filter</button>
        </div>
    </form>

    <?php if (count($list) === 0) { ?>
        <p>Geen blokken gevonden voor deze dag.</p>
    <?php } else { ?>
        <table class="table">
            <thead>
            <tr>
                <th>Tijd</th>
                <th>Type</th>
                <th>Piste</th>
                <th>Ruiter</th>
                <th>Geboekt door</th>
                <th>Lesgever</th>
                <th>Notitie</th>
                <th>Acties</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($list as $row) { ?>

                <tr>
                    <td>
                        <?php echo $row['start_time']; ?><br>
                        <?php echo $row['end_time']; ?>
                    </td>

                    <td>
                        <?php
                        if ($row['kind'] === 'blocked') {
                            echo '<span style="color:#ef4444;">Geblokkeerd</span>';
                        } elseif ($row['type'] === 'lesson') {
                            echo '<span style="color:#ec4899;">Les</span>';
                        } else {
                            echo '<span style="color:#3b82f6;">Piste</span>';
                        }
                        ?>
                    </td>

                    <td><?php echo $row['track_name'] ?? ''; ?></td>
                    <td><?php echo $row['rider_name'] ?? ''; ?></td>
                    <td><?php echo $row['user_name'] ?? ''; ?></td>
                    <td><?php echo $row['instructor_name'] ?? ''; ?></td>
                    <td><?php echo $row['notes'] ?? ''; ?></td>

                    <td>
                        <?php
                        if ($is_admin) {
                            echo '<a href="edit_reservation.php?id=' . $row['id'] . '">Bewerken</a> | ';
                            echo '<a href="admin_reservation.php?remove=' . $row['id'] . '" onclick="return confirm(\'Weet je het zeker?\')">Verwijderen</a>';
                        }

                        else if ($row['kind'] === 'reservation' && $row['user_id'] == $user_id) {
                            echo '<a href="edit_reservation.php?id=' . $row['id'] . '">Bewerken</a> | ';
                            echo '<a href="admin_reservation.php?remove=' . $row['id'] . '" onclick="return confirm(\'Weet je het zeker?\')">Verwijderen</a>';
                        }

                        else {
                            echo '-';
                        }
                        ?>
                    </td>
                </tr>

            <?php } ?>
            </tbody>
        </table>
    <?php } ?>
</div>
<?php include 'footer.php'; ?>
