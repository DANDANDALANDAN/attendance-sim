<?php
// add_student.php

session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../users/login.php');
    exit();
}

require '../config/db.php';

$edit = isset($_GET['id']);
$data = [];

$sections = $pdo
    ->query(
        'SELECT DISTINCT section FROM students WHERE section IS NOT NULL AND section <> "" ORDER BY section'
    )
    ->fetchAll(PDO::FETCH_COLUMN);

if ($edit) {
    $stmt = $pdo->prepare('SELECT * FROM students WHERE id = ?');
    $stmt->execute([$_GET['id']]);
    $data = $stmt->fetch();
    if (!$data) {
        header('Location: student_dashboard.php');
        exit();
    }
}

function clean_upper($input)
{
    return strtoupper(trim($input));
}
function clean_number($input)
{
    return preg_replace('/[^0-9]/', '', $input);
}
function showError($msg)
{
    return '<div class="notification error">' . $msg . '</div>';
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lrn = clean_number($_POST['lrn'] ?? '');
    $last_name = clean_upper($_POST['last_name'] ?? '');
    $first_name = clean_upper($_POST['first_name'] ?? '');
    $middle_initial = strtoupper(
        substr(trim($_POST['middle_initial'] ?? ''), 0, 1)
    );
    $sex = $_POST['sex'] ?? '';
    $grade_level = (int) ($_POST['grade_level'] ?? 0);
    $section = clean_upper($_POST['section'] ?? '');
    $parent_name = clean_upper($_POST['parent_name'] ?? '');
    $parent_address = clean_upper($_POST['parent_address'] ?? '');
    $parent_contact = clean_number($_POST['parent_contact'] ?? '');

    if (strlen($lrn) !== 12) {
        $error = 'LRN must be exactly 12 digits.';
    } elseif ($last_name === '') {
        $error = 'Last name is required.';
    } elseif ($first_name === '') {
        $error = 'First name is required.';
    } elseif ($grade_level === 0) {
        $error = 'Grade level is required.';
    } elseif ($section === '') {
        $error = 'Section is required.';
    } elseif ($parent_name === '') {
        $error = 'Full name of parent/guardian is required.';
    } elseif ($parent_address === '') {
        $error = 'Address of parent/guardian is required.';
    } elseif ($parent_contact === '') {
        $error = 'Contact number of parent/guardian is required.';
    } elseif (!in_array($sex, ['Male', 'Female'])) {
        $error = 'Select a valid sex.';
    } elseif (strlen($middle_initial) > 1) {
        $error = 'Middle initial must be one letter only.';
    }

    if (!$error) {
        if ($edit) {
            $stmt = $pdo->prepare(
                'UPDATE students SET lrn = ?, last_name = ?, first_name = ?, middle_initial = ?, sex = ?, grade_level = ?, section = ?, parent_name = ?, parent_address = ?, parent_contact = ? WHERE id = ?'
            );
            $stmt->execute([
                $lrn,
                $last_name,
                $first_name,
                $middle_initial,
                $sex,
                $grade_level,
                $section,
                $parent_name,
                $parent_address,
                $parent_contact,
                $_GET['id'],
            ]);
            $success = 'Student updated successfully.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO students (lrn, last_name, first_name, middle_initial, sex, grade_level, section, parent_name, parent_address, parent_contact) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $lrn,
                $last_name,
                $first_name,
                $middle_initial,
                $sex,
                $grade_level,
                $section,
                $parent_name,
                $parent_address,
                $parent_contact,
            ]);
            $success = 'Student added successfully.';
        }
        header('Location: student_dashboard.php?success=1');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title><?= $edit ? 'Edit Student' : 'Add Student' ?></title>
<link rel="stylesheet" href="/attendance-sim/css/style.css?v=<?= time() ?>" />
<style>
form.student-form {
    max-width: 600px;
    margin: 20px auto;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
form.student-form label {
    font-weight: normal;
    font-size: 14px;
    margin-bottom: 4px;
    display: block;
}
form.student-form input[type="text"],
form.student-form select {
    width: 100%;
    padding: 8px 10px;
    font-size: 14px;
    border: 1px solid #ccc;
    border-radius: 4px;
    box-sizing: border-box;
    text-transform: uppercase;
}
form.student-form input[type="number"] {
    text-transform: none;
}
form.student-form input#middle_initial {
    max-width: 50px;
    text-align: center;
}
form.student-form select#sex {
    text-transform: uppercase;
}
form.student-form button {
    margin-top: 16px;
    padding: 10px 20px;
    font-size: 16px;
    font-weight: bold;
    background-color: #004080;
    border: none;
    border-radius: 4px;
    color: white;
    cursor: pointer;
    text-transform: uppercase;
}
form.student-form button:hover {
    background-color: #003060;
}
.notification.error {
    background: #ffcccc;
    border: 1px solid red;
    padding: 10px;
    margin-bottom: 12px;
    color: red;
    border-radius: 4px;
    font-weight: bold;
}
.back-btn {
    margin-top: 0;
    text-decoration: none;
    display: inline-block;
    color: #ffffffff;
    background-color: #585f67ff;
    border: 1px solid #004080;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    transition: background-color 0.3s;
    max-width: 50px;
    text-align: center;
    margin-bottom: 8px;
    align-self: flex-end;
}
.back-btn:hover {
    background-color: #004080;
    color: white;
    text-decoration: none;
}
nav a.active {
            background-color: #ffffffff; /* light blue */
            color: #003366;            /* dark blue */
            font-weight: bold;
            text-decoration: none;     /* no underline */
            padding: 8px 12px;         /* optionally add some padding */
            border-radius: 4px;        /* optional rounded corners */
        }
.form-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 600px;
    margin: 0 auto 12px auto;
    width: 100%;
}
.form-title {
    margin: 0;
    font-weight: bold;
    font-size: 1.5rem;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', () => {
    ['last_name','first_name','middle_initial','section','parent_name','parent_address','parent_contact'].forEach(id => {
        const el = document.getElementById(id);
        if(el) {
            if(el.type !== 'select-one') {
                el.style.textTransform = 'uppercase';
                el.addEventListener('input', () => {
                    const start = el.selectionStart, end = el.selectionEnd;
                    el.value = el.value.toUpperCase();
                    el.setSelectionRange(start, end);
                });
            }
        }
    });

    const mi = document.getElementById('middle_initial');
    if(mi) {
        mi.maxLength = 1;
        mi.addEventListener('input', () => {
            let val = mi.value.toUpperCase().replace(/[^A-Z]/g, '');
            mi.value = val.slice(0,1);
        });
    }

    ['lrn','parent_contact'].forEach(id=>{
        const el = document.getElementById(id);
        if(el){
            el.addEventListener('input', () => {
                let start = el.selectionStart, end = el.selectionEnd;
                el.value = el.value.replace(/[^0-9]/g,'');
                el.setSelectionRange(start,end);
            });
        }
    });
    
    const sexSelect = document.getElementById('sex');
    if (sexSelect) {
        function updateSexText() {
            for (let opt of sexSelect.options) {
                opt.text = opt.text.toUpperCase();
            }
            sexSelect.style.textTransform = 'uppercase';
        }
        updateSexText();
        sexSelect.addEventListener('change', updateSexText);
    }
});
</script>
</head>
<body>

<header><h1><?= $edit ? 'Manage Students' : 'Manage Students' ?></h1></header>

<?php include_once __DIR__ . '/../nav.php'; ?>

<?php if ($error): ?>
    <div class="notification error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" class="student-form" novalidate>

    <!-- Header row with aligned title and back button -->
    <div class="form-header-row">
        <h2 class="form-title"><?= $edit
            ? 'Edit Student'
            : 'Add Student' ?></h2>
        <a href="student_dashboard.php" class="back-btn" role="button" aria-label="Back to Manage Students">Back</a>
    </div>

    <label for="lrn">LRN (12 digits) *</label>
    <input type="text" id="lrn" name="lrn" placeholder="123456789012" maxlength="12" required value="<?= htmlspecialchars(
        $data['lrn'] ?? ''
    ) ?>" />

    <label for="last_name">Last Name *</label>
    <input type="text" id="last_name" name="last_name" placeholder="Surname" required value="<?= htmlspecialchars(
        $data['last_name'] ?? ''
    ) ?>" />

    <label for="first_name">First Name *</label>
    <input type="text" id="first_name" name="first_name" placeholder="Given Name" required value="<?= htmlspecialchars(
        $data['first_name'] ?? ''
    ) ?>" />

    <label for="middle_initial">Middle Initial</label>
    <input type="text" id="middle_initial" name="middle_initial" placeholder="M" maxlength="1" value="<?= htmlspecialchars(
        $data['middle_initial'] ?? ''
    ) ?>" />

    <label for="sex">Sex *</label>
    <select id="sex" name="sex" required>
        <option value="">Select Sex</option>
        <option value="Male" <?= isset($data['sex']) && $data['sex'] == 'Male'
            ? 'selected'
            : '' ?>>Male</option>
        <option value="Female" <?= isset($data['sex']) &&
        $data['sex'] == 'Female'
            ? 'selected'
            : '' ?>>Female</option>
    </select>

    <label for="grade_level">Grade Level *</label>
    <select id="grade_level" name="grade_level" required>
        <option value="">Select Grade Level</option>
        <?php for ($i = 7; $i <= 12; $i++): ?>
        <option value="<?= $i ?>" <?= isset($data['grade_level']) &&
(int) $data['grade_level'] == $i
    ? 'selected'
    : '' ?>>Grade <?= $i ?></option>
        <?php endfor; ?>
    </select>

    <label for="section">Section *</label>
    <select id="section" name="section" required>
        <option value="">Select Section</option>
        <?php foreach ($sections as $sec): ?>
        <option value="<?= htmlspecialchars($sec) ?>" <?= isset(
    $data['section']
) && $data['section'] == $sec
    ? 'selected'
    : '' ?>><?= htmlspecialchars($sec) ?></option>
        <?php endforeach; ?>
    </select>

    <h3>Emergency Contact Info</h3>

    <label for="parent_name">Full Name of Parent/Guardian *</label>
    <input type="text" id="parent_name" name="parent_name" placeholder="Parent/Guardian Full Name" required value="<?= htmlspecialchars(
        $data['parent_name'] ?? ''
    ) ?>" />

    <label for="parent_address">Address *</label>
    <input type="text" id="parent_address" name="parent_address" placeholder="Parent/Guardian Address" required value="<?= htmlspecialchars(
        $data['parent_address'] ?? ''
    ) ?>" />

    <label for="parent_contact">Contact Number</label>
    <input type="text" id="parent_contact" name="parent_contact" placeholder="09123456789" value="<?= htmlspecialchars(
        $data['parent_contact'] ?? ''
    ) ?>" />

    <button type="submit">Submit</button>
</form>

</body>
</html>
