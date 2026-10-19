<?php
declare(strict_types=1);

/* =========================================================
   REGISTER FOR AN EVENT   POST php/api/register_event.php
   Body: { "event_id": 5 }      (any logged-in user)
   201 registered | 401 not logged in | 404 no such event
   409 already registered | 422 event is closed / bad input
   ========================================================= */

require_once __DIR__ . '/_common.php';

api_allow(['POST'], api_method());
api_require_login();
api_require_csrf();
api_release_session();

$body    = api_body();
$eventId = api_int($body['event_id'] ?? 0);
if ($eventId <= 0) {
    api_error(422, 'Choose a valid event.', ['event_id' => 'Event is required.']);
}
$userId = (int) $_SESSION['user_id'];

try {
    $stmt = $conn->prepare('SELECT id, title, status FROM events WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $eventId);
    $stmt->execute();
    $event = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$event) {
        api_error(404, 'Event not found.');
    }
    if (!in_array($event['status'], ['Upcoming', 'Ongoing'], true)) {
        api_error(422, 'Registration is closed: this event is ' . strtolower($event['status']) . '.');
    }

    $stmt = $conn->prepare('INSERT INTO registrations (user_id, event_id) VALUES (?, ?)');
    $stmt->bind_param('ii', $userId, $eventId);
    $stmt->execute();
    $newId = (int) $conn->insert_id;
    $stmt->close();

    audit_log($conn, 'REGISTER', 'registration', $newId, 'Registered for event "' . $event['title'] . '"');
    api_ok('You are registered for "' . $event['title'] . '".', ['registration_id' => $newId], [], 201);
} catch (mysqli_sql_exception $ex) {
    if ((int) $ex->getCode() === 1062) {
        api_error(409, 'You are already registered for this event.');
    }
    api_db_error($ex, 'register for the event');
}
