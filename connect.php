<?php
// Database connection
$host = 'localhost';  // MySQL host
$dbname = 'quick_contact';  // Database name
$username = 'your_db_username';  // Your MySQL username
$password = 'your_db_password';  // Your MySQL password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session for login tracking
session_start();

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Login process
    if (isset($_POST['loginUsername']) && isset($_POST['loginPassword'])) {
        $loginUsername = $_POST['loginUsername'];
        $loginPassword = $_POST['loginPassword'];

        // Query to find user in database
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->execute(['username' => $loginUsername]);
        $user = $stmt->fetch();

        // Check if user exists and the password matches
        if ($user && password_verify($loginPassword, $user['password'])) {
            // Successful login: Store user info in session
            $_SESSION['loggedInUser'] = $user;
            echo json_encode(['status' => 'success', 'message' => 'Login successful!']);
        } else {
            // Invalid username or password
            echo json_encode(['status' => 'error', 'message' => 'Invalid username or password.']);
        }
    }

    // Account creation process
    elseif (isset($_POST['createUsername']) && isset($_POST['createPassword']) && isset($_POST['createPhone']) && isset($_POST['createCategory'])) {
        $createUsername = $_POST['createUsername'];
        $createPassword = password_hash($_POST['createPassword'], PASSWORD_DEFAULT); // Hash password
        $createPhone = $_POST['createPhone'];
        $createCategory = $_POST['createCategory'];

        // Insert new user into the database
        $stmt = $pdo->prepare("INSERT INTO users (username, password, phone, category) VALUES (:username, :password, :phone, :category)");
        $stmt->execute([
            'username' => $createUsername,
            'password' => $createPassword,
            'phone' => $createPhone,
            'category' => $createCategory
        ]);

        // Return success response
        echo json_encode(['status' => 'success', 'message' => 'Account created successfully. Please log in.']);
    }

    // Handle contact addition (only if logged in)
    elseif (isset($_POST['contactName']) && isset($_POST['contactPhone']) && isset($_POST['contactCategory'])) {
        if (!isset($_SESSION['loggedInUser'])) {
            die("Unauthorized access.");
        }

        $loggedInUsername = $_SESSION['loggedInUser']['username']; // Use session data
        $contactName = $_POST['contactName'];
        $contactPhone = $_POST['contactPhone'];
        $contactCategory = $_POST['contactCategory'];

        // Insert contact into the database
        $stmt = $pdo->prepare("INSERT INTO contacts (username, contact_name, contact_phone, contact_category) 
                               VALUES (:username, :contact_name, :contact_phone, :contact_category)");
        $stmt->execute([
            'username' => $loggedInUsername,
            'contact_name' => $contactName,
            'contact_phone' => $contactPhone,
            'contact_category' => $contactCategory
        ]);

        echo json_encode(['status' => 'success', 'message' => 'Contact added successfully!']);
    }

    // Handle message sending (only if logged in)
    elseif (isset($_POST['recipientUsername']) && isset($_POST['messageText'])) {
        if (!isset($_SESSION['loggedInUser'])) {
            die("Unauthorized access.");
        }

        $loggedInUsername = $_SESSION['loggedInUser']['username']; // Use session data
        $recipientUsername = $_POST['recipientUsername'];
        $messageText = $_POST['messageText'];

        // Check if recipient exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :recipientUsername");
        $stmt->execute(['recipientUsername' => $recipientUsername]);
        $recipient = $stmt->fetch();

        if ($recipient) {
            // Insert message into the database
            $stmt = $pdo->prepare("INSERT INTO messages (sender, recipient, message_text) 
                                   VALUES (:sender, :recipient, :message_text)");
            $stmt->execute([
                'sender' => $loggedInUsername,
                'recipient' => $recipientUsername,
                'message_text' => $messageText
            ]);

            echo json_encode(['status' => 'success', 'message' => 'Message sent successfully!']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Recipient not found.']);
        }
    }

    // Handle contact search (only if logged in)
    elseif (isset($_GET['searchTerm'])) {
        if (!isset($_SESSION['loggedInUser'])) {
            die("Unauthorized access.");
        }

        $loggedInUsername = $_SESSION['loggedInUser']['username']; // Use session data
        $searchTerm = '%' . $_GET['searchTerm'] . '%';

        $stmt = $pdo->prepare("SELECT * FROM contacts WHERE username = :username AND 
                               (contact_name LIKE :searchTerm OR contact_category LIKE :searchTerm)");
        $stmt->execute([
            'username' => $loggedInUsername,
            'searchTerm' => $searchTerm
        ]);

        $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($contacts);
    }
}
?>
