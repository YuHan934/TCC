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
$available_rooms = null;
$bookings = null;
$error_message = '';
$success_message = '';

// Handle form submissions for booking and updates
try {
    // Create connection
    $conn = mysqli_connect($host, $username, $password);
    if (!$conn) {
        throw new Exception("Connection failed: " . mysqli_connect_error());
    }

    // Create and select database
    if (!mysqli_select_db($conn, $database)) {
        $sql = "CREATE DATABASE IF NOT EXISTS $database";
        if (!mysqli_query($conn, $sql)) {
            throw new Exception("Error creating database: " . mysqli_error($conn));
        }
        mysqli_select_db($conn, $database);
    }

    // Check if tables need to be created
    $result = mysqli_query($conn, "SHOW TABLES LIKE 'room_types'");
    $tableExists = mysqli_num_rows($result) > 0;

    if (!$tableExists) {
        // Create tables
        $queries = [
            "CREATE TABLE room_types (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(50) NOT NULL,
                description TEXT,
                price_per_night DECIMAL(10,2) NOT NULL,
                capacity INT NOT NULL
            )",
            "CREATE TABLE rooms (
                id INT AUTO_INCREMENT PRIMARY KEY,
                room_number VARCHAR(10) NOT NULL UNIQUE,
                room_type_id INT,
                status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
                FOREIGN KEY (room_type_id) REFERENCES room_types(id)
            )",
            "CREATE TABLE bookings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                guest_name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                phone VARCHAR(20),
                room_id INT,
                check_in DATE NOT NULL,
                check_out DATE NOT NULL,
                total_price DECIMAL(10,2) NOT NULL,
                num_guests INT NOT NULL,
                status ENUM('confirmed', 'checked_in', 'checked_out', 'cancelled') DEFAULT 'confirmed',
                special_requests TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (room_id) REFERENCES rooms(id)
            )",
            "INSERT INTO room_types (name, description, price_per_night, capacity) VALUES
                ('Standard', 'Comfortable room with basic amenities', 100.00, 2),
                ('Deluxe', 'Spacious room with city view', 150.00, 2),
                ('Suite', 'Luxury suite with separate living area', 250.00, 4),
                ('Family Room', 'Large room perfect for families', 200.00, 6)",
            "INSERT INTO rooms (room_number, room_type_id) VALUES
                ('101', 1), ('102', 1), ('103', 1),
                ('201', 2), ('202', 2),
                ('301', 3),
                ('401', 4), ('402', 4)"
        ];

        foreach ($queries as $sql) {
            if (!mysqli_query($conn, $sql)) {
                throw new Exception("Error creating tables: " . mysqli_error($conn));
            }
        }
    }

    // Handle booking form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book'])) {
        $guest_name = mysqli_real_escape_string($conn, $_POST['guest_name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $room_id = (int)$_POST['room_id'];
        $check_in = mysqli_real_escape_string($conn, $_POST['check_in']);
        $check_out = mysqli_real_escape_string($conn, $_POST['check_out']);
        $num_guests = (int)$_POST['num_guests'];
        $special_requests = mysqli_real_escape_string($conn, $_POST['special_requests']);

        // Calculate total price
        $sql = "SELECT rt.price_per_night FROM rooms r 
                JOIN room_types rt ON r.room_type_id = rt.id 
                WHERE r.id = $room_id";
        $result = mysqli_query($conn, $sql);
        $room = mysqli_fetch_assoc($result);
        $nights = (strtotime($check_out) - strtotime($check_in)) / (60 * 60 * 24);
        $total_price = $room['price_per_night'] * $nights;

        $sql = "INSERT INTO bookings (guest_name, email, phone, room_id, check_in, check_out, 
                total_price, num_guests, special_requests) 
                VALUES ('$guest_name', '$email', '$phone', $room_id, '$check_in', '$check_out', 
                $total_price, $num_guests, '$special_requests')";

        if (mysqli_query($conn, $sql)) {
            mysqli_query($conn, "UPDATE rooms SET status='occupied' WHERE id=$room_id");
            $success_message = "Booking confirmed successfully!";
        } else {
            $error_message = "Error creating booking: " . mysqli_error($conn);
        }
    }

    // Handle delete booking
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_booking'])) {
        $delete_id = (int)$_POST['delete_id'];

        // Delete the booking
        $sql = "DELETE FROM bookings WHERE id = $delete_id";
        if (mysqli_query($conn, $sql)) {
            // Also update the room status to available
            $sql = "UPDATE rooms SET status = 'available' WHERE id = (SELECT room_id FROM bookings WHERE id = $delete_id)";
            mysqli_query($conn, $sql);
            $success_message = "Booking deleted successfully!";
        } else {
            $error_message = "Error deleting booking: " . mysqli_error($conn);
        }
    }

    // Get available rooms
    $sql = "SELECT r.*, rt.name as room_type, rt.price_per_night, rt.capacity, rt.description 
            FROM rooms r 
            JOIN room_types rt ON r.room_type_id = rt.id 
            WHERE r.status = 'available'
            ORDER BY r.room_number";
    $available_rooms = mysqli_query($conn, $sql);

    // Get all bookings
    $sql = "SELECT b.*, r.room_number, rt.name as room_type 
            FROM bookings b 
            JOIN rooms r ON b.room_id = r.id 
            JOIN room_types rt ON r.room_type_id = rt.id 
            ORDER BY b.check_in";
    $bookings = mysqli_query($conn, $sql);

} catch (Exception $e) {
    $error_message = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Booking System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .room-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .room-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background: white;
        }
        .booking-form {
            max-width: 600px;
            margin: 0 auto;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .price {
            font-size: 1.2em;
            color: #28a745;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <h1>Hotel Booking System</h1>

    <?php if(!empty($error_message)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <?php if(!empty($success_message)): ?>
        <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <!-- Available Rooms -->
    <div class="container">
        <h2>Available Rooms</h2>
        <div class="room-grid">
            <?php if($available_rooms && mysqli_num_rows($available_rooms) > 0): ?>
                <?php while($room = mysqli_fetch_assoc($available_rooms)): ?>
                    <div class="room-card">
                        <h3>Room <?php echo htmlspecialchars($room['room_number']); ?></h3>
                        <p><strong><?php echo htmlspecialchars($room['room_type']); ?></strong></p>
                        <p><?php echo htmlspecialchars($room['description']); ?></p>
                        <p>Capacity: <?php echo htmlspecialchars($room['capacity']); ?> persons</p>
                        <p class="price">$<?php echo htmlspecialchars($room['price_per_night']); ?> per night</p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No rooms available at the moment.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Booking Form -->
    <div class="container">
        <h2>Make a Reservation</h2>
        <form method="POST" class="booking-form">
            <div class="form-group">
                <label for="guest_name">Guest Name:</label>
                <input type="text" id="guest_name" name="guest_name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone:</label>
                <input type="tel" id="phone" name="phone" required>
            </div>
            
            <div class="form-group">
                <label for="room_id">Select Room:</label>
                <select id="room_id" name="room_id" required>
                    <?php 
                    if($available_rooms) {
                        mysqli_data_seek($available_rooms, 0);
                        while($room = mysqli_fetch_assoc($available_rooms)): 
                    ?>
                        <option value="<?php echo $room['id']; ?>">
                            Room <?php echo htmlspecialchars($room['room_number']); ?> - 
                            <?php echo htmlspecialchars($room['room_type']); ?> 
                            ($<?php echo htmlspecialchars($room['price_per_night']); ?>/night)
                        </option>
                    <?php 
                        endwhile;
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="check_in">Check-in Date:</label>
                <input type="date" id="check_in" name="check_in" min="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="check_out">Check-out Date:</label>
                <input type="date" id="check_out" name="check_out" min="<?php echo date('Y-m-d'); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="num_guests">Number of Guests:</label>
                <input type="number" id="num_guests" name="num_guests" min="1" required>
            </div>
            
            <div class="form-group">
                <label for="special_requests">Special Requests:</label>
                <textarea id="special_requests" name="special_requests" rows="4"></textarea>
            </div>
            
            <button type="submit" name="book" class="btn">Book Now</button>
        </form>
    </div>

    <!-- Current Bookings -->
    <div class="container">
        <h2>Current Bookings</h2>
        <?php if($bookings && mysqli_num_rows($bookings) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Guest Name</th>
                        <th>Room</th>
                        <th>Check-in</th>
                        <th>Check-out</th>
                        <th>Total Price</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($booking = mysqli_fetch_assoc($bookings)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($booking['guest_name']); ?></td>
                            <td>Room <?php echo htmlspecialchars($booking['room_number']); ?> 
                                (<?php echo htmlspecialchars($booking['room_type']); ?>)</td>
                            <td><?php echo htmlspecialchars($booking['check_in']); ?></td>
                            <td><?php echo htmlspecialchars($booking['check_out']); ?></td>
                            <td>$<?php echo htmlspecialchars($booking['total_price']); ?></td>
                            <td><?php echo htmlspecialchars($booking['status']); ?></td>
                            <td>
                                <a href="edit_booking.php?id=<?php echo $booking['id']; ?>" class="btn">Edit</a>
                                <form method="POST" action="" style="display:inline;">
                                    <input type="hidden" name="delete_id" value="<?php echo $booking['id']; ?>" />
                                    <button type="submit" name="delete_booking" class="btn" style="background-color: #dc3545;">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No current bookings.</p>
        <?php endif; ?>
    </div>
</body>
</html>