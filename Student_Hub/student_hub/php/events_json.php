<?php
declare(strict_types=1);

/* =========================================================
   PUBLIC EVENTS FEED (read-only JSON for pages/events.html)
   Same field names as data/events.json, plus status/poster.
   Text is HTML-escaped here because events.js writes it with
   innerHTML.
   ========================================================= */

require_once __DIR__ . '/db_mysqli.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

try {
    $stmt = $conn->prepare(
        'SELECT id, title, description, event_date, event_time, location, category, status, poster
         FROM events
         ORDER BY event_date ASC, event_time ASC'
    );
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $out = [];
    foreach ($rows as $r) {
        $ts = strtotime($r['event_date'] . ' ' . $r['event_time']);
        $out[] = [
            'id'          => (int) $r['id'],
            'date'        => date('d', $ts),
            'month'       => strtoupper(date('M', $ts)),
            'sortDate'    => date('Y-m-d H:i', $ts),
            'title'       => h($r['title']),
            'description' => h($r['description']),
            'location'    => h($r['location']),
            'time'        => date('g:i A', $ts),
            'category'    => h($r['category']),
            'status'      => h($r['status']),
            'poster'      => $r['poster'] ? '../uploads/events/' . rawurlencode($r['poster']) : '',
        ];
    }
    echo json_encode($out);
} catch (mysqli_sql_exception $ex) {
    error_log('events_json failed: ' . $ex->getMessage());
    http_response_code(500);
    echo json_encode(['message' => 'Could not load events.']);
}
