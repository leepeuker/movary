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
        let spinner = document.querySelector('div.spinner-border');
        spinner.remove();
        if(!response.ok) {
            processError(response.status);
        } else {
            return response.json();
        }
    })
    .then(data => {
        processdata(data)
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

function processdata(data) {
    let keys = Object.keys(data);
    keys.forEach((key, index) => {
         let row = document.createElement('tr');
         let indexcell = document.createElement('td');
         let netflix_name = document.createElement('td');
         let tmdb = document.createElement('td');
         let tmdb_cover = document.createElement('img');
         let tmdb_link = document.createElement('a');
         let date = document.createElement('td');

         netflix_name.innerText = data[key]['originalname'];
         indexcell.innerText = index + 1;

        if(data[key]['result'] == 'Unknown') {
            tmdb_cover.src = window.location.protocol + "//" + window.location.host + '/images/placeholder-image.png';
            tmdb_cover.className = 'img-fluid';
            tmdb_cover.style.width = '92px';
            tmdb_link.innerText = 'Not found';
        } else {
            tmdb_cover.src = 'https://image.tmdb.org/t/p/w92' + data[key]['result']['poster_path'];
            tmdb_cover.className = 'img-fluid';
            tmdb_link.href = 'https://www.themoviedb.org/movie/' + data[key]['result']['id'];
            tmdb_link.target = '__blank';
            tmdb_link.innerText = data[key]['result']['title'];
        }

         date.innerText = data[key]['date']['day'] + "/" + data[key]['date']['month'] + "/" + data[key]['date']['year'];

         tmdb.append(tmdb_cover, tmdb_link);
         row.append(indexcell, date, netflix_name, tmdb);
         document.getElementById('netflixtbody').append(row);
    });
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