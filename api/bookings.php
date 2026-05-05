<?php
/**
 * api/bookings.php
 * GET           → list all bookings (admin only) or user's bookings (user)
 * POST {action:'add'} → create a new booking (checks + decrements seats_available, saves user_id)
 */
require_once __DIR__ . '/config.php';

$db = db();

if (method() === 'GET') {
    if (isAdmin()) {
        $res  = $db->query("SELECT * FROM bookings ORDER BY created_at DESC");
        $list = [];
        while ($row = $res->fetch_assoc()) $list[] = $row;
        jsonOut($list);
    } else if (!empty($_SESSION['voyage_user_id'])) {
        $stmt = $db->prepare("SELECT * FROM bookings WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param('i', $_SESSION['voyage_user_id']);
        $stmt->execute();
        $res = $stmt->get_result();
        $list = [];
        while ($row = $res->fetch_assoc()) $list[] = $row;
        $stmt->close();
        jsonOut($list);
    } else {
        jsonError('Unauthorized', 401);
    }
}

if (method() === 'POST') {
    $body   = getBody();
    $action = $body['action'] ?? '';

    if ($action === 'add') {
        $tn   = trim($body['traveller_name'] ?? ($body['first_name'] . ' ' . $body['last_name']));
        $em   = trim($body['email']          ?? '');
        $ph   = trim($body['phone']          ?? '');
        $did  = (int)($body['destination_id']   ?? 0);
        $dn   = trim($body['destination_name']  ?? '');
        $dur  = trim($body['duration']          ?? '');
        $tr   = (int)($body['travellers']       ?? 1);
        $dep  = trim($body['departure_date']    ?? '');
        $ret  = trim($body['return_date']       ?? '');
        $amt  = trim($body['amount']            ?? '');
        $sr   = trim($body['special_requests']  ?? '');

        if (!$tn || !$em || !$dn) jsonError('Name, email and destination are required.');
        if (!filter_var($em, FILTER_VALIDATE_EMAIL)) jsonError('Invalid email address.');

        // ── Seat availability check ───────────────────────────────────────────
        if ($did > 0) {
            $stmt = $db->prepare("SELECT price, price_num, seats_available FROM destinations WHERE id=?");
            $stmt->bind_param('i', $did);
            $stmt->execute();
            $dest = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($dest) {
                if ((int)$dest['seats_available'] < $tr) {
                    $avail = (int)$dest['seats_available'];
                    if ($avail <= 0) {
                        jsonError('Sorry, this package is fully booked. No seats available.', 409);
                    } else {
                        jsonError("Only $avail seat(s) remaining for this destination. Please reduce traveller count.", 409);
                    }
                }

                // Compute amount if not provided
                if (!$amt) {
                    $total = $dest['price_num'] * $tr;
                    $amt   = '₹' . number_format($total, 0, '.', ',');
                }
            }
        } else {
            // No destination ID – just compute amount if we can
            if (!$amt && $did > 0) {
                $stmt = $db->prepare("SELECT price_num FROM destinations WHERE id=?");
                $stmt->bind_param('i', $did);
                $stmt->execute();
                $dest = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($dest) {
                    $total = $dest['price_num'] * $tr;
                    $amt   = '₹' . number_format($total, 0, '.', ',');
                }
            }
        }

        $dep_val = $dep ?: null;
        $ret_val = $ret ?: null;
        $status  = 'Pending';
        $user_id = $_SESSION['voyage_user_id'] ?? null;

        $stmt = $db->prepare(
            "INSERT INTO bookings (user_id,traveller_name,email,phone,destination_id,destination_name,duration,travellers,departure_date,return_date,amount,special_requests,status)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        $stmt->bind_param('isssississsss',
            $user_id, $tn, $em, $ph, $did, $dn, $dur, $tr, $dep_val, $ret_val, $amt, $sr, $status
        );
        $stmt->execute();
        $newId = $db->insert_id;
        $stmt->close();

        // ── Decrement seats_available ────────────────────────────────────────
        if ($did > 0) {
            $upd = $db->prepare(
                "UPDATE destinations SET seats_available = GREATEST(0, seats_available - ?), bookings_count = bookings_count + 1 WHERE id=?"
            );
            $upd->bind_param('ii', $tr, $did);
            $upd->execute();
            $upd->close();
        }

        jsonSuccess(['id' => $newId, 'amount' => $amt], 'Booking confirmed! We will contact you shortly.');
    }

    jsonError('Unknown action.');
}
