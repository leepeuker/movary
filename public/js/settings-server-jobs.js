document.addEventListener("DOMContentLoaded", function () {
    // localStorage here is a "hack" to keep state after page refresh, it would be better to not refresh the page and load the table via AJAX
    const alertMessageJobs = localStorage.getItem('alertMessageJobs');
    if (alertMessageJobs) {
        addAlert('alertJobsDiv', alertMessageJobs, 'success');
    }
    localStorage.setItem('alertMessageJobs', '')

    registerJobRowEvents()
    document.getElementById('jobsPerPage').addEventListener('change', () => refreshPage(true))
    document.getElementById('jobRemoveConfirmButton').addEventListener('click', removeJob)
});

let selectedJobId = null

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

    document.getElementById('jobDetailsId').textContent = row.dataset.jobId
    document.getElementById('jobDetailsUser').textContent = formatJobUser(row.dataset.jobUserName, row.dataset.jobUserId)
    document.getElementById('jobDetailsType').textContent = row.dataset.jobType
    document.getElementById('jobDetailsStatus').textContent = row.dataset.jobStatus
    document.getElementById('jobDetailsUpdatedAt').textContent = row.dataset.jobUpdatedAt
    document.getElementById('jobDetailsCreatedAt').textContent = row.dataset.jobCreatedAt
    document.getElementById('jobDetailsParameters').textContent = formatJobParameters(row.dataset.jobParameters)

    const removalWarning = document.getElementById('jobDetailsRemovalWarning')
    if (row.dataset.jobStatus === 'in progress') {
        removalWarning.classList.remove('d-none')
        removalWarning.textContent = 'Removing this record will not stop the job that is currently in progress.'
    } else if (row.dataset.jobStatus === 'waiting') {
        removalWarning.classList.remove('d-none')
        removalWarning.textContent = 'Removing this job prevents it from being processed unless a worker has already claimed it.'
    } else {
        removalWarning.classList.add('d-none')
        removalWarning.textContent = ''
    }

    prepareJobRemoveConfirmation(row.dataset.jobId, row.dataset.jobStatus)

    bootstrap.Modal.getOrCreateInstance('#jobDetailsModal').show()
}

function prepareJobRemoveConfirmation(jobId, jobStatus) {
    document.getElementById('jobRemoveConfirmMessage').textContent = 'Are you sure you want to remove job ' + jobId + '?'
    document.getElementById('jobRemoveConfirmAlerts').innerHTML = ''

    const warning = document.getElementById('jobRemoveConfirmWarning')
    if (jobStatus === 'in progress') {
        warning.classList.remove('d-none')
        warning.textContent = 'This will not stop the job that is currently in progress.'
    } else if (jobStatus === 'waiting') {
        warning.classList.remove('d-none')
        warning.textContent = 'This prevents the job from being processed unless a worker has already claimed it.'
    } else {
        warning.classList.add('d-none')
        warning.textContent = ''
    }
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

    const removeButton = document.getElementById('jobRemoveConfirmButton')
    removeButton.disabled = true

    try {
        const response = await fetch(APPLICATION_URL + '/job-queue/' + selectedJobId, {
            method: 'DELETE',
            signal: AbortSignal.timeout(10000)
        })

        if (!response.ok) {
            console.error('Response status: ' + response.status)
            addAlert('jobRemoveConfirmAlerts', 'Could not remove job', 'danger')

            return
        }
    } catch (error) {
        console.error(error)
        addAlert('jobRemoveConfirmAlerts', 'Could not remove job', 'danger')

        return
    } finally {
        removeButton.disabled = false
    }

    localStorage.setItem('alertMessageJobs', 'Removed job ' + selectedJobId)
    bootstrap.Modal.getInstance('#jobRemoveConfirmModal').hide()
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
        const response = await fetch(
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
        confirmClass: 'btn-danger',
    })

    if (confirmed === false) {
        return
    }

    addAlert('alertJobsDiv', 'Removing processed jobs...', 'info');

    try {
        const response = await fetch(
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
