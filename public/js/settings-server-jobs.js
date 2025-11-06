document.addEventListener("DOMContentLoaded", function () {
    // localStorage here is a "hack" to keep state after page refresh, it would be better to not refresh the page and load the table via AJAX
    const alertMessageJobs = localStorage.getItem('alertMessageJobs');
    console.log(alertMessageJobs)
    if (alertMessageJobs) {
        addAlert('alertJobsDiv', alertMessageJobs, 'success');
    }
    localStorage.setItem('alertMessageJobs', '')

    let url = new URL(window.location.href)
    let params = new URLSearchParams(url.search);
    let jpp = params.get('jpp')

    if (jpp != null) {
        document.getElementById('jobsPerPage').value = jpp

        return
    }

    document.getElementById('jobsPerPage').value = 30
});

function refreshPage() {
    const jobsPerPage = document.getElementById('jobsPerPage').value

    window.location.href = APPLICATION_URL + '/settings/server/jobs?jpp=' + jobsPerPage
}

async function removeAllJobs() {
    const jobsRemoveAllModal = bootstrap.Modal.getInstance('#jobsRemoveAllModal');

    const response = await fetch(
        APPLICATION_URL + '/job-queue/purge-all', {
            method: 'POST',
            signal: AbortSignal.timeout(4000)
        }
    ).catch(function (error) {
        console.error(error)
        addAlert('alertJobsDiv', 'Could not remove all jobs', 'danger');
    });

    jobsRemoveAllModal.hide()

    if (!response.ok) {
        addAlert('alertJobsDiv', 'Could not remove all jobs', 'danger');

        return
    }

    localStorage.setItem('alertMessageJobs', 'Removed all jobs')
    refreshPage()
}

async function removeProcessedJobs() {
    const jobsRemoveProcessedModal = bootstrap.Modal.getInstance('#jobsRemoveProcessedModal');

    const response = await fetch(
        APPLICATION_URL + '/job-queue/purge-processed', {
            method: 'POST',
            signal: AbortSignal.timeout(4000)
        }
    ).catch(function (error) {
        console.error(error)
        addAlert('alertJobsDiv', 'Could not remove processed jobs', 'danger');
    });

    jobsRemoveProcessedModal.hide()

    if (!response.ok) {
        addAlert('alertJobsDiv', 'Could not remove processed jobs', 'danger');

        return
    }

    localStorage.setItem('alertMessageJobs', 'Removed processed jobs')
    refreshPage()
}
