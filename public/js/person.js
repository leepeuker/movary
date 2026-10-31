const PROFILE_BATCH_SIZE = 12
let availableProfiles = null
let renderedProfileCount = 0

document.addEventListener('DOMContentLoaded', () => {
    const changeProfileModal = document.getElementById('changeProfileModal')
    if (changeProfileModal === null) {
        return
    }

    changeProfileModal.addEventListener('show.bs.modal', loadProfileChoices)
    document.getElementById('showMoreProfilesButton').addEventListener('click', renderNextProfileBatch)
    document.getElementById('useSelectedProfileButton').addEventListener('click', useSelectedProfile)
    document.getElementById('restoreDefaultProfileButton').addEventListener('click', restoreDefaultProfile)
})

async function loadProfileChoices() {
    if (availableProfiles !== null) {
        return
    }

    const loading = document.getElementById('profileSelectionLoading')
    const error = document.getElementById('profileSelectionError')
    loading.classList.remove('d-none')
    error.classList.add('d-none')

    try {
        const response = await fetch(APPLICATION_URL + '/persons/' + document.getElementById('personId').value + '/profiles')
        if (response.ok === false) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }

        const data = await response.json()
        availableProfiles = data.profiles
        document.getElementById('restoreDefaultProfileButton').classList.toggle('d-none', data.hasOverride !== true)
        loading.classList.add('d-none')

        if (availableProfiles.length === 0) {
            document.getElementById('profileSelectionEmpty').classList.remove('d-none')
            return
        }

        renderNextProfileBatch()
    } catch (requestError) {
        loading.classList.add('d-none')
        error.classList.remove('d-none')
    }
}

function renderNextProfileBatch() {
    const grid = document.getElementById('profileSelectionGrid')
    const profilesToRender = availableProfiles.slice(renderedProfileCount, renderedProfileCount + PROFILE_BATCH_SIZE)

    profilesToRender.forEach((profile, offset) => {
        const index = renderedProfileCount + offset
        const column = document.createElement('div')
        column.className = 'col'

        const input = document.createElement('input')
        input.type = 'radio'
        input.className = 'btn-check'
        input.name = 'personProfile'
        input.id = 'personProfile' + index
        input.value = profile.path
        input.addEventListener('change', () => {
            document.getElementById('useSelectedProfileButton').disabled = false
        })

        const label = document.createElement('label')
        label.className = 'person-profile-option'
        label.htmlFor = input.id

        const image = document.createElement('img')
        image.className = 'img-fluid rounded'
        image.src = profile.previewUrl
        image.alt = 'TMDB profile image option ' + (index + 1)
        image.loading = 'lazy'

        label.appendChild(image)
        column.appendChild(input)
        column.appendChild(label)
        grid.appendChild(column)
    })

    renderedProfileCount += profilesToRender.length
    document.getElementById('showMoreProfilesButton').classList.toggle(
        'd-none',
        renderedProfileCount >= availableProfiles.length,
    )
}

async function useSelectedProfile() {
    const selectedProfile = document.querySelector('input[name="personProfile"]:checked')
    if (selectedProfile === null) {
        return
    }

    await updatePersonProfile('PUT', JSON.stringify({profilePath: selectedProfile.value}))
}

async function restoreDefaultProfile() {
    const confirmed = await showConfirmationModal({
        title: 'Restore default profile image',
        message: 'Are you sure you want to restore the default profile image?',
        confirmLabel: 'Restore default',
        confirmClass: 'btn-danger',
    })

    if (confirmed === false) {
        return
    }

    await updatePersonProfile('DELETE')
}

async function updatePersonProfile(method, body = null) {
    const saveButton = document.getElementById('useSelectedProfileButton')
    const restoreButton = document.getElementById('restoreDefaultProfileButton')
    saveButton.disabled = true
    restoreButton.disabled = true

    try {
        const response = await fetchWithCsrf(APPLICATION_URL + '/persons/' + document.getElementById('personId').value + '/profile', {
            method: method,
            headers: {'Content-Type': 'application/json'},
            body: body,
        })
        if (response.ok === false) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }

        window.location.reload()
    } catch (requestError) {
        const profileModal = document.getElementById('changeProfileModal')
        if (profileModal.classList.contains('show') === false) {
            bootstrap.Modal.getOrCreateInstance(profileModal).show()
        }

        document.getElementById('profileSelectionError').textContent = 'Could not update the profile image.'
        document.getElementById('profileSelectionError').classList.remove('d-none')
        saveButton.disabled = document.querySelector('input[name="personProfile"]:checked') === null
        restoreButton.disabled = false
    }
}

function toggleBiography() {
    let expandContainer = document.getElementById('expandContainer');
    if (document.getElementsByClassName('truncated').length > 0) {
        document.getElementById('biographyParagraph').classList.remove('truncated');
        expandContainer.getElementsByTagName('i')[0].classList.remove('bi-chevron-down');
        expandContainer.getElementsByTagName('i')[0].classList.add('bi-chevron-up');
        expandContainer.children[1].innerHTML = 'Show less&#8230;';
    } else {
        document.getElementById('biographyParagraph').classList.add('truncated');
        expandContainer.getElementsByTagName('i')[0].classList.add('bi-chevron-down');
        expandContainer.getElementsByTagName('i')[0].classList.remove('bi-chevron-up');
        expandContainer.children[1].innerHTML = 'Show more&#8230;';
    }
}

document.addEventListener("DOMContentLoaded", () => {
    let biographyHeight = document.getElementById('biographyParagraph').offsetHeight;
    let windowHeight = window.outerHeight;
    if (((biographyHeight / windowHeight) * 100) > 20) {
        document.getElementById('biographyParagraph').classList.add('truncated');
        document.getElementById('expandContainer').classList.remove('d-none');
    }
});

function refreshTmdbData() {
    disableMoreModalButtons(true)
    removeAlert('alertPersonOptionModalDiv')

    sendRequest('refresh-tmdb').then(() => {
        location.reload()
    }).catch(() => {
        addAlert('alertPersonOptionModalDiv', 'Cannot refresh TMDB data', 'danger')
    }).finally(() => {
        disableMoreModalButtons(false)
    })
}

function hideInTopLists() {
    disableMoreModalButtons(true)
    removeAlert('alertPersonOptionModalDiv')

    sendRequest('hide-in-top-lists').then(() => {
        document.getElementById('hideInTopListsButton').classList.add('d-none');
        document.getElementById('showInTopListsButton').classList.remove('d-none');
        addAlert('alertPersonOptionModalDiv', 'Person not visible in top lists anymore', 'success')
    }).catch(() => {
        addAlert('alertPersonOptionModalDiv', 'Cannot hide person in top lists', 'danger')
    }).finally(() => {
        disableMoreModalButtons(false)
    })
}

function showInTopLists() {
    disableMoreModalButtons(true)
    removeAlert('alertPersonOptionModalDiv')

    sendRequest('show-in-top-lists').then(() => {
        document.getElementById('hideInTopListsButton').classList.remove('d-none');
        document.getElementById('showInTopListsButton').classList.add('d-none');
        addAlert('alertPersonOptionModalDiv', 'Person visible in top lists again', 'success')
    }).catch(() => {
        addAlert('alertPersonOptionModalDiv', 'Cannot show person in top lists', 'danger')
    }).finally(() => {
        disableMoreModalButtons(false)
    })
}

async function sendRequest(action) {
    const personId = document.getElementById('personId').value;
    const response = await fetchWithCsrf(
        APPLICATION_URL + '/persons/' + personId + '/' + action,
        {method: 'POST'}
    )

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }

    return true
}

function disableMoreModalButtons(disable) {
    document.getElementById('refreshTmdbDataButton').disabled = disable;
    document.getElementById('hideInTopListsButton').disabled = disable;
    document.getElementById('showInTopListsButton').disabled = disable;
}
