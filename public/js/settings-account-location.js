const locationModal = new bootstrap.Modal('#locationModal', {keyboard: false})

const table = document.getElementById('locationsTable');
const rows = table.getElementsByTagName('tr');

document.addEventListener('DOMContentLoaded', function () {
    registerTableRowClickEvent()
    document.getElementById('locationsPerPage').addEventListener('change', updateLocationsPerPage)

    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('toggle')) {
        let enableLocationsFeature = document.getElementById('toggleLocationsFeatureBtn').textContent === 'Disable locations'
        setLocationsToggleAlert('Locations ' + (enableLocationsFeature === true ? 'enabled' : 'disabled'))
        removeQueryParameter('toggle')
    }
    let locationCreatedName = urlParams.get('locationCreated');
    if (locationCreatedName) {
        setLocationsAlert('Location was created: ' + locationCreatedName)
        removeQueryParameter('locationCreated')
    }
    let locationDeletedName = urlParams.get('locationDeleted');
    if (locationDeletedName) {
        setLocationsAlert('Location was deleted: ' + locationDeletedName)
        removeQueryParameter('locationDeleted')
    }
    let locationUpdatedName = urlParams.get('locationUpdated');
    if (locationUpdatedName) {
        setLocationsAlert('Location was updated: ' + locationUpdatedName)
        removeQueryParameter('locationUpdated')
    }
});

function updateLocationsPerPage() {
    const url = new URL(window.location.href)
    url.searchParams.set('perPage', document.getElementById('locationsPerPage').value)
    url.searchParams.set('page', '1')

    window.location.href = url.toString()
}

function refreshLocationsPage() {
    window.location.reload()
}

function removeQueryParameter(name) {
    const url = new URL(window.location.href)
    url.searchParams.delete(name)

    window.history.replaceState(null, '', url.toString())
}

function redirectWithNotification(name, value = '1') {
    const url = new URL(window.location.href)
    url.searchParams.set(name, value)

    window.location.href = url.toString()
}

function setLocationsToggleAlert(message, type = 'success') {
    setLocationsAlert(message, type, 'locationToggleAlerts')
}

function setLocationsAlert(message, type = 'success', alertContainerId = 'locationAlerts') {
    const locationAlerts = document.getElementById(alertContainerId);
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
                this.cells[0].textContent,
                this.dataset.isCinema === 'true'
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

    redirectWithNotification('locationCreated', categoryName)
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

document.getElementById('deleteLocationConfirmModal').addEventListener('show.bs.modal', () => {
    const locationName = document.getElementById('locationModalNameInput').value
    document.getElementById('deleteLocationConfirmMessage').textContent = 'Are you sure you want to delete the location "' + locationName + '"?'
    document.getElementById('deleteLocationConfirmAlerts').innerHTML = ''
})

document.getElementById('deleteLocationConfirmButton').addEventListener('click', async () => {
    const deleteButton = document.getElementById('deleteLocationConfirmButton')
    deleteButton.disabled = true

    let response
    try {
        response = await fetch(APPLICATION_URL + '/settings/locations/' + document.getElementById('locationModalIdInput').value, {
            method: 'DELETE'
        });
    } catch (error) {
        setLocationDeleteConfirmAlertServerError()

        return
    } finally {
        deleteButton.disabled = false
    }

    if (response.status !== 200) {
        setLocationDeleteConfirmAlertServerError(await response.text())
        return
    }

    let categoryName = document.getElementById('locationModalNameInput').value;
    redirectWithNotification('locationDeleted', categoryName)
})

function setLocationDeleteConfirmAlertServerError(message = "Server error, please try again.") {
    document.getElementById('deleteLocationConfirmAlerts').innerHTML = '<div class="alert alert-danger alert-dismissible" role="alert">' + message + '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>'
}

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

    redirectWithNotification('locationUpdated', locationName)
})

async function toggleLocationFeature() {
    let enableLocationsFeature = document.getElementById('toggleLocationsFeatureBtn').textContent === 'Enable locations'
    await sendRequestToggleLocationsFeature(enableLocationsFeature)

    redirectWithNotification('toggle')
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
