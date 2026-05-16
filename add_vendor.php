<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $business_name = trim($_POST['business_name']);
    $owner_name    = trim($_POST['owner_name']);
    $contact       = trim($_POST['contact_number']);
    $email         = trim($_POST['email']);
    $address       = trim($_POST['address']);
    $btype         = trim($_POST['business_type']);

    if (empty($business_name) || empty($owner_name)) {
        $error = "Business name and owner name are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO vendors (business_name, owner_name, contact_number, email, address, business_type) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("ssssss", $business_name, $owner_name, $contact, $email, $address, $btype);
        if ($stmt->execute()) {
            $success = "Vendor added successfully!";
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
    <title>Add Vendor - Halal Hub</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; display: flex; min-height: 100vh; background: #f0f2f5; }
        .sidebar {
            width: 250px; background: linear-gradient(180deg, #2e7d32, #1b5e20);
            color: white; display: flex; flex-direction: column;
            position: fixed; height: 100vh; top: 0; left: 0;
        }
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
        .sidebar-footer a { display: flex; align-items: center; gap: 8px; color: rgba(255,255,255,0.7); text-decoration: none; font-size: 13px; }

        .main { margin-left: 250px; padding: 30px; flex: 1; }
        .page-header { margin-bottom: 24px; }
        .page-header h1 { font-size: 22px; color: #1b5e20; font-weight: 700; }
        .page-header p { color: #666; font-size: 13.5px; margin-top: 4px; }

        .form-card { background: white; border-radius: 12px; padding: 28px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); max-width: 600px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; font-size: 13px; font-weight: 600; color: #444; margin-bottom: 6px; }
        input, select, textarea {
            width: 100%; padding: 10px 14px; border: 1.5px solid #ddd;
            border-radius: 8px; font-size: 13.5px; color: #333;
            transition: border 0.2s; outline: none;
        }
        input:focus, select:focus, textarea:focus { border-color: #2e7d32; }
        textarea { resize: vertical; min-height: 80px; }
        .btn { padding: 11px 28px; background: linear-gradient(135deg, #2e7d32, #1b5e20); color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
        .btn:hover { opacity: 0.9; }
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
        <a href="add_vendor.php" class="active">➕ Add Vendor</a>
        <a href="view_vendors.php">🏪 View Vendors</a>
        <div class="nav-label">Certifications</div>
        <a href="add_cert.php">📄 Add Certification</a>
        <a href="view_certs.php">📋 View Certifications</a>
        <div class="nav-label">Verification</div>
        <a href="verify.php">🔍 Verify QR / Cert</a>
    </nav>
    <div class="sidebar-footer"><a href="logout.php">🚪 Logout</a></div>
</div>

<div class="main">
    <div class="page-header">
        <h1>Add Vendor</h1>
        <p>Register a new SME vendor into the system.</p>
    </div>

    <div class="form-card">
        <?php if ($success): ?><div class="alert success">✅ <?php echo $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert error">❌ <?php echo $error; ?></div><?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Business Name *</label>
                <input type="text" name="business_name" placeholder="e.g. Al-Barakah Food Store" required>
            </div>
            <div class="form-group">
                <label>Owner / Representative Name *</label>
                <input type="text" name="owner_name" placeholder="Full name" required>
            </div>
            <div class="form-group">
                <label>Contact Number</label>
                <input type="text" name="contact_number" placeholder="e.g. 09123456789">
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="business@email.com">
            </div>
            <div class="form-group">
                <label>Business Type</label>
                <select name="business_type">
                    <option value="">-- Select --</option>
                    <option value="Food Manufacturing">Food Manufacturing</option>
                    <option value="Food Retail">Food Retail</option>
                    <option value="Restaurant">Restaurant</option>
                    <option value="Slaughterhouse">Slaughterhouse</option>
                    <option value="Catering">Catering</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" placeholder="Full business address"></textarea>
            </div>
            <button type="submit" class="btn">Save Vendor</button>
        </form>
    </div>
</div>
</body>
</html>