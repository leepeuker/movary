document.addEventListener("DOMContentLoaded", function () {
    // localStorage here is a "hack" to keep state after page refresh, it would be better to not refresh the page and load the table via AJAX
    const alertMessageJobs = localStorage.getItem('alertMessageJobs');
    if (alertMessageJobs) {
        addAlert('alertJobsDiv', alertMessageJobs, 'success');
    }
    localStorage.setItem('alertMessageJobs', '')

    registerJobRowEvents()
    document.getElementById('jobsPerPage').addEventListener('change', () => refreshPage(true))
    document.getElementById('createJobForm').addEventListener('submit', createJob)
    document.getElementById('createJobType').addEventListener('change', updateCreateJobFields)
    updateCreateJobFields()
});

let selectedJobId = null
let selectedJobStatus = null

const creatableJobTypes = {
    tmdb_movie_sync: {
        endpoint: 'tmdb-movie-sync',
        filterType: 'tmdb_movie_sync',
        prepareSubmission: prepareTmdbSyncSubmission,
        unboundedResourceName: 'movie',
    },
    tmdb_person_sync: {
        endpoint: 'tmdb-person-sync',
        filterType: 'tmdb_person_sync',
        prepareSubmission: prepareTmdbSyncSubmission,
        unboundedResourceName: 'person',
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
            errorAlert.textContent = responseBody.error ?? 'Could not create job.'
            errorAlert.classList.remove('d-none')
            return
        }

        bootstrap.Modal.getOrCreateInstance('#createJobModal').hide()
        localStorage.setItem('alertMessageJobs', 'Created job ' + responseBody.jobId)

        const url = new URL(window.location.href)
        url.search = ''
        url.searchParams.set('type', jobConfiguration.filterType)
        url.searchParams.set('user', responseBody.userId)
        window.location.href = url.toString()
    } catch (error) {
        console.error(error)
        errorAlert.textContent = 'Could not create job.'
        errorAlert.classList.remove('d-none')
    } finally {
        createButton.disabled = false
    }
}

async function prepareTmdbSyncSubmission(formData, jobConfiguration) {
    if (formData.get('threshold') !== '' || formData.get('ids').trim() !== '') {
        return true
    }

    const confirmed = await showConfirmationModal({
        title: 'Create unbounded TMDB sync job',
        message: 'This job will attempt to synchronize every eligible ' + jobConfiguration.unboundedResourceName + '. Continue?',
        confirmLabel: 'Create job',
        confirmClass: 'btn-warning',
        restoreSourceModalOnConfirm: true,
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
    document.getElementById('jobDetailsUpdatedAt').textContent = row.dataset.jobUpdatedAt
    document.getElementById('jobDetailsCreatedAt').textContent = row.dataset.jobCreatedAt
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

    localStorage.setItem('alertMessageJobs', 'Removed job ' + selectedJobId)
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

async function removeAllJobs() {
    const confirmed = await showConfirmationModal({
        title: 'Remove all jobs',
        message: 'Are you sure you want to remove all jobs? This will not stop jobs currently in progress.',
        confirmLabel: 'Remove all jobs',
        confirmClass: 'btn-danger',
    })

    if (confirmed === false) {
        return
    }

    addAlert('alertJobsDiv', 'Removing all jobs...', 'info');

    try {
        const response = await fetchWithCsrf(
            APPLICATION_URL + '/job-queue/purge-all', {
                method: 'POST',
                signal: AbortSignal.timeout(10000)
            }
        );

        if (!response.ok) {
            console.error('Response status: ' + response.status)
            addAlert('alertJobsDiv', 'Could not remove all jobs', 'danger');

            return
        }
    } catch (error) {
        console.error(error)
        addAlert('alertJobsDiv', 'Could not remove all jobs', 'danger');

        return
    }

    localStorage.setItem('alertMessageJobs', 'Removed all jobs')
    refreshPage()
}

async function removeProcessedJobs() {
    const confirmed = await showConfirmationModal({
        title: 'Remove processed jobs',
        message: 'Are you sure you want to remove all processed jobs? This includes all jobs with status "done" and "failed".',
        confirmLabel: 'Remove processed jobs',
        confirmClass: 'btn-warning',
    })

    if (confirmed === false) {
        return
    }

    addAlert('alertJobsDiv', 'Removing processed jobs...', 'info');

    try {
        const response = await fetchWithCsrf(
            APPLICATION_URL + '/job-queue/purge-processed', {
                method: 'POST',
                signal: AbortSignal.timeout(10000)
            }
        );

        if (!response.ok) {
            console.error('Response status: ' + response.status)
            addAlert('alertJobsDiv', 'Could not remove processed jobs', 'danger');

            return
        }
    } catch (error) {
        console.error(error)
        addAlert('alertJobsDiv', 'Could not remove processed jobs', 'danger');

        return
    }

    localStorage.setItem('alertMessageJobs', 'Removed processed jobs')
    refreshPage()
}
