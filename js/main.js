function deleteStudent(id) {
    if (!confirm('Are you sure you want to delete this student?')) return;

    // Adjust the fetch URL depending on your folder structure.
    // If your current page is in /students/, use '../ajax/student_actions.php'
    // If in root, use 'ajax/student_actions.php'

    // Example for student_dashboard.php inside /students/:
    const ajaxUrl = '../ajax/student_actions.php';

    fetch(ajaxUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=delete&id=${encodeURIComponent(id)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Student deleted successfully.');
            location.reload();
        } else {
            alert('Error deleting student: ' + data.message);
        }
    })
    .catch(error => {
        alert('Request failed: ' + error);
    });
}
