<?php
// guests.php
require_once 'header.php';

// Fetch all guests
$stmt = $pdo->query("SELECT * FROM Guests ORDER BY created_at DESC");
$guests = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-secondary"><i class="fa-solid fa-address-book me-2"></i>Guest Directory</h3>
    <a href="guest_add.php" class="btn btn-primary fw-bold">
        <i class="fa-solid fa-plus me-1"></i> Add New Guest
    </a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-4">ID</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>ID/Passport No.</th>
                        <th>Email</th>
                        <th>Registered On</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($guests) > 0): ?>
                        <?php foreach ($guests as $guest): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-muted">#<?= $guest['guest_id']; ?></td>
                                <td class="fw-semibold text-dark"><?= sanitize($guest['name']); ?></td>
                                <td><?= $guest['phone'] ? sanitize($guest['phone']) : '<span class="text-muted fst-italic">N/A</span>'; ?></td>
                                <td><?= $guest['id_number'] ? sanitize($guest['id_number']) : '<span class="text-muted fst-italic">N/A</span>'; ?></td>
                                <td><?= $guest['email'] ? sanitize($guest['email']) : '<span class="text-muted fst-italic">N/A</span>'; ?></td>
                                <td><?= format_date($guest['created_at']); ?></td>
                                <td class="text-end pe-4">
                                    <a href="guest_edit.php?id=<?= $guest['guest_id']; ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No guests found in the system.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>