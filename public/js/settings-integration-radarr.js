async function regenerateRadarrFeedId() {
    removeAlert('alertFeedUrlDiv')

    regenerateRadarrFeedRequest().then(webhookUrl => {
        setRadarrFeedUrl(webhookUrl)
        addAlert('alertFeedUrlDiv', 'Generated new feed url', 'success')
        document.getElementById('deleteRadarrFeedButton').classList.remove('disabled')
    }).catch((error) => {
        console.log(error)
        addAlert('alertFeedUrlDiv', 'Could not generate feed url', 'danger')
    })
}

async function regenerateRadarrFeedRequest() {
    const response = await fetch(APPLICATION_URL + '/settings/radarr/feed', {'method': 'put'})

    if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
    }
    const data = await response.json()

    return data.url
}

async function deleteRadarrFeedId() {
    await fetch(APPLICATION_URL + '/settings/radarr/feed', {'method': 'delete'}).then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`)
        }

        setRadarrFeedUrl()
        addAlert('alertFeedUrlDiv', 'Deleted feed url', 'success');
    });
}

function setRadarrFeedUrl(webhookUrl) {
    if (webhookUrl) {
        document.getElementById('radarrFeedUrl').innerHTML = webhookUrl
        document.getElementById('deleteRadarrFeedButton').classList.remove('disabled')
    } else {
        document.getElementById('radarrFeedUrl').innerHTML = '-'
        document.getElementById('deleteRadarrFeedButton').classList.add('disabled')
    }
}
