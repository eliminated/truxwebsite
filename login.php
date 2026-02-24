<?php
require_once "config.php";

$username = $password = "";
$username_err = $password_err = $login_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter username.";
    } else {
        $username = trim($_POST["username"]);
    }

    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }

    if (empty($username_err) && empty($password_err)) {
        // 1. Update Query to select 6 columns (Added profile_picture, user_role)
        $sql = "SELECT id, username, email, password, profile_picture, user_role FROM users WHERE username = ?";

        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = $username;

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) == 1) {
                    // 2. Update Bind Result to accept 6 variables (MUST MATCH THE QUERY ABOVE)
                    mysqli_stmt_bind_result($stmt, $id, $username, $email, $hashed_password, $profile_picture, $user_role);

                    if (mysqli_stmt_fetch($stmt)) {
                        if (password_verify($password, $hashed_password)) {
                            session_start();

                            // 3. Save all 6 variables to the session
                            $_SESSION["loggedin"] = true;
                            $_SESSION["user_id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["email"] = $email;
                            $_SESSION["profile_picture"] = $profile_picture; // Fixes missing profile icon
                            $_SESSION["user_role"] = $user_role;             // Fixes admin access

                            header("location: index.php");
                            exit;
                        } else {
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else {
                    $login_err = "Invalid username or password.";
                }
            }
            mysqli_stmt_close($stmt);
        }
    }
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - TruX</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <h2>Login</h2>

            <?php
            if (!empty($login_err)) {
                echo '<div class="alert alert-danger">' . $login_err . '</div>';
            }
            ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>" />
                    <span class="invalid-feedback"><?php echo $username_err; ?></span>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" />
                    <span class="invalid-feedback"><?php echo $password_err; ?></span>
                </div>

                <button type="submit" class="btn-primary">Login</button>
            </form>

            <p>Don't have an account? <a href="signup.php">Sign up now</a></p>
        </div>
    </div>
</body>
</html>
