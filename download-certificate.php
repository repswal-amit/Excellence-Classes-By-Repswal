<?php
/**
 * Download / Print Certificate
 * Excellence Classes By Repswal
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

initSession();

$certId = trim($_GET['id'] ?? '');

if (empty($certId)) {
    die('Error: Certificate ID is required.');
}

$pdo = getDBConnection();

try {
    $stmt = $pdo->prepare("
        SELECT c.*, p.duration AS program_duration 
        FROM certificates c
        JOIN internship_programs p ON c.program_id = p.id
        WHERE c.certificate_id = ?
        LIMIT 1
    ");
    $stmt->execute([$certId]);
    $cert = $stmt->fetch();

    if (!$cert) {
        die('Error: Certificate record not found in official database.');
    }

    if ($cert['certificate_status'] !== 'Issued') {
        die('Notice: This certificate has a status of "' . htmlspecialchars($cert['certificate_status']) . '" and cannot be exported.');
    }

    // Access control: allowed if admin or intern recipient or public verification
    $isOwner = isInternLoggedIn() && ((int)$_SESSION['intern_id'] === (int)$cert['intern_id']);
    $isAdmin = isAdminLoggedIn();

    // Log download event
    try {
        $performerType = $isAdmin ? 'Admin' : ($isOwner ? 'Intern' : 'Public');
        $performerId = $isAdmin ? ($_SESSION['admin_id'] ?? null) : ($isOwner ? ($_SESSION['intern_id'] ?? null) : null);
        $pdo->prepare("
            INSERT INTO certificate_audit_logs (certificate_id, action, performed_by, performer_type, ip_address, details, created_at)
            VALUES (?, 'Downloaded', ?, ?, ?, 'Official certificate print / PDF export initiated', NOW())
        ")->execute([$cert['id'], $performerId, $performerType, getUserIP()]);
    } catch (Exception $e) {
        // Non-blocking
    }

} catch (PDOException $e) {
    die('Database connection error.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate — <?= e($cert['certificate_id']) ?> | Excellence Classes By Repswal</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">
    
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }
        body {
            margin: 0;
            padding: 20px;
            background: #f1f3f5;
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .cert-container {
            width: 1000px;
            min-height: 680px;
            background: #0a1628;
            border: 8px double #d4a843;
            border-radius: 16px;
            padding: 2.5rem;
            color: #ffffff;
            box-shadow: 0 16px 40px rgba(0,0,0,0.3);
            position: relative;
            box-sizing: border-box;
        }
        .cert-inner {
            border: 1px solid rgba(212, 168, 67, 0.4);
            border-radius: 10px;
            padding: 2.5rem;
            height: 100%;
            position: relative;
        }
        .cert-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: #f0d078;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin: 0;
        }
        .cert-name {
            font-family: 'Playfair Display', serif;
            font-size: 2.2rem;
            font-weight: 700;
            color: #ffffff;
            border-bottom: 2px solid #d4a843;
            display: inline-block;
            padding: 0 1.5rem 0.2rem;
            margin: 0.6rem 0;
        }
        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        @media print {
            body {
                background: none;
                padding: 0;
            }
            .cert-container {
                box-shadow: none;
                width: 100%;
                border-radius: 0;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="no-print d-flex gap-2">
    <button onclick="window.print()" class="btn btn-primary fw-bold shadow">
        <i class="bi bi-printer me-1"></i> Print / Save as PDF
    </button>
    <a href="<?= baseUrl('verify-certificate.php?id=' . urlencode($cert['certificate_id'])) ?>" class="btn btn-dark shadow">
        Verify Online
    </a>
</div>

<div class="cert-container">
    <div class="cert-inner">
        <!-- Header -->
        <div class="text-center mb-3">
            <div style="color: #d4a843; font-size: 0.9rem; font-weight: 700; letter-spacing: 0.2em; text-transform: uppercase;">
                EXCELLENCE CLASSES BY REPSWAL
            </div>
            <div style="color: rgba(255,255,255,0.6); font-size: 0.75rem; letter-spacing: 0.1em; text-transform: uppercase; margin-top: 2px;">
                Sole Proprietorship • Educational & IT Training Institute • Nawalgarh, Rajasthan - 333308
            </div>
        </div>

        <div class="text-center my-3">
            <h1 class="cert-title">Certificate of Internship</h1>
            <div style="width: 100px; height: 2px; background: #d4a843; margin: 10px auto;"></div>
        </div>

        <!-- Body Statement -->
        <div class="text-center my-4" style="font-size: 1.05rem; color: rgba(255,255,255,0.9); line-height: 1.8;">
            This is to certify that
            <div class="cert-name"><?= e($cert['intern_name']) ?></div>
            <br>
            has successfully completed the internship program in
            <div style="color: #f0d078; font-weight: 700; font-size: 1.25rem; margin: 0.35rem 0;">
                <?= e($cert['internship_domain']) ?>
            </div>
            at <strong>Excellence Classes By Repswal</strong> from 
            <strong><?= formatDate($cert['start_date']) ?></strong> to <strong><?= formatDate($cert['end_date']) ?></strong>.
            
            <p style="font-size: 0.9rem; color: rgba(255,255,255,0.7); max-width: 750px; margin: 0.8rem auto 0;">
                The intern has completed the assigned internship requirements and demonstrated their learning and practical skills during the internship period.
            </p>
        </div>

        <!-- Bottom Signatures and QR Code -->
        <div class="row align-items-end mt-4 pt-3" style="border-top: 1px solid rgba(212,168,67,0.3);">
            <div class="col-4 text-start">
                <div style="font-size: 0.75rem; color: rgba(255,255,255,0.6); text-transform: uppercase;">Unique Certificate ID</div>
                <div style="font-family: monospace; font-size: 1rem; font-weight: 700; color: #f0d078;"><?= e($cert['certificate_id']) ?></div>
                <div style="font-size: 0.75rem; color: rgba(255,255,255,0.5); margin-top: 2px;">Issue Date: <?= formatDate($cert['issue_date']) ?></div>
            </div>

            <div class="col-4 text-center">
                <div style="display: inline-block; padding: 6px; background: #ffffff; border-radius: 8px;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=76x76&data=<?= urlencode(baseUrl('verify-certificate.php?id=' . $cert['certificate_id'])) ?>" alt="Verification QR" style="width: 76px; height: 76px; display: block;">
                </div>
                <div style="font-size: 0.7rem; color: rgba(255,255,255,0.6); margin-top: 4px;">Scan to Verify Database Record</div>
            </div>

            <div class="col-4 text-end">
                <div style="display: inline-block; text-align: center;">
                    <div style="font-family: 'Playfair Display', serif; font-size: 1.25rem; font-weight: 700; color: #ffffff; border-bottom: 1px solid rgba(255,255,255,0.4); padding-bottom: 4px;">
                        Amit Repswal
                    </div>
                    <div style="font-size: 0.85rem; color: #f0d078; font-weight: 700;">Proprietor</div>
                    <div style="font-size: 0.72rem; color: rgba(255,255,255,0.5);">Excellence Classes By Repswal</div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
