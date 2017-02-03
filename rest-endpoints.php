<?php
/**
 * Plugin Name:     REST EndPoints
 * Description:     Activate endpoints for custom post types that were registered by another plugin or theme. Visit the settings menu and click on the "REST EndPoints" menu item.   
 * Author:          Alex Paredes
 * Text Domain:     rest-endpoints
 * Domain Path:     /languages
 * Version:         1.0.0
 *
 * @package         rest-endpoints
 */
 
class Endpoints_Plugin {

	public function __construct() {
		// Hook into the admin menu
		add_action('admin_menu', array( $this, 'settings_page' ));
		// Sections and Fields setup
		add_action('admin_init', array( $this, 'setup_sections' ));
		add_action('admin_init', array( $this, 'setup_fields' ));
	}
	public function settings_page() {
		// Add the menu item and page
		$slug = 'endpoints';
		$callback = array(
			$this,
			'settings_page_content'
		);
		add_submenu_page( 'options-general.php', 'REST EndPoint Settings', 'REST EndPoints', 'manage_options', $slug, $callback );
	}
	public function settings_page_content() {
		// Add content to the settings page
		echo '<div class="wrap">';
		echo '<h2>REST EndPoint Settings</h2>';
		echo '<form class="welcome-panel" method="POST" action="options.php">';
		settings_fields('endpoints');
		do_settings_sections('endpoints');
		submit_button();
		echo '</form>';
		echo '</div>';
	}
	public function setup_sections() {
		// Section setup
		add_settings_section( 'first_section', '', array($this, 'section_callback'), 'endpoints' );
		add_settings_section( 'second_section', 'Test EndPoints', array($this,'section_callback'), 'endpoints' );
		add_settings_section( 'third_section', 'Custom Post Types', array($this,'section_callback'), 'endpoints' );
	}
	public function section_callback( $arguments ) {
		// Content of each section is based on the ID
		switch ( $arguments['id'] ) {
		case 'second_section':
			echo '<p>After adding endpoints for existing custom post types, click on the one you want to test below:</p>';
			// get the value of the setting we've registered with register_setting()
			$setting = get_option('endpoint_text_field');
			if (!empty($setting)) {
				// Remove commas
				$myArray = explode(',', $setting);
				// Remove whitespaces
				$myArray = preg_replace('/\s+/', '', $myArray);
				// display each one in an ordered list
				echo '<ol>';
				foreach ( $myArray as $my_Array ) {
					$url = home_url("/wp-json/wp/v2/" . $my_Array);
					echo '<li><a href=' . esc_url($url) . ' target="_blank">' . $my_Array . '</a></li>';
				}
				echo '</ol>';
			}
			// If no endpoints were found, display a friendly message
			else {
				echo '<p class="notice notice-warning"><i><small>Sorry, you have not added any endpoints yet.</i></small></p>';
			}
			break;

		case 'third_section':
			echo '<p>Custom post types on this website are listed here for your convenience, you can add endpoints for these above:</p>';
			// Get public custom post types on this website and exclude the built in ones.
			$args = array(
				'public' => true,
				'_builtin' => false
			);
			$output = 'names';
			$operator = 'and';
			$post_types = get_post_types($args, $output, $operator);
			// If there is registered custom post types, let's display them in an ordered list
			if (!empty($post_types)) {
				echo '<ol>';
				foreach ( $post_types as $post_type ) {
					echo '<li>' . $post_type . '</li>';
				}
				echo '</ol>';
			}
			// If none were found, display a friendly message
			else {
				echo '<p class="notice notice-warning"><i><small>Sorry, no custom post types were found registered on this website.</i></small></p>';
			}
			break;
		}
	}
	public function setup_fields() {
		$fields = array(
			array(
				'uid' => 'endpoint_text_field',
				'label' => '<p class="notice notice-warning">Add EndPoints:</p>',
				'section' => 'first_section',
				'type' => 'text',
				'placeholder' => 'Endpoint1, Endpoint2, Endpoint3',
				'helper' => '<p><small>Please separate with commas.</small></p>'
			)
		);
		foreach( $fields as $field ) {
			add_settings_field($field['uid'], $field['label'], array(
				$this,
				'field_callback'
			) , 'endpoints', $field['section'], $field);
			register_setting('endpoints', $field['uid']);
		}
	}
	public function field_callback ($arguments ) {
		$value = get_option($arguments['uid']);
		if (!$value) {
			$value = $arguments['default'];
		}
		switch ( $arguments['type'] ) {
		case 'text':
			printf('<p><input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" size="40" value="%4$s" /></p>', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $value);
			break;
		}
		if ($helper = $arguments['helper']) {
			printf('<span class="helper"> %s</span>', $helper);
		}
	}
}
new Endpoints_Plugin();

/**
 * Add REST endpoint support for the registered custom post types the user enters in the settings page
 */
add_action('init', 'custom_post_type_rest_support', 25);
function custom_post_type_rest_support() {
	global $wp_post_types;
	$setting = get_option('endpoint_text_field');
	if (!empty($setting)) {
	// Remove commas
	$post_type_name = explode(',', $setting);
	// Remove whitespaces
	$post_type_name = preg_replace('/\s+/', '', $post_type_name);
	// Set to support rest endpoint
	foreach ( $post_type_name as $key ) {
	$wp_post_types[$key]->show_in_rest = true;
		}
	}
}