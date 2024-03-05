# AM+

## Help

> Execute queue jobs.

```bash
php artisan queue:work --stop-when-empty
```

> Execute queue jobs in the background.

```bash
php artisan queue:work --queue=update-artist --daemon
```

> Clear jobs.

```bash
php artisan queue:clear --queue=update-artist
```

### Run locally

Open 2 terminals, one for serve, one for artisan queue command :

```bash
php artisan serve  --port=8080
php artisan queue:work --queue=update-artist --daemon
```

### Apache conf example

```apacheconf
Define PROJECT_DIR c:/.../apple-music-plus-backend
Define PROJECT_URL server-name.wip

<VirtualHost ${PROJECT_URL}:80>
    DocumentRoot ${PROJECT_DIR}/public
    ServerName ${PROJECT_URL}
	ErrorLog ${INSTALL_DIR}/logs/amplus-error.log
	CustomLog ${INSTALL_DIR}/logs/amplus-access.log combined
    <Directory "${PROJECT_DIR}/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

## Commands

### Update all artists

```bash
php artisan app:fetch-all-artists {job=0}
```

### Update artist

```bash
php artisan app:fetch-artist {Artist.storeId} {job=0}
```

## Routes

### /api/auth

-   POST /api/auth/register
    -   name\* (string)
    -   email\* (string)
    -   password\* (string)
-   POST /api/auth/login
    -   email\* (string)
    -   password\* (string)

### ...

-   GET /api/developer_token

### /api/artist

-   POST /api/artist : update artist
    -   artist_id\* (int : storeId)
-   GET /api/artist/list : list all artists (pagination & limit)
    -   sort (string : name | -name | store_id | -store_id | label | -label | last_updated | -last_updated | last_created | -last_created)
    -   page (integer : >= 1)
    -   limit (integer : 5 >= 1000)
-   POST /api/artist/fetch : fetch artist's releases
    -   artist_id\* (int : storeId)
    -   job (boolean, default : false)
-   POST /api/artist/fetchall : fetch all artists releases
    -   job (boolean, default : false)

### /api/user (sanctum middleware)

Use token as Bearer token.

-   GET /api/user
-   GET /api/user/artists
    -   sort (string : name | -name | store_id | -store_id | label | -label | last_updated | -last_updated | last_created | -last_created)
    -   page (integer : >= 1)
    -   limit (integer : 5 >= 1000)
-   GET /api/user/artists/search
    -   term\* (string : <= 255)
    -   page (integer : >= 1)
    -   l (string)
    -   limit (integer : 5 <= 25)
    -   offset (string)
    -   with (string)
-   POST /api/user/artists/fetchall
    -   job (boolean, default : false)
-   POST /api/user/artists/subscribe
    -   artist_id\* (int : storeId)
    -   fetch (boolean, default : false)
-   POST /api/user/artists/unsubscribe
    -   artist_id\* (int : storeId)

#### /api/user/releases

-   GET /api/user/releases

    -   sort (string : name | -name | artistName | -artistName | releaseDate | -releaseDate - created_at | -created_at)
    -   from (string : date format YYYY-MM-DD),
    -   hide_albums (boolean)
    -   hide_eps (boolean)
    -   hide_singles (boolean)
    -   content_rating (string)
    -   all_content_rating (boolean)
    -   weekly (boolean)
    -   artists_ids (string[] : artists.storeId, must exist)'
    -   hide_upcoming (boolean : prohibits only_upcoming)
    -   only_upcoming (boolean : prohibits hide_upcoming)

    > Related & useful routes

        -   GET /api/user/releases/albums
        -   GET /api/user/releases/singles
        -   GET /api/user/releases/eps
        -   GET /api/user/releases/projects

-   GET /api/user/releases/songs
    -   sort (string : name | -name | artistName | -artistName | releaseDate | -releaseDate | - created_at | -created_at)
    -   from (string : date format YYYY-MM-DD),
    -   content_rating (string)
    -   all_content_rating (boolean)
    -   weekly (boolean)
    -   'weeks' => 'integer|min:1',
    -   include_releases (boolean)
    -   artists_ids (string[] : artists.storeId, must exist)'
    -   hide_upcoming (boolean : prohibits only_upcoming)
    -   only_upcoming (boolean : prohibits hide_upcoming)

#### /api/user/tokens

-   GET /api/user/tokens
-   POST /api/user/tokens/create
    -   token_name (string)
-   DELETE /api/user/tokens/delete
    -   token_name (string) OR id (int)

### /api/applemusic

#### /api/applemusic/library (musicKit middleware)

-   POST /api/applemusic/library
    -   type\* (string : albums | songs)
    -   ids (string[] : storeId)

---

### /api/test

-   GET /test/artists
-   GET /test/albums
-   GET /test/users
-   GET /test/songs
-   GET /test/weeklydate

-   GET /api/test/itunesapi
-   GET /api/test/itunesapiscrapped
-   GET /api/test/applemusicapi

#### /api/test/musickitapi

Use Music-Token header with your Music Kit token.

-   GET /api/test/musickitapi
-   GET /api/test/musickitapi/artists
-   GET /api/test/musickitapi/artists/full
