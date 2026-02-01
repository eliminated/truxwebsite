<?php
require_once "../config.php";
session_start();

// Only allow admin users
if (!isset($_SESSION["loggedin"]) || $_SESSION["user_role"] !== 'admin') {
    header("location: ../index.php");
    exit;
}

// Handle approve action
if (isset($_POST['approve'])) {
    $waitlist_id = $_POST['waitlist_id'];
    $access_code = bin2hex(random_bytes(4)); // Generate 8-char code

    $sql = "UPDATE waitlist SET status = 'approved', access_code = ?, approved_at = NOW() WHERE id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "si", $access_code, $waitlist_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // TODO: Send email with access code
        $message = "Approved! Access code: $access_code";
    }
}

// Fetch waitlist
$waitlist_query = "SELECT * FROM waitlist ORDER BY requested_at DESC";
$waitlist_result = mysqli_query($conn, $waitlist_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Waitlist Manager - TruX</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .waitlist-table {
            width: 100%;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .waitlist-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .waitlist-table th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px;
            text-align: left;
        }
        .waitlist-table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .btn-small {
            padding: 6px 15px;
            font-size: 13px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-weight: 500;
        }
        .btn-approve {
            background: #28a745;
            color: white;
        }
        .btn-reject {
            background: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container" style="max-width: 1200px; margin: 30px auto; padding: 20px;">
        <h1>Waitlist Manager</h1>
        
        <?php if (isset($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="waitlist-table">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Reason</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Access Code</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($waitlist_result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars(substr($row['reason'], 0, 50)); ?>...</td>
                            <td><?php echo date('d/m/Y', strtotime($row['requested_at'])); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $row['status']; ?>">
                                    <?php echo ucfirst($row['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['access_code']): ?>
                                    <code><?php echo $row['access_code']; ?></code>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['status'] === 'pending'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="waitlist_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" name="approve" class="btn-small btn-approve">Approve</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
