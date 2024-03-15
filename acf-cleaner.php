<?php
/*
  Plugin Name: Custom Fields Cleaner
  Plugin URI: https://github.com/DcBexter/custom-fields-cleaner
  Description: Remove empty and orphaned Custom Field entries.
  Version: 1.5.0
  Author: DcBexter
  Author URI: https://github.com/DcBexter/
*/
add_action('admin_menu', 'acf_cleaner_admin_menu');
define('ACF_CLEANER_PAGE', plugin_basename(dirname(__FILE__)) . '/acf-cleaner-admin.php');

function acf_cleaner_admin_menu(): void
{
  add_management_page(__('ACF Cleaner | Manage', 'acf_cleaner'),
                      __('ACF Cleaner', 'acf_cleaner'),
                      'manage_options',
                      ACF_CLEANER_PAGE);
}

add_filter('plugin_row_meta', 'acf_cleaner_meta_links', 10, 2);
add_filter('plugin_action_links', 'acf_cleaner_manage_link', 10, 2);

function acf_cleaner_manage_link($links, $file) {
  static $this_plugin;

    if (!$this_plugin){
      $this_plugin = plugin_basename(__FILE__);
    }

    if ($file == $this_plugin) {
      $settings_link = '<a href="tools.php?page=' . ACF_CLEANER_PAGE . '" title="' . __('Use this plugin', 'acf_cleaner') . '">' . __('Manage', 'acf_cleaner') . '</a>';
      array_unshift($links, $settings_link);
    }

    return $links;
}

function acf_cleaner_meta_links($links, $file) {

  $plugin = plugin_basename(__FILE__);

  if ($file == $plugin) {
    $links[] = '<a href="tools.php?page=' . ACF_CLEANER_PAGE . '" title="' . __('Use this plugin', 'acf_cleaner') . '">' . __('Manage', 'acf_cleaner') . '</a>';
    return $links;
  }

  return $links;
}
