<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users WHERE role = 'user'");
    $total_users = $stmt->fetch()['total_users'];

    $stmt = $pdo->query("SELECT COUNT(*) as total_reports FROM reports");
    $total_reports = $stmt->fetch()['total_reports'];

    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM reports GROUP BY status");
    $status_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $lost_reports = $status_counts['hilang'] ?? 0;
    $found_reports = $status_counts['ditemukan'] ?? 0;

    $stmt = $pdo->query("
        SELECT r.*, u.username 
        FROM reports r 
        JOIN users u ON r.user_id = u.id 
        ORDER BY r.created_at DESC 
        LIMIT 5
    ");
    $recent_reports = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
    $recent_users = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Terjadi kesalahan saat mengambil data!";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Stemba Report</title>
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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
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
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }

        .stat-icon.primary {
            background: rgba(37, 99, 235, 0.1);
            color: #2563eb;
        }

        .stat-icon.success {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }

        .stat-icon.warning {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }

        .stat-icon.danger {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            line-height: 1;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 500;
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

        .recent-item {
            border-bottom: 1px solid #e2e8f0;
            padding: 1rem 0;
        }

        .recent-item:last-child {
            border-bottom: none;
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

        .role-badge {
            padding: 0.3rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .role-user {
            background: #e0f2fe;
            color: #0369a1;
        }

        .role-admin {
            background: #fce7f3;
            color: #be185d;
        }

        @media (max-width: 768px) {
            .admin-container {
                padding: 70px 0 20px;
            }

            .admin-sidebar {
                position: static;
                margin-bottom: 1.5rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar -->
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

    <!-- Admin -->
    <section class="admin-container">
        <div class="container">
            <div class="row">

                <div class="col-lg-3 mb-4">
                    <div class="admin-sidebar">
                        <h5 class="fw-bold mb-3">Admin Menu</h5>
                        <nav class="nav-admin">
                            <a class="nav-link active" href="index.php">
                                <i class="fas fa-tachometer-alt"></i>Dashboard
                            </a>
                            <a class="nav-link" href="manage_reports.php">
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
                        <h2 class="fw-bold mb-4"><i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard</h2>


                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-icon primary">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-number text-primary"><?php echo $total_users; ?></div>
                                <div class="stat-label">Total Users</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-icon success">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                                <div class="stat-number text-success"><?php echo $total_reports; ?></div>
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
                                <div class="stat-icon danger">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="stat-number text-danger"><?php echo $found_reports; ?></div>
                                <div class="stat-label">Sudah Ditemukan</div>
                            </div>
                        </div>

                        <div class="row">

                            <div class="col-md-6 mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="fw-semibold mb-0">Laporan Terbaru</h5>
                                    <a href="manage_reports.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                                </div>
                                <div class="recent-list">
                                    <?php if (empty($recent_reports)): ?>
                                        <p class="text-muted text-center py-3">Belum ada laporan</p>
                                    <?php else: ?>
                                        <?php foreach ($recent_reports as $report): ?>
                                            <div class="recent-item">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="fw-semibold mb-1"><?php echo htmlspecialchars($report['title']); ?></h6>
                                                        <p class="text-muted small mb-1">by <?php echo htmlspecialchars($report['username']); ?></p>
                                                        <span class="status-badge <?php echo $report['status'] == 'ditemukan' ? 'status-ditemukan' : 'status-hilang'; ?>">
                                                            <?php echo ucfirst($report['status']); ?>
                                                        </span>
                                                    </div>
                                                    <small class="text-muted"><?php echo date('d M', strtotime($report['created_at'])); ?></small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="fw-semibold mb-0">User Terbaru</h5>
                                    <a href="manage_users.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                                </div>
                                <div class="recent-list">
                                    <?php if (empty($recent_users)): ?>
                                        <p class="text-muted text-center py-3">Belum ada user</p>
                                    <?php else: ?>
                                        <?php foreach ($recent_users as $user): ?>
                                            <div class="recent-item">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="fw-semibold mb-1"><?php echo htmlspecialchars($user['username']); ?></h6>
                                                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($user['email']); ?></p>
                                                    </div>
                                                    <span class="role-badge <?php echo $user['role'] == 'admin' ? 'role-admin' : 'role-user'; ?>">
                                                        <?php echo ucfirst($user['role']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>