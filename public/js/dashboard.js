function toggleButton (element) {
	if (element.nextElementSibling.classList.contains("inactiveItem") == true) {
		// element.classList.remove("active");
		element.nextElementSibling.classList.remove("inactiveItem");
		element.nextElementSibling.classList.add("activeItem");
		element.classList.remove("inactiveItemButton");
		element.classList.add("activeItemButton");
	} else {
		// element.classList.add("active");
		element.nextElementSibling.classList.remove("activeItem");
		element.nextElementSibling.classList.add("inactiveItem");
		element.classList.remove("activeItemButton");
		element.classList.add("inactiveItemButton");
	}
}
