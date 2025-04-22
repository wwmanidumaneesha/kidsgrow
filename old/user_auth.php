<?php
session_start();

// Database connection details
$dsn = "pgsql:host=ep-purple-sea-a1d1bece-pooler.ap-southeast-1.aws.neon.tech;port=5432;dbname=neondb;sslmode=require";
$db_user = "neondb_owner";
$db_password = "npg_39CVAQbvIlOy";

try {
    $pdo = new PDO($dsn, $db_user, $db_password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    die("Database connection failed: " . $e->getMessage());
}

// Utility function for input validation
function validateInput($input, $type) {
    if (empty($input)) {
        return "$type is required";
    }
    if ($type === 'email' && !filter_var($input, FILTER_VALIDATE_EMAIL)) {
        return "Invalid email format";
    }
    if ($type === 'password' && (strlen($input) < 8 || !preg_match('/[A-Z]/', $input) || !preg_match('/[a-z]/', $input) || !preg_match('/[0-9]/', $input) || !preg_match('/[\W_]/', $input))) {
        return "Password must be at least 8 characters long and include uppercase, lowercase, a number, and a special character.";
    }
    return null;
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'sign_up') {
        $full_name = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $name_error = validateInput($full_name, 'Full Name');
        $email_error = validateInput($email, 'Email');
        $password_error = validateInput($password, 'Password');

        if ($name_error || $email_error || $password_error) {
            $_SESSION['error_message'] = $name_error ?: ($email_error ?: $password_error);
            header('Location: signin.php');
            exit;
        }

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password) VALUES (:full_name, :email, :password)");
            $stmt->execute([
                ':full_name' => $full_name,
                ':email'    => $email,
                ':password' => $hashed_password,
            ]);

            $_SESSION['success_message'] = 'Registration successful. Sending welcome email...';
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = $full_name;

            // Redirect to the sign-in page
            header('Location: signin.php');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23505') {
                $_SESSION['error_message'] = 'Email already exists.';
                header('Location: signin.php');
            } else {
                $_SESSION['error_message'] = 'An unexpected error occurred.';
                header('Location: signin.php');
            }
            exit;
        }
    }

    if ($action === 'sign_in') {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $email_error = validateInput($email, 'Email');
        $password_error = validateInput($password, 'Password');

        if ($email_error || $password_error) {
            $_SESSION['error_message'] = $email_error ?: $password_error;
            header('Location: signin.php');
            exit;
        }

        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
            
                // Redirect based on user role
                if ($user['role'] === 'Parent') {
                    header('Location: ./u/dashboard.php');
                } else {
                    header('Location: child_profile.php');
                }
                exit;
                } else {
                    $_SESSION['error_message'] = 'Invalid email or password.';
                    header('Location: signin.php');
                    exit;
                }
            } catch (PDOException $e) {
            http_response_code(500);
            $_SESSION['error_message'] = 'An unexpected error occurred.';
            header('Location: signin.php');
        }
        exit;
    }

    $_SESSION['error_message'] = 'Invalid action specified.';
    header('Location: signin.php');
    exit;
} else {
    http_response_code(405);
    $_SESSION['error_message'] = 'Invalid request method.';
    header('Location: signin.php');
    exit;
}
?>
