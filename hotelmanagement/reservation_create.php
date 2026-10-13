<?php
// reservation_create.php
require_once 'header.php';

$error = '';
$success = '';

// Fetch Guests for the dropdown
$guests = $pdo->query("SELECT guest_id, name FROM Guests ORDER BY name ASC")->fetchAll();

// Fetch Room Types for the dropdown (Including capacity)
$roomTypes = $pdo->query("SELECT room_type_id, room_type_name, price, capacity FROM RoomType ORDER BY price ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guest_id = (int)$_POST['guest_id'];
    $room_type_id = (int)$_POST['room_type_id'];
    $check_in_date = $_POST['check_in_date'];
    $check_out_date = $_POST['check_out_date'];
    $status = sanitize($_POST['status']);
    $total_amount = (float)$_POST['total_amount'];
    
    // Safely grab the array of companion names
    $companions = $_POST['companions'] ?? [];

    if (empty($guest_id) || empty($room_type_id) || empty($check_in_date) || empty($check_out_date) || empty($status)) {
        $error = 'Please fill in all required fields.';
    } elseif ($check_out_date <= $check_in_date) {
        $error = 'Check-out date must be after the check-in date.';
    } else {
        try {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Find an Available Room
            $stmtRoom = $pdo->prepare("
                SELECT rm.room_id 
                FROM Rooms rm
                JOIN RoomStatus rs ON rm.status_id = rs.status_id
                WHERE rm.room_type_id = :rt_id AND rs.status_name = 'Available'
                LIMIT 1
            ");
            $stmtRoom->execute(['rt_id' => $room_type_id]);
            $availableRoom = $stmtRoom->fetch();

            if (!$availableRoom) {
                $error = 'Sorry! There are no available rooms of this type right now.';
            } else {
                $room_id = $availableRoom['room_id'];
                $pdo->beginTransaction();

                // 1. Save the Reservation (Notice: companion_names column is completely gone!)
                $stmt = $pdo->prepare("INSERT INTO Reservations (guest_id, room_type_id, room_id, check_in_date, check_out_date, status, total_amount) VALUES (:guest_id, :room_type_id, :room_id, :check_in, :check_out, :status, :total)");
                $stmt->execute([
                    'guest_id' => $guest_id,
                    'room_type_id' => $room_type_id,
                    'room_id' => $room_id,
                    'check_in' => $check_in_date,
                    'check_out' => $check_out_date,
                    'status' => $status,
                    'total' => $total_amount
                ]);
                $reservation_id = $pdo->lastInsertId();

                // --- NEW 1NF FIX: Insert each companion into their own atomic row! ---
                if (!empty($companions)) {
                    $stmtCompanion = $pdo->prepare("INSERT INTO Companions (reservation_id, name) VALUES (?, ?)");
                    foreach ($companions as $comp_name) {
                        $clean_name = sanitize(trim($comp_name));
                        if (!empty($clean_name)) {
                            $stmtCompanion->execute([$reservation_id, $clean_name]);
                        }
                    }
                }

                // 2. Change the Room's status to Occupied
                $stmtStatus = $pdo->prepare("SELECT status_id FROM RoomStatus WHERE status_name = 'Occupied'");
                $stmtStatus->execute();
                if ($occupiedStatusId = $stmtStatus->fetchColumn()) {
                    $pdo->prepare("UPDATE Rooms SET status_id = :status_id WHERE room_id = :room_id")->execute(['status_id' => $occupiedStatusId, 'room_id' => $room_id]);
                }

                // 3. Create the Invoice
                $pdo->prepare("INSERT INTO Invoices (reservation_id, issued_date, total_amount, status) VALUES (:res_id, CURDATE(), :total, 'Unpaid')")->execute(['res_id' => $reservation_id, 'total' => $total_amount]);

                $pdo->commit();
                $success = 'Reservation created, room automatically assigned, and companions registered!';
            }
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold text-secondary">Create Reservation</h3>
            <a href="reservations.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><i class="fa-solid fa-circle-exclamation me-2"></i><?= $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><i class="fa-solid fa-circle-check me-2"></i><?= $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="reservation_create.php">
                    
                    <div class="mb-3">
                        <label for="guest_id" class="form-label fw-semibold">Primary Guest</label>
                        <select class="form-select" id="guest_id" name="guest_id" required>
                            <option value="" disabled selected>Choose the main guest...</option>
                            <?php foreach ($guests as $g): ?>
                                <option value="<?= $g['guest_id']; ?>"><?= sanitize($g['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="room_type_id" class="form-label fw-semibold">Room Type</label>
                        <select class="form-select" id="room_type_id" name="room_type_id" required>
                            <option value="" data-price="0" data-capacity="1" disabled selected>Select a room type...</option>
                            <?php foreach ($roomTypes as $rt): ?>
                                <option value="<?= $rt['room_type_id']; ?>" data-price="<?= $rt['price']; ?>" data-capacity="<?= isset($rt['capacity']) ? $rt['capacity'] : 2; ?>">
                                    <?= sanitize($rt['room_type_name']); ?> - $<?= number_format($rt['price'], 2); ?> / night
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3 bg-light p-3 rounded border" id="companion_group" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label fw-semibold mb-0">Companions (Optional)</label>
                            <button type="button" class="btn btn-sm btn-outline-primary fw-bold" id="add_companion_btn">
                                <i class="fa-solid fa-user-plus me-1"></i> Add Person
                            </button>
                        </div>
                        <div class="form-text mb-3" id="capacity_text">This room allows extra guests.</div>
                        <div id="companion_inputs_container"></div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="check_in_date" class="form-label fw-semibold">Check-in Date</label>
                            <input type="date" class="form-control" id="check_in_date" name="check_in_date" required>
                        </div>
                        <div class="col-md-6">
                            <label for="check_out_date" class="form-label fw-semibold">Check-out Date</label>
                            <input type="date" class="form-control" id="check_out_date" name="check_out_date" required>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="status" class="form-label fw-semibold">Initial Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Pending" selected>Pending</option>
                                <option value="Confirmed">Confirmed</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="total_amount" class="form-label fw-semibold">Total Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" class="form-control bg-light" id="total_amount" name="total_amount" required readonly placeholder="0.00">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 fw-bold py-2">
                        <i class="fa-solid fa-calendar-check me-2"></i>Create Booking & Assign Room
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkInInput = document.getElementById('check_in_date');
    const checkOutInput = document.getElementById('check_out_date');
    const roomTypeSelect = document.getElementById('room_type_id');
    const totalAmountInput = document.getElementById('total_amount');
    
    const companionGroup = document.getElementById('companion_group');
    const companionContainer = document.getElementById('companion_inputs_container');
    const addCompanionBtn = document.getElementById('add_companion_btn');
    const capacityText = document.getElementById('capacity_text');
    
    let maxCompanions = 0;
    let currentCompanions = 0;

    function handleRoomChange() {
        const selectedOption = roomTypeSelect.options[roomTypeSelect.selectedIndex];
        
        // 1. Calculate the price
        const pricePerNight = parseFloat(selectedOption.getAttribute('data-price')) || 0;
        const checkInDate = new Date(checkInInput.value);
        const checkOutDate = new Date(checkOutInput.value);

        if (!isNaN(checkInDate) && !isNaN(checkOutDate) && checkOutDate > checkInDate && pricePerNight > 0) {
            const diffTime = Math.abs(checkOutDate - checkInDate);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)); 
            const total = diffDays * pricePerNight;
            totalAmountInput.value = total.toFixed(2);
        } else {
            totalAmountInput.value = '0.00';
        }

        // 2. Check capacity and setup companion inputs
        const totalCapacity = parseInt(selectedOption.getAttribute('data-capacity')) || 1;
        maxCompanions = totalCapacity - 1; // Subtract 1 for the primary guest

        if (maxCompanions > 0) {
            companionGroup.style.display = 'block';
            capacityText.innerText = `You can add up to ${maxCompanions} companion(s) for this room type.`;
        } else {
            companionGroup.style.display = 'none';
            companionContainer.innerHTML = '';
            currentCompanions = 0;
        }
        
        updateAddButton();
    }

    function updateAddButton() {
        if (currentCompanions >= maxCompanions) {
            addCompanionBtn.style.display = 'none';
        } else {
            addCompanionBtn.style.display = 'block';
        }
    }

    addCompanionBtn.addEventListener('click', function() {
        if (currentCompanions < maxCompanions) {
            const inputDiv = document.createElement('div');
            inputDiv.className = 'input-group mb-2';
            inputDiv.innerHTML = `
                <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                <input type="text" class="form-control" name="companions[]" placeholder="Companion Full Name" required>
                <button type="button" class="btn btn-outline-danger remove-btn"><i class="fa-solid fa-times"></i></button>
            `;
            companionContainer.appendChild(inputDiv);
            currentCompanions++;
            updateAddButton();

            // Add delete logic for the newly created button
            inputDiv.querySelector('.remove-btn').addEventListener('click', function() {
                inputDiv.remove();
                currentCompanions--;
                updateAddButton();
            });
        }
    });

    checkInInput.addEventListener('change', handleRoomChange);
    checkOutInput.addEventListener('change', handleRoomChange);
    roomTypeSelect.addEventListener('change', handleRoomChange);
});
</script>

<?php require_once 'footer.php'; ?>