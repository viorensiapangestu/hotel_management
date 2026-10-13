<?php
// index.php
require_once 'header.php';

// --- FETCH DASHBOARD METRICS ---

// 1. Total Rooms
$totalRooms = $pdo->query("SELECT COUNT(*) FROM Rooms")->fetchColumn();

// 2. Today's Expected Check-ins (Pending/Confirmed for today)
$todayCheckIns = $pdo->query("SELECT COUNT(*) FROM Reservations WHERE check_in_date = CURDATE() AND status IN ('Pending', 'Confirmed')")->fetchColumn();

// 3. Today's Expected Check-outs (Currently checked in, leaving today)
$todayCheckOuts = $pdo->query("SELECT COUNT(*) FROM Reservations WHERE check_out_date = CURDATE() AND status = 'Checked-In'")->fetchColumn();

// 4. Currently In-House (Guests physically in the building right now)
$inHouse = $pdo->query("SELECT COUNT(*) FROM Stay WHERE check_out_time IS NULL")->fetchColumn();

// 5. Rooms Needing Cleaning (Dirty status)
$dirtyRooms = $pdo->query("
    SELECT COUNT(*) FROM Rooms r 
    JOIN RoomStatus s ON r.status_id = s.status_id 
    WHERE s.status_name LIKE '%Dirty%'
")->fetchColumn();

// 6. Recent 5 Bookings for the mini-table
$recentBookings = $pdo->query("
    SELECT r.reservation_id, g.name, rm.room_number, r.check_in_date, r.status 
    FROM Reservations r
    JOIN Guests g ON r.guest_id = g.guest_id
    LEFT JOIN Rooms rm ON r.room_id = rm.room_id
    ORDER BY r.reservation_id DESC LIMIT 5
")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold text-secondary mb-0">Dashboard Overview</h2>
        <p class="text-muted mb-0">Welcome back! Here is what is happening today.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="reservation_create.php" class="btn btn-primary fw-bold"><i class="fa-solid fa-plus me-1"></i> New Booking</a>
        <a href="service_add.php" class="btn btn-success fw-bold"><i class="fa-solid fa-cart-plus me-1"></i> Add Service</a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card bg-white h-100 shadow-sm border-0 border-bottom border-primary border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase mb-2 fw-bold">Arrivals Today</h6>
                        <h2 class="fw-bold text-primary mb-0"><?= $todayCheckIns; ?></h2>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary fs-3">
                        <i class="fa-solid fa-plane-arrival"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-white h-100 shadow-sm border-0 border-bottom border-warning border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase mb-2 fw-bold">Departures Today</h6>
                        <h2 class="fw-bold text-warning mb-0"><?= $todayCheckOuts; ?></h2>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning fs-3">
                        <i class="fa-solid fa-plane-departure"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-white h-100 shadow-sm border-0 border-bottom border-success border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase mb-2 fw-bold">Active Stays</h6>
                        <h2 class="fw-bold text-success mb-0"><?= $inHouse; ?></h2>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success fs-3">
                        <i class="fa-solid fa-bed"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card bg-white h-100 shadow-sm border-0 border-bottom border-danger border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted text-uppercase mb-2 fw-bold">Rooms to Clean</h6>
                        <h2 class="fw-bold text-danger mb-0"><?= $dirtyRooms; ?></h2>
                    </div>
                    <div class="bg-danger bg-opacity-10 p-3 rounded-circle text-danger fs-3">
                        <i class="fa-solid fa-broom"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4 h-100">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold text-secondary mb-0"><i class="fa-solid fa-bolt text-warning me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body d-flex flex-column gap-3">
                <a href="check_in_out.php" class="btn btn-outline-dark text-start p-3 fw-bold">
                    <i class="fa-solid fa-bell-concierge fs-5 me-3 text-secondary"></i> Go to Front Desk (Stays)
                </a>
                <a href="invoices.php" class="btn btn-outline-dark text-start p-3 fw-bold">
                    <i class="fa-solid fa-file-invoice-dollar fs-5 me-3 text-secondary"></i> View Billing & Invoices
                </a>
                <a href="rooms.php" class="btn btn-outline-dark text-start p-3 fw-bold">
                    <i class="fa-solid fa-door-open fs-5 me-3 text-secondary"></i> Manage Physical Rooms
                </a>
                
                <div class="mt-auto pt-3 border-top">
                    <div class="d-flex justify-content-between align-items-center text-muted">
                        <span class="fw-semibold">Total Hotel Capacity:</span>
                        <span class="badge bg-secondary fs-6"><?= $totalRooms; ?> Rooms</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold text-secondary mb-0"><i class="fa-solid fa-clock-rotate-left text-info me-2"></i>Recent Bookings</h5>
                <a href="reservations.php" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Booking Ref</th>
                                <th>Guest</th>
                                <th>Arrival Date</th>
                                <th>Room</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($recentBookings) > 0): ?>
                                <?php foreach ($recentBookings as $res): 
                                    // Set badge colors dynamically based on status
                                    $badge = 'bg-secondary';
                                    if ($res['status'] === 'Confirmed') $badge = 'bg-primary';
                                    if ($res['status'] === 'Checked-In') $badge = 'bg-success';
                                    if ($res['status'] === 'Checked-Out') $badge = 'bg-dark';
                                    if ($res['status'] === 'Cancelled') $badge = 'bg-danger';
                                ?>
                                <tr>
                                    <td class="ps-4 text-muted fw-bold">#RES-<?= str_pad($res['reservation_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                    <td class="fw-semibold text-dark"><?= sanitize($res['name']); ?></td>
                                    <td><?= format_date($res['check_in_date']); ?></td>
                                    <td><span class="badge bg-secondary"><?= sanitize($res['room_number'] ?? 'N/A'); ?></span></td>
                                    <td><span class="badge <?= $badge; ?>"><?= sanitize($res['status']); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="text-center py-4 text-muted">No reservations found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>