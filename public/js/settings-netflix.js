async function importNetflixHistory() {
    var input = document.getElementById('netflixfile');
    var filedata = new FormData();
    filedata.append('netflixviewactivity', input.files[0]);
    await createloader();
    await fetch('/settings/netflix', {
        method: 'POST',
        body: filedata
    })
    .then(response => {
        document.querySelector('div.spinner-border').parentElement.remove();
        if(!response.ok) {
            processError(response.status);
            return false;
        } else {
            return response.json();
        }
    })
    .then(data => {
        if(data != false) {
            processdata(data)
        }
    })
    .catch(function(error) {
        console.error(error);
    });
}

async function createloader() {
    document.getElementById('netflixtbody').innerHTML = '';
    let row = document.createElement('tr');
    let cell = document.createElement('td');
    let div = document.createElement('div');
    let span = document.createElement('span');
    cell.colSpan = 4;

    div.className = 'spinner-border';
    span.className = 'visually-hidden';

    span.innerText = 'Loading...';
    cell.innerText = "";
    div.append(span);
    cell.append(div);
    row.append(cell);
    document.getElementById('netflixtbody').append(row);
}

function updatetable() {
    let amount = document.getElementById('amounttoshow').value;
    let rows = document.getElementById('netflixtbody').children;
    if(amount == 'all') {
        createpagenav(rows.length, rows.length);
    } else {
        createpagenav(amount, rows.length);
    }
    changepage(1);
}

function changepage(direction) {
    let ul = document.getElementsByClassName('pagination')[0];
    let amount = document.getElementById('amounttoshow').value;
    let rows = document.getElementById('netflixtbody').children;
    var targetpage = -1;
    if(direction === 'previous') {
        if(!ul.children[1].classList.contains('active')) {
            document.getElementsByClassName('page-item active')[0].previousElementSibling.classList.add('active');
            document.getElementsByClassName('page-item active')[1].classList.remove('active');
            targetpage = parseInt(document.getElementsByClassName('page-item active')[0].innerText);
        }
    } else if(direction === 'next') {
        if(!ul.children[ul.childElementCount - 2].classList.contains('active')) {
            document.getElementsByClassName('page-item active')[0].nextElementSibling.classList.add('active');
            document.getElementsByClassName('page-item active')[0].classList.remove('active');
            targetpage = parseInt(document.getElementsByClassName('page-item active')[0].innerText);
        }
    } else if(!isNaN(parseInt(direction))) {
        document.getElementsByClassName('page-item active')[0].classList.remove('active');
        document.querySelectorAll('li.page-item:not(.active)').forEach((el) => {
            if(el.innerText == direction) {
                el.classList.add('active');
            }
        })
        targetpage = parseInt(direction);
    }

    if(targetpage != -1) {
        document.querySelectorAll("tr:not(.d-none)").forEach((el) => {
            el.classList.add('d-none');
        });
        if(amount == 'all') {
            for(let i = 0; i < rows.length; i++) {
                rows[i].classList.remove('d-none');
            }
        } else {
            for(let i = amount * targetpage - amount + 1; i < amount * targetpage + 1; i++) {
                if(rows.length > i) {
                    rows[i].classList.remove('d-none');
                }
            }
        }
    }
}

function createpagenav(amount, items) {
    buttons_number = Math.ceil(items / amount);
    let ul = document.getElementsByClassName('pagination')[0];
    var lastchild = ul.children[ul.childElementCount - 1];

    // remove all children except the first ('previous' button) and the last ('next' button)
    while(ul.childElementCount > 2) {
        lastchild.previousElementSibling.remove();
    }

    // Create nav buttons
    for(let i = 0; i < buttons_number; i++) {
        let li = document.createElement('li');
        let link = document.createElement('a');
        li.style.cursor = 'pointer';
        li.className = i == 0 ? 'page-item active' : 'page-item';
        link.className = 'page-link';
        link.innerText = i + 1;
        li.append(link);
        // For some reason an event instantly runs if a parameter is passed directly to the callback function, so it has to be done this way
        li.addEventListener("click", () => { changepage(link.innerText); });
        lastchild.before(li);
    }

    if(ul.childElementCount == 3) {
        lastchild.classList.add('disabled');
        ul.children[0].classList.add('disabled');

        lastchild.style.cursor = 'not-allowed';
        ul.children[0].style.cursor = 'not-allowed';
    } else {
        lastchild.classList.remove('disabled');
        ul.children[0].classList.remove('disabled');

        lastchild.style.cursor = 'pointer';
        ul.children[0].style.cursor = 'pointer';
    }

    lastchild.addEventListener("click", () => { changepage('next'); });
    ul.children[0].addEventListener("click", () => { changepage('previous'); });
}

function processdata(data) {
    let keys = Object.keys(data);
    let amount = document.getElementById('amounttoshow').value;
    keys.forEach((key, index) => {
        let row = document.createElement('tr');
        let indexcell = document.createElement('td');
        let netflix_name = document.createElement('td');

        let tmdb = document.createElement('td');
        let tmdb_div = document.createElement('div');
        let tmdb_cover_div = document.createElement('div');
        let tmdb_description_div = document.createElement('div');
        let tmdb_cover = document.createElement('img');
        let tmdb_cover_br = document.createElement('br');
        let tmdb_link = document.createElement('a');
        let description = document.createElement('b');
        let date = document.createElement('td');


        netflix_name.innerText = data[key]['originalname'];
        indexcell.innerText = index + 1;

        row.className = index + 1 > amount ?  'd-none' : '';

        row.setAttribute('tmdbid', data[key]['result']['id']);

        tmdb.className = 'w-50';
        tmdb_div.className = "row";
        tmdb_cover_div.className = 'col-md-3 justify-content-center';
        tmdb_description_div.className = 'col-md-9 text-start';
        tmdb_cover.style.width = '92px';
        tmdb_cover.alt = 'Movie poster of ' + (data[key]['result']['title'] ?? 'missing item');

        if(data[key]['result'] == 'Unknown' || data[key]['result']['poster_path'] == null) {
            tmdb_cover.src = window.location.protocol + "//" + window.location.host + '/images/placeholder-image.png';
            tmdb_cover.className = 'img-fluid';
            tmdb_link.innerText = 'Item not found on TMDB';
        } else {
            tmdb_cover.src = 'https://image.tmdb.org/t/p/w92' + data[key]['result']['poster_path'];
            tmdb_cover.className = 'img-fluid';
            tmdb_link.href = 'https://www.themoviedb.org/movie/' + data[key]['result']['id'];
            tmdb_link.target = '__blank';
            tmdb_link.innerText = data[key]['result']['title'];
        }

        if(data[key]['result'] == 'Unknown' || data[key]['result']['overview'] == null) {
            description.innerText = 'Description not found';
            tmdb_description_div.append(description);
        } else {
            let br = document.createElement('br');
            let paragraph = document.createElement('p');
            let release_date = document.createElement('p');
            
            description.innerText = 'Description: ';
            paragraph.innerText = data[key]['result']['overview'];
            release_date.innerText = 'Release date: ' + data[key]['result']['release_date'];
            tmdb_description_div.append(description, br, paragraph, release_date);
        }

        date.innerText = data[key]['date']['day'] + "/" + data[key]['date']['month'] + "/" + data[key]['date']['year'];

        tmdb_cover_div.append(tmdb_cover, tmdb_cover_br, tmdb_link);
        tmdb_div.append(tmdb_cover_div, tmdb_description_div);
        tmdb.append(tmdb_div);
        row.append(indexcell, date, netflix_name, tmdb);
        document.getElementById('netflixtbody').append(row);
    });
    createpagenav(amount, keys.length);
    document.getElementById('amounttoshow').addEventListener('change', updatetable);
}

function processError(errorcode) {
    document.getElementById('netflixtbody').innerHTML = '';
    let errorrow = document.createElement('tr');
    let errorcell = document.createElement('td');
    errorcell.colSpan = 4;

    if(errorcode == 400) {
        errorcell.innerText = 'Error 400. Input file could not be processed. Please try again.';
    } else if(errorcode == 415) {
        errorcell.innerText = 'Error 415. Input file is the wrong type. Upload a CSV file from Netflix instead.';
    }

    errorrow.append(errorcell);
    document.getElementById('netflixtbody').append(errorrow);
}