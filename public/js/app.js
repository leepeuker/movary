if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
        navigator.serviceWorker
            .register('/serviceWorker.js')
            .then(res => console.log('service worker registered'))
            .catch(err => console.log('service worker not registered', err))
    })
}

async function searchTmdbWithLogModalSearchInput() {
    resetLogModalSearchResults()

    await fetch('/settings/netflix/search', {
        method: 'POST', headers: {
            'Content-Type': 'application/json'
        }, body: JSON.stringify({
            'query': document.getElementById('logPlayModalSearchInput').value
        })
    }).then(response => {
        if (!response.ok) {
            console.error(response);
            alert('error')

            return false;
        }

        return response.json()
    }).then(data => {
        loadLogModalSearchResults(data)
    }).catch(function (error) {
        console.error(error);
        alert('error')
    });
}

function loadLogModalSearchResults(data) {
    let searchResultList = document.getElementById('logPlayModalSearchResultList');

    searchResultList.style.marginTop = '1rem'

    data.forEach((item, index) => {
        let listElement = document.createElement('li');
        listElement.className = 'list-group-item list-group-item-action'
        listElement.style.cursor = 'pointer'
        listElement.id = 'searchResult-' + index

        let releaseYear = '?'
        if (item.release_date != null && item.release_date.length > 4) {
            console.log(item.release_date)
            releaseYear = item.release_date.substring(0, 4)
        }
        listElement.innerHTML = item.title + ' (' + releaseYear + ')'

        listElement.dataset.tmdbId = item.id
        listElement.dataset.poster = item.poster_path
        listElement.dataset.title = item.title
        listElement.dataset.releaseYear = releaseYear

        listElement.addEventListener('click', selectTmdbItemForLogging);

        searchResultList.append(listElement);
    });
    console.log(data)
}

function resetLogModalSearchResults() {
    let searchResultList = document.getElementById('logPlayModalSearchResultList');
    searchResultList.style.marginTop = ''
    searchResultList.innerHTML = ''
}

function backToLogModalSearchResults() {
    document.getElementById('logPlayModalWatchDateDiv').classList.add('d-none')
    document.getElementById('logPlayModalSearchDiv').classList.remove('d-none')
    document.getElementById('logPlayModalSearchResultList').classList.remove('d-none')
    document.getElementById('logPlayModalFooter').classList.add('d-none')
    document.getElementById('logPlayModalTitle').innerHTML = 'Log play'
}

function selectTmdbItemForLogging(event) {
    const item = document.getElementById(event.target.id)

    document.getElementById('logPlayModalTitle').innerHTML = item.dataset.title + ' (' + item.dataset.releaseYear + ')'

    document.getElementById('logPlayModalWatchDateDiv').classList.remove('d-none')
    document.getElementById('logPlayModalSearchDiv').classList.add('d-none')
    document.getElementById('logPlayModalSearchResultList').classList.add('d-none')
    document.getElementById('logPlayModalFooter').classList.remove('d-none')

    console.log(document.getElementById('logPlayModalFooter').classList)
}

document.getElementById('logPlayModalSearchInput').addEventListener('change', updateLogPlayModalButtonState);
document.getElementById('logPlayModalSearchInput').addEventListener('input', updateLogPlayModalButtonState);

function updateLogPlayModalButtonState() {
    if (document.getElementById('logPlayModalSearchInput').value !== '') {
        document.getElementById('logPlayModalSearchButton').disabled = false;

        return
    }

    document.getElementById('logPlayModalSearchButton').disabled = true;
}

document.getElementById('logPlayModalSearchInput').addEventListener('keypress', loadLogModalSearchResultsOnEnterPress);

function loadLogModalSearchResultsOnEnterPress(event) {
    if (event.key === "Enter") {
        searchTmdbWithLogModalSearchInput()
    }
}
