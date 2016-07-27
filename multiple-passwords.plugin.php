<?php
/*
 Plugin Name: WP Multiple Passwords
 Plugin URI: https://github.com/solepixel/wp-multiple-passwords
 Description: Store Multiple Passwords for Password protected posts and pages. Includes Advanced Custom Fields support, but can work with any setup by using the filter <code>wpmp_extra_passwords</code>.
 Version: 1.0.0
 Author: Brian DiChiara
 Author URI: http://briandichiara.com
 Text Domain: wpmp
 GitHub Plugin URI: https://github.com/solepixel/wp-multiple-passwords
 */

add_action( 'template_redirect', 'wpmp_override_password' );

/**
 * This is the magic function that does most of the work. It doesn't touch the existing password nor does it affect any of the functionality of the WordPress password protected pages/posts. The trick is to modify the post's post_password value to match an accurate additional password at run time. (template_redirect works great)
 *
 * In short, it takes additional password values ($extra_passwords) and checks each one to find a match of what the user input. If one matches, the post object is modified to insert the correct password into the post_password property, treating the post as if the password was successfully submitted. WordPress will handle the rest of the functionality.
 *
 * Extra passwords can be passed by using the filter `wpmp_extra_passwords`, or if ACF is installed, you have everything you need. A new repeater field will be added to posts where there is a post_password. (requires minimum 5.0.0)
 *
 * One thing you should know for this to work is the actual post must have a password value. I can see a situation where you don't want a password stored or have to deal with passwords in 2 places. I would recommend modifying the post on `save_post` and if there are "extra passwords", but no main password, forcing a long cryptic password into the field that will never match, but will enable all the password functionality of a post.
 *
 * @return void
 */
function wpmp_override_password(){
	global $post;
	$global = false;

	if ( empty( $post ) && isset( $GLOBALS['post'] ) ){
		$post = $GLOBALS['post'];
		$global = true;
	}

	if( ! $post )
		return;

	// store the original password.
	$correct_password = is_array( $post ) ? $post['post_password'] : $post->post_password;

	// bail if there is no password set for the post
	if( ! $correct_password )
		return;

	// this part needs ACF
	$extra_passwords = function_exists( 'get_field' ) ? get_field( '_extra_passwords' ) : false;
	$extra_passwords = apply_filters( 'wpmp_extra_passwords', $extra_passwords, $post );
	/*

	# $extra_passwords format needs to be passed as an array in plain text:
	$extra_passwords = array(
		'extra-password-1',
		'extra-password-2'
	);

	# it can also be passed in the ACF format:
	$extra_passwords = array(
		array(
			'password' => 'extra-password-1'
		),
		array(
			'password' => 'extra-password-2'
		)
	);

	 */

	if( ! $extra_passwords || ! is_array( $extra_passwords ) )
		return;

	if ( ! isset( $_COOKIE['wp-postpass_' . COOKIEHASH ] ) )
		return;

	# the next few lines come from /wp-includes/post-template function post_password_required()
	if( ! class_exists( 'PasswordHash' ) )
		require_once ABSPATH . WPINC . '/class-phpass.php';

	$hasher = new PasswordHash( 8, true );
	$hash = wp_unslash( $_COOKIE[ 'wp-postpass_' . COOKIEHASH ] );
	if ( 0 !== strpos( $hash, '$P$B' ) )
		return;

	$passed = false;

	// Check the extra passwords to see if the submitted password matches
	foreach( $extra_passwords as $password ){
		$check_password = $password;
		if( is_array( $check_password ) ) // ACF Support
			$check_password = $check_password['password'];
		if( $hasher->CheckPassword( $check_password, $hash ) ){
			$passed = $check_password;
			break;
		}
	}

	// don't do anything if none of the passwords matched
	if( ! $passed )
		return;

	// temporarily changed the post_password so everything else works as normal
	if( is_array( $post ) ){
		$post['post_password'] = $passed;
	} else {
		$post->post_password = $passed;
	}

	if( $global )
		$GLOBALS['post'] = $post;
}


/** ACF Support */
add_filter( 'acf/location/rule_types', 'wpmp_location_rules_types' );

/**
 * Create custom ACF Rules for Visibility
 * @param  array $choices  ACF array of choices
 * @return array $choices
 */
function wpmp_location_rules_types( $choices ) {

	$choices['Post']['post_visibility'] = __( 'Post Visibility', 'wpmp' );
	$choices['Page']['page_visibility'] = __( 'Page Visibility', 'wpmp' );

	return $choices;

}

add_filter( 'acf/location/rule_values/post_visibility', 'wpmp_location_rules_values_visibility' );
add_filter( 'acf/location/rule_values/page_visibility', 'wpmp_location_rules_values_visibility' );

/**
 * Values for Visibility Rules
 * @param  array $choices ACF array of choices
 * @return array $choices
 */
function wpmp_location_rules_values_visibility( $choices ) {

	$choices[ 'password' ] = __( 'Has Password', 'wpmp' );
	$choices[ 'no-password' ] = __( 'Does Not Have Password', 'wpmp' );

	return $choices;
}

add_filter( 'acf/location/rule_match/post_visibility', 'wpmp_location_rules_match_visibility', 10, 3 );
add_filter( 'acf/location/rule_match/page_visibility', 'wpmp_location_rules_match_visibility', 10, 3 );

/**
 * Rules to match visibility
 * @param  bool $match   Return value
 * @param  array $rule    ACF Rule Array
 * @param  array $options Rule Options
 * @return bool  $match
 */
function wpmp_location_rules_match_visibility( $match, $rule, $options ){
	global $post;

	if( $rule['value'] == 'password' && $post->post_password ){
		if( $rule['operator'] == "==" ){
			$match = true;
		} elseif( $rule['operator'] == "!=" ){
			$match = false;
		}
	} else if( $rule['value'] == 'no-password' && ! $post->post_password ){
		if( $rule['operator'] == "==" ){
			$match = true;
		} elseif( $rule['operator'] == "!=" ){
			$match = false;
		}
	}

	return $match;
}


if( function_exists('acf_add_local_field_group') ):

	acf_add_local_field_group(array (
		'key' => 'group_579786b845c7f',
		'title' => __( 'Additional Passwords', 'wpmp' ),
		'fields' => array (
			array (
				'key' => 'field_579786e0d0eea',
				'label' => __( 'Extra Passwords', 'wpmp' ),
				'name' => '_extra_passwords',
				'type' => 'repeater',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array (
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'collapsed' => 'field_57978711d0eec',
				'min' => '',
				'max' => '',
				'layout' => 'block',
				'button_label' => __( 'Add Password', 'wpmp' ),
				'sub_fields' => array (
					array (
						'key' => 'field_579786fcd0eeb',
						'label' => __( 'Password', 'wpmp' ),
						'name' => 'password',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array (
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
						'readonly' => 0,
						'disabled' => 0,
					),
					array (
						'key' => 'field_57978711d0eec',
						'label' => __( 'Label', 'wpmp' ),
						'name' => 'label',
						'type' => 'text',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array (
							'width' => '',
							'class' => '',
							'id' => '',
						),
						'default_value' => '',
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'maxlength' => '',
						'readonly' => 0,
						'disabled' => 0,
					),
				),
			),
		),
		'location' => array (
			array (
				array (
					'param' => 'post_visibility',
					'operator' => '==',
					'value' => 'password',
				),
			),
		),
		'menu_order' => 0,
		'position' => 'side',
		'style' => 'default',
		'label_placement' => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen' => '',
		'active' => 1,
		'description' => '',
	));

endif;
