document.addEventListener("DOMContentLoaded", function () {
    // localStorage here is a "hack" to keep state after page refresh, it would be better to not refresh the page and load the table via AJAX
    const alertMessageJobs = localStorage.getItem('alertMessageJobs');
    if (alertMessageJobs) {
        addAlert('alertJobsDiv', alertMessageJobs, 'success');
    }
    localStorage.setItem('alertMessageJobs', '')

    let url = new URL(window.location.href)
    let params = new URLSearchParams(url.search);
    let jpp = params.get('jpp')

    if (jpp !== null) {
        document.getElementById('jobsPerPage').value = jpp
    } else {
        document.getElementById('jobsPerPage').value = 30
    }

    registerJobRowEvents()
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

function refreshPage() {
    const jobsPerPage = document.getElementById('jobsPerPage').value

    window.location.href = APPLICATION_URL + '/settings/server/jobs?jpp=' + jobsPerPage
}

async function removeAllJobs() {
    const jobsRemoveAllModal = bootstrap.Modal.getInstance('#jobsRemoveAllModal');

    addAlert('alertJobsDiv', 'Removing all jobs...', 'info');

    jobsRemoveAllModal.hide()

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
    const jobsRemoveProcessedModal = bootstrap.Modal.getInstance('#jobsRemoveProcessedModal');

    addAlert('alertJobsDiv', 'Removing processed jobs...', 'info');

    jobsRemoveProcessedModal.hide()

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
