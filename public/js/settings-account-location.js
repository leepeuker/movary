const locationModal = new bootstrap.Modal('#locationModal', {keyboard: false})

const table = document.getElementById('locationsTable');
const rows = table.getElementsByTagName('tr');

reloadTable()

async function reloadTable() {
    table.getElementsByTagName('tbody')[0].innerHTML = ''
    document.getElementById('locationsTableLoadingSpinner').classList.remove('d-none')

    const response = await fetch('/settings/locations');

    console.log(response.status)
    if (response.status !== 200) {
        setLocationsAlert('Could not load locations', 'danger')
        document.getElementById('locationsTableLoadingSpinner').classList.add('d-none')

        return
    }

    const locations = await response.json();

    document.getElementById('locationsTableLoadingSpinner').classList.add('d-none')

    locations.forEach((location) => {
        let row = document.createElement('tr');
        row.innerHTML = '<td>' + location.id + '</td>';
        row.innerHTML += '<td>' + location.name + '</td>';
        row.style.cursor = 'pointer'

        table.getElementsByTagName('tbody')[0].appendChild(row);
    })

    registerTableRowClickEvent()
}

function setLocationsAlert(message, type = 'success') {
    const locationManagementAlerts = document.getElementById('locationAlerts');
    locationManagementAlerts.classList.remove('d-none')
    locationManagementAlerts.innerHTML = ''
    locationManagementAlerts.innerHTML = '<div class="alert alert-' + type + ' alert-dismissible" role="alert">' + message + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'
    locationManagementAlerts.style.textAlign = 'center'
}

function registerTableRowClickEvent() {
    for (let i = 0; i < rows.length; i++) {
        if (i === 0) continue

        rows[i].onclick = function () {
            prepareEditLocationsModal(
                this.cells[0].innerHTML,
                this.cells[1].innerHTML,
                this.cells[2].innerHTML,
                this.cells[3].innerHTML === '1'
            )

            locationModal.show()
        };
    }
}

function prepareEditLocationsModal(id, name) {
    document.getElementById('locationModalHeaderTitle').innerHTML = 'Edit User'

    document.getElementById('locationModalFooterCreateButton').classList.add('d-none')
    document.getElementById('locationModalFooterButtons').classList.remove('d-none')

    document.getElementById('locationModalNameInput').value = name

    document.getElementById('locationModalAlerts').innerHTML = ''

    // Remove class invalid-input from all (input) elements
    Array.from(document.querySelectorAll('.invalid-input')).forEach((el) => el.classList.remove('invalid-input'));
}

function showCreateLocationModal() {
    prepareCreateLocationModal()
    locationModal.show()
}

function prepareCreateLocationModal(name) {
    document.getElementById('locationModalHeaderTitle').innerHTML = 'Create Location'

    document.getElementById('locationModalFooterCreateButton').classList.remove('d-none')
    document.getElementById('locationModalFooterButtons').classList.add('d-none')

    document.getElementById('locationModalIdInput').value = ''
    document.getElementById('locationModalNameInput').value = ''

    document.getElementById('locationModalAlerts').innerHTML = ''

    // Remove class invalid-input from all (input) elements
    Array.from(document.querySelectorAll('.invalid-input')).forEach((el) => el.classList.remove('invalid-input'));
}

document.getElementById('createLocationButton').addEventListener('click', async () => {
    if (validateCreateLocationInput() === true) {
        return;
    }

    const response = await fetch('/settings/locations', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            'name': document.getElementById('locationModalNameInput').value,
        })
    })

    if (response.status !== 200) {
        setLocationModalAlertServerError(await response.text())
        return
    }

    setLocationsAlert('Location was created: ' + document.getElementById('locationModalNameInput').value)

    reloadTable()
    locationModal.hide()
})

function setLocationModalAlertServerError(message = "Server error, please try again.") {
    document.getElementById('locationModalAlerts').innerHTML = '<div class="alert alert-danger alert-dismissible" role="alert">' + message + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'
}


function validateCreateLocationInput() {
    let error = false

    const nameInput = document.getElementById('locationModalNameInput');

    let mustNotBeEmptyInputs = [nameInput]

    mustNotBeEmptyInputs.forEach((input) => {
        input.classList.remove('invalid-input');
        if (input.value.toString() === '') {
            input.classList.add('invalid-input');

            error = true
        }
    })

    return error
}
