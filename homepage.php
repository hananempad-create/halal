<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$username = $_SESSION['username'];

// Count stats
$total_vendors = $conn->query("SELECT COUNT(*) as c FROM vendors")->fetch_assoc()['c'];
$total_certs = $conn->query("SELECT COUNT(*) as c FROM certifications")->fetch_assoc()['c'];
$active_certs = $conn->query("SELECT COUNT(*) as c FROM certifications WHERE status='Active'")->fetch_assoc()['c'];
$total_verifications = $conn->query("SELECT COUNT(*) as c FROM verifications")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halal Hub - Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; display: flex; min-height: 100vh; background: #f0f2f5; }

        /* SIDEBAR */
        .sidebar {
            width: 250px;
            background: linear-gradient(180deg, #2e7d32, #1b5e20);
            color: white;
            display: flex;
            flex-direction: column;
            padding: 0;
            position: fixed;
            height: 100vh;
            top: 0; left: 0;
            z-index: 100;
        }
        .sidebar-brand {
            padding: 24px 20px;
            background: rgba(0,0,0,0.15);
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-brand h2 { font-size: 16px; font-weight: 700; color: #fff; line-height: 1.4; }
        .sidebar-brand p { font-size: 11px; color: rgba(255,255,255,0.65); margin-top: 4px; }

        .sidebar-user {
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .avatar {
            width: 36px; height: 36px;
            background: #81c784;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 15px; color: #1b5e20;
        }
        .sidebar-user span { font-size: 13px; color: rgba(255,255,255,0.85); }

        .sidebar-nav { flex: 1; padding: 12px 0; overflow-y: auto; }
        .nav-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: rgba(255,255,255,0.4);
            padding: 12px 20px 4px;
        }
        .sidebar-nav a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 13.5px;
            transition: background 0.2s;
        }
        .sidebar-nav a:hover, .sidebar-nav a.active {
            background: rgba(255,255,255,0.12);
            color: #fff;
        }
        .sidebar-nav a span.icon { font-size: 16px; }

        .sidebar-footer {
            padding: 16px 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-footer a {
            display: flex; align-items: center; gap: 8px;
            color: rgba(255,255,255,0.7); text-decoration: none; font-size: 13px;
        }
        .sidebar-footer a:hover { color: #fff; }

        /* MAIN CONTENT */
        .main { margin-left: 250px; padding: 30px; flex: 1; }

        .page-header { margin-bottom: 28px; }
        .page-header h1 { font-size: 22px; color: #1b5e20; font-weight: 700; }
        .page-header p { color: #666; font-size: 13.5px; margin-top: 4px; }

        /* STAT CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 18px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 22px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .stat-icon {
            width: 52px; height: 52px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px;
        }
        .stat-icon.green { background: #e8f5e9; }
        .stat-icon.blue { background: #e3f2fd; }
        .stat-icon.orange { background: #fff3e0; }
        .stat-icon.purple { background: #f3e5f5; }
        .stat-info h3 { font-size: 26px; font-weight: 700; color: #222; }
        .stat-info p { font-size: 12px; color: #888; margin-top: 2px; }

        /* QUICK ACTIONS */
        .section-title { font-size: 15px; font-weight: 600; color: #333; margin-bottom: 14px; }
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 14px;
            margin-bottom: 30px;
        }
        .action-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            text-decoration: none;
            color: #333;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: transform 0.2s, box-shadow 0.2s;
            border-left: 4px solid #2e7d32;
        }
        .action-card:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(0,0,0,0.1); }
        .action-card .icon { font-size: 28px; margin-bottom: 8px; }
        .action-card h4 { font-size: 14px; font-weight: 600; }
        .action-card p { font-size: 12px; color: #888; margin-top: 3px; }

        /* RECENT TABLE */
        .table-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .table-card-header {
            padding: 16px 20px;
            border-bottom: 1px solid #f0f0f0;
            display: flex; justify-content: space-between; align-items: center;
        }
        .table-card-header h3 { font-size: 14px; font-weight: 600; color: #333; }
        .table-card-header a { font-size: 12px; color: #2e7d32; text-decoration: none; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 20px; text-align: left; font-size: 13px; }
        th { background: #f9fafb; color: #555; font-weight: 600; border-bottom: 1px solid #eee; }
        td { border-bottom: 1px solid #f5f5f5; color: #333; }
        tr:last-child td { border-bottom: none; }
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge.active { background: #e8f5e9; color: #2e7d32; }
        .badge.expired { background: #fce4ec; color: #c62828; }
        .badge.revoked { background: #fff3e0; color: #e65100; }
        .empty { text-align: center; padding: 30px; color: #aaa; font-size: 13px; }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-brand">
        <h2>🕌 Halal Certification &amp; Verification Hub</h2>
        <p>SME Management System</p>
    </div>
    <div class="sidebar-user">
        <div class="avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
        <span><?php echo htmlspecialchars($username); ?></span>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-label">Main</div>
        <a href="homepage.php" class="active"><span class="icon">📊</span> Dashboard</a>

        <div class="nav-label">Vendors</div>
        <a href="add_vendor.php"><span class="icon">➕</span> Add Vendor</a>
        <a href="view_vendors.php"><span class="icon">🏪</span> View Vendors</a>

        <div class="nav-label">Certifications</div>
        <a href="add_cert.php"><span class="icon">📄</span> Add Certification</a>
        <a href="view_certs.php"><span class="icon">📋</span> View Certifications</a>

        <div class="nav-label">Verification</div>
        <a href="verify.php"><span class="icon">🔍</span> Verify QR / Cert</a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php">🚪 Logout</a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main">
    <div class="page-header">
        <h1>Dashboard</h1>
        <p>Welcome back, <?php echo htmlspecialchars($username); ?>! Here's your system overview.</p>
    </div>

    <!-- STATS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon green">🏪</div>
            <div class="stat-info">
                <h3><?php echo $total_vendors; ?></h3>
                <p>Total Vendors</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue">📄</div>
            <div class="stat-info">
                <h3><?php echo $total_certs; ?></h3>
                <p>Total Certifications</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange">✅</div>
            <div class="stat-info">
                <h3><?php echo $active_certs; ?></h3>
                <p>Active Certifications</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple">🔍</div>
            <div class="stat-info">
                <h3><?php echo $total_verifications; ?></h3>
                <p>Total Verifications</p>
            </div>
        </div>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="section-title">Quick Actions</div>
    <div class="actions-grid">
        <a href="add_vendor.php" class="action-card">
            <div class="icon">🏪</div>
            <h4>Add Vendor</h4>
            <p>Register a new SME vendor</p>
        </a>
        <a href="add_cert.php" class="action-card">
            <div class="icon">📄</div>
            <h4>Add Certification</h4>
            <p>Issue a new halal certification</p>
        </a>
        <a href="view_certs.php" class="action-card">
            <div class="icon">📋</div>
            <h4>View Certifications</h4>
            <p>Browse all certification records</p>
        </a>
        <a href="verify.php" class="action-card">
            <div class="icon">🔍</div>
            <h4>Verify Certificate</h4>
            <p>Check certification validity</p>
        </a>
    </div>

    <!-- RECENT CERTIFICATIONS -->
    <div class="section-title">Recent Certifications</div>
    <div class="table-card">
        <div class="table-card-header">
            <h3>Latest Records</h3>
            <a href="view_certs.php">View All →</a>
        </div>
        <?php
        $recent = $conn->query("
            SELECT c.cert_number, v.business_name, c.status, c.expiry_date
            FROM certifications c
            JOIN vendors v ON c.vendor_id = v.id
            ORDER BY c.created_at DESC
            LIMIT 5
        ");
        if ($recent && $recent->num_rows > 0):
        ?>
        <table>
            <thead>
                <tr>
                    <th>Cert No.</th>
                    <th>Business Name</th>
                    <th>Expiry Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $recent->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['cert_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['business_name']); ?></td>
                    <td><?php echo $row['expiry_date']; ?></td>
                    <td><span class="badge <?php echo strtolower($row['status']); ?>"><?php echo $row['status']; ?></span></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty">No certifications yet. <a href="add_cert.php">Add one now →</a></div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>