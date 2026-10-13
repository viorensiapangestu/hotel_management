<?php
// invoices.php
require_once 'header.php';

// Fetch all invoices, joining with Reservations and Guests for human-readable data
$query = "
    SELECT 
        i.invoice_id, 
        i.issued_date, 
        i.total_amount, 
        i.status,
        r.reservation_id,
        g.name AS guest_name
    FROM Invoices i
    JOIN Reservations r ON i.reservation_id = r.reservation_id
    JOIN Guests g ON r.guest_id = g.guest_id
    ORDER BY i.issued_date DESC, i.invoice_id DESC
";
$stmt = $pdo->query($query);
$invoices = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3 class="fw-bold text-secondary"><i class="fa-solid fa-file-invoice-dollar me-2"></i>All Invoices</h3>
</div>

<div class="card shadow-sm border-top border-primary border-4">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
                <thead class="table-dark">
                    <tr>
                        <th class="ps-4">Invoice No.</th>
                        <th>Guest Name</th>
                        <th>Booking Ref</th>
                        <th>Issued Date</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($invoices) > 0): ?>
                        <?php foreach ($invoices as $inv): ?>
                            <tr>
                                <td class="ps-4 fw-bold text-muted">#INV-<?= str_pad($inv['invoice_id'], 5, '0', STR_PAD_LEFT); ?></td>
                                <td class="fw-semibold text-dark"><?= sanitize($inv['guest_name']); ?></td>
                                <td class="text-muted">RES-<?= str_pad($inv['reservation_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td><?= format_date($inv['issued_date']); ?></td>
                                <td class="fw-bold"><?= format_currency($inv['total_amount']); ?></td>
                                <td>
                                    <?php 
                                        $status = $inv['status'];
                                        $badgeClass = 'bg-secondary';
                                        
                                        if ($status === 'Unpaid') $badgeClass = 'bg-danger';
                                        elseif ($status === 'Paid') $badgeClass = 'bg-success';
                                        elseif ($status === 'Partial') $badgeClass = 'bg-warning text-dark';
                                    ?>
                                    <span class="badge <?= $badgeClass; ?> px-2 py-1 fs-6">
                                        <?= sanitize($status); ?>
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <a href="invoice_view.php?id=<?= $inv['invoice_id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fa-solid fa-eye me-1"></i> View Details
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">No invoices found in the system.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>