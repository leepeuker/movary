document.addEventListener('DOMContentLoaded', function () {
    const alertMessage = localStorage.getItem('alertMessageJobSchedules')
    if (alertMessage) {
        addAlert('alertJobSchedulesDiv', alertMessage, 'success')
    }
    localStorage.setItem('alertMessageJobSchedules', '')

    document.getElementById('jobScheduleForm').addEventListener('submit', saveJobSchedule)
    document.getElementById('jobScheduleType').addEventListener('change', updateJobScheduleFields)
    document.getElementById('jobSchedulesPerPage').addEventListener('change', () => refreshJobSchedulesPage(true))
    registerJobScheduleRowEvents()
    updateJobScheduleFields()
})

let selectedJobScheduleId = null

const schedulableJobTypes = {
    tmdb_movie_sync: {
        endpoint: 'tmdb-movie-sync',
        prepareSubmission: prepareUnboundedJobScheduleSubmission,
        syncName: 'TMDB movie metadata',
        unboundedResourceName: 'movie',
    },
    tmdb_person_sync: {
        endpoint: 'tmdb-person-sync',
        prepareSubmission: prepareUnboundedJobScheduleSubmission,
        syncName: 'TMDB person metadata',
        unboundedResourceName: 'person',
    },
    tmdb_image_cache: {
        endpoint: 'tmdb-image-cache-refresh',
    },
    imdb_sync: {
        endpoint: 'imdb-ratings-sync',
        prepareSubmission: prepareUnboundedJobScheduleSubmission,
        syncName: 'IMDb ratings',
        unboundedResourceName: 'movie',
    },
}

function refreshJobSchedulesPage(resetPage = false) {
    const schedulesPerPage = document.getElementById('jobSchedulesPerPage').value
    const url = new URL(window.location.href)

    url.searchParams.set('perPage', schedulesPerPage)
    if (resetPage) {
        url.searchParams.set('page', '1')
    }
    window.location.href = url.toString()
}

function applyJobScheduleFilters() {
    const url = new URL(window.location.href)

    setJobScheduleQueryParameter(url, 'user', document.getElementById('jobScheduleFilterUser').value)
    setJobScheduleQueryParameter(url, 'type', document.getElementById('jobScheduleFilterType').value)
    setJobScheduleQueryParameter(url, 'status', document.getElementById('jobScheduleFilterStatus').value)
    url.searchParams.set('page', '1')

    window.location.href = url.toString()
}

function resetJobScheduleFilters() {
    document.getElementById('jobScheduleFilterUser').value = ''
    document.getElementById('jobScheduleFilterType').value = ''
    document.getElementById('jobScheduleFilterStatus').value = ''
    applyJobScheduleFilters()
}

function setJobScheduleQueryParameter(url, name, value) {
    if (value === '') {
        url.searchParams.delete(name)
        return
    }

    url.searchParams.set(name, value)
}

function registerJobScheduleRowEvents() {
    document.querySelectorAll('#jobSchedulesTable tbody tr').forEach((row) => {
        row.addEventListener('click', () => showEditJobScheduleModal(row))
        row.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return
            }

            event.preventDefault()
            showEditJobScheduleModal(row)
        })
    })
}

function showEditJobScheduleModal(row) {
    selectedJobScheduleId = row.dataset.jobScheduleId

    prepareJobScheduleModal('Edit Job Schedule', 'Save', false)
    document.getElementById('jobScheduleType').value = row.dataset.jobScheduleType
    document.getElementById('jobScheduleCronExpression').value = row.dataset.jobScheduleCronExpression
    document.getElementById('jobScheduleEnabled').checked = row.dataset.jobScheduleEnabled === 'true'
    updateJobScheduleFields()
    populateJobScheduleParameters(row.dataset.jobScheduleParameters)
    document.getElementById('jobScheduleDeleteButton').classList.remove('d-none')
    bootstrap.Modal.getOrCreateInstance('#jobScheduleModal').show()
}

function removeSelectedJobSchedule() {
    if (selectedJobScheduleId === null) {
        return
    }

    removeJobSchedule(selectedJobScheduleId)
}

function showCreateJobScheduleModal() {
    selectedJobScheduleId = null
    prepareJobScheduleModal('Create Job Schedule', 'Create', true)
    document.getElementById('jobScheduleDeleteButton').classList.add('d-none')
    updateJobScheduleFields()
    bootstrap.Modal.getOrCreateInstance('#jobScheduleModal').show()
}

function prepareJobScheduleModal(title, submitLabel, create) {
    document.getElementById('jobScheduleForm').reset()
    document.getElementById('jobScheduleModalTitle').textContent = title
    document.getElementById('jobScheduleSubmitButton').textContent = submitLabel
    document.getElementById('jobScheduleEnabled').disabled = create
    document.getElementById('jobScheduleModalError').classList.add('d-none')
    document.getElementById('jobScheduleModalError').textContent = ''
}

function populateJobScheduleParameters(serializedParameters) {
    let parameters
    try {
        parameters = JSON.parse(serializedParameters)
    } catch (error) {
        console.error(error)
        showJobScheduleModalError('Could not read the schedule parameters.')
        return
    }

    const selectedFields = document.querySelector(
        '[data-job-schedule-fields="' + document.getElementById('jobScheduleType').value + '"]',
    )
    selectedFields.querySelectorAll('[name]').forEach((control) => {
        const value = parameters[control.name]
        if (control.type === 'checkbox') {
            control.checked = value === true
        } else if (Array.isArray(value)) {
            control.value = value.join(', ')
        } else {
            control.value = value ?? ''
        }
    })
}

function updateJobScheduleFields() {
    const selectedJobType = document.getElementById('jobScheduleType').value

    document.querySelectorAll('[data-job-schedule-fields]').forEach((fields) => {
        const isSelected = fields.dataset.jobScheduleFields === selectedJobType
        fields.classList.toggle('d-none', isSelected === false)
        fields.querySelectorAll('input, select, textarea, button').forEach((control) => {
            control.disabled = isSelected === false
        })
    })
}

async function saveJobSchedule(event) {
    event.preventDefault()

    const form = event.currentTarget
    const jobType = document.getElementById('jobScheduleType').value
    const jobConfiguration = schedulableJobTypes[jobType]
    if (jobConfiguration === undefined) {
        showJobScheduleModalError('This job type is not supported.')
        return
    }
    if (form.reportValidity() === false) {
        return
    }

    const formData = new FormData(form)
    const editing = selectedJobScheduleId !== null
    if (editing) {
        formData.set('enabled', document.getElementById('jobScheduleEnabled').checked ? '1' : '0')
    } else {
        formData.delete('jobType')
        formData.delete('enabled')
    }
    if (
        jobConfiguration.prepareSubmission !== undefined &&
        await jobConfiguration.prepareSubmission(formData, jobConfiguration) === false
    ) {
        return
    }

    const submitButton = document.getElementById('jobScheduleSubmitButton')
    const errorAlert = document.getElementById('jobScheduleModalError')
    submitButton.disabled = true
    errorAlert.classList.add('d-none')
    errorAlert.textContent = ''

    try {
        const url = editing
            ? APPLICATION_URL + '/job-schedules/' + selectedJobScheduleId
            : APPLICATION_URL + '/jobs/schedule/' + jobConfiguration.endpoint
        const response = await fetchWithCsrf(url, {
            method: 'POST',
            body: new URLSearchParams(formData),
            signal: AbortSignal.timeout(10000),
        })
        const responseBody = response.status === 204 ? {} : await response.json()

        if (!response.ok) {
            showJobScheduleModalError(responseBody.error ?? 'Could not save job schedule.')
            return
        }

        bootstrap.Modal.getOrCreateInstance('#jobScheduleModal').hide()
        localStorage.setItem(
            'alertMessageJobSchedules',
            editing
                ? 'Updated schedule ' + selectedJobScheduleId
                : 'Created recurring schedule (ID ' + responseBody.scheduleId + ')',
        )
        window.location.reload()
    } catch (error) {
        console.error(error)
        showJobScheduleModalError('Could not save job schedule.')
    } finally {
        submitButton.disabled = false
    }
}

async function prepareUnboundedJobScheduleSubmission(formData, jobConfiguration) {
    if (formData.get('threshold') !== '' || formData.get('ids').trim() !== '') {
        return true
    }

    const confirmed = await showConfirmationModal({
        title: selectedJobScheduleId === null ? 'Create unbounded sync schedule' : 'Update unbounded sync schedule',
        message: 'This schedule will synchronize ' + jobConfiguration.syncName + ' for every eligible ' + jobConfiguration.unboundedResourceName + ' on each run. Continue?',
        confirmLabel: selectedJobScheduleId === null ? 'Create schedule' : 'Update schedule',
        confirmClass: 'btn-warning',
        restoreSourceModalOnConfirm: false,
    })

    if (confirmed === true) {
        formData.set('confirmUnbounded', '1')
    }

    return confirmed
}

function showJobScheduleModalError(message) {
    const errorAlert = document.getElementById('jobScheduleModalError')
    errorAlert.textContent = message
    errorAlert.classList.remove('d-none')
    bootstrap.Modal.getOrCreateInstance('#jobScheduleModal').show()
}

async function removeJobSchedule(scheduleId) {
    const confirmed = await showConfirmationModal({
        title: 'Delete recurring schedule',
        message: 'Delete recurring schedule ' + scheduleId + '? Existing queue jobs are not removed.',
        confirmLabel: 'Delete schedule',
        confirmClass: 'btn-danger',
    })
    if (confirmed === false) {
        return
    }

    const deleteButton = document.getElementById('jobScheduleDeleteButton')
    deleteButton.disabled = true
    try {
        const response = await fetchWithCsrf(APPLICATION_URL + '/job-schedules/' + scheduleId, {
            method: 'DELETE',
            signal: AbortSignal.timeout(10000),
        })
        if (!response.ok) {
            throw new Error('Response status: ' + response.status)
        }

        localStorage.setItem('alertMessageJobSchedules', 'Deleted schedule ' + scheduleId)
        window.location.reload()
    } catch (error) {
        console.error(error)
        showJobScheduleModalError('Could not delete schedule ' + scheduleId)
    } finally {
        deleteButton.disabled = false
    }
}

async function deleteAllJobSchedules() {
    const confirmed = await showConfirmationModal({
        title: 'Delete all recurring schedules',
        message: 'Delete all recurring schedules? Existing queue jobs are not removed.',
        confirmLabel: 'Delete all schedules',
        confirmClass: 'btn-danger',
    })
    if (confirmed === false) {
        return
    }

    const deleteButton = document.getElementById('deleteAllJobSchedulesButton')
    deleteButton.disabled = true
    try {
        const response = await fetchWithCsrf(APPLICATION_URL + '/job-schedules', {
            method: 'DELETE',
            signal: AbortSignal.timeout(10000),
        })
        if (!response.ok) {
            throw new Error('Response status: ' + response.status)
        }

        localStorage.setItem('alertMessageJobSchedules', 'Deleted all recurring schedules')
        window.location.reload()
    } catch (error) {
        console.error(error)
        addAlert('alertJobSchedulesDiv', 'Could not delete all recurring schedules', 'danger')
    } finally {
        deleteButton.disabled = false
    }
}
