<p align="center"><img src="https://i.kym-cdn.com/photos/images/newsfeed/001/516/619/f59.png"></p>

## About
This is an exhentai gallery archiver based on Laravel and inspired by [this project](https://github.com/Sn0wCrack/ExHen-Archive). I'm working on this to put my Laravel knowledge to the test. Right now it can only download the gallery metadata and store in database. 

## Features
Top ones are most likely to come sooner.

- [x] Request to archive from favorites page (Supported modes: Minimal, Compact)
- [x] Show feedback of succesful archiving to user in browser 
- [x] Get official gallery metadata 
- [x] Browse archived galleries through API
- [x] Search by name and tags 
- [x] Download and store gallery from official archives
- [x] Keep track of archived galleries and show a badge on the exhentai list
- [ ] Request to archive from gallery page and search page 
- [ ] Manually add gallery
- [ ] Archive galleries on background (queue jobs)
- [ ] Synchronize gallery with exhentai
- [ ] Track EH-Credits and give warning when amount isn't enough to download gallery
- [ ] Basic authorization
- [ ] Gallery viewer and browser
- [ ] Download torrent
- [ ] Scheduled downloads
- [ ] Mobile mode gallery viewer
- [ ] Add public and private paths to galleries

## Requirements
- Apache2 and MySQL
- PHP 7.2+
- Composer
- Browser with userscript extension installed

## Setup
1. `git clone repo@ssh exhentai-archiver && cd exhentai-archiver`
2. `composer install`
3. Edit .env file with database login and name of database to use, defaults: root, no pass, exhentai
4. ```bash
	php artisan queue:table 
	php artisan queue:failed-table
	php artisan migrate
	php artisan queue:work
	```
5. Install an userscript extension on your browser (I recommend ViolentMonkey)
6. Edit `var api_url = 'http://localhost/lr/ehentai-archiver/public/api/'`, replace `lr/ehentai-archiver/` with the directory where you installed the project
7. Add the userscript.js to the extension

## Usage
1. Go to exhentai favorites page
2. Check the boxes of galleries you want to archive, select Archive and click Confirm. Careful not to select Delete
3. (Expected behavior) The galleries will be downloaded and you will see a green badge appear on succesful jobs or a red badge on errors.

## ToDo
1. Write more detailed description
2. Make noob friendly setup