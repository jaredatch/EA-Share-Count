# Change Log
All notable changes to this project will be documented in this file, formatted via [this recommendation](http://keepachangelog.com/).

## [1.5.3] = 2016-01-28
### Changed
- Icons are now included in CSS. Icon classes have changed from font awesome to our custom icons. Ex: fa-facebook is now easc-facebook

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