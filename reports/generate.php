<?php
session_start();
$reportData = [
    'start_date' => $_GET['from'],
    'end_date' => $_GET['to']
];

$pdf = shell_exec(
    "python3 ../python/reports.py " . 
    escapeshellarg(json_encode($reportData))
);

header('Content-Type: application/pdf');
echo $pdf;


?>