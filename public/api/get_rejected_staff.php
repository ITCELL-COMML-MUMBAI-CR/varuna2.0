<?php
/**
 * API to get rejected staff for an SCI's section (Robust Version)
 * Current Time: Monday, June 16, 2025 at 1:22 PM IST
 * Location: Kalyan, Maharashtra, India
 */
require_once __DIR__ . '/../../src/init.php';
require_once __DIR__ . '/../../config.php';
header('Content-Type: application/json');

$response = ['data' => []];

try {
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'SCI') {
        throw new Exception('Access denied.', 403);
    }

    $sci_section = $_SESSION['section'] ?? null;
    if (!$sci_section) {
        throw new Exception('User section not defined.');
    }

    // This query now uses LEFT JOINs to be safer
    $query = "SELECT s.id, s.name, s.designation, r.remark, u.username as remarked_by
              FROM varuna_staff s 
              LEFT JOIN contracts c ON s.contract_id = c.id
              LEFT JOIN varuna_remarks r ON s.id = r.staff_id
              LEFT JOIN varuna_users u ON r.remark_by_user_id = u.id
              WHERE s.status = 'rejected' AND c.section_code = ?
              ORDER BY r.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute([$sci_section]);
    $staff_list = $stmt->fetchAll();

    // Clean up null values for display
    foreach($staff_list as &$staff_member) {
        $staff_member['remark'] = $staff_member['remark'] ?? 'No remark entered.';
        $staff_member['remarked_by'] = $staff_member['remarked_by'] ?? 'N/A';
    }
    
    $response['data'] = $staff_list;

} catch (Exception $e) {
    http_response_code(500);
    $response['error'] = $e->getMessage();
}

echo json_encode($response);