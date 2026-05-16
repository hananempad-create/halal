<?php
session_start();
require_once 'connect.php';

if (isset($_SESSION['user_id'])) {
    header("Location: homepage.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        $stmt = $conn->prepare("SELECT id, username, email, password FROM users WHERE username=? OR email=?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email']    = $user['email'];
                header("Location: homepage.php");
                exit();
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login – Halal Hub</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',sans-serif;background:linear-gradient(135deg,#1b5e20,#4caf50);min-height:100vh;display:flex;justify-content:center;align-items:center;padding:20px}
.card{background:#fff;border-radius:16px;padding:40px 36px;width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,0.2)}
.logo{text-align:center;margin-bottom:28px}
.logo .icon{font-size:48px}
.logo h1{font-size:22px;color:#1b5e20;font-weight:700;margin-top:8px}
.logo p{font-size:12px;color:#888;margin-top:3px}
h2{font-size:18px;color:#333;font-weight:700;margin-bottom:20px}
.alert{padding:11px 14px;border-radius:8px;font-size:13px;margin-bottom:18px;background:#fce4ec;color:#c62828}
.form-group{margin-bottom:18px}
label{display:block;font-size:13px;font-weight:600;color:#444;margin-bottom:6px}
input{width:100%;padding:11px 14px;border:1.5px solid #ddd;border-radius:8px;font-size:14px;outline:none;transition:border .2s}
input:focus{border-color:#2e7d32}
.btn{width:100%;padding:13px;background:linear-gradient(135deg,#2e7d32,#1b5e20);color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer;margin-top:4px;transition:opacity .2s}
.btn:hover{opacity:.9}
.switch{text-align:center;margin-top:20px;font-size:13px;color:#666}
.switch a{color:#2e7d32;font-weight:600;text-decoration:none}
</style>
</head>
<body>
<div class="card">
  <div class="logo">
    <div class="icon">🕌</div>
    <h1>Halal Hub</h1>
    <p>Certification &amp; Verification System</p>
  </div>
  <h2>Sign In</h2>
  <?php if($error): ?><div class="alert">⚠️ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
  <form method="POST">
    <div class="form-group">
      <label>Username or Email</label>
      <input type="text" name="username" placeholder="Enter username or email" required>
    </div>
    <div class="form-group">
      <label>Password</label>
      <input type="password" name="password" placeholder="Enter password" required>
    </div>
    <button class="btn" type="submit">Login</button>
  </form>
  <p class="switch">No account? <a href="register.php">Register here</a></p>
</div>
</body>
</html>