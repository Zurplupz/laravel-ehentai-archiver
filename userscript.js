// ==UserScript==
// @name     E-hentai favorite galleries archiver
// @author	 Zurplupz - https://github.com/Zurplupz
// @version  0.0.1
// @grant    none
// @match      *://exhentai.org/*
// @match      *://e-hentai.org/*
// ==/UserScript==
var api_url = 'http://localhost/lr/ehentai-archiver/public/api/'

docReady(function () {
	const form = document.querySelector('form[name="favform"]')

	// on click confirm button @ favorites
	form.addEventListener('submit', async function (e) {
		e.preventDefault();

		const form = new FormData(this);

		let action = form.get('ddact')
		let gallery_ids = form.getAll('modifygids[]')

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

		for (let i in gallery_ids) {
			let id = gallery_ids[i]
			let gdata = findGalleryData(id)

			if (!gdata) continue

			galleries[id] = gdata
		}

		if (!galleries) return

		const body = JSON.stringify({ galleries })

		try {
			let response = await apiRequest(api_url + 'galleries', {
				body, method : 'post', 
				headers : { 'Content-Type': 'application/json' }
			})

			console.log(response)
		}

		catch (e) {
			alert(e)
			console.error(e)
		}
	})

	const action_selector = document.querySelector('[name="ddact"]')

	let opt = document.createElement('option');
	opt.value = 'archive'
	opt.innerHTML = 'Archive'
	action_selector.appendChild(opt)

	console.log('done')
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
		let json;

		try {
			json = await response.json()
		} catch (e) {
			throw new Error(e)
		}

		throw new Error(json.errors)
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

function findGalleryData(value) {
	let selector = 'input[value="' + value + '"]'

	const input = document.querySelector(selector)

	if (!input) { 
		console.error('Not found input with name: ' + selector)
		return 
	}

	let mode = document.querySelector('#dms select').value

	if (!mode) {
		throw 'Cant find gallery mode selector'
	}

	switch (mode) {
		case 't' : {
			let wrapper =  input.closest('.glname')

			if (!wrapper) {
				console.error('Not found wrapper for field: ' + value)
				return ''
			}

			return {
				url : wrapper.firstElementChild.firstElementChild.getAttribute('href'),
				name : wrapper.querySelector('.glink').innerText
			}
		}

		case 'm' : {
			let row =  input.closest('tr')

			if (!row) {
				console.error('Not found wrapper for field: ' + value)
				return ''
			}

			return { 
				url : row.querySelector('.glname a').getAttribute('href'),
				name : row.querySelector('.glink').innerText,
				category : row.querySelector('.glcat .cs').innerText,
				favorited : row.querySelector('.glfav').innerText,
				posted : row.querySelector('.gl2m').lastElementChild.innerText,
				group : row.querySelector('.gl2m').lastElementChild.getAttribute('title')
			}
		}

		default: {
			console.error('invalid or not supported mode')
			return ''
		}
	}
}