# Compactorium
![Compactorium logo](public/assets/img/logotype.png)

A self-hosted mausoleum for your music CDs collection. Every barcode... opens a grave holding another relic of format that should have been extinct long ago.

## Is this for me?
- If your question is "Do I already own Lady Gaga's *Fame Monster*?" -> YES
- "I can't remember whether my *Dark Side of the Moon* is a 1984 pressing, or 1987" -> [DISCOGS](https://www.discogs.com)

## Features
- Keep track of your CDs collection. Simple **Artist - Album**.
- Quickly scan your collection with phone camera - identify albums by barcode.

## TODO/Future plans
- Collection stats
- Multiple users
- Installer with root account creation flow
- Docker Compose
- Web sockets (metadata fetched notification)
- Migrate to Symfony because things can't be kept simple

## Requirements
- PHP 8.4
- Composer

## Usage
### Installation
Clone the repository. In main directory, create a copy of `.env.example` as `.env`, and make adjustments to the settings.

Install dependencies. Assuming you have `composer` [command available](https://getcomposer.org/download/), run:
```bash
composer install
```

Point your web server to `public` directory.

### Scan
You need a device with camera that is able to maintain focus on barcodes. A smartphone will do just fine, laptops were not tested.

Go to your library page. Pressing `Open scanner` launches the camera window. Allow camera access if browser is asking you to.

The camera window lets you register multiple albums in a batch. Point your camera at the barcode on the back of each album, one at a time. Once the barcode gets detected, the proper album is automatically added to your library.


### Downloading metadata
Currently, the album info download needs to be ran manually after scanning. [In CLI](https://www.geeksforgeeks.org/php/how-to-execute-php-code-using-command-line/), launch `sync.php` - a one-shot script for fetching metadata of scanned barcodes:
```bash
php bin/sync.php
```

In future releases, this script will launch a persistent worker for filling scans metadata on the fly.