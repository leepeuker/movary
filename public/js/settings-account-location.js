const locationModal = new bootstrap.Modal('#locationModal', {keyboard: false})

const table = document.getElementById('locationsTable');
const rows = table.getElementsByTagName('tr');

document.addEventListener('DOMContentLoaded', function () {
    reloadTable()

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('toggle')) {
        let enableLocationsFeature = document.getElementById('toggleLocationsFeatureBtn').textContent === 'Disable locations'
        setLocationsAlert('Locations ' + (enableLocationsFeature === true ? 'enabled' : 'disabled'))
        window.history.replaceState(null, '', window.location.pathname);
    }
    let locationCreatedName = urlParams.get('locationCreated');
    if (locationCreatedName) {
        setLocationsAlert('Location was created: ' + locationCreatedName)
        window.history.replaceState(null, '', window.location.pathname);
    }
    let locationDeletedName = urlParams.get('locationDeleted');
    if (locationDeletedName) {
        setLocationsAlert('Location was deleted: ' + locationDeletedName)
        window.history.replaceState(null, '', window.location.pathname);
    }
    let locationUpdatedName = urlParams.get('locationUpdated');
    if (locationUpdatedName) {
        setLocationsAlert('Location was updated: ' + locationUpdatedName)
        window.history.replaceState(null, '', window.location.pathname);
    }
});

async function reloadTable() {
    table.getElementsByTagName('tbody')[0].innerHTML = ''

    if (document.getElementById('toggleLocationsFeatureBtn').textContent === 'Enable locations') {
        return
    }

    document.getElementById('locationsTableLoadingSpinner').classList.remove('d-none')

    const response = await fetch(APPLICATION_URL + '/settings/locations');

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
        row.innerHTML += '<td>' + location.name + '</td>';
        row.innerHTML += '<td class="d-none">' + location.isCinema + '</td>';
        row.style.cursor = 'pointer'

        table.getElementsByTagName('tbody')[0].appendChild(row);
    })

    registerTableRowClickEvent()
}

function setLocationsAlert(message, type = 'success') {
    const locationAlerts = document.getElementById('locationAlerts');
    locationAlerts.classList.remove('d-none');
    locationAlerts.innerHTML = '';
    locationAlerts.style.textAlign = 'center';

    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible`;
    alertDiv.setAttribute('role', 'alert');

    const textNode = document.createTextNode(message);
    alertDiv.appendChild(textNode);

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'btn-close';
    closeBtn.setAttribute('data-bs-dismiss', 'alert');
    closeBtn.setAttribute('aria-label', 'Close');

    alertDiv.appendChild(closeBtn);
    locationAlerts.appendChild(alertDiv);
}

function registerTableRowClickEvent() {
    for (let i = 0; i < rows.length; i++) {
        if (i === 0) continue

        rows[i].onclick = function () {

            prepareEditLocationsModal(
                this.dataset.id,
                this.cells[0].innerHTML,
                this.cells[1].innerHTML === 'true'
            )

            locationModal.show()
        };
    }
}

function prepareEditLocationsModal(id, name, isCinema) {
    document.getElementById('locationModalHeaderTitle').innerHTML = 'Edit Location'

    document.getElementById('locationModalFooterCreateButton').classList.add('d-none')
    document.getElementById('locationModalFooterButtons').classList.remove('d-none')

    document.getElementById('locationModalIdInput').value = id
    document.getElementById('locationModalNameInput').value = name
    document.getElementById('locationModalCinemaInput').checked = isCinema

    document.getElementById('locationModalAlerts').innerHTML = ''

    // Remove class invalid-input from all (input) elements
    Array.from(document.querySelectorAll('.invalid-input')).forEach((el) => el.classList.remove('invalid-input'));
}

function showCreateLocationModal() {
    prepareCreateLocationModal()
    locationModal.show()
}

function prepareCreateLocationModal() {
    document.getElementById('locationModalHeaderTitle').innerHTML = 'Create Location'

    document.getElementById('locationModalFooterCreateButton').classList.remove('d-none')
    document.getElementById('locationModalFooterButtons').classList.add('d-none')

    document.getElementById('locationModalIdInput').value = ''
    document.getElementById('locationModalNameInput').value = ''
    document.getElementById('locationModalCinemaInput').checked = false

    document.getElementById('locationModalAlerts').innerHTML = ''

    // Remove class invalid-input from all (input) elements
    Array.from(document.querySelectorAll('.invalid-input')).forEach((el) => el.classList.remove('invalid-input'));
}

document.getElementById('createLocationButton').addEventListener('click', async () => {
    if (validateCreateLocationInput() === true) {
        return;
    }

    let categoryName = document.getElementById('locationModalNameInput').value;
    let isCinema = document.getElementById('locationModalCinemaInput').checked;
    const response = await fetch(APPLICATION_URL + '/settings/locations', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            'name': categoryName,
            'isCinema': isCinema
        })
    })

    if (response.status !== 200) {
        setLocationModalAlertServerError(await response.text())
        return
    }

    let url = window.location.href;
    if (url.indexOf('?') > -1){
        url += '&locationCreated=' + categoryName
    } else {
        url += '?locationCreated=' + categoryName
    }
    window.location.href = url;
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

    const response = await fetch(APPLICATION_URL + '/settings/locations/' + document.getElementById('locationModalIdInput').value, {
        method: 'DELETE'
    });

    if (response.status !== 200) {
        setLocationModalAlertServerError()
        return
    }

    let categoryName = document.getElementById('locationModalNameInput').value;
    let url = window.location.href;
    if (url.indexOf('?') > -1){
        url += '&locationDeleted=' + categoryName
    } else {
        url += '?locationDeleted=' + categoryName
    }
    window.location.href = url;
})

document.getElementById('updateLocationButton').addEventListener('click', async () => {
    if (validateCreateLocationInput() === true) {
        return;
    }

    let locationName = document.getElementById('locationModalNameInput').value;
    let isCinema = document.getElementById('locationModalCinemaInput').checked;
    const response = await fetch(APPLICATION_URL + '/settings/locations/' + document.getElementById('locationModalIdInput').value, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            'name': locationName,
            'isCinema': isCinema
        })
    })

    if (response.status !== 200) {
        setLocationModalAlertServerError(await response.text())

        return
    }

    let url = window.location.href;
    if (url.indexOf('?') > -1){
        url += '&locationUpdated=' + locationName
    } else {
        url += '?locationUpdated=' + locationName
    }
    window.location.href = url;
})

async function toggleLocationFeature() {
    let enableLocationsFeature = document.getElementById('toggleLocationsFeatureBtn').textContent === 'Enable locations'
    await sendRequestToggleLocationsFeature(enableLocationsFeature)

    let url = window.location.href;
    if (url.indexOf('?') > -1){
        url += '&toggle=1'
    } else {
        url += '?toggle=1'
    }
    window.location.href = url;
}

async function sendRequestToggleLocationsFeature(isLocationsEnabled) {
    const response = await fetch(APPLICATION_URL + '/settings/locations/toggle-feature', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            'locationsEnabled': isLocationsEnabled,
        })
    })

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }
}
