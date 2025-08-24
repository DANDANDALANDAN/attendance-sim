<?php
session_start();
set_time_limit(0); // Allow longer execution time for large CSV uploads

if (!isset($_SESSION['user_id'])) {
    header('Location: ../users/login.php');
    exit();
}
require '../config/db.php';

$error = '';
$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check file upload and extension
    if (
        !isset($_FILES['csv_file']) ||
        $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK
    ) {
        $error = 'Upload error or no file selected.';
    } else {
        $file = $_FILES['csv_file']['tmp_name'];
        $ext = strtolower(
            pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION)
        );
        if ($ext !== 'csv') {
            $error = 'Invalid file type. Please upload a CSV file.';
        }
    }

    // Open file handle
    if (!$error) {
        $handle = fopen($file, 'r');
        if (!$handle) {
            $error = 'Failed to open uploaded CSV file.';
        }
    }

    // Check CSV header
    if (!$error) {
        $expectedHeader = [
            'lrn',
            'last_name',
            'first_name',
            'middle_initial',
            'sex',
            'grade_level',
            'section',
            'parent_name',
            'parent_address',
            'parent_contact',
        ];
        $header = fgetcsv($handle);
        $headerLower = array_map('strtolower', $header);
        if ($headerLower !== $expectedHeader) {
            $error = 'CSV header does not match the template.';
            fclose($handle);
        }
    }

    // Process CSV rows
    if (!$error) {
        try {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO students (lrn, last_name, first_name, middle_initial, sex,
                                      grade_level, section, parent_name, parent_address, parent_contact)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    last_name = VALUES(last_name),
                    first_name = VALUES(first_name),
                    middle_initial = VALUES(middle_initial),
                    sex = VALUES(sex),
                    grade_level = VALUES(grade_level),
                    section = VALUES(section),
                    parent_name = VALUES(parent_name),
                    parent_address = VALUES(parent_address),
                    parent_contact = VALUES(parent_contact)
            ");

            $rowNumber = 1;
            $insertedCount = 0;
            $batchSize = 100;

            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                $row = array_map('trim', $row);

                // Validate LRN: exactly 12 digits
                if (!preg_match('/^\d{12}$/', $row[0])) {
                    $errors[] = "Row {$rowNumber}: LRN must be exactly 12 digits.";
                    continue;
                }

                // Check required fields presence
                if (
                    $row[1] === '' ||
                    $row[2] === '' ||
                    $row[6] === '' ||
                    $row[7] === '' ||
                    $row[8] === ''
                ) {
                    $errors[] = "Row {$rowNumber}: Required fields missing.";
                    continue;
                }

                // Sex validation
                if (!in_array(strtolower($row[4]), ['male', 'female'])) {
                    $errors[] = "Row {$rowNumber}: Sex must be Male or Female.";
                    continue;
                }

                // Grade level validation
                if (!is_numeric($row[5])) {
                    $errors[] = "Row {$rowNumber}: Grade level must be a number.";
                    continue;
                }

                // Normalize data
                $row[1] = strtoupper($row[1]);
                $row[2] = strtoupper($row[2]);
                $row[3] = strtoupper(substr($row[3], 0, 1));
                $row[6] = strtoupper($row[6]);
                $row[7] = strtoupper($row[7]);
                $row[8] = strtoupper($row[8]);
                $parentContactClean = preg_replace('/[^0-9]/', '', $row[9]);

                try {
                    $stmt->execute([
                        $row[0],
                        $row[1],
                        $row[2],
                        $row[3],
                        ucfirst(strtolower($row[4])),
                        (int) $row[5],
                        $row[6],
                        $row[7],
                        $row[8],
                        $parentContactClean,
                    ]);
                    $insertedCount++;
                } catch (PDOException $e) {
                    $errors[] =
                        "Row {$rowNumber}: Database error - " .
                        $e->getMessage();
                    continue; // skip error row, continue processing others
                }

                // Batch commit every 100 rows
                if ($insertedCount % $batchSize === 0) {
                    $pdo->commit();
                    $pdo->beginTransaction();
                }
            }

            // Commit any remaining transactions
            $pdo->commit();
            fclose($handle);

            $success = "Successfully processed {$insertedCount} rows.";
        } catch (Exception $e) {
            $pdo->rollBack();
            fclose($handle);
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Bulk Upload Students</title>
<link rel="stylesheet" href="/attendance-sim/css/style.css?v=<?= time() ?>" />
<style>
/* Keep your existing CSS styles as before */
body { font-family: Arial, sans-serif; text-align: center; }
.notification { padding: 12px; border-radius: 5px; margin-bottom: 20px; text-align: left; max-width: 650px; margin-left: auto; margin-right: auto; }
.error { background-color: #fdd; border: 1px solid #f99; color: #900; }
.success { background-color: #dfd; border: 1px solid #9c9; color: #090; }
ul { margin-top: 0; padding-left: 20px; }
.header-row { display: flex; justify-content: space-between; align-items: center; max-width: 650px; margin: 0 auto 16px auto; width: 100%; }
.bulk-upload-title { font-weight: bold; margin: 0; font-size: 1.5rem; }
form { margin-top: 20px; display: flex; flex-direction: column; align-items: flex-start; max-width: 650px; width: 100%; margin-left: auto; margin-right: auto; }
label { display: block; margin-bottom: 8px; font-weight: bold; }
input[type="file"] { width: 100%; padding: 6px; box-sizing: border-box; margin-bottom: 12px; }
button { background-color: #004080; color: white; font-size: 14px; padding: 10px 18px; border: none; border-radius: 6px; cursor: pointer; text-transform: uppercase; font-weight: bold; }
button:hover { background-color: #003060; }
.back-btn { margin-top: 0; text-decoration: none; display: inline-block; color: #ffffffff; background-color: #585f67ff; border: 1px solid #004080; padding: 10px 20px; border-radius: 6px; cursor: pointer; transition: background-color 0.3s; max-width: 150px; text-align: center; }
.back-btn:hover { background-color: #004080; color: white; text-decoration: none; }
nav a.active { background-color: #ffffffff; color: #003366; font-weight: bold; text-decoration: none; padding: 8px 12px; border-radius: 4px; }
</style>
</head>
<body>
<header><h1>Manage Students</h1></header>
<?php include_once __DIR__ . '/../nav.php'; ?>

<?php if ($error): ?>
    <div class="notification error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="notification success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($errors): ?>
    <div class="notification error">
        <strong>Some rows were skipped due to errors:</strong>
        <ul>
            <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" novalidate>
    <div class="header-row">
        <h2 class="bulk-upload-title">Bulk Upload/Update Students</h2>
        <a href="student_dashboard.php" class="back-btn" role="button" aria-label="Back to Manage Students">Back</a>
    </div>

    <label for="csv_file">CSV file (must match template):</label>
    <p><a href="student_bulk_template.csv" download>Download CSV Template</a></p>
    <input type="file" id="csv_file" name="csv_file" accept=".csv" required />
    <button type="submit">Upload</button>
</form>
</body>
</html>
