# WP Multiple Passwords
Allow multiple passwords for password protected pages and posts. Includes Advanced Custom Fields (4.x, 5.x) support, but not required.

## Installation
1. Upload wp-multiple-passwords into your WordPress plugins folder.
2. Activate the plugin on the WordPress Admin Plugins page.

## Usage
1. Create a post or page and set visibility to Password Protected
2. Set your password
3. Save/Update your post/page
4. If using ACF (4.x, 5.x) you will see a new meta box titled "Additional Passwords"
5. Enter as many passwords as you'd like to allow access to this page.
6. Update your post/page.

If you are using ACF (Advanced Custom Fields) 4.x or 5.x, there's nothing left to do. If you would like to use your own meta box solution, follow the instructions below to hook into this plugin. Advanced Custom Fields 4.x is currently not supported.

## Custom Meta Box
You may choose not to use ACF to handle your additional password storage and would like to build your own, or maybe you just want to hard code some extra passwords into your theme. The plugin has a filter for passing passwords into a post/page.

### Filter: `wpmp_extra_passwords`
Passwords can be added either by string, or associative array. A string example would look like:
```
add_filter( 'wpmp_extra_passwords', 'mytheme_extra_string_passwords', 10, 2 );

function mytheme_extra_string_passwords( $extra_passwords, $post ){

	$new_passwords = array(
		'new-password-1',
		'new-password-2',
		'new-password-3'
	);

	$extra_passwords = array_merge( $extra_passwords, $new_passwords );

	return $extra_passwords;
}
```
An example using an array would look like:
```
add_filter( 'wpmp_extra_passwords', 'mytheme_extra_array_passwords', 10, 2 );

function mytheme_extra_array_passwords( $extra_passwords, $post ){

	$new_passwords = array(
		array(
			'password' => 'new-password-1',
			'usage' => 'Bob uses this password.'
		),
		array(
			'password' => 'new-password-2',
			'usage' => 'Client uses this one.'
		),
		array(
			'password' => 'new-password-3',
			'usage' => 'For legacy support of the old password.'
		)
	);

	$extra_passwords = array_merge( $extra_passwords, $new_passwords );

	return $extra_passwords;
}
```

## Notes
It is required by this plugin to have an actual password stored in the original WordPress "Password" field in order for it to work. This plugin does not modify any of the default WordPress password protected posts functionality so should be compatible with any specific tweaks made to the functionality or `the_password_form` changes made to Password Protected posts/pages.

## Changelog

#### 1.1.0
* Added support for ACF 4.x (Free, non-PRO version)

#### 1.0.1
* Added some checks for proper syntax
* Trimmed whitespace from passwords
* Skipped empty passwords
* Set default value of $extra_passwords to array()

#### 1.0.0
* Initial Release
