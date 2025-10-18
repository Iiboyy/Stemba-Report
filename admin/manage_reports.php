<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
    $user_filter = isset($_GET['user_id']) ? $_GET['user_id'] : '';

    $sql = "SELECT r.*, u.username, u.email 
            FROM reports r 
            JOIN users u ON r.user_id = u.id 
            WHERE 1=1";

    $params = [];

    if (!empty($search)) {
        $sql .= " AND (r.title LIKE ? OR r.description LIKE ? OR r.location LIKE ? OR u.username LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }

    if (!empty($status_filter)) {
        $sql .= " AND r.status = ?";
        $params[] = $status_filter;
    }

    if (!empty($user_filter)) {
        $sql .= " AND r.user_id = ?";
        $params[] = $user_filter;
    }

    $sql .= " ORDER BY r.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reports = $stmt->fetchAll();

    $users_stmt = $pdo->query("SELECT id, username FROM users ORDER BY username");
    $all_users = $users_stmt->fetchAll();
} catch (PDOException $e) {
    $reports = [];
    $all_users = [];
    $error = "Terjadi kesalahan saat mengambil data!";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_report'])) {
    $report_id = $_POST['report_id'];

    try {
        $stmt = $pdo->prepare("SELECT image FROM reports WHERE id = ?");
        $stmt->execute([$report_id]);
        $report = $stmt->fetch();

        if ($report) {
            if ($report['image']) {
                $images = json_decode($report['image'], true);
                if (is_array($images)) {
                    foreach ($images as $image) {
                        if (file_exists('../uploads/' . $image)) {
                            unlink('../uploads/' . $image);
                        }
                    }
                } else {
                    if (file_exists('../uploads/' . $report['image'])) {
                        unlink('../uploads/' . $report['image']);
                    }
                }
            }

            $stmt = $pdo->prepare("DELETE FROM reports WHERE id = ?");
            $stmt->execute([$report_id]);

            header("Location: manage_reports.php?success=Laporan berhasil dihapus!");
            exit();
        }
    } catch (PDOException $e) {
        $error = "Gagal menghapus laporan!";
    }
}

function timeAgo($timestamp)
{
    $current_time = time();
    $timestamp = strtotime($timestamp);
    $time_difference = $current_time - $timestamp;

    if ($time_difference < 60) {
        return 'Baru saja';
    } elseif ($time_difference < 3600) {
        $minutes = floor($time_difference / 60);
        return $minutes . ' menit lalu';
    } elseif ($time_difference < 86400) {
        $hours = floor($time_difference / 3600);
        return $hours . ' jam lalu';
    } else {
        $days = floor($time_difference / 86400);
        return $days . ' hari lalu';
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Laporan - Admin Stemba Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .admin-container {
            padding: 80px 0 30px;
            background: #f8fafc;
            min-height: 100vh;
        }

        .admin-sidebar {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            padding: 1.5rem;
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .admin-main {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            padding: 2rem;
        }

        .nav-admin .nav-link {
            color: #475569;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-admin .nav-link:hover,
        .nav-admin .nav-link.active {
            background: rgba(37, 99, 235, 0.1);
            color: #2563eb;
        }

        .nav-admin .nav-link i {
            width: 20px;
            margin-right: 0.5rem;
        }

        .search-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid #e2e8f0;
        }

        .report-item {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: white;
            transition: all 0.3s ease;
        }

        .report-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-hilang {
            background: #fef3c7;
            color: #d97706;
        }

        .status-ditemukan {
            background: #d1fae5;
            color: #065f46;
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

        .btn-delete {
            background: #dc2626;
            border-color: #dc2626;
            color: white;
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .btn-delete:hover {
            background: #b91c1c;
            border-color: #b91c1c;
            transform: translateY(-1px);
        }

        .btn-detail {
            background: #2563eb;
            border-color: #2563eb;
            color: white;
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .btn-detail:hover {
            background: #1d4ed8;
            border-color: #1d4ed8;
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

        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: #64748b;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .admin-container {
                padding: 70px 0 20px;
            }

            .admin-sidebar {
                position: static;
                margin-bottom: 1.5rem;
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
            .action-group {
                flex-direction: column;
            }

            .action-group .btn {
                width: 100%;
                text-align: center;
                justify-content: center;
            }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        mark.bg-warning {
            background-color: #fef3c7 !important;
            padding: 0.1rem 0.2rem;
            border-radius: 2px;
        }

        @media (min-width: 992px) {
            .action-group {
                min-width: 150px;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="../index.php">
                <div class="logo-placeholder me-2">
                    <img src="../assets/img/logo.png" alt="logo" width="55" height="50">
                </div>
                <div class="brand-text">
                    <span class="fw-bold text-dark">SMK NEGERI 7</span>
                    <small class="text-muted d-block">Stemba Report</small>
                </div>
            </a>

            <div class="navbar-nav ms-auto">
                <div class="d-flex gap-2 align-items-center">
                    <span class="text-dark me-2">
                        <i class="fas fa-user-shield me-1"></i>Admin Panel
                    </span>
                    <a href="../logout.php" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger" id="deleteModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Hapus Laporan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Apakah Anda yakin ingin menghapus laporan ini? Tindakan ini tidak dapat dibatalkan dan akan menghapus semua data termasuk gambar.</p>
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

    <section class="admin-container">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 mb-4">
                    <div class="admin-sidebar">
                        <h5 class="fw-bold mb-3">Admin Menu</h5>
                        <nav class="nav-admin">
                            <a class="nav-link" href="index.php">
                                <i class="fas fa-tachometer-alt"></i>Dashboard
                            </a>
                            <a class="nav-link active" href="manage_reports.php">
                                <i class="fas fa-clipboard-list"></i>Kelola Laporan
                            </a>
                            <a class="nav-link" href="manage_users.php">
                                <i class="fas fa-users"></i>Kelola User
                            </a>
                            <a class="nav-link" href="../index.php">
                                <i class="fas fa-home"></i>Kembali ke Site
                            </a>
                        </nav>
                    </div>
                </div>

                <div class="col-lg-9">
                    <div class="admin-main">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2 class="fw-bold mb-0"><i class="fas fa-clipboard-list me-2"></i>Kelola Laporan</h2>
                            <span class="badge bg-primary"><?php echo count($reports); ?> Laporan</span>
                        </div>

                        <?php if (isset($_GET['success'])): ?>
                            <div class="success-message">
                                <i class="fas fa-check-circle"></i>
                                <?php echo htmlspecialchars($_GET['success']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="search-card">
                            <h5 class="fw-semibold mb-3">Filter Laporan</h5>
                            <form method="GET" action="">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Pencarian</label>
                                        <input type="text" name="search" class="form-control"
                                            placeholder="Cari judul, deskripsi, lokasi, atau user..."
                                            value="<?php echo htmlspecialchars($search); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">Status</label>
                                        <select name="status" class="form-select">
                                            <option value="">Semua Status</option>
                                            <option value="hilang" <?php echo $status_filter == 'hilang' ? 'selected' : ''; ?>>Masih Hilang</option>
                                            <option value="ditemukan" <?php echo $status_filter == 'ditemukan' ? 'selected' : ''; ?>>Sudah Ditemukan</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-semibold">User</label>
                                        <select name="user_id" class="form-select">
                                            <option value="">Semua User</option>
                                            <?php foreach ($all_users as $user): ?>
                                                <option value="<?php echo $user['id']; ?>"
                                                    <?php echo $user_filter == $user['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($user['username']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold">&nbsp;</label>
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-filter me-1"></i>Filter
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <?php if (empty($reports)): ?>
                            <div class="empty-state">
                                <i class="fas fa-search"></i>
                                <h5 class="fw-semibold">Tidak ada laporan ditemukan</h5>
                                <p class="text-muted">Coba ubah filter pencarian Anda</p>
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
                                                        <img src="../uploads/<?php echo htmlspecialchars($first_image); ?>"
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

                                            <div class="col-md-6 mb-3 mb-md-0">
                                                <h6 class="fw-semibold mb-1"><?php echo htmlspecialchars($report['title']); ?></h6>
                                                <p class="text-muted mb-2 small">
                                                    <?php echo strlen($report['description']) > 100 ? 
                                                        substr(htmlspecialchars($report['description']), 0, 100) . '...' : 
                                                        htmlspecialchars($report['description']); ?>
                                                </p>

                                                <div class="d-flex flex-wrap gap-3 small text-muted mb-2">
                                                    <span><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($report['location']); ?></span>
                                                    <span><i class="fas fa-calendar me-1"></i><?php echo date('d M Y', strtotime($report['date_lost'])); ?></span>
                                                    <span><i class="fas fa-user me-1"></i><?php echo htmlspecialchars($report['username']); ?></span>
                                                </div>

                                                <span class="status-badge <?php echo $report['status'] == 'ditemukan' ? 'status-ditemukan' : 'status-hilang'; ?>">
                                                    <i class="fas <?php echo $report['status'] == 'ditemukan' ? 'fa-check' : 'fa-clock'; ?> me-1"></i>
                                                    <?php echo ucfirst($report['status']); ?>
                                                </span>

                                                <small class="text-muted d-block mt-1">
                                                    <i class="fas fa-clock me-1"></i><?php echo timeAgo($report['created_at']); ?>
                                                </small>
                                            </div>

                                            <div class="col-md-3">
                                                <div class="action-group">
                                                    <a href="../report_detail.php?id=<?php echo $report['id']; ?>" 
                                                       class="btn btn-detail" target="_blank">
                                                        <i class="fas fa-eye me-1"></i>Lihat Detail
                                                    </a>
                                                    <button type="button" class="btn btn-delete"
                                                        data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                        data-report-id="<?php echo $report['id']; ?>">
                                                        <i class="fas fa-trash me-1"></i>Hapus
                                                    </button>
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

        class AdvancedSearch {
            constructor() {
                this.reports = <?php echo json_encode($reports); ?>;
                this.filteredReports = [...this.reports];
                this.init();
            }

            init() {
                this.setupRealTimeSearch();
                this.setupMultipleFilters();
                this.setupSorting();
            }

            setupRealTimeSearch() {
                const searchInput = document.querySelector('input[name="search"]');
                let timeout = null;

                searchInput.addEventListener('input', (e) => {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => {
                        this.applyFilters();
                    }, 300);
                });
            }

            applyFilters() {
                const searchTerm = document.querySelector('input[name="search"]').value.toLowerCase();
                const statusFilter = document.querySelector('select[name="status"]').value;
                const userFilter = document.querySelector('select[name="user_id"]').value;

                this.filteredReports = this.reports.filter(report => {
                    const matchesSearch = !searchTerm ||
                        report.title.toLowerCase().includes(searchTerm) ||
                        report.description.toLowerCase().includes(searchTerm) ||
                        report.location.toLowerCase().includes(searchTerm) ||
                        report.username.toLowerCase().includes(searchTerm);

                    const matchesStatus = !statusFilter || report.status === statusFilter;

                    const matchesUser = !userFilter || report.user_id == userFilter;

                    return matchesSearch && matchesStatus && matchesUser;
                });

                this.renderResults();
                this.updateResultsCount();
            }

            renderResults() {
                const container = document.querySelector('.reports-list');

                if (this.filteredReports.length === 0) {
                    container.innerHTML = `
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h5 class="fw-semibold">Tidak ada laporan ditemukan</h5>
                            <p class="text-muted">Coba ubah filter pencarian Anda</p>
                        </div>
                    `;
                    return;
                }

                container.innerHTML = this.filteredReports.map(report => {
                    const images = report.image ? (typeof report.image === 'string' ? JSON.parse(report.image) : report.image) : [];
                    const firstImage = images.length > 0 ? images[0] : report.image;
                    const imageCount = images.length > 0 ? images.length : (report.image ? 1 : 0);
                    
                    return `
                    <div class="report-item fade-in">
                        <div class="row align-items-center">
                            <div class="col-auto mb-3 mb-md-0">
                                <div class="position-relative">
                                    ${firstImage ? 
                                        `<img src="../uploads/${firstImage}" alt="${report.title}" class="report-image">` :
                                        `<div class="report-image bg-light d-flex align-items-center justify-content-center">
                                            <i class="fas fa-image text-muted"></i>
                                        </div>`
                                    }
                                    ${imageCount > 1 ? 
                                        `<span class="image-count-badge">+${imageCount - 1}</span>` : 
                                        ''
                                    }
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3 mb-md-0">
                                <h6 class="fw-semibold mb-1">${this.highlightText(report.title)}</h6>
                                <p class="text-muted mb-2 small">${this.highlightText(report.description)}</p>
                                
                                <div class="d-flex flex-wrap gap-3 small text-muted mb-2">
                                    <span><i class="fas fa-map-marker-alt me-1"></i>${report.location}</span>
                                    <span><i class="fas fa-calendar me-1"></i>${this.formatDate(report.date_lost)}</span>
                                    <span><i class="fas fa-user me-1"></i>${report.username}</span>
                                </div>
                                
                                <span class="status-badge ${report.status === 'ditemukan' ? 'status-ditemukan' : 'status-hilang'}">
                                    <i class="fas ${report.status === 'ditemukan' ? 'fa-check' : 'fa-clock'} me-1"></i>
                                    ${report.status.charAt(0).toUpperCase() + report.status.slice(1)}
                                </span>
                                
                                <small class="text-muted d-block mt-1">
                                    <i class="fas fa-clock me-1"></i>${this.timeAgo(report.created_at)}
                                </small>
                            </div>
                            
                            <div class="col-md-3">
                                <div class="action-group">
                                    <a href="../report_detail.php?id=${report.id}" 
                                       class="btn btn-detail" target="_blank">
                                        <i class="fas fa-eye me-1"></i>Lihat Detail
                                    </a>
                                    <button type="button" class="btn btn-delete" 
                                            data-bs-toggle="modal" data-bs-target="#deleteModal"
                                            data-report-id="${report.id}">
                                        <i class="fas fa-trash me-1"></i>Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `}).join('');
            }

            highlightText(text) {
                const searchTerm = document.querySelector('input[name="search"]').value.toLowerCase();
                if (!searchTerm) return text;

                const regex = new RegExp(`(${searchTerm})`, 'gi');
                return text.replace(regex, '<mark class="bg-warning">$1</mark>');
            }

            updateResultsCount() {
                const badge = document.querySelector('.badge.bg-primary');
                if (badge) {
                    badge.textContent = `${this.filteredReports.length} Laporan`;
                }
            }

            formatDate(dateString) {
                return new Date(dateString).toLocaleDateString('id-ID', {
                    day: 'numeric',
                    month: 'short',
                    year: 'numeric'
                });
            }

            timeAgo(timestamp) {
                const current_time = Date.now();
                const timestamp_ms = new Date(timestamp).getTime();
                const time_difference = current_time - timestamp_ms;

                if (time_difference < 60000) {
                    return 'Baru saja';
                } else if (time_difference < 3600000) {
                    const minutes = Math.floor(time_difference / 60000);
                    return minutes + ' menit lalu';
                } else if (time_difference < 86400000) {
                    const hours = Math.floor(time_difference / 3600000);
                    return hours + ' jam lalu';
                } else {
                    const days = Math.floor(time_difference / 86400000);
                    return days + ' hari lalu';
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            new AdvancedSearch();
        });
    </script>
</body>
</html>