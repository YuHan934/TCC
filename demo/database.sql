CREATE DATABASE IF NOT EXISTS hotel_db;
USE hotel_db;

-- Room Types Table
CREATE TABLE room_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    price_per_night DECIMAL(10,2) NOT NULL,
    capacity INT NOT NULL
);

-- Rooms Table
CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_number VARCHAR(10) NOT NULL UNIQUE,
    room_type_id INT,
    status ENUM('available', 'occupied', 'maintenance') DEFAULT 'available',
    FOREIGN KEY (room_type_id) REFERENCES room_types(id)
);

-- Bookings Table
CREATE TABLE bookings (
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
);

-- Insert sample room types
INSERT INTO room_types (name, description, price_per_night, capacity) VALUES
('Standard', 'Comfortable room with basic amenities', 100.00, 2),
('Deluxe', 'Spacious room with city view', 150.00, 2),
('Suite', 'Luxury suite with separate living area', 250.00, 4),
('Family Room', 'Large room perfect for families', 200.00, 6);

-- Insert sample rooms
INSERT INTO rooms (room_number, room_type_id) VALUES
('101', 1), ('102', 1), ('103', 1),
('201', 2), ('202', 2),
('301', 3),
('401', 4), ('402', 4);