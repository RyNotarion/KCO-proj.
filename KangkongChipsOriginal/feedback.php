<?php
ob_start(); // Start output buffering

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require "c:\Users\Nynia Noces\Downloads\PHPMailer-master\PHPMailer-master\src\PHPMailer.php";
require "c:\Users\Nynia Noces\Downloads\PHPMailer-master\PHPMailer-master\src\Exception.php";
require "c:\Users\Nynia Noces\Downloads\PHPMailer-master\PHPMailer-master\src\SMTP.php";

// Database connection
$conn = new mysqli('localhost', 'root', '', 'feedback_db');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Insert feedback and send email
if (isset($_POST['submit'])) {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $message = $conn->real_escape_string($_POST['message']);
    $rating = $conn->real_escape_string($_POST['rating']); // Get the rating

    // Insert into database
    $conn->query("INSERT INTO feedback (name, email, message, rating) VALUES ('$name', '$email', '$message', '$rating')");

    // Send email notification
    $mail = new PHPMailer(true);
    try {
        $mail->SMTPDebug = 2; // Enable verbose debug output
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'raymundnotarion8@gmail.com'; // Your Gmail address
        $mail->Password = 'fdri nisy kmro rfoq'; // Your Gmail App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('raymundnotarion8@gmail.com', 'Feedback System');
        $mail->addAddress('raymundnotarion8@gmail.com'); // Change this to your email

        $mail->isHTML(true);
        $mail->Subject = "New Feedback from $name";
        $mail->Body = nl2br("Name: $name<br>Email: $email<br>Message: $message<br>Rating: $rating Stars");

        $mail->send();
    } catch (Exception $e) {
        echo "Failed to send email. Mailer Error: {$mail->ErrorInfo}";
    }

    header("Location: feedback.php"); // Refresh to show new feedback
    exit();
}

// Delete feedback
if (isset($_POST['delete'])) {
    $delete_id = $conn->real_escape_string($_POST['delete_id']);
    $conn->query("DELETE FROM feedback WHERE id='$delete_id'");
    header("Location: feedback.php"); // Refresh to show updated list
    exit();
}

// Retrieve feedback
$result = $conn->query("SELECT * FROM feedback");
$feedbackExists = false;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback List</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background-color: #f4f7f9;
            margin: 0;
            padding: 0;
            color: #333;
            overflow: hidden; /* Prevent scrolling on body */
        }
        h1 {
            text-align: center;
            color: #2c3e50;
            margin: 20px 0;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        form {
            margin-bottom: 20px;
            padding: 20px;
            border-radius: 8px;
            background-color: #eaecef;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        input[type="text"], input[type="email"], textarea {
            width: calc(100% - 20px);
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 16px;
        }
        button {
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            padding: 10px 15px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #0056b3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            display: none; /* Initially hide the table */
        }
        th, td {
            padding: 15px;
            border: 1px solid #ddd;
            text-align: left;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .delete-button {
            background: transparent; /* Set background to transparent */
            border: 1px solid #e74c3c; /* Add border with the same color */
            color: #e74c3c; /* Keep the text color */
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s, color 0.3s; /* Add transition for color change */
        }
        .delete-button:hover {
            background-color: #e74c3c; /* Change background on hover */
            color: white; /* Change text color on hover */
        }
        .rating {
            margin: 10px 0;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Feedback Form</h1>

    <form method="POST" action="">
        <input type="text" name="name" placeholder="Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <textarea name="message" placeholder="Your message" required rows="4"></textarea>
        <div class="rating">
            <label>Product Rating:</label><br>
            <input type="radio" name="rating" value="1" required> ★
            <input type="radio" name="rating" value="2"> ★★
            <input type="radio" name="rating" value="3"> ★★★
            <input type="radio" name="rating" value="4"> ★★★★
            <input type="radio" name="rating" value="5"> ★★★★★
        </div>
        <button type="submit" name="submit">Submit Feedback</button>
    </form>

    <button id="toggleFeedback" onclick="toggleFeedback()">Show All Feedback</button>
    <h2 style="display:none;" id="feedbackHeader">All Feedback</h2>
    <table id="feedbackTable">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Message</th>
                <th>Rating</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php
        while ($row = $result->fetch_assoc()) {
            $feedbackExists = true;
            echo "<tr>
                    <td>{$row['name']}</td>
                    <td>{$row['email']}</td>
                    <td>{$row['message']}</td>
                    <td>{$row['rating']} Stars</td>
                    <td>
                        <form method='POST' action='' style='display:inline;'>
                            <input type='hidden' name='delete_id' value='{$row['id']}'>
                            <button type='submit' name='delete' class='delete-button'>Delete</button>
                        </form>
                    </td>
                  </tr>";
        }

        if (!$feedbackExists) {
            echo "<script>
                    document.getElementById('feedbackTable').style.display = 'none';
                    document.getElementById('feedbackHeader').style.display = 'none';
                    document.getElementById('toggleFeedback').style.display = 'none';
                  </script>";
        } else {
            echo "<script>document.getElementById('feedbackTable').style.display = 'table';</script>";
        }

        $conn->close();
        ?>
        </tbody>
    </table>
</div>

<script>
    function toggleFeedback() {
        const table = document.getElementById('feedbackTable');
        const header = document.getElementById('feedbackHeader');
        const button = document.getElementById('toggleFeedback');

        if (table.style.display === 'none' || table.style.display === '') {
            table.style.display = 'table'; // Show table
            header.style.display = 'block'; // Show header
            button.innerText = 'Hide Feedback'; // Change button text
        } else {
            table.style.display = 'none'; // Hide table
            header.style.display = 'none'; // Hide header
            button.innerText = 'Show All Feedback'; // Change button text back
        }
    }
</script>
</body>
</html>
