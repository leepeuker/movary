function toggleButton (element) {
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
