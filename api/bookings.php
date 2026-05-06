<?php
/**
 * api/bookings.php
 * GET                    → list bookings (admin: all, user: own)
 * POST {action:'add'}    → create booking (destination / hotel / flight)
 * POST {action:'cancel'} → cancel a booking (owner or admin only)
 */
require_once __DIR__ . '/config.php';

$db = db();

// ── GET ───────────────────────────────────────────────────────────────────────
if (method() === 'GET') {
    if (isAdmin()) {
        $res  = $db->query("SELECT * FROM bookings ORDER BY created_at DESC");
        $list = [];
        while ($row = $res->fetch_assoc()) $list[] = $row;
        jsonOut($list);
    } elseif (!empty($_SESSION['voyage_user_id'])) {
        $stmt = $db->prepare("SELECT * FROM bookings WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param('i', $_SESSION['voyage_user_id']);
        $stmt->execute();
        $res  = $stmt->get_result();
        $list = [];
        while ($row = $res->fetch_assoc()) $list[] = $row;
        $stmt->close();
        jsonOut($list);
    } else {
        jsonError('Unauthorized', 401);
    }
}

// ── POST ──────────────────────────────────────────────────────────────────────
if (method() === 'POST') {
    $body   = getBody();
    $action = $body['action'] ?? '';

    // ── CANCEL ────────────────────────────────────────────────────────────────
    if ($action === 'cancel') {
        $bid = (int)($body['id'] ?? 0);
        if (!$bid) jsonError('Invalid booking ID.');

        // Fetch booking to verify ownership
        $stmt = $db->prepare("SELECT id, user_id, status, destination_id, travellers FROM bookings WHERE id = ?");
        $stmt->bind_param('i', $bid);
        $stmt->execute();
        $booking = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$booking) jsonError('Booking not found.', 404);

        $userId = $_SESSION['voyage_user_id'] ?? null;
        if (!isAdmin() && (string)$booking['user_id'] !== (string)$userId) {
            jsonError('Unauthorized.', 403);
        }

        if ($booking['status'] === 'Cancelled') {
            jsonError('Booking is already cancelled.');
        }

        // Update status
        $upd = $db->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ?");
        $upd->bind_param('i', $bid);
        $upd->execute();
        $upd->close();

        // Restore seats if destination booking
        if (!empty($booking['destination_id']) && (int)$booking['destination_id'] > 0) {
            $travellers = (int)$booking['travellers'];
            $rel = $db->prepare(
                "UPDATE destinations SET seats_available = seats_available + ?,
                 bookings_count = GREATEST(0, bookings_count - 1) WHERE id = ?"
            );
            $rel->bind_param('ii', $travellers, $booking['destination_id']);
            $rel->execute();
            $rel->close();
        }

        jsonSuccess(['id' => $bid], 'Booking cancelled successfully.');
    }

    // ── ADD ───────────────────────────────────────────────────────────────────
    if ($action === 'add') {
        $bookingType = trim($body['booking_type'] ?? 'destination');
        if (!in_array($bookingType, ['destination', 'hotel', 'flight'])) {
            $bookingType = 'destination';
        }

        // Common fields
        $tn  = trim($body['traveller_name'] ?? (($body['first_name'] ?? '') . ' ' . ($body['last_name'] ?? '')));
        $em  = trim($body['email'] ?? '');
        $ph  = trim($body['phone'] ?? '');
        $tr  = (int)($body['travellers'] ?? 1);
        $dep = trim($body['departure_date'] ?? '');
        $sr  = trim($body['special_requests'] ?? '');
        $amt = trim($body['amount'] ?? '');

        if (!$tn || !$em) jsonError('Name and email are required.');
        if (!filter_var($em, FILTER_VALIDATE_EMAIL)) jsonError('Invalid email address.');

        // Type-specific fields
        $did            = 0;
        $dn             = '';
        $dur            = '';
        $hotelLocation  = null;
        $flightFrom     = null;
        $flightTo       = null;
        $checkin        = null;
        $checkout       = null;
        $ret            = null;

        // ── Destination ───────────────────────────────────────────────────────
        if ($bookingType === 'destination') {
            $did = (int)($body['destination_id'] ?? 0);
            $dn  = trim($body['destination_name'] ?? '');
            $dur = trim($body['duration'] ?? '');
            $ret = trim($body['return_date'] ?? '') ?: null;

            if (!$dn) jsonError('Destination is required.');

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
                            jsonError('Sorry, this package is fully booked.', 409);
                        } else {
                            jsonError("Only $avail seat(s) remaining. Please reduce traveller count.", 409);
                        }
                    }
                    if (!$amt) {
                        $total = $dest['price_num'] * $tr;
                        $amt   = '₹' . number_format($total, 0, '.', ',');
                    }
                }
            }
        }

        // ── Hotel ─────────────────────────────────────────────────────────────
        elseif ($bookingType === 'hotel') {
            $dn            = trim($body['hotel_name'] ?? $body['destination_name'] ?? '');
            $hotelLocation = trim($body['hotel_location'] ?? '');
            $checkin       = trim($body['checkin_date'] ?? $dep) ?: null;
            $checkout      = trim($body['checkout_date'] ?? '') ?: null;
            $dur           = 'Per Night';

            if (!$dn) jsonError('Hotel name is required.');
            if (!$amt) $amt = trim($body['price_display'] ?? '');
        }

        // ── Flight ────────────────────────────────────────────────────────────
        elseif ($bookingType === 'flight') {
            $flightFrom = trim($body['flight_from'] ?? '');
            $flightTo   = trim($body['flight_to']   ?? '');
            $dn         = $flightFrom . ' → ' . $flightTo;
            $dur        = trim($body['duration'] ?? '');
            $ret        = null;

            if (!$flightFrom || !$flightTo) jsonError('Flight origin and destination are required.');
            if (!$amt) $amt = trim($body['price_display'] ?? '');
        }

        $dep_val = $dep ?: null;
        $status  = 'Pending';
        $user_id = $_SESSION['voyage_user_id'] ?? null;

        $stmt = $db->prepare(
            "INSERT INTO bookings
             (user_id, booking_type, traveller_name, email, phone,
              destination_id, destination_name, hotel_location, flight_from, flight_to,
              duration, travellers, departure_date, return_date, checkin_date, checkout_date,
              amount, special_requests, status)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        $stmt->bind_param(
            'issssississssssssss',
            $user_id,
            $bookingType,
            $tn,
            $em,
            $ph,
            $did,
            $dn,
            $hotelLocation,
            $flightFrom,
            $flightTo,
            $dur,
            $tr,
            $dep_val,
            $ret,
            $checkin,
            $checkout,
            $amt,
            $sr,
            $status
        );
        $stmt->execute();
        $newId = $db->insert_id;
        $stmt->close();

        // Decrement seats for destination bookings
        if ($bookingType === 'destination' && $did > 0) {
            $upd = $db->prepare(
                "UPDATE destinations SET seats_available = GREATEST(0, seats_available - ?),
                 bookings_count = bookings_count + 1 WHERE id=?"
            );
            $upd->bind_param('ii', $tr, $did);
            $upd->execute();
            $upd->close();
        }

        jsonSuccess(['id' => $newId, 'amount' => $amt], 'Booking confirmed! We will contact you shortly.');
    }

    jsonError('Unknown action.');
}
