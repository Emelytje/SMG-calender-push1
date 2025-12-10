<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: calendar.php');
    exit;
}

$type          = isset($_POST['type']) ? $_POST['type'] : 'piste';
$track_id      = isset($_POST['track_id']) ? (int)$_POST['track_id'] : 0;
$start_in      = isset($_POST['start']) ? $_POST['start'] : '';
$end_in        = isset($_POST['end']) ? $_POST['end'] : '';
$rider_name    = isset($_POST['rider_name']) ? $_POST['rider_name'] : '';
$notes         = isset($_POST['notes']) ? $_POST['notes'] : '';
$instructor_id = isset($_POST['instructor_id']) ? (int)$_POST['instructor_id'] : 0;

if ($track_id === 0 || $start_in === '') {
    echo 'Vul alle verplichte velden in. <a href="calendar.php">Terug</a>';
    exit;
}

// start: "2025-12-04T10:00"
$start_db = str_replace('T', ' ', $start_in) . ':00';

if ($end_in === '') {
    // standaard 30 minuten extra
    $ts = strtotime(str_replace('T', ' ', $start_in));
    $ts_end = $ts + 30 * 60;
    $end_db = date('Y-m-d H:i:s', $ts_end);
} else {
    $end_db = str_replace('T', ' ', $end_in) . ':00';
}

$user_id = (int)$_SESSION['user_id'];

$safe_start = mysqli_real_escape_string($db, $start_db);
$safe_end   = mysqli_real_escape_string($db, $end_db);
$safe_notes = mysqli_real_escape_string($db, $notes);
$safe_rider = mysqli_real_escape_string($db, $rider_name);

if ($type === 'blocked') {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        echo 'Alleen admin kan blokkeren. <a href="calendar.php">Terug</a>';
        exit;
    }

    // overlap met andere blokkades
    $sql_check_b = "SELECT COUNT(*) AS c
                    FROM blocked_times
                    WHERE track_id = " . $track_id . "
                      AND NOT (end_time <= '" . $safe_start . "' OR start_time >= '" . $safe_end . "')";
    $res_b = mysqli_query($db, $sql_check_b);
    $row_b = mysqli_fetch_assoc($res_b);
    if ((int)$row_b['c'] > 0) {
        echo 'Er is al een blokkade in dit tijdslot. <a href="calendar.php">Terug</a>';
        exit;
    }

    // overlap met reserveringen
    $sql_check_r = "SELECT COUNT(*) AS c
                    FROM reservations
                    WHERE track_id = " . $track_id . "
                      AND status = 'active'
                      AND NOT (end_time <= '" . $safe_start . "' OR start_time >= '" . $safe_end . "')";
    $res_r = mysqli_query($db, $sql_check_r);
    $row_r = mysqli_fetch_assoc($res_r);
    if ((int)$row_r['c'] > 0) {
        echo 'Er zijn al reserveringen in dit tijdslot. <a href="calendar.php">Terug</a>';
        exit;
    }

    // blokkade opslaan
    $sql = "INSERT INTO blocked_times (track_id, start_time, end_time, reason)
            VALUES (" . $track_id . ", '" . $safe_start . "', '" . $safe_end . "', '" . $safe_notes . "')";
    mysqli_query($db, $sql);

    header('Location: calendar.php');
    exit;
}

// hier: normale reservering (piste/les)

// overlap met andere reserveringen
$sql_check_r2 = "SELECT COUNT(*) AS c
                 FROM reservations
                 WHERE track_id = " . $track_id . "
                   AND status = 'active'
                   AND NOT (end_time <= '" . $safe_start . "' OR start_time >= '" . $safe_end . "')";
$res2 = mysqli_query($db, $sql_check_r2);
$row2 = mysqli_fetch_assoc($res2);
if ((int)$row2['c'] > 0) {
    echo 'Er is al iets gereserveerd in dit tijdslot. <a href="calendar.php">Terug</a>';
    exit;
}

// overlap met blokkades
$sql_check_b2 = "SELECT COUNT(*) AS c
                 FROM blocked_times
                 WHERE track_id = " . $track_id . "
                   AND NOT (end_time <= '" . $safe_start . "' OR start_time >= '" . $safe_end . "')";
$res_b2 = mysqli_query($db, $sql_check_b2);
$row_b2 = mysqli_fetch_assoc($res_b2);
if ((int)$row_b2['c'] > 0) {
    echo 'Dit tijdslot is geblokkeerd. <a href="calendar.php">Terug</a>';
    exit;
}

$sql = "INSERT INTO reservations (user_id, track_id, start_time, end_time, type, notes, rider_name, instructor_id, status)
        VALUES (" . $user_id . ", " . $track_id . ", '" . $safe_start . "', '" . $safe_end . "',
                '" . $type . "', '" . $safe_notes . "', '" . $safe_rider . "', " . $instructor_id . ", 'active')";
mysqli_query($db, $sql);

header('Location: calendar.php');
exit;
