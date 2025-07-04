<?php
/**
 * API to get counts of records that will be deleted in a cascade deletion
 * Used to inform the user before confirmation
 */
require_once __DIR__ . '/../../../src/init.php';
header('Content-Type: application/json');

try {
    // Security: Only Admins can access this
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' || !in_array($_SESSION['role'],['ADMIN','SCI'])) {
        throw new Exception('Access Denied.', 403);
    }
    
    $type = $_GET['type'] ?? ''; // 'licensee' or 'contract'
    $id = $_GET['id'] ?? '';
    
    if (empty($type) || empty($id)) {
        throw new Exception('Type and ID are required.', 400);
    }
    
    $response = [];
    
    if ($type === 'licensee') {
        // Get licensee name
        $licensee_stmt = $pdo->prepare("SELECT name FROM varuna_licensee WHERE id = ?");
        $licensee_stmt->execute([$id]);
        $licensee_name = $licensee_stmt->fetchColumn();
        
        if (!$licensee_name) {
            throw new Exception('Licensee not found.', 404);
        }
        
        // Get contract count
        $contract_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM contracts WHERE licensee_id = ?");
        $contract_count_stmt->execute([$id]);
        $contract_count = $contract_count_stmt->fetchColumn();
        
        // Get staff count
        $staff_count_stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM varuna_staff vs 
            JOIN contracts c ON vs.contract_id = c.id 
            WHERE c.licensee_id = ?
        ");
        $staff_count_stmt->execute([$id]);
        $staff_count = $staff_count_stmt->fetchColumn();
        
        $response = [
            'name' => $licensee_name,
            'type' => 'licensee',
            'contracts' => $contract_count,
            'staff' => $staff_count
        ];
        
    } elseif ($type === 'contract') {
        // Get contract details
        $contract_stmt = $pdo->prepare("
            SELECT c.contract_name, l.name as licensee_name 
            FROM contracts c 
            LEFT JOIN varuna_licensee l ON c.licensee_id = l.id 
            WHERE c.id = ?
        ");
        $contract_stmt->execute([$id]);
        $contract = $contract_stmt->fetch();
        
        if (!$contract) {
            throw new Exception('Contract not found.', 404);
        }
        
        // Get staff count
        $staff_count_stmt = $pdo->prepare("SELECT COUNT(*) FROM varuna_staff WHERE contract_id = ?");
        $staff_count_stmt->execute([$id]);
        $staff_count = $staff_count_stmt->fetchColumn();
        
        $response = [
            'name' => $contract['contract_name'],
            'licensee_name' => $contract['licensee_name'],
            'type' => 'contract',
            'staff' => $staff_count
        ];
        
    } else {
        throw new Exception('Invalid type. Must be "licensee" or "contract".', 400);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $response
    ]);

} catch (Exception $e) {
    http_response_code($e->getCode() > 0 ? $e->getCode() : 500);
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
} 