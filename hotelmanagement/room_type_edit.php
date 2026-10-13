<?php
// room_type_edit.php
require_once 'header.php';

$error = '';
$success = '';

$type_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$type_id) {
    echo "<div class='alert alert-danger'>Invalid Room Type ID provided.</div>";
    require_once 'footer.php';
    exit;
}

// Fetch existing data
$stmt = $pdo->prepare("SELECT * FROM RoomType WHERE room_type_id = :id");
$stmt->execute(['id' => $type_id]);
$room_type = $stmt->fetch();

if (!$room_type) {
    echo "<div class='alert alert-danger'>Room type not found.</div>";
    require_once 'footer.php';
    exit;
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_type_name = sanitize($_POST['room_type_name']);
    $price = $_POST['price'];
    $capacity = (int)$_POST['capacity'];
    $description = sanitize($_POST['description']);

    if (empty($room_type_name) || empty($price) || empty($capacity)) {
        $error = 'Name, Price, and Capacity are required fields.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE RoomType SET room_type_name = :name, price = :price, capacity = :capacity, description = :desc WHERE room_type_id = :id");
            $stmt->execute([
                'name' => $room_type_name,
                'price' => $price,
                'capacity' => $capacity,
                'desc' => $description,
                'id' => $type_id
            ]);
            $success = 'Room type updated successfully!';
            
            // Update local array to reflect changes in form
            $room_type['room_type_name'] = $room_type_name;
            $room_type['price'] = $price;
            $room_type['capacity'] = $capacity;
            $room_type['description'] = $description;
            
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold text-secondary">Edit Room Type</h3>
            <a href="room_types.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
        </div>

        <div class="card shadow-sm">
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i><?= $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="room_type_edit.php?id=<?= $type_id; ?>">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="room_type_name" class="form-label fw-semibold">Room Type Name</label>
                            <input type="text" class="form-control" id="room_type_name" name="room_type_name" value="<?= sanitize($room_type['room_type_name']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="price" class="form-label fw-semibold">Price per Night</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?= $room_type['price']; ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="capacity" class="form-label fw-semibold">Max Capacity (Persons)</label>
                            <input type="number" class="form-control" id="capacity" name="capacity" value="<?= $room_type['capacity']; ?>" required min="1">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label fw-semibold">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?= sanitize($room_type['description']); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-success w-100 fw-bold"><i class="fa-solid fa-floppy-disk me-2"></i>Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>