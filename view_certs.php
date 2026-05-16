<?php
session_start();
require_once 'connect.php';
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
$username = $_SESSION['username'];
$success = $error = '';

// Handle DELETE
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM certifications WHERE id=$id") ? $success = "Certification deleted." : $error = "Delete failed.";
}

// Handle EDIT submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_id'])) {
    $id           = intval($_POST['edit_id']);
    $vendor_id    = intval($_POST['vendor_id']);
    $cert_number  = trim($_POST['cert_number']);
    $issuing_body = trim($_POST['issuing_body']);
    $issue_date   = $_POST['issue_date'];
    $expiry_date  = $_POST['expiry_date'];
    $status       = $_POST['status'];
    $remarks      = trim($_POST['remarks']);
    $stmt = $conn->prepare("UPDATE certifications SET vendor_id=?,cert_number=?,issuing_body=?,issue_date=?,expiry_date=?,status=?,remarks=? WHERE id=?");
    $stmt->bind_param("issssssi",$vendor_id,$cert_number,$issuing_body,$issue_date,$expiry_date,$status,$remarks,$id);
    $stmt->execute() ? $success = "Certification updated." : $error = "Update failed: ".$conn->error;
    $stmt->close();
}

$search = trim($_GET['q'] ?? '');
$sql = "SELECT c.*, v.business_name FROM certifications c JOIN vendors v ON c.vendor_id=v.id";
if ($search) {
    $s = $conn->real_escape_string($search);
    $sql .= " WHERE c.cert_number LIKE '%$s%' OR v.business_name LIKE '%$s%' OR c.issuing_body LIKE '%$s%' OR c.status LIKE '%$s%'";
}
$sql .= " ORDER BY c.created_at DESC";
$certs = $conn->query($sql);

$edit_cert = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $eid = intval($_GET['edit']);
    $edit_cert = $conn->query("SELECT * FROM certifications WHERE id=$eid")->fetch_assoc();
}

$vendors = $conn->query("SELECT id, business_name FROM vendors ORDER BY business_name ASC");
$vendor_list = [];
if ($vendors) { while($v=$vendors->fetch_assoc()) $vendor_list[] = $v; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Certifications – Halal Hub</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',sans-serif;display:flex;min-height:100vh;background:#f0f2f5}
.sidebar{width:250px;background:linear-gradient(180deg,#2e7d32,#1b5e20);color:white;display:flex;flex-direction:column;position:fixed;height:100vh;top:0;left:0;z-index:100}
.sidebar-brand{padding:22px 18px;background:rgba(0,0,0,.15);text-align:center;border-bottom:1px solid rgba(255,255,255,.1)}
.sidebar-brand h2{font-size:14px;font-weight:700;color:#fff;line-height:1.5}
.sidebar-user{padding:14px 18px;display:flex;align-items:center;gap:10px;border-bottom:1px solid rgba(255,255,255,.1)}
.avatar{width:36px;height:36px;background:#81c784;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:15px;color:#1b5e20}
.sidebar-user span{font-size:13px;color:rgba(255,255,255,.85)}
.sidebar-nav{flex:1;padding:10px 0;overflow-y:auto}
.nav-label{font-size:10px;text-transform:uppercase;letter-spacing:1.2px;color:rgba(255,255,255,.4);padding:12px 18px 4px}
.sidebar-nav a{display:flex;align-items:center;gap:10px;padding:10px 18px;color:rgba(255,255,255,.8);text-decoration:none;font-size:13.5px;transition:background .2s}
.sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(255,255,255,.12);color:#fff}
.sidebar-footer{padding:14px 18px;border-top:1px solid rgba(255,255,255,.1)}
.sidebar-footer a{color:rgba(255,255,255,.7);text-decoration:none;font-size:13px}
.main{margin-left:250px;padding:30px;flex:1}
.page-header{margin-bottom:24px}
.page-header h1{font-size:22px;color:#1b5e20;font-weight:700}
.page-header p{color:#666;font-size:13.5px;margin-top:4px}
.alert{padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:18px}
.alert.success{background:#e8f5e9;color:#2e7d32}
.alert.error{background:#fce4ec;color:#c62828}
.search-bar{display:flex;gap:10px;margin-bottom:20px;align-items:center;flex-wrap:wrap}
.search-bar input{padding:9px 14px;border:1.5px solid #ddd;border-radius:8px;font-size:13.5px;outline:none;min-width:220px;flex:1;transition:border .2s}
.search-bar input:focus{border-color:#2e7d32}
.btn{padding:10px 20px;background:linear-gradient(135deg,#2e7d32,#1b5e20);color:#fff;border:none;border-radius:8px;font-size:13.5px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block;transition:opacity .2s}
.btn:hover{opacity:.9}
.btn-sm{padding:6px 12px;font-size:12px}
.btn-warning{background:linear-gradient(135deg,#f57f17,#e65100)}
.btn-danger{background:linear-gradient(135deg,#c62828,#b71c1c)}
.card{background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden}
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse}
th,td{padding:12px 16px;text-align:left;font-size:13px}
th{background:#f9fafb;color:#555;font-weight:600;border-bottom:1px solid #eee;white-space:nowrap}
td{border-bottom:1px solid #f5f5f5;color:#333}
tr:last-child td{border-bottom:none}
tr:hover td{background:#fafafa}
.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}
.badge.active{background:#e8f5e9;color:#2e7d32}
.badge.expired{background:#fce4ec;color:#c62828}
.badge.revoked{background:#fff3e0;color:#e65100}
.empty-state{text-align:center;padding:40px 20px;color:#aaa;font-size:14px}
.empty-state .big{font-size:40px;margin-bottom:10px}
.qr-token{font-family:monospace;font-size:11px;color:#888}
.modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:200;justify-content:center;align-items:center}
.modal-bg.open{display:flex}
.modal{background:#fff;border-radius:14px;padding:30px;width:100%;max-width:580px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.25)}
.modal h2{font-size:17px;color:#1b5e20;margin-bottom:20px;font-weight:700}
.form-group{margin-bottom:16px}
.form-group label{display:block;font-size:13px;font-weight:600;color:#444;margin-bottom:5px}
.form-group input,.form-group select,.form-group textarea{width:100%;padding:10px 14px;border:1.5px solid #ddd;border-radius:8px;font-size:13.5px;color:#333;outline:none;transition:border .2s}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{border-color:#2e7d32}
.form-group textarea{resize:vertical;min-height:70px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.modal-footer{margin-top:20px;display:flex;gap:10px}
</style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-brand"><h2>🕌 Halal Certification &amp; Verification Hub</h2></div>
    <div class="sidebar-user">
        <div class="avatar"><?php echo strtoupper(substr($username,0,1)); ?></div>
        <span><?php echo htmlspecialchars($username); ?></span>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-label">Main</div>
        <a href="homepage.php">📊 Dashboard</a>
        <div class="nav-label">Vendors</div>
        <a href="add_vendor.php">➕ Add Vendor</a>
        <a href="view_vendors.php">🏪 View Vendors</a>
        <div class="nav-label">Certifications</div>
        <a href="add_cert.php">📄 Add Certification</a>
        <a href="view_certs.php" class="active">📋 View Certifications</a>
        <div class="nav-label">Verification</div>
        <a href="verify.php">🔍 Verify QR / Cert</a>
    </nav>
    <div class="sidebar-footer"><a href="logout.php">🚪 Logout</a></div>
</div>

<div class="main">
    <div class="page-header">
        <h1>Certifications</h1>
        <p>All halal certifications issued in the system.</p>
    </div>

    <?php if($success): ?><div class="alert success">✅ <?php echo $success; ?></div><?php endif; ?>
    <?php if($error):   ?><div class="alert error">❌ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>

    <div class="search-bar">
        <form method="GET" style="display:flex;gap:10px;flex:1;flex-wrap:wrap">
            <input type="text" name="q" placeholder="Search by cert number, business, issuing body, or status…" value="<?php echo htmlspecialchars($search); ?>">
            <button class="btn" type="submit">🔍 Search</button>
            <?php if($search): ?><a href="view_certs.php" class="btn btn-warning">✕ Clear</a><?php endif; ?>
        </form>
        <a href="add_cert.php" class="btn">➕ Add Certification</a>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>#</th><th>Cert Number</th><th>Business</th><th>Issuing Body</th><th>Issue Date</th><th>Expiry Date</th><th>Status</th><th>QR Token</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php if($certs && $certs->num_rows > 0): $i=1; while($c=$certs->fetch_assoc()):
                    $qr_res = $conn->query("SELECT qr_token FROM qr_codes WHERE cert_id=".$c['id']." LIMIT 1");
                    $qr_token = ($qr_res && $qr_res->num_rows > 0) ? $qr_res->fetch_assoc()['qr_token'] : null;
                ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><strong><?php echo htmlspecialchars($c['cert_number']); ?></strong></td>
                    <td><?php echo htmlspecialchars($c['business_name']); ?></td>
                    <td><?php echo htmlspecialchars($c['issuing_body'] ?: '—'); ?></td>
                    <td><?php echo $c['issue_date']; ?></td>
                    <td><?php echo $c['expiry_date']; ?></td>
                    <td><span class="badge <?php echo strtolower($c['status']); ?>"><?php echo $c['status']; ?></span></td>
                    <td>
                        <?php if($qr_token): ?>
                            <span class="qr-token" title="<?php echo htmlspecialchars($qr_token); ?>"><?php echo substr($qr_token,0,12).'…'; ?></span>
                        <?php else: ?>
                            <span style="color:#ccc;font-size:12px">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="white-space:nowrap">
                        <a href="view_certs.php?edit=<?php echo $c['id']; ?><?php echo $search?"&q=".urlencode($search):''; ?>" class="btn btn-sm btn-warning">✏️ Edit</a>
                        <a href="view_certs.php?delete=<?php echo $c['id']; ?><?php echo $search?"&q=".urlencode($search):''; ?>"
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Delete this certification?')">🗑️ Delete</a>
                    </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="9">
                    <div class="empty-state">
                        <div class="big">📋</div>
                        <?php echo $search ? "No certifications match your search." : "No certifications yet."; ?><br>
                        <a href="add_cert.php">Add one →</a>
                    </div>
                </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if($edit_cert): ?>
<div class="modal-bg open">
    <div class="modal">
        <h2>✏️ Edit Certification</h2>
        <form method="POST">
            <input type="hidden" name="edit_id" value="<?php echo $edit_cert['id']; ?>">
            <div class="form-group">
                <label>Vendor / Business *</label>
                <select name="vendor_id" required>
                    <option value="">-- Select Vendor --</option>
                    <?php foreach($vendor_list as $v): ?>
                    <option value="<?php echo $v['id']; ?>" <?php echo ($edit_cert['vendor_id']==$v['id'])?'selected':''; ?>>
                        <?php echo htmlspecialchars($v['business_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Certificate Number *</label><input type="text" name="cert_number" value="<?php echo htmlspecialchars($edit_cert['cert_number']); ?>" required></div>
            <div class="form-group"><label>Issuing Body</label><input type="text" name="issuing_body" value="<?php echo htmlspecialchars($edit_cert['issuing_body']); ?>"></div>
            <div class="form-row">
                <div class="form-group"><label>Issue Date *</label><input type="date" name="issue_date" value="<?php echo $edit_cert['issue_date']; ?>" required></div>
                <div class="form-group"><label>Expiry Date *</label><input type="date" name="expiry_date" value="<?php echo $edit_cert['expiry_date']; ?>" required></div>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <?php foreach(['Active','Expired','Revoked'] as $st): ?>
                    <option <?php echo ($edit_cert['status']==$st)?'selected':''; ?>><?php echo $st; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Remarks</label><textarea name="remarks"><?php echo htmlspecialchars($edit_cert['remarks']); ?></textarea></div>
            <div class="modal-footer">
                <button type="submit" class="btn">💾 Update</button>
                <a href="view_certs.php<?php echo $search?"?q=".urlencode($search):''; ?>" class="btn btn-danger">✕ Cancel</a>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

</body>
</html>