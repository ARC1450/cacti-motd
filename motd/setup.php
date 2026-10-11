<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2026 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDtool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

function plugin_motd_install() {
	// Register the motd plugin/page
	api_plugin_register_hook('motd', 'draw_navigation_text','plugin_motd_draw_navigation_text','setup.php');
	api_plugin_register_hook('motd', 'config_arrays','plugin_motd_config_arrays','setup.php');
	
	// Register the motd before and after login ability, register title change, and include CSS to deal with longer MOTD/Banners.
	api_plugin_register_hook('motd','login_before','plugin_motd_pre_login','includes/motd_login.php');
	api_plugin_register_hook('motd','login_after','plugin_motd_post_login','includes/motd_login.php');
	api_plugin_register_hook('motd','custom_login','plugin_motd_login_title','includes/motd_login.php');
	api_plugin_register_hook('motd','page_head','plugin_motd_page_head','includes/motd_login.php');

	// Restrict the MOTD administration
	api_plugin_register_realm('motd', 'motd_banners.php', __('motd Admin', 'motd'), 1);
	
	plugin_motd_setup_table();
}

function plugin_motd_uninstall() {
	db_execute('DROP TABLE IF EXISTS plugin_motd_message');
}

function plugin_motd_version() {
	global $config;
	$info = parse_ini_file($config['base_path'] . '/plugins/motd/INFO', true);

	return $info['info'];
}

function plugin_motd_check_config() {
	global $config;
	
	$info = plugin_motd_version();
        $new  = $info['version'];
        $old  = db_fetch_cell('SELECT version FROM plugin_config WHERE directory="motd"');

	if(cacti_version_compare($old,$new,'<')) {
		        // Since this is the initial release, just set the version, author, etc correctly
		        db_execute_prepared("UPDATE plugin_config
		                SET version = ?, author = ?, webpage = ?
                		WHERE directory = 'motd'",
		                [$info['version'], $info['author'], $info['homepage']]
        		);
	}
	return true;
}

function plugin_motd_setup_table() {
	$data              = [];
	$data['columns'][] = ['name' => 'id', 'type' => 'int(11)', 'NULL' => false, 'auto_increment' => true];
	$data['columns'][] = ['name' => 'title', 'type' => 'varchar(200)', 'NULL' => false, 'default' => ''];
	$data['columns'][] = ['name' => 'message', 'type' => 'varchar(2048)', 'NULL' => false, 'default' => ''];
	$data['columns'][] = ['name' => 'enabled', 'type' => 'varchar(2)', 'NULL' => false, 'default' => ''];
	$data['columns'][] = ['name' => 'ack_required', 'type' => 'varchar(2)', 'NULL' => false, 'default' => ''];
	$data['columns'][] = ['name' => 'type', 'type' => 'varchar(5)', 'NULL' => false , 'default' => 'pre'];

	$data['primary']   = 'id';
	$data['keys'][]    = ['name' => 'enabled', 'columns' => 'enabled'];
	$data['type']      = 'InnoDB';
	$data['comment']   = 'Holds motd banners';

	api_plugin_db_table_create('motd', 'plugin_motd_message', $data);
}

function plugin_motd_config_arrays() {
	global $menu;

	$menu[__('Management')]['plugins/motd/motd_banners.php'] = __('motd Banner', 'motd');

	$files = ['index.php', 'plugins.php', 'motd_banners.php'];

	if (in_array(get_current_page(), $files, true)) {
		plugin_motd_check_config();
	}
}

function plugin_motd_draw_navigation_text($nav) {
	$nav['motd_banners.php:'] = [
		'title'		=> __('Manage motds', 'motd'),
		'mapping'	=> 'index.php:',
		'url'		=> 'motd_banners.php',
		'level'		=> '1'
	];

	return $nav;
}

