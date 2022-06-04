const elems = document.querySelectorAll('.datepicker_input')
for (const elem of elems) {
	const datepicker = new Datepicker(elem, {
		'format': 'dd.mm.yyyy', // UK format
		title: 'Watch date',
	})
}

$('#watchDateModal').on('show.bs.modal', function (e) {
	document.getElementById('watchDate').value = ''
	document.getElementById('tmdbId').value = e.relatedTarget.id
})
