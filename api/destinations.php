<?php
/**
 * api/destinations.php
 * GET              → list all destinations
 * GET ?id=X        → single destination
 * GET ?search=Q    → search by title/description/subtitle
 * GET ?available=1 → only destinations with seats_available > 0
 * POST {action:'add'}    → add (admin only)
 * POST {action:'update'} → update (admin only)
 * POST {action:'delete'} → delete (admin only)
 */
require_once __DIR__ . '/config.php';

$db = db();

if (method() === 'GET') {
    $id             = isset($_GET['id'])        ? (int)$_GET['id']     : 0;
    $search         = isset($_GET['search'])    ? trim($_GET['search']) : '';
    $only_available = isset($_GET['available']) && $_GET['available'] === '1';

    // Single destination by ID
    if ($id > 0) {
        $stmt = $db->prepare("SELECT * FROM destinations WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) jsonError('Destination not found.', 404);
        $row['details'] = json_decode($row['details'] ?? '[]', true);
        jsonOut($row);
    }

    // Build query with optional search + availability filter
    $conditions = [];
    $bindTypes  = '';
    $bindValues = [];

    if ($search !== '') {
        $like          = '%' . $search . '%';
        $conditions[]  = '(title LIKE ? OR description LIKE ? OR subtitle LIKE ?)';
        $bindTypes    .= 'sss';
        $bindValues[]  = $like;
        $bindValues[]  = $like;
        $bindValues[]  = $like;
    }

    if ($only_available) {
        $conditions[] = 'seats_available > 0';
    }

    $sql = "SELECT * FROM destinations";
    if ($conditions) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }
    $sql .= " ORDER BY id ASC";

    if ($bindValues) {
        $stmt = $db->prepare($sql);
        $refs = [&$bindTypes];
        foreach ($bindValues as &$val) {
            $refs[] = &$val;
        }
        unset($val);
        call_user_func_array([$stmt, 'bind_param'], $refs);
        $stmt->execute();
        $res = $stmt->get_result();
        $stmt->close();
    } else {
        $res = $db->query($sql);
    }

    $list = [];
    while ($row = $res->fetch_assoc()) {
        $row['details'] = json_decode($row['details'] ?? '[]', true);
        $list[] = $row;
    }
    jsonOut($list);
}

// POST actions – admin required
if (method() === 'POST') {
    $body   = getBody();
    $action = $body['action'] ?? '';

    if ($action === 'add' || $action === 'update') {
        requireAdmin();

        $title        = trim($body['title']            ?? '');
        $subtitle     = trim($body['subtitle']         ?? '');
        $price        = trim($body['price']            ?? '');
        $price_num    = (float)($body['price_num']     ?? preg_replace('/[^0-9.]/', '', $price));
        $duration     = trim($body['duration']         ?? '');
        $description  = trim($body['description']      ?? '');
        $image        = trim($body['image']            ?? '');
        $tag          = trim($body['tag']              ?? '🗺️ Tour');
        $rating       = (float)($body['rating']        ?? 4.5);
        $b_count      = (int)($body['bookings_count']  ?? 0);
        $seats        = (int)($body['seats_available'] ?? 20);
        $details_raw  = $body['details'] ?? [];
        $details_json = json_encode(is_array($details_raw) ? $details_raw : [], JSON_UNESCAPED_UNICODE);

        if (!$title || !$price) jsonError('Title and price are required.');

        if ($action === 'add') {
            $stmt = $db->prepare(
                "INSERT INTO destinations
                 (title,subtitle,price,price_num,duration,description,image,tag,rating,bookings_count,seats_available,details)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?)"
            );
            // s s s d s s s s d i i s
            $stmt->bind_param('sssdssssdiis',
                $title, $subtitle, $price, $price_num, $duration,
                $description, $image, $tag, $rating, $b_count, $seats, $details_json
            );
            $stmt->execute();
            $newId = $db->insert_id;
            $stmt->close();
            jsonSuccess(['id' => $newId], 'Destination added.');
        } else {
            $id = (int)($body['id'] ?? 0);
            if (!$id) jsonError('ID required for update.');
            $stmt = $db->prepare(
                "UPDATE destinations
                 SET title=?,subtitle=?,price=?,price_num=?,duration=?,description=?,image=?,tag=?,rating=?,bookings_count=?,seats_available=?,details=?,updated_at=NOW()
                 WHERE id=?"
            );
            // s s s d s s s s d i i s i
            $stmt->bind_param('sssdssssdiisi',
                $title, $subtitle, $price, $price_num, $duration,
                $description, $image, $tag, $rating, $b_count, $seats, $details_json, $id
            );
            $stmt->execute();
            $stmt->close();
            jsonSuccess([], 'Destination updated.');
        }
    }

    if ($action === 'delete') {
        requireAdmin();
        $id = (int)($body['id'] ?? 0);
        if (!$id) jsonError('ID required.');
        $stmt = $db->prepare("DELETE FROM destinations WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        jsonSuccess([], 'Destination deleted.');
    }

    jsonError('Unknown action.');
}
