<?php
declare(strict_types=1);

/* =========================================================
   EVENTS API   (php/api/events.php)
   GET    ?id=5                      one event                 (public)
   GET    ?q=&category=&status=&page=&per_page=   list         (public)
   POST   JSON body                  create event              (admin)
   PUT    ?id=5 + JSON body          update event              (admin)
   DELETE ?id=5                      delete event + poster     (admin)
   Posters are uploaded on the Manage Events page; this API
   returns them and keeps them when an event is updated.
   ========================================================= */

require_once __DIR__ . '/_common.php';

const EV_CATEGORIES = ['Workshop', 'Competition', 'Seminar', 'Exhibition'];
const EV_STATUSES   = ['Upcoming', 'Ongoing', 'Completed', 'Cancelled'];
const EV_POSTER_DIR = __DIR__ . '/../../uploads/events/';

/** Row from the database -> JSON shape. */
function ev_out(array $r): array
{
    return [
        'id'          => (int) $r['id'],
        'title'       => $r['title'],
        'description' => $r['description'],
        'event_date'  => $r['event_date'],
        'event_time'  => substr((string) $r['event_time'], 0, 5),
        'location'    => $r['location'],
        'category'    => $r['category'],
        'status'      => $r['status'],
        'poster_url'  => $r['poster'] ? 'uploads/events/' . rawurlencode($r['poster']) : null,
    ];
}

function ev_fetch(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare(
        'SELECT id, title, description, event_date, event_time, location, category, status, poster
         FROM events WHERE id = ? LIMIT 1'
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

/** Validate before any database work. Returns [clean, errors]. */
function ev_validate(array $in): array
{
    $d = [
        'title'       => api_str($in['title'] ?? ''),
        'description' => api_str($in['description'] ?? ''),
        'event_date'  => api_str($in['event_date'] ?? ''),
        'event_time'  => api_str($in['event_time'] ?? ''),
        'location'    => api_str($in['location'] ?? ''),
        'category'    => api_str($in['category'] ?? ''),
        'status'      => api_str($in['status'] ?? ''),
    ];
    $err = [];

    if ($d['title'] === '') {
        $err['title'] = 'Title is required.';
    } elseif (mb_strlen($d['title']) > 150) {
        $err['title'] = 'Title must be at most 150 characters.';
    }

    if ($d['description'] === '') {
        $err['description'] = 'Description is required.';
    } elseif (mb_strlen($d['description']) > 2000) {
        $err['description'] = 'Description must be at most 2000 characters.';
    }

    $dt = DateTime::createFromFormat('!Y-m-d', $d['event_date']);
    if (!$dt || $dt->format('Y-m-d') !== $d['event_date']) {
        $err['event_date'] = 'Enter a valid date (YYYY-MM-DD).';
    }

    $t = substr($d['event_time'], 0, 5);
    $tm = DateTime::createFromFormat('!H:i', $t);
    if (!$tm || $tm->format('H:i') !== $t) {
        $err['event_time'] = 'Enter a valid time (HH:MM).';
    } else {
        $d['event_time'] = $tm->format('H:i:s');
    }

    if ($d['location'] === '') {
        $err['location'] = 'Location is required.';
    } elseif (mb_strlen($d['location']) > 150) {
        $err['location'] = 'Location must be at most 150 characters.';
    }

    if (!in_array($d['category'], EV_CATEGORIES, true)) {
        $err['category'] = 'Select a valid category.';
    }
    if (!in_array($d['status'], EV_STATUSES, true)) {
        $err['status'] = 'Select a valid status.';
    }
    return [$d, $err];
}

$method = api_method();
api_allow(['GET', 'POST', 'PUT', 'DELETE'], $method);

/* ---------- GET: public ---------- */
if ($method === 'GET') {
    api_release_session();
    $id = api_id();

    try {
        if ($id !== null) {
            $row = ev_fetch($conn, $id);
            if (!$row) {
                api_error(404, 'Event not found.');
            }
            api_ok('Event loaded.', ev_out($row));
        }

        $q        = api_str($_GET['q'] ?? '');
        $category = api_str($_GET['category'] ?? '');
        $status   = api_str($_GET['status'] ?? '');
        [$page, $per] = api_paging(8);

        $where = []; $types = ''; $params = [];
        if ($q !== '') {
            $like = api_like($q);
            $where[] = '(title LIKE ? OR location LIKE ? OR description LIKE ?)';
            $types  .= 'sss';
            array_push($params, $like, $like, $like);
        }
        if ($category !== '') {
            if (!in_array($category, EV_CATEGORIES, true)) { api_error(422, 'Unknown category filter.'); }
            $where[] = 'category = ?'; $types .= 's'; $params[] = $category;
        }
        if ($status !== '') {
            if (!in_array($status, EV_STATUSES, true)) { api_error(422, 'Unknown status filter.'); }
            $where[] = 'status = ?'; $types .= 's'; $params[] = $status;
        }
        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM events $whereSql");
        if ($params) { $stmt->bind_param($types, ...$params); }
        $stmt->execute();
        $total = (int) $stmt->get_result()->fetch_assoc()['total'];
        $stmt->close();

        $pages = max(1, (int) ceil($total / $per));
        if ($page > $pages) { $page = $pages; }
        $offset = ($page - 1) * $per;

        $stmt = $conn->prepare(
            "SELECT id, title, description, event_date, event_time, location, category, status, poster
             FROM events $whereSql
             ORDER BY event_date DESC, event_time DESC, id DESC
             LIMIT ? OFFSET ?"
        );
        $bind = [...$params, $per, $offset];
        $stmt->bind_param($types . 'ii', ...$bind);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        api_ok('Events loaded.', array_map('ev_out', $rows), [
            'meta' => ['page' => $page, 'per_page' => $per, 'total' => $total, 'pages' => $pages],
        ]);
    } catch (mysqli_sql_exception $ex) {
        api_db_error($ex, 'load events');
    }
}

/* ---------- everything below changes data: admin + CSRF ---------- */
api_require_admin();
api_require_csrf();
api_release_session();

try {
    /* ---------- POST: create ---------- */
    if ($method === 'POST') {
        [$d, $errors] = ev_validate(api_body());
        if ($errors) {
            api_error(422, 'Please correct the highlighted fields.', $errors);
        }

        $stmt = $conn->prepare(
            'INSERT INTO events (title, description, event_date, event_time, location, category, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->bind_param('sssssss', $d['title'], $d['description'], $d['event_date'],
            $d['event_time'], $d['location'], $d['category'], $d['status']);
        $stmt->execute();
        $newId = (int) $conn->insert_id;
        $stmt->close();

        audit_log($conn, 'CREATE', 'event', $newId, 'Added event "' . $d['title'] . '" (API)');
        api_ok('Event "' . $d['title'] . '" was added successfully.', ev_out(ev_fetch($conn, $newId)), [], 201);
    }

    /* ---------- PUT / DELETE need an id ---------- */
    $id = api_id();
    if ($id === null) {
        api_error(400, 'The id parameter is required (e.g. ?id=5).');
    }
    $existing = ev_fetch($conn, $id);
    if (!$existing) {
        api_error(404, 'Event not found. It may have been deleted already.');
    }

    /* ---------- PUT: update (poster is kept as it is) ---------- */
    if ($method === 'PUT') {
        [$d, $errors] = ev_validate(api_body());
        if ($errors) {
            api_error(422, 'Please correct the highlighted fields.', $errors);
        }

        $stmt = $conn->prepare(
            'UPDATE events SET title = ?, description = ?, event_date = ?, event_time = ?,
                    location = ?, category = ?, status = ? WHERE id = ?'
        );
        $stmt->bind_param('sssssssi', $d['title'], $d['description'], $d['event_date'],
            $d['event_time'], $d['location'], $d['category'], $d['status'], $id);
        $stmt->execute();
        $stmt->close();

        audit_log($conn, 'UPDATE', 'event', $id, 'Updated event "' . $d['title'] . '" (API)');
        api_ok('Event "' . $d['title'] . '" was updated successfully.', ev_out(ev_fetch($conn, $id)));
    }

    /* ---------- DELETE ---------- */
    $stmt = $conn->prepare('DELETE FROM events WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $deleted = $stmt->affected_rows;
    $stmt->close();

    if ($deleted !== 1) {
        api_error(404, 'No event was deleted.');
    }
    // remove the poster file too (only names this app generated)
    if ($existing['poster'] && preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $existing['poster'])) {
        $path = EV_POSTER_DIR . $existing['poster'];
        if (is_file($path)) { @unlink($path); }
    }
    audit_log($conn, 'DELETE', 'event', $id, 'Deleted event "' . $existing['title'] . '" (API)');
    api_ok('Event "' . $existing['title'] . '" was deleted successfully.', ['id' => $id]);
} catch (mysqli_sql_exception $ex) {
    if ((int) $ex->getCode() === 1062) {
        api_error(409, 'An event with this title already exists on this date.', ['title' => 'Duplicate title for this date.']);
    }
    api_db_error($ex, 'save the event');
}
