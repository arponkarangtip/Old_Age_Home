<?php
session_start();

/* --- 1. EXISTING DATABASE & LOGIC (UNCHANGED) --- */
$dbuser = "root";
$dbpass = "";
$host   = "localhost";
$db     = "hostel";

$mysqli = new mysqli($host, $dbuser, $dbpass, $db);
if ($mysqli->connect_error) {
    die("Database connection failed");
}

/* SAFE FETCH FUNCTION */
function getValue($mysqli, $query, $default = 0) {
    $res = $mysqli->query($query);
    if ($res && $row = $res->fetch_row()) {
        return $row[0] ?? $default;
    }
    return $default;
}

/* METRICS */
$totalUsers   = getValue($mysqli, "SELECT COUNT(*) FROM registration");
$totalMeals   = getValue($mysqli, "SELECT SUM(breakfast+lunch+dinner) FROM meals");
$totalExpense = getValue($mysqli, "SELECT SUM(amount) FROM expenses");

$mealRate = ($totalMeals > 0) ? round($totalExpense / $totalMeals, 2) : 0;

/* MONTHLY MEALS */
$mealMonths = [];
$mealCounts = [];
$q1 = $mysqli->query("
    SELECT DATE_FORMAT(meal_date,'%b %Y') m, 
           SUM(breakfast+lunch+dinner) t
    FROM meals
    GROUP BY m
    ORDER BY meal_date
");
while ($q1 && $r = $q1->fetch_assoc()) {
    $mealMonths[] = $r['m'];
    $mealCounts[] = $r['t'];
}

/* MONTHLY EXPENSE */
$expenseMonths = [];
$expenseTotals = [];
$q2 = $mysqli->query("
    SELECT DATE_FORMAT(created_at,'%b %Y') m, SUM(amount) t
    FROM expenses
    GROUP BY m
    ORDER BY created_at
");
while ($q2 && $r = $q2->fetch_assoc()) {
    $expenseMonths[] = $r['m'];
    $expenseTotals[] = $r['t'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Analytics Dashboard</title>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<style>
    /* --- RESET & LAYOUT --- */
    * { margin: 0; padding: 0; box-sizing: border-box; font-family: "Poppins", sans-serif; }
    
    body {
        background: #0f172a;
        color: #fff;
        display: flex; /* Flex layout for Sidebar + Content */
        min-height: 100vh;
    }

    /* --- SIDEBAR --- */
    .sidebar {
        width: 250px;
        background: #1f2933;
        border-right: 1px solid rgba(34, 211, 238, 0.2);
        padding: 20px;
        position: fixed;
        height: 100%;
        display: flex;
        flex-direction: column;
        z-index: 100;
    }

    .sidebar h2 {
        color: #22d3ee;
        margin-bottom: 40px;
        font-size: 24px;
        text-align: center;
        text-shadow: 0 0 10px rgba(34, 211, 238, 0.5);
    }

    .sidebar a {
        text-decoration: none;
        color: white;
        padding: 15px;
        margin-bottom: 10px;
        border-radius: 8px;
        transition: 0.3s;
        display: block;
        opacity: 0.8;
    }

    .sidebar a:hover, .sidebar a.active {
        background: #111; /* Slight highlight on hover */
        color: #22d3ee;
        opacity: 1;
        box-shadow: 0 0 10px rgba(34, 211, 238, 0.1);
    }

    /* --- MAIN CONTENT WRAPPER --- */
    .main-content {
        margin-left: 250px; /* Space for sidebar */
        width: calc(100% - 250px);
        display: flex;
        flex-direction: column;
    }

    /* --- HEADER --- */
    .header {
        height: 70px;
        background: #1f2933;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 40px;
        position: sticky; /* Keeps header at top */
        top: 0;
        z-index: 99;
    }
    
    .header h3 {
        font-size: 20px;
        font-weight: 500;
        color: #fff;
    }

    .header .user-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .header .user-avatar {
        width: 35px; height: 35px;
        border-radius: 50%;
        background: #22d3ee;
        display: flex;
        align-items: center;
        justify-content: center;
        color: black;
    }

    /* --- DASHBOARD CONTENT AREA --- */
    .dashboard-container {
        padding: 30px;
    }

    .page-title { margin-bottom: 30px; font-weight: 600; font-size: 28px; }

    /* --- CARDS --- */
    .cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }

    .card {
        background: #1e293b;
        padding: 25px;
        border-radius: 12px;
        text-align: center;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(255,255,255,0.05);
    }

    .card h2 { color: #22d3ee; font-size: 28px; margin-bottom: 5px; }
    .card p { opacity: 0.7; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; }

    /* --- CHARTS --- */
    .charts {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
    }

    .chart-box {
        background: #1e293b;
        padding: 20px;
        border-radius: 12px;
        border: 1px solid rgba(255,255,255,0.05);
    }
    
    .chart-box h3 { margin-bottom: 15px; color: #cbd5e1; font-weight: 400; }

    @media(max-width: 1100px) {
        .charts { grid-template-columns: 1fr; }
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
       <a href="analytics.php" class="active">Analytics</a>
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

        <div class="dashboard-container">
            <h1 class="page-title">Performance Metrics</h1>

            <div class="cards">
                <div class="card">
                    <h2><?= $totalUsers ?></h2>
                    <p>Total Users</p>
                </div>
                <div class="card">
                    <h2><?= $totalMeals ?></h2>
                    <p>Total Meals</p>
                </div>
                <div class="card">
                    <h2>৳<?= $totalExpense ?></h2>
                    <p>Total Expense</p>
                </div>
                <div class="card">
                    <h2>৳<?= $mealRate ?></h2>
                    <p>Meal Rate</p>
                </div>
            </div>

            <div class="charts">
                <div class="chart-box">
                    <h3>Monthly Meals</h3>
                    <canvas id="mealChart"></canvas>
                </div>

                <div class="chart-box">
                    <h3>Monthly Expense</h3>
                    <canvas id="expenseChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Meal Chart
    new Chart(document.getElementById('mealChart'),{
        type:'line',
        data:{
            labels: <?= json_encode($mealMonths) ?>,
            datasets:[{
                label:'Meals',
                data: <?= json_encode($mealCounts) ?>,
                borderColor:'#22d3ee',
                backgroundColor:'rgba(34,211,238,0.2)',
                tension:0.4,
                fill:true,
                pointBackgroundColor: '#22d3ee'
            }]
        },
        options: {
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.1)' } },
                x: { grid: { display: false } }
            }
        }
    });

    // Expense Chart
    new Chart(document.getElementById('expenseChart'),{
        type:'bar',
        data:{
            labels: <?= json_encode($expenseMonths) ?>,
            datasets:[{
                label:'Expense',
                data: <?= json_encode($expenseTotals) ?>,
                backgroundColor:'#facc15',
                borderRadius: 5
            }]
        },
        options: {
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.1)' } },
                x: { grid: { display: false } }
            }
        }
    });
    </script>

</body>
</html>