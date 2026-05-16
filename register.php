<?php
session_start();
require_once 'connect.php';

if (isset($_SESSION['user_id'])) { header("Location: homepage.php"); exit(); }

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if (empty($username)||empty($email)||empty($password)||empty($confirm)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username=? OR email=?");
        $stmt->bind_param("ss",$username,$email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = "Username or email already exists.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins  = $conn->prepare("INSERT INTO users (username,email,password) VALUES (?,?,?)");
            $ins->bind_param("sss",$username,$email,$hash);
            $ins->execute() ? $success = "Account created! You may now login." : $error = "Registration failed. Try again.";
            $ins->close();
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
<title>Register – Halal Hub</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',sans-serif;background:linear-gradient(135deg,#1b5e20,#4caf50);min-height:100vh;display:flex;justify-content:center;align-items:center;padding:20px}
.card{background:#fff;border-radius:16px;padding:40px 36px;width:100%;max-width:420px;box-shadow:0 20px 60px rgba(0,0,0,0.2)}
.logo{text-align:center;margin-bottom:24px}
.logo .icon{font-size:44px}
.logo h1{font-size:20px;color:#1b5e20;font-weight:700;margin-top:6px}
h2{font-size:18px;color:#333;font-weight:700;margin-bottom:18px}
.alert{padding:11px 14px;border-radius:8px;font-size:13px;margin-bottom:16px}
.alert.error{background:#fce4ec;color:#c62828}
.alert.success{background:#e8f5e9;color:#2e7d32}
.form-group{margin-bottom:16px}
label{display:block;font-size:13px;font-weight:600;color:#444;margin-bottom:5px}
input{width:100%;padding:11px 14px;border:1.5px solid #ddd;border-radius:8px;font-size:14px;outline:none;transition:border .2s}
input:focus{border-color:#2e7d32}
.btn{width:100%;padding:13px;background:linear-gradient(135deg,#2e7d32,#1b5e20);color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer;margin-top:4px}
.btn:hover{opacity:.9}
.switch{text-align:center;margin-top:18px;font-size:13px;color:#666}
.switch a{color:#2e7d32;font-weight:600;text-decoration:none}
</style>
</head>
<body>
<div class="card">
  <div class="logo"><div class="icon">🕌</div><h1>Halal Hub</h1></div>
  <h2>Create Account</h2>
  <?php if($error): ?><div class="alert error">⚠️ <?php echo htmlspecialchars($error); ?></div><?php endif; ?>
  <?php if($success): ?><div class="alert success">✅ <?php echo htmlspecialchars($success); ?></div><?php endif; ?>
  <form method="POST">
    <div class="form-group"><label>Username</label><input type="text" name="username" required></div>
    <div class="form-group"><label>Email</label><input type="email" name="email" required></div>
    <div class="form-group"><label>Password</label><input type="password" name="password" required></div>
    <div class="form-group"><label>Confirm Password</label><input type="password" name="confirm_password" required></div>
    <button class="btn" type="submit">Register</button>
  </form>
  <p class="switch">Already have an account? <a href="index.php">Login here</a></p>
</div>
</body>
</html>