<?php
session_start();
require_once 'connect.php';
if (!isset($_SESSION['user_id'])) { header("Location: index.php"); exit(); }
$username = $_SESSION['username'];
$result_data = null;
$search_val  = '';
$search_type = 'qr';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $search_val  = trim($_POST['search_val']);
    $search_type = $_POST['search_type'];
    $ip          = $_SERVER['REMOTE_ADDR'];
    $outcome     = 'Invalid';
    $cert_row    = null;

    if ($search_type === 'qr') {
        $s    = $conn->real_escape_string($search_val);
        $qres = $conn->query("SELECT * FROM qr_codes WHERE qr_token='$s' LIMIT 1");
        if ($qres && $qres->num_rows > 0) {
            $qr   = $qres->fetch_assoc();
            $cres = $conn->query("SELECT c.*,v.business_name,v.owner_name,v.contact_number,v.email,v.address,v.business_type FROM certifications c JOIN vendors v ON c.vendor_id=v.id WHERE c.id=".$qr['cert_id']." LIMIT 1");
            if ($cres && $cres->num_rows > 0) {
                $cert_row = $cres->fetch_assoc();
                $outcome  = ($cert_row['status'] === 'Active' && strtotime($cert_row['expiry_date']) >= time()) ? 'Valid' : 'Expired';
            }
        }
    } else {
        $s    = $conn->real_escape_string($search_val);
        $cres = $conn->query("SELECT c.*,v.business_name,v.owner_name,v.contact_number,v.email,v.address,v.business_type FROM certifications c JOIN vendors v ON c.vendor_id=v.id WHERE c.cert_number='$s' LIMIT 1");
        if ($cres && $cres->num_rows > 0) {
            $cert_row = $cres->fetch_assoc();
            $outcome  = ($cert_row['status'] === 'Active' && strtotime($cert_row['expiry_date']) >= time()) ? 'Valid' : 'Expired';
        }
    }

    // Log the verification attempt
    $sv = $conn->real_escape_string($search_val);
    $conn->query("INSERT INTO verifications (qr_token, ip_address, result) VALUES ('$sv','$ip','$outcome')");

    $result_data = ['outcome' => $outcome, 'cert' => $cert_row];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify Certificate – Halal Hub</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',sans-serif;display:flex;min-height:100vh;background:#f0f2f5}

/* SIDEBAR */
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

/* MAIN */
.main{margin-left:250px;padding:30px;flex:1}
.page-header{margin-bottom:24px}
.page-header h1{font-size:22px;color:#1b5e20;font-weight:700}
.page-header p{color:#666;font-size:13.5px;margin-top:4px}

/* VERIFY CARD */
.verify-card{background:#fff;border-radius:14px;padding:30px;max-width:640px;box-shadow:0 2px 8px rgba(0,0,0,.06);margin-bottom:28px}
.verify-card h2{font-size:16px;color:#1b5e20;font-weight:700;margin-bottom:6px}
.verify-card p{font-size:13px;color:#888;margin-bottom:20px}
.search-row{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
.search-row select{padding:10px 14px;border:1.5px solid #ddd;border-radius:8px;font-size:13.5px;outline:none;transition:border .2s;background:#fff}
.search-row select:focus{border-color:#2e7d32}
.search-row input{flex:1;min-width:200px;padding:10px 14px;border:1.5px solid #ddd;border-radius:8px;font-size:13.5px;outline:none;transition:border .2s}
.search-row input:focus{border-color:#2e7d32}
.btn{padding:10px 22px;background:linear-gradient(135deg,#2e7d32,#1b5e20);color:#fff;border:none;border-radius:8px;font-size:13.5px;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block;white-space:nowrap}
.btn:hover{opacity:.9}

/* RESULT BOX */
.result-box{margin-top:24px;border-radius:12px;padding:24px;border:2px solid}
.result-box.valid  {border-color:#2e7d32;background:#f1f8f1}
.result-box.expired{border-color:#e65100;background:#fff8f0}
.result-box.invalid{border-color:#c62828;background:#fff0f0}
.result-header{display:flex;align-items:center;gap:14px;margin-bottom:20px}
.result-icon{font-size:40px;line-height:1}
.result-title{font-size:20px;font-weight:700}
.result-title.valid  {color:#2e7d32}
.result-title.expired{color:#e65100}
.result-title.invalid{color:#c62828}
.result-sub{font-size:12px;color:#888;margin-top:3px}
.detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px 24px}
.detail-item label{font-size:11px;text-transform:uppercase;letter-spacing:.8px;color:#999;display:block;margin-bottom:3px}
.detail-item span{font-size:13.5px;color:#333;font-weight:500}
.detail-item.full{grid-column:span 2}

/* BADGE */
.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}
.badge.active {background:#e8f5e9;color:#2e7d32}
.badge.expired{background:#fce4ec;color:#c62828}
.badge.revoked{background:#fff3e0;color:#e65100}
.badge.valid  {background:#e8f5e9;color:#2e7d32}
.badge.invalid{background:#fce4ec;color:#c62828}

/* LOG TABLE */
.log-card{background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden;max-width:640px}
.log-card-header{padding:14px 20px;border-bottom:1px solid #f0f0f0;font-size:14px;font-weight:600;color:#333}
.table-wrap{overflow-x:auto}
table{width:100%;border-collapse:collapse}
th,td{padding:11px 16px;text-align:left;font-size:13px}
th{background:#f9fafb;color:#555;font-weight:600;border-bottom:1px solid #eee;white-space:nowrap}
td{border-bottom:1px solid #f5f5f5;color:#333}
tr:last-child td{border-bottom:none}
tr:hover td{background:#fafafa}
.empty-state{text-align:center;padding:30px;color:#aaa;font-size:13px}
</style>
</head>
<body>

<!-- SIDEBAR -->
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
        <a href="view_certs.php">📋 View Certifications</a>
        <div class="nav-label">Verification</div>
        <a href="verify.php" class="active">🔍 Verify QR / Cert</a>
    </nav>
    <div class="sidebar-footer"><a href="logout.php">🚪 Logout</a></div>
</div>

<!-- MAIN -->
<div class="main">
    <div class="page-header">
        <h1>Verify Certificate</h1>
        <p>Check the validity of a halal certification by QR token or certificate number.</p>
    </div>

    <div class="verify-card">
        <h2>🔍 Verification Lookup</h2>
        <p>Enter a QR token or certificate number to check its current status.</p>
        <form method="POST">
            <div class="search-row">
                <select name="search_type">
                    <option value="qr"   <?php echo ($search_type==='qr')  ?'selected':''; ?>>QR Token</option>
                    <option value="cert" <?php echo ($search_type==='cert')?'selected':''; ?>>Certificate Number</option>
                </select>
                <input type="text" name="search_val"
                       placeholder="Enter QR token or cert number…"
                       value="<?php echo htmlspecialchars($search_val); ?>" required>
                <button type="submit" class="btn">✅ Verify</button>
            </div>
        </form>

        <?php if ($result_data !== null):
            $out  = strtolower($result_data['outcome']);
            $cert = $result_data['cert'];
        ?>
        <div class="result-box <?php echo $out; ?>">
            <div class="result-header">
                <span class="result-icon">
                    <?php echo $out==='valid' ? '✅' : ($out==='expired' ? '⚠️' : '❌'); ?>
                </span>
                <div>
                    <div class="result-title <?php echo $out; ?>">
                        <?php
                        if ($out === 'valid')   echo 'Valid Certificate';
                        elseif ($out === 'expired') echo 'Expired / Revoked';
                        else echo 'Not Found';
                        ?>
                    </div>
                    <div class="result-sub">
                        <?php
                        if ($out === 'valid')       echo 'This certification is active and legitimate.';
                        elseif ($out === 'expired') echo 'This certification is no longer valid.';
                        else                        echo 'No matching record was found in the system.';
                        ?>
                    </div>
                </div>
            </div>

            <?php if ($cert): ?>
            <div class="detail-grid">
                <div class="detail-item"><label>Business Name</label><span><?php echo htmlspecialchars($cert['business_name']); ?></span></div>
                <div class="detail-item"><label>Owner</label><span><?php echo htmlspecialchars($cert['owner_name']); ?></span></div>
                <div class="detail-item"><label>Certificate Number</label><span><?php echo htmlspecialchars($cert['cert_number']); ?></span></div>
                <div class="detail-item"><label>Issuing Body</label><span><?php echo htmlspecialchars($cert['issuing_body'] ?: '—'); ?></span></div>
                <div class="detail-item"><label>Issue Date</label><span><?php echo $cert['issue_date']; ?></span></div>
                <div class="detail-item"><label>Expiry Date</label><span><?php echo $cert['expiry_date']; ?></span></div>
                <div class="detail-item"><label>Status</label><span><span class="badge <?php echo strtolower($cert['status']); ?>"><?php echo $cert['status']; ?></span></span></div>
                <div class="detail-item"><label>Business Type</label><span><?php echo htmlspecialchars($cert['business_type'] ?: '—'); ?></span></div>
                <?php if(!empty($cert['address'])): ?>
                <div class="detail-item full"><label>Address</label><span><?php echo htmlspecialchars($cert['address']); ?></span></div>
                <?php endif; ?>
                <?php if(!empty($cert['remarks'])): ?>
                <div class="detail-item full"><label>Remarks</label><span><?php echo htmlspecialchars($cert['remarks']); ?></span></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Verification Log -->
    <div class="log-card">
        <div class="log-card-header">📋 Recent Verification Log</div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>#</th><th>Token / Cert No.</th><th>Result</th><th>IP Address</th><th>Date &amp; Time</th></tr>
                </thead>
                <tbody>
                <?php
                $log = $conn->query("SELECT * FROM verifications ORDER BY verified_at DESC LIMIT 20");
                if ($log && $log->num_rows > 0): $n=1; while($row=$log->fetch_assoc()):
                ?>
                <tr>
                    <td><?php echo $n++; ?></td>
                    <td style="font-family:monospace;font-size:12px"><?php
                        $tok = htmlspecialchars($row['qr_token']);
                        echo strlen($tok) > 30 ? substr($tok,0,30).'…' : $tok;
                    ?></td>
                    <td><span class="badge <?php echo strtolower($row['result']); ?>"><?php echo $row['result']; ?></span></td>
                    <td><?php echo htmlspecialchars($row['ip_address']); ?></td>
                    <td><?php echo date('M d, Y H:i', strtotime($row['verified_at'])); ?></td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="5"><div class="empty-state">No verifications yet. Try verifying a certificate above.</div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>