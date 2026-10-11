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
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

function motd_show_tab($current_tab) {
	global $config, $motd_tabs;

	print "<div class='tabs'><nav><ul>";

	if (cacti_sizeof($motd_tabs)) {
		foreach ($motd_tabs as $url => $name) {
			print "<li><a class='" . (($url == $current_tab) ? 'pic selected' : 'pic') . "' href='" . $config['url_path'] .
				"plugins/motd/$url'>$name</a></li>";
		}
	}

	print '</ul></nav></div>';
}

function motd_check_debug() {
	global $debug;

	if (!$debug) {
		$plugin_debug = read_config_option('selective_plugin_debug');

		if (preg_match('/(^|[, ]+)(motd)($|[, ]+)/', $plugin_debug, $matches)) {
			$debug = (cacti_sizeof($matches) == 4 && $matches[2] == 'motd');
		}
	}
}

/**
 * Log debug message
 *
 * @param string $message Text of the message that will be logged
 *
 * @return void
 */
function motd_debug($message = '') {
	global $debug;

	if ($debug) {
		cacti_log('DEBUG: ' . trim($message), true, 'MOTD');
	}
}
