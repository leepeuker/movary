import { Modal } from 'bootstrap';
const jobModal = new Modal('#jobModal')
const jobModalTypeInput = document.getElementById('jobModalType');

async function showJobModal(jobType) {
    jobModalTypeInput.value = jobType
    setJobModalTitle(jobModalTypeInput.value)

    loadJobModal(true)
}

async function loadJobModal(showModal) {
    setJobModalLoadingSpinner(true)
    document.getElementById('jobModalEmptyMessage').classList.add('d-none')
    document.getElementById('jobModalErrorAlert').classList.add('d-none')

    if (showModal === true) {
        jobModal.show();
    }

    let jobs = null

    try {
        jobs = await fetchJobs(jobModalTypeInput.value);
    } catch (error) {
        document.getElementById('jobModalErrorAlert').classList.remove('d-none')
    }

    setJobModalLoadingSpinner(false)

    if (jobs !== null) {
        renderJobModalTable(jobs)
    }
}

function setJobModalTitle(jobType) {
    let title

    switch (jobType) {
        case 'trakt_import_ratings':
            title = 'Rating imports';
            break;
        case 'trakt_import_history':
            title = 'History imports';
            break;
        case 'letterboxd_import_ratings':
            title = 'Rating imports';
            break;
        case 'letterboxd_import_history':
            title = 'History imports';
            break;
        case 'plex_import_watchlist':
            title = 'Watchlist imports';
            break;
        case 'jellyfin_import_history':
            title = 'History imports';
            break;
        case 'jellyfin_export_history':
            title = 'History exports';
            break;
        default:
            throw new Error('Not supported job type: ' + jobType);
    }


    document.getElementById('jobModalTitle').innerText = title;
}

async function fetchJobs(jobType) {

    const response = await fetch('/jobs?type=' + jobType)

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }

    return await response.json()
}

function setJobModalLoadingSpinner(isActive = true) {
    if (isActive === true) {
        emptyJobModalTable()
        document.getElementById('jobModalLoadingSpinner').classList.remove('d-none');
    } else {
        document.getElementById('jobModalLoadingSpinner').classList.add('d-none');
    }
}

async function renderJobModalTable(jobs) {
    const table = document.getElementById('jobModalTable');

    let tbodyRef = table.getElementsByTagName('tbody')[0];

    table.getElementsByTagName('tbody').innerHtml = 'ads'
    if (jobs.length === 0) {
        document.getElementById('jobModalEmptyMessage').classList.remove('d-none')
    } else {
        document.getElementById('jobModalEmptyMessage').classList.add('d-none')
    }

    jobs.forEach((job, index, jobs) => {
        let newRow = tbodyRef.insertRow();

        const createdAtCell = newRow.insertCell();
        createdAtCell.appendChild(document.createTextNode(job.createdAt));

        const statusCell = newRow.insertCell();
        statusCell.appendChild(document.createTextNode(job.status));

        const finishedAtCell = newRow.insertCell();
        finishedAtCell.appendChild(document.createTextNode(job.status === 'done' || job.status === 'failed' ? job.updatedAt : '-'));

        if (index === jobs.length - 1) {
            statusCell.style.borderBottom = '0'
            createdAtCell.style.borderBottom = '0'
            finishedAtCell.style.borderBottom = '0'
        }
    });
}

async function emptyJobModalTable() {
    const table = document.getElementById('jobModalTable');

    let tbodyRef = table.getElementsByTagName('tbody')[0];
    tbodyRef.innerHTML = '';
}
