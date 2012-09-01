<?php
/*
Plugin Name: WordChimp
Plugin URI: http://hudsoncs.com/projects/wordchimp/
Description: Allows you to easily select and send a group of posts as a MailChimp campaign
Version: 2.0
Author: David Hudson
Author URI: http://hudsoncs.com/
License: GPL
*/

/*  
	Copyright 2011  David Hudson  (email : david@hudsoncs.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Include some MailChimp API goodness
require_once 'MCAPI.class.php';

// Administrator
// Create navigation buttons
add_action('admin_menu', 'wordchimp_menu');

// Initialize admin page styles
add_action( 'admin_init', 'wordchimp_admin_init' );

// Setup ajax calls
add_action( 'wp_ajax_wordchimp_get_post', 'wordchimp_get_post' );
add_action( 'wp_ajax_wordchimp_campaign_preview', 'wordchimp_campaign_preview' );

function wordchimp_admin_init() {
	wp_register_style( 'wordchimpStyle', '/wp-content/plugins/wordchimp/style.css' );
	wp_register_script( 'wordchimpjQuery', 'https://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js' );
	wp_register_script( 'wordchimpjQueryUI', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.14/jquery-ui.min.js' );
	wp_register_script( 'wordchimpScript', '/wp-content/plugins/wordchimp/js/script.js' );
}

function register_wordchimp_settings() {
	register_setting( 'wordchimp_options_group', 'mailchimp_api_key' );
	register_setting( 'wordchimp_options_group', 'wordchimp_campaign_from_name' );
	register_setting( 'wordchimp_options_group', 'wordchimp_campaign_from_email' );
	register_setting( 'wordchimp_options_group', 'wordchimp_logo_url' );
	register_setting( 'wordchimp_options_group', 'wordchimp_strip_images' );
	register_setting( 'wordchimp_options_group', 'wordchimp_show_author' );
	register_setting( 'wordchimp_options_group', 'wordchimp_show_timestamp' );
	register_setting( 'wordchimp_options_group', 'wordchimp_timestamp_format' );
	register_setting( 'wordchimp_options_group', 'wordchimp_template' );
	register_setting( 'wordchimp_options_group', 'wordchimp_use_excerpt' );
	register_setting( 'wordchimp_options_group', 'wordchimp_page_capability' );
	register_setting( 'wordchimp_options_group', 'wordchimp_campaigns_capability' );
	register_setting( 'wordchimp_options_group', 'wordchimp_settings_capability' );
}

function wordchimp_admin_styles() {
   wp_enqueue_style( 'wordchimpStyle' );
}

function wordchimp_admin_scripts() {
   wp_enqueue_script( 'wordchimpjQuery' );
   wp_enqueue_script( 'wordchimpjQueryUI' );
   wp_enqueue_script( 'wordchimpScript' );
}

function wordchimp_menu() {
	$page_capability = get_option( 'wordchimp_page_capability' ) == '' ? 'manage_options' : get_option( 'wordchimp_page_capability' );
	$campaigns_capability = get_option( 'wordchimp_campaigns_capability' ) == '' ? 'manage_options' : get_option( 'wordchimp_campaigns_capability' );
	$settings_capability = get_option( 'wordchimp_settings_capability' ) == '' ? 'manage_options' : get_option( 'wordchimp_settings_capability' );

	$page = add_menu_page('WordChimp', 'WordChimp', $page_capability, 'wordchimp', 'wordchimp_dashboard');
	$campaigns_page = add_submenu_page('wordchimp', 'Campaign Stats', 'Campaign Stats', $campaigns_capability, 'wordchimp-campaigns', 'wordchimp_campaigns_page');
	$settings_page = add_submenu_page('options-general.php', 'WordChimp Settings', 'WordChimp', $settings_capability, __FILE__, 'wordchimp_settings_page');
	
	add_action( 'admin_print_styles-' . $page, 'wordchimp_admin_styles' );
	add_action( 'admin_print_scripts-' . $page, 'wordchimp_admin_scripts' );
	
	add_action( 'admin_print_styles-' . $campaigns_page, 'wordchimp_admin_styles' );
	add_action( 'admin_print_scripts-' . $campaigns_page, 'wordchimp_admin_scripts');
	
	add_action( 'admin_print_styles-' . $settings_page, 'wordchimp_admin_styles' );
	add_action( 'admin_print_scripts-' . $settings_page, 'wordchimp_admin_scripts' );
	
	add_action( 'admin_init', 'register_wordchimp_settings' );
}

add_filter('plugin_action_links', 'wordchimp_plugin_action_links', 10, 2);

function wordchimp_plugin_action_links($links, $file) {
    static $this_plugin;

    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }

    if ($file == $this_plugin) {
        $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wordchimp/wordchimp.php">Settings</a>';
        array_unshift($links, $settings_link);
    }

    return $links;
}

function wordchimp_campaigns_page() {
	global $random;
	global $wpdb;
	
	echo <<<EOF
	<div class='wrap wordchimp'>
		<img src='http://hudsoncs.com/images/wordchimp_logo.png' id='wordchimp_logo' />
		<span id='wordchimp_credits'>Created by <a href='mailto:david@hudsoncs.com'>David Hudson</a> at <a href='http://hudsoncs.com/' target='_blank'>HudsonCS</a></span>
		<div id='icon-themes' class='icon32'></div>
		<h2 class="nav-tab-wrapper">
			<a href="admin.php?page=wordchimp" class="nav-tab">Dashboard</a>
			<a href="admin.php?page=wordchimp-campaigns" class="nav-tab nav-tab-active">Stats</a>
			<a href="options-general.php?page=wordchimp/wordchimp.php" class="nav-tab">Settings</a>
		</h2>
EOF;

	echo "<h1>Campaigns</h1>";
	echo "<p class='wordchimp_notice'>Shows information and statistics for all sent campaigns (generated through WordChimp or otherwise)</p>";
	if (get_option( 'mailchimp_api_key' ) == "") {
		echo "<p class='wordchimp_error'>You must enter your MailChimp API key in the settings page before you can continue. " . $random['whoops'][rand(0, count($random['whoops'])-1)] . "</p>";
	} else {
		$api = new MCAPI_WordChimp(get_option( 'mailchimp_api_key' ));
		$campaigns = $api->campaigns();

		if ($api->errorCode){
			echo "<p class='wordchimp_error'>Sorry, we were unable to get a list of your campaigns. " . $random['whoops'][rand(0, count($random['whoops'])-1)] . " {$api->errorCode} {$api->errorMessage}</p>";
		} elseif (sizeof($campaigns['data']) <= 0) {
			echo "<p class='wordchimp_error'>Sorry, there are no campaigns available that have been sent. Please send a campaign and come on back to take stats for a spin!</p>";
		} else {
			echo <<<EOF
				<table class='wordchimp_campaigns_analytics_table'>
					<tbody>
EOF;
			foreach ($campaigns['data'] as $campaign) {
				if ($campaign['status'] == 'sent') {
					$stats = $api->campaignStats($campaign['id']);

					if ($api->errorCode) {
						echo "<p class='wordchimp_error'>Sorry, we were unable to get analytics for a campaign. " . $random['whoops'][rand(0, count($random['whoops'])-1)] . " {$api->errorCode} {$api->errorMessage}</p>";
					} else {
						$wordchimp_timestamp_format = get_option( 'wordchimp_timestamp_format' ) == '' ? 'm/d/Y g:ia' : get_option( 'wordchimp_timestamp_format' );
						
						$campaign['send_time'] = date($wordchimp_timestamp_format, strtotime($campaign['send_time']));
						$stats['last_open'] = $stats['last_open'] != '' ? date($wordchimp_timestamp_format, strtotime($stats['last_open'])) : 'NEVA';
						$stats['last_click'] = $stats['last_click'] != '' ? date($wordchimp_timestamp_format, strtotime($stats['last_click'])) : 'NEVA';

						echo "
							<tr class='wordchimp_campaign_separate'>
								<td colspan='6'></td>
							</tr>
							<tr>
								<td colspan='6' class='wordchimp_campaign_title'><a href='" . get_bloginfo('wpurl') . "/wp-admin/admin-ajax.php?action=wordchimp_campaign_preview&cid={$campaign['id']}' target='_blank'>{$campaign['title']}</a></td>
							</tr>
							<tr>
								<td><span class='wordchimp_campaign_data_title'>Sent on:</span><br /><span class='wordchimp_campaign_data_value'>{$campaign['send_time']}</span></td>
								<td><span class='wordchimp_campaign_data_title'>Emails sent:</span><br /><span class='wordchimp_campaign_data_value'>{$campaign['emails_sent']}</span></td>
								<td><span class='wordchimp_campaign_data_title'>Hard bounces:</span><br /><span class='wordchimp_campaign_data_value'>{$stats['hard_bounces']}</span></td>
								<td><span class='wordchimp_campaign_data_title'>Soft bounces:</span><br /><span class='wordchimp_campaign_data_value'>{$stats['soft_bounces']}</span></td>
								<td><span class='wordchimp_campaign_data_title'>Unsubscribes:</span><br /><span class='wordchimp_campaign_data_value'>{$stats['unsubscribes']}</span></td>
								<td><span class='wordchimp_campaign_data_title'>Forwards:</span><br /><span class='wordchimp_campaign_data_value'>{$stats['forwards']}</span></td>
							</tr>
							<tr>
								<td><span class='wordchimp_campaign_data_title'>Forwards opens:</span><br /><span class='wordchimp_campaign_data_value'>{$stats['forwards_opens']}</span></td>
								<td><span class='wordchimp_campaign_data_title'>Unique Opens:</span><br /><span class='wordchimp_campaign_data_value'>{$stats['unique_opens']}</span></td>
								<td><span class='wordchimp_campaign_data_title'>Last open:</span><br /><span class='wordchimp_campaign_data_value'>{$stats['last_open']}</span></td>
								<td><span class='wordchimp_campaign_data_title'>Clicks:</span><br /><span class='wordchimp_campaign_data_value'>{$stats['clicks']}</span></td>
								<td><span class='wordchimp_campaign_data_title'>Users who clicked:</span><br /><span class='wordchimp_campaign_data_value'>{$stats['users_who_clicked']}</span></td>
								<td><span class='wordchimp_campaign_data_title'>Last click:</span><br /><span class='wordchimp_campaign_data_value'>{$stats['last_click']}</span></td>
							</tr>";
					}
				}
			}
			echo "</tbody></table>";
		}
	}
	echo "</div>";
}

function wordchimp_settings_page() {
	global $random;
	
	echo <<<EOF
	<div class='wrap wordchimp'>
		<img src='http://hudsoncs.com/images/wordchimp_logo.png' id='wordchimp_logo' />
		<span id='wordchimp_credits'>Created by <a href='mailto:david@hudsoncs.com'>David Hudson</a> at <a href='http://hudsoncs.com/' target='_blank'>HudsonCS</a></span>
		<div id='icon-themes' class='icon32'></div>
		<h2 class="nav-tab-wrapper">
			<a href="admin.php?page=wordchimp" class="nav-tab">Dashboard</a>
			<a href="admin.php?page=wordchimp-campaigns" class="nav-tab">Stats</a>
			<a href="options-general.php?page=wordchimp/wordchimp.php" class="nav-tab nav-tab-active">Settings</a>
		</h2>
EOF;
	echo "<form method='post' action='options.php'> ";
	settings_fields( 'wordchimp_options_group' );
	do_settings_fields( 'wordchimp_settings_page', 'wordchimp_options_group' );
	
	$mailchimp_api_key = get_option( 'mailchimp_api_key' );
	$wordchimp_campaign_from_name = get_option( 'wordchimp_campaign_from_name' );
	$wordchimp_campaign_from_email = get_option( 'wordchimp_campaign_from_email' );
	$wordchimp_strip_images_checked = get_option( 'wordchimp_strip_images' ) == true ? 'CHECKED' : '';
	$wordchimp_show_author_checked = get_option( 'wordchimp_show_author' ) == true ? 'CHECKED' : '';
	$wordchimp_show_timestamp_checked = get_option( 'wordchimp_show_timestamp' ) == true ? 'CHECKED' : '';
	$wordchimp_timestamp_format = get_option( 'wordchimp_timestamp_format' ) == '' ? 'm/d/Y g:ia' : get_option( 'wordchimp_timestamp_format' );
	$wordchimp_use_excerpt_checked = get_option( 'wordchimp_use_excerpt' ) == true ? 'CHECKED' : '';
	$wordchimp_page_capability = get_option( 'wordchimp_page_capability' ) == '' ? 'manage_options' : get_option( 'wordchimp_page_capability' );
	$wordchimp_campaigns_capability = get_option( 'wordchimp_campaigns_capability' ) == '' ? 'manage_options' : get_option( 'wordchimp_campaigns_capability' );
	$wordchimp_settings_capability = get_option( 'wordchimp_settings_capability' ) == '' ? 'manage_options' : get_option( 'wordchimp_settings_capability' );
	
	if ($_GET['settings-updated'] == 'true') {
		echo "<p class='wordchimp_success'>Great success! Your settings have been updated. " . $random['compliment'][rand(0, count($random['compliment'])-1)] . "</p>";
	}
	
	echo <<<EOF
		<h1>Settings</h1>
		<h3>APIs</h3>
		<label><strong>MailChimp API Key</strong><br /><input type='text' name='mailchimp_api_key' value='{$mailchimp_api_key}' /></label><br/><br/>
		<h3>Security</h3>
		<small>Security is based on WordPress 'capabilities'. The 'manage_option' capability is the default and if you don't know what this is or how this works, please do not change it! For more information, visit <a href='http://codex.wordpress.org/Roles_and_Capabilities' target='_blank'>http://codex.wordpress.org/Roles_and_Capabilities</a>.</small><br />
		<label><strong>Create Campaign Through WordChimp:</strong><br />
			<select name='wordchimp_page_capability'>
				<option>{$wordchimp_page_capability}</option>
				<option disabled>-----</option>
				<option disabled value=''>Super Admin</option>
				<option>manage_network</option>
				<option>manage_sites</option>
				<option>manage_network_users</option>
				<option>manage_network_themes</option>
				<option>manage_network_options</option>
				<option>unfiltered_html when using Multisite</option>
				<option disabled value=''>Administrator</option>
				<option>activate_plugins</option>
				<option>add_users</option>
				<option>create_users</option>
				<option>delete_others_pages</option>
				<option>delete_others_posts</option>
				<option>delete_pages</option>
				<option>delete_plugins</option>
				<option>delete_posts</option>
				<option>delete_private_pages</option>
				<option>delete_private_posts</option>
				<option>delete_published_pages</option>
				<option>delete_published_posts</option>
				<option>delete_themes</option>
				<option>delete_users</option>
				<option>edit_dashboard</option>
				<option>edit_files</option>
				<option>edit_others_pages</option>
				<option>edit_others_posts</option>
				<option>edit_pages</option>
				<option>edit_plugins</option>
				<option>edit_posts</option>
				<option>edit_private_pages</option>
				<option>edit_private_posts</option>
				<option>edit_published_pages</option>
				<option>edit_published_posts</option>
				<option>edit_theme_options</option>
				<option>edit_themes</option>
				<option>edit_users</option>
				<option>export</option>
				<option>import</option>
				<option>install_plugins</option>
				<option>install_themes</option>
				<option>list_users</option>
				<option>manage_categories</option>
				<option>manage_links</option>
				<option>manage_options</option>
				<option>moderate_comments</option>
				<option>promote_users</option>
				<option>publish_pages</option>
				<option>publish_posts</option>
				<option>read_private_pages</option>
				<option>read_private_posts</option>
				<option>read</option>
				<option>remove_users</option>
				<option>switch_themes</option>
				<option>unfiltered_html</option>
				<option>unfiltered_upload</option>
				<option>update_core</option>
				<option>update_plugins</option>
				<option>update_themes</option>
				<option>upload_files</option>
				<option disabled value=''>Editor</option>
				<option>delete_others_pages</option>
				<option>delete_others_posts</option>
				<option>delete_pages</option>
				<option>delete_posts</option>
				<option>delete_private_pages</option>
				<option>delete_private_posts</option>
				<option>delete_published_pages</option>
				<option>delete_published_posts</option>
				<option>edit_others_pages</option>
				<option>edit_others_posts</option>
				<option>edit_pages</option>
				<option>edit_posts</option>
				<option>edit_private_pages</option>
				<option>edit_private_posts</option>
				<option>edit_published_pages</option>
				<option>edit_published_posts</option>
				<option>manage_categories</option>
				<option>manage_links</option>
				<option>moderate_comments</option>
				<option>publish_pages</option>
				<option>publish_posts</option>
				<option>read</option>
				<option>read_private_pages</option>
				<option>read_private_posts</option>
				<option>unfiltered_html</option>
				<option>upload_files</option>
				<option disabled value=''>Author</option>
				<option>delete_posts</option>
				<option>delete_published_posts</option>
				<option>edit_posts</option>
				<option>edit_published_posts</option>
				<option>publish_posts</option>
				<option>read</option>
				<option>upload_files</option>
				<option disabled value=''>Contributor</option>
				<option>delete_posts</option>
				<option>edit_posts</option>
				<option>read</option>
				<option disabled value=''>Subscriber</option>
				<option>read</option>
			</select>
		</label><br /><br />
		<label><strong>Access Campaign Stats Through WordChimp:</strong><br />
			<select name='wordchimp_campaigns_capability'>
				<option>{$wordchimp_campaigns_capability}</option>
				<option disabled>-----</option>
				<option disabled value=''>Super Admin</option>
				<option>manage_network</option>
				<option>manage_sites</option>
				<option>manage_network_users</option>
				<option>manage_network_themes</option>
				<option>manage_network_options</option>
				<option>unfiltered_html when using Multisite</option>
				<option disabled value=''>Administrator</option>
				<option>activate_plugins</option>
				<option>add_users</option>
				<option>create_users</option>
				<option>delete_others_pages</option>
				<option>delete_others_posts</option>
				<option>delete_pages</option>
				<option>delete_plugins</option>
				<option>delete_posts</option>
				<option>delete_private_pages</option>
				<option>delete_private_posts</option>
				<option>delete_published_pages</option>
				<option>delete_published_posts</option>
				<option>delete_themes</option>
				<option>delete_users</option>
				<option>edit_dashboard</option>
				<option>edit_files</option>
				<option>edit_others_pages</option>
				<option>edit_others_posts</option>
				<option>edit_pages</option>
				<option>edit_plugins</option>
				<option>edit_posts</option>
				<option>edit_private_pages</option>
				<option>edit_private_posts</option>
				<option>edit_published_pages</option>
				<option>edit_published_posts</option>
				<option>edit_theme_options</option>
				<option>edit_themes</option>
				<option>edit_users</option>
				<option>export</option>
				<option>import</option>
				<option>install_plugins</option>
				<option>install_themes</option>
				<option>list_users</option>
				<option>manage_categories</option>
				<option>manage_links</option>
				<option>manage_options</option>
				<option>moderate_comments</option>
				<option>promote_users</option>
				<option>publish_pages</option>
				<option>publish_posts</option>
				<option>read_private_pages</option>
				<option>read_private_posts</option>
				<option>read</option>
				<option>remove_users</option>
				<option>switch_themes</option>
				<option>unfiltered_html</option>
				<option>unfiltered_upload</option>
				<option>update_core</option>
				<option>update_plugins</option>
				<option>update_themes</option>
				<option>upload_files</option>
				<option disabled value=''>Editor</option>
				<option>delete_others_pages</option>
				<option>delete_others_posts</option>
				<option>delete_pages</option>
				<option>delete_posts</option>
				<option>delete_private_pages</option>
				<option>delete_private_posts</option>
				<option>delete_published_pages</option>
				<option>delete_published_posts</option>
				<option>edit_others_pages</option>
				<option>edit_others_posts</option>
				<option>edit_pages</option>
				<option>edit_posts</option>
				<option>edit_private_pages</option>
				<option>edit_private_posts</option>
				<option>edit_published_pages</option>
				<option>edit_published_posts</option>
				<option>manage_categories</option>
				<option>manage_links</option>
				<option>moderate_comments</option>
				<option>publish_pages</option>
				<option>publish_posts</option>
				<option>read</option>
				<option>read_private_pages</option>
				<option>read_private_posts</option>
				<option>unfiltered_html</option>
				<option>upload_files</option>
				<option disabled value=''>Author</option>
				<option>delete_posts</option>
				<option>delete_published_posts</option>
				<option>edit_posts</option>
				<option>edit_published_posts</option>
				<option>publish_posts</option>
				<option>read</option>
				<option>upload_files</option>
				<option disabled value=''>Contributor</option>
				<option>delete_posts</option>
				<option>edit_posts</option>
				<option>read</option>
				<option disabled value=''>Subscriber</option>
				<option>read</option>
			</select>
		</label><br /><br />
		<label><strong>Access to WordChimp Settings:</strong><br />
			<select name='wordchimp_settings_capability'>
				<option>{$wordchimp_settings_capability}</option>
				<option disabled>-----</option>
				<option disabled value=''>Super Admin</option>
				<option>manage_network</option>
				<option>manage_sites</option>
				<option>manage_network_users</option>
				<option>manage_network_themes</option>
				<option>manage_network_options</option>
				<option>unfiltered_html when using Multisite</option>
				<option disabled value=''>Administrator</option>
				<option>activate_plugins</option>
				<option>add_users</option>
				<option>create_users</option>
				<option>delete_others_pages</option>
				<option>delete_others_posts</option>
				<option>delete_pages</option>
				<option>delete_plugins</option>
				<option>delete_posts</option>
				<option>delete_private_pages</option>
				<option>delete_private_posts</option>
				<option>delete_published_pages</option>
				<option>delete_published_posts</option>
				<option>delete_themes</option>
				<option>delete_users</option>
				<option>edit_dashboard</option>
				<option>edit_files</option>
				<option>edit_others_pages</option>
				<option>edit_others_posts</option>
				<option>edit_pages</option>
				<option>edit_plugins</option>
				<option>edit_posts</option>
				<option>edit_private_pages</option>
				<option>edit_private_posts</option>
				<option>edit_published_pages</option>
				<option>edit_published_posts</option>
				<option>edit_theme_options</option>
				<option>edit_themes</option>
				<option>edit_users</option>
				<option>export</option>
				<option>import</option>
				<option>install_plugins</option>
				<option>install_themes</option>
				<option>list_users</option>
				<option>manage_categories</option>
				<option>manage_links</option>
				<option>manage_options</option>
				<option>moderate_comments</option>
				<option>promote_users</option>
				<option>publish_pages</option>
				<option>publish_posts</option>
				<option>read_private_pages</option>
				<option>read_private_posts</option>
				<option>read</option>
				<option>remove_users</option>
				<option>switch_themes</option>
				<option>unfiltered_html</option>
				<option>unfiltered_upload</option>
				<option>update_core</option>
				<option>update_plugins</option>
				<option>update_themes</option>
				<option>upload_files</option>
				<option disabled value=''>Editor</option>
				<option>delete_others_pages</option>
				<option>delete_others_posts</option>
				<option>delete_pages</option>
				<option>delete_posts</option>
				<option>delete_private_pages</option>
				<option>delete_private_posts</option>
				<option>delete_published_pages</option>
				<option>delete_published_posts</option>
				<option>edit_others_pages</option>
				<option>edit_others_posts</option>
				<option>edit_pages</option>
				<option>edit_posts</option>
				<option>edit_private_pages</option>
				<option>edit_private_posts</option>
				<option>edit_published_pages</option>
				<option>edit_published_posts</option>
				<option>manage_categories</option>
				<option>manage_links</option>
				<option>moderate_comments</option>
				<option>publish_pages</option>
				<option>publish_posts</option>
				<option>read</option>
				<option>read_private_pages</option>
				<option>read_private_posts</option>
				<option>unfiltered_html</option>
				<option>upload_files</option>
				<option disabled value=''>Author</option>
				<option>delete_posts</option>
				<option>delete_published_posts</option>
				<option>edit_posts</option>
				<option>edit_published_posts</option>
				<option>publish_posts</option>
				<option>read</option>
				<option>upload_files</option>
				<option disabled value=''>Contributor</option>
				<option>delete_posts</option>
				<option>edit_posts</option>
				<option>read</option>
				<option disabled value=''>Subscriber</option>
				<option>read</option>
			</select>
		</label><br /><br />
		<h3>Options</h3>
		<label><strong>Default From Name:</strong><br /><input type='text' name='wordchimp_campaign_from_name' value='{$wordchimp_campaign_from_name}' /></label><br /><br />
		<label><strong>Default From E-mail:</strong><br /><input type='text' name='wordchimp_campaign_from_email' value='{$wordchimp_campaign_from_email}' /></label><br /><br />
		<label><input type='checkbox' name='wordchimp_use_excerpt' value='true' {$wordchimp_use_excerpt_checked} /> Use excerpts instead of the full post content for newsletter.</label><br /><br />
		<label><input type='checkbox' name='wordchimp_strip_images' value='true' {$wordchimp_strip_images_checked} /> Strip images from posts (Fixes some compatibility issues with posts that have very large images)</label><br /><br />
		<label><input type='checkbox' name='wordchimp_show_author' value='true' {$wordchimp_show_author_checked} /> Show author inside of post</label><br /><br />
		<label><input type='checkbox' name='wordchimp_show_timestamp' value='true' {$wordchimp_show_timestamp_checked} /> Show post created date/time inside of post</label><br /><br />
		<label><strong>Date/Time Format:</strong><br /><input type='text' name='wordchimp_timestamp_format' value='{$wordchimp_timestamp_format}' /></label><br /><br />
		<p class="submit">
			<input type="submit" class="button-primary" value="Save Changes" />
		</p>
	</form>
EOF;

	echo "</div>";
}

function wordchimp_dashboard() {
	global $random;
	global $wpdb;
	
	echo <<<EOF
	<div class='wrap wordchimp'>
		<img src='http://hudsoncs.com/images/wordchimp_logo.png' id='wordchimp_logo' />
		<span id='wordchimp_credits'>Created by <a href='mailto:david@hudsoncs.com'>David Hudson</a> at <a href='http://hudsoncs.com/' target='_blank'>HudsonCS</a></span>
		<div id='icon-themes' class='icon32'></div>
		<h2 class="nav-tab-wrapper">
			<a href="admin.php?page=wordchimp" class="nav-tab nav-tab-active">Dashboard</a>
			<a href="admin.php?page=wordchimp-campaigns" class="nav-tab">Stats</a>
			<a href="options-general.php?page=wordchimp/wordchimp.php" class="nav-tab">Settings</a>
		</h2>
EOF;

	switch ($_REQUEST['wp_cmd']) {
		default:
		case "step1":
			if (get_option( 'mailchimp_api_key' ) == "") {
				echo "<p class='wordchimp_error'>You must enter your MailChimp API key in the settings page before you can continue. " . $random['whoops'][rand(0, count($random['whoops'])-1)] . "</p>";
			} else {
				$api = new MCAPI_WordChimp(get_option( 'mailchimp_api_key') );
				$types = $api->templates(array('user' => true, 'gallery' => true));
				
				echo "<h1>Step 1: Select Template</h1><span class='wordchimp_notice'>Templates provided by you (shown below as user) and by the MailChimp gallery. Not all templates shown are 100% compatible with WordChimp. Try the 'Simple Newsletter' template under the gallery section if you're unsure.</span>";
				//print_r($templates);
				foreach ($types as $type => $templates) {
					echo "<div style='clear:both;overflow:auto;width:100%;'><h4>{$type}</h4>";
					foreach ($templates as $template) {
						echo "<div class='template_select_box' template_id='{$template['id']}'><span>{$template['name']}</span><img src='{$template['preview_image']}' /></div>";
					}
					echo "</div>";
				}
				
				echo <<<EOF
				<form method='post' id='step1_form'>
					<input type='hidden' name='wp_cmd' value='step2' />
					<input type='hidden' name='template_id' />
				</form>
EOF;
			}
		break;
		
		case "step2":
			if (get_option( 'mailchimp_api_key' ) == "") {
				echo "<p class='wordchimp_error'>You must enter your MailChimp API key in the settings page before you can continue. " . $random['whoops'][rand(0, count($random['whoops'])-1)] . "</p>";
			} else {
				echo "
					<h1>Step 2: Select Posts</h1>
					<span class='wordchimp_notice'>
						<strong>Instructions:</strong>
						<ol style='width:50%'>
							<li>Under 'Select Posts', choose a template section you would like to insert a post into. If you're unsure, most templates have section names like 'main' where the main content would go. Feel free to experiment!</li>
							<li>Click the post you would like to insert.</li>
							<li>On the right, you should notice your post inserted into the desired section.</li>
							<li>When you're done adding all of your posts. click 'Next' at the very bottom.</li>
						</ol>
					</span>";

				// Pull selected template info
				$api = new MCAPI_WordChimp(get_option( 'mailchimp_api_key') );
				$template_info = $api->templateInfo($_POST['template_id']);
				
				// Get last 40 posts
				$sql = "SELECT id, post_author, post_date, post_content, post_title, post_excerpt, post_name FROM {$wpdb->prefix}posts WHERE post_type = 'post' AND post_status = 'publish' ORDER BY post_date DESC LIMIT 40";
				$posts = $wpdb->get_results($sql, ARRAY_A);
				
				echo "
				<div style='width:20%;float:left;' id='posts_listing'>
					<h3>Select Posts</h3>
					<select id='wordchimp_section_select'>
						<option value='' disabled>Select Template Section</option>
					";
				
				foreach ($template_info['sections'] as $section) {
					echo "<option>{$section}</option>";
				}
				
				echo "
					</select>
					<ul style='margin:15px 0px;'>
						";
						
				foreach ($posts as $post) {
					echo "<li post_id='{$post['id']}'>{$post['post_title']}</li>";
				}
				
				echo <<<EOF
					</ul>
				</div>
				<div style='width:75%;float:right;' id='posts_used'>
					<h3>Template Sections</h3>
					<form method='post' id='step2_form'>
						<input type='hidden' name='wp_cmd' value='step3' />
						<input type='hidden' name='template_id' value='{$_POST['template_id']}' />
EOF;
	
				// Setup sections
				foreach ($template_info['default_content'] as $section => $content) {
					echo "<h3>{$section}</h3><textarea name='html_{$section}' id='html_{$section}'>" . str_replace("\t", '', str_replace("   ",'',htmlspecialchars($content))) . "</textarea>"; // Show text area. Remove all the extra tabs and spacing that usually shows up in these templates.
				}

				echo <<<EOF
					</form>
				</div>
				<div style='clear:both;'>
					<input type="submit" class="button-primary" value="Next" id="step2_submit" />
				</div>
EOF;
			}
		break;
		
		case "step3":
			if (get_option( 'mailchimp_api_key' ) == "") {
				echo "<p class='wordchimp_error'>You must enter your MailChimp API key in the settings page before you can continue. " . $random['whoops'][rand(0, count($random['whoops'])-1)] . "</p>";
			} else {
				$api = new MCAPI_WordChimp(get_option( 'mailchimp_api_key') );

				$retval = $api->lists();

				if ($api->errorCode){
					echo "<p class='wordchimp_error'>Something went wrong when trying to get your MailChimp e-mail lists.  " . $random['whoops'][rand(0, count($random['whoops'])-1)] . " {$api->errorCode} {$api->errorMessage}</p>";
				} else {
					echo "<h1>Step 3: Select List</h1><span class='wordchimp_notice'>Please select the MailChimp list you would like to send to.</span><br /><br />";
					echo "
						<form method='post'>
							<input type='hidden' name='wp_cmd' value='step4' />
							<input type='hidden' name='template_id' value='{$_POST['template_id']}' />
							";
					
					// Add the previously sent posts
					foreach ($_POST as $key => $value) {
						if ($key != 'wp_cmd' && $key != 'template_id') {
							echo "<input type='hidden' name='{$key}' value=\"" . htmlspecialchars(stripslashes($value)) . "\" />";
						}
					}

					// Show MailChimp lists for selection
					foreach ($retval['data'] as $count => $list) {
						if ($count == 0) {
							echo "<label><input type='radio' name='mailchimp_list_id' value='{$list['id']}' CHECKED /> {$list['name']} ({$list['stats']['member_count']} Subscribers)</label><br />";
						} else {
							echo "<label><input type='radio' name='mailchimp_list_id' value='{$list['id']}' /> {$list['name']} ({$list['stats']['member_count']} Subscribers)</label><br />";
						}
					}
					echo <<<EOF
					<p class="submit">
						<input type="submit" class="button-primary" value="Next" />
					</p>
				</form>
EOF;
				}
			}
		break;
		
		case "step4":
			if (get_option( 'mailchimp_api_key' ) == "") {
				echo "<p class='wordchimp_error'>You must enter your MailChimp API key in the settings page before you can continue. " . $random['whoops'][rand(0, count($random['whoops'])-1)] . "</p>";
			} else {
				echo "
					<h1>Step 4: Complete Campaign Information</h1>
					<form method='post'>
						<input type='hidden' name='wp_cmd' value='step5' />
						<input type='hidden' name='template_id' value='{$_POST['template_id']}' />
						<input type='hidden' name='mailchimp_list_id' value='{$_POST['mailchimp_list_id']}' />
						";
						
				// Add modified template sections
				foreach ($_POST as $key => $value) {
					if ($key != 'wp_cmd' && $key != 'template_id' && $key != 'mailchimp_list_id') {
						echo "<input type='hidden' name='{$key}' value=\"" . htmlspecialchars(stripslashes($value)) . "\" />";
					}
				}
				
				echo "<span class='wordchimp_notice'>Please enter the campaign information.</span><br /><br />";
				
				// Setup campaign options
				$wordchimp_campaign_from_email = htmlspecialchars(get_option( 'wordchimp_campaign_from_email' ));
				$wordchimp_campaign_from_name = htmlspecialchars(get_option( 'wordchimp_campaign_from_name' ));
				echo "
				<table class='wordchimp_form_table'>
					<tr valign='top'>
						<th scope='row'>Title</th>
						<td><input type='text' name='wordchimp_campaign_title' /></td>
					</tr>
					<tr valign='top'>
						<th scope='row'>Subject</th>
						<td><input type='text' name='wordchimp_campaign_subject' /></td>
					</tr>
					<tr valign='top'>
						<th scope='row'>From Email</th>
						<td><input type='text' name='wordchimp_campaign_from_email' value='{$wordchimp_campaign_from_email}' /></td>
					</tr>
					<tr valign='top'>
						<th scope='row'>From Name</th>
						<td><input type='text' name='wordchimp_campaign_from_name' value='{$wordchimp_campaign_from_name}' /></td>
					</tr>
				</table>
				<h3>Campaign Tracking</h3>
				<label><input type='checkbox' name='wordchimp_campaign_track_opens' value='true' checked /> Track number of times email was opened</label><br />
				<label><input type='checkbox' name='wordchimp_campaign_track_html_clicks' value='true' checked /> Track number of times a user clicked an HTML link</label><br />
				<label><input type='checkbox' name='wordchimp_campaign_track_text_clicks' value='true' checked /> Track number of times a user clicked a text link</label>";
				
				echo <<<EOF
					<p class="submit">
						<input type="submit" class="button-primary" value="Next" />
					</p>
				</form>
EOF;
			}
		break;
		
		case "step5":
			if (get_option( 'mailchimp_api_key' ) == "") {
				echo "<p class='wordchimp_error'>You must enter your MailChimp API key in the settings page before you can continue. " . $random['whoops'][rand(0, count($random['whoops'])-1)] . "</p>";
			} else {
				// Build the campaign
				$api = new MCAPI_WordChimp(get_option( 'mailchimp_api_key' ));

				$type = 'regular';

				$opts['template_id'] 	= $_POST['template_id'];
				$opts['list_id'] 			= $_POST['mailchimp_list_id'];
				$opts['subject'] 			= $_POST['wordchimp_campaign_subject'];
				$opts['from_email'] 	= $_POST['wordchimp_campaign_from_email'];
				$opts['from_name'] 	= $_POST['wordchimp_campaign_from_name'];
				$opts['tracking'] 		= array('opens' => $_POST['wordchimp_campaign_track_opens'] == 'true' ? true : false, 'html_clicks' => $_POST['wordchimp_campaign_track_html_clicks'] == 'true' ? true : false, 'text_clicks' => $_POST['wordchimp_campaign_track_text_clicks'] == 'true' ? true : false);
				$opts['authenticate'] 	= true;
				$opts['title'] 				= $_POST['wordchimp_campaign_title'];
				
				foreach ($_POST as $key => $value) {
					if ($key != 'wp_cmd' && $key != 'template_id' && $key != 'mailchimp_list_id') {
						$content[$key] = stripslashes($value);
					}
				}

				$campaignId = $api->campaignCreate($type, $opts, $content);
				
				if ($api->errorCode){
					echo "<p class='wordchimp_error'>There was an error creating your campaign. " . $random['whoops'][rand(0, count($random['whoops'])-1)] . " {$api->errorCode} {$api->errorMessage}</p>";
				} else {
					echo "<h1>Step 5: Test/Send</h1><p class='wordchimp_success'>Great success! Your campaign was created. {$random['compliment'][rand(0, count($random['compliment'])-1)]} Now what?</p>";
					
					echo "<a href='" . get_bloginfo('wpurl') . "/wp-admin/admin-ajax.php?action=wordchimp_campaign_preview&cid={$campaignId}' target='_blank'>Preview Campaign in Browser</a><br /><br />";
					echo <<<EOF
					<h3>Send a test?</h3>
					<form method='post'>
						<input type='hidden' name='wp_cmd' value='step6' />
						<input type='hidden' name='template_id' value='{$_POST['template_id']}' />
						<input type='hidden' name='mailchimp_campaign_id' value='{$campaignId}' />
						<input type='text' name='mailchimp_test_emails' value='test@email.com, test@otheremail.com' onClick="this.value='';" />
EOF;
						// Add modified template sections
						foreach ($_POST as $key => $value) {
							if ($key != 'wp_cmd' && $key != 'template_id' && $key != 'mailchimp_list_id') {
								echo "<input type='hidden' name='{$key}' value=\"" . htmlspecialchars(stripslashes($value)) . "\" />";
							}
						}
						
						echo <<<EOF
						<p class="submit">
							<input type="submit" class="button-primary" value="Send Test" />
						</p>
					</form>
					<h3>Send fo' real?</h3>
					<form method='post'>
						<input type='hidden' name='wp_cmd' value='step7' />
						<input type='hidden' name='mailchimp_campaign_id' value='{$campaignId}' />
						<p class="submit">
							<input type="submit" class="button-primary" value="Send Fo' Real!" />
						</p>
					</form>
EOF;
				}
			}
		break;
		
		case "step6":
			$api = new MCAPI_WordChimp(get_option( 'mailchimp_api_key' ));
			$emails = explode(",", $_POST['mailchimp_test_emails']);
			$campaignId = $_POST['mailchimp_campaign_id'];
			
			$retval = $api->campaignSendTest($campaignId, $emails);
			
			if ($api->errorCode){
				echo "<p class='wordchimp_error'>Unable to send test campaign.  " . $random['whoops'][rand(0, count($random['whoops'])-1)] . " {$api->errorCode} {$api->errorMessage}</p>";
			} else {
				echo "<h1>Step 5: Test/Send</h1><span class='wordchimp_notice'>Great success! A test has been sent to the e-mail addresses you provided. " . $random['compliment'][rand(0, count($random['compliment']) -1)] . "</span><br /><br />";
			}
			
			echo "<a href='" . get_bloginfo('wpurl') . "/wp-admin/admin-ajax.php?action=wordchimp_campaign_preview&cid={$campaignId}' target='_blank'>Preview Campaign in Browser</a><br /><br />";
			echo <<<EOF
			<h3>Send a test?</h3>
			<form method='post'>
				<input type='hidden' name='wp_cmd' value='step6' />
				<input type='hidden' name='mailchimp_campaign_id' value='{$campaignId}' />
				<input type='text' name='mailchimp_test_emails' value='test@email.com, test@otheremail.com' onClick="this.value='';" />
EOF;
						// Add modified template sections
						foreach ($_POST as $key => $value) {
							if ($key != 'wp_cmd' && $key != 'template_id' && $key != 'mailchimp_list_id') {
								echo "<input type='hidden' name='{$key}' value=\"" . htmlspecialchars(stripslashes($value)) . "\" />";
							}
						}
						
						echo <<<EOF
				<p class="submit">
					<input type="submit" class="button-primary" value="Send Test" />
				</p>
			</form>
			<h3>Send fo' real?</h3>
			<form method='post'>
				<input type='hidden' name='wp_cmd' value='step7' />
				<input type='hidden' name='mailchimp_campaign_id' value='{$campaignId}' />
				<p class="submit">
					<input type="submit" class="button-primary" value="Send Fo' Real!" />
				</p>
			</form>
EOF;
		break;
		
		case "step7":
			$api = new MCAPI_WordChimp(get_option( 'mailchimp_api_key' ));
			$campaignId = $_POST['mailchimp_campaign_id'];
			$emails = explode(",", $_POST['mailchimp_test_emails']);
			
			$retval = $api->campaignSendNow($campaignId);
			
			if ($api->errorCode){
				echo "<p class='wordchimp_error'>Unable to send out campaign.  " . $random['whoops'][rand(0, count($random['whoops'])-1)] . " {$api->errorCode} {$api->errorMessage}</p>";
			} else {
				echo "<h1>Ding! Campaign Sent</h1><p class='wordchimp_success'>Great success! You're campaign has been sent out. " . $random['compliment'][rand(0, count($random['compliment']) -1)] . "</p><br /><br />";
			}
		break;
	}
	
	echo <<<EOF
	</div>
EOF;
}

// Ajax
function wordchimp_get_post() {
	global $random;
	global $wpdb;
	
	$sql = "SELECT post_author, display_name, post_date, post_content, post_title, post_excerpt, post_name FROM {$wpdb->prefix}posts LEFT JOIN {$wpdb->prefix}users ON {$wpdb->prefix}posts.post_author = {$wpdb->prefix}users.ID WHERE {$wpdb->prefix}posts.id = {$_POST['post_id']}";
	
	$post = $wpdb->get_row($sql, ARRAY_A);

	$display = "<h4>{$post['post_title']}</h4>";	
	
	if (get_option( 'wordchimp_show_author')) {
		$display .= "\r\n<small>Authored by: {$post['display_name']}</small><br />";
	}
	
	if (get_option( 'wordchimp_show_timestamp' )) {
		if (get_option( 'wordchimp_timestamp_format' ) == "") {
			$post['formatted_post_date'] = date('m/d/Y g:ia', strtotime($post['post_date']));
		} else {
			$post['formatted_post_date'] = date(get_option( 'wordchimp_timestamp_format' ), strtotime($post['post_date']));
		}
		
		$display .= "\r\n<small>Posted on: <em>{$post['formatted_post_date']}</em></small><br />";
	}
	
	// Check to see if we're using an excerpt or the full post
	$post_type = get_option( 'wordchimp_use_excerpt' ) == 'true' ? 'post_excerpt' : 'post_content';
	
	// Strip images if necessary
	if (get_option( 'wordchimp_strip_images')) {
		$post[$post_type] = preg_replace("/<img[^>]+\>/i", "", $post[$post_type]);
	}
	
	// Remove short codes and finalize html content
	$display .= "\r\n" . do_shortcode($post[$post_type]) . "\r\n<hr />\r\n";

	echo $display;
	die();
}

function wordchimp_campaign_preview() {
	global $random;
	global $wpdb;
	
	$api = new MCAPI_WordChimp(get_option( 'mailchimp_api_key' ));
	
	$campaignContent = $api->campaignContent($_GET['cid']);
	
	switch ($_GET['type']) {
		default:
		case "html":
			echo $campaignContent['html'];
		break;
		
		case "text":
			echo "<html><body><pre>{$campaignContent['text']}</pre></body></html>";
		break;
	}
	die();
}

// Random comments for the lulz
$random['compliment'][] = "Aren't you special?";
$random['compliment'][] = "Way to go champ!";
$random['compliment'][] = "WordChimp loves you.";
$random['compliment'][] = "You're like a MailChimp/Wordpress ninja now.";
$random['compliment'][] = "Did you just get your hair did? Nice!";
$random['compliment'][] = "You're a bi-winner!";
$random['compliment'][] = "Extremely groovy!";
$random['compliment'][] = "You're making the whole office look good.";
$random['compliment'][] = "You should run for President!";

$random['whoops'][] = "Whoops!";
$random['whoops'][] = "What did you do?";
$random['whoops'][] = "It's broke-a-did.";
$random['whoops'][] = "Oh snap!";
$random['whoops'][] = "Oh geeze...";
$random['whoops'][] = "WWWWHHHHHHHYYYYYYYY?????";
?>