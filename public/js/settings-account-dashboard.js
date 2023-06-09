function moveItemUp(clickedElement) {
    let row = clickedElement.closest('.list-group-item');
    let firstChild = row.parentElement.firstElementChild;
    if(row === firstChild) {
        return;
    }
    let previousElement = row.previousElementSibling;
    previousElement.before(row);
}

function moveItemDown(clickedElement) {
    let row = clickedElement.closest('.list-group-item');
    let firstChild = row.parentElement.lastElementChild;
    if(row === firstChild) {
        return;
    }
    let nextElement = row.nextElementSibling;
    nextElement.after(row);
}

function toggleRowExtension(element) {
    if(element.classList.contains('bi-eye')) {
        element.classList.remove('bi-eye');
        element.classList.add('bi-eye-slash');
        element.closest('.dashboardRowItem').style.opacity = 0.5;
    } else {
        element.classList.remove('bi-eye-slash');
        element.classList.add('bi-eye');
        element.closest('.dashboardRowItem').style.opacity = 1;
    }
}

function getRowOrder() {
    let rows = document.getElementsByClassName('dashboardRowItem');
    let rowOrder = [];
    for(let i = 0; i < rows.length; i++) {
        rowOrder.push(rows[i].closest('.dashboardRowItem').dataset.rowid);
    }
    return rowOrder;
}

function getExtendedRows() {
    let extendedRows = document.getElementsByClassName('bi-eye');
    let rowList = [];
    for(let i = 0; i < extendedRows.length; i++) {
        rowList.push(extendedRows[i].closest('.dashboardRowItem').dataset.rowid);
    }
    return rowList;
}

async function updateDashboardRows() {
    let rowOrder = getRowOrder();
    let extendedRows = getExtendedRows();
    await fetch('/settings/account/update-dashboard-rows', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            'rowOrder': rowOrder,
            'extendedRows': extendedRows
        })
    }).then(response => {
        if (!response.ok) {
            console.error(error);
            return false;
        } else {
            addAlert('accountDashboardSettingsLog', 'Dashboard rows succesfully updated!', 'success');
        };
    }).catch(function (error) {
        addAlert('accountDashboardSettingsLog', 'Error: Please check your browser console log (F12 -> Console) and the Movary application logs and report the error via <a href="https://github.com/leepeuker/movary" target="_blank">Github</a>.', 'danger');
        console.error(error);
    });
}