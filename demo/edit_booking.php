<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "hotel_db";

// Initialize variables
$booking_details = null;
$success_message = '';
$error_message = '';

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Check if booking ID is passed in the URL
if (isset($_GET['id'])) {
    $booking_id = (int)$_GET['id'];

    // Fetch the booking details
    $sql = "SELECT b.*, r.room_number, rt.name as room_type, rt.capacity, rt.price_per_night
            FROM bookings b 
            JOIN rooms r ON b.room_id = r.id 
            JOIN room_types rt ON r.room_type_id = rt.id 
            WHERE b.id = $booking_id";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $booking_details = mysqli_fetch_assoc($result);
    } else {
        $error_message = "Booking not found.";
    }
} else {
    $error_message = "No booking ID provided.";
}

// Handle form submission for editing booking
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_booking'])) {
    $guest_name = mysqli_real_escape_string($conn, $_POST['guest_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $check_in = mysqli_real_escape_string($conn, $_POST['check_in']);
    $check_out = mysqli_real_escape_string($conn, $_POST['check_out']);
    $num_guests = (int)$_POST['num_guests'];
    $special_requests = mysqli_real_escape_string($conn, $_POST['special_requests']);

    // Calculate total price
    $room_id = $booking_details['room_id']; // Keep the same room ID
    $sql = "SELECT price_per_night FROM room_types WHERE id = (SELECT room_type_id FROM rooms WHERE id = $room_id)";
    $result = mysqli_query($conn, $sql);
    $room = mysqli_fetch_assoc($result);
    $nights = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);
    $total_price = $room['price_per_night'] * $nights;

    // Update booking in the database
    $sql = "UPDATE bookings SET guest_name = '$guest_name', email = '$email', phone = '$phone', 
            check_in = '$check_in', check_out = '$check_out', total_price = $total_price, 
            num_guests = $num_guests, special_requests = '$special_requests' 
            WHERE id = $booking_id";

    if (mysqli_query($conn, $sql)) {
        $success_message = "Booking updated successfully!";
    } else {
        $error_message = "Error updating booking: " . mysqli_error($conn);
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Booking</title>
    <style>
        /* General styling for the page */
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f9;
        }
        h1 {
            color: #333;
        }
        .error-message, .success-message {
            padding: 10px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
        }
        .form-group {
            margin: 10px 0;
        }
        label {
            font-weight: bold;
        }
        input[type="text"], input[type="email"], input[type="tel"], input[type="date"], input[type="number"], textarea {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 48%;
            margin: 10px 1%;
            display: inline-block;
        }
        button:hover {
            background-color: #0056b3;
        }
        .back-btn {
            padding: 10px 20px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            width: 48%;
            margin: 10px 1%;
            display: inline-block;
            text-align: center;
        }
        .back-btn:hover {
            background-color: #218838;
        }
        .button-container {
            text-align: center;
        }
    </style>
</head>
<body>

    <h1>Edit Booking</h1>

    <!-- Display Error or Success Messages -->
    <?php if(!empty($error_message)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <?php if(!empty($success_message)): ?>
        <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <?php if ($booking_details): ?>
        <form method="POST" class="edit-booking-form">
            <div class="form-group">
                <label for="guest_name">Guest Name:</label>
                <input type="text" id="guest_name" name="guest_name" value="<?php echo htmlspecialchars($booking_details['guest_name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($booking_details['email']); ?>" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone:</label>
                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($booking_details['phone']); ?>">
            </div>

            <div class="form-group">
                <label for="check_in">Check-in Date:</label>
                <input type="date" id="check_in" name="check_in" value="<?php echo htmlspecialchars($booking_details['check_in']); ?>" min="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="form-group">
                <label for="check_out">Check-out Date:</label>
                <input type="date" id="check_out" name="check_out" value="<?php echo htmlspecialchars($booking_details['check_out']); ?>" min="<?php echo date('Y-m-d'); ?>" required>
            </div>

            <div class="form-group">
                <label for="num_guests">Number of Guests:</label>
                <input type="number" id="num_guests" name="num_guests" value="<?php echo htmlspecialchars($booking_details['num_guests']); ?>" min="1" required>
            </div>

            <div class="form-group">
                <label for="special_requests">Special Requests:</label>
                <textarea id="special_requests" name="special_requests" rows="4"><?php echo htmlspecialchars($booking_details['special_requests']); ?></textarea>
            </div>

            <div class="button-container">
                <button type="submit" name="update_booking">Update Booking</button>
                <a href="mainpage.php" class="back-btn">Back to Main Page</a>
            </div>
        </form>
    <?php endif; ?>

</body>
</html>

<?php
// Close database connection
mysqli_close($conn);
?>
