<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];
$success = $error = '';

// Get vendors for dropdown
$vendors = $conn->query("SELECT id, business_name FROM vendors ORDER BY business_name ASC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vendor_id    = intval($_POST['vendor_id']);
    $cert_number  = trim($_POST['cert_number']);
    $issuing_body = trim($_POST['issuing_body']);
    $issue_date   = $_POST['issue_date'];
    $expiry_date  = $_POST['expiry_date'];
    $status       = $_POST['status'];
    $remarks      = trim($_POST['remarks']);

    if (empty($vendor_id) || empty($cert_number) || empty($issue_date) || empty($expiry_date)) {
        $error = "Vendor, certificate number, and dates are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO certifications (vendor_id, cert_number, issuing_body, issue_date, expiry_date, status, remarks) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("issssss", $vendor_id, $cert_number, $issuing_body, $issue_date, $expiry_date, $status, $remarks);
        if ($stmt->execute()) {
            // Auto-generate QR token
            $cert_id = $conn->insert_id;
            $qr_token = bin2hex(random_bytes(16));
            $conn->query("INSERT INTO qr_codes (cert_id, qr_token) VALUES ($cert_id, '$qr_token')");
            $success = "Certification added! QR Token: <strong>$qr_token</strong>";
        } else {
            $error = "Error: " . $conn->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Certification - Halal Hub</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; display: flex; min-height: 100vh; background: #f0f2f5; }
        .sidebar { width: 250px; background: linear-gradient(180deg, #2e7d32, #1b5e20); color: white; display: flex; flex-direction: column; position: fixed; height: 100vh; top: 0; left: 0; }
        .sidebar-brand { padding: 24px 20px; background: rgba(0,0,0,0.15); text-align: center; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .sidebar-brand h2 { font-size: 15px; font-weight: 700; color: #fff; line-height: 1.4; }
        .sidebar-user { padding: 16px 20px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .avatar { width: 36px; height: 36px; background: #81c784; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 15px; color: #1b5e20; }
        .sidebar-user span { font-size: 13px; color: rgba(255,255,255,0.85); }
        .sidebar-nav { flex: 1; padding: 12px 0; }
        .nav-label { font-size: 10px; text-transform: uppercase; letter-spacing: 1.2px; color: rgba(255,255,255,0.4); padding: 12px 20px 4px; }
        .sidebar-nav a { display: flex; align-items: center; gap: 10px; padding: 11px 20px; color: rgba(255,255,255,0.8); text-decoration: none; font-size: 13.5px; }
        .sidebar-nav a:hover, .sidebar-nav a.active { background: rgba(255,255,255,0.12); color: #fff; }
        .sidebar-footer { padding: 16px 20px; border-top: 1px solid rgba(255,255,255,0.1); }
        .sidebar-footer a { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 13px; }

        .main { margin-left: 250px; padding: 30px; flex: 1; }
        .page-header { margin-bottom: 24px; }
        .page-header h1 { font-size: 22px; color: #1b5e20; font-weight: 700; }
        .page-header p { color: #666; font-size: 13.5px; margin-top: 4px; }

        .form-card { background: white; border-radius: 12px; padding: 28px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); max-width: 600px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-size: 13px; font-weight: 600; color: #444; margin-bottom: 6px; }
        input, select, textarea { width: 100%; padding: 10px 14px; border: 1.5px solid #ddd; border-radius: 8px; font-size: 13.5px; color: #333; outline: none; transition: border 0.2s; }
        input:focus, select:focus, textarea:focus { border-color: #2e7d32; }
        textarea { resize: vertical; min-height: 80px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .btn { padding: 11px 28px; background: linear-gradient(135deg, #2e7d32, #1b5e20); color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
        .alert { padding: 12px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 18px; }
        .alert.success { background: #e8f5e9; color: #2e7d32; }
        .alert.error { background: #fce4ec; color: #c62828; }
    </style>
</head>
<body>
<div class="sidebar">
    <div class="sidebar-brand"><h2>🕌 Halal Certification &amp; Verification Hub</h2></div>
    <div class="sidebar-user">
        <div class="avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
        <span><?php echo htmlspecialchars($username); ?></span>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-label">Main</div>
        <a href="homepage.php">📊 Dashboard</a>
        <div class="nav-label">Vendors</div>
        <a href="add_vendor.php">➕ Add Vendor</a>
        <a href="view_vendors.php">🏪 View Vendors</a>
        <div class="nav-label">Certifications</div>
        <a href="add_cert.php" class="active">📄 Add Certification</a>
        <a href="view_certs.php">📋 View Certifications</a>
        <div class="nav-label">Verification</div>
        <a href="verify.php">🔍 Verify QR / Cert</a>
    </nav>
    <div class="sidebar-footer"><a href="logout.php">🚪 Logout</a></div>
</div>

<div class="main">
    <div class="page-header">
        <h1>Add Certification</h1>
        <p>Issue a new halal certification for a registered vendor.</p>
    </div>

    <div class="form-card">
        <?php if ($success): ?><div class="alert success">✅ <?php echo $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert error">❌ <?php echo $error; ?></div><?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Vendor / Business *</label>
                <select name="vendor_id" required>
                    <option value="">-- Select Vendor --</option>
                    <?php if ($vendors && $vendors->num_rows > 0): ?>
                        <?php while ($v = $vendors->fetch_assoc()): ?>
                        <option value="<?php echo $v['id']; ?>"><?php echo htmlspecialchars($v['business_name']); ?></option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Certificate Number *</label>
                <input type="text" name="cert_number" placeholder="e.g. HALAL-2024-00123" required>
            </div>
            <div class="form-group">
                <label>Issuing Body</label>
                <input type="text" name="issuing_body" placeholder="e.g. IDCP, ICRC, BARMM-HCAC">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Issue Date *</label>
                    <input type="date" name="issue_date" required>
                </div>
                <div class="form-group">
                    <label>Expiry Date *</label>
                    <input type="date" name="expiry_date" required>
                </div>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="Active">Active</option>
                    <option value="Expired">Expired</option>
                    <option value="Revoked">Revoked</option>
                </select>
            </div>
            <div class="form-group">
                <label>Remarks</label>
                <textarea name="remarks" placeholder="Optional notes..."></textarea>
            </div>
            <button type="submit" class="btn">Issue Certification</button>
        </form>
    </div>
</div>
</body>
</html>