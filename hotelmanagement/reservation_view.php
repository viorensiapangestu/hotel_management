<?php
// reservation_view.php
require_once 'header.php';

$reservation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$reservation_id) {
    echo "<div class='alert alert-danger'>Invalid Reservation ID provided.</div>";
    require_once 'footer.php';
    exit;
}

// Fetch reservation and guest details
$query = "
    SELECT 
        r.*, 
        g.name, g.phone, g.email, g.id_number 
    FROM Reservations r
    JOIN Guests g ON r.guest_id = g.guest_id
    WHERE r.reservation_id = :id
";
$stmt = $pdo->prepare($query);
$stmt->execute(['id' => $reservation_id]);
$res = $stmt->fetch();

if (!$res) {
    echo "<div class='alert alert-danger'>Reservation not found.</div>";
    require_once 'footer.php';
    exit;
}

// Determine status badge color
$status = $res['status'];
$badgeClass = 'bg-secondary';
if ($status === 'Pending') $badgeClass = 'bg-warning text-dark';
elseif ($status === 'Confirmed') $badgeClass = 'bg-primary';
elseif ($status === 'Checked-In') $badgeClass = 'bg-success';
elseif ($status === 'Checked-Out') $badgeClass = 'bg-info text-dark';
elseif ($status === 'Cancelled') $badgeClass = 'bg-danger';

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-secondary">
        Reservation <span class="text-primary">#RES-<?= str_pad($res['reservation_id'], 4, '0', STR_PAD_LEFT); ?></span>
    </h3>
    <div>
        <a href="reservations.php" class="btn btn-outline-secondary me-2"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
        <a href="reservation_edit.php?id=<?= $res['reservation_id']; ?>" class="btn btn-primary"><i class="fa-solid fa-pen-to-square me-1"></i> Edit</a>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-dark text-white fw-bold">
                <i class="fa-solid fa-bed me-2"></i>Booking Details
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <th class="text-muted w-50">Status:</th>
                        <td><span class="badge <?= $badgeClass; ?> px-2 py-1"><?= sanitize($status); ?></span></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Check-in Date:</th>
                        <td class="fw-semibold"><?= format_date($res['check_in_date']); ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Check-out Date:</th>
                        <td class="fw-semibold"><?= format_date($res['check_out_date']); ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Total Amount:</th>
                        <td class="fw-bold text-success fs-5"><?= format_currency($res['total_amount']); ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-4">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white fw-bold">
                <i class="fa-solid fa-user me-2"></i>Guest Information
            </div>
            <div class="card-body">
                <table class="table table-borderless mb-0">
                    <tr>
                        <th class="text-muted w-50">Full Name:</th>
                        <td class="fw-semibold"><?= sanitize($res['name']); ?></td>
                    </tr>
                    
                    <?php if (!empty($res['companion_names'])): ?>
                    <tr>
                        <th class="text-muted">Companion(s):</th>
                        <td class="fw-semibold"><?= sanitize($res['companion_names']); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th class="text-muted">Phone Number:</th>
                        <td><?= $res['phone'] ? sanitize($res['phone']) : '<span class="text-muted fst-italic">N/A</span>'; ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Email:</th>
                        <td><?= $res['email'] ? sanitize($res['email']) : '<span class="text-muted fst-italic">N/A</span>'; ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">ID / Passport:</th>
                        <td><?= $res['id_number'] ? sanitize($res['id_number']) : '<span class="text-muted fst-italic">N/A</span>'; ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>