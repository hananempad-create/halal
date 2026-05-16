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

// Build base URL for verify page — QR will encode this full URL
$protocol    = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$base_url    = $protocol . '://' . $_SERVER['HTTP_HOST'];
$dir         = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$verify_base = $base_url . $dir . '/verify.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Certifications – Halal Hub</title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
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
.btn-qr{background:linear-gradient(135deg,#0277bd,#01579b)}
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

/* Edit Modal */
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

/* QR Modal */
.qr-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:300;justify-content:center;align-items:center}
.qr-modal-bg.open{display:flex}
.qr-modal{background:#fff;border-radius:20px;padding:28px 24px;width:100%;max-width:400px;box-shadow:0 24px 80px rgba(0,0,0,.3);text-align:center;position:relative}
.qr-modal-close{position:absolute;top:14px;right:16px;background:none;border:none;font-size:20px;cursor:pointer;color:#aaa}
.qr-modal-close:hover{color:#333}
/* Card preview inside modal */
.qr-card-preview{background:#fff;border:2px solid #e8f5e9;border-radius:14px;overflow:hidden;margin-bottom:18px}
.qr-card-header-bar{background:linear-gradient(135deg,#2e7d32,#1b5e20);padding:14px 16px;text-align:left;display:flex;align-items:center;justify-content:space-between}
.qr-card-header-bar .titles h4{font-size:13px;font-weight:700;color:#fff;margin-bottom:2px}
.qr-card-header-bar .titles p{font-size:10px;color:rgba(255,255,255,.7)}
.qr-card-header-bar .mosque-icon{font-size:28px}
.qr-card-body{padding:18px 18px 14px}
.qr-card-qr{display:flex;justify-content:center;margin-bottom:14px}
.qr-card-qr canvas,.qr-card-qr img{border-radius:6px;border:3px solid #e8f5e9}
.qr-card-business{font-size:15px;font-weight:700;color:#1b5e20;margin-bottom:4px}
.qr-card-certnum{font-size:11px;font-family:monospace;color:#888;margin-bottom:8px}
.qr-card-status{display:inline-block;padding:3px 14px;border-radius:20px;font-size:11px;font-weight:700;margin-bottom:10px}
.qr-card-status.active{background:#e8f5e9;color:#2e7d32}
.qr-card-status.expired{background:#fce4ec;color:#c62828}
.qr-card-status.revoked{background:#fff3e0;color:#e65100}
.qr-card-expiry{font-size:11px;color:#aaa;margin-bottom:10px}
.qr-card-footer-bar{background:#f9fafb;border-top:1px solid #eee;padding:8px;font-size:10px;color:#bbb}
.qr-modal-actions{display:flex;gap:10px;justify-content:center}
.btn-download{padding:11px 24px;background:linear-gradient(135deg,#2e7d32,#1b5e20);color:#fff;border:none;border-radius:8px;font-size:13.5px;font-weight:600;cursor:pointer}
.btn-download:hover{opacity:.9}
.btn-close-qr{padding:11px 24px;background:#f0f2f5;color:#555;border:none;border-radius:8px;font-size:13.5px;font-weight:600;cursor:pointer}
.btn-close-qr:hover{background:#e0e0e0}
#downloadCanvas{display:none}
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
                    <tr><th>#</th><th>Cert Number</th><th>Business</th><th>Issuing Body</th><th>Issue Date</th><th>Expiry Date</th><th>Status</th><th>QR Code</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php if($certs && $certs->num_rows > 0): $i=1; while($c=$certs->fetch_assoc()):
                    $qr_res   = $conn->query("SELECT qr_token FROM qr_codes WHERE cert_id=".$c['id']." LIMIT 1");
                    $qr_token = ($qr_res && $qr_res->num_rows > 0) ? $qr_res->fetch_assoc()['qr_token'] : null;
                    // Full verify URL — scanning the QR goes straight to the result page
                    $verify_url = $verify_base . '?search_type=qr&search_val=' . urlencode($qr_token ?? '');
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
                            <button class="btn btn-sm btn-qr qr-btn"
                                data-url="<?php echo htmlspecialchars($verify_url, ENT_QUOTES); ?>"
                                data-cert="<?php echo htmlspecialchars($c['cert_number'], ENT_QUOTES); ?>"
                                data-biz="<?php echo htmlspecialchars($c['business_name'], ENT_QUOTES); ?>"
                                data-status="<?php echo strtolower($c['status']); ?>"
                                data-expiry="<?php echo $c['expiry_date']; ?>"
                            >🔳 View QR</button>
                        <?php else: ?>
                            <span style="color:#ccc;font-size:12px">No QR</span>
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

<!-- ========== QR CODE MODAL ========== -->
<div class="qr-modal-bg" id="qrModalBg">
    <div class="qr-modal">
        <button class="qr-modal-close" onclick="closeQR()">✕</button>

        <div class="qr-card-preview" id="qrCardPreview">
            <div class="qr-card-header-bar">
                <div class="titles">
                    <h4>Halal Certification Hub</h4>
                    <p>Official Halal Certificate</p>
                </div>
                <span class="mosque-icon">🕌</span>
            </div>
            <div class="qr-card-body">
                <div class="qr-card-qr"><div id="qrCodeEl"></div></div>
                <div class="qr-card-business" id="qrCardBusiness"></div>
                <div class="qr-card-certnum"  id="qrCardCertNum"></div>
                <span class="qr-card-status"  id="qrCardStatus"></span>
                <div class="qr-card-expiry"   id="qrCardExpiry"></div>
            </div>
            <div class="qr-card-footer-bar">Scan QR code to verify this certification online</div>
        </div>

        <div class="qr-modal-actions">
            <button class="btn-download" onclick="downloadQR()">⬇️ Download QR Card</button>
            <button class="btn-close-qr" onclick="closeQR()">Close</button>
        </div>
    </div>
</div>

<!-- Hidden canvas for PNG composition -->
<canvas id="downloadCanvas"></canvas>

<!-- ========== EDIT MODAL ========== -->
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

<script>
let currentQR = {};

function showQR(verifyUrl, certNum, business, status, expiryDate) {
    currentQR = { verifyUrl, certNum, business, status, expiryDate };

    document.getElementById('qrCodeEl').innerHTML = '';

    document.getElementById('qrCardBusiness').textContent = business;
    document.getElementById('qrCardCertNum').textContent  = certNum;
    document.getElementById('qrCardExpiry').textContent   = 'Valid until: ' + expiryDate;

    const statusEl = document.getElementById('qrCardStatus');
    statusEl.textContent = status.charAt(0).toUpperCase() + status.slice(1);
    statusEl.className   = 'qr-card-status ' + status;

    // QR encodes the full verify URL — scanning redirects straight to verification result
    new QRCode(document.getElementById('qrCodeEl'), {
        text: verifyUrl,
        width: 180,
        height: 180,
        colorDark: '#1b5e20',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.H
    });

    document.getElementById('qrModalBg').classList.add('open');
}

function closeQR() {
    document.getElementById('qrModalBg').classList.remove('open');
    document.getElementById('qrCodeEl').innerHTML = '';
}

function downloadQR() {
    setTimeout(() => {
        const qrEl     = document.getElementById('qrCodeEl');
        const qrCanvas = qrEl.querySelector('canvas');
        const qrImg    = qrEl.querySelector('img');
        let qrDataUrl  = qrCanvas ? qrCanvas.toDataURL('image/png') : (qrImg ? qrImg.src : null);

        if (!qrDataUrl) { alert('QR not ready, please try again.'); return; }

        const qrImage = new Image();
        qrImage.onload = function () {
            const W = 500, H = 640;
            const canvas = document.getElementById('downloadCanvas');
            canvas.width = W; canvas.height = H;
            const ctx = canvas.getContext('2d');

            // White card background
            ctx.fillStyle = '#ffffff';
            roundRect(ctx, 0, 0, W, H, 20);
            ctx.fill();

            // Green header
            const grad = ctx.createLinearGradient(0, 0, W, 120);
            grad.addColorStop(0, '#2e7d32');
            grad.addColorStop(1, '#1b5e20');
            ctx.fillStyle = grad;
            roundRect(ctx, 0, 0, W, 115, [20, 20, 0, 0]);
            ctx.fill();

            // Mosque emoji
            ctx.font = '46px serif';
            ctx.textAlign = 'right';
            ctx.fillText('🕌', W - 22, 68);

            // Header text
            ctx.textAlign = 'left';
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 18px "Segoe UI", Arial, sans-serif';
            ctx.fillText('Halal Certification Hub', 24, 50);
            ctx.font = '13px "Segoe UI", Arial, sans-serif';
            ctx.fillStyle = 'rgba(255,255,255,0.72)';
            ctx.fillText('Official Halal Certificate', 24, 72);

            // Separator
            ctx.fillStyle = '#e8f5e9';
            ctx.fillRect(0, 115, W, 3);

            // QR box
            const qrSize = 210, qrX = (W - qrSize) / 2, qrY = 138;
            ctx.fillStyle = '#f1f8f1';
            roundRect(ctx, qrX - 14, qrY - 14, qrSize + 28, qrSize + 28, 16);
            ctx.fill();
            ctx.strokeStyle = '#c8e6c9'; ctx.lineWidth = 2; ctx.stroke();
            ctx.drawImage(qrImage, qrX, qrY, qrSize, qrSize);

            // Business name
            ctx.textAlign = 'center';
            ctx.fillStyle = '#1b5e20';
            ctx.font = 'bold 22px "Segoe UI", Arial, sans-serif';
            let nameY = qrY + qrSize + 50;
            nameY = wrapText(ctx, currentQR.business, W / 2, nameY, W - 60, 30);

            // Cert number
            ctx.font = '13px monospace';
            ctx.fillStyle = '#888888';
            ctx.fillText(currentQR.certNum, W / 2, nameY + 10);

            // Status badge
            const statusLabel = currentQR.status.charAt(0).toUpperCase() + currentQR.status.slice(1);
            const bc = {
                active:  { bg:'#e8f5e9', text:'#2e7d32', border:'#a5d6a7' },
                expired: { bg:'#fce4ec', text:'#c62828', border:'#ef9a9a' },
                revoked: { bg:'#fff3e0', text:'#e65100', border:'#ffcc80' }
            }[currentQR.status] || { bg:'#e8f5e9', text:'#2e7d32', border:'#a5d6a7' };

            const bW = 130, bH = 30, bX = (W - bW) / 2, bY = nameY + 26;
            ctx.fillStyle = bc.bg; ctx.strokeStyle = bc.border; ctx.lineWidth = 1.5;
            roundRect(ctx, bX, bY, bW, bH, 15); ctx.fill(); ctx.stroke();
            ctx.fillStyle = bc.text;
            ctx.font = 'bold 13px "Segoe UI", Arial, sans-serif';
            ctx.fillText(statusLabel, W / 2, bY + 21);

            // Expiry
            ctx.font = '12px "Segoe UI", Arial, sans-serif';
            ctx.fillStyle = '#aaaaaa';
            ctx.fillText('Valid until: ' + currentQR.expiryDate, W / 2, bY + 56);

            // Footer bar
            ctx.fillStyle = '#f9fafb';
            roundRect(ctx, 0, H - 56, W, 56, [0, 0, 20, 20]);
            ctx.fill();
            ctx.strokeStyle = '#eeeeee'; ctx.lineWidth = 1;
            ctx.beginPath(); ctx.moveTo(0, H - 56); ctx.lineTo(W, H - 56); ctx.stroke();
            ctx.font = '11px "Segoe UI", Arial, sans-serif';
            ctx.fillStyle = '#aaaaaa';
            ctx.fillText('Scan QR code to verify this certification online', W / 2, H - 30);
            ctx.fillStyle = '#bbbbbb';
            ctx.fillText('Halal Certification & Verification Hub', W / 2, H - 13);

            // Download
            const link = document.createElement('a');
            link.download = 'HalalCert_' + currentQR.certNum.replace(/[^a-z0-9]/gi, '_') + '.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        };
        qrImage.src = qrDataUrl;
    }, 150);
}

// Canvas rounded rect helper (polyfill for older browsers)
function roundRect(ctx, x, y, w, h, r) {
    if (typeof r === 'number') r = [r, r, r, r];
    ctx.beginPath();
    ctx.moveTo(x + r[0], y);
    ctx.lineTo(x + w - r[1], y);
    ctx.quadraticCurveTo(x + w, y, x + w, y + r[1]);
    ctx.lineTo(x + w, y + h - r[2]);
    ctx.quadraticCurveTo(x + w, y + h, x + w - r[2], y + h);
    ctx.lineTo(x + r[3], y + h);
    ctx.quadraticCurveTo(x, y + h, x, y + h - r[3]);
    ctx.lineTo(x, y + r[0]);
    ctx.quadraticCurveTo(x, y, x + r[0], y);
    ctx.closePath();
}

// Wrap long text, returns new Y after last line
function wrapText(ctx, text, x, y, maxWidth, lineHeight) {
    const words = text.split(' ');
    let line = '';
    for (let i = 0; i < words.length; i++) {
        const test = line + words[i] + ' ';
        if (ctx.measureText(test).width > maxWidth && i > 0) {
            ctx.fillText(line.trim(), x, y);
            line = words[i] + ' ';
            y += lineHeight;
        } else {
            line = test;
        }
    }
    ctx.fillText(line.trim(), x, y);
    return y;
}

// Event delegation — handles all .qr-btn clicks via data attributes
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.qr-btn');
    if (btn) {
        showQR(
            btn.dataset.url,
            btn.dataset.cert,
            btn.dataset.biz,
            btn.dataset.status,
            btn.dataset.expiry
        );
    }
});

document.getElementById('qrModalBg').addEventListener('click', function(e) {
    if (e.target === this) closeQR();
});
</script>

</body>
</html>