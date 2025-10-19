<?php
include 'includes/config.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$report_id = intval($_GET['id']);

try {
    $stmt = $pdo->prepare("
        SELECT r.*, u.username, u.email 
        FROM reports r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.id = ?
    ");
    $stmt->execute([$report_id]);
    $report = $stmt->fetch();

    if (!$report) {
        header("Location: index.php");
        exit();
    }

    $images = !empty($report['image']) ? json_decode($report['image'], true) : [];
} catch (PDOException $e) {
    die("Error mengambil data laporan");
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
    <title><?php echo htmlspecialchars($report['title']); ?> - Stemba Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .detail-container {
            padding: 100px 0 50px;
            background: #f8fafc;
            min-height: 100vh;
        }

        .detail-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .image-gallery {
            position: relative;
        }

        .main-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 12px;
        }

        .thumbnail-container {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            overflow-x: auto;
            padding: 10px 0;
        }

        .thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .thumbnail:hover,
        .thumbnail.active {
            border-color: #0d6efd;
        }

        .report-info {
            padding: 2rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
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

        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .info-item i {
            width: 20px;
            margin-right: 10px;
            color: #6c757d;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 2rem;
        }

        @media (max-width: 768px) {
            .detail-container {
                padding: 90px 0 30px;
            }

            .main-image {
                height: 300px;
            }

            .action-buttons {
                flex-direction: column;
            }
        }

        /* Comment System Styles */
        .comment-form-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }

        .comment-item {
            animation: fadeInUp 0.5s ease;
        }

        .comment-item .card {
            border-radius: 8px;
        }

        .delete-comment {
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .comment-item:hover .delete-comment {
            opacity: 1;
        }

        .empty-comments {
            background: #f8f9fa;
            border-radius: 8px;
            border: 2px dashed #dee2e6;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Character count warning */
        .text-warning {
            color: #dc2626 !important;
            font-weight: 600;
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

            <div class="navbar-nav ms-auto">
                <a href="index.php" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Kembali
                </a>
            </div>
        </div>
    </nav>

    <!-- Report Detail -->
    <section class="detail-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="detail-card">
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="image-gallery p-4">
                                    <?php if (!empty($images)): ?>
                                        <img src="uploads/<?php echo htmlspecialchars($images[0]); ?>"
                                            alt="<?php echo htmlspecialchars($report['title']); ?>"
                                            class="main-image" id="mainImage">

                                        <?php if (count($images) > 1): ?>
                                            <div class="thumbnail-container">
                                                <?php foreach ($images as $index => $image): ?>
                                                    <img src="uploads/<?php echo htmlspecialchars($image); ?>"
                                                        alt="Thumbnail <?php echo $index + 1; ?>"
                                                        class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                                                        onclick="changeImage(this, '<?php echo htmlspecialchars($image); ?>')">
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="main-image bg-light d-flex align-items-center justify-content-center">
                                            <i class="fas fa-image fa-3x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-lg-6">
                                <div class="report-info">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h2 class="fw-bold"><?php echo htmlspecialchars($report['title']); ?></h2>
                                        <span class="status-badge <?php echo $report['status'] == 'ditemukan' ? 'status-ditemukan' : 'status-hilang'; ?>">
                                            <i class="fas <?php echo $report['status'] == 'ditemukan' ? 'fa-check' : 'fa-clock'; ?> me-1"></i>
                                            <?php echo ucfirst($report['status']); ?>
                                        </span>
                                    </div>

                                    <div class="mb-4">
                                        <h6 class="fw-semibold mb-2">Deskripsi Barang:</h6>
                                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($report['description'])); ?></p>
                                    </div>

                                    <div class="info-items">
                                        <div class="info-item">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <div>
                                                <strong>Lokasi Kehilangan:</strong>
                                                <div class="text-muted"><?php echo htmlspecialchars($report['location']); ?></div>
                                            </div>
                                        </div>

                                        <div class="info-item">
                                            <i class="fas fa-calendar"></i>
                                            <div>
                                                <strong>Tanggal Kehilangan:</strong>
                                                <div class="text-muted"><?php echo date('d M Y', strtotime($report['date_lost'])); ?></div>
                                            </div>
                                        </div>

                                        <div class="info-item">
                                            <i class="fas fa-user"></i>
                                            <div>
                                                <strong>Dilaporkan oleh:</strong>
                                                <div class="text-muted"><?php echo htmlspecialchars($report['username']); ?></div>
                                            </div>
                                        </div>

                                        <div class="info-item">
                                            <i class="fas fa-clock"></i>
                                            <div>
                                                <strong>Dibuat pada:</strong>
                                                <div class="text-muted"><?php echo date('d M Y H:i', strtotime($report['created_at'])); ?> (<?php echo timeAgo($report['created_at']); ?>)</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="action-buttons">
                                        <a href="index.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-arrow-left me-1"></i>Kembali
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="comments-section mt-5 p-4">
                            <div class="row">
                                <div class="col-12">
                                    <h5 class="fw-semibold mb-4">
                                        <i class="fas fa-comments me-2"></i>Komentar
                                        <span class="badge bg-primary ms-2" id="commentCount">0</span>
                                    </h5>

                                    <!-- Comment Form -->
                                    <?php if (isset($_SESSION['user_id'])): ?>
                                        <div class="comment-form-card mb-4">
                                            <form id="commentForm" class="comment-form">
                                                <input type="hidden" name="report_id" value="<?php echo $report_id; ?>">
                                                <div class="mb-3">
                                                    <label for="comment" class="form-label fw-semibold">Tambah Komentar</label>
                                                    <textarea class="form-control" id="comment" name="comment"
                                                        rows="3" placeholder="Tulis komentar Anda..."
                                                        maxlength="500" required></textarea>
                                                    <div class="form-text">
                                                        <span id="charCount">0</span>/500 karakter
                                                    </div>
                                                </div>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">
                                                        Komentar akan tampil dengan username Anda
                                                    </small>
                                                    <button type="submit" class="btn btn-primary" id="submitCommentBtn">
                                                        <i class="fas fa-paper-plane me-1"></i>Kirim Komentar
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <a href="login.php" class="alert-link">Login</a> untuk menambahkan komentar
                                        </div>
                                    <?php endif; ?>

                                    <!-- Comments List -->
                                    <div class="comments-list" id="commentsList">
                                        <div class="text-center py-4">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <p class="text-muted mt-2">Memuat komentar...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function changeImage(thumbElement, imageSrc) {
            const mainImage = document.getElementById('mainImage');
            mainImage.src = 'uploads/' + imageSrc;

            document.querySelectorAll('.thumbnail').forEach(thumb => {
                thumb.classList.remove('active');
            });
            thumbElement.classList.add('active');
        }

        class CommentSystem {
            constructor() {
                this.reportId = <?php echo $report_id; ?>;
                this.commentsList = document.getElementById('commentsList');
                this.commentForm = document.getElementById('commentForm');
                this.commentCount = document.getElementById('commentCount');
                this.charCount = document.getElementById('charCount');
                this.commentTextarea = document.getElementById('comment');
                this.submitBtn = document.getElementById('submitCommentBtn');

                this.init();
            }

            init() {
                this.loadComments();
                this.setupEventListeners();
            }

            setupEventListeners() {
                if (this.commentForm) {
                    this.commentForm.addEventListener('submit', this.handleCommentSubmit.bind(this));
                }

                if (this.commentTextarea) {
                    this.commentTextarea.addEventListener('input', this.updateCharCount.bind(this));
                }
            }

            updateCharCount() {
                const length = this.commentTextarea.value.length;
                this.charCount.textContent = length;

                if (length > 450) {
                    this.charCount.classList.add('text-warning');
                } else {
                    this.charCount.classList.remove('text-warning');
                }
            }

            async handleCommentSubmit(e) {
                e.preventDefault();

                const formData = new FormData(this.commentForm);
                const comment = formData.get('comment').trim();

                if (!comment) {
                    this.showAlert('Komentar tidak boleh kosong!', 'error');
                    return;
                }

                this.setSubmitButtonState(true);

                try {
                    const response = await fetch('includes/add_comment.php', {
                        method: 'POST',
                        body: formData
                    });

                    const responseText = await response.text();

                    let result;
                    try {
                        result = JSON.parse(responseText);
                    } catch (parseError) {
                        console.error('JSON Parse Error:', parseError);
                        console.error('Response:', responseText);
                        throw new Error('Invalid server response');
                    }

                    if (result.success) {
                        this.commentForm.reset();
                        this.updateCharCount();
                        this.showAlert('Komentar berhasil ditambahkan!', 'success');

                        await this.loadComments();
                    } else {
                        this.showAlert(result.error || 'Gagal menambahkan komentar!', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    this.showAlert('Terjadi kesalahan! Silakan coba lagi.', 'error');
                } finally {
                    this.setSubmitButtonState(false);
                }
            }

            setSubmitButtonState(loading) {
                if (this.submitBtn) {
                    if (loading) {
                        this.submitBtn.disabled = true;
                        this.submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Mengirim...';
                    } else {
                        this.submitBtn.disabled = false;
                        this.submitBtn.innerHTML = '<i class="fas fa-paper-plane me-1"></i>Kirim Komentar';
                    }
                }
            }

            async loadComments() {
                try {
                    const response = await fetch(`includes/get_comments.php?report_id=${this.reportId}`);

                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    const comments = await response.json();
                    this.renderComments(comments);
                    this.updateCommentCount(comments.length);
                } catch (error) {
                    console.error('Error loading comments:', error);
                    this.commentsList.innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Gagal memuat komentar. Silakan refresh halaman.
                </div>
            `;
                }
            }

            renderComments(comments) {
                if (!comments || comments.length === 0) {
                    this.commentsList.innerHTML = `
                <div class="empty-comments text-center py-4">
                    <i class="fas fa-comments fa-2x text-muted mb-3"></i>
                    <h6 class="text-muted">Belum ada komentar</h6>
                    <p class="text-muted small">Jadilah yang pertama berkomentar!</p>
                </div>
            `;
                    return;
                }

                this.commentsList.innerHTML = comments.map(comment => `
            <div class="comment-item mb-3" data-comment-id="${comment.id}">
                <div class="card border-0 bg-light">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div class="d-flex align-items-center">
                                <strong class="me-2">${this.escapeHtml(comment.username)}</strong>
                                <small class="text-muted">${this.timeAgo(comment.created_at)}</small>
                            </div>
                            ${this.canDeleteComment(comment) ? `
                            <button class="btn btn-sm btn-outline-danger delete-comment" 
                                    data-comment-id="${comment.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                            ` : ''}
                        </div>
                        <p class="mb-0">${this.escapeHtml(comment.comment)}</p>
                    </div>
                </div>
            </div>
        `).join('');

                this.setupDeleteHandlers();
            }

            canDeleteComment(comment) {
                const currentUserId = <?php echo isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'null'; ?>;
                const isAdmin = <?php echo (isset($_SESSION['role']) && $_SESSION['role'] == 'admin') ? 'true' : 'false'; ?>;
                return isAdmin || (currentUserId && currentUserId == comment.user_id);
            }

            setupDeleteHandlers() {
                document.querySelectorAll('.delete-comment').forEach(btn => {
                    btn.addEventListener('click', this.handleDeleteComment.bind(this));
                });
            }

            async handleDeleteComment(e) {
                const commentId = e.target.closest('.delete-comment').dataset.commentId;
                const commentItem = e.target.closest('.comment-item');
                const commentText = commentItem.querySelector('p').textContent;
                const username = commentItem.querySelector('strong').textContent;
                const shouldDelete = await this.showDeleteConfirmation(username, commentText);
                if (!shouldDelete) {
                    return;
                }

                const deleteBtn = e.target.closest('.delete-comment');
                const originalHtml = deleteBtn.innerHTML;
                deleteBtn.disabled = true;
                deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                try {
                    const response = await fetch('includes/delete_comment.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `comment_id=${commentId}`
                    });

                    const result = await response.json();

                    if (result.success) {
                        commentItem.style.transition = 'all 0.3s ease';
                        commentItem.style.opacity = '0';
                        commentItem.style.transform = 'translateX(-20px)';

                        setTimeout(() => {
                            commentItem.remove();
                            this.updateCommentCount(parseInt(this.commentCount.textContent) - 1);
                        }, 300);
                    } else {
                        this.showAlert(result.error || 'Gagal menghapus komentar!', 'error');
                        deleteBtn.disabled = false;
                        deleteBtn.innerHTML = originalHtml;
                    }
                } catch (error) {
                    console.error('Error:', error);
                    this.showAlert('Terjadi kesalahan!', 'error');
                    deleteBtn.disabled = false;
                    deleteBtn.innerHTML = originalHtml;
                }
            }

            showDeleteConfirmation(username, commentText) {
                return new Promise((resolve) => {
                    const modal = document.createElement('div');
                    modal.className = 'modal fade';
                    modal.innerHTML = `
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header border-0">
                            <div class="modal-icon text-danger">
                                <i class="fas fa-exclamation-triangle fa-2x"></i>
                            </div>
                            <h5 class="modal-title ms-3">Hapus Komentar?</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p class="mb-2">Anda akan menghapus komentar dari <strong>${username}</strong>:</p>
                            <div class="alert alert-light border">
                                <p class="mb-0 text-muted">"${this.truncateText(commentText, 100)}"</p>
                            </div>
                            <p class="text-muted small mt-2">Tindakan ini tidak dapat dibatalkan.</p>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <button type="button" class="btn btn-danger" id="confirmDelete">Ya, Hapus</button>
                        </div>
                    </div>
                </div>
            `;

                    document.body.appendChild(modal);

                    const bsModal = new bootstrap.Modal(modal);
                    bsModal.show();

                    modal.querySelector('#confirmDelete').addEventListener('click', () => {
                        bsModal.hide();
                        resolve(true);
                    });

                    modal.addEventListener('hidden.bs.modal', () => {
                        document.body.removeChild(modal);
                        resolve(false);
                    });
                });
            }

            updateCommentCount(count) {
                this.commentCount.textContent = count;
            }

            timeAgo(timestamp) {
                const currentTime = new Date();
                const commentTime = new Date(timestamp);
                const timeDifference = currentTime - commentTime;

                const minutes = Math.floor(timeDifference / 60000);
                const hours = Math.floor(timeDifference / 3600000);
                const days = Math.floor(timeDifference / 86400000);

                if (minutes < 1) return 'Baru saja';
                if (minutes < 60) return `${minutes} menit lalu`;
                if (hours < 24) return `${hours} jam lalu`;
                return `${days} hari lalu`;
            }

            escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            truncateText(text, maxLength) {
                if (text.length <= maxLength) return text;
                return text.substring(0, maxLength) + '...';
            }

            showAlert(message, type) {
                const existingAlert = document.querySelector('.comment-alert');
                if (existingAlert) {
                    existingAlert.remove();
                }

                const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
                const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle';

                const alert = document.createElement('div');
                alert.className = `alert ${alertClass} alert-dismissible fade show comment-alert mb-4`;
                alert.innerHTML = `
            <i class="fas ${icon} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

                const commentsList = document.getElementById('commentsList');
                commentsList.parentNode.insertBefore(alert, commentsList);

                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.parentNode.removeChild(alert);
                    }
                }, 3000);
            }
        }

        const style = document.createElement('style');
        style.textContent = `
    .modal-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: rgba(220, 53, 69, 0.1);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .comment-item {
        transition: all 0.3s ease;
    }
    
    .delete-comment {
        transition: all 0.3s ease;
    }
`;

        document.head.appendChild(style);

        document.addEventListener('DOMContentLoaded', function() {
            new CommentSystem();
        });
    </script>
</body>

</html>