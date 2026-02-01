<?php
session_start();
require_once "config.php";

$message = '';
$message_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $reason = trim($_POST['reason']);
    $ip = $_SERVER['REMOTE_ADDR'];

    // Validate
    if (empty($name) || empty($email)) {
        $message = 'Please fill in all required fields.';
        $message_type = 'error';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'error';
    } else {
        // Check if already registered
        $check_sql = "SELECT id FROM waitlist WHERE email = ?";
        if ($stmt = mysqli_prepare($conn, $check_sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) > 0) {
                $message = 'You are already on the waitlist!';
                $message_type = 'info';
            } else {
                // Insert into waitlist
                $insert_sql = "INSERT INTO waitlist (name, email, reason, ip_address) VALUES (?, ?, ?, ?)";
                if ($stmt2 = mysqli_prepare($conn, $insert_sql)) {
                    mysqli_stmt_bind_param($stmt2, "ssss", $name, $email, $reason, $ip);
                    if (mysqli_stmt_execute($stmt2)) {
                        $message = 'Success! You\'re on the waitlist. We\'ll email you when ready!';
                        $message_type = 'success';
                    } else {
                        $message = 'Something went wrong. Please try again.';
                        $message_type = 'error';
                    }
                    mysqli_stmt_close($stmt2);
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join TruX Beta Waitlist</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .waitlist-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .waitlist-box {
            background: white;
            padding: 50px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 600px;
            width: 100%;
        }
        .waitlist-box h1 {
            text-align: center;
            color: #667eea;
            margin-bottom: 10px;
            font-size: 36px;
        }
        .waitlist-box .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 16px;
        }
        .beta-badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 20px;
        }
        .feature-list {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .feature-list h3 {
            color: #333;
            margin-bottom: 15px;
        }
        .feature-list ul {
            list-style: none;
            padding: 0;
        }
        .feature-list li {
            padding: 8px 0;
            color: #666;
        }
        .feature-list li:before {
            content: "✓ ";
            color: #667eea;
            font-weight: bold;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="waitlist-container">
        <div class="waitlist-box">
            <div style="text-align: center;">
                <span class="beta-badge">🔥 BETA ACCESS</span>
            </div>
            <h1>Join TruX Beta</h1>
            <p class="subtitle">Be among the first to experience the next generation social platform</p>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <div class="feature-list">
                <h3>What you'll get:</h3>
                <ul>
                    <li>Early access to all features</li>
                    <li>Direct input on development</li>
                    <li>Exclusive beta user badge</li>
                    <li>Priority support</li>
                </ul>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Why do you want to join? (Optional)</label>
                    <textarea name="reason" class="form-control" rows="3" placeholder="Tell us what excites you about TruX..."></textarea>
                </div>
                
                <button type="submit" class="btn-primary">Join Waitlist</button>
            </form>
            
            <p style="text-align: center; margin-top: 20px; color: #999; font-size: 14px;">
                Already have an access code? <a href="signup.php" style="color: #667eea;">Sign up here</a>
            </p>
        </div>
    </div>
</body>
</html>
