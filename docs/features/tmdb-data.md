## Movie meta data

Update movie or person meta data with themoviedb.org information.
Make sure you have added the variables `TMDB_API_KEY` to the environment.

Helpful commands:

`php bin/console.php tmdb:movie:sync` -> Refresh local movie meta data

`php bin/console.php tmdb:person:sync` -> Refresh local person meta data

**Interesting flags:**

- `--hours`
  Only update movies/persons which were last synced X hours or longer ago
- `--threshold`
  Maximum number of movies/person to sync for this run

## Image Cache

Enable by setting environment variable `TMDB_ENABLE_IMAGE_CACHING` to `1`.

To e.g. prevent rate limit issues with the TMDB api you should cache tmdb images (movie/person posters) with movary.
This will store a local copy of the image in the `storage` directory and serve this image instead of the original one from TMDB.
Make sure you persist the content of the `storage` directory to keep data e.g. when restarting docker container.

Execute the cache refresh command regularly, e.g. via cronjob, to keep the cache up to date.

Helpful commands:

- `php bin/console.php tmdb:imageCache:refresh` -> Refresh local image cache
- `php bin/console.php tmdb:imageCache:delete` -> Delete locally cached images
