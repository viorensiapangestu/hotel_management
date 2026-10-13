<?php
// room_edit.php
require_once 'header.php';

$error = '';
$success = '';

$room_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$room_id) {
    echo "<div class='alert alert-danger'>Invalid Room ID provided.</div>";
    require_once 'footer.php';
    exit;
}

// Fetch the existing room data
$stmtRoom = $pdo->prepare("SELECT * FROM Rooms WHERE room_id = :id");
$stmtRoom->execute(['id' => $room_id]);
$room = $stmtRoom->fetch();

if (!$room) {
    echo "<div class='alert alert-danger'>Room not found.</div>";
    require_once 'footer.php';
    exit;
}

// Fetch Room Types and Statuses for the dropdowns
$room_types = $pdo->query("SELECT room_type_id, room_type_name FROM RoomType ORDER BY room_type_name ASC")->fetchAll();
$room_statuses = $pdo->query("SELECT status_id, status_name FROM RoomStatus ORDER BY status_name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_number = sanitize($_POST['room_number']);
    $room_type_id = (int)$_POST['room_type_id'];
    $status_id = (int)$_POST['status_id'];

    if (empty($room_number) || empty($room_type_id) || empty($status_id)) {
        $error = 'All fields are required.';
    } else {
        // Check if the new room number is already taken by a DIFFERENT room
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM Rooms WHERE room_number = :room_number AND room_id != :id");
        $stmtCheck->execute(['room_number' => $room_number, 'id' => $room_id]);
        
        if ($stmtCheck->fetchColumn() > 0) {
            $error = 'Room number ' . $room_number . ' is already assigned to another room.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE Rooms SET room_number = :room_number, room_type_id = :type_id, status_id = :status_id WHERE room_id = :id");
                $stmt->execute([
                    'room_number' => $room_number,
                    'type_id' => $room_type_id,
                    'status_id' => $status_id,
                    'id' => $room_id
                ]);
                $success = 'Room updated successfully!';
                
                // Update local array so the form shows the latest data
                $room['room_number'] = $room_number;
                $room['room_type_id'] = $room_type_id;
                $room['status_id'] = $status_id;
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
            <h3 class="fw-bold text-secondary">Edit Room</h3>
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

                <form method="POST" action="room_edit.php?id=<?= $room_id; ?>">
                    <div class="mb-3">
                        <label for="room_number" class="form-label fw-semibold">Room Number</label>
                        <input type="text" class="form-control" id="room_number" name="room_number" value="<?= sanitize($room['room_number']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="room_type_id" class="form-label fw-semibold">Room Type</label>
                        <select class="form-select" id="room_type_id" name="room_type_id" required>
                            <?php foreach ($room_types as $type): ?>
                                <option value="<?= $type['room_type_id']; ?>" <?= $room['room_type_id'] == $type['room_type_id'] ? 'selected' : ''; ?>>
                                    <?= sanitize($type['room_type_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="status_id" class="form-label fw-semibold">Current Status</label>
                        <select class="form-select" id="status_id" name="status_id" required>
                            <?php foreach ($room_statuses as $status): ?>
                                <option value="<?= $status['status_id']; ?>" <?= $room['status_id'] == $status['status_id'] ? 'selected' : ''; ?>>
                                    <?= sanitize($status['status_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100 fw-bold"><i class="fa-solid fa-floppy-disk me-2"></i>Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>