function moveItemUp(clickedElement) {
    let row = clickedElement.closest('.list-group-item');
    let firstChild = row.parentElement.firstElementChild;
    if (row === firstChild) {
        return;
    }
    let previousElement = row.previousElementSibling;
    previousElement.before(row);
}

function moveItemDown(clickedElement) {
    let row = clickedElement.closest('.list-group-item');
    let firstChild = row.parentElement.lastElementChild;
    if (row === firstChild) {
        return;
    }
    let nextElement = row.nextElementSibling;
    nextElement.after(row);
}

function toggleRowVisibility(element) {
    if (element.classList.contains('bi-eye')) {
        element.classList.remove('bi-eye');
        element.classList.add('bi-eye-slash');
        element.closest('.dashboardRowItem').style.opacity = 0.5;
    } else {
        element.classList.remove('bi-eye-slash');
        element.classList.add('bi-eye');
        element.closest('.dashboardRowItem').style.opacity = 1;
    }
}

function toggleRowExtension(element) {
    if (element.classList.contains('bi-chevron-expand')) {
        element.classList.remove('bi-chevron-expand');
        element.classList.add('bi-chevron-contract');
    } else {
        element.classList.remove('bi-chevron-contract');
        element.classList.add('bi-chevron-expand');
    }
}

function getRowOrder() {
    let rows = document.getElementsByClassName('dashboardRowItem');

    let rowOrder = [];
    for (let i = 0; i < rows.length; i++) {
        rowOrder.push(rows[i].closest('.dashboardRowItem').dataset.rowid);
    }

    return rowOrder;
}

function getVisibleRows() {
    let rows = document.getElementsByClassName('bi-eye');

    let rowList = [];
    for (let i = 0; i < rows.length; i++) {
        rowList.push(rows[i].closest('.dashboardRowItem').dataset.rowid);
    }

    return rowList;
}

function getExtendedRows() {
    let extendedRows = document.getElementsByClassName('bi-chevron-expand');

    let rowList = [];
    for (let i = 0; i < extendedRows.length; i++) {
        rowList.push(extendedRows[i].closest('.dashboardRowItem').dataset.rowid);
    }

    return rowList;
}

async function updateDashboardRows() {
    let orderRows = getRowOrder();
    let visibleRows = getVisibleRows();
    let extendedRows = getExtendedRows();
    await fetch('/settings/account/update-dashboard-rows', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            'orderRows': orderRows,
            'visibleRows': visibleRows,
            'extendedRows': extendedRows
        })
    }).then(response => {
        if (!response.ok) {
            console.error(response);
            addAlert('accountDashboardSettingsLog', 'Something went wrong. Check the logs and report the error via <a href="https://github.com/leepeuker/movary/issues" target="_blank">Github</a>.', 'danger');

            return false;
        } else {
            addAlert('accountDashboardSettingsLog', 'Dashboard rows successfully updated.', 'success');
        }
    }).catch(function (error) {
        addAlert('accountDashboardSettingsLog', 'Something went wrong. Check the logs and report the error via <a href="https://github.com/leepeuker/movary/issues" target="_blank">Github</a>.', 'danger');
        console.error(error);
    });
}

async function resetDashboardRows() {
    await fetch('/settings/account/reset-dashboard-rows', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
    }).then(response => {
        if (!response.ok) {
            console.error(response);
            addAlert('accountDashboardSettingsLog', 'Something went wrong. Check the logs and report the error via <a href="https://github.com/leepeuker/movary/issues" target="_blank">Github</a>.', 'danger');

            return false;
        } else {
            location.reload()
        }
    }).catch(function (error) {
        addAlert('accountDashboardSettingsLog', 'Something went wrong. Check the logs and report the error via <a href="https://github.com/leepeuker/movary/issues" target="_blank">Github</a>.', 'danger');
        console.error(error);
    });
}
