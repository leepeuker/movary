function toggleButton(element) {
    if (element.nextElementSibling.classList.contains("inactiveItem") == true) {
        element.nextElementSibling.classList.remove("inactiveItem");
        element.nextElementSibling.classList.add("activeItem");
        element.classList.remove("inactiveItemButton");
        element.classList.add("activeItemButton");

        if (document.getElementById('html').dataset.bsTheme === 'dark') {
            element.classList.add("text-white");
        } else {
            element.classList.add("activeItemButtonActiveLight");
        }

        element.getElementsByClassName("bi")[0].classList.remove("bi-chevron-down");
        element.getElementsByClassName("bi")[0].classList.add("bi-chevron-up");
    } else {
        element.nextElementSibling.classList.remove("activeItem");
        element.nextElementSibling.classList.add("inactiveItem");
        element.classList.remove("activeItemButton", "text-white", "activeItemButtonActiveLight");
        element.classList.add("inactiveItemButton");

        element.getElementsByClassName("bi")[0].classList.remove("bi-chevron-up");
        element.getElementsByClassName("bi")[0].classList.add("bi-chevron-down");
    }
}

window.addEventListener('load', function () {
    const ratingData = document.getElementById('ratingData').dataset;

    console.log(document.getElementById('ratingData').dataset.count1)
    const data = [
        {rating: 1, count: ratingData.count1},
        {rating: 2, count: ratingData.count2},
        {rating: 3, count: ratingData.count3},
        {rating: 4, count: ratingData.count4},
        {rating: 5, count: ratingData.count5},
        {rating: 6, count: ratingData.count6},
        {rating: 7, count: ratingData.count7},
        {rating: 8, count: ratingData.count8},
        {rating: 9, count: ratingData.count9},
        {rating: 10, count: ratingData.count10}
    ];

    new Chart(
        document.getElementById('acquisitions'),
        {
            type: 'bar',
            data: {
                labels: data.map(row => row.rating),
                datasets: [
                    {
                        label: 'Count',
                        data: data.map(row => row.count)
                    }
                ]
            },
            options: {
                barThickness: 100,
                animation: {
                    duration: 0
                },
                plugins: {
                    legend: {
                        display: false
                    },
                },
                scales: {
                    y: {
                        display: false,
                    }
                }
            },
        }
    );

})
