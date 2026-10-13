<?php
// service_add.php
require_once 'header.php';

$error = '';
$success = '';

// Fetch only guests who are currently Checked-In
$in_house_guests = $pdo->query("
    SELECT r.reservation_id, g.name, rm.room_number 
    FROM Reservations r
    JOIN Guests g ON r.guest_id = g.guest_id
    LEFT JOIN Rooms rm ON r.room_id = rm.room_id
    WHERE r.status = 'Checked-In'
")->fetchAll();

// Fetch all services, grouped by category
$services_db = $pdo->query("SELECT * FROM Services ORDER BY category, price DESC")->fetchAll();
$services_by_category = [];
foreach ($services_db as $s) {
    $services_by_category[$s['category']][] = $s;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = (int)$_POST['reservation_id'];
    $quantities = $_POST['qty'] ?? [];

    if (empty($reservation_id)) {
        $error = "Please select a guest.";
    } else {
        try {
            $pdo->beginTransaction();
            $total_added_to_bill = 0;

            foreach ($quantities as $service_id => $qty) {
                $qty = (int)$qty;
                if ($qty > 0) {
                    $stmtPrice = $pdo->prepare("SELECT price FROM Services WHERE service_id = ?");
                    $stmtPrice->execute([$service_id]);
                    $price = (float)$stmtPrice->fetchColumn();

                    $line_total = $price * $qty;
                    $total_added_to_bill += $line_total;

                    $stmtOrder = $pdo->prepare("INSERT INTO GuestServices (reservation_id, service_id, quantity, total_price) VALUES (?, ?, ?, ?)");
                    $stmtOrder->execute([$reservation_id, $service_id, $qty, $line_total]);
                }
            }

            if ($total_added_to_bill > 0) {
                // --- NEW: Generate a completely separate invoice for these services! ---
                $stmtNewInvoice = $pdo->prepare("INSERT INTO Invoices (reservation_id, issued_date, total_amount, status) VALUES (?, CURDATE(), ?, 'Unpaid')");
                $stmtNewInvoice->execute([$reservation_id, $total_added_to_bill]);

                $pdo->commit();
                $success = "Services added successfully! A new separate invoice for $" . number_format($total_added_to_bill, 2) . " has been created.";
            } else {
                $pdo->rollBack();
                $error = "Please enter a quantity for at least one service.";
            }
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error adding services: " . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold text-secondary"><i class="fa-solid fa-bell-concierge me-2"></i>Add Services</h3>
            <a href="services.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back to List</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i><?= $success; ?></div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 border-top border-primary border-4">
            <div class="card-body p-4">
                <form method="POST">
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold text-dark">Select In-House Guest</label>
                        <select name="reservation_id" class="form-select form-select-lg" required>
                            <option value="" disabled selected>Who is ordering?</option>
                            <?php foreach ($in_house_guests as $guest): ?>
                                <option value="<?= $guest['reservation_id']; ?>">
                                    <?= sanitize($guest['name']); ?> (Room <?= sanitize($guest['room_number']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <h5 class="fw-bold text-secondary mb-3 border-bottom pb-2">Select Services</h5>
                    
                    <?php foreach ($services_by_category as $category => $items): ?>
                        <div class="mb-4">
                            <h6 class="fw-bold text-primary text-uppercase"><?= sanitize($category); ?></h6>
                            <div class="row g-2">
                                <?php foreach ($items as $item): ?>
                                    <div class="col-md-6">
                                        <div class="input-group input-group-sm mb-2">
                                            <span class="input-group-text bg-light w-50 text-start" style="font-size: 0.85rem;">
                                                <?= sanitize($item['service_name']); ?><br>
                                                <small class="text-success fw-bold">$<?= number_format($item['price'], 2); ?></small>
                                            </span>
                                            <input type="number" name="qty[<?= $item['service_id']; ?>]" class="form-control service-qty" min="0" placeholder="Qty" data-price="<?= $item['price']; ?>">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="d-flex justify-content-between align-items-center mt-4 p-3 bg-light rounded border">
                        <span class="fw-bold fs-5">Live Total:</span>
                        <span class="fw-bold fs-4 text-success" id="live_total">$0.00</span>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold py-2 mt-3">
                        <i class="fa-solid fa-cart-plus me-2"></i> Charge to Guest Room
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.service-qty').forEach(input => {
    input.addEventListener('input', () => {
        let total = 0;
        document.querySelectorAll('.service-qty').forEach(box => {
            const qty = parseInt(box.value) || 0;
            const price = parseFloat(box.getAttribute('data-price')) || 0;
            total += (qty * price);
        });
        document.getElementById('live_total').innerText = '$' + total.toFixed(2);
    });
});
</script>

<?php require_once 'footer.php'; ?>