<?php
include 'includes/config.php';


if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $date_lost = $_POST['date_lost'];


    if (empty($title) || empty($description) || empty($location) || empty($date_lost)) {
        $error = "Harap isi semua field yang wajib!";
    } else {
        try {

            $image_names = [];

            if (isset($_FILES['image']) && !empty($_FILES['image']['name'][0])) {
                $images = $_FILES['image'];
                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                $max_size = 2 * 1024 * 1024;


                for ($i = 0; $i < count($images['name']); $i++) {
                    if ($images['error'][$i] === UPLOAD_ERR_OK) {

                        if (!in_array($images['type'][$i], $allowed_types)) {
                            $error = "Hanya file gambar JPG, PNG, atau GIF yang diizinkan!";
                            break;
                        } elseif ($images['size'][$i] > $max_size) {
                            $error = "Ukuran file maksimal 2MB!";
                            break;
                        } else {

                            $file_extension = pathinfo($images['name'][$i], PATHINFO_EXTENSION);
                            $image_name = uniqid() . '_' . $i . '.' . $file_extension;
                            $upload_path = 'uploads/' . $image_name;


                            if (move_uploaded_file($images['tmp_name'][$i], $upload_path)) {
                                $image_names[] = $image_name;
                            } else {
                                $error = "Gagal mengupload gambar!";
                                break;
                            }
                        }
                    }
                }
            }


            if (empty($error)) {

                $image_json = !empty($image_names) ? json_encode($image_names) : null;

                $stmt = $pdo->prepare("
                    INSERT INTO reports (user_id, title, description, location, date_lost, image, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'hilang')
                ");
                $stmt->execute([$user_id, $title, $description, $location, $date_lost, $image_json]);

                $success = "Laporan berhasil dibuat!";


                $title = $description = $location = $date_lost = '';

                echo '<script>localStorage.removeItem("report_draft");</script>';
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buat Laporan - Stemba Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .form-container {
            padding: 100px 0 50px;
            background: #f8fafc;
            min-height: 100vh;
        }

        .form-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            overflow: hidden;
            max-width: 700px;
            margin: 0 auto;
        }

        .form-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 2rem;
            text-align: center;
        }

        .form-body {
            padding: 2rem;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .form-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .required::after {
            content: " *";
            color: #dc2626;
        }

        .btn-submit {
            border-radius: 8px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .image-preview {
            width: 150px;
            height: 150px;
            border: 2px dashed #e2e8f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #f8fafc;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .image-preview:hover {
            border-color: var(--primary);
            background: #f0f4f8;
        }

        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-preview i {
            font-size: 2rem;
            color: #94a3b8;
        }

        .file-input {
            display: none;
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

        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .char-count {
            font-size: 0.8rem;
            color: #64748b;
            text-align: right;
            margin-top: 0.25rem;
        }

        .char-count.warning {
            color: #dc2626;
        }

        /* Mobile Responsive */
        @media (max-width: 768px) {
            .form-container {
                padding: 90px 0 30px;
            }

            .form-header {
                padding: 1.5rem;
            }

            .form-body {
                padding: 1.5rem;
            }

            .image-preview {
                width: 120px;
                height: 120px;
            }

            .form-actions {
                flex-direction: column;
            }

            .form-actions .btn {
                width: 100%;
            }
        }

        .drop-zone {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .drop-zone:hover,
        .drop-zone--over {
            border-color: #0d6efd;
            background: #e7f1ff;
        }

        .drop-zone-content {
            pointer-events: none;
        }

        .image-preview-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 0.5rem;
            margin-bottom: 1rem;
        }

        .image-preview-card img {
            width: 100%;
            height: 120px;
            object-fit: cover;
            border-radius: 4px;
        }

        .btn-remove {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
        }

        .is-invalid {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
        }

        .invalid-feedback {
            display: block;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875em;
            color: #dc3545;
        }

        .alert {
            border-radius: 8px;
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarForm">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarForm">
                <div class="navbar-nav ms-auto">
                    <div class="d-flex flex-column flex-lg-row gap-2 align-items-center">
                        <a href="dashboard.php" class="btn btn-outline-primary btn-sm w-100 w-lg-auto">
                            <i class="fas fa-arrow-left me-1"></i>Kembali ke Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Form Laporan -->
    <section class="form-container">
        <div class="container">
            <div class="form-card">
                <div class="form-header">
                    <h3 class="mb-2"><i class="fas fa-plus-circle me-2"></i>Buat Laporan Baru</h3>
                    <p class="mb-0 opacity-90">Laporkan barang hilang Anda</p>
                </div>

                <div class="form-body">
                    <?php if (!empty($success)): ?>
                        <div class="success-message">
                            <i class="fas fa-check-circle"></i>
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" enctype="multipart/form-data">

                        <div class="mb-4">
                            <label class="form-label d-block">Foto Barang</label>

                            <div class="drop-zone mb-3" id="dropZone">
                                <div class="drop-zone-content">
                                    <i class="fas fa-cloud-upload-alt fa-2x mb-3 text-primary"></i>
                                    <h5 class="mb-2">Drop files here or click to upload</h5>
                                    <p class="text-muted mb-0">Supports JPG, PNG, GIF - Max 2MB per file</p>
                                    <small class="text-muted">You can upload multiple images</small>
                                </div>
                                <input type="file" id="image" name="image[]" class="file-input"
                                    accept="image/*" multiple style="display: none;">
                            </div>

                            <div class="image-previews row g-2" id="imagePreviews"></div>

                            <div class="upload-status mt-2" id="uploadStatus"></div>
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label required">Judul Laporan</label>
                            <input type="text" class="form-control" id="title" name="title"
                                placeholder="Contoh: Tas Sekolah Hitam Hilang"
                                value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>"
                                required maxlength="100">
                            <div class="char-count" id="title-count">0/100 karakter</div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label required">Deskripsi Barang</label>
                            <textarea class="form-control" id="description" name="description"
                                rows="4" placeholder="Jelaskan ciri-ciri barang, merk, warna, kondisi, dan isi barang..."
                                required maxlength="500"><?php echo isset($description) ? htmlspecialchars($description) : ''; ?></textarea>
                            <div class="char-count" id="description-count">0/500 karakter</div>
                        </div>

                        <div class="mb-3">
                            <label for="location" class="form-label required">Lokasi Kehilangan</label>
                            <input type="text" class="form-control" id="location" name="location"
                                placeholder="Contoh: Lab Komputer, Perpustakaan, Lapangan Basket"
                                value="<?php echo isset($location) ? htmlspecialchars($location) : ''; ?>"
                                required maxlength="100">
                            <div class="char-count" id="location-count">0/100 karakter</div>
                        </div>

                        <div class="mb-4">
                            <label for="date_lost" class="form-label required">Tanggal Kehilangan</label>
                            <input type="date" class="form-control" id="date_lost" name="date_lost"
                                value="<?php echo isset($date_lost) ? $date_lost : ''; ?>"
                                required max="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="form-actions">
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times me-2"></i>Batal
                            </a>
                            <button type="submit" class="btn btn-primary btn-submit">
                                <i class="fas fa-paper-plane me-2"></i>Buat Laporan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.querySelector('.image-preview');

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
                }
                reader.readAsDataURL(file);
            } else {
                preview.innerHTML = '<i class="fas fa-camera"></i>';
            }
        });

        function setupCharCounter(inputId, counterId, maxLength) {
            const input = document.getElementById(inputId);
            const counter = document.getElementById(counterId);

            input.addEventListener('input', function() {
                const length = this.value.length;
                counter.textContent = `${length}/${maxLength} karakter`;

                if (length > maxLength * 0.8) {
                    counter.classList.add('warning');
                } else {
                    counter.classList.remove('warning');
                }
            });

            counter.textContent = `${input.value.length}/${maxLength} karakter`;
        }


        setupCharCounter('title', 'title-count', 100);
        setupCharCounter('description', 'description-count', 500);
        setupCharCounter('location', 'location-count', 100);

        document.getElementById('date_lost').max = new Date().toISOString().split('T')[0];


        class ImageGallery {
            constructor() {
                this.dropZone = document.getElementById('dropZone');
                this.fileInput = document.getElementById('image');
                this.previewsContainer = document.getElementById('imagePreviews');
                this.uploadStatus = document.getElementById('uploadStatus');
                this.selectedFiles = [];

                this.init();
            }

            init() {
                this.setupDragAndDrop();
                this.setupFileInput();
                this.setupPreviewHandlers();
            }

            setupDragAndDrop() {
                ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                    this.dropZone.addEventListener(eventName, this.preventDefaults, false);
                    document.body.addEventListener(eventName, this.preventDefaults, false);
                });


                ['dragenter', 'dragover'].forEach(eventName => {
                    this.dropZone.addEventListener(eventName, this.highlight, false);
                });

                ['dragleave', 'drop'].forEach(eventName => {
                    this.dropZone.addEventListener(eventName, this.unhighlight, false);
                });


                this.dropZone.addEventListener('drop', this.handleDrop.bind(this), false);


                this.dropZone.addEventListener('click', () => {
                    this.fileInput.click();
                });
            }

            preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            highlight() {
                this.classList.add('drop-zone--over');
            }

            unhighlight() {
                this.classList.remove('drop-zone--over');
            }

            handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                this.handleFiles(files);
            }

            setupFileInput() {
                this.fileInput.addEventListener('change', (e) => {
                    this.handleFiles(e.target.files);
                });
            }


            handleFiles(files) {
                const validFiles = [...files].filter(file => {

                    const isValidType = file.type.startsWith('image/');
                    const isValidSize = file.size <= 2 * 1024 * 1024; // 

                    if (!isValidType) {
                        this.showStatus('Hanya file gambar yang diizinkan!', 'error');
                        return false;
                    }

                    if (!isValidSize) {
                        this.showStatus(`File ${file.name} terlalu besar (maks 2MB)!`, 'error');
                        return false;
                    }

                    return true;
                });

                this.selectedFiles = [...this.selectedFiles, ...validFiles];


                this.renderPreviews();
                this.updateFileInput();
            }


            renderPreviews() {
                this.previewsContainer.innerHTML = this.selectedFiles.map((file, index) => {
                    const url = URL.createObjectURL(file);
                    return `
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="image-preview-card position-relative">
                            <img src="${url}" class="img-fluid rounded" alt="Preview">
                            <button type="button" class="btn-remove btn btn-sm btn-danger position-absolute top-0 end-0 m-1" 
                                    data-index="${index}">
                                <i class="fas fa-times"></i>
                            </button>
                            <div class="image-info small text-muted mt-1">
                                ${this.formatFileSize(file.size)}
                            </div>
                        </div>
                    </div>
                `;
                }).join('');


                this.setupRemoveHandlers();
            }

            setupRemoveHandlers() {
                document.querySelectorAll('.btn-remove').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        const index = parseInt(e.target.closest('.btn-remove').dataset.index);
                        this.removeFile(index);
                    });
                });
            }

            removeFile(index) {

                this.selectedFiles = this.selectedFiles.filter((_, i) => i !== index);
                this.renderPreviews();
                this.updateFileInput();
            }

            updateFileInput() {

                const dt = new DataTransfer();
                this.selectedFiles.forEach(file => dt.items.add(file));
                this.fileInput.files = dt.files;


                this.showStatus(`Selected ${this.selectedFiles.length} file(s)`, 'success');
            }

            formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }

            showStatus(message, type) {
                const alertClass = type === 'error' ? 'alert-danger' : 'alert-success';
                this.uploadStatus.innerHTML = `
                <div class="alert ${alertClass} alert-dismissible fade show small" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;


                if (type === 'success') {
                    setTimeout(() => {
                        this.uploadStatus.innerHTML = '';
                    }, 3000);
                }
            }
        }


        document.addEventListener('DOMContentLoaded', function() {
            new ImageGallery();
        });


        class FormManager {
            constructor() {
                this.form = document.querySelector('form');
                this.fields = ['title', 'description', 'location', 'date_lost'];
                this.isAutoSaving = false;
                this.autoSaveInterval = null;

                this.init();
            }

            init() {
                this.setupAutoSave();
                this.setupAdvancedValidation();
                this.loadDraft();
                this.setupFormSubmission();
            }


            setupAutoSave() {
                this.fields.forEach(fieldName => {
                    const field = document.getElementById(fieldName);
                    if (field) {
                        field.addEventListener('input', this.debounce(() => {
                            this.saveDraft();
                        }, 1000));
                    }
                });
            }


            setupAdvancedValidation() {
                this.fields.forEach(fieldName => {
                    const field = document.getElementById(fieldName);
                    if (field) {
                        field.addEventListener('blur', () => this.validateField(field));
                        field.addEventListener('input', () => this.clearFieldError(field));
                    }
                });
            }

            validateField(field) {
                const value = field.value.trim();
                const fieldName = field.name;

                this.clearFieldError(field);


                if (field.required && !value) {
                    this.showFieldError(field, 'Field ini wajib diisi');
                    return false;
                }

                switch (fieldName) {
                    case 'title':
                        if (value.length < 5) {
                            this.showFieldError(field, 'Judul minimal 5 karakter');
                            return false;
                        }
                        break;

                    case 'description':
                        if (value.length < 10) {
                            this.showFieldError(field, 'Deskripsi minimal 10 karakter');
                            return false;
                        }
                        break;

                    case 'location':
                        if (value.length < 3) {
                            this.showFieldError(field, 'Lokasi minimal 3 karakter');
                            return false;
                        }
                        break;

                    case 'date_lost':
                        const selectedDate = new Date(value);
                        const today = new Date();
                        if (selectedDate > today) {
                            this.showFieldError(field, 'Tanggal tidak boleh melebihi hari ini');
                            return false;
                        }
                        break;
                }

                return true;
            }

            showFieldError(field, message) {
                field.classList.add('is-invalid');

                let feedback = field.nextElementSibling;
                if (!feedback || !feedback.classList.contains('invalid-feedback')) {
                    feedback = document.createElement('div');
                    feedback.className = 'invalid-feedback';
                    field.parentNode.insertBefore(feedback, field.nextSibling);
                }

                feedback.textContent = message;
            }

            clearFieldError(field) {
                field.classList.remove('is-invalid');
                const feedback = field.nextElementSibling;
                if (feedback && feedback.classList.contains('invalid-feedback')) {
                    feedback.textContent = '';
                }
            }


            saveDraft() {
                if (this.isAutoSaving) return;

                this.isAutoSaving = true;

                const draft = {};
                this.fields.forEach(fieldName => {
                    const field = document.getElementById(fieldName);
                    if (field) {
                        draft[fieldName] = field.value;
                    }
                });


                const imageInput = document.getElementById('image');
                if (imageInput && imageInput.files.length > 0) {
                    draft.imageCount = imageInput.files.length;
                }

                localStorage.setItem('report_draft', JSON.stringify(draft));


                this.showSaveIndicator();

                setTimeout(() => {
                    this.isAutoSaving = false;
                }, 500);
            }


            loadDraft() {
                const draft = localStorage.getItem('report_draft');
                if (draft) {
                    const draftData = JSON.parse(draft);

                    this.fields.forEach(fieldName => {
                        const field = document.getElementById(fieldName);
                        if (field && draftData[fieldName]) {
                            field.value = draftData[fieldName];
                        }
                    });


                    if (this.isFormNotEmpty(draftData)) {
                        this.showDraftLoaded();
                    }
                }
            }

            isFormNotEmpty(draftData) {
                return this.fields.some(fieldName => draftData[fieldName] && draftData[fieldName].trim() !== '');
            }

            showDraftLoaded() {
                const alert = document.createElement('div');
                alert.className = 'alert alert-info alert-dismissible fade show';
                alert.innerHTML = `
            <i class="fas fa-info-circle me-2"></i>
            Draft tersimpan telah dimuat. <a href="#" id="clearDraft" class="alert-link">Hapus draft</a>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

                this.form.insertBefore(alert, this.form.firstChild);

                document.getElementById('clearDraft').addEventListener('click', (e) => {
                    e.preventDefault();
                    this.clearDraft();
                    alert.remove();
                });
            }

            clearDraft() {
                localStorage.removeItem('report_draft');
                this.fields.forEach(fieldName => {
                    const field = document.getElementById(fieldName);
                    if (field) field.value = '';
                });
            }

            showSaveIndicator() {

                let indicator = document.getElementById('saveIndicator');
                if (!indicator) {
                    indicator = document.createElement('div');
                    indicator.id = 'saveIndicator';
                    indicator.className = 'position-fixed bottom-0 end-0 m-3';
                    document.body.appendChild(indicator);
                }

                indicator.innerHTML = `
            <div class="alert alert-success alert-dismissible fade show small" role="alert">
                <i class="fas fa-save me-1"></i> Draft tersimpan
                <button type="button" class="btn-close btn-close-sm" data-bs-dismiss="alert"></button>
            </div>
        `;


                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(indicator.querySelector('.alert'));
                    bsAlert.close();
                }, 2000);
            }

            setupFormSubmission() {
                this.form.addEventListener('submit', (e) => {

                    localStorage.removeItem('report_draft');
                });
            }


            debounce(func, wait) {
                let timeout;
                return function executedFunction(...args) {
                    const later = () => {
                        clearTimeout(timeout);
                        func(...args);
                    };
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                };
            }
        }


        document.addEventListener('DOMContentLoaded', function() {
            new FormManager();
        });
    </script>
</body>

</html>