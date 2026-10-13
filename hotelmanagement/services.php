<?php
// services.php
require_once 'header.php';

// Fetch all ordered services
$query = "
    SELECT 
        gs.guest_service_id,
        g.name AS guest_name,
        rm.room_number,
        s.category,
        s.service_name,
        gs.quantity,
        gs.total_price,
        gs.order_time
    FROM GuestServices gs
    JOIN Reservations r ON gs.reservation_id = r.reservation_id
    JOIN Guests g ON r.guest_id = g.guest_id
    LEFT JOIN Rooms rm ON r.room_id = rm.room_id
    JOIN Services s ON gs.service_id = s.service_id
    ORDER BY gs.order_time DESC
";
$orders = $pdo->query($query)->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-secondary"><i class="fa-solid fa-bell-concierge me-2"></i>Guest Services</h3>
    <a href="service_add.php" class="btn btn-primary fw-bold">
        <i class="fa-solid fa-plus me-1"></i> Add Service Order
    </a>
</div>

<div class="card shadow-sm border-0 border-top border-primary border-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-4">Date & Time</th>
                        <th>Guest</th>
                        <th>Room</th>
                        <th>Category</th>
                        <th>Service / Description</th>
                        <th class="text-center">Qty</th>
                        <th>Total Price</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($orders) > 0): ?>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td class="ps-4 text-muted small"><?= date('d M Y, h:i A', strtotime($o['order_time'])); ?></td>
                                <td class="fw-semibold text-primary"><?= sanitize($o['guest_name']); ?></td>
                                <td><span class="badge bg-dark"><?= sanitize($o['room_number'] ?? 'N/A'); ?></span></td>
                                <td><span class="badge bg-secondary"><?= sanitize($o['category']); ?></span></td>
                                <td class="fw-semibold"><?= sanitize($o['service_name']); ?></td>
                                <td class="text-center fw-bold text-dark"><?= sanitize($o['quantity']); ?></td>
                                <td class="fw-bold text-success">$<?= number_format($o['total_price'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No services have been ordered yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>