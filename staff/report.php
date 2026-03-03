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
<html>
<head>
<title>Meal Reports</title>
<style>
body{font-family:Arial;background:#f4f6f9;margin:0}
.container{padding:30px}
.header{display:flex;justify-content:space-between;align-items:center;background-color: #1f2933;color: #ddd;}
.back{background:red;color:white;padding:8px 14px;border-radius:5px;text-decoration:none}
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:20px;margin:25px 0}
.card{background:#fff;padding:20px;border-radius:8px}
table{width:100%;border-collapse:collapse;background:#fff;margin-top:15px}
th,td{padding:10px;border-bottom:1px solid #ddd;text-align:center}
th{background:#1f2933;color:#fff}
.section{margin-bottom:50px}
</style>
</head>
<body>

<div class="container">

<div class="header">
    <h1>Meal Reports</h1>
    <a class="back" href="logout.php">logout</a>
</div>

<div class="cards">
    <div class="card">
        <b>Total Meals</b>
        <h2><?= $grandTotalMeals ?></h2>
    </div>

    <div class="card">
        <b>Total Expense</b>
        <h2>৳<?= $totalExpense ?></h2>
    </div>

    <div class="card">
        <b>Meal Rate</b>
        <h2>৳<?= $mealRate ?></h2>
    </div>

    <div class="card">
        <b>User</b>
        <h2><?= $user ?></h2>
    </div>
</div>

<div class="section">
<h2>📅 Daily Meal Report</h2>
<table>
<tr>
    <th>Date</th>
    <th>Total Meals</th>
    <th>Meal Expense</th>
</tr>
<?php while($d=$dailyReport->fetch_assoc()): ?>
<tr>
    <td><?= $d['meal_date'] ?></td>
    <td><?= $d['total_meals'] ?></td>
    <td>৳<?= round($d['total_meals'] * $mealRate, 2) ?></td>
</tr>
<?php endwhile; ?>
</table>
</div>

<div class="section">
<h2>📊 Monthly Meal Report</h2>
<table>
<tr>
    <th>Month</th>
    <th>Total Meals</th>
    <th>Meal Expense</th>
</tr>
<?php while($m=$monthlyReport->fetch_assoc()): ?>
<tr>
    <td><?= $m['month'] ?></td>
    <td><?= $m['total_meals'] ?></td>
    <td>৳<?= round($m['total_meals'] * $mealRate, 2) ?></td>
</tr>
<?php endwhile; ?>
</table>
</div>

</div>
</body>
</html>
