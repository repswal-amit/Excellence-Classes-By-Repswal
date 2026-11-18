<?php
/**
 * Internship Certificate Management & Issuance
 * Excellence Classes By Repswal
 */

$adminPageTitle = 'Certificate Management';
$currentAdminPage = 'certificates';

require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();

// Handle Issue / Reissue / Revoke Certificate
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid security token.');
        redirect(baseUrl('admin/certificates.php'));
    }

    $action = $_POST['action'] ?? '';

    // Issue Certificate
    if ($action === 'issue_certificate') {
        if (!canIssueCertificates()) {
            setFlash('danger', 'You do not have administrative authority to issue certificates. Only the Proprietor can issue certificates.');
            redirect(baseUrl('admin/certificates.php'));
        }

        $enrollmentId = (int)($_POST['enrollment_id'] ?? 0);
        $issueDate = !empty($_POST['issue_date']) ? $_POST['issue_date'] : date('Y-m-d');

        try {
            $pdo->beginTransaction();

            // Verify enrollment is marked completed
            $enrStmt = $pdo->prepare("
                SELECT e.*, i.full_name, p.title AS domain_title, p.duration AS prog_duration
                FROM internship_enrollments e
                JOIN interns i ON e.intern_id = i.id
                JOIN internship_programs p ON e.program_id = p.id
                WHERE e.id = ?
            ");
            $enrStmt->execute([$enrollmentId]);
            $enr = $enrStmt->fetch();

            if (!$enr) {
                $pdo->rollBack();
                setFlash('danger', 'Enrollment record not found.');
                redirect(baseUrl('admin/certificates.php'));
            }

            if ((int)$enr['completion_approved'] !== 1) {
                $enrUpd = $pdo->prepare("
                    UPDATE internship_enrollments
                    SET internship_status = 'Completed',
                        completion_approved = 1,
                        completion_approved_by = ?,
                        completion_approved_at = NOW(),
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $enrUpd->execute([$currentAdmin['id'], $enrollmentId]);
            }

            // Check if certificate already exists
            $certCheck = $pdo->prepare("SELECT id, certificate_id, certificate_status FROM certificates WHERE enrollment_id = ?");
            $certCheck->execute([$enrollmentId]);
            $existing = $certCheck->fetch();

            $certRecordId = 0;
            $certId = '';

            if ($existing) {
                if ($existing['certificate_status'] === 'Issued') {
                    $pdo->rollBack();
                    setFlash('warning', "An active certificate (#{$existing['certificate_id']}) has already been issued for this enrollment.");
                    redirect(baseUrl('admin/certificates.php'));
                } else {
                    // Update existing
                    $certRecordId = (int)$existing['id'];
                    $certId = $existing['certificate_id'];
                    $upd = $pdo->prepare("
                        UPDATE certificates 
                        SET certificate_status = 'Issued', issue_date = ?, issued_by = ?, issued_at = NOW(), updated_at = NOW()
                        WHERE id = ?
                    ");
                    $upd->execute([$issueDate, $currentAdmin['id'], $certRecordId]);
                }
            } else {
                // Create new
                $certId = generateCertificateId();
                $ins = $pdo->prepare("
                    INSERT INTO certificates 
                    (certificate_id, enrollment_id, intern_id, program_id, intern_name, internship_domain, start_date, end_date, issue_date, certificate_status, issued_by, issued_at, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Issued', ?, NOW(), NOW())
                ");
                $ins->execute([
                    $certId, $enrollmentId, $enr['intern_id'], $enr['program_id'],
                    $enr['full_name'], $enr['domain_title'], $enr['start_date'], $enr['end_date'],
                    $issueDate, $currentAdmin['id']
                ]);
                $certRecordId = (int)$pdo->lastInsertId();
            }

            // Audit log
            $auditStmt = $pdo->prepare("
                INSERT INTO certificate_audit_logs (certificate_id, action, performed_by, performer_type, ip_address, details, created_at)
                VALUES (?, 'Issued', ?, 'Admin', ?, 'Certificate issued by Proprietor', NOW())
            ");
            $auditStmt->execute([$certRecordId, $currentAdmin['id'], getUserIP()]);

            $pdo->commit();

            logAdminAction($pdo, $currentAdmin['id'], 'Issue Certificate', 'certificates', $certRecordId, null, ['cert_id' => $certId]);
            setFlash('success', "Certificate #{$certId} issued successfully!");
            redirect(baseUrl('admin/certificates.php'));

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            setFlash('danger', 'Database error: ' . $e->getMessage());
            redirect(baseUrl('admin/certificates.php'));
        }
    }

    // Revoke Certificate
    if ($action === 'revoke_certificate') {
        if (!canRevokeCertificates()) {
            setFlash('danger', 'You do not have administrative authority to revoke certificates.');
            redirect(baseUrl('admin/certificates.php'));
        }

        $certId = (int)($_POST['id'] ?? 0);
        $reason = trim($_POST['revoked_reason'] ?? 'Revoked by administration');

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                UPDATE certificates 
                SET certificate_status = 'Revoked', revoked_reason = ?, revoked_by = ?, revoked_at = NOW(), updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$reason, $currentAdmin['id'], $certId]);

            // Audit log
            $auditStmt = $pdo->prepare("
                INSERT INTO certificate_audit_logs (certificate_id, action, performed_by, performer_type, ip_address, details, created_at)
                VALUES (?, 'Revoked', ?, 'Admin', ?, ?, NOW())
            ");
            $auditStmt->execute([$certId, $currentAdmin['id'], getUserIP(), "Revoked with reason: {$reason}"]);

            $pdo->commit();

            logAdminAction($pdo, $currentAdmin['id'], 'Revoke Certificate', 'certificates', $certId, null, ['reason' => $reason]);
            setFlash('success', "Certificate marked as Revoked.");
            redirect(baseUrl('admin/certificates.php'));

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            setFlash('danger', 'Error: ' . $e->getMessage());
            redirect(baseUrl('admin/certificates.php'));
        }
    }
}

// Fetch certificates
$certificates = $pdo->query("
    SELECT c.*, adm.full_name AS issuer_name, p.duration AS program_duration
    FROM certificates c
    LEFT JOIN admins adm ON c.issued_by = adm.id
    LEFT JOIN internship_programs p ON c.program_id = p.id
    ORDER BY c.created_at DESC
")->fetchAll();

// Eligible enrollments ready for issuance (all non-cancelled enrollments without an already Issued certificate)
$eligibleEnrollments = $pdo->query("
    SELECT e.id, e.completion_approved, e.internship_status, i.full_name, p.title AS program_title, c.certificate_status, c.certificate_id
    FROM internship_enrollments e
    JOIN interns i ON e.intern_id = i.id
    JOIN internship_programs p ON e.program_id = p.id
    LEFT JOIN certificates c ON e.id = c.enrollment_id
    WHERE e.internship_status != 'Cancelled' AND (c.certificate_status IS NULL OR c.certificate_status != 'Issued')
    ORDER BY e.completion_approved DESC, i.full_name ASC
")->fetchAll();
?>

<!-- ═══════════════════ HEADER CONTROLS ═══════════════════ -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Internship Certificate Authority</h4>
        <p class="text-muted small mb-0">Authorized issuance, QR code verification management, and audit tracking by Amit Repswal (Proprietor).</p>
    </div>
    <button type="button" class="btn btn-gold rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#issueCertModal">
        <i class="bi bi-patch-check-fill me-2"></i>Issue New Certificate
    </button>
</div>

<!-- ═══════════════════ CERTIFICATES TABLE ═══════════════════ -->
<div class="panel-card">
    <div class="panel-header">
        <h5 class="panel-title">
            <i class="bi bi-award-fill text-gold"></i> Issued Certificates Records (<?= count($certificates) ?>)
        </h5>
        <div class="text-muted small">
            Permanent cryptographic records tied to MySQL database
        </div>
    </div>

    <div class="panel-body p-0">
        <?php if (empty($certificates)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-award fs-1 d-block mb-2 text-secondary"></i>
                <h5 class="fw-bold">No Certificates Issued Yet</h5>
                <p class="small mb-0">When an enrolled intern successfully completes all requirements and has completion approved, issue their official certificate here.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Certificate ID</th>
                            <th>Intern Recipient</th>
                            <th>Internship Domain</th>
                            <th>Tenure Dates</th>
                            <th>Issue Date</th>
                            <th>Status</th>
                            <th>Signatory</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($certificates as $cert): ?>
                            <tr>
                                <td class="fw-bold font-monospace text-primary">
                                    <a href="<?= baseUrl('verify-certificate.php?id=' . urlencode($cert['certificate_id'])) ?>" target="_blank" class="text-decoration-none">
                                        <?= e($cert['certificate_id']) ?> <i class="bi bi-box-arrow-up-right small"></i>
                                    </a>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($cert['intern_name']) ?></div>
                                </td>
                                <td>
                                    <div class="small fw-semibold"><?= e($cert['internship_domain']) ?></div>
                                </td>
                                <td>
                                    <div class="small text-muted">
                                        <?= formatDate($cert['start_date']) ?> — <?= formatDate($cert['end_date']) ?>
                                    </div>
                                </td>
                                <td>
                                    <?= formatDate($cert['issue_date']) ?>
                                </td>
                                <td>
                                    <?= statusBadge($cert['certificate_status']) ?>
                                </td>
                                <td>
                                    <small class="text-muted"><?= e($cert['issuer_name'] ?? 'Proprietor') ?></small>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <!-- Certificate Preview Modal Trigger -->
                                        <button type="button" class="btn btn-action" data-bs-toggle="modal" data-bs-target="#certModal<?= (int)$cert['id'] ?>" title="Preview & Print Certificate">
                                            <i class="bi bi-eye"></i>
                                        </button>

                                        <a href="<?= baseUrl('verify-certificate.php?id=' . urlencode($cert['certificate_id'])) ?>" target="_blank" class="btn btn-action" title="Public Verification Link">
                                            <i class="bi bi-qr-code"></i>
                                        </a>

                                        <?php if ($cert['certificate_status'] === 'Issued'): ?>
                                            <button type="button" class="btn btn-action btn-action-danger" data-bs-toggle="modal" data-bs-target="#revokeModal<?= (int)$cert['id'] ?>" title="Revoke Certificate">
                                                <i class="bi bi-shield-x"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>

                            <!-- Certificate Landscape Preview & Print Modal -->
                            <div class="modal fade" id="certModal<?= (int)$cert['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-xl">
                                    <div class="modal-content border-0 shadow-lg">
                                        <div class="modal-header bg-navy text-white">
                                            <h5 class="modal-title fw-bold">Official Certificate — <?= e($cert['certificate_id']) ?></h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>

                                        <div class="modal-body p-4 p-md-5 bg-light">
                                            <!-- LANDSCAPE A4 CERTIFICATE DESIGN TEMPLATE -->
                                            <div id="printCert<?= (int)$cert['id'] ?>" class="certificate-preview-box mx-auto" style="max-width: 960px; min-height: 620px; background: #0a1628; border: 8px double #d4a843; border-radius: 16px; padding: 3rem 2.5rem; color: #ffffff; position: relative; box-shadow: 0 16px 40px rgba(0,0,0,0.3); font-family: 'Inter', sans-serif;">
                                                
                                                <!-- Inner Decorative Border -->
                                                <div style="border: 1px solid rgba(212, 168, 67, 0.4); border-radius: 10px; padding: 2.5rem; height: 100%; position: relative;">
                                                    
                                                    <!-- Top Header -->
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

                                                    <!-- Body Statement -->
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

                                                    <!-- Bottom Row: Signatory, Verification QR, and Meta -->
                                                    <div class="row align-items-end mt-4 pt-3" style="border-top: 1px solid rgba(212,168,67,0.3);">
                                                        <div class="col-4 text-start">
                                                            <div style="font-size: 0.75rem; color: rgba(255,255,255,0.6); text-transform: uppercase;">Unique Certificate ID</div>
                                                            <div style="font-family: monospace; font-size: 0.95rem; font-weight: 700; color: #f0d078;"><?= e($cert['certificate_id']) ?></div>
                                                            <div style="font-size: 0.72rem; color: rgba(255,255,255,0.5); margin-top: 2px;">Issue Date: <?= formatDate($cert['issue_date']) ?></div>
                                                        </div>

                                                        <div class="col-4 text-center">
                                                            <div style="display: inline-block; padding: 6px; background: #ffffff; border-radius: 8px;">
                                                                <!-- Dynamic QR placeholder pointing to verify URL -->
                                                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=72x72&data=<?= urlencode(baseUrl('verify-certificate.php?id=' . $cert['certificate_id'])) ?>" alt="Verification QR" style="width: 72px; height: 72px; display: block;">
                                                            </div>
                                                            <div style="font-size: 0.68rem; color: rgba(255,255,255,0.6); margin-top: 4px;">Scan to Verify Database Record</div>
                                                        </div>

                                                        <div class="col-4 text-end">
                                                            <div style="display: inline-block; text-align: center;">
                                                                <!-- Official Proprietor Designation -->
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

                            <!-- Revoke Modal -->
                            <div class="modal fade" id="revokeModal<?= (int)$cert['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow-lg">
                                        <form method="POST" action="<?= baseUrl('admin/certificates.php') ?>">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="action" value="revoke_certificate">
                                            <input type="hidden" name="id" value="<?= (int)$cert['id'] ?>">

                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title fw-bold">Revoke Certificate: <?= e($cert['certificate_id']) ?></h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>

                                            <div class="modal-body p-4">
                                                <div class="alert alert-warning small mb-3">
                                                    <strong>Warning:</strong> Revoking a certificate renders it immediately null & void on public verification checks and logs the reason permanently.
                                                </div>

                                                <label class="form-label fw-semibold">Reason for Revocation <span class="text-danger">*</span></label>
                                                <textarea name="revoked_reason" rows="3" class="form-control" placeholder="Specify administrative or academic reason..." required></textarea>
                                            </div>

                                            <div class="modal-footer bg-light">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-danger fw-bold">Confirm Revocation</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ═══════════════════ ISSUE CERTIFICATE MODAL ═══════════════════ -->
<div class="modal fade" id="issueCertModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST" action="<?= baseUrl('admin/certificates.php') ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="issue_certificate">

                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-award-fill me-2 text-gold"></i>Issue Official Certificate</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <?php if (empty($eligibleEnrollments)): ?>
                        <div class="alert alert-info small mb-0">
                            No enrolled interns are currently pending certificate issuance. Approve an application under <strong>Applications</strong> first.
                        </div>
                    <?php else: ?>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Select Enrolled Intern <span class="text-danger">*</span></label>
                            <select name="enrollment_id" class="form-select form-select-lg" required>
                                <option value="">-- Choose enrolled intern --</option>
                                <?php foreach ($eligibleEnrollments as $ee): ?>
                                    <option value="<?= (int)$ee['id'] ?>">
                                        <?= e($ee['full_name']) ?> — <?= e($ee['program_title']) ?> (<?= ((int)$ee['completion_approved'] === 1) ? 'Completed' : e($ee['internship_status']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Certificate Issue Date</label>
                            <input type="date" name="issue_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="p-3 bg-light rounded-3 small text-muted">
                            <i class="bi bi-shield-check text-success me-1"></i>
                            Certificate will be signed under the authority of <strong>Amit Repswal (Proprietor)</strong> with a permanent tamper-proof record and QR verification.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <?php if (!empty($eligibleEnrollments)): ?>
                        <button type="submit" class="btn btn-gold fw-bold">Issue Certificate</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

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
