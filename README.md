# Facebook Cover Scraper
WordPress scraper plugin that gets cover images from profiles/groups etc without an api

## Shortcode

```
[fb_public_cover url="https://facebook.com/someonesprofile" expiry="7 Days"]
// TODO - actually output the image
```

## Code
```
FacebookCoverScraper::getInstance()->get_public_cover_src('https://www.facebook.com/somepublicprofile/', '180 minutes')
// path of image returned 
```

* Plugin will save the image to wp-uploads/facebook-public-cover
* When the expiry passes the page request will blow away the cached image and refetch. 
* omitting the expiry will keep the image indefinitely.


