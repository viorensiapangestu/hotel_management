<?php
// room_types.php
require_once 'header.php';

// Fetch all room types from the database
$stmt = $pdo->query("SELECT * FROM RoomType ORDER BY price ASC");
$room_types = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-secondary"><i class="fa-solid fa-layer-group me-2"></i>Room Types</h3>
    <a href="room_type_add.php" class="btn btn-primary fw-bold">
        <i class="fa-solid fa-plus me-1"></i> Add Room Type
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Type Name</th>
                        <th>Price / Night</th>
                        <th>Capacity</th>
                        <th>Description</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($room_types) > 0): ?>
                        <?php foreach ($room_types as $type): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-muted">#<?= $type['room_type_id']; ?></td>
                                <td class="fw-semibold text-primary"><?= sanitize($type['room_type_name']); ?></td>
                                <td><?= format_currency($type['price']); ?></td>
                                <td><i class="fa-solid fa-user-group text-secondary me-1"></i> <?= $type['capacity']; ?> Persons</td>
                                <td><small class="text-muted"><?= sanitize(substr($type['description'], 0, 40)) . '...'; ?></small></td>
                                <td class="text-end pe-4">
                                    <a href="room_type_edit.php?id=<?= $type['room_type_id']; ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No room types configured yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>