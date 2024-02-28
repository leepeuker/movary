document.addEventListener("DOMContentLoaded", function () {
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

    window.location.href = '/settings/server/jobs?jpp=' + jobsPerPage
}

async function deleteJobs(target = 'all') {
    let confirmation;
    if(target === 'all') {
        confirmation = confirm('Are you sure you want to remove all processed jobs (done + failed)?');
    } else if(target === 'processed') {
        confirmation = confirm('Are you sure you want to remove all jobs? This will not stop active job processes');
    }
    if(confirmation === true) {
        await fetch('/api/job-queue?target=' + target, {
            method: 'DELETE',
        }).then(response => {
            if(response.ok) {
                window.location.reload();
            }
        })
    }
}
