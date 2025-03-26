<?php
session_start();
require_once 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password']; // In a real system, you'd hash this
    
    // Check if this is a valid interviewer
    $stmt = $conn->prepare("SELECT id, name FROM interviewers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $interviewer = $result->fetch_assoc();
        $_SESSION['interviewer_id'] = $interviewer['id'];
        $_SESSION['interviewer_name'] = $interviewer['name'];
        header("Location: faculty.php");
        exit();
    } else {
        $error = "Invalid credentials or you're not registered as an interviewer";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interviewer Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white shadow-md rounded-lg p-6 w-96">
        <h2 class="text-2xl font-bold text-green-700 mb-6 text-center">Interviewer Login</h2>
        
        <?php if(isset($error)): ?>
            <div class="bg-red-100 text-red-700 p-2 mb-4 rounded">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST" class="space-y-4">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" id="email" name="email" required 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:ring-green-300">
            </div>
            
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <input type="password" id="password" name="password" required 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring focus:ring-green-300">
            </div>
            
            <div class="pt-2">
                <button type="submit" class="w-full bg-green-700 text-white px-4 py-2 rounded-md font-medium hover:bg-green-600">
                    Login
                </button>
            </div>
        </form>
    </div>
</body>
</html>