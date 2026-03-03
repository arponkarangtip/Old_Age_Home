<?php
session_start();

/* LOGOUT */
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: user_dashboard.php");
    exit;
}

/* USER LOGIN (already set by login system) */
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = "arpon"; // demo
}
$user = $_SESSION['user'];

/* DB CONNECTION */
$dbuser="root";
$dbpass="";
$host="localhost";
$db="hostel";
$mysqli = new mysqli($host,$dbuser,$dbpass,$db);
if ($mysqli->connect_error) {
    die("Database connection failed");
}

/* DATE */
$today = date('Y-m-d');
$currentMonth = date('Y-m');

/* 🔹 TODAY MEAL (ONLY THIS USER) */
$stmt = $mysqli->prepare(
    "SELECT breakfast, lunch, dinner
     FROM meals
     WHERE name = ? AND meal_date = ?"
);
$stmt->bind_param("ss", $user, $today);
$stmt->execute();
$todayMeal = $stmt->get_result()->fetch_assoc();

/* 🔹 USER MONTHLY MEALS */
$stmt = $mysqli->prepare(
    "SELECT SUM(breakfast + lunch + dinner) total
     FROM meals
     WHERE name = ?
     AND DATE_FORMAT(meal_date,'%Y-%m') = ?"
);
$stmt->bind_param("ss", $user, $currentMonth);
$stmt->execute();
$userMonthlyMeals = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

/* 🔹 TOTAL MEALS (ALL USERS – FOR RATE) */
$totalMeals = $mysqli->query(
    "SELECT SUM(breakfast + lunch + dinner) total FROM meals"
)->fetch_assoc()['total'] ?? 0;

/* 🔹 TOTAL EXPENSE */
$totalExpense = $mysqli->query(
    "SELECT SUM(amount) total FROM expenses"
)->fetch_assoc()['total'] ?? 0;

/* 🔹 MEAL RATE (AUTO – SAME AS ADMIN) */
$mealRate = ($totalMeals > 0)
    ? round($totalExpense / $totalMeals, 2)
    : 0;

/* COST CALCULATION */
$todayMealCount =
    ($todayMeal['breakfast'] ?? 0) +
    ($todayMeal['lunch'] ?? 0) +
    ($todayMeal['dinner'] ?? 0);

$todayCost   = round($todayMealCount * $mealRate, 2);
$monthlyCost = round($userMonthlyMeals * $mealRate, 2);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Meal Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
    /* --- RESET & LAYOUT --- */
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Poppins", sans-serif; }
    
    body {
        background: #f4f6f9;
        display: flex;
        min-height: 100vh;
    }

    /* --- SIDEBAR --- */
    .sidebar {
        width: 260px;
        background: #1f2933;
        color: white;
        padding: 20px;
        position: fixed;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .sidebar h2 {
        color: #00fff2;
        margin-bottom: 40px;
        font-size: 22px;
        text-align: center;
        letter-spacing: 1px;
    }

    .sidebar a {
        text-decoration: none;
        color: #cfd8dc;
        padding: 15px;
        margin-bottom: 10px;
        border-radius: 8px;
        transition: 0.3s;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 15px;
    }

    .sidebar a:hover, .sidebar a.active {
        background: rgba(0, 255, 242, 0.1);
        color: #00fff2;
    }

    .sidebar-footer {
        margin-top: auto;
    }

    /* --- MAIN CONTENT WRAPPER --- */
    .main-content {
        margin-left: 260px;
        width: calc(100% - 260px);
        display: flex;
        flex-direction: column;
    }

    /* --- HEADER --- */
    .header {
        background: #1f2933;
        padding: 20px 40px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #e0e0e0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .header h2 { color: #333; font-size: 24px; }
    
    .user-profile {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
        color: white;
    }

    /* --- DASHBOARD CONTENT --- */
    .container {
        padding: 40px;
        max-width: 1200px;
        margin: 0 auto;
        width: 100%;
    }

    .cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 25px;
        margin-bottom: 40px;
    }

    .card {
        background: #fff;
        padding: 25px;
        border-radius: 12px;
        text-align: center;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        border: 1px solid #eee;
        transition: transform 0.2s;
    }

    .card:hover { transform: translateY(-5px); }

    .card b { display: block; color: #666; margin-bottom: 10px; font-size: 14px; text-transform: uppercase; }
    .card h2 { color: #1f2933; font-size: 32px; font-weight: 600; margin: 0; }

    /* --- TABLE STYLES --- */
    .status-section {
        background: #fff;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }

    h3.section-title { margin-bottom: 20px; color: #333; }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th, td {
        padding: 15px;
        border-bottom: 1px solid #eee;
        text-align: center;
    }

    th {
        background: #f8f9fa;
        color: #555;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 13px;
    }

    .badge {
        padding: 8px 16px;
        border-radius: 30px;
        color: #fff;
        font-size: 13px;
        font-weight: 500;
        display: inline-block;
        min-width: 100px;
    }
    .yes { background: #16a34a; box-shadow: 0 2px 4px rgba(22, 163, 74, 0.3); }
    .no { background: #dc2626; box-shadow: 0 2px 4px rgba(220, 38, 38, 0.3); }

    @media (max-width: 900px) {
        .sidebar { width: 70px; padding: 20px 10px; }
        .sidebar h2, .sidebar span { display: none; }
        .sidebar a { justify-content: center; }
        .sidebar a i { font-size: 20px; }
        .main-content { margin-left: 70px; width: calc(100% - 70px); }
    }
</style>
</head>

<body>

    <div class="sidebar">
        <h2>User Meal</h2>
        <h3><a href="dashboard.php"><i class="fa fa-desktop"></i>Dashboard</a></h3>
        <h3><a href="book-hostel.php">Book Resident Details</a></h3>
        <h3><a href="room-details.php">Room Details</a></h3>
       <h3><a href="register-complaint.php">Complaint Registration</a></h3>
       <h3><a href="my-complaints.php"> Registered Complaints </a></h3>
        <h3><a href="feedback.php"> Feedback </a></h3>
       <h3><a href="my-profile.php"> My Profile </a></h3>
       <h3></h3><a href="change-password.php">Change Password</a></h3>
       <h3></h3><a href="access-log.php">Access log</a></h3>
    
    </div>

    <div class="main-content">
        
        <div class="header">
            <h2></h2>
            <div class="user-profile">
                <i class="fa-solid fa-circle-user" style="font-size: 24px; color: #1f2933;"></i>
                <a href="logout.php"><button style="background-color: #00fff2; height: 30px;width: 80px;">Logout</button></a>
            </div>
        </div>

        <div class="container">
            <div class="cards">
                <div class="card">
                    <b>Today’s Meals</b>
                    <h2><?= $todayMealCount ?></h2>
                </div>

                <div class="card">
                    <b>Today’s Cost</b>
                    <h2>৳<?= $todayCost ?></h2>
                </div>

                <div class="card">
                    <b>This Month Meals</b>
                    <h2><?= $userMonthlyMeals ?></h2>
                </div>

                <div class="card">
                    <b>This Month Cost</b>
                    <h2>৳<?= $monthlyCost ?></h2>
                </div>

                <div class="card">
                    <b>Current Meal Rate</b>
                    <h2>৳<?= $mealRate ?></h2>
                </div>
            </div>

            <div class="status-section">
                <h3 class="section-title">Today’s Meal Status (<?= date('d M Y') ?>)</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Breakfast</th>
                            <th>Lunch</th>
                            <th>Dinner</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <span class="badge <?= ($todayMeal['breakfast'] ?? 0) ? 'yes':'no' ?>">
                                    <?= ($todayMeal['breakfast'] ?? 0) ? 'Taken':'Not Taken' ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= ($todayMeal['lunch'] ?? 0) ? 'yes':'no' ?>">
                                    <?= ($todayMeal['lunch'] ?? 0) ? 'Taken':'Not Taken' ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= ($todayMeal['dinner'] ?? 0) ? 'yes':'no' ?>">
                                    <?= ($todayMeal['dinner'] ?? 0) ? 'Taken':'Not Taken' ?>
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>
</html>