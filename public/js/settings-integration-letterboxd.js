function toggleTableVisibility() {
    document.getElementById('importTable').classList.remove('d-none')
    document.getElementById('showImportTableButton').disabled = true
    console.log(document.getElementById('showImportTableButton').marginBottom)
}

async function triggerLetterboxdDiaryImport() {
    let requestFormData = new FormData();
    requestFormData.append('letterboxdDiaryCsv', document.getElementById('letterboxdDiaryCsv').files[0]);
    await fetch('/api/job-queue/schedule/letterboxd-diary-sync', {
        method: 'POST', body: requestFormData
    }).then(response => {
        if(response.ok) {
            addAlert('letterboxdImportDiaryResponse', 'History import scheduled', 'success', true, 0);
        } else {
            return response.json();
        }
    }).then(jsonresponse => {
        if(jsonresponse !== null) {
            addAlert('letterboxdImportDiaryResponse', jsonresponse['message'], 'danger', true, 0);
        }
    });
}

async function triggerLetterboxdRatingsImport() {
    let requestFormData = new FormData();
    requestFormData.append('letterboxdDiaryCsv', document.getElementById('letterboxdRatingsCsv').files[0]);
    await fetch('/api/job-queue/schedule/letterboxd-ratings-sync', {
        method: 'POST', body: requestFormData
    }).then(response => {
        if(response.ok) {
            addAlert('letterboxdImportResponse', 'Ratings import scheduled', 'success', true, 0);
        } else {
            return response.json();
        }
    }).then(jsonresponse => {
        if(jsonresponse !== null) {
            addAlert('letterboxdImportResponse', jsonresponse['message'], 'danger', true, 0);
        }
    });
}
