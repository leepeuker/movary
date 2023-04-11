const modal = new bootstrap.Modal('#usersModal', {
    keyboard: false
})

function deleteUser() {
    window.location.href = '/settings/users'
}

function saveUser() {
    window.location.href = '/settings/users'
}

const table = document.getElementById('usersTable');
const rows = table.getElementsByTagName('tr');

for (let i = 0; i < rows.length; i++) {
    rows[i].onclick = function() {
        document.getElementById('nameInput').value = this.cells[1].innerHTML
        document.getElementById('emailInput').value = this.cells[2].innerHTML
        document.getElementById('adminInput').checked = this.cells[3].innerHTML === '1'

        modal.show()
    };
}
