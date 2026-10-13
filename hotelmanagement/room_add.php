<?php
// room_add.php
require_once 'header.php';

$error = '';
$success = '';

// Fetch Room Types for the dropdown
$stmtTypes = $pdo->query("SELECT room_type_id, room_type_name FROM RoomType ORDER BY room_type_name ASC");
$room_types = $stmtTypes->fetchAll();

// Fetch Room Statuses for the dropdown
$stmtStatuses = $pdo->query("SELECT status_id, status_name FROM RoomStatus ORDER BY status_name ASC");
$room_statuses = $stmtStatuses->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_number = sanitize($_POST['room_number']);
    $room_type_id = (int)$_POST['room_type_id'];
    $status_id = (int)$_POST['status_id'];

    if (empty($room_number) || empty($room_type_id) || empty($status_id)) {
        $error = 'All fields are required.';
    } else {
        // Check if room number already exists
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM Rooms WHERE room_number = :room_number");
        $stmtCheck->execute(['room_number' => $room_number]);
        if ($stmtCheck->fetchColumn() > 0) {
            $error = 'Room number ' . $room_number . ' already exists in the system.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO Rooms (room_number, room_type_id, status_id) VALUES (:room_number, :type_id, :status_id)");
                $stmt->execute([
                    'room_number' => $room_number,
                    'type_id' => $room_type_id,
                    'status_id' => $status_id
                ]);
                $success = 'Room successfully added!';
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold text-secondary">Add New Room</h3>
            <a href="rooms.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i><?= $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="room_add.php">
                    <div class="mb-3">
                        <label for="room_number" class="form-label fw-semibold">Room Number</label>
                        <input type="text" class="form-control" id="room_number" name="room_number" required placeholder="e.g. 101, 102B, Penthouse A">
                    </div>
                    
                    <div class="mb-3">
                        <label for="room_type_id" class="form-label fw-semibold">Room Type</label>
                        <select class="form-select" id="room_type_id" name="room_type_id" required>
                            <option value="" disabled selected>Select a room type...</option>
                            <?php foreach ($room_types as $type): ?>
                                <option value="<?= $type['room_type_id']; ?>"><?= sanitize($type['room_type_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="status_id" class="form-label fw-semibold">Initial Status</label>
                        <select class="form-select" id="status_id" name="status_id" required>
                            <option value="" disabled selected>Select a status...</option>
                            <?php foreach ($room_statuses as $status): ?>
                                <option value="<?= $status['status_id']; ?>"><?= sanitize($status['status_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100 fw-bold"><i class="fa-solid fa-door-open me-2"></i>Save Room</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>