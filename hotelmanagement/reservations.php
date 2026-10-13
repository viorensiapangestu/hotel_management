<?php
// reservations.php
require_once 'header.php';

// Fetch reservations, utilizing GROUP_CONCAT to pull the normalized companions back into a readable list!
$query = "
    SELECT 
        r.reservation_id, 
        g.name AS guest_name, 
        GROUP_CONCAT(c.name SEPARATOR ', ') AS companion_names,
        rm.room_number,
        r.check_in_date, 
        r.check_out_date, 
        r.status, 
        r.total_amount 
    FROM Reservations r
    JOIN Guests g ON r.guest_id = g.guest_id
    LEFT JOIN Rooms rm ON r.room_id = rm.room_id
    LEFT JOIN Companions c ON r.reservation_id = c.reservation_id
    GROUP BY 
        r.reservation_id, 
        g.name, 
        rm.room_number, 
        r.check_in_date, 
        r.check_out_date, 
        r.status, 
        r.total_amount
    ORDER BY r.check_in_date DESC, r.reservation_id DESC
";
$stmt = $pdo->query($query);
$reservations = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-secondary"><i class="fa-solid fa-calendar-days me-2"></i>All Reservations</h3>
    <a href="reservation_create.php" class="btn btn-primary fw-bold">
        <i class="fa-solid fa-plus me-1"></i> New Booking
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-4">Booking ID</th>
                        <th>Guest Name</th>
                        <th>Companion(s)</th>
                        <th>Room</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($reservations) > 0): ?>
                        <?php foreach ($reservations as $res): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-muted">#RES-<?= str_pad($res['reservation_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td class="fw-semibold text-dark"><?= sanitize($res['guest_name']); ?></td>
                                
                                <td class="text-muted">
                                    <?= !empty($res['companion_names']) ? sanitize($res['companion_names']) : '<span class="fst-italic">-</span>'; ?>
                                </td>

                                <td class="fw-bold text-primary">
                                    <?= !empty($res['room_number']) ? sanitize($res['room_number']) : '<span class="text-muted fst-italic">Unassigned</span>'; ?>
                                </td>
                                
                                <td><?= format_date($res['check_in_date']); ?></td>
                                <td><?= format_date($res['check_out_date']); ?></td>
                                <td><?= format_currency($res['total_amount']); ?></td>
                                <td>
                                    <?php 
                                        $status = $res['status'];
                                        $badgeClass = 'bg-secondary';
                                        
                                        if ($status === 'Pending') $badgeClass = 'bg-warning text-dark';
                                        elseif ($status === 'Confirmed') $badgeClass = 'bg-primary';
                                        elseif ($status === 'Checked-In') $badgeClass = 'bg-success';
                                        elseif ($status === 'Checked-Out') $badgeClass = 'bg-info text-dark';
                                        elseif ($status === 'Cancelled') $badgeClass = 'bg-danger';
                                    ?>
                                    <span class="badge <?= $badgeClass; ?> px-2 py-1 fs-6">
                                        <?= sanitize($status); ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group" role="group">
                                        <a href="reservation_view.php?id=<?= $res['reservation_id']; ?>" class="btn btn-sm btn-outline-primary" title="View Details">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <a href="reservation_edit.php?id=<?= $res['reservation_id']; ?>" class="btn btn-sm btn-outline-secondary" title="Edit Booking">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                        <?php if ($status === 'Pending' || $status === 'Confirmed'): ?>
                                            <a href="reservation_cancel.php?id=<?= $res['reservation_id']; ?>" class="btn btn-sm btn-outline-danger" title="Cancel Booking" onclick="return confirm('Are you sure you want to cancel this reservation?');">
                                                <i class="fa-solid fa-ban"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">No reservations found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>