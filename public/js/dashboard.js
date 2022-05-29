function toggleButton (element) {
	if (element.nextElementSibling.style.display == "none") {
		// element.classList.remove("active");
		element.nextElementSibling.style.display = "";
		element.style.backgroundColor = "#e9ecef";
	} else {
		// element.classList.add("active");
		element.nextElementSibling.style.display = "none";
		element.style.backgroundColor = "#fff";
	}
}
