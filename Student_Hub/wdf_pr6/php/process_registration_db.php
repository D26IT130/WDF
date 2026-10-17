<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Sanitize
    $name = htmlspecialchars(trim($_POST["name"]));
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $mobile = htmlspecialchars(trim($_POST["mobile"]));
    $course_id = intval($_POST["course_id"]);
    $year = intval($_POST["year"]);
    $gender = htmlspecialchars(trim($_POST["gender"]));

    // Validation
    if (
        empty($name) ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        empty($mobile) ||
        empty($course_id) ||
        empty($year) ||
        empty($gender)
    ) {
        die("Invalid input data.");
    }

    // JSON file path
    $file = "../data/students.json";

    // Read existing data
    if (file_exists($file)) {
        $students = json_decode(file_get_contents($file), true);
    } else {
        $students = [];
    }

    // Add new record
    $students[] = [
        "name" => $name,
        "email" => $email,
        "mobile" => $mobile,
        "course_id" => $course_id,
        "year" => $year,
        "gender" => $gender,
        "created_at" => date("Y-m-d H:i:s")
    ];

    // Save back to JSON
    file_put_contents(
        $file,
        json_encode($students, JSON_PRETTY_PRINT)
    );

    echo "<h2>Registration Successful!</h2>";
    echo "<p>Student data saved to students.json</p>";
}
?>