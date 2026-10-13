<?php
// check_in_out.php
require_once 'header.php';

$error = '';
$success = '';

// --- HANDLE ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. PROCESS CHECK-IN
    if ($action === 'check_in') {
        $reservation_id = (int)$_POST['reservation_id'];

        // Count ANY unpaid invoices for this reservation (Room bill must be paid first)
        $stmtPay = $pdo->prepare("SELECT COUNT(*) FROM Invoices WHERE reservation_id = :id AND status != 'Paid'");
        $stmtPay->execute(['id' => $reservation_id]);
        $unpaid_count = $stmtPay->fetchColumn();

        $stmtRoom = $pdo->prepare("SELECT room_id FROM Reservations WHERE reservation_id = :id");
        $stmtRoom->execute(['id' => $reservation_id]);
        $room_id = $stmtRoom->fetchColumn();

        if ($unpaid_count > 0) {
            $error = 'Guest cannot be checked in! Their main room bill has not been paid yet.';
        } elseif (!$room_id) {
            $error = 'Critical Error: No room was assigned to this reservation.';
        } else {
            try {
                $pdo->beginTransaction();
                $stmtStay = $pdo->prepare("INSERT INTO Stay (reservation_id, check_in_time) VALUES (:res_id, NOW())");
                $stmtStay->execute(['res_id' => $reservation_id]);
                $pdo->prepare("UPDATE Reservations SET status = 'Checked-In' WHERE reservation_id = ?")->execute([$reservation_id]);
                $pdo->commit();
                $success = 'Guest successfully checked into their pre-assigned room!';
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Check-in failed: ' . $e->getMessage();
            }
        }
    }

    // 2. PROCESS CHECK-OUT (WITH STRICT LOCK!)
    if ($action === 'check_out') {
        $stay_id = (int)$_POST['stay_id'];
        $reservation_id = (int)$_POST['reservation_id'];
        $room_id = (int)$_POST['room_id'];

        // Check if the guest has ANY unpaid service or room bills
        $stmtPayCheck = $pdo->prepare("SELECT COUNT(*) FROM Invoices WHERE reservation_id = ? AND status != 'Paid'");
        $stmtPayCheck->execute([$reservation_id]);
        $unpaid_bills = $stmtPayCheck->fetchColumn();

        if ($unpaid_bills > 0) {
            $error = "Guest cannot check out! They currently have <b>" . $unpaid_bills . " unpaid invoice(s)</b> (room or extra services). Please clear their bills first.";
        } else {
            try {
                $pdo->beginTransaction();
                $pdo->prepare("UPDATE Stay SET check_out_time = NOW() WHERE stay_id = ?")->execute([$stay_id]);
                $pdo->prepare("UPDATE Reservations SET status = 'Checked-Out' WHERE reservation_id = ?")->execute([$reservation_id]);
                
                $stmtStatus = $pdo->query("SELECT status_id FROM RoomStatus WHERE status_name LIKE '%Dirty%' OR status_name LIKE '%Maintenance%' LIMIT 1");
                if ($dirty_id = $stmtStatus->fetchColumn()) {
                    $pdo->prepare("UPDATE Rooms SET status_id = ? WHERE room_id = ?")->execute([$dirty_id, $room_id]);
                }
                $pdo->commit();
                $success = "Check-out processed! Room requires housekeeping.";
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = "Check-out failed: " . $e->getMessage();
            }
        }
    }
}

// --- FETCH DATA FOR DASHBOARD ---

// 1. Pending Arrivals (Count unpaid bills to block check-in)
$arrivals = $pdo->query("
    SELECT 
        r.reservation_id, g.name, r.check_in_date, rm.room_number,
        (SELECT COUNT(*) FROM Invoices WHERE reservation_id = r.reservation_id AND status != 'Paid') AS unpaid_invoices
    FROM Reservations r
    JOIN Guests g ON r.guest_id = g.guest_id
    LEFT JOIN Rooms rm ON r.room_id = rm.room_id
    WHERE r.status IN ('Confirmed', 'Pending')
    ORDER BY r.check_in_date ASC
")->fetchAll();

// 2. Currently In-House (Count unpaid bills to warn receptionist)
$in_house = $pdo->query("
    SELECT 
        s.stay_id, s.check_in_time, r.reservation_id, rm.room_id, rm.room_number, g.name,
        (SELECT COUNT(*) FROM Invoices WHERE reservation_id = r.reservation_id AND status != 'Paid') AS unpaid_invoices
    FROM Stay s
    JOIN Reservations r ON s.reservation_id = r.reservation_id
    JOIN Rooms rm ON r.room_id = rm.room_id
    JOIN Guests g ON r.guest_id = g.guest_id
    WHERE s.check_out_time IS NULL
")->fetchAll();

// 3. Historical Data
$history = $pdo->query("
    SELECT g.name, rm.room_number, s.check_in_time, s.check_out_time 
    FROM Stay s
    JOIN Reservations r ON s.reservation_id = r.reservation_id
    JOIN Rooms rm ON r.room_id = rm.room_id
    JOIN Guests g ON r.guest_id = g.guest_id
    WHERE s.check_out_time IS NOT NULL
    ORDER BY s.check_out_time DESC
")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-secondary"><i class="fa-solid fa-bell-concierge me-2"></i>Actual Check-In & Check-Out</h3>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= $error; ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="fa-solid fa-circle-check me-2"></i><?= $success; ?></div>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-xl-6 mb-4">
        <div class="card shadow-sm border-top border-primary border-4 h-100">
            <div class="card-header bg-white"><h5 class="mb-0 fw-bold text-primary">Expected Arrivals</h5></div>
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Guest</th><th>Payment</th><th class="text-end pe-4">Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($arrivals as $arr): 
                            $isPaid = ($arr['unpaid_invoices'] == 0);
                        ?>
                        <tr>
                            <td class="ps-3 fw-semibold"><?= sanitize($arr['name']); ?></td>
                            <td>
                                <?php if ($isPaid): ?>
                                    <span class="badge bg-success">Paid</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Unpaid Bills</span>
                                <?php endif; ?>
                            </td>
                            <td class="pe-3 text-end">
                                <form method="POST" class="d-flex justify-content-end align-items-center">
                                    <input type="hidden" name="action" value="check_in">
                                    <input type="hidden" name="reservation_id" value="<?= $arr['reservation_id']; ?>">
                                    <span class="badge bg-dark fs-6 me-3">Rm <?= sanitize($arr['room_number'] ?? 'N/A'); ?></span>
                                    <button type="submit" class="btn btn-sm btn-success fw-bold" <?= !$isPaid ? 'disabled' : ''; ?>>
                                        Check In <i class="fa-solid fa-arrow-right-to-bracket ms-1"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; if (empty($arrivals)) echo "<tr><td colspan='3' class='text-center py-3 text-muted'>No pending arrivals.</td></tr>"; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-6 mb-4">
        <div class="card shadow-sm border-top border-warning border-4 h-100">
            <div class="card-header bg-white"><h5 class="mb-0 fw-bold text-warning text-dark">Active Stays (Ready for Check-Out)</h5></div>
            <div class="card-body p-0">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>Guest</th><th>Room</th><th>Actual Arrival</th><th class="text-end">Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($in_house as $in): ?>
                        <tr>
                            <td class="ps-3 fw-semibold text-primary"><?= sanitize($in['name']); ?></td>
                            <td><span class="badge bg-dark"><?= sanitize($in['room_number']); ?></span></td>
                            <td class="text-muted small"><?= date('M d, h:i A', strtotime($in['check_in_time'])); ?></td>
                            <td class="text-end pe-3">
                                <form method="POST" class="d-inline" onsubmit="return confirm('Check out <?= sanitize($in['name']); ?>?');">
                                    <input type="hidden" name="action" value="check_out">
                                    <input type="hidden" name="stay_id" value="<?= $in['stay_id']; ?>">
                                    <input type="hidden" name="reservation_id" value="<?= $in['reservation_id']; ?>">
                                    <input type="hidden" name="room_id" value="<?= $in['room_id']; ?>">
                                    
                                    <?php if ($in['unpaid_invoices'] > 0): ?>
                                        <span class="badge bg-danger me-2"><?= $in['unpaid_invoices']; ?> Unpaid Bill(s)</span>
                                    <?php endif; ?>

                                    <button type="submit" class="btn btn-sm btn-warning fw-bold text-dark">Check Out</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; if (empty($in_house)) echo "<tr><td colspan='4' class='text-center py-3 text-muted'>No active guests in-house.</td></tr>"; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-top border-secondary border-4">
    <div class="card-header bg-white">
        <h5 class="mb-0 fw-bold text-secondary"><i class="fa-solid fa-clock-rotate-left me-2"></i>Actual Stay History</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-4">Guest Name</th>
                        <th>Room</th>
                        <th>Exact Check-In Time</th>
                        <th>Exact Check-Out Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($history) > 0): ?>
                        <?php foreach ($history as $hist): ?>
                            <tr>
                                <td class="ps-4 fw-semibold"><?= sanitize($hist['name']); ?></td>
                                <td><span class="badge bg-secondary"><?= sanitize($hist['room_number']); ?></span></td>
                                <td class="text-success fw-bold"><?= date('d M Y, h:i A', strtotime($hist['check_in_time'])); ?></td>
                                <td class="text-danger fw-bold"><?= date('d M Y, h:i A', strtotime($hist['check_out_time'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center py-4 text-muted">No completed stays recorded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>