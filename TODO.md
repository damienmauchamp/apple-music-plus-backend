# TODO

## Next

### Important

-   ‼️ storefront/timezone => must be the same ? if not, "upcoming" will contain stuff releasing tomorrow !OR! if streamable OK
    -   preorders : `"isComplete": false`
    -   songs : `"attributes.previews": []` // `"attributes.previews": [ { url: "..." } ]`

### Routes

-   subscribe to multiple artists ======> if error, do not sub
-   unsubscribe to multiple artists
-   fetch multiple artists
-   ? iTunes API version of fetching ?

### Commands

-   fetching artist's infos (https://developer.apple.com/documentation/applemusicapi/get_a_catalog_artist)
    -   for albums: : https://api.music.apple.com/v1/catalog/:storefront/artists/:id/:relationship?limit=100&sort=-releaseDate ({next?: string, data: [{id, type: "albums", href, attributes: {...}] )
        -   relationship: albums
        -   limit : <=100
    -   for albums: : https://api.music.apple.com/v1/catalog/:storefront/artists/:id/:relationship?limit=20&sort=-releaseDate ({next?: string, data: [{id, type: "songs", href, attributes: {...}] )
        -   relationship: songs
        -   limit : <=20

## Later

-   get user's artists in his library (firstly back : send music token then fetch / then front : fetch artists then display the list ?)

## Ideas

- dockerise
- seed db
