# Change Log
All notable changes to this project will be documented in this file, formatted via [this recommendation](http://keepachangelog.com/).

## [1.8.0] = 2017-01-03
### Added
- Added `ea_share_count_display_wrap_format` filter, see #75
- Allow unique styling for each instance, see #78
- Added `ea_share_count_link_url` filter, see #80

### Fixed
- Update handling of Pinterest API response, see #64
- Strip tags from title before using in URL, see #71
- Simplified the image attribute, see #74
- Ensure icon styling only targets icons, see #70
- Removed nonce from email modal, see #69
- Properly receive HTTP requests to prevent errors, see #68
- Fix slim styling display, see #66

## [1.7.1] = 2016-08-24
### Fixed
- Issue with Facebook comment count parsing, see #63

### Added
- Composer support

## [1.7.0] = 2016-08-20
### Changed
- Now receive counts directly from social services (no more SharedCount), see #57
- Updates now automatically pull from GitHub tags, see #53

### Added
- Filter for adding additional styles (ea_share_count_admin_services), see #58
- Filter for adding additional share count services (ea_share_count_query_services), see #57
- Admin notice regarding changing share count source
- Version number is now saved to database for future updates

### Fixed
- PHP Notice if you were getting share counts for a custom URL rather than post ID
- Used correct hooks for Theme Hook Alliance, see #55

## [1.6.0] = 2016-06-23
### Added
- Checkbox in metabox to hide share buttons on this specific post, see #51
- When opening email modal, focus on first input

### Fixed
- Replaced PHP short tags with proper tags, see #50

## [1.5.9] = 2016-05-23
### Fixed
- Issue with automated theme location placement not working on in all cases. See #44

## [1.5.8] = 2016-05-19
### Fixed
- Issue with 'after_content' theme location not working on some themes. See #43

## [1.5.7] = 2016-04-21
### Added
- 'ea_share_count_update_queue' filter

## [1.5.6] = 2016-04-12
### Changed
- Use quotes in JS partial selector, fixing JS issue in WordPress 4.5

## [1.5.5] = 2016-03-22
### Added
- Added support for custom post types
- Added commas to the share counts in metabox
- Added readme file with documentation

### Changed
- Expired share counts now update after page has loaded, rather during page load
- Default style is now 'fancy' rather than 'bubble'

## [1.5.4] = 2016-02-26
### Changed
- Moved the theme location code to a later hook (template_redirect) so filterable in theme.

## [1.5.3] = 2016-01-28
### Added
- Display before/after content now supports all themes. First looks for Genesis and Theme Hook Alliance hooks, then falls back to 'the_content' filter (see #36)

### Changed
- Icons are now included in CSS. Icon classes have changed from font awesome to our custom icons. Ex: fa-facebook is now easc-facebook (see #35)
- Reduced size of print button when using GSS style
- Renamed Genesis Simple Share style to Slim
- Rearranged fields on settings page

### Fixed
- Special characters now work in emails (see #34)

## [1.5.2] - 2015-11-13
### Changed
- Links to author names on plugin page
- Renamed main plugin file to match plugin name

## [1.5.1] - 2015-11-13
### Changed
- Version bump to test plugin updater

## [1.5.0] - 2015-11-13
### Added
- Email sharing feature
- Plugin updater

## [1.4.0] - 2015-10-21
### Added
- Print included service option
- Total Counts included service option
- Settings to toggle count number visibility

### Changed
- Refactored Included Service setting so that the service order is saved
- Seperate the regisration and enqueuing of assets
- Enqueue assets earlier

## [1.3.0] - 2015-10-14
### Added
- Metabox to supported post types for viewing/updating share counts
- Filter for theme locations
- Settings link to plugin page

### Changed
- Refactored plugin structure
- Non-post URLs are now stored in a single option

### Fixed
- Pinterest js library bug
- Select2 v4 issue, reverted to v3.5.x

## [1.2.0] - 2015-10-06
### Added
- Setting for post types supported
- Setting for hiding empty counts
- Uninstall functionality

### Changed
- Setting format for included services
- Setting theme location to support before AND after
- Share bar display markup and CSS
- Reorganized file structure
- Assets enqueue earlier if possible

## [1.1.0] - 2015-09-30
### Added
- Settings page for managing API Details and Theme Display
- A prime_the_pump() function for ensuring there is share data for a certain number of posts
- 'ea_share_count_total' filter for modifying the total count. Useful when wanting to query based on specific metric (eg: facebook likes)

## [1.0.6] - 2015-09-16
### Fixed
- Default Facebook like opens share dialog box
- Single post image filter bug
### Added
- "GSS" style option to mimic the Genesis Simple Share plugin

## [1.0.5] - 2015-09-02
### Fixed
- Incorrect target="_blank" assignment

### Added
- Filter for single post images
- Javascript event for share clicks
- Fancy button styles

## [1.0.4] - 2015-07-24
### Fixed
- Use correct variable name for Pinterest image
### Added
- Support for arbitrary URLs as ID

## [1.0.3] - 2015-07-4
### Fixed
- Update share button js to correctly look for target="_blank"

## [1.0.2] - 2015-05-08
### Added
- Total count parameter, accessible in count() method
- Total count is also stored in 'ea_share_count_total' key for query sorting

## [1.0.1] - 2015-03-17
### Added
- Parameter for specifying the link style
- Styles for bubble count Facebook and Twitter

### Changed
- Removed unnecessary $post_date argument on 'ea_share_count_update_increments' filter, see #6
- Inline documentation

## [1.0.0] - 2015-03-10
- Initial Commit
