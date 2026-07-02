<?php
// public/apply.php
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/auth.php';
requireLogin();

$job_id = (int)($_GET['job_id'] ?? $_POST['job_id'] ?? 0);
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cover = trim($_POST['cover_letter'] ?? '');
    if (!isset($_FILES['resume']) || empty($_FILES['resume']['name'])) $errors[] = 'Resume required';
    else {
        $allowed = ['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['resume']['tmp_name']);
        if (!in_array($mime,$allowed)) $errors[] = 'Resume must be PDF/DOC/DOCX';
        if ($_FILES['resume']['size'] > MAX_UPLOAD_SIZE) $errors[] = 'Resume too large';
    }

    if (empty($errors)) {
        $ext = pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION);
        $target = UPLOAD_DIR . 'resumes/';
        if (!is_dir($target)) mkdir($target, 0755, true);
        $fname = uniqid('resume_') . '.' . $ext;
        if (!move_uploaded_file($_FILES['resume']['tmp_name'], $target . $fname)) $errors[] = 'Upload failed';
        else {
            $resume_path = 'assets/uploads/resumes/' . $fname;
            $uid = currentUserId();
            $stmt = $conn->prepare("INSERT INTO applications (job_id,user_id,cover_letter,resume_path) VALUES (?,?,?,?)");
            $stmt->bind_param('iiss',$job_id,$uid,$cover,$resume_path);
            if ($stmt->execute()) {
                header('Location: /job-portal/public/dashboard/user_dashboard.php?applied=1');
                exit;
            } else $errors[] = 'DB error: '.$conn->error;
        }
    }
}

// load job title (user context)
$job_title = '';
$job_location = '';
$company_name = '';
if ($job_id > 0) {
    $t = $conn->prepare('SELECT j.title, j.location, c.name as company_name FROM jobs j JOIN companies c ON j.company_id = c.id WHERE j.id = ? LIMIT 1');
    if ($t) {
        $t->bind_param('i', $job_id);
        $t->execute();
        $job = $t->get_result()->fetch_assoc();
        if ($job) {
            $job_title = $job['title'];
            $job_location = $job['location'] ?? '';
            $company_name = $job['company_name'] ?? '';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply for <?= htmlspecialchars($job_title) ?> - Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #1A535C;
            --primary-light: #4ECDC4;
            --background: #F7FFF7;
            --accent: #FF6B6B;
            --highlight: #FFE66D;
            --gradient: linear-gradient(135deg, var(--primary-dark) 0%, #2a7a85 100%);
            --light-gradient: linear-gradient(135deg, var(--primary-light) 0%, #6bd4cc 100%);
            --accent-gradient: linear-gradient(135deg, var(--accent) 0%, #ff5252 100%);
            --highlight-gradient: linear-gradient(135deg, var(--highlight) 0%, #ffed80 100%);
        }

        body {
            background-color: var(--background);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .apply-page {
            min-height: 100vh;
            padding: 30px 0;
        }

        .page-header-apply {
            background: var(--gradient);
            color: white;
            border-radius: 20px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 15px 35px rgba(26, 83, 92, 0.3);
            position: relative;
            overflow: hidden;
        }

        .page-header-apply::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: rgba(78, 205, 196, 0.2);
            border-radius: 50%;
        }

        .apply-form-container {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            border: 2px solid rgba(26, 83, 92, 0.1);
        }

        .job-preview-card {
            background: var(--gradient);
            color: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px rgba(26, 83, 92, 0.2);
            border: 2px solid var(--primary-light);
        }

        .job-preview-title {
            font-weight: 700;
            font-size: 1.4rem;
            margin-bottom: 0.5rem;
        }

        .job-preview-company {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 0.25rem;
        }

        .job-preview-location {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .form-section {
            margin-bottom: 2rem;
        }

        .section-title {
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            position: relative;
            padding-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: var(--gradient);
            border-radius: 2px;
        }

        .section-title i {
            font-size: 1.2rem;
            color: var(--primary-light);
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-label {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-label i {
            color: var(--primary-light);
            width: 20px;
        }

        .form-control {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .form-control:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(78, 205, 196, 0.1);
            background: white;
        }

        .form-control::placeholder {
            color: #9ca3af;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 150px;
        }

        .file-upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            background: #fafafa;
            cursor: pointer;
            position: relative;
        }

        .file-upload-area:hover {
            border-color: var(--primary-light);
            background: #f0f4ff;
        }

        .file-upload-area.dragover {
            border-color: var(--primary-light);
            background: #e0e7ff;
        }

        .file-input {
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            opacity: 0;
            cursor: pointer;
        }

        .file-upload-icon {
            font-size: 3rem;
            color: var(--primary-light);
            margin-bottom: 1rem;
        }

        .file-upload-text {
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .file-upload-hint {
            font-size: 0.85rem;
            color: #6b7280;
        }

        .file-selected {
            background: rgba(78, 205, 196, 0.1);
            border-color: var(--primary-light);
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: rgba(78, 205, 196, 0.1);
            border-radius: 10px;
            margin-top: 1rem;
            border: 1px solid var(--primary-light);
        }

        .file-info i {
            color: var(--primary-light);
            font-size: 1.5rem;
        }

        .file-details {
            flex: 1;
        }

        .file-name {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 0.25rem;
        }

        .file-size {
            font-size: 0.85rem;
            color: #6b7280;
        }

        .character-count {
            font-size: 0.8rem;
            color: var(--primary-dark);
            text-align: right;
            margin-top: 0.25rem;
            font-weight: 500;
        }

        .required-field::after {
            content: '*';
            color: var(--accent);
            margin-left: 0.25rem;
        }

        .submit-btn {
            background: var(--light-gradient);
            border: none;
            color: white;
            padding: 1rem 2rem;
            border-radius: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-top: 1rem;
            box-shadow: 0 8px 20px rgba(78, 205, 196, 0.3);
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(78, 205, 196, 0.4);
            color: white;
            background: var(--light-gradient);
        }

        .back-btn {
            background: var(--primary-dark);
            border: none;
            color: white;
            padding: 1rem 2rem;
            border-radius: 15px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            text-decoration: none;
            margin-right: 1rem;
        }

        .back-btn:hover {
            background: #14444c;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(26, 83, 92, 0.3);
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .flash-message {
            border: none;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-left: 4px solid var(--accent);
        }

        .flash-danger {
            background: linear-gradient(135deg, rgba(255, 107, 107, 0.1) 0%, rgba(255, 255, 255, 0.9) 100%);
            color: var(--primary-dark);
        }

        .form-tips {
            background: var(--highlight-gradient);
            border: 2px solid var(--highlight);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .form-tips h6 {
            color: var(--primary-dark);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
        }

        .form-tips ul {
            margin: 0;
            padding-left: 1.5rem;
            color: var(--primary-dark);
        }

        .form-tips li {
            margin-bottom: 0.5rem;
        }

        .progress-indicator {
            background: white;
            border-radius: 15px;
            padding: 1rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            border: 2px solid rgba(26, 83, 92, 0.1);
        }

        .step-active {
            color: var(--primary-light);
            font-weight: 700;
        }

        .floating {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        @media (max-width: 768px) {
            .apply-page {
                padding: 15px 0;
            }
            
            .page-header-apply {
                padding: 1.5rem;
            }
            
            .apply-form-container {
                padding: 1.5rem;
            }
            
            .section-title {
                font-size: 1.1rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .back-btn, .submit-btn {
                width: 100%;
                margin-right: 0;
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="apply-page">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">

                    <!-- Page Header -->
                    <div class="page-header-apply">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h1 class="mb-2"><i class="fas fa-paper-plane me-2 floating"></i>Apply for Job</h1>
                                <p class="lead mb-0">Submit your application and take the next step in your career journey.</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="bg-white rounded-pill px-3 py-2 d-inline-block">
                                    <small class="fw-bold" style="color: var(--primary-dark);">Step 1 of 1</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Job Preview -->
                    <?php if (!empty($job_title)): ?>
                        <div class="job-preview-card">
                            <div class="job-preview-title"><?= htmlspecialchars($job_title) ?></div>
                            <?php if (!empty($company_name)): ?>
                                <div class="job-preview-company">
                                    <i class="fas fa-building me-2"></i><?= htmlspecialchars($company_name) ?>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($job_location)): ?>
                                <div class="job-preview-location">
                                    <i class="fas fa-map-marker-alt me-2"></i><?= htmlspecialchars($job_location) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Flash Message -->
                    <?php if(!empty($errors)): ?>
                        <div class="alert flash-message flash-danger">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle me-3" style="font-size: 1.5rem; color: var(--accent);"></i>
                                <div class="flex-grow-1">
                                    <h5 class="alert-heading mb-1" style="color: var(--primary-dark);">Application Issues</h5>
                                    <?= implode('<br>', array_map('htmlspecialchars', $errors)) ?>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Apply Form -->
                    <div class="apply-form-container">
                        <form method="post" enctype="multipart/form-data" id="applyForm">
                            <input type="hidden" name="job_id" value="<?= htmlspecialchars($job_id) ?>">

                            <!-- Cover Letter Section -->
                            <div class="form-section">
                                <h3 class="section-title">
                                    <i class="fas fa-envelope-open-text"></i>
                                    Cover Letter
                                </h3>

                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-edit"></i>
                                        Your Cover Letter
                                    </label>
                                    <textarea name="cover_letter" class="form-control" rows="8" 
                                              placeholder="Introduce yourself and explain why you're the perfect candidate for this position. Highlight your relevant experience and skills..."
                                              maxlength="2000"><?= htmlspecialchars($_POST['cover_letter'] ?? '') ?></textarea>
                                    <div class="character-count" id="coverCount">0/2000 characters</div>
                                </div>
                            </div>

                            <!-- Resume Section -->
                            <div class="form-section">
                                <h3 class="section-title">
                                    <i class="fas fa-file-upload"></i>
                                    Upload Resume
                                </h3>

                                <div class="form-group">
                                    <label class="form-label required-field">
                                        <i class="fas fa-file-pdf"></i>
                                        Resume (PDF, DOC, DOCX)
                                    </label>
                                    
                                    <div class="file-upload-area" id="fileUploadArea">
                                        <input type="file" name="resume" class="file-input" id="resumeInput" accept=".pdf,.doc,.docx" required>
                                        <div class="file-upload-icon">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                        </div>
                                        <div class="file-upload-text">
                                            Click to upload or drag and drop
                                        </div>
                                        <div class="file-upload-hint">
                                            Maximum file size: 5MB • Supported formats: PDF, DOC, DOCX
                                        </div>
                                    </div>

                                    <div id="fileInfo" style="display: none;">
                                        <div class="file-info">
                                            <i class="fas fa-file-pdf"></i>
                                            <div class="file-details">
                                                <div class="file-name" id="fileName"></div>
                                                <div class="file-size" id="fileSize"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Tips -->
                            <div class="form-tips">
                                <h6>
                                    <i class="fas fa-lightbulb"></i>
                                    Application Tips
                                </h6>
                                <ul>
                                    <li>Customize your cover letter for this specific position</li>
                                    <li>Highlight relevant experience and achievements</li>
                                    <li>Keep your resume updated and professional</li>
                                    <li>Proofread for spelling and grammar errors</li>
                                    <li>Mention why you're interested in this company</li>
                                </ul>
                            </div>

                            <!-- Action Buttons -->
                            <div class="action-buttons">
                                <a href="/job-portal/public/job_view.php?id=<?= $job_id ?>" class="btn back-btn">
                                    <i class="fas fa-arrow-left"></i>
                                    Back to Job
                                </a>
                                <button type="submit" class="btn submit-btn">
                                    <i class="fas fa-paper-plane"></i>
                                    Submit Application
                                </button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const coverInput = document.querySelector('textarea[name="cover_letter"]');
        const fileInput = document.getElementById('resumeInput');
        const fileUploadArea = document.getElementById('fileUploadArea');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');

        // Character count for cover letter
        function updateCharacterCount() {
            const countElement = document.getElementById('coverCount');
            countElement.textContent = `${coverInput.value.length}/2000 characters`;
        }

        coverInput.addEventListener('input', updateCharacterCount);
        updateCharacterCount(); // Initialize count

        // File upload handling
        fileInput.addEventListener('change', handleFileSelect);
        
        // Drag and drop functionality
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, unhighlight, false);
        });

        function highlight() {
            fileUploadArea.classList.add('dragover');
        }

        function unhighlight() {
            fileUploadArea.classList.remove('dragover');
        }

        fileUploadArea.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            fileInput.files = files;
            handleFileSelect();
        }

        function handleFileSelect() {
            const file = fileInput.files[0];
            if (file) {
                // Validate file type
                const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a PDF, DOC, or DOCX file.');
                    clearFile();
                    return;
                }

                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB.');
                    clearFile();
                    return;
                }

                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);
                fileInfo.style.display = 'block';
                fileUploadArea.classList.add('file-selected');
            }
        }

        function clearFile() {
            fileInput.value = '';
            fileInfo.style.display = 'none';
            fileUploadArea.classList.remove('file-selected');
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        // Form submission enhancement
        const form = document.getElementById('applyForm');
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting Application...';
            submitBtn.disabled = true;
        });
    });
    </script>
</body>
</html>

<?php include __DIR__ . '/../includes/footer.php'; ?>