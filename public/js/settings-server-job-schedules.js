document.addEventListener('DOMContentLoaded', function () {
    const alertMessage = localStorage.getItem('alertMessageJobSchedules')
    if (alertMessage) {
        addAlert('alertJobSchedulesDiv', alertMessage, 'success')
    }
    localStorage.setItem('alertMessageJobSchedules', '')

    document.getElementById('createJobScheduleForm').addEventListener('submit', createJobSchedule)
    document.getElementById('createJobScheduleType').addEventListener('change', updateCreateJobScheduleFields)
    document.getElementById('jobSchedulesPerPage').addEventListener('change', () => refreshJobSchedulesPage(true))
    registerJobScheduleRowEvents()
    updateCreateJobScheduleFields()
})

let selectedJobScheduleId = null
let selectedJobScheduleEnabled = null

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
        row.addEventListener('click', () => showJobScheduleDetails(row))
        row.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return
            }

            event.preventDefault()
            showJobScheduleDetails(row)
        })
    })
}

function showJobScheduleDetails(row) {
    selectedJobScheduleId = row.dataset.jobScheduleId
    selectedJobScheduleEnabled = row.dataset.jobScheduleEnabled === 'true'

    document.getElementById('jobScheduleDetailsId').textContent = selectedJobScheduleId
    document.getElementById('jobScheduleDetailsUser').textContent = formatJobScheduleUser(
        row.dataset.jobScheduleUserName,
        row.dataset.jobScheduleUserId,
    )
    document.getElementById('jobScheduleDetailsType').textContent = row.dataset.jobScheduleType
    document.getElementById('jobScheduleDetailsCronExpression').textContent = row.dataset.jobScheduleCronExpression
    setLocalizedUtcDateTime(
        document.getElementById('jobScheduleDetailsNextRunAt'),
        row.dataset.jobScheduleNextRunAt,
    )
    document.getElementById('jobScheduleDetailsStatus').textContent = selectedJobScheduleEnabled ? 'enabled' : 'disabled'
    document.getElementById('jobScheduleDetailsParameters').textContent = formatJobScheduleParameters(
        row.dataset.jobScheduleParameters,
    )
    document.getElementById('jobScheduleDetailsAlerts').innerHTML = ''

    const enabledButton = document.getElementById('jobScheduleEnabledButton')
    enabledButton.textContent = selectedJobScheduleEnabled ? 'Disable schedule' : 'Enable schedule'
    bootstrap.Modal.getOrCreateInstance('#jobScheduleDetailsModal').show()
}

function formatJobScheduleUser(userName, userId) {
    if (userName === '-' && userId === '-') {
        return '-'
    }

    return userName + ' (ID ' + userId + ')'
}

function formatJobScheduleParameters(parameters) {
    try {
        const parsedParameters = JSON.parse(parameters)

        if (Object.keys(parsedParameters).length === 0) {
            return '-'
        }

        return JSON.stringify(parsedParameters, null, 2)
    } catch (error) {
        console.error(error)
        return parameters
    }
}

function toggleSelectedJobSchedule() {
    if (selectedJobScheduleId === null || selectedJobScheduleEnabled === null) {
        return
    }

    setJobScheduleEnabled(selectedJobScheduleId, selectedJobScheduleEnabled === false)
}

function removeSelectedJobSchedule() {
    if (selectedJobScheduleId === null) {
        return
    }

    removeJobSchedule(selectedJobScheduleId)
}

function showCreateJobScheduleModal() {
    document.getElementById('createJobScheduleForm').reset()
    document.getElementById('createJobScheduleError').classList.add('d-none')
    document.getElementById('createJobScheduleError').textContent = ''
    updateCreateJobScheduleFields()
    bootstrap.Modal.getOrCreateInstance('#createJobScheduleModal').show()
}

function updateCreateJobScheduleFields() {
    const selectedJobType = document.getElementById('createJobScheduleType').value

    document.querySelectorAll('[data-job-schedule-fields]').forEach((fields) => {
        const isSelected = fields.dataset.jobScheduleFields === selectedJobType
        fields.classList.toggle('d-none', isSelected === false)
        fields.querySelectorAll('input, select, textarea, button').forEach((control) => {
            control.disabled = isSelected === false
        })
    })
}

async function createJobSchedule(event) {
    event.preventDefault()

    const form = event.currentTarget
    const jobType = document.getElementById('createJobScheduleType').value
    const jobConfiguration = schedulableJobTypes[jobType]
    if (jobConfiguration === undefined) {
        showCreateJobScheduleError('This job type is not supported.')
        return
    }
    if (form.reportValidity() === false) {
        return
    }

    const formData = new FormData(form)
    formData.delete('jobType')
    if (
        jobConfiguration.prepareSubmission !== undefined &&
        await jobConfiguration.prepareSubmission(formData, jobConfiguration) === false
    ) {
        return
    }

    const createButton = document.getElementById('createJobScheduleSubmitButton')
    const errorAlert = document.getElementById('createJobScheduleError')
    createButton.disabled = true
    errorAlert.classList.add('d-none')
    errorAlert.textContent = ''

    try {
        const response = await fetchWithCsrf(APPLICATION_URL + '/jobs/schedule/' + jobConfiguration.endpoint, {
            method: 'POST',
            body: new URLSearchParams(formData),
            signal: AbortSignal.timeout(10000),
        })
        const responseBody = await response.json()

        if (!response.ok) {
            showCreateJobScheduleError(responseBody.error ?? 'Could not create job schedule.')
            return
        }

        bootstrap.Modal.getOrCreateInstance('#createJobScheduleModal').hide()
        localStorage.setItem(
            'alertMessageJobSchedules',
            'Created recurring schedule (ID ' + responseBody.scheduleId + ')',
        )
        window.location.reload()
    } catch (error) {
        console.error(error)
        showCreateJobScheduleError('Could not create job schedule.')
    } finally {
        createButton.disabled = false
    }
}

async function prepareUnboundedJobScheduleSubmission(formData, jobConfiguration) {
    if (formData.get('threshold') !== '' || formData.get('ids').trim() !== '') {
        return true
    }

    const confirmed = await showConfirmationModal({
        title: 'Create unbounded sync schedule',
        message: 'This schedule will synchronize ' + jobConfiguration.syncName + ' for every eligible ' + jobConfiguration.unboundedResourceName + ' on each run. Continue?',
        confirmLabel: 'Create schedule',
        confirmClass: 'btn-warning',
        restoreSourceModalOnConfirm: false,
    })

    if (confirmed === true) {
        formData.set('confirmUnbounded', '1')
    }

    return confirmed
}

function showCreateJobScheduleError(message) {
    const errorAlert = document.getElementById('createJobScheduleError')
    errorAlert.textContent = message
    errorAlert.classList.remove('d-none')
    bootstrap.Modal.getOrCreateInstance('#createJobScheduleModal').show()
}

async function setJobScheduleEnabled(scheduleId, enabled) {
    const enabledButton = document.getElementById('jobScheduleEnabledButton')
    enabledButton.disabled = true
    try {
        const response = await fetchWithCsrf(APPLICATION_URL + '/job-schedules/' + scheduleId + '/enabled', {
            method: 'POST',
            body: new URLSearchParams({enabled: enabled ? '1' : '0'}),
            signal: AbortSignal.timeout(10000),
        })
        if (!response.ok) {
            throw new Error('Response status: ' + response.status)
        }

        localStorage.setItem(
            'alertMessageJobSchedules',
            (enabled ? 'Enabled' : 'Disabled') + ' schedule ' + scheduleId,
        )
        window.location.reload()
    } catch (error) {
        console.error(error)
        addAlert('jobScheduleDetailsAlerts', 'Could not update schedule ' + scheduleId, 'danger')
    } finally {
        enabledButton.disabled = false
    }
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
        addAlert('jobScheduleDetailsAlerts', 'Could not delete schedule ' + scheduleId, 'danger')
        bootstrap.Modal.getOrCreateInstance('#jobScheduleDetailsModal').show()
    } finally {
        deleteButton.disabled = false
    }
}
