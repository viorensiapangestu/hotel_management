<?php
// staff_edit.php
require_once 'header.php';

$error = '';
$success = '';

// Get the user ID from the URL
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$user_id) {
    echo "<div class='alert alert-danger'>Invalid Staff ID provided.</div>";
    require_once 'footer.php';
    exit;
}

// Fetch the existing user data
$stmt = $pdo->prepare("SELECT * FROM Users WHERE user_id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo "<div class='alert alert-danger'>Staff member not found.</div>";
    require_once 'footer.php';
    exit;
}

// Handle the form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $role = sanitize($_POST['role']);
    $password = $_POST['password']; // Optional field

    if (empty($name) || empty($email) || empty($role)) {
        $error = 'Name, email, and role are required.';
    } else {
        // Ensure the new email isn't already taken by a DIFFERENT user
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Users WHERE email = :email AND user_id != :id");
        $stmt->execute(['email' => $email, 'id' => $user_id]);
        $email_exists = $stmt->fetchColumn();

        if ($email_exists) {
            $error = 'This email address is already in use by another account.';
        } else {
            try {
                if (!empty($password)) {
                    // Update EVERYTHING, including a new hashed password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE Users SET name = :name, email = :email, role = :role, password = :password WHERE user_id = :id");
                    $stmt->execute([
                        'name' => $name, 'email' => $email, 'role' => $role, 
                        'password' => $hashed_password, 'id' => $user_id
                    ]);
                } else {
                    // Update details ONLY, keep the old password
                    $stmt = $pdo->prepare("UPDATE Users SET name = :name, email = :email, role = :role WHERE user_id = :id");
                    $stmt->execute([
                        'name' => $name, 'email' => $email, 'role' => $role, 'id' => $user_id
                    ]);
                }
                
                $success = 'Staff member updated successfully!';
                
                // Update the local $user array so the form reflects the new changes immediately
                $user['name'] = $name;
                $user['email'] = $email;
                $user['role'] = $role;

            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold text-secondary">Edit Staff Member</h3>
            <a href="staff_manage.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i><?= $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="staff_edit.php?id=<?= $user_id; ?>">
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= sanitize($user['name']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= sanitize($user['email']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">New Password (Optional)</label>
                        <input type="password" class="form-control" id="password" name="password">
                        <div class="form-text text-muted">Leave blank to keep the current password.</div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="role" class="form-label fw-semibold">System Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="Staff" <?= $user['role'] === 'Staff' ? 'selected' : ''; ?>>Staff (Standard Access)</option>
                            <option value="Admin" <?= $user['role'] === 'Admin' ? 'selected' : ''; ?>>Admin (Full Access)</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100 fw-bold"><i class="fa-solid fa-floppy-disk me-2"></i>Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>