<?php
// payment_process.php
require_once 'header.php';

$error = '';
$success = '';

// Check if we arrived here from a specific invoice
$preselected_invoice = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0;

// Fetch all unpaid or partially paid invoices for the dropdown
$queryInvoices = "
    SELECT 
        i.invoice_id, 
        i.total_amount, 
        g.name AS guest_name
    FROM Invoices i
    JOIN Reservations r ON i.reservation_id = r.reservation_id
    JOIN Guests g ON r.guest_id = g.guest_id
    WHERE i.status IN ('Unpaid', 'Partial')
    ORDER BY i.invoice_id ASC
";
$pending_invoices = $pdo->query($queryInvoices)->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $invoice_id = (int)$_POST['invoice_id'];
    $amount = $_POST['amount'];
    $payment_method = sanitize($_POST['payment_method']);

    if (empty($invoice_id) || empty($amount) || empty($payment_method)) {
        $error = 'All fields are required.';
    } elseif (!is_numeric($amount) || $amount <= 0) {
        $error = 'Please enter a valid payment amount greater than zero.';
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Insert the new payment record
            $stmtInsert = $pdo->prepare("INSERT INTO Payments (invoice_id, payment_date, amount, payment_method) VALUES (:inv_id, NOW(), :amount, :method)");
            $stmtInsert->execute([
                'inv_id' => $invoice_id,
                'amount' => $amount,
                'method' => $payment_method
            ]);

            // 2. Fetch the invoice's required total amount
            $stmtInvTotal = $pdo->prepare("SELECT total_amount FROM Invoices WHERE invoice_id = :id");
            $stmtInvTotal->execute(['id' => $invoice_id]);
            $invoice_total = $stmtInvTotal->fetchColumn();

            // 3. Calculate the total of ALL payments made towards this invoice so far
            $stmtPayTotal = $pdo->prepare("SELECT SUM(amount) FROM Payments WHERE invoice_id = :id");
            $stmtPayTotal->execute(['id' => $invoice_id]);
            $total_paid = $stmtPayTotal->fetchColumn();

            // 4. Determine the new status
            $new_status = 'Partial';
            if ($total_paid >= $invoice_total) {
                $new_status = 'Paid';
            }

            // 5. Update the invoice status
            $stmtUpdateInv = $pdo->prepare("UPDATE Invoices SET status = :status WHERE invoice_id = :id");
            $stmtUpdateInv->execute([
                'status' => $new_status,
                'id' => $invoice_id
            ]);

            $pdo->commit();

            $success = "Payment of $" . number_format($amount, 2) . " processed successfully! Invoice status is now: " . $new_status . ".";

            // Remove the paid invoice from the local array so it disappears from the dropdown if fully paid
            if ($new_status === 'Paid') {
                foreach ($pending_invoices as $key => $inv) {
                    if ($inv['invoice_id'] == $invoice_id) {
                        unset($pending_invoices[$key]);
                    }
                }
            }

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = 'Transaction failed: ' . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold text-secondary">Process Payment</h3>
            <a href="invoices.php" class="btn btn-outline-secondary"><i class="fa-solid fa-arrow-left me-1"></i> Back to Invoices</a>
        </div>

        <div class="card shadow-sm border-top border-success border-4">
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= $error; ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fa-solid fa-circle-check me-2"></i><?= $success; ?>
                        <br><a href="invoice_view.php?id=<?= $invoice_id; ?>" class="alert-link mt-2 d-inline-block">View Updated Invoice</a>
                    </div>
                <?php endif; ?>

                <form method="POST" action="payment_process.php">
                    <div class="mb-4">
                        <label for="invoice_id" class="form-label fw-semibold">Select Invoice</label>
                        <select class="form-select" id="invoice_id" name="invoice_id" required>
                            <option value="" disabled <?= !$preselected_invoice ? 'selected' : ''; ?>>Choose a pending invoice...</option>
                            <?php foreach ($pending_invoices as $inv): ?>
                                <option value="<?= $inv['invoice_id']; ?>" <?= ($preselected_invoice == $inv['invoice_id']) ? 'selected' : ''; ?>>
                                    INV-<?= str_pad($inv['invoice_id'], 5, '0', STR_PAD_LEFT); ?> - <?= sanitize($inv['guest_name']); ?> (Total: $<?= number_format($inv['total_amount'], 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($pending_invoices)): ?>
                            <div class="form-text text-success fw-bold mt-2"><i class="fa-solid fa-check-double me-1"></i> All invoices are currently paid in full!</div>
                        <?php endif; ?>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="amount" class="form-label fw-semibold">Payment Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" step="0.01" class="form-control" id="amount" name="amount" required placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="payment_method" class="form-label fw-semibold">Payment Method</label>
                            <select class="form-select" id="payment_method" name="payment_method" required>
                                <option value="Cash">Cash</option>
                                <option value="Credit Card">Credit Card</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success w-100 fw-bold py-2" <?= empty($pending_invoices) ? 'disabled' : ''; ?>>
                        <i class="fa-solid fa-cash-register me-2"></i>Apply Payment
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>