<?php
/*
Plugin Name: What's Up WordPress?
Plugin URI: http://www.jamesdimick.com/creations/whats-up-wordpress/
Description: Exposes various bits of data to the What's Up WordPress desktop application.
Version: 1.0
Author: James Dimick
Author URI: http://www.jamesdimick.com/

=== VERSION HISTORY ===
  01.19.10 - v1.0 - The first version

=== LEGAL INFORMATION ===
  Copyright (C) 2010 James Dimick <mail@jamesdimick.com> - www.JamesDimick.com

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

register_activation_hook(__FILE__, 'wuw_activate');
register_deactivation_hook(__FILE__, 'wuw_deactivate');
add_action('init', 'wuw_init');
function wuw_activate(){$admin_role=get_role('administrator');if($admin_role->has_cap('whats_up_wordpress')===false)$admin_role->add_cap('whats_up_wordpress');}
function wuw_deactivate(){$admin_role=get_role('administrator');if($admin_role->has_cap('whats_up_wordpress')===true)$admin_role->remove_cap('whats_up_wordpress');}
function wuw_init() {
	if(isset($_POST['whatsupwordpressusername']) && isset($_POST['whatsupwordpresspassword'])) {
		$post_user = sanitize_user(trim($_POST['whatsupwordpressusername']));
		$post_pass = trim($_POST['whatsupwordpresspassword']);
		$results = '';
		if(user_pass_ok($post_user, $post_pass)) {
			$user_data = get_userdatabylogin($post_user);
			set_current_user($user_data->ID);
			if(current_user_can('whats_up_wordpress')) {
				if(!function_exists('get_preferred_from_update_core')) require_once(ABSPATH.'wp-admin/includes/update.php');
				$cur = get_preferred_from_update_core();
				$upgrade = isset($cur->response) && $cur->response === 'upgrade' ? 1 : 0;
				if(!function_exists('get_plugins')) require_once(ABSPATH.'wp-admin/includes/plugin.php');
				$all_plugins = get_plugins();
				$active_plugins = 0; foreach((array)$all_plugins as $plugin_file => $plugin_data) if(is_plugin_active($plugin_file)) $active_plugins++;
				$update_plugins = get_transient('update_plugins');
				$update_count = 0; if(!empty($update_plugins->response)) $update_count = count($update_plugins->response);
				$num_posts = wp_count_posts('post', 'readable');
				$num_comm = wp_count_comments();
				header('Content-Type: application/json');
				exit(json_encode(array(
					'site_name' => (string)get_option('blogname'),
					'site_url' => (string)site_url(),
					'site_admin_url' => (string)admin_url(),
					'wordpress_version' => (string)$GLOBALS['wp_version'],
					'core_update_available' => (int)$upgrade,
					'active_plugins' => (int)$active_plugins,
					'updatable_plugins' => (int)$update_count,
					'total_posts' => (int)array_sum((array)$num_posts)-$num_posts->trash,
					'total_posts_categories' => (int)wp_count_terms('category', 'ignore_empty=true'),
					'published_posts' => (int)$num_posts->publish,
					'draft_posts' => (int)$num_posts->draft,
					'pending_posts' => (int)$num_posts->pending,
					'scheduled_posts' => (int)$num_posts->future,
					'trashed_posts' => (int)$num_posts->trash,
					'total_comments' => (int)$num_comm->total_comments,
					'approved_comments' => (int)$num_comm->approved,
					'pending_comments' => (int)$num_comm->moderated,
					'spam_comments' => (int)$num_comm->spam,
					'trashed_comments' => (int)$num_comm->trash
				)));
			}
		}
	}
}
?>