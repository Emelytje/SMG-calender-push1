<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

$raw_id = isset($_POST['id']) ? $_POST['id'] : '';
$start  = isset($_POST['start']) ? $_POST['start'] : '';
$end    = isset($_POST['end']) ? $_POST['end'] : '';

if ($raw_id === '' || $start === '' || $end === '') {
    exit;
}

// id begint met 'r' (reservation) of 'b' (blocked)
$kind = substr($raw_id, 0, 1);
$id   = (int)substr($raw_id, 1);

$user_id = (int)$_SESSION['user_id'];

$safe_start = mysqli_real_escape_string($db, $start);
$safe_end   = mysqli_real_escape_string($db, $end);

if ($kind === 'b') {
    // geblokkeerde tijd verschuiven (alleen admin)
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        exit;
    }

    $sql = "SELECT * FROM blocked_times WHERE id = " . $id . " LIMIT 1";
    $res = mysqli_query($db, $sql);
    if (!$res || mysqli_num_rows($res) === 0) {
        exit;
    }
    $row = mysqli_fetch_assoc($res);
    $track_id = (int)$row['track_id'];

    // overlap met andere blokkades
    $sql_b = "SELECT COUNT(*) AS c
              FROM blocked_times
              WHERE track_id = " . $track_id . "
                AND id <> " . $id . "
                AND NOT (end_time <= '" . $safe_start . "' OR start_time >= '" . $safe_end . "')";
    $res_b = mysqli_query($db, $sql_b);
    $row_b = mysqli_fetch_assoc($res_b);
    if ((int)$row_b['c'] > 0) {
        exit;
    }

    // overlap met reserveringen
    $sql_r = "SELECT COUNT(*) AS c
              FROM reservations
              WHERE track_id = " . $track_id . "
                AND status = 'active'
                AND NOT (end_time <= '" . $safe_start . "' OR start_time >= '" . $safe_end . "')";
    $res_r = mysqli_query($db, $sql_r);
    $row_r = mysqli_fetch_assoc($res_r);
    if ((int)$row_r['c'] > 0) {
        exit;
    }

    $sql_u = "UPDATE blocked_times
              SET start_time = '" . $safe_start . "',
                  end_time   = '" . $safe_end . "'
              WHERE id = " . $id;
    mysqli_query($db, $sql_u);
    exit;
}

// anders: normale reservering
$sql = "SELECT * FROM reservations WHERE id = " . $id . " LIMIT 1";
$res = mysqli_query($db, $sql);
if (!$res || mysqli_num_rows($res) === 0) {
    exit;
}
$row = mysqli_fetch_assoc($res);

$is_owner      = ((int)$row['user_id'] === $user_id);
$is_admin      = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
$is_instructor = ((int)$row['instructor_id'] === $user_id);

if (!$is_owner && !$is_admin && !$is_instructor) {
    exit;
}

$track_id = (int)$row['track_id'];

// overlap met andere reserveringen
$sql_r2 = "SELECT COUNT(*) AS c
           FROM reservations
           WHERE track_id = " . $track_id . "
             AND status = 'active'
             AND id <> " . $id . "
             AND NOT (end_time <= '" . $safe_start . "' OR start_time >= '" . $safe_end . "')";
$res2 = mysqli_query($db, $sql_r2);
$row2 = mysqli_fetch_assoc($res2);
if ((int)$row2['c'] > 0) {
    exit;
}

// overlap met blokkades
$sql_b2 = "SELECT COUNT(*) AS c
           FROM blocked_times
           WHERE track_id = " . $track_id . "
             AND NOT (end_time <= '" . $safe_start . "' OR start_time >= '" . $safe_end . "')";
$resb2 = mysqli_query($db, $sql_b2);
$rowb2 = mysqli_fetch_assoc($resb2);
if ((int)$rowb2['c'] > 0) {
    exit;
}

$sql_upd = "UPDATE reservations
            SET start_time = '" . $safe_start . "',
                end_time   = '" . $safe_end . "'
            WHERE id = " . $id;
mysqli_query($db, $sql_upd);

exit;
