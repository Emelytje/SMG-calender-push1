<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include 'header.php';

$user_id = (int)$_SESSION['user_id'];
$role    = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';

// Actieve reserveringen
$reservations = array();

$sql = "SELECT r.*,
               t.name AS track_name,
               u.username AS user_name,
               i.username AS instructor_name
        FROM reservations r
        LEFT JOIN tracks t ON r.track_id = t.id
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN users i ON r.instructor_id = i.id
        WHERE r.status = 'active'
        ORDER BY r.start_time";

$result = mysqli_query($db, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $reservations[] = $row;
    }
}

// Geblokkeerde tijden (aparte tabel)
$blocked = array();

$sql2 = "SELECT b.*, t.name AS track_name
         FROM blocked_times b
         LEFT JOIN tracks t ON b.track_id = t.id
         ORDER BY b.start_time";

$result2 = mysqli_query($db, $sql2);
if ($result2) {
    while ($row = mysqli_fetch_assoc($result2)) {
        $blocked[] = $row;
    }
}

// Events voor JS
$events = array();

// Reservaties
for ($i = 0; $i < count($reservations); $i++) {
    $r = $reservations[$i];

    $title = $r['track_name'];

    if ($r['type'] === 'lesson') {
        if ($r['instructor_name'] !== null && $r['instructor_name'] !== '') {
            $title = 'Les ' . $r['instructor_name'] . ' - ' . $r['track_name'];
        } else {
            $title = 'Les - ' . $r['track_name'];
        }
    } else {
        $title = 'Piste - ' . $r['track_name'];
    }

    $color = '#3b82f6'; // piste
    if ($r['type'] === 'lesson') {
        $color = '#ec4899'; // les
    }

    $notes = $r['notes'];
    if ($notes === null) { $notes = ''; }

    $rider = $r['rider_name'];
    if ($rider === null) { $rider = ''; }

    $events[] = array(
        'id' => 'r' . $r['id'],
        'title' => $title,
        'start' => $r['start_time'],
        'end'   => $r['end_time'],
        'backgroundColor' => $color,
        'borderColor'     => $color,
        'extendedProps'   => array(
            'kind'          => 'reservation',
            'type'          => $r['type'],
            'notes'         => $notes,
            'rider_name'    => $rider,
            'user_name'     => $r['user_name'],
            'track_id'      => $r['track_id'],
            'user_id'       => $r['user_id'],
            'instructor_id' => $r['instructor_id'],
        ),
    );
}

// Blokkades
for ($i = 0; $i < count($blocked); $i++) {
    $b = $blocked[$i];

    $title = 'Geblokkeerd - ' . $b['track_name'];
    $color = '#ef4444';

    $notes = '';
    if (isset($b['notes']) && $b['notes'] !== null) {
        $notes = $b['notes'];
    }

    $events[] = array(
        'id' => 'b' . $b['id'],
        'title' => $title,
        'start' => $b['start_time'],
        'end'   => $b['end_time'],
        'backgroundColor' => $color,
        'borderColor'     => $color,
        'extendedProps'   => array(
            'kind'  => 'blocked',
            'notes' => $notes,
        ),
    );
}

$json_events = json_encode($events);
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.14/index.global.min.css">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.14/index.global.min.js"></script>

<div class="card" style="background: transparent; box-shadow: none;">
    <h2>Kalender</h2>
    <p>
        <span style="background:#3b82f6;color:#fff;padding:2px 6px;border-radius:4px;">Piste (blauw)</span>
        <span style="background:#ec4899;color:#fff;padding:2px 6px;border-radius:4px;">Les (roze)</span>
        <span style="background:#ef4444;color:#fff;padding:2px 6px;border-radius:4px;">Geblokkeerd (rood)</span>
    </p>
</div>

<div class="calendar-layout">

    <div class="calendar-form card">
        <h3>Nieuwe / aangepaste reservering</h3>
        <p style="font-size:0.9rem;">Tip: klik of sleep in de kalender rechts. De tijden worden hier automatisch ingevuld (standaard +30 minuten).</p>
        <form method="post" action="save_reservation.php">
            <!-- verborgen veld voor bestaande reservatie (0 = nieuw) -->
            <input type="hidden" name="reservation_id" id="reservation_id" value="0">

            <label>Type</label>
            <select name="type" id="form_type">
                <option value="piste">Piste</option>
                <option value="lesson">Les</option>
                <option value="blocked">Geblokkeerd (alleen admin)</option>
            </select>

            <label>Piste</label>
            <select name="track_id" id="form_track">
                <?php
                $tracks_res = mysqli_query($db, "SELECT id, name FROM tracks ORDER BY name");
                while ($t = mysqli_fetch_assoc($tracks_res)) {
                    echo '<option value="'.$t['id'].'">'.$t['name'].'</option>';
                }
                ?>
            </select>

            <label>Startdatum-tijd</label>
            <input type="datetime-local" name="start" id="form_start">

            <label>Einddatum-tijd</label>
            <input type="datetime-local" name="end" id="form_end">

            <label>Naam ruiter (als je voor iemand anders boekt)</label>
            <input type="text" name="rider_name" id="form_rider">

            <label>Notitie</label>
            <input type="text" name="notes" id="form_notes">

            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') { ?>
                <label>Lesgever (bij les)</label>
                <select name="instructor_id" id="form_instructor">
                    <option value="0">Geen / niet van toepassing</option>
                    <?php
                    $inst_res = mysqli_query($db, "SELECT id, username FROM users WHERE role = 'instructor' OR role = 'admin'");
                    while ($u = mysqli_fetch_assoc($inst_res)) {
                        echo '<option value="'.$u['id'].'">'.$u['username'].'</option>';
                    }
                    ?>
                </select>
            <?php } else { ?>
                <input type="hidden" name="instructor_id" id="form_instructor" value="0">
            <?php } ?>

            <button type="submit" style="margin-top:1rem;">Opslaan</button>
        </form>
    </div>

    <div class="calendar-view">
        <div id="calendar"></div>
    </div>

</div>

<script>
    var events = <?php echo $json_events ? $json_events : '[]'; ?>;
    var currentUserId   = <?php echo (int)$user_id; ?>;
    var currentUserRole = "<?php echo htmlspecialchars($role, ENT_QUOTES); ?>";

    document.addEventListener('DOMContentLoaded', function () {
        var calendarEl = document.getElementById('calendar');

        if (typeof FullCalendar === 'undefined') {
            calendarEl.innerHTML = 'FullCalendar kon niet geladen worden. Controleer internet / adblock.';
            return;
        }

        function pad2(n) {
            return n.toString().padStart(2, '0');
        }

        function toLocalInputValue(dateObj) {
            // voor datetime-local: YYYY-MM-DDTHH:MM
            var year  = dateObj.getFullYear();
            var month = pad2(dateObj.getMonth() + 1);
            var day   = pad2(dateObj.getDate());
            var hour  = pad2(dateObj.getHours());
            var min   = pad2(dateObj.getMinutes());
            return year + '-' + month + '-' + day + 'T' + hour + ':' + min;
        }

        function toMysqlDateTime(dateObj) {
            var year  = dateObj.getFullYear();
            var month = pad2(dateObj.getMonth() + 1);
            var day   = pad2(dateObj.getDate());
            var hour  = pad2(dateObj.getHours());
            var min   = pad2(dateObj.getMinutes());
            var sec   = pad2(dateObj.getSeconds());
            return year + '-' + month + '-' + day + ' ' + hour + ':' + min + ':' + sec;
        }

        // helpers voor formulier
        function resetFormForNew() {
            var idInput    = document.getElementById('reservation_id');
            var typeInput  = document.getElementById('form_type');
            var startInput = document.getElementById('form_start');
            var endInput   = document.getElementById('form_end');
            var riderInput = document.getElementById('form_rider');
            var notesInput = document.getElementById('form_notes');
            var trackInput = document.getElementById('form_track');
            var instrInput = document.getElementById('form_instructor');

            if (idInput)    idInput.value = '0'; // nieuw
            if (typeInput)  typeInput.value = 'piste';
            if (startInput) startInput.value = '';
            if (endInput)   endInput.value = '';
            if (riderInput) riderInput.value = '';
            if (notesInput) notesInput.value = '';
            if (trackInput && trackInput.options.length > 0) {
                trackInput.selectedIndex = 0;
            }
            if (instrInput) instrInput.value = '0';
        }

        var calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            locale: 'nl',
            firstDay: 1,
            slotMinTime: '06:00:00',
            slotMaxTime: '22:00:00',
            editable: true,
            selectable: true,   // belangrijk: klik/sleep om nieuw blok te maken
            events: events,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'timeGridWeek,timeGridDay'
            },
            select: function(info) {
                // klik/sleep in kalender → formulier vullen voor NIEUWE reservering
                var start = info.start;
                var end = info.end;

                if (!end) {
                    // geen range: standaard 30 minuten na start
                    end = new Date(start.getTime() + 30 * 60000);
                }

                var startStr = toLocalInputValue(start);
                var endStr   = toLocalInputValue(end);

                resetFormForNew();

                var startInput = document.getElementById('form_start');
                var endInput   = document.getElementById('form_end');

                if (startInput && endInput) {
                    startInput.value = startStr;
                    endInput.value   = endStr;
                    startInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }

                calendar.unselect();
            },
            eventDrop: function (info) {
                // slepen → tijd aanpassen (rechten worden gecontroleerd in update_reservation.php)
                sendUpdate(info.event);
            },
            eventResize: function (info) {
                // groter/kleiner slepen
                sendUpdate(info.event);
            },
            eventClick: function(info) {
                var e     = info.event;
                var extra = e.extendedProps;
                var msg   = '';

                msg += 'Blok: ' + e.title + '\n';
                msg += 'Van: ' + e.start.toLocaleString() + '\n';
                if (e.end) {
                    msg += 'Tot: ' + e.end.toLocaleString() + '\n';
                }
                if (extra && extra.rider_name) {
                    msg += 'Ruiter: ' + extra.rider_name + '\n';
                }
                if (extra && extra.user_name) {
                    msg += 'Geboekt door: ' + extra.user_name + '\n';
                }
                if (extra && extra.notes) {
                    msg += 'Notitie: ' + extra.notes + '\n';
                }
                if (extra && extra.kind === 'blocked') {
                    msg += '(Geblokkeerde tijd)\n';
                }

                // Mag deze gebruiker dit blok bewerken?
                var canEdit = false;

                if (extra.kind === 'reservation') {
                    var isAdmin      = (currentUserRole === 'admin');
                    var isOwner      = (extra.user_id == currentUserId);
                    var isInstructor = (extra.instructor_id && extra.instructor_id == currentUserId);

                    if (isAdmin || isOwner || isInstructor) {
                        canEdit = true;
                    }
                } else if (extra.kind === 'blocked') {
                    // blokkades alleen door admin te bewerken
                    if (currentUserRole === 'admin') {
                        canEdit = true;
                    }
                }

                alert(msg + (canEdit ? '\n\nJe kunt dit blok aanpassen via de kalender (slepen) of het formulier.' : '\n\nJe kunt deze reservering niet aanpassen (niet jouw reservering).'));

                // Alle bewerken via formulier alleen voor reserveringen, niet voor blocked
                if (extra.kind === 'reservation' && canEdit) {
                    // formulier vullen met gegevens
                    var idInput    = document.getElementById('reservation_id');
                    var typeInput  = document.getElementById('form_type');
                    var startInput = document.getElementById('form_start');
                    var endInput   = document.getElementById('form_end');
                    var riderInput = document.getElementById('form_rider');
                    var notesInput = document.getElementById('form_notes');
                    var trackInput = document.getElementById('form_track');
                    var instrInput = document.getElementById('form_instructor');

                    if (idInput)    idInput.value = e.id.substring(1); // 'r123' → '123'
                    if (typeInput && extra.type) typeInput.value = extra.type;
                    if (startInput) startInput.value = toLocalInputValue(e.start);
                    if (endInput)   {
                        if (e.end) {
                            endInput.value = toLocalInputValue(e.end);
                        } else {
                            endInput.value = toLocalInputValue(e.start);
                        }
                    }
                    if (riderInput) riderInput.value = extra.rider_name || '';
                    if (notesInput) notesInput.value = extra.notes || '';
                    if (trackInput && extra.track_id) trackInput.value = extra.track_id;
                    if (instrInput && extra.instructor_id) instrInput.value = extra.instructor_id;

                    // naar formulier scrollen
                    if (startInput) {
                        startInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            }
        });

        calendar.render();

        function sendUpdate(event) {
            var start = toMysqlDateTime(event.start);
            var end   = event.end ? toMysqlDateTime(event.end) : start;

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_reservation.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            var body = 'id=' + encodeURIComponent(event.id) +
                       '&start=' + encodeURIComponent(start) +
                       '&end=' + encodeURIComponent(end);

            xhr.send(body);
        }
    });
</script>

<?php
include 'footer.php';
?>
