<?php
/**
 * Login Page
 * Faysal Glass And Aluminium Centre
 * 
 * User authentication page
 * Note: Using plain text passwords as per requirements
 */

// Start session
session_start();

// Include database connection
include('includes/database.php');
include('includes/txt.php');

// Redirect if already logged in
if(isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: dashboard/dashboard.php");
    exit();
}

$error = '';
$success = '';

// Handle login form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password']; // Plain text password
    
    // Validation
    if(empty($username) || empty($password)) {
        $error = "Please enter both username and password";
    } else {
        // Check if users table exists
        $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
        if(mysqli_num_rows($table_check) == 0) {
            $error = "System not properly configured. Please contact administrator.";
        } else {
            // Query to check user credentials (plain text comparison)
            $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password' AND status = 1";
            $result = mysqli_query($conn, $query);
            
            if($result && mysqli_num_rows($result) == 1) {
                $user = mysqli_fetch_assoc($result);
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                
                // Update last login time
                $update_query = "UPDATE users SET last_login = NOW() WHERE id = '" . $user['id'] . "'";
                mysqli_query($conn, $update_query);
                
                // Redirect to dashboard
                header("Location: dashboard/dashboard.php");
                exit();
            } else {
                $error = "Invalid username or password";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?php echo $software_name; ?></title>
    
    <!-- Bootstrap 4 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-green: #1e7e34;
            --primary-blue: #0066cc;
            --light-green: #28a745;
            --light-blue: #007bff;
        }
        
        body {
            background: linear-gradient(135deg, #1e7e34 0%, #0066cc 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
            animation: fadeInUp 0.6s ease;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            background: linear-gradient(135deg, #1e7e34, #0066cc);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }
        
        .login-header i {
            font-size: 60px;
            margin-bottom: 15px;
        }
        
        .login-header h3 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        
        .login-header p {
            margin: 5px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        .login-body {
            padding: 40px 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 8px;
            display: block;
        }
        
        .input-group {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .input-group-text {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            color: #1e7e34;
        }
        
        .form-control {
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
            font-size: 14px;
            color: #1a1a1a;
            font-weight: 500;
        }
        
        .form-control:focus {
            border-color: #1e7e34;
            box-shadow: 0 0 0 0.2rem rgba(30,126,52,0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #1e7e34, #0066cc);
            border: none;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 10px;
            width: 100%;
            color: white;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,102,204,0.3);
            color: white;
        }
        
        .alert {
            border-radius: 10px;
            font-weight: 500;
            margin-bottom: 20px;
        }
        
        .login-footer {
            background: #f8f9fc;
            padding: 15px 30px;
            text-align: center;
            border-top: 1px solid #e3e6f0;
        }
        
        .login-footer p {
            margin: 0;
            color: #4a5568;
            font-size: 13px;
            font-weight: 500;
        }
        
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
            color: #6c757d;
        }
        
        .input-group {
            position: relative;
        }
        
        .company-info {
            text-align: center;
            margin-top: 20px;
            color: white;
        }
        
        @media (max-width: 768px) {
            .login-card {
                margin: 20px;
            }
            
            .login-header {
                padding: 30px 20px;
            }
            
            .login-body {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-glass-cheers"></i>
                <h3><?php echo $software_name; ?></h3>
                <p>Login to access your dashboard</p>
            </div>
            
            <div class="login-body">
                <?php if($error != ''): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <?php if($success != ''): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle mr-2"></i> <?php echo $success; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="loginForm">
                    <div class="form-group">
                        <label><i class="fas fa-user mr-2"></i> Username</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fas fa-user"></i>
                                </span>
                            </div>
                            <input type="text" name="username" class="form-control" placeholder="Enter your username" 
                                   autocomplete="off" required autofocus>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-lock mr-2"></i> Password</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fas fa-key"></i>
                                </span>
                            </div>
                            <input type="password" name="password" id="password" class="form-control" 
                                   placeholder="Enter your password" required>
                            <div class="input-group-append">
                                <span class="input-group-text password-toggle" onclick="togglePassword()">
                                    <i class="fas fa-eye" id="toggleIcon"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="rememberMe">
                            <label class="custom-control-label" for="rememberMe">Remember me</label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-login">
                        <i class="fas fa-sign-in-alt mr-2"></i> Login to Dashboard
                    </button>
                </form>
            </div>
            
            <div class="login-footer">
                <p>&copy; <?php echo date('Y'); ?> <?php echo $software_name; ?> - All Rights Reserved</p>
                <p class="mt-2">
                    <small><i class="fas fa-shield-alt"></i> Secure Login System</small>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function togglePassword() {
            var passwordField = document.getElementById("password");
            var toggleIcon = document.getElementById("toggleIcon");
            
            if (passwordField.type === "password") {
                passwordField.type = "text";
                toggleIcon.classList.remove("fa-eye");
                toggleIcon.classList.add("fa-eye-slash");
            } else {
                passwordField.type = "password";
                toggleIcon.classList.remove("fa-eye-slash");
                toggleIcon.classList.add("fa-eye");
            }
        }
        
        // Remember me functionality using localStorage
        $(document).ready(function() {
            // Check if there's a remembered username
            if(localStorage.getItem('rememberedUsername')) {
                $('input[name="username"]').val(localStorage.getItem('rememberedUsername'));
                $('#rememberMe').prop('checked', true);
            }
            
            // Save username when checkbox is checked
            $('#rememberMe').change(function() {
                if($(this).is(':checked')) {
                    var username = $('input[name="username"]').val();
                    if(username) {
                        localStorage.setItem('rememberedUsername', username);
                    }
                } else {
                    localStorage.removeItem('rememberedUsername');
                }
            });
            
            // Update stored username when username field changes
            $('input[name="username"]').on('change keyup', function() {
                if($('#rememberMe').is(':checked')) {
                    var username = $(this).val();
                    if(username) {
                        localStorage.setItem('rememberedUsername', username);
                    }
                }
            });
            
            // Form validation before submit
            $('#loginForm').on('submit', function(e) {
                var username = $('input[name="username"]').val().trim();
                var password = $('input[name="password"]').val().trim();
                
                if(username === '') {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Error!',
                        text: 'Please enter your username',
                        icon: 'error',
                        confirmButtonColor: '#1e7e34'
                    });
                    return false;
                }
                
                if(password === '') {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Error!',
                        text: 'Please enter your password',
                        icon: 'error',
                        confirmButtonColor: '#1e7e34'
                    });
                    return false;
                }
                
                return true;
            });
        });
    </script>
    
    <!-- SweetAlert2 for better error messages -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>
</html>