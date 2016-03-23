# EA Share Count #
**Contributors:** jaredatch, billerickson
**Tags:** facebook, linkedin, pinterest, share, share buttons, social, stumbleupon, twitter
**Requires at least:** 4.1 
**Tested up to:** 4.4  
**Stable tag:** 1.5.5
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

A lean plugin that leverages SharedCount.com API to quickly retrieve, cache, and display various social sharing counts.

## Installation ##

Once installed, go to Settings > Share Count to customize. 

Register at [SharedCount.com](http://www.sharedcount.com), then add your API code to the settings page. In most instances the free plan will be enough due to caching of share counts. 

If you do not provide a SharedCount API key, the plugin will still display share buttons but all counts will be 0. I recommend you select "No" in the "Show Empty Counts" dropdown.

Specify the services you would like included, the theme location, and style.

## Customization ##

You can also use the ea_share() function to access any of the internal methods. The most common use will be to get a specific share count. 

* `ea_share()->core->count( get_the_ID(), 'facebook' );` Provides the number of facebook likes/shares/comments
* `ea_share()->core->count( get_the_ID(), 'included_total' );` Provides the total count from all the services specified in settings

There are also many filters in place to customize the plugin. [Here are some code snippets](http://www.billerickson.net/code-tag/ea-share-count/).

* `ea_share_count_display` Customize what is displayed in the share count area.
* `ea_share_count_theme_locations` Specify which hooks/filters are used for the "Before Content" and "After Content" share buttons. See [the code](https://github.com/jaredatch/EA-Share-Count/blob/master/includes/class-front.php#L38) for more information.
* `ea_share_count_link` An array of elements used to form the share link. See [the code](https://github.com/jaredatch/EA-Share-Count/blob/master/includes/class-front.php#L442) for more information.
* `ea_share_count_default_image` Default image used by Pinterest for generic URLs.
* `ea_share_count_single_image` Image used by Pinterest for singular content (post, page...). Set to Featured Image by default.
* `ea_share_count_site_url` What URL is used if you specify 'site' as the ID. Defaults to home_url(). 
* `ea_share_count_single` The share count used when the requested service isn't recognized. If you're adding custom services, you'll use this to set the share count for that service.
* `ea_share_count_total` Customize what is used as the 'total_count'
* `ea_share_count_load_css` Disable the CSS from loading, [like this](https://gist.github.com/billerickson/fe8079583c1b030e4d59). Note that the icons are part of the CSS, so you'll need to include your own icon font.
* `ea_share_count_load_js` Disable the JavaScript from loading. 
* `ea_share_count_email_modal` Enable email modal window in use cases where the share button is manually being called.
* `ea_share_count_email_labels` Email modal window labels for different fields. See [the code](https://github.com/jaredatch/EA-Share-Count/blob/master/includes/class-front.php#L188) for more information.
* `ea_share_count_email_subject` Subject used in email modal. Defaults to "Your friend $from_name has shared an article with you"
* `ea_share_count_email_body` Body of email used in email modal. Defaults to Post Title and Post Permalink.
* `ea_share_count_email_headers` Email headers used by email modal. See [the code](https://github.com/jaredatch/EA-Share-Count/blob/master/includes/class-core.php#L72) for more information.
* `ea_share_count_update_increments` How frequently the share counts are updated. See [the code](https://github.com/jaredatch/EA-Share-Count/blob/master/includes/class-core.php#L297) for default increments.
* `ea_share_count_api_params` Customize parameters passed to SharedCount API
* `ea_share_count_admin_services` What services are available on the settings page
* `ea_share_count_options` The options used on the settings page


## Screenshots ##

### 1. Settings Page. ###
![Settings Page](https://s3.amazonaws.com/f.cl.ly/items/3p3t471j112o2U3D2t2f/screenshot-1.jpg?v=cf213561)

### 2. Bubble Style (default) ###
![Bubble Style](https://s3.amazonaws.com/f.cl.ly/items/1D0m2q270u1719112W3S/Screen%20Shot%202016-03-23%20at%204.02.31%20PM.png?v=84be6c71)

### 3. Fancy Style ###
![Fancy Style](https://s3.amazonaws.com/f.cl.ly/items/1K3q1G312k3F3u0r0r21/Screen%20Shot%202016-03-23%20at%204.03.33%20PM.png?v=f44e0d06)

### 4. Slim Style ###
![Slim Style](https://s3.amazonaws.com/f.cl.ly/items/1L06211I3E3v1O2o0y0L/slim.jpg?v=58095cff)