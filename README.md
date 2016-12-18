# EA Share Count #
![Release](https://img.shields.io/github/release/jaredatch/ea-share-count .svg) ![Total Downloads](https://img.shields.io/github/downloads/jaredatch/ea-share-count/latest/total.svg?style=flat-square&maxAge=2592000)  ![License](https://img.shields.io/badge/license-GPL--2.0%2B-red.svg?style=flat-square&maxAge=2592000)

**Contributors:** jaredatch, billerickson  
**Tags:** facebook, linkedin, pinterest, share, share buttons, social, stumbleupon, twitter  
**Requires at least:** 4.1  
**Tested up to:** 4.6.0  
**Stable tag:** 1.7.1  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html

EA Share Count is a lean plugin for quickly retrieving, caching, and displaying various social sharing counts and buttons. It's developer-friendly and very extensible.

## Installation ##

[Download the plugin here.](https://github.com/jaredatch/EA-Share-Count/archive/master.zip) Once installed, go to Settings > Share Count to customize.

Use the "Retrieve Share Counts From" checkboxes to select which APIs you'd like to query for share counts. By default it will not receive any share counts. The Facebook API requires you to provide an Access Token.

The "Share Buttons to Display" field lets you control which share buttons to display and in what order. They can be automatically added before and/or after your site's content by selecting a Theme Location. Alternatively you can use `ea_share()->from->display()` in your theme to display the buttons.

## Screenshots ##

### 1. Settings Page. ###
![Settings Page](https://d3vv6lp55qjaqc.cloudfront.net/items/2T2j1w3P2P2b3L2P2Z3Y/Screen%20Shot%202016-08-15%20at%204.39.19%20PM.png?v=1dbe1998)

### 2. Fancy Style (default) ###
![Fancy Style](https://s3.amazonaws.com/f.cl.ly/items/1K3q1G312k3F3u0r0r21/Screen%20Shot%202016-03-23%20at%204.03.33%20PM.png?v=f44e0d06)

### 3. Slim Style ###
![Slim Style](https://s3.amazonaws.com/f.cl.ly/items/1L06211I3E3v1O2o0y0L/slim.jpg?v=58095cff)

### 4. Bubble Style ###
![Bubble Style](https://s3.amazonaws.com/f.cl.ly/items/1D0m2q270u1719112W3S/Screen%20Shot%202016-03-23%20at%204.02.31%20PM.png?v=84be6c71)

### 5. Email Popup ###
![Email Popup](https://s3.amazonaws.com/f.cl.ly/items/453x450Y2g1a2t2W1Y3d/Screen%20Shot%202016-06-02%20at%208.00.16%20AM.png?v=cb901d0b)

## Customization ##

You can also use the ea_share() function to access any of the internal methods. The most common use will be to get a specific share count.  [See all options here.](https://github.com/jaredatch/EA-Share-Count/blob/master/includes/class-core.php#L157)

* `ea_share()->core->count( get_the_ID(), 'facebook' );` Provides the number of facebook likes/shares/comments
* `ea_share()->core->count( get_the_ID(), 'included_total' );` Provides the total count from all the services specified in settings
* `ea_share()->front->display( $location, $echo );` Display the share buttons, as configured in Settings > Share Count. The $location is an identifying class added to wrapping HTML (useful if you have buttons in multiple locations). $echo is a boolean value indicating whether the buttons should be echoed or returned.

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
* `ea_share_count_api_params` Customize the global API parameters (currently only contains URL). [Example](http://www.billerickson.net/code/ea-share-count-use-production-url/)
* `ea_share_count_admin_services` What services are available on the settings page
* `ea_share_count_query_services` What services are available to be queried for share counts
* `ea_share_count_options` The options used on the settings page
