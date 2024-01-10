## Introduction

The primary source for information about movies, persons etc. is [TMDB](https://www.themoviedb.org/).
Make sure you have set a valid TMDB api key, e.g. via environment variable (`TMDB_API_KEY`) or in the admin server settings UI.


!!! tip

    Execute the metadata sync and image cache commands regularly, e.g. via cronjobs, to keep your application data up to date.

## Movie & Person data

### Description
Update local movie or person information with the latest data from TMDB.

### Commands
```shell
php bin/console.php tmdb:movie:sync
php bin/console.php tmdb:person:sync
```

#### Important flags

- `--help`
  Detailed information about the command
- `--hours`
  Only update movies/persons which were last synced X hours or longer ago
- `--threshold`
  Maximum number of movies/person to sync for this run

#### Example

Update information for the first 50 movies which were updated at least 48 hours ago. 
```shell
php bin/console.php tmdb:movie:sync` --hours 48 --threshold 50
```

## Image Cache

### Description
Enable by setting environment variable `TMDB_ENABLE_IMAGE_CACHING` to `1`.

To prevent rate limit issues with the TMDB api you should cache TMDB images.
This will store a local copy of the images in the `storage` directory and use this image instead of the original one from TMDB.
Make sure you persist the content of the `storage` directory to keep data e.g. when restarting docker container.

### Commands
```shell
php bin/console.php tmdb:imageCache:refresh
php bin/console.php tmdb:imageCache:delete
```

## Import personal User Ratings

There is no native integration to import the personal movie ratings of a tmdb user at the moment.
However, you can use third party tools like [TMDBToMovary](https://github.com/SirMartin/TMDBToMovary) to work around this limitation. 
