<?php
session_start();
require_once __DIR__ . '/../../src/db.php';
require_once __DIR__ . '/../../src/auth.php';

// Require user login
requireLogin();

$uid = currentUserId();
if (!$uid) {
    header('Location: /job-portal/public/login.php');
    exit;
}

// Helper to ensure resume_path column
function ensure_resume_column($conn) {
    $res = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'resume_path'");
    if (mysqli_num_rows($res) === 0) {
        // add column
        mysqli_query($conn, "ALTER TABLE users ADD COLUMN resume_path VARCHAR(255) DEFAULT NULL");
    }
}

ensure_resume_column($conn);

$msg = '';
$msg_type = ''; // success, danger, info
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['resume']) || empty($_FILES['resume']['name'])) {
        $msg = 'Please choose a file to upload.';
        $msg_type = 'danger';
    } else {
        $allowed = ['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['resume']['tmp_name']);
        if (!in_array($mime, $allowed)) {
            $msg = 'Resume must be PDF, DOC, or DOCX format.';
            $msg_type = 'danger';
        } elseif ($_FILES['resume']['size'] > MAX_UPLOAD_SIZE) {
            $msg = 'File too large (maximum 5 MB allowed).';
            $msg_type = 'danger';
        } else {
            $ext = pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION);
            $target = UPLOAD_DIR . 'resumes/';
            if (!is_dir($target)) mkdir($target, 0755, true);
            $fname = uniqid('resume_') . '.' . $ext;
            if (!move_uploaded_file($_FILES['resume']['tmp_name'], $target . $fname)) {
                $msg = 'Upload failed. Please try again.';
                $msg_type = 'danger';
            } else {
                $resume_path = 'assets/uploads/resumes/' . $fname;
                $u = $conn->prepare("UPDATE users SET resume_path = ? WHERE id = ?");
                if ($u === false) {
                    $msg = 'Database error. Please try again.';
                    $msg_type = 'danger';
                } else {
                    $u->bind_param('si', $resume_path, $uid);
                    if ($u->execute()) {
                        $msg = 'Your CV has been uploaded successfully!';
                        $msg_type = 'success';
                        // Update user data (guard against $user being a string)
                        if (!is_array($user)) {
                            $user = [];
                        }
                        $user['resume_path'] = $resume_path;
                    } else {
                        $msg = 'Database error. Please try again.';
                        $msg_type = 'danger';
                    }
                }
            }
        }
    }
}

$user_stmt = $conn->prepare('SELECT username, full_name, email, resume_path FROM users WHERE id = ? LIMIT 1');
if ($user_stmt === false) {
    // prepare failed — avoid fatal and show minimal page, user will have to re-login or contact admin
    $user = [];
} else {
    $user_stmt->bind_param('i', $uid);
    $user_stmt->execute();
    $user = $user_stmt->get_result()->fetch_assoc();
}

// close finfo if opened
if (isset($finfo) && is_resource($finfo)) {
    finfo_close($finfo);
}

include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload CV - Job Portal</title>
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

        .upload-cv-page {
            min-height: 100vh;
            padding: 30px 0;
        }

        .page-header-cv {
            background: var(--gradient);
            color: white;
            border-radius: 20px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 15px 35px rgba(26, 83, 92, 0.3);
            position: relative;
            overflow: hidden;
        }

        .page-header-cv::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: rgba(78, 205, 196, 0.2);
            border-radius: 50%;
        }

        .cv-form-container {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            border: 2px solid rgba(26, 83, 92, 0.1);
        }

        .user-header {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, rgba(247, 255, 247, 0.8) 0%, rgba(255, 255, 255, 0.9) 100%);
            border-radius: 15px;
            border-left: 4px solid var(--primary-light);
            box-shadow: 0 5px 15px rgba(78, 205, 196, 0.1);
        }

        .user-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: var(--light-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.3rem;
            margin-right: 1.5rem;
            box-shadow: 0 5px 15px rgba(78, 205, 196, 0.3);
        }

        .user-info h4 {
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .user-info p {
            color: #666;
            margin: 0;
            font-size: 0.9rem;
        }

        .form-section {
            margin-bottom: 2.5rem;
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

        .file-upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 15px;
            padding: 3rem 2rem;
            text-align: center;
            transition: all 0.3s ease;
            background: #fafafa;
            cursor: pointer;
            position: relative;
            margin-bottom: 1.5rem;
        }

        .file-upload-area:hover {
            border-color: var(--primary-light);
            background: #f0f4ff;
        }

        .file-upload-area.dragover {
            border-color: var(--primary-light);
            background: #e0e7ff;
            transform: scale(1.02);
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
            font-size: 4rem;
            color: var(--primary-light);
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }

        .file-upload-area:hover .file-upload-icon {
            transform: translateY(-5px);
        }

        .file-upload-text {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 0.5rem;
        }

        .file-upload-hint {
            font-size: 0.9rem;
            color: #6b7280;
            margin-bottom: 1rem;
        }

        .file-upload-features {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-top: 1.5rem;
        }

        .file-feature {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary-dark);
            font-size: 0.85rem;
            font-weight: 500;
        }

        .file-feature i {
            color: var(--primary-light);
        }

        .file-selected {
            background: rgba(78, 205, 196, 0.1);
            border-color: var(--primary-light);
        }

        .file-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem;
            background: rgba(78, 205, 196, 0.1);
            border-radius: 12px;
            margin-top: 1.5rem;
            border-left: 4px solid var(--primary-light);
        }

        .file-info i {
            color: var(--primary-light);
            font-size: 2rem;
        }

        .file-details {
            flex: 1;
        }

        .file-name {
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 0.25rem;
            font-size: 1.1rem;
        }

        .file-size {
            font-size: 0.9rem;
            color: #6b7280;
        }

        .file-remove {
            background: none;
            border: none;
            color: var(--accent);
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .file-remove:hover {
            background: rgba(255, 107, 107, 0.1);
            transform: scale(1.1);
        }

        .current-cv-card {
            background: rgba(78, 205, 196, 0.1);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            border-left: 4px solid var(--primary-light);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .current-cv-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .current-cv-header i {
            font-size: 2rem;
            color: var(--primary-dark);
            margin-right: 1rem;
        }

        .current-cv-title {
            font-weight: 700;
            color: var(--primary-dark);
            font-size: 1.2rem;
            margin-bottom: 0.25rem;
        }

        .current-cv-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .cv-action-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .cv-view-btn {
            background: var(--light-gradient);
            color: white;
        }

        .cv-view-btn:hover {
            background: var(--light-gradient);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 205, 196, 0.3);
        }

        .cv-replace-btn {
            background: var(--primary-dark);
            color: white;
        }

        .cv-replace-btn:hover {
            background: #14444c;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 83, 92, 0.3);
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

        .flash-message {
            border: none;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .flash-success {
            border-left: 4px solid var(--primary-light);
            background: linear-gradient(135deg, rgba(78, 205, 196, 0.1) 0%, rgba(255, 255, 255, 0.9) 100%);
            color: var(--primary-dark);
        }

        .flash-danger {
            border-left: 4px solid var(--accent);
            background: linear-gradient(135deg, rgba(255, 107, 107, 0.1) 0%, rgba(255, 255, 255, 0.9) 100%);
            color: var(--primary-dark);
        }

        .flash-info {
            border-left: 4px solid var(--primary-light);
            background: linear-gradient(135deg, rgba(78, 205, 196, 0.1) 0%, rgba(255, 255, 255, 0.9) 100%);
            color: var(--primary-dark);
        }

        .cv-tips {
            background: var(--highlight-gradient);
            border: 2px solid var(--highlight);
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .cv-tips h6 {
            color: var(--primary-dark);
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 700;
        }

        .cv-tips ul {
            margin: 0;
            padding-left: 1.5rem;
            color: var(--primary-dark);
        }

        .cv-tips li {
            margin-bottom: 0.5rem;
        }

        .no-cv-card {
            background: var(--highlight-gradient);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            border-left: 4px solid var(--highlight);
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .no-cv-icon {
            font-size: 3rem;
            color: var(--primary-dark);
            margin-bottom: 1rem;
        }

        .no-cv-title {
            font-weight: 700;
            color: var(--primary-dark);
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }

        .no-cv-text {
            color: var(--primary-dark);
            opacity: 0.8;
        }

        .floating {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        @media (max-width: 768px) {
            .upload-cv-page {
                padding: 15px 0;
            }
            
            .page-header-cv {
                padding: 1.5rem;
            }
            
            .cv-form-container {
                padding: 1.5rem;
            }
            
            .user-header {
                flex-direction: column;
                text-align: center;
            }
            
            .user-avatar {
                margin-right: 0;
                margin-bottom: 1rem;
            }
            
            .file-upload-features {
                flex-direction: column;
                gap: 1rem;
            }
            
            .current-cv-actions {
                flex-direction: column;
            }
            
            .cv-action-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="upload-cv-page">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">

                    <!-- Page Header -->
                    <div class="page-header-cv">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h1 class="mb-2"><i class="fas fa-file-upload me-2 floating"></i>Manage Your CV</h1>
                                <p class="lead mb-0">Upload your resume to apply for jobs faster and stand out to employers.</p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="bg-white rounded-pill px-3 py-2 d-inline-block">
                                    <small class="fw-bold" style="color: var(--primary-dark);">Career Essentials</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Flash Message -->
                    <?php if (!empty($msg)): ?>
                        <div class="alert flash-message flash-<?= $msg_type ?>">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-<?= $msg_type === 'success' ? 'check-circle' : ($msg_type === 'danger' ? 'exclamation-triangle' : 'info-circle') ?> me-3" 
                                   style="font-size: 1.5rem; color: <?= $msg_type === 'success' ? 'var(--primary-light)' : ($msg_type === 'danger' ? 'var(--accent)' : 'var(--primary-light)') ?>;"></i>
                                <div class="flex-grow-1">
                                    <h5 class="alert-heading mb-1" style="color: var(--primary-dark);">
                                        <?= $msg_type === 'success' ? 'Success!' : ($msg_type === 'danger' ? 'Please Check' : 'Notice') ?>
                                    </h5>
                                    <?= htmlspecialchars($msg) ?>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- CV Form Container -->
                    <div class="cv-form-container">
                        <!-- User Header -->
                        <div class="user-header">
                            <div class="user-avatar">
                                <?= strtoupper(substr($user['username'] ?? 'U', 0, 1)) ?>
                            </div>
                            <div class="user-info">
                                <h4><?= htmlspecialchars($user['full_name'] ?? $user['username'] ?? 'User') ?></h4>
                                <p>Keep your resume updated for better job opportunities</p>
                            </div>
                        </div>

                        <!-- Current CV Section -->
                        <?php if (!empty($user['resume_path'])): ?>
                            <div class="current-cv-card">
                                <div class="current-cv-header">
                                    <i class="fas fa-file-check" style="color: var(--primary-light);"></i>
                                    <div>
                                        <div class="current-cv-title">Current CV Uploaded</div>
                                        <div style="color: var(--primary-dark); opacity: 0.8;">Ready for job applications</div>
                                    </div>
                                </div>
                                <div class="current-cv-actions">
                                    <a href="/job-portal/public/<?= htmlspecialchars($user['resume_path']) ?>" 
                                       target="_blank" 
                                       class="cv-action-btn cv-view-btn">
                                        <i class="fas fa-eye"></i>
                                        View Current CV
                                    </a>
                                    <a href="#upload-section" 
                                       class="cv-action-btn cv-replace-btn">
                                        <i class="fas fa-sync"></i>
                                        Replace CV
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="no-cv-card">
                                <div class="no-cv-icon">
                                    <i class="fas fa-file-exclamation"></i>
                                </div>
                                <div class="no-cv-title">No CV Uploaded Yet</div>
                                <div class="no-cv-text">Upload your resume to start applying for jobs</div>
                            </div>
                        <?php endif; ?>

                        <!-- Upload Section -->
                        <div class="form-section" id="upload-section">
                            <h3 class="section-title">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <?= empty($user['resume_path']) ? 'Upload Your CV' : 'Update Your CV' ?>
                            </h3>

                            <form method="POST" enctype="multipart/form-data" id="cvForm">
                                <div class="file-upload-area" id="fileUploadArea">
                                    <input type="file" name="resume" class="file-input" id="resumeInput" 
                                           accept=".pdf,.doc,.docx" required>
                                    <div class="file-upload-icon">
                                        <i class="fas fa-file-upload"></i>
                                    </div>
                                    <div class="file-upload-text">
                                        Drag & drop your CV here
                                    </div>
                                    <div class="file-upload-hint">
                                        or click to browse files
                                    </div>
                                    
                                    <div class="file-upload-features">
                                        <div class="file-feature">
                                            <i class="fas fa-file-pdf"></i>
                                            <span>PDF</span>
                                        </div>
                                        <div class="file-feature">
                                            <i class="fas fa-file-word"></i>
                                            <span>DOC/DOCX</span>
                                        </div>
                                        <div class="file-feature">
                                            <i class="fas fa-weight-hanging"></i>
                                            <span>Max 5MB</span>
                                        </div>
                                    </div>
                                </div>

                                <div id="fileInfo" style="display: none;">
                                    <div class="file-info">
                                        <i class="fas fa-file-alt"></i>
                                        <div class="file-details">
                                            <div class="file-name" id="fileName"></div>
                                            <div class="file-size" id="fileSize"></div>
                                        </div>
                                        
                                    </div>
                                </div>

                                <!-- CV Tips -->
                                <div class="cv-tips">
                                    <h6>
                                        <i class="fas fa-lightbulb"></i>
                                        CV Best Practices
                                    </h6>
                                    <ul>
                                        <li>Use a professional file name (e.g., "John_Doe_Resume.pdf")</li>
                                        <li>Ensure your contact information is up-to-date</li>
                                        <li>Keep it to 1-2 pages for best results</li>
                                        <li>Highlight relevant skills and experience</li>
                                        <li>Proofread for spelling and grammar errors</li>
                                    </ul>
                                </div>

                                <button type="submit" class="btn submit-btn" id="submitBtn">
                                    <i class="fas fa-upload"></i>
                                    <?= empty($user['resume_path']) ? 'Upload CV' : 'Update CV' ?>
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('resumeInput');
        const fileUploadArea = document.getElementById('fileUploadArea');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        const submitBtn = document.getElementById('submitBtn');

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
                    showAlert('Please select a PDF, DOC, or DOCX file.', 'danger');
                    clearFile();
                    return;
                }

                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    showAlert('File size must be less than 5MB.', 'danger');
                    clearFile();
                    return;
                }

                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);
                fileInfo.style.display = 'block';
                fileUploadArea.classList.add('file-selected');
                
                // Update button text
                submitBtn.innerHTML = '<i class="fas fa-upload"></i> Upload ' + file.name;
            }
        }

        function clearFile() {
            fileInput.value = '';
            fileInfo.style.display = 'none';
            fileUploadArea.classList.remove('file-selected');
            submitBtn.innerHTML = '<i class="fas fa-upload"></i> <?= empty($user['resume_path']) ? 'Upload CV' : 'Update CV' ?>';
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function showAlert(message, type) {
            // Create temporary alert
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert flash-message flash-${type} mt-3`;
            alertDiv.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-${type === 'danger' ? 'exclamation-triangle' : 'info-circle'} me-3" 
                       style="font-size: 1.5rem; color: ${type === 'danger' ? 'var(--accent)' : 'var(--primary-light)'};"></i>
                    <div class="flex-grow-1" style="color: var(--primary-dark);">${message}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `;
            
            const container = document.querySelector('.cv-form-container');
            container.insertBefore(alertDiv, container.firstChild);
            
            // Remove alert after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 5000);
        }

        // Form submission enhancement
        const form = document.getElementById('cvForm');
        form.addEventListener('submit', function(e) {
            if (!fileInput.files.length) {
                e.preventDefault();
                showAlert('Please select a file to upload.', 'danger');
                return;
            }
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
            submitBtn.disabled = true;
        });
    });
    </script>
</body>
</html>

<?php include __DIR__ . '/../includes/footer.php'; ?>