<?php
// reservation_edit.php
require_once 'header.php';

$error = '';
$success = '';

$reservation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$reservation_id) {
    echo "<div class='alert alert-danger'>Invalid Reservation ID provided.</div>";
    require_once 'footer.php';
    exit;
}

// Fetch the existing reservation data
$stmtRes = $pdo->prepare("SELECT * FROM Reservations WHERE reservation_id = :id");
$stmtRes->execute(['id' => $reservation_id]);
$res = $stmtRes->fetch();

if (!$res) {
    echo "<div class='alert alert-danger'>Reservation not found.</div>";
    require_once 'footer.php';
    exit;
}

// Fetch guests for the dropdown
$stmtGuests = $pdo->query("SELECT guest_id, name, phone FROM Guests ORDER BY name ASC");
$guests = $stmtGuests->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guest_id = (int)$_POST['guest_id'];
    $check_in_date = $_POST['check_in_date'];
    $check_out_date = $_POST['check_out_date'];
    $status = sanitize($_POST['status']);
    $total_amount = $_POST['total_amount'];

    if (empty($guest_id) || empty($check_in_date) || empty($check_out_date) || empty($status) || $total_amount === '') {
        $error = 'All fields are required.';
    } elseif (strtotime($check_out_date) <= strtotime($check_in_date)) {
        $error = 'Check-out date must be after the check-in date.';
    } elseif (!is_numeric($total_amount) || $total_amount < 0) {
        $error = 'Please enter a valid total amount.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE Reservations SET guest_id = :guest_id, check_in_date = :check_in, check_out_date = :check_out, status = :status, total_amount = :total_amount WHERE reservation_id = :id");
            $stmt->execute([
                'guest_id' => $guest_id,
                'check_in' => $check_in_date,
                'check_out' => $check_out_date,
                'status' => $status,
                'total_amount' => $total_amount,
                'id' => $reservation_id
            ]);
            $success = 'Reservation updated successfully!';
            
            // Update local array so the form reflects the changes
            $res['guest_id'] = $guest_id;
            $res['check_in_date'] = $check_in_date;
            $res['check_out_date'] = $check_out_date;
            $res['status'] = $status;
            $res['total_amount'] = $total_amount;
            
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold text-secondary">Edit Reservation #RES-<?= str_pad($reservation_id, 4, '0', STR_PAD_LEFT); ?></h3>
            <div>
                <a href="reservation_view.php?id=<?= $reservation_id; ?>" class="btn btn-outline-primary me-2"><i class="fa-solid fa-eye me-1"></i> View</a>
                <a href="reservations.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i><?= $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="reservation_edit.php?id=<?= $reservation_id; ?>">
                    <div class="mb-3">
                        <label for="guest_id" class="form-label fw-semibold">Guest</label>
                        <select class="form-select" id="guest_id" name="guest_id" required>
                            <?php foreach ($guests as $guest): ?>
                                <option value="<?= $guest['guest_id']; ?>" <?= $res['guest_id'] == $guest['guest_id'] ? 'selected' : ''; ?>>
                                    <?= sanitize($guest['name']); ?> <?= $guest['phone'] ? ' (' . sanitize($guest['phone']) . ')' : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="check_in_date" class="form-label fw-semibold">Check-in Date</label>
                            <input type="date" class="form-control" id="check_in_date" name="check_in_date" value="<?= $res['check_in_date']; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="check_out_date" class="form-label fw-semibold">Check-out Date</label>
                            <input type="date" class="form-control" id="check_out_date" name="check_out_date" value="<?= $res['check_out_date']; ?>" required>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="status" class="form-label fw-semibold">Reservation Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Pending" <?= $res['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Confirmed" <?= $res['status'] === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="Checked-In" <?= $res['status'] === 'Checked-In' ? 'selected' : ''; ?>>Checked-In</option>
                                <option value="Checked-Out" <?= $res['status'] === 'Checked-Out' ? 'selected' : ''; ?>>Checked-Out</option>
                                <option value="Cancelled" <?= $res['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="total_amount" class="form-label fw-semibold">Total Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" class="form-control" id="total_amount" name="total_amount" value="<?= $res['total_amount']; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100 fw-bold"><i class="fa-solid fa-floppy-disk me-2"></i>Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>