// ==UserScript==
// @name     E-hentai favorite pages archiver
// @version  1
// @grant    none
// @match      *://exhentai.org/*
// @match      *://e-hentai.org/*
// ==/UserScript==
docReady(function () {
	const form = document.querySelector('form[name="favform"]')

	// on click confirm button @ favorites
	form.addEventListener('submit', function (e) {
		e.preventDefault();

		const body = new FormData(this);

		let action = body.get('ddact')
		let gallery_ids = body.get('modifygids')

		if (action !== 'archive' && !gallery_ids) {
			console.log('not archiving')
			return
		}

		console.log('archiving')
	})

	const action_selector = document.querySelector('[name="ddact"]')

	let opt = document.createElement('option');
	opt.value = 'archive'
	opt.innerHTML = 'Archive'
	action_selector.appendChild(opt)
})

function docReady(fn) {
    // see if DOM is already available
    if (document.readyState === "complete" || document.readyState === "interactive") {
        // call on next available tick
        setTimeout(fn, 1);
    } else {
        document.addEventListener("DOMContentLoaded", fn);
    }
}