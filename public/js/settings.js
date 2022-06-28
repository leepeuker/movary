document.addEventListener('DOMContentLoaded', function () {
	fetchPlexWebhookId().then(webhookId => { updatePlexWebhookUrl(webhookId) })
})

function regeneratePlexWebhookId () {
	regeneratePlexWebhookIdRequest().then(webhookId => { updatePlexWebhookUrl(webhookId) })
}

function updatePlexWebhookUrl (webhookId) {
	if (webhookId) {
		document.getElementById('plexWebhookUrl').innerHTML = location.protocol + '//' + location.host + '/plex/' + webhookId
	} else {
		document.getElementById('plexWebhookUrl').innerHTML = '-'
	}
}

async function fetchPlexWebhookId () {
	const response = await fetch('/user/plex-webhook-id')

	if (!response.ok) {
		throw new Error(`HTTP error! status: ${response.status}`)
	}
	const data = await response.json()

	return data.id
}

async function regeneratePlexWebhookIdRequest () {
	const response = await fetch('/user/plex-webhook-id', { 'method': 'put' })

	if (!response.ok) {
		throw new Error(`HTTP error! status: ${response.status}`)
	}
	const data = await response.json()

	return data.id
}
