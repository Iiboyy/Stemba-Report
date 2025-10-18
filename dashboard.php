<?php
include 'includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

try {
    $stmt = $pdo->prepare("
        SELECT * FROM reports 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$user_id]);
    $reports = $stmt->fetchAll();

    $total_reports = count($reports);
    $found_reports = 0;
    $lost_reports = 0;

    foreach ($reports as $report) {
        if ($report['status'] == 'ditemukan') {
            $found_reports++;
        } else {
            $lost_reports++;
        }
    }
} catch (PDOException $e) {
    $error = "Terjadi kesalahan saat mengambil data!";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $report_id = $_POST['report_id'];
    $new_status = $_POST['status'];

    try {
        $stmt = $pdo->prepare("UPDATE reports SET status = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$new_status, $report_id, $user_id]);

        header("Location: dashboard.php?success=Status berhasil diupdate!");
        exit();
    } catch (PDOException $e) {
        $error = "Gagal update status!";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_report'])) {
    $report_id = $_POST['report_id'];

    try {
        $stmt = $pdo->prepare("SELECT image FROM reports WHERE id = ? AND user_id = ?");
        $stmt->execute([$report_id, $user_id]);
        $report = $stmt->fetch();

        if ($report) {
            if ($report['image']) {
                $images = json_decode($report['image'], true);
                if (is_array($images)) {
                    foreach ($images as $image) {
                        if (file_exists('uploads/' . $image)) {
                            unlink('uploads/' . $image);
                        }
                    }
                } else {
                    if (file_exists('uploads/' . $report['image'])) {
                        unlink('uploads/' . $report['image']);
                    }
                }
            }

            $stmt = $pdo->prepare("DELETE FROM reports WHERE id = ? AND user_id = ?");
            $stmt->execute([$report_id, $user_id]);

            header("Location: dashboard.php?success=Laporan berhasil dihapus!");
            exit();
        } else {
            $error = "Laporan tidak ditemukan!";
        }
    } catch (PDOException $e) {
        $error = "Gagal menghapus laporan!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Stemba Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .dashboard-container {
            padding: 100px 0 30px;
            background: #f8fafc;
            min-height: 100vh;
        }

        .dashboard-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            margin-bottom: 1.5rem;
            overflow: hidden;
        }

        .dashboard-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
        }

        .dashboard-body {
            padding: 2rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem 1rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.25rem;
        }

        .stat-icon.primary {
            background: rgba(37, 99, 235, 0.1);
            color: #2563eb;
        }

        .stat-icon.warning {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }

        .stat-icon.success {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .stat-number {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            line-height: 1;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .report-item {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            background: white;
            transition: all 0.3s ease;
        }

        .report-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border-color: #cbd5e1;
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .status-hilang {
            background: #fef3c7;
            color: #d97706;
            border: 1px solid #fcd34d;
        }

        .status-ditemukan {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #64748b;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }

        .empty-state h5 {
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }

        .empty-state p {
            font-size: 0.95rem;
            margin-bottom: 2rem;
            line-height: 1.5;
        }

        .empty-state .btn {
            padding: 0.75rem 2rem;
            font-size: 1rem;
            border-radius: 10px;
            font-weight: 600;
        }

        .btn-status {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: 2px solid;
        }

        .btn-found {
            background: #10b981;
            border-color: #10b981;
            color: white;
        }

        .btn-found:hover {
            background: #059669;
            border-color: #059669;
            transform: translateY(-1px);
        }

        .btn-lost {
            background: #f59e0b;
            border-color: #f59e0b;
            color: white;
        }

        .btn-lost:hover {
            background: #d97706;
            border-color: #d97706;
            transform: translateY(-1px);
        }

        .btn-delete {
            background: #dc2626;
            border-color: #dc2626;
            color: white;
        }

        .btn-delete:hover {
            background: #b91c1c;
            border-color: #b91c1c;
            transform: translateY(-1px);
        }

        .success-message {
            background: #d1fae5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .error-message {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .report-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            position: relative;
        }

        .image-count-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #2563eb;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 600;
            border: 2px solid white;
        }

        .report-meta {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.8rem;
            color: #64748b;
        }

        .meta-item i {
            width: 12px;
            color: #2563eb;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .action-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            width: 100%;
        }

        .status-action {
            display: flex;
            gap: 0.5rem;
        }

        .status-action form {
            flex: 1;
        }

        .status-action .btn {
            width: 100%;
            text-align: center;
            justify-content: center;
        }

        .other-actions {
            display: flex;
            gap: 0.5rem;
        }

        .other-actions .btn {
            flex: 1;
            text-align: center;
            justify-content: center;
        }

        /* Modal styles */
        .modal-danger .modal-header {
            background: #dc2626;
            color: white;
        }

        .modal-danger .btn-close {
            filter: invert(1);
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .dashboard-container {
                padding: 90px 0 20px;
            }

            .dashboard-header {
                padding: 1.25rem 1.5rem;
            }

            .dashboard-body {
                padding: 1.5rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }

            .stat-card {
                padding: 1.25rem 0.75rem;
            }

            .stat-number {
                font-size: 1.5rem;
            }

            .report-item {
                padding: 1rem;
            }

            .report-image {
                width: 60px;
                height: 60px;
            }

            .image-count-badge {
                width: 20px;
                height: 20px;
                font-size: 0.6rem;
            }

            .report-meta {
                gap: 0.75rem;
            }

            .empty-state {
                padding: 2rem 1rem;
            }

            .empty-state i {
                font-size: 2.5rem;
            }

            .action-buttons {
                justify-content: flex-start;
                margin-top: 1rem;
            }

            .action-group {
                width: 100%;
            }
        }

        @media (max-width: 576px) {
            .navbar-nav .d-flex {
                flex-direction: column;
                gap: 0.5rem;
                width: 100%;
            }

            .navbar-nav .btn {
                width: 100%;
                justify-content: center;
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-buttons .btn {
                width: 100%;
                text-align: center;
            }

            .status-action {
                flex-direction: column;
            }

            .other-actions {
                flex-direction: column;
            }
        }

        @media (min-width: 992px) {
            .action-group {
                min-width: 200px;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <div class="logo-placeholder me-2">
                    <img src="assets/img/logo.png" alt="logo" width="55" height="50">
                </div>
                <div class="brand-text">
                    <span class="fw-bold text-dark">SMK NEGERI 7</span>
                    <small class="text-muted d-block">Stemba Report</small>
                </div>
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarDashboard">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarDashboard">
                <div class="navbar-nav ms-auto">
                    <div class="d-flex flex-column flex-lg-row gap-2 align-items-center">
                        <a href="add_report.php" class="btn btn-primary btn-sm w-100 w-lg-auto">
                            <i class="fas fa-plus-circle me-1"></i>Tambah Laporan
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-danger">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Hapus Laporan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Apakah Anda yakin ingin menghapus laporan ini? Tindakan ini tidak dapat dibatalkan.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <form method="POST" action="" id="deleteForm">
                        <input type="hidden" name="report_id" id="deleteReportId">
                        <button type="submit" name="delete_report" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i>Ya, Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <section class="dashboard-container">
        <div class="container">
            <?php if (isset($_GET['success'])): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($_GET['success']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="dashboard-card">
                <div class="dashboard-header">
                    <h2 class="mb-1 fw-bold"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</h2>
                    <p class="text-muted mb-0">Kelola laporan barang hilang Anda</p>
                </div>

                <div class="dashboard-body">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon primary">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <div class="stat-number text-primary"><?php echo $total_reports; ?></div>
                            <div class="stat-label">Total Laporan</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon warning">
                                <i class="fas fa-search"></i>
                            </div>
                            <div class="stat-number text-warning"><?php echo $lost_reports; ?></div>
                            <div class="stat-label">Masih Hilang</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-number text-success"><?php echo $found_reports; ?></div>
                            <div class="stat-label">Sudah Ditemukan</div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0 fw-semibold">Laporan Saya</h4>
                        <a href="add_report.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus-circle me-1"></i>Tambah Laporan
                        </a>
                    </div>

                    <?php if (empty($reports)): ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard-list"></i>
                            <h5 class="fw-semibold mb-3">Belum ada laporan</h5>
                            <p class="mb-4 text-muted">Mulai dengan membuat laporan barang hilang pertama Anda</p>
                        </div>
                    <?php else: ?>
                        <div class="reports-list">
                            <?php foreach ($reports as $report): ?>
                                <?php
                                $images = !empty($report['image']) ? json_decode($report['image'], true) : [];
                                $first_image = !empty($images) ? $images[0] : $report['image'];
                                $image_count = is_array($images) ? count($images) : ($report['image'] ? 1 : 0);
                                ?>
                                <div class="report-item">
                                    <div class="row align-items-center">
                                        <div class="col-auto mb-3 mb-md-0">
                                            <div class="position-relative">
                                                <?php if ($first_image): ?>
                                                    <img src="uploads/<?php echo htmlspecialchars($first_image); ?>"
                                                        alt="<?php echo htmlspecialchars($report['title']); ?>"
                                                        class="report-image">
                                                <?php else: ?>
                                                    <div class="report-image bg-light d-flex align-items-center justify-content-center">
                                                        <i class="fas fa-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($image_count > 1): ?>
                                                    <span class="image-count-badge">
                                                        +<?php echo $image_count - 1; ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="col-md-5 mb-3 mb-md-0">
                                            <h6 class="fw-semibold mb-1"><?php echo htmlspecialchars($report['title']); ?></h6>
                                            <p class="text-muted mb-2 small">
                                                <?php echo strlen($report['description']) > 100 ?
                                                    substr(htmlspecialchars($report['description']), 0, 100) . '...' :
                                                    htmlspecialchars($report['description']); ?>
                                            </p>
                                            <div class="report-meta">
                                                <div class="meta-item">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <span><?php echo htmlspecialchars($report['location']); ?></span>
                                                </div>
                                                <div class="meta-item">
                                                    <i class="fas fa-calendar"></i>
                                                    <span><?php echo date('d M Y', strtotime($report['date_lost'])); ?></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-2 mb-3 mb-md-0">
                                            <span class="status-badge <?php echo $report['status'] == 'ditemukan' ? 'status-ditemukan' : 'status-hilang'; ?>">
                                                <i class="fas <?php echo $report['status'] == 'ditemukan' ? 'fa-check' : 'fa-clock'; ?>"></i>
                                                <?php echo ucfirst($report['status']); ?>
                                            </span>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="action-group">
                                                <div class="status-action">
                                                    <?php if ($report['status'] == 'hilang'): ?>
                                                        <form method="POST" class="w-100">
                                                            <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                                            <input type="hidden" name="status" value="ditemukan">
                                                            <button type="submit" name="update_status" class="btn btn-found btn-status">
                                                                <i class="fas fa-check me-1"></i>Ditemukan
                                                            </button>
                                                        </form>
                                                    <?php else: ?>
                                                        <form method="POST" class="w-100">
                                                            <input type="hidden" name="report_id" value="<?php echo $report['id']; ?>">
                                                            <input type="hidden" name="status" value="hilang">
                                                            <button type="submit" name="update_status" class="btn btn-lost btn-status">
                                                                <i class="fas fa-clock me-1"></i>Hilang
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="other-actions">
                                                    <a href="report_detail.php?id=<?php echo $report['id']; ?>" class="btn btn-outline-primary btn-sm">
                                                        <i class="fas fa-eye me-1"></i>Detail
                                                    </a>
                                                    <button type="button" class="btn btn-delete btn-sm"
                                                        data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                        data-report-id="<?php echo $report['id']; ?>">
                                                        <i class="fas fa-trash me-1"></i>Hapus
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteModal = document.getElementById('deleteModal');
            const deleteForm = document.getElementById('deleteForm');
            const deleteReportId = document.getElementById('deleteReportId');

            deleteModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const reportId = button.getAttribute('data-report-id');
                deleteReportId.value = reportId;
            });
        });
    </script>
</body>

</html>