<?php
/**
 * api/stats.php
 * GET → admin dashboard statistics
 */
require_once __DIR__ . '/config.php';
requireAdmin();

$db = db();

$stats = [];

// Total bookings, confirmed, pending, cancelled
$res = $db->query("SELECT status, COUNT(*) AS cnt FROM bookings GROUP BY status");
$statusCounts = ['Confirmed'=>0,'Pending'=>0,'Cancelled'=>0];
while ($row = $res->fetch_assoc()) $statusCounts[$row['status']] = (int)$row['cnt'];
$stats['total_bookings'] = array_sum($statusCounts);
$stats['confirmed']      = $statusCounts['Confirmed'];
$stats['pending']        = $statusCounts['Pending'];
$stats['cancelled']      = $statusCounts['Cancelled'];

// Total destinations (active tours)
$row = $db->query("SELECT COUNT(*) AS cnt FROM destinations")->fetch_assoc();
$stats['total_tours'] = (int)$row['cnt'];

// Recent 5 bookings
$res  = $db->query("SELECT * FROM bookings ORDER BY created_at DESC LIMIT 5");
$recent = [];
while ($row = $res->fetch_assoc()) $recent[] = $row;
$stats['recent_bookings'] = $recent;

// Top 5 destinations by bookings_count
$res  = $db->query("SELECT id,title,image,tag,bookings_count FROM destinations ORDER BY bookings_count DESC LIMIT 5");
$top  = [];
while ($row = $res->fetch_assoc()) $top[] = $row;
$stats['top_destinations'] = $top;

jsonOut($stats);
