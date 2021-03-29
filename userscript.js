// ==UserScript==
// @name     E-hentai favorite pages archiver
// @author	 Zurplupz - https://github.com/Zurplupz
// @version  0.0.1
// @grant    none
// @match      *://exhentai.org/*
// @match      *://e-hentai.org/*
// ==/UserScript==
var api_url = ''

docReady(function () {
	const form = document.querySelector('form[name="favform"]')

	// on click confirm button @ favorites
	form.addEventListener('submit', async function (e) {
		e.preventDefault();

		const body = new FormData(this);

		let action = body.get('ddact')
		let gallery_ids = body.getAll('modifygids[]')

		if (action !== 'archive' && !gallery_ids) {
			console.log('not archiving')
			return
		}

		console.log('archiving')

		if (!api_url) {
			alert('API URL not set in UserScript')
			return
		}

		let galleries = {}

		console.log(gallery_ids)

		for (let i in gallery_ids) {
			let id = gallery_ids[i]
			let url = findGalleryUrl(id)

			if (!url) continue

			galleries[id] = url
		}
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

async function apiRequest(url, params={}, expect='json') {
	let response = await fetch(url, params)

	if (!response.ok) {
		let body = response.body

		try {
			let data = await response.json()
		} catch (e) {
			throw body
		}

		throw data.error
	}

	switch (expect) {
		case 'json':
			data = await response.json()
			break;
		default:
			data = await response.text()
			break;
	}

	return data
}

function findGalleryUrl(value) {
	let selector = 'input[value="' + value + '"]'

	const input = document.querySelector(selector)

	if (!input) { 
		console.error('Not found input with name: ' + selector)
		return 
	}

	let url = input.closest('.glname').firstElementChild.firstElementChild.getAttribute('href')

	return url || ''
}