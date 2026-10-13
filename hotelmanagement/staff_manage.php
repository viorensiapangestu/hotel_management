<?php
// staff_manage.php
require_once 'header.php';

// Ensure only authorized personnel (like Admins) can access this later, 
// but for now, we just fetch all users.
$stmt = $pdo->query("SELECT user_id, name, email, role, created_at FROM Users ORDER BY created_at DESC");
$staff_members = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-secondary"><i class="fa-solid fa-users-gear me-2"></i>Manage Staff</h3>
    <a href="staff_add.php" class="btn btn-primary fw-bold">
        <i class="fa-solid fa-plus me-1"></i> Add New Staff
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
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined Date</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($staff_members) > 0): ?>
                        <?php foreach ($staff_members as $staff): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-muted">#<?= $staff['user_id']; ?></td>
                                <td><?= sanitize($staff['name']); ?></td>
                                <td><?= sanitize($staff['email']); ?></td>
                                <td>
                                    <?php if ($staff['role'] === 'Admin'): ?>
                                        <span class="badge bg-danger">Admin</span>
                                    <?php else: ?>
                                        <span class="badge bg-info text-dark">Staff</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= format_date($staff['created_at']); ?></td>
                                <td class="text-end pe-4">
                                    <a href="staff_edit.php?id=<?= $staff['user_id']; ?>" class="btn btn-sm btn-outline-secondary">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                        <a href="staff_delete.php?id=<?= $staff['user_id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this staff member?');">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No staff members found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>