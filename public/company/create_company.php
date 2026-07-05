<?php
// public/company_create.php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/auth.php';
requireLogin();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $industry = trim($_POST['industry'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $logo_path = null;

    if ($name === '') $errors[] = 'Company name required';

    // file upload
    if (!empty($_FILES['logo']['name'])) {
        $allowed = ['image/png','image/jpeg','image/jpg'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['logo']['tmp_name']);
        if (!in_array($mime,$allowed)) $errors[] = 'Logo must be PNG/JPG';
        if ($_FILES['logo']['size'] > MAX_UPLOAD_SIZE) $errors[] = 'Logo too large';
        if (empty($errors)) {
            $ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $target = UPLOAD_DIR . 'logos/';
            if (!is_dir($target)) mkdir($target, 0755, true);
            $fname = uniqid('logo_') . '.' . $ext;
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $target . $fname)) {
                $logo_path = 'assets/uploads/logos/' . $fname;
            } else $errors[] = 'Failed to upload logo';
        }
    }

    if (empty($errors)) {
        $user_id = currentUserId();
        $stmt = $conn->prepare("INSERT INTO companies (user_id,name,website,industry,description,logo_path) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param('isssss',$user_id,$name,$website,$industry,$description,$logo_path);
        if ($stmt->execute()) {
            header('Location: /company_list.php?created=1');
            exit;
        } else $errors[] = 'DB error: '.$conn->error;
    }
}
include __DIR__ . '/includes/header.php';
?>
<div class="container mt-4">
  <h2>Create Company Profile</h2>
  <?php if($errors): ?><div class="alert alert-danger"><?= htmlspecialchars(implode('<br>',$errors)) ?></div><?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Website</label><input name="website" class="form-control"></div>
    <div class="mb-3"><label class="form-label">Industry</label><input name="industry" class="form-control"></div>
    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control"></textarea></div>
    <div class="mb-3"><label class="form-label">Logo (PNG/JPG)</label><input name="logo" type="file" class="form-control"></div>
    <button class="btn btn-success">Create</button>
  </form>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
