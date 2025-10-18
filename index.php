<?php
include 'includes/config.php';

try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
    $date_filter = isset($_GET['date']) ? $_GET['date'] : '';

    $sql = "SELECT r.*, u.username 
            FROM reports r 
            JOIN users u ON r.user_id = u.id 
            WHERE 1=1";

    $params = [];

    if (!empty($search)) {
        $sql .= " AND (r.title LIKE ? OR r.description LIKE ? OR r.location LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }

    if (!empty($status_filter)) {
        $sql .= " AND r.status = ?";
        $params[] = $status_filter;
    }

    if (!empty($date_filter)) {
        $sql .= " AND r.date_lost = ?";
        $params[] = $date_filter;
    }

    $sql .= " ORDER BY r.created_at DESC LIMIT 6";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reports = $stmt->fetchAll();
} catch (PDOException $e) {
    $reports = [];
    $error = "Terjadi kesalahan saat mengambil data laporan.";
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
    <title>Stemba Report - Sistem Pelaporan Barang Hilang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>
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

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home me-1"></i>Beranda
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#laporan">
                            <i class="fas fa-list me-1"></i>Laporan
                        </a>
                    </li>
                </ul>

                <div class="navbar-auth d-flex gap-2">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['role'] == 'admin'): ?>
                            <a href="admin/index.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-user-shield me-1"></i>Admin Panel
                            </a>
                        <?php endif; ?>
                        <a href="dashboard.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                        <a href="logout.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                        <a href="register.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-user-plus me-1"></i>Daftar
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <section class="hero-professional">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-content">
                        <div class="hero-badge">
                            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                <i class="fas fa-shield-alt me-1"></i>Sistem Terintegrasi
                            </span>
                        </div>
                        <h1 class="hero-title">
                            Sistem Pelaporan Barang Hilang
                        </h1>
                        <p class="hero-description">
                            Temukan barang hilang Anda dengan mudah. Laporkan dan pantau status barang hilang
                            di lingkungan sekolah dengan sistem yang modern dan terpercaya.
                        </p>
                        <div class="hero-actions">
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <a href="add_report.php" class="btn btn-primary btn-lg me-2 mb-2">
                                    <i class="fas fa-plus-circle me-2"></i>Buat Laporan
                                </a>
                                <a href="dashboard.php" class="btn btn-outline-secondary btn-lg mb-2">
                                    <i class="fas fa-chart-bar me-2"></i>Dashboard
                                </a>
                            <?php else: ?>
                                <a href="register.php" class="btn btn-primary btn-lg me-2 mb-2">
                                    <i class="fas fa-rocket me-2"></i>Daftar Sekarang
                                </a>
                                <a href="login.php" class="btn btn-outline-secondary btn-lg mb-2">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="hero-stats">
                            <div class="stat-item">
                                <h4>50+</h4>
                                <p>Laporan Aktif</p>
                            </div>
                            <div class="stat-item">
                                <h4>85%</h4>
                                <p>Rate Berhasil</p>
                            </div>
                            <div class="stat-item">
                                <h4>24/7</h4>
                                <p>Ketersediaan</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mt-4 mt-lg-0">
                    <div class="hero-visual-clean">
                        <div class="feature-showcase">
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-search"></i>
                                </div>
                                <div class="feature-text">
                                    <h5>Cari Barang</h5>
                                    <p>Temukan barang hilang dengan pencarian cerdas</p>
                                </div>
                            </div>
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-camera"></i>
                                </div>
                                <div class="feature-text">
                                    <h5>Bukti Visual</h5>
                                    <p>Lihat foto barang untuk identifikasi yang lebih akurat</p>
                                </div>
                            </div>
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="feature-text">
                                    <h5>Status Transparan</h5>
                                    <p>Pantau perkembangan laporan secara real-time</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="search-professional bg-light py-4">
        <div class="container">
            <div class="search-container">
                <h4 class="search-title text-center text-lg-start">
                    <i class="fas fa-search me-2"></i>Cari Barang Hilang
                </h4>
                <form method="GET" action="index.php" class="search-form">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-5">
                            <label class="form-label fw-semibold">Nama Barang atau Lokasi</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" name="search" class="form-control border-start-0"
                                    placeholder="Contoh: Tas sekolah, dompet, kunci..."
                                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="hilang" <?php echo (isset($_GET['status']) && $_GET['status'] == 'hilang') ? 'selected' : ''; ?>>Masih Hilang</option>
                                <option value="ditemukan" <?php echo (isset($_GET['status']) && $_GET['status'] == 'ditemukan') ? 'selected' : ''; ?>>Sudah Ditemukan</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <label class="form-label fw-semibold">Tanggal</label>
                            <input type="date" name="date" class="form-control"
                                value="<?php echo isset($_GET['date']) ? htmlspecialchars($_GET['date']) : ''; ?>">
                        </div>
                        <div class="col-12 col-md-2">
                            <button type="submit" class="btn btn-primary w-100 py-2">
                                <i class="fas fa-search me-2"></i>Cari
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <section id="laporan" class="reports-professional py-5">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 class="fw-bold">Laporan Terbaru</h2>
                <p class="text-muted">Barang-barang yang baru dilaporkan hilang</p>
            </div>

            <?php if (empty($reports)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Tidak ada laporan ditemukan</h5>
                    <p class="text-muted">Coba ubah kata kunci pencarian atau filter</p>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($reports as $report): ?>
                        <?php
                        $images = !empty($report['image']) ? json_decode($report['image'], true) : [];
                        $first_image = !empty($images) ? $images[0] : $report['image'];
                        ?>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card report-card h-100 border-0 shadow-sm">
                                <div class="card-img-container">
                                    <?php if ($first_image): ?>
                                        <img src="uploads/<?php echo htmlspecialchars($first_image); ?>"
                                            class="card-img-top"
                                            alt="<?php echo htmlspecialchars($report['title']); ?>"
                                            style="height: 200px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center"
                                            style="height: 200px;">
                                            <i class="fas fa-image fa-2x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    <span class="card-badge badge <?php echo $report['status'] == 'ditemukan' ? 'bg-success' : 'bg-warning'; ?>">
                                        <i class="fas <?php echo $report['status'] == 'ditemukan' ? 'fa-check' : 'fa-clock'; ?> me-1"></i>
                                        <?php echo ucfirst($report['status']); ?>
                                    </span>
                                    <?php if (count($images) > 1): ?>
                                        <span class="image-count-badge badge bg-primary">
                                            <i class="fas fa-images me-1"></i>+<?php echo count($images) - 1; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($report['title']); ?></h5>
                                    <p class="card-text text-muted">
                                        <?php echo strlen($report['description']) > 100 ?
                                            substr(htmlspecialchars($report['description']), 0, 100) . '...' :
                                            htmlspecialchars($report['description']); ?>
                                    </p>
                                    <div class="card-meta">
                                        <div class="meta-item">
                                            <i class="fas fa-map-marker-alt text-primary"></i>
                                            <span><?php echo htmlspecialchars($report['location']); ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-calendar text-primary"></i>
                                            <span><?php echo date('d M Y', strtotime($report['date_lost'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($report['username']); ?>
                                        </small>
                                        <small class="text-muted">
                                            <?php echo timeAgo($report['created_at']); ?>
                                        </small>
                                    </div>
                                    <div class="mt-2">
                                        <a href="report_detail.php?id=<?php echo $report['id']; ?>"
                                            class="btn btn-outline-primary btn-sm w-100">
                                            <i class="fas fa-eye me-1"></i>Lihat Detail
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="process-section py-5 bg-white">
        <div class="container">
            <div class="section-header text-center mb-5">
                <h2 class="fw-bold">Alur Pengembalian Barang</h2>
                <p class="text-muted">Proses pengembalian barang hilang yang ditemukan</p>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="process-steps">
                        <div class="process-step">
                            <div class="step-number">
                                <span>1</span>
                            </div>
                            <div class="step-content">
                                <div class="step-icon">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                                <h5 class="fw-semibold">Laporan Dibuat</h5>
                                <p class="text-muted mb-0">
                                    Barang hilang dilaporkan melalui sistem Stemba Report dengan mengisi form laporan
                                    dan upload foto barang.
                                </p>
                            </div>
                        </div>

                        <div class="process-step">
                            <div class="step-number">
                                <span>2</span>
                            </div>
                            <div class="step-content">
                                <div class="step-icon">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <h5 class="fw-semibold">Serahkan ke Guru Kesiswaan</h5>
                                <p class="text-muted mb-0">
                                    Barang yang ditemukan diserahkan kepada Guru Kesiswaan untuk diverifikasi
                                    dan dicatat dalam sistem.
                                </p>
                            </div>
                        </div>

                        <div class="process-step">
                            <div class="step-number">
                                <span>3</span>
                            </div>
                            <div class="step-content">
                                <div class="step-icon">
                                    <i class="fas fa-door-open"></i>
                                </div>
                                <h5 class="fw-semibold">Ambil di Ruang Kesiswaan</h5>
                                <p class="text-muted mb-0">
                                    Pemilik barang dapat mengambil barangnya di Ruang Kesiswaan dengan menunjukkan
                                    bukti identitas dan konfirmasi laporan.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="info-card mt-5">
                        <div class="row align-items-center">
                            <div class="col-md-2 text-center">
                                <div class="info-icon">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                            </div>
                            <div class="col-md-10">
                                <h6 class="fw-semibold mb-2">Informasi Penting</h6>
                                <p class="mb-0">
                                    <strong>Waktu Pengambilan:</strong> Senin - Jumat, 07.00 - 15.00 WIB<br>
                                    <strong>Lokasi:</strong> Ruang Kesiswaan SMKN Negeri 7 Semarang<br>
                                    <strong>Kontak:</strong> Guru Kesiswaan (08XX-XXXX-XXXX)
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer-section bg-dark text-light pt-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="footer-brand mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <img src="assets/img/logo-white.png" alt="SMK Negeri 7" width="50" height="53" class="me-3">
                            <div>
                                <h5 class="fw-bold text-white mb-0">SMK NEGERI 7</h5>
                                <small class="text-muted">Stemba Report</small>
                            </div>
                        </div>
                        <p class="text-muted mb-3">
                            Sistem pelaporan dan pencarian barang hilang terintegrasi untuk lingkungan SMK Negeri 7.
                        </p>
                        <div class="social-links">
                            <a href="https://www.facebook.com/smknegeri7semarang/?_rdc=1&_rdr#" class="social-link">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="https://www.instagram.com/smknegeri7semarang" class="social-link">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="https://www.tiktok.com/@smknegeri7semarang" class="social-link">
                                <i class="fab fa-tiktok"></i>
                            </a>
                            <a href="https://www.youtube.com/@SMKNegeri7Semarang" class="social-link">
                                <i class="fab fa-youtube"></i>
                            </a>
                            <a href="https://www.linkedin.com/school/smk-negeri-7-semarang/" class="social-link">
                                <i class="fab fa-linkedin"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-lg-2 col-md-6">
                    <h6 class="text-white fw-semibold mb-3">Quick Links</h6>
                    <ul class="footer-links list-unstyled">
                        <li class="mb-2">
                            <a href="index.php" class="text-decoration-none">
                                <i class="fas fa-chevron-right me-2 small"></i>Beranda
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="#laporan" class="text-decoration-none">
                                <i class="fas fa-chevron-right me-2 small"></i>Laporan
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="login.php" class="text-decoration-none">
                                <i class="fas fa-chevron-right me-2 small"></i>Login
                            </a>
                        </li>
                        <li class="mb-2">
                            <a href="register.php" class="text-decoration-none">
                                <i class="fas fa-chevron-right me-2 small"></i>Daftar
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h6 class="text-white fw-semibold mb-3">Kontak Kami</h6>
                    <div class="contact-info">
                        <div class="contact-item d-flex align-items-start mb-3">
                            <i class="fas fa-map-marker-alt text-primary mt-1 me-3"></i>
                            <div>
                                <p class="mb-0 small">
                                    Jl. Simpang Lima No.1, RT.02/RW.01, Mugassari,
                                    Kec. Semarang Sel., Kota Semarang, Jawa Tengah 50249
                                </p>
                            </div>
                        </div>
                        <div class="contact-item d-flex align-items-center mb-3">
                            <i class="fas fa-phone text-primary me-3"></i>
                            <span class="small">(024) 8311532</span>
                        </div>
                        <div class="contact-item d-flex align-items-center mb-3">
                            <i class="fas fa-envelope text-primary me-3"></i>
                            <span class="small">admin@smkn7semarang.sch.id</span>
                        </div>
                        <div class="contact-item d-flex align-items-center">
                            <i class="fas fa-clock text-primary me-3"></i>
                            <span class="small">Senin - Jumat: 07.00 - 15.00</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h6 class="text-white fw-semibold mb-3">Statistik</h6>
                    <div class="stats-grid">
                        <div class="stat-item text-center p-3 bg-dark border border-secondary rounded mb-3">
                            <h4 class="text-primary mb-1">50+</h4>
                            <small class="text-muted">Laporan Aktif</small>
                        </div>
                        <div class="stat-item text-center p-3 bg-dark border border-secondary rounded mb-3">
                            <h4 class="text-success mb-1">85%</h4>
                            <small class="text-muted">Rate Berhasil</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="footer-bottom border-top border-secondary pt-4 mt-4">
                <div class="row">
                    <div class="col-12 text-center">
                        <p class="mb-2 small">
                            &copy; 2025 SMKN Negeri 7 Semarang. All rights reserved. Powered by Iqbalpra.
                        </p>
                    </div>
                </div>
            </div>

        </div>
        </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>