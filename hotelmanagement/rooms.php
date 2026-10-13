<?php
// rooms.php
require_once 'header.php';

// Fetch all physical rooms along with their type, capacity, amenities, price, and status
$query = "
    SELECT 
        rm.room_id, 
        rm.room_number, 
        rt.room_type_name, 
        rt.capacity,
        rt.amenities,
        rt.price, 
        rs.status_name 
    FROM Rooms rm
    JOIN RoomType rt ON rm.room_type_id = rt.room_type_id
    JOIN RoomStatus rs ON rm.status_id = rs.status_id
    ORDER BY rm.room_number ASC
";
$stmt = $pdo->query($query);
$rooms = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-secondary"><i class="fa-solid fa-door-open me-2"></i>Physical Rooms</h3>
    <a href="room_add.php" class="btn btn-primary fw-bold">
        <i class="fa-solid fa-plus me-1"></i> Add Room
    </a>
</div>

<div class="card shadow-sm border-top border-primary border-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-4">Room No.</th>
                        <th>Room Type</th>
                        <th>Capacity</th>
                        <th style="max-width: 250px;">Amenities</th>
                        <th>Price/Night</th>
                        <th>Current Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($rooms) > 0): ?>
                        <?php foreach ($rooms as $room): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-dark fs-5"><?= sanitize($room['room_number']); ?></td>
                                <td class="fw-semibold text-primary"><?= sanitize($room['room_type_name']); ?></td>
                                <td>
                                    <i class="fa-solid fa-user-group me-1 text-muted"></i> <?= $room['capacity']; ?>
                                </td>
                                <td style="max-width: 250px; white-space: normal;">
                                    <small class="text-muted"><?= sanitize($room['amenities'] ?? 'None specified'); ?></small>
                                </td>
                                <td class="fw-bold text-success">$<?= number_format($room['price'], 2); ?></td>
                                <td>
                                    <?php 
                                        $status = $room['status_name'];
                                        $badgeClass = 'bg-secondary';
                                        
                                        if ($status === 'Available') $badgeClass = 'bg-success';
                                        elseif ($status === 'Occupied') $badgeClass = 'bg-danger';
                                        elseif ($status === 'Dirty') $badgeClass = 'bg-warning text-dark';
                                        elseif ($status === 'Maintenance') $badgeClass = 'bg-dark';
                                    ?>
                                    <span class="badge <?= $badgeClass; ?> px-2 py-1 fs-6">
                                        <?= sanitize($status); ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="room_edit.php?id=<?= $room['room_id']; ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No rooms have been added yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>