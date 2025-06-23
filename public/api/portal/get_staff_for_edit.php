<?php
/**
 * Portal API: Fetches data for a single staff member to populate an edit form.
 * Ensures the staff belongs to the logged-in licensee.
 */
require_once __DIR__ . '/../../../src/init.php';
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['is_licensee_portal'], $_SESSION['licensee_id'])) {
        throw new Exception('Not authenticated for portal access.', 403);
    }

    $staff_id = $_GET['id'] ?? '';
    if (empty($staff_id)) {
        throw new Exception('Staff ID is required.', 400);
    }

    // CRITICAL: This query joins to ensure the requested staff member
    // belongs to a contract owned by the logged-in licensee.
    $stmt = $pdo->prepare(
        "SELECT s.* FROM varuna_staff s
         JOIN contracts c ON s.contract_id = c.id
         WHERE s.id = ? AND c.licensee_id = ?"
    );
    $stmt->execute([$staff_id, $_SESSION['licensee_id']]);
    $staff = $stmt->fetch();

    if (!$staff) {
        throw new Exception('Staff member not found or access denied.', 404);
    }

    echo json_encode(['success' => true, 'staff' => $staff]);

} catch (Exception $e) {
    http_response_code($e->getCode() > 0 ? $e->getCode() : 500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}