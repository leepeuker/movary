document.addEventListener("DOMContentLoaded", function () {
    // localStorage here is a "hack" to keep state after page refresh, it would be better to not refresh the page and load the table via AJAX
    const alertMessageJobs = localStorage.getItem('alertMessageJobQueue');
    if (alertMessageJobs) {
        addAlert('alertJobsDiv', alertMessageJobs, 'success');
    }
    localStorage.setItem('alertMessageJobQueue', '')

    registerJobRowEvents()
    document.getElementById('jobsPerPage').addEventListener('change', () => refreshPage(true))
    document.getElementById('createJobForm').addEventListener('submit', createJob)
    document.getElementById('createJobType').addEventListener('change', updateCreateJobFields)
    document.getElementById('removeJobsForm').addEventListener('submit', removeJobs)
    document.getElementById('removeJobsModal').addEventListener('show.bs.modal', resetRemoveJobsForm)
    updateCreateJobFields()
});

let selectedJobId = null
let selectedJobStatus = null

const creatableJobTypes = {
    tmdb_movie_sync: {
        endpoint: 'tmdb-movie-sync',
        filterType: 'tmdb_movie_sync',
        prepareSubmission: prepareUnboundedSyncSubmission,
        syncName: 'TMDB movie metadata',
        unboundedResourceName: 'movie',
    },
    tmdb_person_sync: {
        endpoint: 'tmdb-person-sync',
        filterType: 'tmdb_person_sync',
        prepareSubmission: prepareUnboundedSyncSubmission,
        syncName: 'TMDB person metadata',
        unboundedResourceName: 'person',
    },
    tmdb_image_cache: {
        endpoint: 'tmdb-image-cache-refresh',
        filterType: 'tmdb_image_cache',
    },
    imdb_sync: {
        endpoint: 'imdb-ratings-sync',
        filterType: 'imdb_sync',
        prepareSubmission: prepareUnboundedSyncSubmission,
        syncName: 'IMDb ratings',
        unboundedResourceName: 'movie',
    },
}

function showCreateJobModal() {
    document.getElementById('createJobForm').reset()
    document.getElementById('createJobError').classList.add('d-none')
    document.getElementById('createJobError').textContent = ''
    updateCreateJobFields()
    bootstrap.Modal.getOrCreateInstance('#createJobModal').show()
}

function updateCreateJobFields() {
    const selectedJobType = document.getElementById('createJobType').value

    document.querySelectorAll('[data-job-fields]').forEach((fields) => {
        const isSelected = fields.dataset.jobFields === selectedJobType
        fields.classList.toggle('d-none', isSelected === false)
        fields.querySelectorAll('input, select, textarea, button').forEach((control) => {
            control.disabled = isSelected === false
        })
    })
}

async function createJob(event) {
    event.preventDefault()

    const form = event.currentTarget
    const jobType = document.getElementById('createJobType').value
    const jobConfiguration = creatableJobTypes[jobType]
    if (jobConfiguration === undefined) {
        showCreateJobError('This job type is not supported.')
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

    const createButton = document.getElementById('createJobSubmitButton')
    const errorAlert = document.getElementById('createJobError')
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
            showCreateJobError(responseBody.error ?? 'Could not create job.')
            return
        }

        bootstrap.Modal.getOrCreateInstance('#createJobModal').hide()
        localStorage.setItem('alertMessageJobQueue', 'Created Job (ID ' + responseBody.jobId + ')')

        const url = new URL(window.location.href)
        url.search = ''
        url.searchParams.set('type', jobConfiguration.filterType)
        url.searchParams.set('user', responseBody.userId)
        window.location.href = url.toString()
    } catch (error) {
        console.error(error)
        showCreateJobError('Could not create job.')
    } finally {
        createButton.disabled = false
    }
}

async function prepareUnboundedSyncSubmission(formData, jobConfiguration) {
    if (formData.get('threshold') !== '' || formData.get('ids').trim() !== '') {
        return true
    }

    const confirmed = await showConfirmationModal({
        title: 'Create unbounded sync job',
        message: 'This job will synchronize ' + jobConfiguration.syncName + ' for every eligible ' + jobConfiguration.unboundedResourceName + '. Continue?',
        confirmLabel: 'Create job',
        confirmClass: 'btn-warning',
        restoreSourceModalOnConfirm: false,
    })

    if (confirmed === true) {
        formData.set('confirmUnbounded', '1')
    }

    return confirmed
}

function showCreateJobError(message) {
    const errorAlert = document.getElementById('createJobError')
    errorAlert.textContent = message
    errorAlert.classList.remove('d-none')
    bootstrap.Modal.getOrCreateInstance('#createJobModal').show()
}

function registerJobRowEvents() {
    document.querySelectorAll('#jobsTable tbody tr').forEach((row) => {
        row.addEventListener('click', () => showJobDetails(row))
        row.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return
            }

            event.preventDefault()
            showJobDetails(row)
        })
    })
}

function showJobDetails(row) {
    selectedJobId = row.dataset.jobId
    selectedJobStatus = row.dataset.jobStatus

    document.getElementById('jobDetailsId').textContent = row.dataset.jobId
    document.getElementById('jobDetailsUser').textContent = formatJobUser(row.dataset.jobUserName, row.dataset.jobUserId)
    document.getElementById('jobDetailsType').textContent = row.dataset.jobType
    document.getElementById('jobDetailsStatus').textContent = row.dataset.jobStatus
    setLocalizedUtcDateTime(document.getElementById('jobDetailsUpdatedAt'), row.dataset.jobUpdatedAt)
    setLocalizedUtcDateTime(document.getElementById('jobDetailsCreatedAt'), row.dataset.jobCreatedAt)
    document.getElementById('jobDetailsParameters').textContent = formatJobParameters(row.dataset.jobParameters)
    document.getElementById('jobDetailsAlerts').innerHTML = ''

    const removalWarningMessage = getJobRemovalWarning(row.dataset.jobStatus)
    const removalWarning = document.getElementById('jobDetailsRemovalWarning')
    if (removalWarningMessage !== '') {
        removalWarning.classList.remove('d-none')
        removalWarning.textContent = removalWarningMessage
    } else {
        removalWarning.classList.add('d-none')
        removalWarning.textContent = ''
    }

    bootstrap.Modal.getOrCreateInstance('#jobDetailsModal').show()
}

function getJobRemovalWarning(jobStatus) {
    if (jobStatus === 'in progress') {
        return 'Removing this record will not stop the job that is currently in progress.'
    }

    if (jobStatus === 'waiting') {
        return 'Removing this job prevents it from being processed unless a worker has already claimed it.'
    }

    return ''
}

function formatJobUser(userName, userId) {
    if (userName === '-' && userId === '-') {
        return '-'
    }

    return userName + ' (ID ' + userId + ')'
}

function formatJobParameters(parameters) {
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

async function removeJob() {
    if (selectedJobId === null) {
        return
    }

    const removalWarning = getJobRemovalWarning(selectedJobStatus)
    let confirmationMessage = 'Are you sure you want to remove job ' + selectedJobId + '?'
    if (removalWarning !== '') {
        confirmationMessage += ' ' + removalWarning
    }

    const confirmed = await showConfirmationModal({
        title: 'Remove job',
        message: confirmationMessage,
        confirmLabel: 'Remove job',
        confirmClass: 'btn-danger',
    })

    if (confirmed === false) {
        return
    }

    const removeButton = document.getElementById('jobRemoveButton')
    removeButton.disabled = true

    try {
        const response = await fetchWithCsrf(APPLICATION_URL + '/job-queue/' + selectedJobId, {
            method: 'DELETE',
            signal: AbortSignal.timeout(10000)
        })

        if (!response.ok) {
            console.error('Response status: ' + response.status)
            addAlert('jobDetailsAlerts', 'Could not remove job', 'danger')
            bootstrap.Modal.getOrCreateInstance('#jobDetailsModal').show()

            return
        }
    } catch (error) {
        console.error(error)
        addAlert('jobDetailsAlerts', 'Could not remove job', 'danger')
        bootstrap.Modal.getOrCreateInstance('#jobDetailsModal').show()

        return
    } finally {
        removeButton.disabled = false
    }

    localStorage.setItem('alertMessageJobQueue', 'Removed job ' + selectedJobId)
    refreshPage()
}

function refreshPage(resetPage = false) {
    const jobsPerPage = document.getElementById('jobsPerPage').value
    const url = new URL(window.location.href)

    url.searchParams.set('perPage', jobsPerPage)
    url.searchParams.delete('jpp')
    if (resetPage) {
        url.searchParams.set('page', '1')
    }
    window.location.href = url.toString()
}

function applyJobFilters() {
    const url = new URL(window.location.href)

    setOptionalQueryParameter(url, 'user', document.getElementById('jobFilterUser').value)
    setOptionalQueryParameter(url, 'type', document.getElementById('jobFilterType').value)
    setOptionalQueryParameter(url, 'status', document.getElementById('jobFilterStatus').value)
    url.searchParams.set('page', '1')

    window.location.href = url.toString()
}

function resetJobFilters() {
    document.getElementById('jobFilterUser').value = ''
    document.getElementById('jobFilterType').value = ''
    document.getElementById('jobFilterStatus').value = ''
    applyJobFilters()
}

function setOptionalQueryParameter(url, name, value) {
    if (value === '') {
        url.searchParams.delete(name)
        return
    }

    url.searchParams.set(name, value)
}

async function removeJobs(event) {
    event.preventDefault()

    const scope = new FormData(event.currentTarget).get('removeJobsScope')
    const removalOptions = {
        processed: {
            endpoint: '/job-queue/purge-processed',
            errorMessage: 'Could not remove processed jobs',
            successMessage: 'Removed processed jobs',
        },
        all: {
            endpoint: '/job-queue/purge-all',
            errorMessage: 'Could not remove all jobs',
            successMessage: 'Removed all jobs',
        },
    }
    const removal = removalOptions[scope]
    if (removal === undefined) {
        return
    }

    const removeButton = document.getElementById('removeJobsSubmitButton')
    const errorElement = document.getElementById('removeJobsError')
    removeButton.disabled = true
    errorElement.classList.add('d-none')

    try {
        const response = await fetchWithCsrf(
            APPLICATION_URL + removal.endpoint, {
                method: 'POST',
                signal: AbortSignal.timeout(10000)
            }
        );

        if (!response.ok) {
            console.error('Response status: ' + response.status)
            errorElement.textContent = removal.errorMessage
            errorElement.classList.remove('d-none')

            return
        }
    } catch (error) {
        console.error(error)
        errorElement.textContent = removal.errorMessage
        errorElement.classList.remove('d-none')

        return
    } finally {
        removeButton.disabled = false
    }

    localStorage.setItem('alertMessageJobQueue', removal.successMessage)
    refreshPage()
}

function resetRemoveJobsForm() {
    document.getElementById('removeJobsForm').reset()
    document.getElementById('removeJobsError').classList.add('d-none')
}
