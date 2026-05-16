<?php
// sidebar.php – include this in every authenticated page
// Requires: $username, $active_page
$pages = [
    'dashboard'   => ['href'=>'homepage.php',    'icon'=>'📊', 'label'=>'Dashboard'],
    'add_vendor'  => ['href'=>'add_vendor.php',   'icon'=>'➕', 'label'=>'Add Vendor'],
    'view_vendors'=> ['href'=>'view_vendors.php', 'icon'=>'🏪', 'label'=>'View Vendors'],
    'add_cert'    => ['href'=>'add_cert.php',     'icon'=>'📄', 'label'=>'Add Certification'],
    'view_certs'  => ['href'=>'view_certs.php',   'icon'=>'📋', 'label'=>'View Certifications'],
    'verify'      => ['href'=>'verify.php',       'icon'=>'🔍', 'label'=>'Verify QR / Cert'],
];
?>
<div class="sidebar">
  <div class="sidebar-brand">
    <div style="font-size:28px;margin-bottom:6px">🕌</div>
    <h2>Halal Certification &amp;<br>Verification Hub</h2>
    <p style="font-size:11px;color:rgba(255,255,255,.5);margin-top:4px">SME Management System</p>
  </div>
  <div class="sidebar-user">
    <div class="avatar"><?php echo strtoupper(substr($username,0,1)); ?></div>
    <span><?php echo htmlspecialchars($username); ?></span>
  </div>
  <nav class="sidebar-nav">
    <div class="nav-label">Main</div>
    <a href="homepage.php" class="<?php echo ($active_page==='dashboard')?'active':''; ?>">📊 Dashboard</a>
    <div class="nav-label">Vendors</div>
    <a href="add_vendor.php"   class="<?php echo ($active_page==='add_vendor')?'active':''; ?>">➕ Add Vendor</a>
    <a href="view_vendors.php" class="<?php echo ($active_page==='view_vendors')?'active':''; ?>">🏪 View Vendors</a>
    <div class="nav-label">Certifications</div>
    <a href="add_cert.php"  class="<?php echo ($active_page==='add_cert')?'active':''; ?>">📄 Add Certification</a>
    <a href="view_certs.php" class="<?php echo ($active_page==='view_certs')?'active':''; ?>">📋 View Certifications</a>
    <div class="nav-label">Verification</div>
    <a href="verify.php" class="<?php echo ($active_page==='verify')?'active':''; ?>">🔍 Verify QR / Cert</a>
  </nav>
  <div class="sidebar-footer"><a href="logout.php">🚪 Logout</a></div>
</div>