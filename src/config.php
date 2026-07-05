<?php
// src/config.php
define('DB_HOST','localhost');
define('DB_USER','root');
define('DB_PASS',''); // set your DB password
define('DB_NAME','job_portal');

// file upload settings
define('UPLOAD_DIR', __DIR__ . '/../public/assets/uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB
