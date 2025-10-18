<?php
include '../includes/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

try {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $role_filter = isset($_GET['role']) ? $_GET['role'] : '';

    $sql = "SELECT u.*, 
                   (SELECT COUNT(*) FROM reports WHERE user_id = u.id) as report_count,
                   (SELECT COUNT(*) FROM reports WHERE user_id = u.id AND status = 'ditemukan') as found_count
            FROM users u 
            WHERE 1=1";

    $params = [];

    if (!empty($search)) {
        $sql .= " AND (u.username LIKE ? OR u.email LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
    }

    if (!empty($role_filter)) {
        $sql .= " AND u.role = ?";
        $params[] = $role_filter;
    }

    $sql .= " ORDER BY u.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $users = [];
    $error = "Terjadi kesalahan saat mengambil data!";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['role'];

    try {
        if ($user_id == $_SESSION['user_id']) {
            $error = "Tidak dapat mengubah role sendiri!";
        } else {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$new_role, $user_id]);

            header("Location: manage_users.php?success=Role user berhasil diupdate!");
            exit();
        }
    } catch (PDOException $e) {
        $error = "Gagal update role user!";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];

    try {
        if ($user_id == $_SESSION['user_id']) {
            $error = "Tidak dapat menghapus akun sendiri!";
        } else {
            $stmt = $pdo->prepare("SELECT image FROM reports WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $reports = $stmt->fetchAll();

            foreach ($reports as $report) {
                if ($report['image'] && file_exists('../uploads/' . $report['image'])) {
                    unlink('../uploads/' . $report['image']);
                }
            }

            $stmt = $pdo->prepare("DELETE FROM reports WHERE user_id = ?");
            $stmt->execute([$user_id]);

            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);

            header("Location: manage_users.php?success=User berhasil dihapus!");
            exit();
        }
    } catch (PDOException $e) {
        $error = "Gagal menghapus user!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - Admin Stemba Report</title>
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

        .user-item {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: white;
            transition: all 0.3s ease;
        }

        .user-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .role-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.75rem;
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

        .stats-badge {
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
            font-size: 0.7rem;
            font-weight: 600;
            background: #f1f5f9;
            color: #475569;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .btn-sm {
            padding: 0.3rem 0.6rem;
            font-size: 0.75rem;
            border-radius: 6px;
        }

        .btn-role {
            background: #8b5cf6;
            border-color: #8b5cf6;
            color: white;
        }

        .btn-role:hover {
            background: #7c3aed;
            border-color: #7c3aed;
        }

        .btn-delete {
            background: #dc2626;
            border-color: #dc2626;
            color: white;
        }

        .btn-delete:hover {
            background: #b91c1c;
            border-color: #b91c1c;
        }

        .success-message {
            background: #d1fae5;
            border: 1px solid #a7f3d0;
            color: #065f46;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .error-message {
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
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
                flex-direction: column;
                margin-top: 1rem;
            }

            .action-buttons .btn {
                width: 100%;
                text-align: center;
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

    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-danger" id="deleteModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Hapus User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2"><strong>Peringatan:</strong> Tindakan ini akan menghapus:</p>
                    <ul class="mb-0">
                        <li>Akun user beserta semua datanya</li>
                        <li>Semua laporan yang dibuat user</li>
                        <li>File gambar yang diupload user</li>
                    </ul>
                    <p class="mt-2 mb-0 text-danger"><strong>Tindakan ini tidak dapat dibatalkan!</strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <form method="POST" action="" id="deleteForm">
                        <input type="hidden" name="user_id" id="deleteUserId">
                        <button type="submit" name="delete_user" class="btn btn-danger">
                            <i class="fas fa-trash me-1"></i>Ya, Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin -->
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
                            <a class="nav-link" href="manage_reports.php">
                                <i class="fas fa-clipboard-list"></i>Kelola Laporan
                            </a>
                            <a class="nav-link active" href="manage_users.php">
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
                            <h2 class="fw-bold mb-0"><i class="fas fa-users me-2"></i>Kelola User</h2>
                            <span class="badge bg-primary"><?php echo count($users); ?> User</span>
                        </div>

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

                        <div class="search-card">
                            <h5 class="fw-semibold mb-3">Filter User</h5>
                            <form method="GET" action="">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Pencarian</label>
                                        <input type="text" name="search" class="form-control"
                                            placeholder="Cari username atau email..."
                                            value="<?php echo htmlspecialchars($search); ?>">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold">Role</label>
                                        <select name="role" class="form-select">
                                            <option value="">Semua Role</option>
                                            <option value="user" <?php echo $role_filter == 'user' ? 'selected' : ''; ?>>User</option>
                                            <option value="admin" <?php echo $role_filter == 'admin' ? 'selected' : ''; ?>>Admin</option>
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

                        <?php if (empty($users)): ?>
                            <div class="empty-state">
                                <i class="fas fa-users"></i>
                                <h5 class="fw-semibold">Tidak ada user ditemukan</h5>
                                <p class="text-muted">Coba ubah filter pencarian Anda</p>
                            </div>
                        <?php else: ?>
                            <div class="users-list">
                                <?php foreach ($users as $user): ?>
                                    <div class="user-item">
                                        <div class="row align-items-center">
                                            <div class="col-md-6 mb-3 mb-md-0">
                                                <div class="d-flex align-items-center mb-2">
                                                    <h6 class="fw-semibold mb-0 me-2"><?php echo htmlspecialchars($user['username']); ?></h6>
                                                    <span class="role-badge <?php echo $user['role'] == 'admin' ? 'role-admin' : 'role-user'; ?>">
                                                        <i class="fas <?php echo $user['role'] == 'admin' ? 'fa-shield-alt' : 'fa-user'; ?> me-1"></i>
                                                        <?php echo ucfirst($user['role']); ?>
                                                    </span>
                                                    <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                        <span class="badge bg-warning ms-2">Anda</span>
                                                    <?php endif; ?>
                                                </div>

                                                <p class="text-muted mb-2 small">
                                                    <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($user['email']); ?>
                                                </p>

                                                <div class="d-flex gap-3">
                                                    <span class="stats-badge">
                                                        <i class="fas fa-clipboard-list me-1"></i>
                                                        <?php echo $user['report_count']; ?> Laporan
                                                    </span>
                                                    <span class="stats-badge">
                                                        <i class="fas fa-check-circle me-1"></i>
                                                        <?php echo $user['found_count']; ?> Ditemukan
                                                    </span>
                                                </div>

                                                <small class="text-muted d-block mt-2">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    Bergabung: <?php echo date('d M Y', strtotime($user['created_at'])); ?>
                                                </small>
                                            </div>

                                            <div class="col-md-6">
                                                <div class="action-buttons justify-content-md-end">
                                                    <form method="POST" class="d-inline-block">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <select name="role" class="form-select form-select-sm d-inline-block w-auto me-2"
                                                            onchange="this.form.submit()"
                                                            <?php echo $user['id'] == $_SESSION['user_id'] ? 'disabled' : ''; ?>>
                                                            <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                                                            <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                        </select>
                                                        <input type="hidden" name="update_role">
                                                    </form>

                                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                        <button type="button" class="btn btn-delete btn-sm"
                                                            data-bs-toggle="modal" data-bs-target="#deleteModal"
                                                            data-user-id="<?php echo $user['id']; ?>"
                                                            data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                                            <i class="fas fa-trash me-1"></i>Hapus
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-secondary btn-sm" disabled>
                                                            <i class="fas fa-ban me-1"></i>Tidak Tersedia
                                                        </button>
                                                    <?php endif; ?>
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
            const deleteUserId = document.getElementById('deleteUserId');

            deleteModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const userId = button.getAttribute('data-user-id');
                const username = button.getAttribute('data-username');
                deleteUserId.value = userId;

                const modalTitle = deleteModal.querySelector('.modal-title');
                modalTitle.innerHTML = '<i class="fas fa-exclamation-triangle me-2"></i>Hapus User: ' + username;
            });
        });
    </script>
</body>

</html>