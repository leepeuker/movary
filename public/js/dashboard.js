const dashboardRowConcurrency = 2;
const dashboardRowQueue = [];
const deferredDashboardRows = [];
let activeDashboardRowRequests = 0;
let deferredDashboardRowsScheduled = false;

function toggleButton (element) {
	const rowContent = element.nextElementSibling;

	if (rowContent.classList.contains("inactiveItem") === true) {
		rowContent.classList.remove("inactiveItem");
		rowContent.classList.add("activeItem");
		element.classList.remove("inactiveItemButton");
		element.classList.add("activeItemButton");

		if (document.getElementById('html').dataset.bsTheme === 'dark') {
			element.classList.add("text-white");
		} else {
			element.classList.add("activeItemButtonActiveLight");
		}

		element.getElementsByClassName("bi")[0].classList.remove("bi-chevron-down");
		element.getElementsByClassName("bi")[0].classList.add("bi-chevron-up");
		prioritizeDashboardRow(element);
	} else {
		rowContent.classList.remove("activeItem");
		rowContent.classList.add("inactiveItem");
		element.classList.remove("activeItemButton", "text-white", "activeItemButtonActiveLight");
		element.classList.add("inactiveItemButton");

		element.getElementsByClassName("bi")[0].classList.remove("bi-chevron-up");
		element.getElementsByClassName("bi")[0].classList.add("bi-chevron-down");
	}
}

function initializeDashboardRows () {
	const dashboardRows = document.getElementById('dashboardRows');
	if (dashboardRows === null) {
		return;
	}

	for (const rowButton of dashboardRows.querySelectorAll('[data-dashboard-row-id]')) {
		if (rowButton.nextElementSibling.classList.contains('dashboardRowPlaceholder')) {
			rowButton.dataset.dashboardRowStatus = 'deferred';
			deferredDashboardRows.push(rowButton);
		} else {
			rowButton.dataset.dashboardRowStatus = 'loaded';
		}
	}

	processDashboardRowQueue();
}

function processDashboardRowQueue () {
	while (activeDashboardRowRequests < dashboardRowConcurrency && dashboardRowQueue.length > 0) {
		const rowButton = dashboardRowQueue.shift();
		if (rowButton.dataset.dashboardRowStatus !== 'queued') {
			continue;
		}

		activeDashboardRowRequests++;
		rowButton.dataset.dashboardRowStatus = 'loading';
		loadDashboardRow(rowButton).finally(() => {
			activeDashboardRowRequests--;
			processDashboardRowQueue();
		});
	}

	if (dashboardRowQueue.length === 0 && activeDashboardRowRequests === 0) {
		scheduleDeferredDashboardRows();
	}
}

function scheduleDeferredDashboardRows () {
	if (deferredDashboardRowsScheduled === true || deferredDashboardRows.length === 0) {
		return;
	}

	deferredDashboardRowsScheduled = true;
	const enqueueDeferredRows = () => {
		for (const rowButton of deferredDashboardRows.splice(0)) {
			if (rowButton.dataset.dashboardRowStatus === 'deferred') {
				rowButton.dataset.dashboardRowStatus = 'queued';
				dashboardRowQueue.push(rowButton);
			}
		}
		processDashboardRowQueue();
	};

	if ('requestIdleCallback' in window) {
		window.requestIdleCallback(enqueueDeferredRows);
	} else {
		window.setTimeout(enqueueDeferredRows, 0);
	}
}

function prioritizeDashboardRow (rowButton) {
	if (rowButton.dataset.dashboardRowStatus === 'deferred') {
		const deferredIndex = deferredDashboardRows.indexOf(rowButton);
		if (deferredIndex !== -1) {
			deferredDashboardRows.splice(deferredIndex, 1);
		}
		rowButton.dataset.dashboardRowStatus = 'queued';
		dashboardRowQueue.unshift(rowButton);
	} else if (rowButton.dataset.dashboardRowStatus === 'queued') {
		const queueIndex = dashboardRowQueue.indexOf(rowButton);
		if (queueIndex !== -1) {
			dashboardRowQueue.splice(queueIndex, 1);
			dashboardRowQueue.unshift(rowButton);
		}
	} else if (rowButton.dataset.dashboardRowStatus === 'error') {
		retryDashboardRow(rowButton);
	}

	processDashboardRowQueue();
}

async function loadDashboardRow (rowButton) {
	const dashboardRows = document.getElementById('dashboardRows');
	const username = dashboardRows.dataset.username;
	const rowId = rowButton.dataset.dashboardRowId;

	try {
		const response = await fetch(
			APPLICATION_URL + '/users/' + encodeURIComponent(username) + '/dashboard/rows/' + encodeURIComponent(rowId),
		);
		if (response.ok === false) {
			throw new Error('Dashboard row request failed with status ' + response.status);
		}

		const responseDocument = new DOMParser().parseFromString(await response.text(), 'text/html');
		const renderedRowButton = responseDocument.querySelector('[data-dashboard-row-id="' + rowId + '"]');
		const renderedRowContent = renderedRowButton?.nextElementSibling;
		if (renderedRowContent === undefined || renderedRowContent === null) {
			throw new Error('Dashboard row response did not contain row content');
		}

		const isExtended = rowButton.getElementsByClassName('bi-chevron-up').length > 0;
		renderedRowContent.classList.toggle('activeItem', isExtended);
		renderedRowContent.classList.toggle('inactiveItem', isExtended === false);
		rowButton.nextElementSibling.replaceWith(renderedRowContent);
		rowButton.dataset.dashboardRowStatus = 'loaded';
	} catch (error) {
		console.error(error);
		rowButton.dataset.dashboardRowStatus = 'error';
		renderDashboardRowError(rowButton);
	}
}

function renderDashboardRowError (rowButton) {
	const rowContent = rowButton.nextElementSibling;
	rowContent.replaceChildren();

	const errorMessage = document.createElement('span');
	errorMessage.classList.add('text-danger', 'me-2');
	errorMessage.textContent = 'Could not load this dashboard row.';

	const retryButton = document.createElement('button');
	retryButton.classList.add('btn', 'btn-outline-secondary', 'btn-sm');
	retryButton.type = 'button';
	retryButton.textContent = 'Retry';
	retryButton.addEventListener('click', () => retryDashboardRow(rowButton));

	rowContent.append(errorMessage, retryButton);
}

function retryDashboardRow (rowButton) {
	if (rowButton.dataset.dashboardRowStatus !== 'error') {
		return;
	}

	const rowContent = rowButton.nextElementSibling;
	rowContent.innerHTML = '<div class="d-flex justify-content-center align-items-center"><div class="spinner-border text-secondary" role="status"><span class="visually-hidden">Loading dashboard row</span></div></div>';
	rowButton.dataset.dashboardRowStatus = 'queued';
	dashboardRowQueue.unshift(rowButton);
	processDashboardRowQueue();
}

document.addEventListener('DOMContentLoaded', initializeDashboardRows);
