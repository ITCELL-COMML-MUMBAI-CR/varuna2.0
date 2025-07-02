<?php
/**
 * Migration Script: Rename legacy staff document files to new naming convention.
 *
 * Old format: {staff_id}_{any_text}_{docType}.{ext}
 * New format: {staff_id}_{docType}.{ext}
 *
 * Run from project root via CLI:
 *    php scripts/rename_staff_documents.php
 *
 * MAKE SURE you have a full backup of both database and uploads directory before running.
 */

// --- Bootstrap framework & database ---
$projectRoot = dirname(__DIR__);
require_once $projectRoot . '/src/init.php'; // sets up $pdo and other helpers

if (php_sapi_name() !== 'cli') {
    exit("This script must be run from the command line.\n");
}

$uploadDir = realpath($projectRoot . '/public/uploads/staff');
if (!$uploadDir) {
    exit("Uploads directory not found: public/uploads/staff\n");
}

// Map DB column => document type portion of filename
$docMap = [
    'police_image'     => 'police',
    'medical_image'    => 'medical',
    'ta_image'         => 'ta',
    'ppo_image'        => 'ppo',
    'profile_image'    => 'profile',
    'signature_image'  => 'signature',
    'adhar_card_image' => 'adhar',
];

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Starting staff document migration...\n";

$sql = "SELECT id, police_image, medical_image, ta_image, ppo_image, profile_image, signature_image, adhar_card_image FROM varuna_staff";
$stmt = $pdo->query($sql);
$totalProcessed = 0;
$totalRenamed   = 0;
$totalErrors    = 0;

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $updateFragments = [];
    $updateValues    = [];

    foreach ($docMap as $column => $docType) {
        $oldFilename = $row[$column] ?? '';
        if (!$oldFilename) {
            continue; // nothing stored
        }

        $totalProcessed++;

        // Determine new filename
        $ext = pathinfo($oldFilename, PATHINFO_EXTENSION);
        $newFilename = $row['id'] . '_' . $docType . '.' . $ext;

        // Skip if already in correct format
        if ($oldFilename === $newFilename) {
            continue;
        }

        $oldPath = $uploadDir . DIRECTORY_SEPARATOR . $oldFilename;
        $newPath = $uploadDir . DIRECTORY_SEPARATOR . $newFilename;

        // Attempt physical rename first
        $renamed = false;
        if (file_exists($oldPath)) {
            // Ensure we don't overwrite an existing target file
            if (!file_exists($newPath)) {
                $renamed = rename($oldPath, $newPath);
            } else {
                echo "[SKIP] Target file already exists: {$newFilename}\n";
                $renamed = true; // treat as renamed to allow DB patch
            }
        } else {
            echo "[WARN] Source file missing: {$oldFilename}\n";
        }

        if ($renamed) {
            $updateFragments[] = "$column = ?";
            $updateValues[]    = $newFilename;
            $totalRenamed++;
            echo "[OK] {$oldFilename} => {$newFilename}\n";
        } else {
            $totalErrors++;
            echo "[ERR] Failed to rename {$oldFilename}\n";
        }
    }

    // Update DB if any file names changed for this row
    if ($updateFragments) {
        $updateValues[] = $row['id'];
        $updSql = "UPDATE varuna_staff SET " . implode(', ', $updateFragments) . " WHERE id = ?";
        $updStmt = $pdo->prepare($updSql);
        $updStmt->execute($updateValues);
    }
}

echo "\nMigration complete.\n";
echo "Processed files: {$totalProcessed}\n";
echo "Renamed/updated: {$totalRenamed}\n";
echo "Errors: {$totalErrors}\n"; 