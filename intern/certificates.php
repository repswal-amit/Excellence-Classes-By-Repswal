<?php
/**
 * Intern Certificates & Verified Credentials
 * Excellence Classes By Repswal
 */

$internPageTitle = 'My Certificates';
$currentInternPage = 'certificates';

require_once __DIR__ . '/includes/header.php';

// Fetch certificates for this intern
try {
    $stmt = $pdo->prepare("
        SELECT c.*, p.title AS program_title, p.duration AS program_duration,
               e.completion_approved
        FROM certificates c
        JOIN internship_programs p ON c.program_id = p.id
        JOIN internship_enrollments e ON c.enrollment_id = e.id
        WHERE c.intern_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$internId]);
    $myCertificates = $stmt->fetchAll();
} catch (PDOException $e) {
    $myCertificates = [];
}
?>

<!-- ═══════════════════ HEADER ═══════════════════ -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">My Certificates of Internship</h4>
        <p class="text-muted small mb-0">Official verifiable credentials backed by permanent database records and dynamic QR code authentication.</p>
    </div>
</div>

<!-- ═══════════════════ CERTIFICATES DISPLAY ═══════════════════ -->
<?php if (empty($myCertificates)): ?>
    <div class="panel-card p-5 text-center text-muted">
        <i class="bi bi-award fs-1 d-block mb-2 text-gold"></i>
        <h5 class="fw-bold">No Certificates Issued Yet</h5>
        <p class="small max-w-500 mx-auto mb-3">
            Certificates of Internship are issued by Amit Repswal (Proprietor) following the successful completion and approval of all assigned curriculum tasks.
        </p>
        <a href="<?= baseUrl('intern/tasks.php') ?>" class="btn btn-primary rounded-pill px-4">
            <i class="bi bi-list-task me-1"></i>Check Task Progress
        </a>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($myCertificates as $cert): ?>
            <?php
                $isIssued = ($cert['certificate_status'] === 'Issued');
                $isRevoked = ($cert['certificate_status'] === 'Revoked');
            ?>
            <div class="col-lg-6">
                <div class="panel-card h-100 d-flex flex-column border-2 <?= $isIssued ? 'border-primary' : '' ?>">
                    <div class="panel-header d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-patch-check-fill text-gold fs-4"></i>
                            <div>
                                <span class="badge bg-gold text-dark fw-bold">Certificate of Internship</span>
                                <div class="font-monospace fw-bold text-primary small mt-1"><?= e($cert['certificate_id']) ?></div>
                            </div>
                        </div>
                        <?= statusBadge($cert['certificate_status']) ?>
                    </div>

                    <div class="panel-body flex-grow-1">
                        <h5 class="fw-bold text-dark mb-1"><?= e($cert['internship_domain']) ?></h5>
                        <p class="text-muted small mb-3">Issued to <strong><?= e($cert['intern_name']) ?></strong></p>

                        <div class="p-3 bg-light rounded-3 small mb-3">
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Tenure:</span>
                                <strong><?= formatDate($cert['start_date']) ?> to <?= formatDate($cert['end_date']) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom">
                                <span class="text-muted">Issue Date:</span>
                                <strong><?= formatDate($cert['issue_date']) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between py-1">
                                <span class="text-muted">Issuing Authority:</span>
                                <strong>Amit Repswal (Proprietor)</strong>
                            </div>
                        </div>

                        <?php if ($isIssued): ?>
                            <div class="d-flex align-items-center gap-3 p-2 border rounded-3 bg-white">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=54x54&data=<?= urlencode(baseUrl('verify-certificate.php?id=' . $cert['certificate_id'])) ?>" alt="QR" style="width: 54px; height: 54px;" class="rounded">
                                <div class="small">
                                    <div class="fw-bold text-dark">Live QR Authentication</div>
                                    <span class="text-muted">Anyone can scan this code to verify your credentials against our official records.</span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="panel-footer d-flex justify-content-between align-items-center">
                        <?php if ($isIssued): ?>
                            <a href="<?= baseUrl('verify-certificate.php?id=' . urlencode($cert['certificate_id'])) ?>" target="_blank" class="small text-primary text-decoration-none">
                                <i class="bi bi-box-arrow-up-right me-1"></i>Public Verification Link
                            </a>
                            <button type="button" class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#certModal<?= (int)$cert['id'] ?>">
                                <i class="bi bi-printer me-1"></i>View & Print
                            </button>
                        <?php else: ?>
                            <span class="small text-muted">Awaiting final administrative seal</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Landscape A4 Printable Certificate Modal -->
            <?php if ($isIssued): ?>
                <div class="modal fade" id="certModal<?= (int)$cert['id'] ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-xl">
                        <div class="modal-content border-0 shadow-lg">
                            <div class="modal-header bg-navy text-white">
                                <h5 class="modal-title fw-bold">Certificate of Internship — <?= e($cert['certificate_id']) ?></h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="modal-body p-4 p-md-5 bg-light">
                                <div id="printCert<?= (int)$cert['id'] ?>" class="certificate-preview-box mx-auto" style="max-width: 960px; min-height: 620px; background: #0a1628; border: 8px double #d4a843; border-radius: 16px; padding: 3rem 2.5rem; color: #ffffff; position: relative; box-shadow: 0 16px 40px rgba(0,0,0,0.3); font-family: 'Inter', sans-serif;">
                                    
                                    <!-- Inner Border -->
                                    <div style="border: 1px solid rgba(212, 168, 67, 0.4); border-radius: 10px; padding: 2.5rem; height: 100%; position: relative;">
                                        
                                        <!-- Header -->
                                        <div class="text-center mb-3">
                                            <div style="color: #d4a843; font-size: 0.85rem; font-weight: 700; letter-spacing: 0.2em; text-transform: uppercase;">
                                                EXCELLENCE CLASSES BY REPSWAL
                                            </div>
                                            <div style="color: rgba(255,255,255,0.6); font-size: 0.72rem; letter-spacing: 0.1em; text-transform: uppercase; margin-top: 2px;">
                                                Sole Proprietorship • Educational & IT Training Institute • Nawalgarh, Rajasthan
                                            </div>
                                        </div>

                                        <div class="text-center my-3">
                                            <h1 style="font-family: 'Playfair Display', serif; font-size: 2.3rem; font-weight: 700; color: #f0d078; letter-spacing: 0.05em; text-transform: uppercase; margin: 0;">
                                                Certificate of Internship
                                            </h1>
                                            <div style="width: 80px; height: 2px; background: #d4a843; margin: 8px auto;"></div>
                                        </div>

                                        <!-- Recipient Statement -->
                                        <div class="text-center my-4" style="font-size: 1rem; color: rgba(255,255,255,0.9); line-height: 1.8;">
                                            This is to certify that
                                            <div style="font-family: 'Playfair Display', serif; font-size: 2rem; font-weight: 700; color: #ffffff; margin: 0.6rem 0; border-bottom: 2px solid #d4a843; display: inline-block; padding: 0 1.5rem 0.2rem;">
                                                <?= e($cert['intern_name']) ?>
                                            </div>
                                            <br>
                                            has successfully completed the internship program in
                                            <span style="color: #f0d078; font-weight: 700; font-size: 1.15rem; display: block; margin: 0.35rem 0;">
                                                <?= e($cert['internship_domain']) ?>
                                            </span>
                                            at <strong>Excellence Classes By Repswal</strong> from 
                                            <strong><?= formatDate($cert['start_date']) ?></strong> to <strong><?= formatDate($cert['end_date']) ?></strong>.
                                            <p style="font-size: 0.88rem; color: rgba(255,255,255,0.7); max-width: 700px; margin: 0.8rem auto 0;">
                                                The intern has completed the assigned internship requirements and demonstrated their learning and practical skills during the internship period.
                                            </p>
                                        </div>

                                        <!-- Signatures and QR Code -->
                                        <div class="row align-items-end mt-4 pt-3" style="border-top: 1px solid rgba(212,168,67,0.3);">
                                            <div class="col-4 text-start">
                                                <div style="font-size: 0.75rem; color: rgba(255,255,255,0.6); text-transform: uppercase;">Unique Certificate ID</div>
                                                <div style="font-family: monospace; font-size: 0.95rem; font-weight: 700; color: #f0d078;"><?= e($cert['certificate_id']) ?></div>
                                                <div style="font-size: 0.72rem; color: rgba(255,255,255,0.5); margin-top: 2px;">Issue Date: <?= formatDate($cert['issue_date']) ?></div>
                                            </div>

                                            <div class="col-4 text-center">
                                                <div style="display: inline-block; padding: 6px; background: #ffffff; border-radius: 8px;">
                                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=72x72&data=<?= urlencode(baseUrl('verify-certificate.php?id=' . $cert['certificate_id'])) ?>" alt="Verification QR" style="width: 72px; height: 72px; display: block;">
                                                </div>
                                                <div style="font-size: 0.68rem; color: rgba(255,255,255,0.6); margin-top: 4px;">Scan to Verify Database Record</div>
                                            </div>

                                            <div class="col-4 text-end">
                                                <div style="display: inline-block; text-align: center;">
                                                    <div style="font-family: 'Playfair Display', serif; font-size: 1.15rem; font-weight: 700; color: #ffffff; border-bottom: 1px solid rgba(255,255,255,0.4); padding-bottom: 3px;">
                                                        Amit Repswal
                                                    </div>
                                                    <div style="font-size: 0.82rem; color: #f0d078; font-weight: 700;">Proprietor</div>
                                                    <div style="font-size: 0.68rem; color: rgba(255,255,255,0.5);">Excellence Classes By Repswal</div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer bg-light">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary" onclick="printDoc('printCert<?= (int)$cert['id'] ?>')">
                                    <i class="bi bi-printer me-1"></i>Print Landscape Certificate
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
function printDoc(id) {
    const el = document.getElementById(id);
    const win = window.open('', '', 'height=800,width=1100');
    win.document.write('<html><head><title>Certificate of Internship</title>');
    win.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">');
    win.document.write('<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Playfair+Display:wght@700&display=swap" rel="stylesheet">');
    win.document.write('<style>@page{size: landscape; margin:0;} body{margin:20px; font-family:"Inter",sans-serif; background:#fff;}</style>');
    win.document.write('</head><body>');
    win.document.write(el.outerHTML);
    win.document.write('</body></html>');
    win.document.close();
    win.focus();
    setTimeout(() => { win.print(); win.close(); }, 700);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
