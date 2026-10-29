<?php
include("sess_check.php");

$q_koor = mysqli_query($conn, "SELECT * FROM koordinator LIMIT 1");
$q_man = mysqli_query($conn, "SELECT * FROM manager LIMIT 1");
$q_lead = mysqli_query($conn, "SELECT * FROM leader LIMIT 1");

$res = [
  'koordinator' => mysqli_fetch_assoc($q_koor),
  'manager' => mysqli_fetch_assoc($q_man),
  'leader' => mysqli_fetch_assoc($q_lead)
];

echo "<pre>";
print_r($res);
echo "</pre>";
?>
