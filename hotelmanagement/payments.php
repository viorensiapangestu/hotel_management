<?php
// payments.php
require_once 'header.php';

// Fetch all payments and join with Invoices and Guests for context
$query = "
    SELECT 
        p.payment_id, 
        p.payment_date, 
        p.amount, 
        p.payment_method,
        i.invoice_id,
        g.name AS guest_name
    FROM Payments p
    JOIN Invoices i ON p.invoice_id = i.invoice_id
    JOIN Guests g ON i.guest_id = g.guest_id
    ORDER BY p.payment_date DESC, p.payment_id DESC
";
$stmt = $pdo->query($query);
$payments = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-secondary"><i class="fa-solid fa-money-bill-trend-up me-2"></i>Payment History</h3>
    <a href="payment_process.php" class="btn btn-success fw-bold">
        <i class="fa-solid fa-plus me-1"></i> Record Payment
    </a>
</div>

<div class="card shadow-sm border-top border-success border-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-4">Receipt ID</th>
                        <th>Date & Time</th>
                        <th>Guest Name</th>
                        <th>Invoice Ref</th>
                        <th>Payment Method</th>
                        <th class="text-end pe-4">Amount Paid</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($payments) > 0): ?>
                        <?php foreach ($payments as $pay): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-muted">#REC-<?= str_pad($pay['payment_id'], 6, '0', STR_PAD_LEFT); ?></td>
                                <td><?= date('M d, Y h:i A', strtotime($pay['payment_date'])); ?></td>
                                <td class="fw-semibold text-dark"><?= sanitize($pay['guest_name']); ?></td>
                                <td>
                                    <a href="invoice_view.php?id=<?= $pay['invoice_id']; ?>" class="text-decoration-none">
                                        INV-<?= str_pad($pay['invoice_id'], 5, '0', STR_PAD_LEFT); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><i class="fa-solid fa-wallet me-1"></i> <?= sanitize($pay['payment_method']); ?></span>
                                </td>
                                <td class="text-end pe-4 fw-bold text-success">
                                    +<?= format_currency($pay['amount']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">No payments have been recorded yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>