<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2024 The Cacti Group                                 |
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

/* set default action */
set_default_action();

function plugin_motd_pre_login() {
	$motd = db_fetch_assoc("SELECT * FROM plugin_motd_message WHERE (type='pre' AND ack_required='' AND enabled='on')");
	
	if (cacti_sizeof($motd)) {
		foreach ($motd as $row) {
			print "<td>" . $row['message'] . "</td>";
		}
	}
}

function plugin_motd_post_login() {
	$motd = db_fetch_assoc("SELECT * FROM plugin_motd_message WHERE (type='post' AND ack_required='' AND enabled='on')");
	if (cacti_sizeof($motd)) {
		foreach ($motd as $row) {
			print "<td>" . $row['message'] . "</td>";
		}
	}
}

function plugin_motd_page_head() {
        global $config;
	
        $selectedTheme = get_selected_theme();

        if (file_exists($config['base_path'] . '/plugins/motd/themes/' . $selectedTheme . '/login.css')) {
        	print "<link type='text/css' href='" . $config['url_path'] . 'plugins/motd/themes/' . $selectedTheme . "/login.css' rel='stylesheet'>";
        }
}
