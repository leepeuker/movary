function toggleBiography()
{
    let expandContainer = document.getElementById('expandContainer');
    if(document.getElementsByClassName('truncated').length > 0) {
        document.getElementById('biographyParagraph').classList.remove('truncated');
        expandContainer.getElementsByTagName('i')[0].classList.remove('bi-chevron-down');
        expandContainer.getElementsByTagName('i')[0].classList.add('bi-chevron-up');
        expandContainer.children[1].innerHTML = 'Show less&#8230;';
    } else {
        document.getElementById('biographyParagraph').classList.add('truncated');
        expandContainer.getElementsByTagName('i')[0].classList.add('bi-chevron-down');
        expandContainer.getElementsByTagName('i')[0].classList.remove('bi-chevron-up');
        expandContainer.children[1].innerHTML = 'Show more&#8230;';        
    }
}

document.addEventListener("DOMContentLoaded", () => {
    let biographyHeight = document.getElementById('biographyParagraph').offsetHeight;
    let windowHeight = window.outerHeight;
    if(((biographyHeight / windowHeight) * 100) > 20) {
        document.getElementById('biographyParagraph').classList.add('truncated');
        document.getElementById('expandContainer').classList.remove('d-none');
    }
});