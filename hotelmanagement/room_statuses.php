<?php
// room_statuses.php
require_once 'header.php';

$error = '';
$success = '';

// Handle adding a new status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status_name = sanitize($_POST['status_name']);

    if (empty($status_name)) {
        $error = 'Status name is required.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO RoomStatus (status_name) VALUES (:name)");
            $stmt->execute(['name' => $status_name]);
            $success = 'Room status added successfully!';
        } catch (PDOException $e) {
            // Error 23000 is a constraint violation (like our UNIQUE constraint on status_name)
            if ($e->getCode() == 23000) {
                $error = 'This status name already exists.';
            } else {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Fetch all statuses
$stmt = $pdo->query("SELECT * FROM RoomStatus ORDER BY status_name ASC");
$statuses = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-secondary"><i class="fa-solid fa-clipboard-check me-2"></i>Room Statuses</h3>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card shadow-sm border-top border-primary border-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3">Add New Status</h5>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger py-2"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success py-2"><i class="fa-solid fa-circle-check me-2"></i><?= $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="room_statuses.php">
                    <div class="mb-3">
                        <label for="status_name" class="form-label fw-semibold">Status Name</label>
                        <input type="text" class="form-control" id="status_name" name="status_name" required placeholder="e.g. Under Maintenance">
                    </div>
                    <button type="submit" class="btn btn-primary w-100 fw-bold">Add Status</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th class="ps-4" style="width: 20%;">ID</th>
                                <th>Status Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($statuses) > 0): ?>
                                <?php foreach ($statuses as $status): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-muted">#<?= $status['status_id']; ?></td>
                                        <td class="fw-semibold">
                                            <span class="badge bg-secondary px-3 py-2 fs-6">
                                                <?= sanitize($status['status_name']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="2" class="text-center py-4 text-muted">No room statuses configured yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>