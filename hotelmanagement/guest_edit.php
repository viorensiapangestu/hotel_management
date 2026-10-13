<?php
// guest_edit.php
require_once 'header.php';

$error = '';
$success = '';

$guest_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$guest_id) {
    echo "<div class='alert alert-danger'>Invalid Guest ID provided.</div>";
    require_once 'footer.php';
    exit;
}

// Fetch existing guest data
$stmtGuest = $pdo->prepare("SELECT * FROM Guests WHERE guest_id = :id");
$stmtGuest->execute(['id' => $guest_id]);
$guest = $stmtGuest->fetch();

if (!$guest) {
    echo "<div class='alert alert-danger'>Guest not found.</div>";
    require_once 'footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $phone = sanitize($_POST['phone']);
    $id_number = sanitize($_POST['id_number']);
    $email = !empty($_POST['email']) ? sanitize($_POST['email']) : null;

    if (empty($name)) {
        $error = 'Guest name is required.';
    } else {
        $can_proceed = true;

        if ($email !== null) {
            // Check uniqueness but ignore THIS guest's current email
            $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM Guests WHERE email = :email AND guest_id != :id");
            $stmtCheck->execute(['email' => $email, 'id' => $guest_id]);
            if ($stmtCheck->fetchColumn() > 0) {
                $error = 'This email address is already in use by another guest.';
                $can_proceed = false;
            }
        }

        if ($can_proceed) {
            try {
                $stmt = $pdo->prepare("UPDATE Guests SET name = :name, phone = :phone, id_number = :id_number, email = :email WHERE guest_id = :id");
                $stmt->execute([
                    'name' => $name,
                    'phone' => $phone,
                    'id_number' => $id_number,
                    'email' => $email,
                    'id' => $guest_id
                ]);
                $success = 'Guest information updated successfully!';
                
                // Update local array for the form
                $guest['name'] = $name;
                $guest['phone'] = $phone;
                $guest['id_number'] = $id_number;
                $guest['email'] = $email;
                
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
            <h3 class="fw-bold text-secondary">Edit Guest Information</h3>
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

                <form method="POST" action="guest_edit.php?id=<?= $guest_id; ?>">
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= sanitize($guest['name']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="phone" class="form-label fw-semibold">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?= sanitize((string)$guest['phone']); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="id_number" class="form-label fw-semibold">ID / Passport Number</label>
                        <input type="text" class="form-control" id="id_number" name="id_number" value="<?= sanitize((string)$guest['id_number']); ?>">
                    </div>

                    <div class="mb-4">
                        <label for="email" class="form-label fw-semibold">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= sanitize((string)$guest['email']); ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100 fw-bold"><i class="fa-solid fa-floppy-disk me-2"></i>Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>