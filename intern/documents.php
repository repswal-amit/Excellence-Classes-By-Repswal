<?php
/**
 * Intern Documents (Offer Letters & Joining Letters)
 * Excellence Classes By Repswal
 */

$internPageTitle = 'My Official Documents';
$currentInternPage = 'documents';

require_once __DIR__ . '/includes/header.php';

// Fetch documents issued to this intern
try {
    $stmt = $pdo->prepare("
        SELECT d.*, p.title AS program_title, p.duration AS program_duration,
               e.start_date, e.end_date, i.full_name AS intern_name, i.college_name, i.email AS intern_email
        FROM documents d
        JOIN internship_enrollments e ON d.enrollment_id = e.id
        JOIN internship_programs p ON e.program_id = p.id
        JOIN interns i ON d.intern_id = i.id
        WHERE d.intern_id = ?
        ORDER BY d.created_at DESC
    ");
    $stmt->execute([$internId]);
    $myDocuments = $stmt->fetchAll();
} catch (PDOException $e) {
    $myDocuments = [];
}
?>

<!-- ═══════════════════ HEADER ═══════════════════ -->
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-1">My Official Documents</h4>
        <p class="text-muted small mb-0">Access official Offer Letters, Joining Letters, and Experience Certificates issued by the institute.</p>
    </div>
</div>

<!-- ═══════════════════ DOCUMENTS CARDS & TABLE ═══════════════════ -->
<div class="panel-card">
    <div class="panel-body p-0">
        <?php if (empty($myDocuments)): ?>
            <div class="p-5 text-center text-muted">
                <i class="bi bi-file-earmark-text fs-1 d-block mb-2 text-secondary"></i>
                <h5 class="fw-bold">No Documents Issued Yet</h5>
                <p class="small mb-0">Once your internship application is approved by the administration, your official Offer Letter will be issued here.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Document ID</th>
                            <th>Document Title</th>
                            <th>Internship Program</th>
                            <th>Issue Date</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myDocuments as $doc): ?>
                            <tr>
                                <td class="fw-bold font-monospace text-primary"><?= e($doc['document_id']) ?></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($doc['document_type']) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-primary border"><?= e($doc['program_title']) ?></span>
                                </td>
                                <td><?= formatDate($doc['issued_date']) ?></td>
                                <td><?= statusBadge($doc['status']) ?></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#viewDocModal<?= (int)$doc['id'] ?>">
                                        <i class="bi bi-printer me-1"></i>View & Print
                                    </button>
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
                                                <?php endif; ?>

                                                <p>
                                                    We wish you outstanding success in your engineering endeavors.
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
