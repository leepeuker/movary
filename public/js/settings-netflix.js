async function importNetflixHistory() {
    let importData = [];
    const rows = document.getElementsByClassName('netflixrow');

    for (let i = 0; i < rows.length; i++) {
        const currentRow = rows[i];

        if (currentRow.dataset.tmdbid !== 'undefined') {
            importData.push({
                'watchDate': currentRow.querySelector('td.date-column').innerText,
                'tmdbId': currentRow.dataset.tmdbid,
                'dateFormat': document.getElementById('dateFormatPhp').value,
                'personalRating': getRatingFromStars(currentRow)
            });
        }
    }

    const importDataAsJson = JSON.stringify(importData);

    createPageNavigation(1, 1, true);
    disable(document.getElementById('importNetflixButton'));

    await createSpinner(document.getElementById('netflixTableBody'), 'netflix');
    await fetch('/settings/netflix/import', {
        method: 'POST',
        headers: {
            'Content-type': 'application/json'
        },
        body: importDataAsJson
    }).then(response => {
        if (!response.ok) {
            processError(response.status);

            return
        }
        let td = document.createElement('td');
        let tr = document.createElement('tr');

        td.colSpan = 4;
        td.innerHTML = '<p class="text-success">The data has succesfully been imported!</p>';
        tr.append(td);
        document.getElementById('netflixTableBody').append(tr);
        document.getElementById('netflixTableBody').getElementsByTagName('tr')[0].remove();
    }).catch(function (error) {
        console.error(error);
        processError(500);
    });
}

async function uploadNetflixHistory() {

    let requestFormData = new FormData();
    requestFormData.append('netflixActivityCsv', document.getElementById('netflixCsvInput').files[0]);

    document.getElementById('netflixTableBody').getElementsByTagName('tr')[0].remove();
    disable(document.getElementById('importNetflixButton'));
    disable(document.getElementById('searchInput'));

    await createSpinner(document.getElementById('netflixTableBody'), 'netflix');
    await fetch('/settings/netflix', {
        method: 'POST',
        body: requestFormData
    }).then(response => {
        document.getElementById('netflixTableBody').querySelector('div.spinner-border').parentElement.remove();
        if (!response.ok) {
            processError(response.status);
            return false;
        } else {
            return response.json();
        }
    }).then(data => {
        if (data !== false) {
            processNetflixData(data)
        }
    }).catch(function (error) {
        console.error(error);
        processError(500);
    });
}

async function searchTMDB(event) {
    event.preventDefault();
    var query = document.getElementById('searchtmdb').value;
    await createSpinner(document.getElementById('tmdbsearchresults'), 'tmdb');
    await fetch('/settings/netflix/search', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            'query': query
        })
    }).then(response => {
        document.getElementById('tmdbsearchresults').querySelector('div.spinner-border').remove();
        if (!response.ok) {
            processError(response.status);
            return false;
        } else {
            return response.json();
        }
    }).then(data => {
        processTMDBData(data);
    })
        .catch(function (error) {
            console.error(error);
            processError(500);
        });
}

async function createSpinner(parent, target) {
    parent.innerHTML = '';
    let div = document.createElement('div');
    let span = document.createElement('span');
    div.className = 'spinner-border';
    span.className = 'visually-hidden';
    span.innerText = 'Loading...';
    div.append(span);
    if (target === 'netflix') {
        let row = document.createElement('tr');
        let cell = document.createElement('td');
        cell.colSpan = 4;
        cell.innerText = "";
        cell.append(div);
        row.append(cell);
        parent.append(row);
    } else if (target === 'tmdb') {
        parent.append(div);
    }
}

function updateTable() {
    let amount = document.getElementById('amountToShowInput').value;
    let rows = document.getElementById('netflixTableBody').children;
    let filter = document.getElementById('selectFilterInput').value;
    if (filter === 'notfound') {
        for (let i = 0; i < rows.length; i++) {
            if (rows[i].dataset.tmdbid !== 'undefined') {
                rows[i].classList.add('d-none');
            } else {
                rows[i].classList.remove('d-none');
            }
        }
        document.querySelector('label[for="amountToShowInput"]').classList.add('d-none');
        document.getElementById('amountToShowInput').classList.add('d-none');
        createPageNavigation(amount, amount);
        changePage('all');
    } else {
        document.querySelector('label[for="amountToShowInput"]').classList.remove('d-none');
        document.getElementById('amountToShowInput').classList.remove('d-none');
        if (amount === 'all') {
            createPageNavigation(rows.length, rows.length);
        } else {
            createPageNavigation(amount, rows.length);
        }
        changePage(1);
    }
}

function changePage(pageNumber = null) {
    var direction = "";
    if (!isNaN(parseInt(pageNumber))) {
        // The function was manually triggered by JS code
        direction = pageNumber;
    } else if (this.nextElementSibling == null) {
        // User clicked on the next button
        direction = 'next';
    } else if (this.previousElementSibling == null) {
        // User clicked on the previous button
        direction = 'previous'
    } else {
        direction = this.innerText;
    }
    let ul = document.getElementsByClassName('pagination')[0];
    let amount = document.getElementById('amountToShowInput').value;
    let rows = document.getElementById('netflixTableBody').children;
    let notfoundrows = document.querySelectorAll("tr[data-tmdbid='undefined']");
    var targetpage = -1;
    if (direction === 'previous') {
        if (!ul.children[1].classList.contains('active')) {
            document.getElementsByClassName('page-item active')[0].previousElementSibling.classList.add('active');
            document.getElementsByClassName('page-item active')[1].classList.remove('active');
            targetpage = parseInt(document.getElementsByClassName('page-item active')[0].innerText);
        }
    } else if (direction === 'next') {
        if (!ul.children[ul.childElementCount - 2].classList.contains('active')) {
            document.getElementsByClassName('page-item active')[0].nextElementSibling.classList.add('active');
            document.getElementsByClassName('page-item active')[0].classList.remove('active');
            targetpage = parseInt(document.getElementsByClassName('page-item active')[0].innerText);
        }
    } else if (!isNaN(parseInt(direction))) {
        document.getElementsByClassName('page-item active')[0].classList.remove('active');
        document.querySelectorAll('li.page-item:not(.active)').forEach((el) => {
            if (el.innerText === direction) {
                el.classList.add('active');
            }
        })
        targetpage = parseInt(direction);
    }

    if (targetpage !== -1) {
        var filter = document.getElementById('selectFilterInput').value;
        let tbody = document.getElementById('netflixTableBody');
        tbody.querySelectorAll("tr:not(.d-none)").forEach((el) => {
            el.classList.add('d-none');
        });
        if (amount === 'all') {
            for (let i = 0; i < rows.length; i++) {
                if (filter === 'notfound' && rows[i].dataset.tmdbid === 'undefined') {
                    rows[i].classList.remove('d-none');
                } else if (filter === 'all') {
                    rows[i].classList.remove('d-none');
                }
            }
        } else {
            for (let i = amount * targetpage - amount + 1; i < amount * targetpage + 1; i++) {
                if (rows.length > i && filter != 'notfound') {
                    rows[i].classList.remove('d-none');
                } else if (notfoundrows.length > i && filter == 'notfound') {
                    notfoundrows[i].classList.remove('d-none');
                }
            }
        }
    }
    window.scrollTo(0, 0);
}

function createPageNavigation(amount, items, reset = null) {
    let ul = document.getElementsByClassName('pagination')[0];
    let lastChild = ul.children[ul.childElementCount - 1];
    let firstChild = ul.firstElementChild;
    // remove all children except the first ('previous' button) and the last ('next' button)
    while (ul.childElementCount > 2) {
        lastChild.previousElementSibling.remove();
    }

    if (reset != null) {
        let center = document.createElement('li');
        let a = document.createElement('a');
        a.innerText = '#';
        a.className = 'page-link';
        center.className = 'page-item';
        center.append(a);
        lastChild.before(center);
        disable(center);
        disable(lastChild);
        disable(firstChild);
    } else {
        const buttonsNumber = Math.ceil(items / amount);

        // Create nav buttons
        for (let i = 0; i < buttonsNumber; i++) {
            let li = document.createElement('li');
            let link = document.createElement('a');
            li.style.cursor = 'pointer';
            li.className = i === 0 ? 'page-item active' : 'page-item';
            link.className = 'page-link';
            link.innerText = i + 1;
            li.append(link);
            li.addEventListener("click", changePage);
            lastChild.before(li);
        }

        if (ul.childElementCount === 3) {
            disable(lastChild);
            disable(firstChild);
        } else {
            enable(lastChild, 'pointer');
            enable(firstChild, 'pointer');
        }
    }

    lastChild.addEventListener("click", changePage);
    firstChild.addEventListener("click", changePage);
}

function processTMDBData(data) {
    let parent = document.getElementById('tmdbsearchresults');
    data.forEach((item) => {
        let media_div = document.createElement('div');
        let thumb_div = document.createElement('div');
        let descr_div = document.createElement('div');
        let radio_div = document.createElement('div');
        let heading = document.createElement('h3');
        let link = document.createElement('a');
        let img = document.createElement('img');
        let paragraph = document.createElement('p');
        let release_date = document.createElement('p');
        let radio = document.createElement('input');

        media_div.className = 'd-flex flex-row mb-3 tmdbrow';
        thumb_div.className = 'flex-shrink-0 align-self-start';
        descr_div.className = 'flex-grow-1 ms-3 align-self-center';
        radio_div.className = 'input-group-text';

        media_div.setAttribute('data-tmdbid', item['id'])

        img.src = item['poster_path'] != null ? 'https://image.tmdb.org/t/p/w92' + item['poster_path'] : '/images/placeholder-image.png';
        img.className = 'img-fluid';
        img.alt = 'Cover of ' + item['title'];
        img.style.width = '92px';

        link.innerText = item['title'];
        link.href = 'https://www.themoviedb.org/movie/' + item['id'];
        link.target = '_blank';
        heading.append(link);
        paragraph.innerText = item['overview'];
        release_date.innerText = "Release date: " + item['release_date'];

        radio_div.style.height = 'fit-content';
        radio.className = 'form-check-input tmdbradio';
        radio.type = 'radio';

        descr_div.append(heading, paragraph, release_date);
        thumb_div.append(img);
        radio_div.append(radio);
        media_div.append(thumb_div, descr_div, radio_div);
        media_div.addEventListener('click', selectTMDBItem);
        parent.append(media_div);
    });
}

function processNetflixData(netflixActivityItems) {
    const amount = document.getElementById('amountToShowInput').value;

    netflixActivityItems.forEach((netflixActivityItem, index) => {
        let row = document.createElement('tr');
        let indexcell = document.createElement('td');
        let netflix_name = document.createElement('td');

        let tmdb = document.createElement('td');
        let tmdb_div = document.createElement('div');
        let tmdb_cover_div = document.createElement('div');
        let tmdb_description_div = document.createElement('div');
        let tmdb_rating_div = document.createElement('div');
        let tmdb_rating_span = document.createElement('span');
        let tmdb_cover = document.createElement('img');
        let tmdb_cover_br = document.createElement('br');
        let tmdb_link = document.createElement('a');
        let description = document.createElement('b');
        let editbtn = document.createElement('button');
        let date = document.createElement('td');
        let paragraph = document.createElement('p');
        let release_date = document.createElement('p');

        netflix_name.innerText = netflixActivityItem.netflixMovieName;
        netflix_name.className = 'netflixcolumn';
        indexcell.innerText = index + 1;

        row.className = 'netflixrow';
        if (document.getElementById('selectFilterInput').value === 'notfound') {
            if (netflixActivityItem.tmdbMatch !== null) {
                row.classList.add('d-none');
            } else {
                row.classList.remove('d-none');
            }
        } else if (index + 1 > amount) {
            row.classList.add('d-none');
        }

        row.id = index + 1;

        date.className = 'date-column';
        release_date.className = 'mb-auto pb-3';

        editbtn.className = 'btn btn-success align-self-start';
        editbtn.innerHTML = '<i class="bi bi-pencil-square"></i>';
        editbtn.setAttribute('data-bs-toggle', 'modal');
        editbtn.setAttribute('data-bs-target', '#tmdbSearchModal');

        tmdb.className = 'w-50 tmdb-column';
        tmdb_div.className = "row";
        tmdb_cover_div.className = 'col-md-3 justify-content-center';
        tmdb_description_div.className = 'col-md-9 text-start d-flex flex-column';
        tmdb_cover.style.width = '92px';

        tmdb_rating_div.className = 'fw-light mb-3 ratingStars';
        tmdb_rating_div.style.color = 'rgb(255, 193, 7)';
        tmdb_rating_div.style.fontSize = '1.5rem';
        tmdb_rating_div.style.marginTop = '0.5rem';
        tmdb_rating_span.style.cursor = 'pointer';
        tmdb_rating_span.className = 'ratingSpan';

        tmdb_link.target = '__blank';
        tmdb_cover.className = 'img-fluid';

        for (let i = 1; i <= 10; i++) {
            let tmdb_rating_icon = document.createElement('i');
            tmdb_rating_icon.className = 'bi bi-star ratingIcon';
            tmdb_rating_icon.dataset.rating = i;
            tmdb_rating_icon.addEventListener('click', setRatingStars);
            tmdb_rating_span.appendChild(tmdb_rating_icon);
        }


        if (netflixActivityItem.tmdbMatch !== null) {
            tmdb_cover.alt = 'Cover of ' + netflixActivityItem.tmdbMatch.title;
            row.setAttribute('data-tmdbid', netflixActivityItem.tmdbMatch.id);
        } else {
            tmdb_cover.alt = 'Cover of missing item';
            row.setAttribute('data-tmdbid', 'undefined');
            row.classList.add('bg-warning')
        }

        if (netflixActivityItem.tmdbMatch === null || netflixActivityItem.tmdbMatch.posterPath === null) {
            tmdb_cover.src = '/images/placeholder-image.png';
            tmdb_link.innerText = 'Image not found on TMDB';
        } else {
            tmdb_cover.src = 'https://image.tmdb.org/t/p/w92' + netflixActivityItem.tmdbMatch.poster_path;
            tmdb_link.href = 'https://www.themoviedb.org/movie/' + netflixActivityItem.tmdbMatch.id;
            tmdb_link.innerText = netflixActivityItem.tmdbMatch.title;
        }

        if (netflixActivityItem.tmdbMatch === null || netflixActivityItem.tmdbMatch.overview === null) {
            description.innerText = 'Description not found';
        } else {
            description.innerText = 'Description: ';
            paragraph.innerText = netflixActivityItem.tmdbMatch.overview;
            release_date.innerText = 'Release date: ' + netflixActivityItem.tmdbMatch.release_date;
        }
        tmdb_rating_div.append(tmdb_rating_span);
        tmdb_description_div.append(description, paragraph, release_date, tmdb_rating_div);
        tmdb_description_div.append(editbtn);

        date.innerText = formatDate(netflixActivityItem.netflixWatchDate);

        tmdb_cover_div.append(tmdb_cover, tmdb_cover_br, tmdb_link);
        tmdb_div.append(tmdb_cover_div, tmdb_description_div);
        tmdb.append(tmdb_div);
        row.append(indexcell, date, netflix_name, tmdb);

        document.getElementById('netflixTableBody').append(row);
    });

    if (document.getElementById('selectFilterInput').value === 'notfound') {
        createPageNavigation(amount, amount);
    } else {
        createPageNavigation(amount, netflixActivityItems.length);
    }

    enable(document.getElementById('importNetflixButton'), 'pointer');
    enable(document.getElementById('searchInput'));
    enable(document.getElementById('selectFilterInput'));
    enable(document.getElementById('amountToShowInput'));
}

function formatDate(date) {
    const today = new Date(date)
    const dd = String(today.getDate()).padStart(2, '0')
    const mm = String(today.getMonth() + 1).padStart(2, '0') //January is 0!
    const yyyy = today.getFullYear()

    if (document.getElementById('dateFormatJavascript').value === 'dd.mm.yyyy') {
        return dd + '.' + mm + '.' + yyyy
    }
    if (document.getElementById('dateFormatJavascript').value === 'dd.mm.yy') {
        return dd + '.' + mm + '.' + yyyy.toString().slice(-2)
    }
    if (document.getElementById('dateFormatJavascript').value === 'yy-mm-dd') {
        return yyyy.toString().slice(-2) + '-' + mm + '-' + dd
    }

    return yyyy + '-' + mm + '-' + dd
}

function processError(errorcode) {
    document.getElementById('netflixTableBody').innerHTML = '';
    let errorrow = document.createElement('tr');
    let errorcell = document.createElement('td');
    errorcell.colSpan = 4;

    if (errorcode == 400) {
        errorcell.innerText = 'Error 400. Input file could not be processed. Please try again.';
    } else if (errorcode == 415) {
        errorcell.innerText = 'Error 415. Input file is the wrong type. Upload a CSV file from Netflix instead.';
    } else if (errorcode == 500) {
        errorcell.innerText = 'Error 500. Something has gone wrong. Please check your browser console log (F12 -> Console) or your Movary application logs and submit it to the Github issues, so this can be solved.';
    }

    errorrow.append(errorcell);
    document.getElementById('netflixTableBody').append(errorrow);
}

function selectTMDBItem() {
    let radio = document.querySelector("input.tmdbradio:checked");
    if (radio != null) {
        radio.checked = false;
    }
    this.getElementsByClassName('tmdbradio')[0].checked = true;
}

function saveTMDBItem() {
    let checkedrow = document.querySelector('input.tmdbradio:checked').closest('.tmdbrow');
    let rowid = document.getElementById('tmdbSearchModal').dataset.rowid;
    let targetrow = document.getElementById(rowid);
    targetrow.getElementsByClassName('img-fluid')[0].src = checkedrow.getElementsByClassName('img-fluid')[0].src;
    targetrow.getElementsByClassName('img-fluid')[0].alt = checkedrow.getElementsByClassName('img-fluid')[0].alt;
    targetrow.getElementsByTagName('a')[0].href = checkedrow.getElementsByTagName('a')[0].href;
    targetrow.getElementsByTagName('a')[0].innerText = checkedrow.getElementsByTagName('a')[0].innerText;
    targetrow.getElementsByTagName('p')[0].innerText = checkedrow.getElementsByTagName('p')[0].innerText;
    targetrow.getElementsByTagName('p')[1].innerText = checkedrow.getElementsByTagName('p')[1].innerText;
    targetrow.setAttribute('data-tmdbid', checkedrow.dataset.tmdbid);
    targetrow.classList.remove('bg-warning')
    if (targetrow.getElementsByClassName('bi-star-fill').length > 0) {
        targetrow.getElementsByClassName('bi-star-fill')[targetrow.getElementsByClassName('bi-star-fill').length - 1].click();
    }
    const modal = bootstrap.Modal.getInstance(document.getElementById('tmdbSearchModal'));
    modal.hide();
}

function searchTable() {
    let query = document.getElementById('searchInput').value.toUpperCase();
    let rows = document.getElementsByClassName('netflixrow');

    if (query.length > 2) {
        for (let i = 0; i < rows.length; i++) {
            if (rows[i].getElementsByClassName('netflixcolumn')[0].innerText.toUpperCase().indexOf(query) > -1) {
                rows[i].classList.remove('d-none');
            } else {
                rows[i].classList.add('d-none');
            }
        }
        createPageNavigation(1, 1);
        disable(document.getElementById('selectFilterInput'));
        disable(document.getElementById('amountToShowInput'));
    } else {
        changePage(1);
        createPageNavigation(document.getElementById('amountToShowInput').value, rows.length);
        enable(document.getElementById('selectFilterInput'));
        enable(document.getElementById('amountToShowInput'));
    }
}

function scrollDown() {
    window.scrollTo(0, document.body.scrollHeight);
}

function setRatingStars() {
    let newRating = this.dataset.rating;
    let row = this.closest('.netflixrow');
    if (getRatingFromStars(row) == newRating) {
        newRating = null;
    }

    for (let ratingStarNumber = 1; ratingStarNumber <= 10; ratingStarNumber++) {
        row.querySelector('i[data-rating="' + ratingStarNumber + '"]').classList.remove('bi-star-fill');
        row.querySelector('i[data-rating="' + ratingStarNumber + '"]').classList.remove('bi-star');

        if (ratingStarNumber <= newRating) {
            row.querySelector('i[data-rating="' + ratingStarNumber + '"]').classList.add('bi-star-fill');
        } else {
            row.querySelector('i[data-rating="' + ratingStarNumber + '"]').classList.add('bi-star');
        }
    }
}

function getRatingFromStars(row) {
    let rating = 0;

    for (let ratingStarNumber = 1; ratingStarNumber <= 10; ratingStarNumber++) {
        if (row.querySelector('i[data-rating="' + ratingStarNumber + '"]').classList.contains('bi-star') === true) {
            break;
        }

        rating = ratingStarNumber;
    }

    return rating;
}

function enable(el, cursor = '') {
    el.classList.remove('disabled');
    el.removeAttribute('disabled');
}

function disable(el) {
    el.classList.add('disabled');
    el.setAttribute('disabled', '');
}

document.getElementById('tmdbSearchModal').addEventListener('show.bs.modal', event => {
    let button = event.relatedTarget;
    let id = button.closest('.netflixrow').id;
    document.getElementById('tmdbSearchModal').setAttribute('data-rowid', id);
    document.getElementById('tmdbSearchModal').setAttribute('data-rowid', id);
});

document.getElementById('tmdbSearchModal').addEventListener('hidden.bs.modal', event => {
    document.getElementById('tmdbsearchresults').innerHTML = '';
});
