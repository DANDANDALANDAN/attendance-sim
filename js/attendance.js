let timer;

function processScan(id) {
  if (!id) return;
  clearTimeout(timer);
  
  fetch('ajax/attendance.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `id=${encodeURIComponent(id)}`
  })
  .then(response => response.json())
  .then(data => {
    if (data.status === 'ok') {
      document.getElementById('display').innerHTML = `
        <div class="card">
          <img src="students/photos/default.png" alt="Photo" />
          <h2>${data.name}</h2>
          <p>${data.section}</p>
          <p>${data.time} - ${data.type}</p>
        </div>`;
      alert(`SMS to Emergency: ${data.name} (${data.section}) at ${data.time} [${data.type}]`);
      timer = setTimeout(() => {
        document.getElementById('display').innerHTML = '';
      }, 3000);
    } else {
      alert('Error: ' + (data.message || 'Unknown error'));
    }
  })
  .catch(() => {
    alert('Failed to record attendance.');
  });
}
