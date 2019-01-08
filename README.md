# Facebook Cover Scraper
WordPress scraper plugin that gets cover images from profiles/groups etc without an api

## Shortcode

```
[fb_public_cover slug="someonesprofile" expiry="7 Days" href="a-link" class="image-class" _target="parent"]

```

## Code
```
FacebookCoverScraper::getInstance()->get_public_cover_src('somepublicprofile', '180 minutes')
// path of image returned 
```

* Plugin will save the image to wp-uploads/facebook-public-cover
* When the expiry passes the page request will blow away the cached image and refetch. 
* omitting the expiry will keep the image indefinitely.


