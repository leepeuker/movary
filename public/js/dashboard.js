function toggleButton (element) {
	if (element.nextElementSibling.classList.contains("inactiveItem") == true) {
		// element.classList.remove("active");
		element.nextElementSibling.classList.remove("inactiveItem");
		element.nextElementSibling.classList.add("activeItem");
		element.classList.remove("inactiveItemButton");
		element.classList.add("activeItemButton");

		element.getElementsByClassName("bi")[0].classList.remove("bi-chevron-down");
		element.getElementsByClassName("bi")[0].classList.add("bi-chevron-up");
	} else {
		// element.classList.add("active");
		element.nextElementSibling.classList.remove("activeItem");
		element.nextElementSibling.classList.add("inactiveItem");
		element.classList.remove("activeItemButton");
		element.classList.add("inactiveItemButton");

		element.getElementsByClassName("bi")[0].classList.remove("bi-chevron-up");
		element.getElementsByClassName("bi")[0].classList.add("bi-chevron-down");
	}
}
