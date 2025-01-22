<?php
define("DBNAME", "Menu");
define("DBHOST", "localhost");
define("DBUSER", "root");
define("DBPASS", "TCC12345");
define("ADMIN_PUBLIC_URL", "http://localhost/TCC/demo/mainpage.php");

// Create the database connection
$conn = new mysqli(DBHOST, DBUSER, DBPASS, DBNAME);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>