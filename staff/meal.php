<?php
session_start();

/* LOGOUT */
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

/* TEMP USER */
if (!isset($_SESSION['user'])) {
    $_SESSION['user'] = "Admin";
}

/* DB CONNECTION */
$dbuser="root";
$dbpass="";
$host="localhost";
$db="hostel";
$mysqli = new mysqli($host,$dbuser,$dbpass,$db);

/* DEFAULT SETTINGS */
if (!isset($_SESSION['rate_mode'])) $_SESSION['rate_mode'] = 'auto';
if (!isset($_SESSION['manual_rate'])) $_SESSION['manual_rate'] = 0;

/* SET MEAL RATE MODE */
if (isset($_POST['set_rate'])) {
    $_SESSION['rate_mode'] = $_POST['rate_mode'];
    $_SESSION['manual_rate'] = (float)$_POST['manual_rate'];
}

/* ADD MEAL */
if (isset($_POST['add_meal'])) {
    $name = trim($_POST['name']);
    $date = $_POST['meal_date'];

    $check = $mysqli->prepare(
        "SELECT id FROM meals WHERE name=? AND meal_date=?"
    );
    $check->bind_param("ss",$name,$date);
    $check->execute();
    $check->store_result();

    if ($check->num_rows == 0) {
        $stmt = $mysqli->prepare(
            "INSERT INTO meals (name, breakfast, lunch, dinner, meal_date)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "siiis",
            $name,
            $_POST['breakfast'],
            $_POST['lunch'],
            $_POST['dinner'],
            $date
        );
        $stmt->execute();
    }
}

/* DELETE MEAL */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $mysqli->query("DELETE FROM meals WHERE id=$id");
}

/* UPDATE MEAL */
if (isset($_POST['update_meal'])) {
    $stmt = $mysqli->prepare(
        "UPDATE meals SET breakfast=?, lunch=?, dinner=? WHERE id=?"
    );
    $stmt->bind_param(
        "iiii",
        $_POST['breakfast'],
        $_POST['lunch'],
        $_POST['dinner'],
        $_POST['id']
    );
    $stmt->execute();
}

/* DATA */
$today = date('Y-m-d');

$meals = $mysqli->query(
    "SELECT * FROM meals WHERE meal_date='$today'"
);

$totalMeals = $mysqli->query(
    "SELECT SUM(breakfast+lunch+dinner) total FROM meals WHERE meal_date='$today'"
)->fetch_assoc()['total'] ?? 0;

$totalExpense = $mysqli->query(
    "SELECT SUM(amount) total FROM expenses"
)->fetch_assoc()['total'] ?? 0;

/* MEAL RATE */
if ($_SESSION['rate_mode'] === 'manual') {
    $mealRate = $_SESSION['manual_rate'];
} else {
    $mealRate = ($totalMeals > 0) ? round($totalExpense / $totalMeals, 2) : 0;
}

$totalMealExpense = round($totalMeals * $mealRate, 2);

/* REPORTS */
$dailyReport = $mysqli->query(
    "SELECT meal_date, SUM(breakfast+lunch+dinner) total
     FROM meals GROUP BY meal_date ORDER BY meal_date DESC LIMIT 7"
);

$monthlyReport = $mysqli->query(
    "SELECT DATE_FORMAT(meal_date,'%Y-%m') month,
            SUM(breakfast+lunch+dinner) total
     FROM meals GROUP BY month ORDER BY month DESC LIMIT 6"
);
?>
<!DOCTYPE html>
<html>
<head>
<title>Meal Management</title>

<style>
body{font-family:Arial;background:#f4f6f9;margin:0;display:flex}
.sidebar{width:230px;background:#1f2933;color:#fff;padding:20px}
.sidebar h2{text-align:center}
.sidebar a{display:block;color:#cfd8dc;padding:10px;text-decoration:none}
.sidebar a:hover{background:#374151;color:#fff}
.main{flex:1}
.header{display:flex;justify-content:space-between;background-color:#1f2933;align-items:center;margin-bottom:20px;height:80px}
.logout{background:#dc2626;color:#fff;padding:8px 14px;border-radius:5px;text-decoration:none}
.logout:hover{background:#b91c1c}
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:20px;margin-bottom:30px}
.card{background:#fff;padding:20px;border-radius:8px}
table{width:100%;border-collapse:collapse;background:#fff}
th,td{padding:10px;border-bottom:1px solid #ddd}
th{background:#1f2933;color:#fff}
input,select,button{padding:6px}
.section{margin-bottom:40px}
</style>
</head>
<body>

<div class="sidebar">
     <h2>OAHM System</h2>
      <a href="dashboard.php"></i> Dashboard</a>
	  <a href="add-courses.php"><i class="fa fa-files-o"></i> Courses</a>
	  <a href="create-room.php"><i class="fa fa-desktop"></i> Rooms</a>
	  <a href="registration.php"><i class="fa fa-user"></i>Resident Registration</a>
	  <a href="meal.php"><i class="fa fa-user"></i>Meal Registration</a>


</div>


<div class="main">

<div class="header">
    <h1></h1>
    <a class="logout" href="logout.php">Logout</a>
</div>

<div class="cards">
    <div class="card"><b>Total Meals</b><h2><?= $totalMeals ?></h2></div>
    <div class="card"><b>Meal Rate</b><h2>৳<?= $mealRate ?></h2></div>
    <div class="card"><b>Total Meal Expense</b><h2>৳<?= $totalMealExpense ?></h2></div>
    <div class="card"><b>Total Expense</b><h2>৳<?= $totalExpense ?></h2></div>
    <div class="card"><b>User</b><h2>Staff</h2></div>
</div>

<div class="section">
<h2>Meal Rate Settings</h2>
<form method="post">
    <select name="rate_mode">
        <option value="auto" <?= $_SESSION['rate_mode']=='auto'?'selected':'' ?>>Auto</option>
        <option value="manual" <?= $_SESSION['rate_mode']=='manual'?'selected':'' ?>>Manual</option>
    </select>
    <input type="number" step="0.01" name="manual_rate" value="<?= $_SESSION['manual_rate'] ?>">
    <button name="set_rate">Apply</button>
</form>
</div>

<div class="section">
<h2>Add Meal</h2>
<form method="post">
    <input name="name" placeholder="Member Name" required>
    <input type="date" name="meal_date" value="<?= $today ?>" required>
    <select name="breakfast"><option>0</option><option>1</option></select>
    <select name="lunch"><option>0</option><option>1</option></select>
    <select name="dinner"><option>0</option><option>1</option></select>
    <button name="add_meal">Add</button>
</form>
</div>

<div class="section">
<h2>Today's Meals</h2>
<table>
<tr><th>Name</th><th>B</th><th>L</th><th>D</th><th>Total</th><th>Action</th></tr>
<?php while($m=$meals->fetch_assoc()): ?>
<tr>
<form method="post">
<td><?= $m['name'] ?></td>
<td><input type="number" name="breakfast" value="<?= $m['breakfast'] ?>" min="0" max="1"></td>
<td><input type="number" name="lunch" value="<?= $m['lunch'] ?>" min="0" max="1"></td>
<td><input type="number" name="dinner" value="<?= $m['dinner'] ?>" min="0" max="1"></td>
<td><?= $m['breakfast']+$m['lunch']+$m['dinner'] ?></td>
<td>
    <input type="hidden" name="id" value="<?= $m['id'] ?>">
    <button name="update_meal">Update</button>
    <a href="?delete=<?= $m['id'] ?>" onclick="return confirm('Delete?')">Delete</a>
</td>
</form>
</tr>
<?php endwhile; ?>
</table>
</div>

<div class="section">
<h2>Daily Report</h2>
<table>
<tr><th>Date</th><th>Total Meals</th><th>Expense</th></tr>
<?php while($r=$dailyReport->fetch_assoc()): ?>
<tr>
<td><?= $r['meal_date'] ?></td>
<td><?= $r['total'] ?></td>
<td>৳<?= round($r['total'] * $mealRate, 2) ?></td>
</tr>
<?php endwhile; ?>
</table>
</div>

<div class="section">
<h2>Monthly Report</h2>
<table>
<tr><th>Month</th><th>Total Meals</th><th>Expense</th></tr>
<?php while($r=$monthlyReport->fetch_assoc()): ?>
<tr>
<td><?= $r['month'] ?></td>
<td><?= $r['total'] ?></td>
<td>৳<?= round($r['total'] * $mealRate, 2) ?></td>
</tr>
<?php endwhile; ?>
</table>
</div>

</div>
</body>
</html>
