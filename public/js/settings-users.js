document.addEventListener('DOMContentLoaded', function () {
    const modal = new bootstrap.Modal('#usersModal', {
        keyboard: false
    })

    const table = document.getElementById('usersTable');
    const rows = table.getElementsByTagName('tr');

    for (let i = 0; i < rows.length; i++) {
        rows[i].onclick = function() {
            document.getElementById('idInput').value = this.cells[0].innerHTML
            document.getElementById('nameInput').value = this.cells[1].innerHTML
            document.getElementById('emailInput').value = this.cells[2].innerHTML
            document.getElementById('adminInput').checked = this.cells[3].innerHTML === '1'

            modal.show()
        };
    }

});

async function saveUser() {
    const response = await fetch('/settings/users/' + document.getElementById('idInput').value, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            'name': document.getElementById('nameInput').value,
            'email': document.getElementById('emailInput').value,
            'isAdmin': document.getElementById('adminInput').checked,
        })
    })

    window.location.href = '/settings/users'
}

async function deleteUser() {
    if (confirm('Are you sure you want to delete the user?') === false) {
        return
    }

    const response = await fetch('/settings/users/' + document.getElementById('idInput').value, {
        method: 'DELETE'
    });

    window.location.href = '/settings/users'
}

