<?php
// --- 1. DATABASE CONNECTION ---
$dbuser = "root";
$dbpass = "";
$host = "localhost";
$db = "hostel";

$mysqli = new mysqli($host, $dbuser, $dbpass, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// --- 2. HANDLE FORM SUBMISSION ---
$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $mysqli->real_escape_string($_POST['user_name']);
    $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
    $review = $mysqli->real_escape_string($_POST['review_text']);

    if (!empty($name) && !empty($review) && $rating > 0) {
        $sql = "INSERT INTO user_reviews (user_name, rating, review_text) VALUES ('$name', '$rating', '$review')";
        if ($mysqli->query($sql) === TRUE) {
            // Redirect to prevent resubmission on refresh
            header("Location: " . $_SERVER['PHP_SELF']); 
            exit();
        } else {
            $message = "<p style='color:red; text-align:center;'>Error: " . $mysqli->error . "</p>";
        }
    } else {
        $message = "<p style='color:orange; text-align:center;'>Please fill all fields and select a star rating.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Reviews | Hostel Management</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
    /* --- RESET & BASIC --- */
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Poppins", sans-serif; }
    
    body {
        background: linear-gradient(135deg, #181818, #202020);
        color: white;
        display: flex; /* Flex layout for Sidebar + Content */
        min-height: 100vh;
    }

    /* --- SIDEBAR STYLES --- */
    .sidebar {
        width: 250px;
        background: rgba(0, 0, 0, 0.8);
        border-right: 1px solid rgba(0, 255, 242, 0.2);
        padding: 20px;
        position: fixed;
        height: 100%;
        backdrop-filter: blur(10px);
        display: flex;
        flex-direction: column;
        z-index: 100;
    }

    .sidebar h2 {
        color: #00fff2;
        margin-bottom: 40px;
        font-size: 24px;
        text-align: center;
        text-shadow: 0 0 10px rgba(0, 255, 242, 0.5);
    }

    .sidebar a {
        text-decoration: none;
        color: white;
        padding: 15px;
        margin-bottom: 10px;
        border-radius: 8px;
        transition: 0.3s;
        display: block;
    }

    .sidebar a:hover, .sidebar a.active {
        background: rgba(0, 255, 242, 0.1);
        color: #00fff2;
        box-shadow: 0 0 10px rgba(0, 255, 242, 0.2);
    }

    /* --- MAIN CONTENT WRAPPER --- */
    .main-content {
        margin-left: 250px; /* Space for sidebar */
        width: calc(100% - 250px);
        display: flex;
        flex-direction: column;
    }

    /* --- HEADER STYLES --- */
    .header {
        height: 70px;
        background: rgba(255, 255, 255, 0.05);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 40px;
        backdrop-filter: blur(5px);
        position: sticky;
        top: 0;
        z-index: 99;
    }

    .header h3 {
        color: #00fff2;
        font-weight: 500;
        font-size: 20px;
    }

    .header .user-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .header .user-info span {
        font-weight: 500;
    }

    .header .user-avatar {
        width: 40px; height: 40px;
        border-radius: 50%;
        background: #00fff2;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #000;
        font-weight: bold;
    }

    /* --- PAGE CONTENT CONTAINER --- */
    .page-container {
        padding: 40px;
        flex: 1;
    }

    h1 {
        text-align: center;
        font-size: 38px;
        margin-bottom: 30px;
        text-shadow: 0 0 15px #00fff2;
    }

    .container {
        max-width: 900px;
        margin: auto;
    }

    /* --- REVIEW FORM --- */
    .review-form {
        background: rgba(255,255,255,0.08);
        padding: 25px;
        border-radius: 18px;
        backdrop-filter: blur(12px);
        box-shadow: 0 0 15px rgba(0,255,255,0.25);
        margin-bottom: 40px;
        transition: 0.3s;
    }

    .review-form:hover { box-shadow: 0 0 25px rgba(0,255,255,0.6); }
    .review-form label { font-size: 18px; color: #00fff2; font-weight: 500; }

    .review-input, .review-textarea {
        width: 100%;
        padding: 12px;
        margin-top: 8px;
        margin-bottom: 20px;
        border-radius: 12px;
        background: rgba(255,255,255,0.1);
        border: 1px solid rgba(255,255,255,0.25);
        color: white;
        font-size: 16px;
        outline: none;
    }
    .review-textarea { height: 120px; resize: none; }

    /* --- STARS --- */
    .stars {
        display: flex; gap: 10px; margin-bottom: 15px;
        font-size: 28px; cursor: pointer; color: #777;
    }
    .stars .star:hover, .stars .star.active { color: #00fff2; text-shadow: 0 0 12px #00fff2; }

    /* --- BUTTON --- */
    .btn {
        background: #00fff2; color: #000; padding: 12px 22px;
        border: none; border-radius: 12px; font-size: 18px;
        font-weight: 600; cursor: pointer; transition: 0.3s; width: 100%;
    }
    .btn:hover { background: white; box-shadow: 0 0 15px #00fff2; }

    /* --- REVIEW LIST (DYNAMIC) --- */
    .review-card {
        background: rgba(255,255,255,0.07);
        padding: 20px;
        border-radius: 16px;
        margin-bottom: 20px;
        backdrop-filter: blur(10px);
        transition: 0.3s;
        animation: fadeIn 0.6s ease;
        border-left: 4px solid #00fff2;
    }
    .review-card:hover { transform: translateY(-6px); box-shadow: 0 0 18px rgba(0,255,255,0.6); }
    .review-card h3 { margin-bottom: 8px; font-size: 20px; color: #00fff2; display: flex; justify-content: space-between;}
    .review-card .date { font-size: 12px; color: #aaa; font-weight: normal; }
    .review-card p { margin-bottom: 8px; line-height: 1.6; }
    .rating-display { color: gold; font-size: 20px; margin-bottom: 6px; }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<script>
    function selectStar(index) {
        let stars = document.querySelectorAll(".star");
        stars.forEach((s, i) => {
            if (i < index) s.classList.add("active");
            else s.classList.remove("active");
        });
        document.getElementById("rating").value = index;
    }
</script>
</head>
<body>

    <div class="sidebar">
        <h2>User Review</h2>
        <a href="dashboard.php">Dashboard</a>
        <a href="book-hostel.php">Book Resident Details</a>
        <a href="room-details.php">Room Details</a>
        <a href="register-complaint.php">Complaint Registration</a>
        <a href="my-complaints.php"> Registered Complaints </a>
        <a href="feedback.php" class="active"> Feedback </a>
        <a href="my-profile.php"> My Profile </a>
        <a href="change-password.php">Change Password</a>
        <a href="access-log.php">Access log</a>
    </div>

    <div class="main-content">
        
        <div class="header">
            <h3></h3>
            <div class="user-info">
                <a href="logout.php"><button style="background-color: #00fff2; height: 30px;width: 80px;">Logout</button></a>
            </div>
        </div>

        <div class="page-container">
            <h1>User Feedback & Reviews</h1>
            
            <div class="container">
                <?php echo $message; ?>

                <form class="review-form" method="POST" action="">
                    <label>Your Name</label>
                    <input type="text" name="user_name" class="review-input" placeholder="Enter your name" required>

                    <label>Rate Our Service</label>
                    <div class="stars">
                        <span class="star" onclick="selectStar(1)">★</span>
                        <span class="star" onclick="selectStar(2)">★</span>
                        <span class="star" onclick="selectStar(3)">★</span>
                        <span class="star" onclick="selectStar(4)">★</span>
                        <span class="star" onclick="selectStar(5)">★</span>
                    </div>
                    <input type="hidden" name="rating" id="rating" required>

                    <label>Your Review</label>
                    <textarea name="review_text" class="review-textarea" placeholder="Write your feedback..." required></textarea>

                    <button type="submit" class="btn">Submit Review</button>
                </form>

                <div class="review-list">
                    <?php
                    $sql_fetch = "SELECT * FROM user_reviews ORDER BY id DESC";
                    $result = $mysqli->query($sql_fetch);

                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            // Convert number rating to stars
                            $stars_output = "";
                            for($i=0; $i<5; $i++) {
                                if($i < $row['rating']) {
                                    $stars_output .= "★";
                                } else {
                                    $stars_output .= "☆";
                                }
                            }
                            
                            echo '<div class="review-card">';
                            echo '<h3>' . htmlspecialchars($row['user_name']) . ' <span class="date">' . date('M d, Y', strtotime($row['created_at'])) . '</span></h3>';
                            echo '<div class="rating-display">' . $stars_output . '</div>';
                            echo '<p>' . htmlspecialchars($row['review_text']) . '</p>';
                            echo '</div>';
                        }
                    } else {
                        echo "<p style='text-align:center; color:#777;'>No reviews yet. Be the first!</p>";
                    }
                    ?>
                </div>

            </div>
        </div>
    </div>

</body>
</html>