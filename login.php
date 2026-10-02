<?php
session_start();
include 'includes/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password, role, branch_id, tenant_id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user && password_verify($password, $user['password'])) {
        // SaaS Expiry Check
        if ($user['role'] !== 'super_admin') {
            $t_id = (int)$user['tenant_id'];
            $t_res = $conn->query("SELECT subscription_status, subscription_ends_at FROM tenants WHERE id = $t_id");
            if ($t_res && $t_res->num_rows > 0) {
                $tenant = $t_res->fetch_assoc();
                if ($tenant['subscription_status'] === 'suspended') {
                    $error = "Your business account is suspended. Please contact Super Admin.";
                    goto login_error;
                }
                if (!empty($tenant['subscription_ends_at']) && strtotime($tenant['subscription_ends_at']) < time()) {
                    $error = "Your subscription has expired (Due: " . date('M d, Y', strtotime($tenant['subscription_ends_at'])) . "). Please contact Super Admin to renew your plan.";
                    goto login_error;
                }
            }
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $user['role'];
        $_SESSION['branch_id'] = $user['branch_id'];
        $_SESSION['tenant_id'] = $user['tenant_id'];
        echo "<script>sessionStorage.setItem('strict_session', 'active'); window.location.href = 'index.php';</script>";
        exit();
    } else {
        $error = "Invalid username or password!";
    }
    login_error:
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - POS System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="manifest" href="manifest.json?v=2">
    <link rel="icon" type="image/svg+xml" href="assets/app-icon.svg">
    <link rel="icon" type="image/png" sizes="192x192" href="assets/icon-192.png">
    <link rel="shortcut icon" href="assets/app-icon.svg">
    <meta name="theme-color" content="#4f46e5">
    <style>
        body { 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            min-height: 100vh;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            font-family: 'Inter', sans-serif;
            margin: 0;
        }
        .login-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 2rem;
        }
        .login-box { 
            background: white; 
            padding: 3rem 2.5rem; 
            border-radius: 16px; 
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); 
            width: 100%; 
            text-align: center; 
            position: relative;
        }
        .logo-container {
            margin-bottom: 2rem;
        }
        .logo-container img {
            height: 60px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .login-box h2 { 
            margin-bottom: 0.5rem; 
            color: #1e293b; 
            font-weight: 700;
            font-size: 1.5rem;
        }
        .login-box p.subtitle {
            color: #64748b;
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }
        .form-group { 
            margin-bottom: 1.25rem; 
            text-align: left; 
        }
        .form-group label { 
            display: block; 
            margin-bottom: 0.5rem; 
            font-weight: 600; 
            color: #475569;
            font-size: 0.9rem;
        }
        .form-group input { 
            width: 100%; 
            padding: 0.75rem 1rem; 
            border: 2px solid #e2e8f0; 
            border-radius: 8px; 
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            font-family: 'Inter', sans-serif;
        }
        .form-group input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }
        .error { 
            background-color: #fef2f2;
            color: #ef4444; 
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 1.5rem; 
            font-size: 0.9rem; 
            font-weight: 500;
            border: 1px solid #fecaca;
        }
        .btn-primary {
            width: 100%;
            padding: 0.75rem;
            background-color: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.1s;
        }
        .btn-primary:hover {
            background-color: #1d4ed8;
        }
        .btn-primary:active {
            transform: scale(0.98);
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-box">
            <div class="logo-container">
                <?php if(file_exists('assets/images/logo.jpg')): ?>
                <img src="assets/images/logo.jpg" alt="Logo">
                <?php else: ?>
                <span style="font-size: 2.5rem;">🛍️</span>
                <?php endif; ?>
            </div>
            <h2>Welcome Back</h2>
            <p class="subtitle">Please enter your details to sign in</p>
            
            <?php if($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Enter your username" required autofocus>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-primary" style="margin-top: 1rem;">Sign In</button>
            </form>
        </div>
    </div>
</body>
</html>
