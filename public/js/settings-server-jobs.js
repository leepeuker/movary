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

    window.location.href = APPLICATION_URL + '/settings/server/jobs?jpp=' + jobsPerPage
}
