# AM+

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
