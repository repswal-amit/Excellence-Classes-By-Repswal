<?php
/**
 * Document Generation System (Offer Letters, Joining Letters, Experience Letters)
 * Excellence Classes By Repswal
 */

$adminPageTitle = 'Document Management';
$currentAdminPage = 'documents';

require_once __DIR__ . '/includes/header.php';

$pdo = getDBConnection();

// Handle Generate / Issue Document
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Invalid security token.');
        redirect(baseUrl('admin/documents.php'));
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create_document') {
        $enrollmentId = (int)($_POST['enrollment_id'] ?? 0);
        $docType = $_POST['document_type'] ?? 'Offer Letter';

        if ($enrollmentId <= 0 || !in_array($docType, ['Offer Letter', 'Joining Letter', 'Experience Certificate'])) {
            setFlash('danger', 'Please choose a valid enrollment and document type.');
            redirect(baseUrl('admin/documents.php'));
        }

        try {
            // Find intern ID from enrollment
            $stmt = $pdo->prepare("SELECT intern_id FROM internship_enrollments WHERE id = ?");
            $stmt->execute([$enrollmentId]);
            $enr = $stmt->fetch();

            if (!$enr) {
                setFlash('danger', 'Enrollment not found.');
                redirect(baseUrl('admin/documents.php'));
            }

            $docId = generateDocumentId();
            $ins = $pdo->prepare("
                INSERT INTO documents (document_id, enrollment_id, intern_id, document_type, issued_date, status, issued_by, created_at)
                VALUES (?, ?, ?, ?, CURDATE(), 'Issued', ?, NOW())
            ");
            $ins->execute([$docId, $enrollmentId, $enr['intern_id'], $docType, $currentAdmin['id']]);

            logAdminAction($pdo, $currentAdmin['id'], 'Issue Document', 'documents', null, null, ['doc_id' => $docId, 'type' => $docType]);
            setFlash('success', "{$docType} (#{$docId}) generated and issued successfully.");
            redirect(baseUrl('admin/documents.php'));

        } catch (PDOException $e) {
            setFlash('danger', 'Error creating document: ' . $e->getMessage());
            redirect(baseUrl('admin/documents.php'));
        }
    }

    if ($action === 'revoke_document') {
        $docId = (int)($_POST['id'] ?? 0);
        $reason = trim($_POST['revoked_reason'] ?? 'Administrative decision');

        try {
            $upd = $pdo->prepare("UPDATE documents SET status = 'Revoked', revoked_reason = ?, updated_at = NOW() WHERE id = ?");
            $upd->execute([$reason, $docId]);

            logAdminAction($pdo, $currentAdmin['id'], 'Revoke Document', 'documents', $docId);
            setFlash('success', 'Document marked as Revoked.');
            redirect(baseUrl('admin/documents.php'));
        } catch (PDOException $e) {
            setFlash('danger', 'Error: ' . $e->getMessage());
            redirect(baseUrl('admin/documents.php'));
        }
    }
}

// Fetch documents
$documents = $pdo->query("
    SELECT d.*, i.full_name AS intern_name, i.email AS intern_email, i.college_name,
           p.title AS program_title, p.duration AS program_duration,
           e.start_date, e.end_date
    FROM documents d
    JOIN interns i ON d.intern_id = i.id
    JOIN internship_enrollments e ON d.enrollment_id = e.id
    JOIN internship_programs p ON e.program_id = p.id
    ORDER BY d.created_at DESC
")->fetchAll();

// Active enrollments for document generator
$activeEnrollments = $pdo->query("
    SELECT e.id, i.full_name, p.title AS program_title
    FROM internship_enrollments e
    JOIN interns i ON e.intern_id = i.id
    JOIN internship_programs p ON e.program_id = p.id
    ORDER BY i.full_name ASC
")->fetchAll();
?>

<!-- ═══════════════════ HEADER CONTROLS ═══════════════════ -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Official Documents & Letters</h4>
        <p class="text-muted small mb-0">Generate, preview, and issue verifiable Offer Letters, Joining Letters, and Experience Certificates.</p>
    </div>
    <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#generateDocModal">
        <i class="bi bi-file-earmark-plus-fill me-2"></i>Generate Document
    </button>
</div>

<!-- ═══════════════════ DOCUMENTS TABLE ═══════════════════ -->
<div class="panel-card">
    <div class="panel-header">
        <h5 class="panel-title">
            <i class="bi bi-file-earmark-text text-primary"></i> Generated Documents (<?= count($documents) ?>)
        </h5>
    </div>

    <div class="panel-body p-0">
        <?php if (empty($documents)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-file-earmark-x fs-1 d-block mb-2 text-secondary"></i>
                <h5 class="fw-bold">No Documents Generated Yet</h5>
                <p class="small mb-0">Select an intern enrollment to issue an official Offer Letter or Joining Letter.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Document ID</th>
                            <th>Recipient Intern</th>
                            <th>Document Type</th>
                            <th>Program Domain</th>
                            <th>Issued Date</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $doc): ?>
                            <tr>
                                <td class="fw-bold font-monospace text-primary"><?= e($doc['document_id']) ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($doc['intern_name']) ?></div>
                                    <small class="text-muted"><?= e($doc['college_name']) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= e($doc['document_type']) ?></span>
                                </td>
                                <td>
                                    <div class="small fw-semibold"><?= e($doc['program_title']) ?></div>
                                    <small class="text-muted"><?= e($doc['program_duration']) ?></small>
                                </td>
                                <td><?= formatDate($doc['issued_date']) ?></td>
                                <td>
                                    <?= statusBadge($doc['status']) ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-1">
                                        <!-- View / Print Modal Trigger -->
                                        <button type="button" class="btn btn-action" data-bs-toggle="modal" data-bs-target="#viewDocModal<?= (int)$doc['id'] ?>" title="Preview & Print">
                                            <i class="bi bi-eye"></i>
                                        </button>

                                        <?php if ($doc['status'] !== 'Revoked'): ?>
                                            <form method="POST" action="<?= baseUrl('admin/documents.php') ?>" class="d-inline" onsubmit="return confirm('Revoke this document?');">
                                                <?= csrfField() ?>
                                                <input type="hidden" name="action" value="revoke_document">
                                                <input type="hidden" name="id" value="<?= (int)$doc['id'] ?>">
                                                <button type="submit" class="btn btn-action btn-action-danger" title="Revoke">
                                                    <i class="bi bi-shield-x"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>

                            <!-- Document Printable Preview Modal -->
                            <div class="modal fade" id="viewDocModal<?= (int)$doc['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                    <div class="modal-content border-0 shadow-lg">
                                        <div class="modal-header bg-navy text-white">
                                            <h5 class="modal-title fw-bold"><?= e($doc['document_type']) ?> — <?= e($doc['document_id']) ?></h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body p-4 p-md-5 bg-white text-dark" id="printableDoc<?= (int)$doc['id'] ?>">
                                            <!-- Letterhead -->
                                            <div class="text-center pb-3 border-bottom mb-4">
                                                <h3 class="fw-bold mb-0 text-navy font-display">EXCELLENCE CLASSES BY REPSWAL</h3>
                                                <div class="text-gold fw-bold small text-uppercase letter-spacing-1">Learn. Develop. Grow.</div>
                                                <div class="text-muted small mt-1">
                                                    Educational & IT Training Institute • Sole Proprietorship by Amit Repswal<br>
                                                    Nawalgarh, Jhunjhunu, Rajasthan - 333308 | <?= e(getSetting('site_email', 'info@excellenceclasses.com')) ?>
                                                </div>
                                            </div>

                                            <div class="d-flex justify-content-between align-items-center mb-4 small">
                                                <div><strong>Ref ID:</strong> <?= e($doc['document_id']) ?></div>
                                                <div><strong>Date:</strong> <?= formatDate($doc['issued_date']) ?></div>
                                            </div>

                                            <div class="mb-4">
                                                <strong>To,</strong><br>
                                                <strong><?= e($doc['intern_name']) ?></strong><br>
                                                <?= e($doc['college_name']) ?><br>
                                                Email: <?= e($doc['intern_email']) ?>
                                            </div>

                                            <h5 class="fw-bold text-center mb-4 text-decoration-underline">
                                                SUB: OFFICIAL INTERNSHIP <?= strtoupper(e($doc['document_type'])) ?>
                                            </h5>

                                            <div class="leading-relaxed text-secondary mb-4">
                                                <p>Dear <strong><?= e($doc['intern_name']) ?></strong>,</p>

                                                <?php if ($doc['document_type'] === 'Offer Letter'): ?>
                                                    <p>
                                                        We are pleased to offer you an internship position as an <strong>Intern in <?= e($doc['program_title']) ?></strong> at <strong>Excellence Classes By Repswal</strong>. Based on your application and academic credentials, we believe you possess the technical aptitude to benefit greatly from our practical training curriculum.
                                                    </p>
                                                    <p>
                                                        Your internship is scheduled to commence on <strong><?= formatDate($doc['start_date']) ?></strong> and conclude on <strong><?= formatDate($doc['end_date']) ?></strong> (Duration: <?= e($doc['program_duration']) ?>). During this period, you will be assigned structured software development tasks, project deliverables, and hands-on coding milestones.
                                                    </p>
                                                <?php elseif ($doc['document_type'] === 'Joining Letter'): ?>
                                                    <p>
                                                        This is to confirm that <strong><?= e($doc['intern_name']) ?></strong> has formally joined the <strong><?= e($doc['program_title']) ?></strong> program at Excellence Classes By Repswal with effect from <strong><?= formatDate($doc['start_date']) ?></strong>.
                                                    </p>
                                                    <p>
                                                        The intern has been provided access to our Intern Portal and assigned initial curriculum tasks under direct mentorship.
                                                    </p>
                                                <?php else: ?>
                                                    <p>
                                                        This is to certify that <strong><?= e($doc['intern_name']) ?></strong> has completed an internship in <strong><?= e($doc['program_title']) ?></strong> at Excellence Classes By Repswal from <strong><?= formatDate($doc['start_date']) ?></strong> to <strong><?= formatDate($doc['end_date']) ?></strong>.
                                                    </p>
                                                    <p>
                                                        During the tenure, the intern demonstrated commendable technical dedication and software engineering discipline.
                                                    </p>
                                                <?php endif; ?>

                                                <p>
                                                    We look forward to a productive and rewarding learning journey with our institute.
                                                </p>
                                            </div>

                                            <!-- Signature Block -->
                                            <div class="row pt-4 mt-4 border-top">
                                                <div class="col-6">
                                                    <small class="text-muted d-block">Authorized Seal:</small>
                                                    <div class="mt-2 text-navy fw-bold small">
                                                        EXCELLENCE CLASSES BY REPSWAL<br>
                                                        NAWALGARH (RAJASTHAN)
                                                    </div>
                                                </div>
                                                <div class="col-6 text-end">
                                                    <small class="text-muted d-block">Authorized Signatory:</small>
                                                    <div class="mt-3 fw-bold text-dark">Amit Repswal</div>
                                                    <div class="small text-muted">Proprietor</div>
                                                    <div class="text-muted" style="font-size: 0.72rem;">Excellence Classes By Repswal</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal-footer bg-light">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            <button type="button" class="btn btn-primary" onclick="printDoc('printableDoc<?= (int)$doc['id'] ?>')">
                                                <i class="bi bi-printer me-1"></i>Print Document
                                            </button>
                                        </div>
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

<!-- ═══════════════════ GENERATE DOCUMENT MODAL ═══════════════════ -->
<div class="modal fade" id="generateDocModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form method="POST" action="<?= baseUrl('admin/documents.php') ?>">
                <?= csrfField() ?>
                <input type="hidden" name="action" value="create_document">

                <div class="modal-header bg-navy text-white">
                    <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-plus me-2 text-gold"></i>Issue Official Document</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Enrolled Intern</label>
                        <select name="enrollment_id" class="form-select form-select-lg" required>
                            <option value="">-- Choose enrolled intern --</option>
                            <?php foreach ($activeEnrollments as $ae): ?>
                                <option value="<?= (int)$ae['id'] ?>">
                                    <?= e($ae['full_name']) ?> (<?= e($ae['program_title']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Document Type</label>
                        <select name="document_type" class="form-select form-select-lg" required>
                            <option value="Offer Letter">Internship Offer Letter</option>
                            <option value="Joining Letter">Internship Joining Letter</option>
                            <option value="Experience Certificate">Internship Experience Certificate</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold">Generate & Issue</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function printDoc(id) {
    const el = document.getElementById(id);
    const win = window.open('', '', 'height=750,width=900');
    win.document.write('<html><head><title>Print Document</title>');
    win.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">');
    win.document.write('<style>body{padding:40px; font-family:"Inter",sans-serif; color:#212529;}</style>');
    win.document.write('</head><body>');
    win.document.write(el.innerHTML);
    win.document.write('</body></html>');
    win.document.close();
    win.focus();
    setTimeout(() => { win.print(); win.close(); }, 500);
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
