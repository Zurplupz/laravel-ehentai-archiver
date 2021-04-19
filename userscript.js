// ==UserScript==
// @name     E-hentai favorite galleries archiver
// @author	 Zurplupz - https://github.com/Zurplupz
// @version  0.1.0
// @match      *://exhentai.org/*
// @match      *://e-hentai.org/*
// @resource style http://localhost/lr/ehentai-archiver/public/css/eh-archiver.css
// @grant    GM_addStyle
// @grant    GM_getResourceText
// ==/UserScript==

const style = GM_getResourceText("style");
GM_addStyle(style);

var api_url = 'http://localhost/lr/ehentai-archiver/public/api/'
const debug = false

docReady(function () {
	displayGalleriesStatus()

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

		let inputs = document.querySelectorAll('input,select,option')

		inputs.disabled = true

		let url = api_url + 'galleries'

		if (debug) {
			url += '?debug=true'
		} 

		try {
			let response = await apiRequest(url, {
				body, method : 'post',
				headers : {
					'Content-Type': 'application/json',
					'Accept' : 'application/json' 
				}
			})

			showFeedback(response)
		}

		catch (e) {
			alert(e)
			console.error(e)
		}

		finally {
			inputs.disabled = false
		}
	})

	// add archive to options selector
	const action_selector = document.querySelector('[name="ddact"]')

	if (action_selector) {
		const opt = document.createElement('option');
		opt.value = 'archive'
		opt.innerHTML = 'Archive'
		action_selector.appendChild(opt)
	}

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
	try {
		const gallery = findGalleryElement(value)
		const el = gallery.wrapper

		switch (gallery.mode) {
			case 't' :
			return {
				url : el.firstElementChild.firstElementChild.getAttribute('href'),
				name : el.querySelector('.glink').innerText
			}

			case 'm' :
			return { 
				url : el.querySelector('.glname a').getAttribute('href'),
				name : el.querySelector('.glink').innerText,
				category : el.querySelector('.glcat .cs').innerText,
				favorited : el.querySelector('.glfav').innerText,
				posted : el.querySelector('.gl2m').lastElementChild.innerText,
				group : el.querySelector('.gl2m').lastElementChild.getAttribute('title')
			}

			default: break;
		}
	}

	catch (e) { 
		console.error(e)
		return 
	}
}

function findGalleryElement(value) {
	let selector = 'input[value="' + value + '"]'

	const input = document.querySelector(selector)

	if (!input) {
		throw new Error('Not found input with name: ' + selector)
	}

	const mode = document.querySelector('#dms select').value

	if (!mode) {
		throw new Error('Cant find gallery mode selector')
	}

	switch (mode) {
		case 't' : {
			let wrapper =  input.closest('.glname')

			if (!wrapper) {
				throw new Error('Not found wrapper for field: ' + value)
			}

			return { wrapper, mode }
		}

		case 'm' : {
			let wrapper =  input.closest('tr')

			if (!wrapper) {
				throw new Error('Not found wrapper for field: ' + value)
			}

			return { wrapper, mode }
		}

		default: {
			throw new Error('invalid or not supported mode: ' + mode)
		}
	}
}

function showFeedback(api_response) {
	if (api_response.errors) return

	for (let id in api_response) {
		try {
			const gallery = findGalleryElement(id)
			const status = api_response[id]['status']

			addBadge(gallery.wrapper, gallery.mode, status)
		}

		catch (e) {
			console.error(e)
			continue
		}
	}
}

function statusLabel(status) {
	const label = document.createElement('label')
	label.classList.add('gal-status-label')

	switch (status) {
		case 'archived': {
			label.innerText = 'Archived'
			label.classList.add('gal-archived')
			break;
		}
		case 'pending': {
			label.innerText = 'Pending'
			label.classList.add('gal-pending')
			break;
		}
		case 'error': {
			label.innerText = 'Error'
			label.classList.add('gal-error')
			break;
		}
		case 'not_found': {
			label.innerText = 'Not found'
			label.classList.add('gal-unknown')
			break;
		}

		default: 
			throw new Error('status not valid: ' + status)
			break;
	}

	label.innerText += ' ' + emoji(status)

	return label
}

function emoji(name) {
	const emojis = {
		archive : 0x1F4C1,
		pending : 0x1F504,
		error : 0x274C,
		not_found : 0x2753
	}

	if (!emojis[name]) {
		return ''
	}

	return String.fromCodePoint(emojis[name])
}

function addBadge(el, gallery_mode, status) {

	let badge = statusLabel(status)
	
	switch (gallery_mode) {
		case 't' : {
			return;
		}

		case 'm' : {
			el.querySelector('.glname').prepend(badge)
			return;
		}

		default: return;
	}
}

async function displayGalleriesStatus() {
	if (!api_url) {
		alert('API URL not set in UserScript')
		return
	}

	const mode = document.querySelector('#dms select').value

	if (!mode) {
		throw new Error('Cant find gallery mode selector')
	}

	const gid_list = []
	let links;

	switch (mode) {
		case 't' : break;

		case 'm' :
			links = document.querySelectorAll('.glname a')
			break;

		default: {
			throw new Error('invalid or not supported mode: ' + mode)
		}
	}

	links.forEach(function (v, k) {
		const url = v.getAttribute("href")
		
		if (!url) return

		const gid = url.split('/')[4]

		if (!gid) return

		this.push(gid)

	}, gid_list);

	const gid_query = gid_list.map((v, k) => { return 'gids[]='+ v }).join('&');

	const url = api_url + 'galleries_status?' + gid_query

	try {
		const response = await apiRequest(url, { method : 'post' })
		
		showFeedback(response)
	}

	catch (e) {
		console.error(e)
	}
}