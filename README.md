# AM+

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

## Run locally

Open 2 terminals, one for serve, one for artisan queue command :

```bash
php artisan serve  --port=8080
php artisan queue:work --queue=update-artist --daemon
```

## Apache conf example

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

---

-   POST /api/auth/register
    -   name (string)
    -   email (string)
    -   password (string)
-   POST /api/auth/login
    -   email (string)
    -   password (string)

## /api/user

Use token as Bearer token.

-   GET /api/user
-   GET /api/user/tokens
-   POST /api/user/tokens/create
    -   token_name (string)
-   DELETE /api/user/tokens/delete
    -   token_name (string) OR id (int)

## /api/test

-   /api/test/itunesapi
-   /api/test/itunesapiscrapped
-   /api/test/applemusicapi

### /api/test/musickitapi

Use Music-Token header with your Music Kit token.

-   GET /api/test/musickitapi
-   GET /api/test/musickitapi/artists
-   GET /api/test/musickitapi/artists/full

> to complete
