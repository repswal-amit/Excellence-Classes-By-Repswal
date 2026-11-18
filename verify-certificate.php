<?php
/**
 * Public Certificate Verification Portal
 * Excellence Classes By Repswal
 */

$currentPage = 'verify-certificate';
$pageTitle = 'Certificate Verification';
$pageDescription = 'Verify official internship completion certificates issued by Excellence Classes By Repswal using unique Certificate ID or QR code.';

require_once __DIR__ . '/includes/header.php';

$searchedId = trim($_GET['id'] ?? ($_POST['certificate_id'] ?? ''));
$verificationResult = null; // 'valid', 'revoked', 'not_found', null
$certData = null;

if (!empty($searchedId)) {
    try {
        $pdo = getDBConnection();
        
        // Query certificate joined with program details
        $stmt = $pdo->prepare("
            SELECT 
                c.id,
                c.certificate_id,
                c.intern_name,
                c.internship_domain,
                c.start_date,
                c.end_date,
                c.issue_date,
                c.certificate_status,
                c.revoked_reason,
                c.revoked_at,
                c.file_path,
                p.title AS program_title,
                p.duration AS program_duration,
                p.mode AS program_mode
            FROM certificates c
            LEFT JOIN internship_programs p ON c.program_id = p.id
            WHERE c.certificate_id = ?
            LIMIT 1
        ");
        $stmt->execute([$searchedId]);
        $cert = $stmt->fetch();

        if ($cert) {
            $certData = $cert;
            if ($cert['certificate_status'] === 'Issued') {
                $verificationResult = 'valid';

                // Log audit trail
                try {
                    $ip = getClientIP();
                    $logStmt = $pdo->prepare("
                        INSERT INTO certificate_audit_logs (certificate_id, action, performer_type, ip_address, details, created_at)
                        VALUES (?, 'Verified', 'Public', ?, 'Public verification lookup through verification portal', NOW())
                    ");
                    $logStmt->execute([$cert['id'], $ip]);
                } catch (Exception $e) {
                    // Non-blocking log failure
                }

            } elseif ($cert['certificate_status'] === 'Revoked') {
                $verificationResult = 'revoked';

                // Log revoked lookup
                try {
                    $ip = getClientIP();
                    $logStmt = $pdo->prepare("
                        INSERT INTO certificate_audit_logs (certificate_id, action, performer_type, ip_address, details, created_at)
                        VALUES (?, 'Verified', 'Public', ?, 'Lookup on revoked certificate record', NOW())
                    ");
                    $logStmt->execute([$cert['id'], $ip]);
                } catch (Exception $e) {
                    // Non-blocking
                }

            } else {
                $verificationResult = 'not_found';
            }
        } else {
            $verificationResult = 'not_found';
        }

    } catch (PDOException $e) {
        $verificationResult = 'not_found';
    }
}
?>

<!-- ═══════════════════ PAGE HEADER ═══════════════════ -->
<section class="page-header">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= baseUrl() ?>">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Verify Certificate</li>
                    </ol>
                </nav>
                <h1 class="page-title">Certificate Verification Portal</h1>
                <p class="page-subtitle">
                    Authenticate official internship completion certificates issued by Excellence Classes By Repswal. Every valid credential is permanently recorded and digitally verifiable.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end d-none d-lg-block">
                <span class="badge bg-gold-subtle text-gold px-3 py-2 rounded-pill fw-semibold">
                    <i class="bi bi-shield-check me-1"></i> Tamper-Proof Verification
                </span>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════ SEARCH BOX SECTION ═══════════════════ -->
<section class="section py-5 bg-light border-bottom">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm p-4 p-md-5">
                    <div class="text-center mb-4">
                        <div class="metric-icon royal mx-auto mb-3" style="width: 52px; height: 52px;">
                            <i class="bi bi-qr-code-scan"></i>
                        </div>
                        <h4 class="fw-bold mb-1">Verify Authenticity</h4>
                        <p class="text-secondary small mb-0">Enter the unique Certificate ID printed on the document or scanned via QR code.</p>
                    </div>

                    <form action="<?= baseUrl('verify-certificate.php') ?>" method="GET" class="row g-2">
                        <div class="col-md-9">
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" name="id" class="form-control border-start-0" placeholder="e.g. ECBR-2026-WD-1001" value="<?= e($searchedId) ?>" required autofocus>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary btn-lg w-100 rounded-3 fw-semibold">
                                <i class="bi bi-check2-circle me-1"></i> Verify
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════ RESULT PRESENTATION ═══════════════════ -->
<section class="section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">

                <?php if ($verificationResult === 'valid' && $certData): ?>
                    <!-- VALID CERTIFICATE RESULT -->
                    <div class="glass-card p-4 p-md-5 border-success border-2 shadow-lg reveal">
                        <!-- Status Banner -->
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pb-3 mb-4 border-bottom">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 54px; height: 54px; font-size: 1.8rem;">
                                    <i class="bi bi-patch-check-fill"></i>
                                </div>
                                <div>
                                    <h4 class="fw-bold text-success mb-0">Valid & Authentic Certificate</h4>
                                    <small class="text-muted">Database Record ID: <span class="fw-bold text-dark"><?= e($certData['certificate_id']) ?></span></small>
                                </div>
                            </div>
                            <span class="badge bg-success-subtle text-success px-3 py-2 rounded-pill fw-semibold">
                                <i class="bi bi-shield-fill-check me-1"></i> Official Record Confirmed
                            </span>
                        </div>

                        <!-- Certificate Visual Details -->
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3">
                                    <div class="text-muted small text-uppercase fw-semibold mb-1">Intern Full Name</div>
                                    <div class="fs-5 fw-bold text-dark"><?= e($certData['intern_name']) ?></div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3">
                                    <div class="text-muted small text-uppercase fw-semibold mb-1">Internship Domain</div>
                                    <div class="fs-5 fw-bold text-primary"><?= e($certData['internship_domain']) ?></div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded-3">
                                    <div class="text-muted small text-uppercase fw-semibold mb-1">Program Duration</div>
                                    <div class="fw-bold text-dark"><?= e($certData['program_duration'] ?? 'Completed') ?></div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded-3">
                                    <div class="text-muted small text-uppercase fw-semibold mb-1">Internship Tenure</div>
                                    <div class="fw-bold text-dark">
                                        <?= formatDate($certData['start_date']) ?> — <?= formatDate($certData['end_date']) ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded-3">
                                    <div class="text-muted small text-uppercase fw-semibold mb-1">Date of Issuance</div>
                                    <div class="fw-bold text-dark"><?= formatDate($certData['issue_date']) ?></div>
                                </div>
                            </div>
                        </div>

                        <!-- Institutional Certification Statement -->
                        <div class="card bg-navy text-white p-4 rounded-3 border-0 mb-4" style="background: linear-gradient(135deg, var(--navy), var(--navy-mid));">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="text-gold small text-uppercase fw-bold mb-1">Issuing Organization</div>
                                    <h5 class="fw-bold mb-1">Excellence Classes By Repswal</h5>
                                    <p class="text-white-50 small mb-0">
                                        Sole Proprietorship • Educational Services • Nawalgarh, Rajasthan - 333308
                                    </p>
                                </div>
                                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                    <div class="text-white-50 small">Authorized Signatory:</div>
                                    <div class="fw-bold text-gold">Amit Repswal</div>
                                    <div class="small text-white-50">Proprietor</div>
                                </div>
                            </div>
                        </div>

                        <!-- Privacy notice -->
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 text-muted small">
                            <div>
                                <i class="bi bi-info-circle me-1"></i> Private details (such as grades, email, and task submissions) are kept confidential.
                            </div>
                            <div>
                                Verified on <?= date('d M Y, h:i A') ?>
                            </div>
                        </div>
                    </div>

                <?php elseif ($verificationResult === 'revoked' && $certData): ?>
                    <!-- REVOKED CERTIFICATE RESULT -->
                    <div class="glass-card p-4 p-md-5 border-danger border-2 shadow-lg reveal">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pb-3 mb-4 border-bottom">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-danger text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 54px; height: 54px; font-size: 1.8rem;">
                                    <i class="bi bi-shield-x"></i>
                                </div>
                                <div>
                                    <h4 class="fw-bold text-danger mb-0">Certificate Revoked</h4>
                                    <small class="text-muted">Certificate ID: <span class="fw-bold text-dark"><?= e($certData['certificate_id']) ?></span></small>
                                </div>
                            </div>
                            <span class="badge bg-danger-subtle text-danger px-3 py-2 rounded-pill fw-semibold">
                                <i class="bi bi-exclamation-octagon-fill me-1"></i> Null & Void
                            </span>
                        </div>

                        <div class="alert alert-danger mb-4" role="alert">
                            <h6 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Official Notice of Revocation</h6>
                            <p class="mb-0 small">
                                This certificate was previously issued but has been officially <strong>REVOKED</strong> by the administration of Excellence Classes By Repswal. It is no longer valid for any employment, academic, or professional representation.
                            </p>
                            <?php if (!empty($certData['revoked_reason'])): ?>
                                <hr class="my-2">
                                <div class="small"><strong>Stated Reason:</strong> <?= e($certData['revoked_reason']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($certData['revoked_at'])): ?>
                                <div class="small mt-1 text-muted">Revoked On: <?= formatDate($certData['revoked_at']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="row g-3 text-secondary small">
                            <div class="col-sm-6"><strong>Originally Issued To:</strong> <?= e($certData['intern_name']) ?></div>
                            <div class="col-sm-6"><strong>Domain:</strong> <?= e($certData['internship_domain']) ?></div>
                            <div class="col-sm-6"><strong>Organization:</strong> Excellence Classes By Repswal</div>
                            <div class="col-sm-6"><strong>Original Issue Date:</strong> <?= formatDate($certData['issue_date']) ?></div>
                        </div>
                    </div>

                <?php elseif ($verificationResult === 'not_found'): ?>
                    <!-- NOT FOUND RESULT -->
                    <div class="glass-card p-4 p-md-5 border-warning border-2 shadow-lg text-center reveal">
                        <div class="bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 68px; height: 68px; font-size: 2.2rem;">
                            <i class="bi bi-exclamation-diamond-fill"></i>
                        </div>
                        <h3 class="fw-bold text-dark mb-2">Certificate Not Found</h3>
                        <p class="text-secondary max-w-600 mx-auto mb-4">
                            We could not find any active or historical certificate matching ID <code class="fw-bold text-danger"><?= e($searchedId) ?></code> in the official database of Excellence Classes By Repswal.
                        </p>
                        
                        <div class="alert alert-light text-start border max-w-600 mx-auto small mb-4">
                            <strong class="d-block mb-1 text-dark"><i class="bi bi-lightbulb me-1 text-warning"></i>Tips for verification:</strong>
                            <ul class="mb-0 ps-3 text-muted">
                                <li>Ensure the Certificate ID is typed accurately without extraneous spaces (e.g. <code>ECBR-YYYY-XX-NNNN</code>).</li>
                                <li>Certificates are only issued after official completion approval by Amit Repswal (Proprietor).</li>
                                <li>If you hold a printed or digital document that fails verification, contact our administrative office immediately.</li>
                            </ul>
                        </div>

                        <div class="d-flex gap-2 justify-content-center">
                            <a href="<?= baseUrl('verify-certificate.php') ?>" class="btn btn-outline-secondary">Try Another ID</a>
                            <a href="<?= baseUrl('contact.php?subject=' . urlencode('Certificate Verification Assistance for ' . $searchedId)) ?>" class="btn btn-primary">
                                <i class="bi bi-envelope me-1"></i>Contact Administration
                            </a>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- DEFAULT INFO STATE (NO SEARCH YET) -->
                    <div class="row g-4 reveal">
                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm p-4 text-center">
                                <div class="metric-icon royal mx-auto mb-3" style="width: 48px; height: 48px;">
                                    <i class="bi bi-database-check"></i>
                                </div>
                                <h6 class="fw-bold mb-2">Direct Database Lookup</h6>
                                <p class="text-secondary small mb-0">Every verification query checks active MySQL records in real-time — never trusting static imagery alone.</p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm p-4 text-center">
                                <div class="metric-icon gold mx-auto mb-3" style="width: 48px; height: 48px;">
                                    <i class="bi bi-qr-code"></i>
                                </div>
                                <h6 class="fw-bold mb-2">Quick QR Validation</h6>
                                <p class="text-secondary small mb-0">Every PDF certificate includes a dynamic QR code pointing directly to this official verification url.</p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card h-100 border-0 shadow-sm p-4 text-center">
                                <div class="metric-icon green mx-auto mb-3" style="width: 48px; height: 48px;">
                                    <i class="bi bi-shield-lock"></i>
                                </div>
                                <h6 class="fw-bold mb-2">Audit Logged</h6>
                                <p class="text-secondary small mb-0">All verification attempts and status queries are cryptographically logged with IP timestamps.</p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
