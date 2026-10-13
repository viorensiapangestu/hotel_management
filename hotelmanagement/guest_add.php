<?php
// guest_add.php
require_once 'header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $phone = sanitize($_POST['phone']);
    $id_number = sanitize($_POST['id_number']);
    // If email is empty, set it to NULL to avoid UNIQUE constraint conflicts on empty strings
    $email = !empty($_POST['email']) ? sanitize($_POST['email']) : null;

    if (empty($name)) {
        $error = 'Guest name is required.';
    } else {
        $can_proceed = true;

        // Only check for duplicate email if one was provided
        if ($email !== null) {
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM Guests WHERE email = :email");
            $stmtCheck->execute(['email' => $email]);
            if ($stmtCheck->fetchColumn() > 0) {
                $error = 'This email address is already registered to another guest.';
                $can_proceed = false;
            }
        }

        if ($can_proceed) {
            try {
                $stmt = $pdo->prepare("INSERT INTO Guests (name, phone, id_number, email) VALUES (:name, :phone, :id_number, :email)");
                $stmt->execute([
                    'name' => $name,
                    'phone' => $phone,
                    'id_number' => $id_number,
                    'email' => $email
                ]);
                $success = 'Guest registered successfully!';
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
            <h3 class="fw-bold text-secondary">Add New Guest</h3>
            <a href="guests.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i><?= $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="guest_add.php">
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required placeholder="e.g. Jane Doe">
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label fw-semibold">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone" placeholder="e.g. +1 555-0198">
                    </div>

                    <div class="mb-3">
                        <label for="id_number" class="form-label fw-semibold">ID / Passport Number</label>
                        <input type="text" class="form-control" id="id_number" name="id_number" placeholder="e.g. P12345678">
                    </div>

                    <div class="mb-4">
                        <label for="email" class="form-label fw-semibold">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="jane.doe@example.com">
                        <div class="form-text">Must be unique if provided.</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="fa-solid fa-user-plus me-2"></i>Register Guest</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>