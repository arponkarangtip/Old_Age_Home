<?php
session_start();

/* DB CONNECTION */
$dbuser="root";
$dbpass="";
$host="localhost";
$db="hostel";
$mysqli = new mysqli($host,$dbuser,$dbpass,$db);

/* FALLBACK USER */
$user = $_SESSION['user'] ?? 'Admin';

/* TOTAL EXPENSE */
$totalExpense = $mysqli->query(
    "SELECT SUM(amount) total FROM expenses"
)->fetch_assoc()['total'] ?? 0;

/* DAILY REPORT */
$dailyReport = $mysqli->query(
    "SELECT meal_date,
            SUM(breakfast+lunch+dinner) AS total_meals
     FROM meals
     GROUP BY meal_date
     ORDER BY meal_date DESC"
);

/* MONTHLY REPORT */
$monthlyReport = $mysqli->query(
    "SELECT DATE_FORMAT(meal_date,'%Y-%m') AS month,
            SUM(breakfast+lunch+dinner) AS total_meals
     FROM meals
     GROUP BY month
     ORDER BY month DESC"
);

/* GRAND TOTAL MEALS */
$grandTotalMeals = $mysqli->query(
    "SELECT SUM(breakfast+lunch+dinner) total FROM meals"
)->fetch_assoc()['total'] ?? 0;

/* AUTO MEAL RATE */
$mealRate = ($grandTotalMeals > 0)
    ? round($totalExpense / $grandTotalMeals, 2)
    : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Meal Reports</title>
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
        background: #1f2933; /* Dark Sidebar */
        color: white;
        padding: 20px;
        position: fixed;
        height: 100%;
        display: flex;
        flex-direction: column;
        z-index: 100;
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

    /* --- MAIN CONTENT --- */
    .main-content {
        margin-left: 260px;
        width: calc(100% - 260px);
        display: flex;
        flex-direction: column;
    }

    /* --- TOP HEADER --- */
    .header {
        background: #1f2933;
        padding: 15px 40px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #e0e0e0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }

    .header h1 { font-size: 24px; color: #333; margin: 0; }
    .header-user { font-weight: 600; color: #555; display: flex; align-items: center; gap: 10px; }

    /* --- PAGE CONTAINER --- */
    .container { padding: 30px 40px; }

    /* --- CARDS --- */
    .cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }

    .card {
        background: #fff;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        border: 1px solid #eee;
        text-align: center;
    }

    .card b { color: #888; font-size: 14px; text-transform: uppercase; display: block; margin-bottom: 10px; }
    .card h2 { font-size: 28px; color: #1f2933; margin: 0; }

    /* --- TABLES --- */
    .section { margin-bottom: 50px; background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    .section h2 { font-size: 18px; margin-bottom: 20px; color: #333; border-bottom: 2px solid #f4f6f9; padding-bottom: 10px; }

    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 15px; border-bottom: 1px solid #eee; text-align: center; }
    th { background: #1f2933; color: #fff; font-weight: 500; font-size: 14px; }
    tr:hover { background-color: #f9fafb; }

    @media (max-width: 900px) {
        .sidebar { width: 70px; padding: 20px 10px; }
        .sidebar h2, .sidebar span { display: none; }
        .sidebar a { justify-content: center; }
        .main-content { margin-left: 70px; width: calc(100% - 70px); }
    }
</style>
</head>
<body>

    <div class="sidebar">
     <h2>OAHM System</h2>
	   <a href="dashboard.php">Dashboard</a>
	   <a href="add-courses.php">Courses</a>
	   <a href="create-room.php">Rooms</a>
	    <a href="registration.php">Resident Registration</a>
	   <a href="manage-students.php">Manage Resident</a>
	   <a href="meal.php">Meal Management</a>
	  <a href="report.php">Reports</a>
	   <a href="analytics.php">Analytics</a>
	  <a href="feedbacks.php">Feedback</a>
       <a href="access-log.php">User Access logs</a>
    </div>

    <div class="main-content">
        
        <div class="header">
            <h1></h1>
            <div class="header-user">
                <a href="logout.php"><button style="background-color:red; color: white;">Logout</button></a>
            </div>
        </div>

        <div class="container">

            <div class="cards">
                <div class="card">
                    <b>Total Meals Served</b>
                    <h2><?= $grandTotalMeals ?></h2>
                </div>

                <div class="card">
                    <b>Total Expense</b>
                    <h2>৳<?= $totalExpense ?></h2>
                </div>

                <div class="card">
                    <b>Current Meal Rate</b>
                    <h2>৳<?= $mealRate ?></h2>
                </div>

                <div class="card">
                    <b>Logged In As</b>
                    <h2><?= htmlspecialchars($user) ?></h2>
                </div>
            </div>

            <div class="section">
                <h2>📅 Daily Meal Breakdown</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Total Meals</th>
                            <th>Est. Daily Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($d=$dailyReport->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('d M, Y', strtotime($d['meal_date'])) ?></td>
                            <td><?= $d['total_meals'] ?></td>
                            <td>৳<?= round($d['total_meals'] * $mealRate, 2) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="section">
                <h2>📊 Monthly Summary</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Total Meals</th>
                            <th>Total Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($m=$monthlyReport->fetch_assoc()): ?>
                        <tr>
                            <td><?= date('F Y', strtotime($m['month'])) ?></td>
                            <td><?= $m['total_meals'] ?></td>
                            <td>৳<?= round($m['total_meals'] * $mealRate, 2) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</body>
</html>