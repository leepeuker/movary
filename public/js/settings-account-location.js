const locationModal = new bootstrap.Modal('#locationModal', {keyboard: false})

const table = document.getElementById('locationsTable');
const rows = table.getElementsByTagName('tr');

reloadTable()

async function reloadTable(featureIsEnabled = true) {
    table.getElementsByTagName('tbody')[0].innerHTML = ''

    if (featureIsEnabled === false) {
        return
    }

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
        row.dataset.id = location.id
        row.innerHTML += '<td >' + location.name + '</td>';
        row.style.cursor = 'pointer'

        table.getElementsByTagName('tbody')[0].appendChild(row);
    })

    registerTableRowClickEvent()
}

function setLocationsAlert(message, type = 'success') {
    const locationAlerts = document.getElementById('locationAlerts');
    locationAlerts.classList.remove('d-none')
    locationAlerts.innerHTML = ''
    locationAlerts.innerHTML = '<div class="alert alert-' + type + ' alert-dismissible" role="alert">' + message + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'
    locationAlerts.style.textAlign = 'center'
}

function registerTableRowClickEvent() {
    for (let i = 0; i < rows.length; i++) {
        if (i === 0) continue

        rows[i].onclick = function () {
            prepareEditLocationsModal(
                this.dataset.id,
                this.cells[0].innerHTML
            )

            locationModal.show()
        };
    }
}

function prepareEditLocationsModal(id, name) {
    document.getElementById('locationModalHeaderTitle').innerHTML = 'Edit Location'

    document.getElementById('locationModalFooterCreateButton').classList.add('d-none')
    document.getElementById('locationModalFooterButtons').classList.remove('d-none')

    document.getElementById('locationModalIdInput').value = id
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

document.getElementById('deleteLocationButton').addEventListener('click', async () => {
    if (confirm('Are you sure you want to delete the location?') === false) {
        return
    }

    const response = await fetch('/settings/locations/' + document.getElementById('locationModalIdInput').value, {
        method: 'DELETE'
    });

    if (response.status !== 200) {
        setLocationModalAlertServerError()
        return
    }

    setLocationsAlert('Location was deleted: ' + document.getElementById('locationModalNameInput').value)

    reloadTable()
    locationModal.hide()
})

document.getElementById('updateLocationButton').addEventListener('click', async () => {
    if (validateCreateLocationInput() === true) {
        return;
    }

    const response = await fetch('/settings/locations/' + document.getElementById('locationModalIdInput').value, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            'name': document.getElementById('locationModalNameInput').value
        })
    })

    if (response.status !== 200) {
        setLocationModalAlertServerError(await response.text())

        return
    }

    setLocationsAlert('Location was updated: ' + document.getElementById('locationModalNameInput').value)

    reloadTable()
    locationModal.hide()
})

function disableLocationFeature() {
    let featureIsDisabled = document.getElementById('toggleLocationsFeatureBtn').textContent === 'Enable locations'
    setLocationFeatureBtnState(!featureIsDisabled)
    setLocationTableState(featureIsDisabled)
    reloadTable(featureIsDisabled)

    setLocationsAlert('Locations ' + (featureIsDisabled === true ? 'enabled' : 'disabled'))
}

function setLocationFeatureBtnState(featureIsEnabled) {
    if (featureIsEnabled === true) {
        document.getElementById('toggleLocationsFeatureBtn').classList.add('btn-primary')
        document.getElementById('toggleLocationsFeatureBtn').classList.remove('btn-outline-danger')
        document.getElementById('toggleLocationsFeatureBtn').textContent = 'Enable locations'

        return
    }

    document.getElementById('toggleLocationsFeatureBtn').classList.add('btn-outline-danger')
    document.getElementById('toggleLocationsFeatureBtn').classList.remove('btn-primary')
    document.getElementById('toggleLocationsFeatureBtn').textContent = 'Disable locations'

}

function setLocationTableState(featureIsEnabled) {
    if (featureIsEnabled === true) {
        document.getElementById('createLocationBtn').disabled = false

        return
    }

    document.getElementById('createLocationBtn').disabled = true
}