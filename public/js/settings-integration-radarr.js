async function regenerateRadarrFeedId() {
    await fetch('/api/radarr/regeneratefeedid', {'method': 'put'}).then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        } else {
            return response.json();
        }
    }).then(radarrFeedUrl => {
        document.getElementById('radarrFeedUrl').innerText = radarrFeedUrl.url;
        addAlert('alertFeedUrlDiv', 'Generated new feed url', 'success');
    });

}

async function deleteRadarrFeedId()
{
    await fetch('/api/radarr/deletefeedid', {'method': 'delete'}).then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        } else {
            addAlert('alertFeedUrlDiv', 'Deleted new feed url', 'success');
        }
    });
}