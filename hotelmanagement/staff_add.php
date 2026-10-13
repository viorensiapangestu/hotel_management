<?php
// staff_add.php
require_once 'header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $role = sanitize($_POST['role']);

    // Basic Validation
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = 'All fields are required.';
    } else {
        // Check if the email is already in use
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $email_exists = $stmt->fetchColumn();

        if ($email_exists) {
            $error = 'This email address is already registered.';
        } else {
            // Hash the password securely
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert into database
            try {
                $stmt = $pdo->prepare("INSERT INTO Users (name, email, password, role) VALUES (:name, :email, :password, :role)");
                $stmt->execute([
                    'name' => $name,
                    'email' => $email,
                    'password' => $hashed_password,
                    'role' => $role
                ]);
                $success = 'Staff member added successfully!';
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
            <h3 class="fw-bold text-secondary">Add New Staff</h3>
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

                <form method="POST" action="staff_add.php">
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" required placeholder="e.g. John Doe">
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label fw-semibold">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required placeholder="name@grandhotel.com">
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label fw-semibold">Temporary Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-text">They can change this later.</div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="role" class="form-label fw-semibold">System Role</label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="" disabled selected>Select a role...</option>
                            <option value="Staff">Staff (Standard Access)</option>
                            <option value="Admin">Admin (Full Access)</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="fa-solid fa-user-plus me-2"></i>Create Account</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>