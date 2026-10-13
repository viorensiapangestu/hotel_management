<?php
// invoice_view.php
require_once 'header.php';

$invoice_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$invoice_id) {
    echo "<div class='alert alert-danger'>Invalid Invoice ID provided.</div>";
    require_once 'footer.php';
    exit;
}

// 1. Fetch Core Invoice, Reservation, and Guest Details
$queryInvoice = "
    SELECT 
        i.invoice_id, i.issued_date, i.total_amount, i.status, 
        r.reservation_id, r.check_in_date, r.check_out_date, r.total_amount AS room_charge,
        g.name, g. email, g.phone
    FROM Invoices i
    JOIN Reservations r ON i.reservation_id = r.reservation_id
    JOIN Guests g ON r.guest_id = g.guest_id
    WHERE i.invoice_id = :id
";
$stmtInv = $pdo->prepare($queryInvoice);
$stmtInv->execute(['id' => $invoice_id]);
$invoice = $stmtInv->fetch();

if (!$invoice) {
    echo "<div class='alert alert-danger'>Invoice not found.</div>";
    require_once 'footer.php';
    exit;
}

// 2. Fetch Extra Services tied to this reservation (FIXED ALL order_time references!)
$queryServices = "
    SELECT 
        sv.service_name, gs.quantity, gs.total_price, gs.order_time
    FROM GuestServices gs
    JOIN Services sv ON gs.service_id = sv.service_id
    WHERE gs.reservation_id = :res_id
    ORDER BY gs.order_time ASC
";
$stmtServ = $pdo->prepare($queryServices);
$stmtServ->execute(['res_id' => $invoice['reservation_id']]);
$extra_services = $stmtServ->fetchAll();

// 3. Fetch Payments already made against this invoice
$queryPayments = "SELECT payment_date, amount, payment_method FROM Payments WHERE invoice_id = :inv_id ORDER BY payment_date ASC";
$stmtPay = $pdo->prepare($queryPayments);
$stmtPay->execute(['inv_id' => $invoice_id]);
$payments = $stmtPay->fetchAll();

// Calculate totals
$total_paid = 0;
foreach ($payments as $pay) {
    $total_paid += $pay['amount'];
}
$balance_due = max(0, $invoice['total_amount'] - $total_paid);

// Determine badge color
$status = $invoice['status'];
$badgeClass = ($status === 'Paid') ? 'bg-success' : (($status === 'Partial') ? 'bg-warning text-dark' : 'bg-danger');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-secondary">
        Invoice #INV-<?= str_pad($invoice['invoice_id'], 5, '0', STR_PAD_LEFT); ?>
    </h3>
    <div>
        <?php if ($balance_due > 0): ?>
            <a href="payment_process.php?invoice_id=<?= $invoice['invoice_id']; ?>" class="btn btn-success me-2 fw-bold">
                <i class="fa-solid fa-credit-card me-1"></i> Add Payment
            </a>
        <?php endif; ?>
        <button onclick="window.print()" class="btn btn-outline-dark me-2"><i class="fa-solid fa-print me-1"></i> Print</button>
        <a href="invoices.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back</a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card shadow-sm mb-4">
            <div class="card-body p-5">
                
                <div class="row mb-5 border-bottom pb-4">
                    <div class="col-sm-6">
                        <h2 class="fw-bold text-primary mb-1">HOTEL MANAGEMENT</h2>
                        <p class="text-muted mb-0">123 Hospitality Blvd<br>City, State 12345</p>
                    </div>
                    <div class="col-sm-6 text-sm-end">
                        <h4 class="fw-bold text-muted">INVOICE</h4>
                        <p class="mb-0"><strong>Date:</strong> <?= format_date($invoice['issued_date']); ?></p>
                        <p class="mb-0"><strong>Status:</strong> <span class="badge <?= $badgeClass; ?>"><?= sanitize($status); ?></span></p>
                    </div>
                </div>

                <div class="row mb-5">
                    <div class="col-sm-6">
                        <h6 class="fw-bold text-muted mb-2">BILLED TO:</h6>
                        <p class="mb-1 fw-semibold fs-5"><?= sanitize($invoice['name']); ?></p>
                        <p class="mb-0 text-muted"><?= $invoice['email'] ? sanitize($invoice['email']) : 'No email provided'; ?></p>
                        <p class="mb-0 text-muted"><?= $invoice['phone'] ? sanitize($invoice['phone']) : 'No phone provided'; ?></p>
                    </div>
                    <div class="col-sm-6 text-sm-end">
                        <h6 class="fw-bold text-muted mb-2">STAY DETAILS:</h6>
                        <p class="mb-1"><strong>Ref:</strong> RES-<?= str_pad($invoice['reservation_id'], 4, '0', STR_PAD_LEFT); ?></p>
                        <p class="mb-0"><strong>Check-In:</strong> <?= format_date($invoice['check_in_date']); ?></p>
                        <p class="mb-0"><strong>Check-Out:</strong> <?= format_date($invoice['check_out_date']); ?></p>
                    </div>
                </div>

                <h6 class="fw-bold text-muted mb-3">ITEMIZED CHARGES</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>Description</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Room Accommodation (Base Rate)</td>
                                <td class="text-center">1</td>
                                <td class="text-end"><?= format_currency($invoice['room_charge']); ?></td>
                            </tr>
                            
                            <?php if (count($extra_services) > 0): ?>
                                <?php foreach ($extra_services as $srv): ?>
                                    <tr>
                                        <td>
                                            <?= sanitize($srv['service_name']); ?> 
                                            <br><small class="text-muted"><?= format_date($srv['order_time']); ?></small>
                                        </td>
                                        <td class="text-center"><?= $srv['quantity']; ?></td>
                                        <td class="text-end"><?= format_currency($srv['total_price']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="2" class="text-end">Grand Total:</th>
                                <th class="text-end fs-5 text-dark"><?= format_currency($invoice['total_amount']); ?></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <h6 class="fw-bold text-muted mb-3">PAYMENT HISTORY</h6>
                        <?php if (count($payments) > 0): ?>
                            <table class="table table-sm text-muted">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Method</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $pay): ?>
                                        <tr>
                                            <td><?= format_date($pay['payment_date']); ?></td>
                                            <td><?= sanitize($pay['payment_method']); ?></td>
                                            <td class="text-end"><?= format_currency($pay['amount']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-muted fst-italic">No payments recorded yet.</p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="bg-light p-3 rounded">
                            <p class="mb-1 text-muted">Total Paid: <span class="fw-semibold text-success ms-2"><?= format_currency($total_paid); ?></span></p>
                            <h4 class="mb-0 mt-2 fw-bold text-<?= $balance_due > 0 ? 'danger' : 'success'; ?>">
                                Balance Due: <?= format_currency($balance_due); ?>
                            </h4>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>