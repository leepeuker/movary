async function regenerateRadarrFeedId() {
    await fetch('/api/radarr/regeneratefeedid', {'method': 'put'}).then(response => function () {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        } else {
            addAlert('alertFeedUrlDiv', 'Generated new feed url', 'success');
        }
    });

}

async function deleteRadarrFeedId()
{
    await fetch('/api/radarr/deletefeedid', {'method': 'delete'}).then(response => function () {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        } else {
            addAlert('alertFeedUrlDiv', 'Deleted new feed url', 'success');
        }
    });
}