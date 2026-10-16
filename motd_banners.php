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

require('../../include/auth.php');

$motd_tabs = [
        'motd_banners.php'       => __('Banners', 'motd'),
];

$motd_fields = [
        'general_space' => [
                'method'        => 'spacer',
                'friendly_name' => __('General Settings','motd')
        ],
        'title' => [
                'method'        => 'textbox',
                'friendly_name' => __('Title'),
                'description'   => __('A useful name for this motd banner'),
                'value'         => '|arg1:title|',
                'max_length'    => '200',
                'size'          => '200',
                'default'       => __('New Banner')
        ],
        'message'  => [
                'friendly_name' => __('Banner', 'motd'),
                'method'        => 'textarea',
                'textarea_rows' => 10,
                'textarea_cols' => 100,
                'description'   => __('motd Banner to display', 'motd'),
                'value'         => '|arg1:message|'
        ],
        'id' => [
                'method' => 'hidden_zero',
                'value'  => '|arg1:id|'
        ],
        'enabled' => [
                'method'        => 'checkbox',
                'friendly_name' => __('Enable motd', 'motd'),
                'description'   => __('Uncheck this box to disable this motd from being displayed.', 'motd'),
                'value'         => '|arg1:enabled|',
                'default'       => ''
        ],
        'ack_required' => [
                'method'        => 'checkbox',
                'friendly_name' => __('Require Acknowledgement', 'motd'),
                'description'   => __('Check this box to require acknowledgement of motd.', 'motd'),
                'value'         => '|arg1:ack_required|',
                'default'       => ''
        ],
        'type' => [
                'method'        => 'textbox',
                'friendly_name' => __('Pre- or Post-login', 'motd'),
                'description'   => __('Display motd pre- or post-login. Valid values are \'pre\' and \'post\' for pre-login or post-login, respectively.', 'motd'),
                'value'         => '|arg1:type|',
                'max_length'    => '4',
                'size'          => '4',
                'default'       => __('pre')
        ]
];

$motd_actions_menu = [
        1 => __('Delete', 'motd'),
        2 => __('Disable', 'motd'),
        3 => __('Enable', 'motd'),
        4 => __('Duplicate', 'motd'),
];

set_default_action();

switch (get_request_var('action')) {
	case 'save':
		form_save();

		break;
	case 'actions':
		form_actions();

		break;
	case 'edit':
		top_header();
		motd_data_edit();
		bottom_footer();

		break;
        case 'enable':
                $id = get_filter_request_var('id');

                if ($id > 0) {
                        db_execute_prepared('UPDATE plugin_motd_message
                        SET enabled = "on"
                        WHERE id = ?', [$id]);
                }

                header('Location: ' . htmlspecialchars(basename($_SERVER['PHP_SELF'])) . '?header=false');
                exit;

                break;
        case 'disable':
                $id = get_filter_request_var('id');

                if ($id > 0) {
                        db_execute_prepared('UPDATE plugin_motd_message
                        SET enabled = ""
                        WHERE id = ?', [$id]);
                }

                header('Location: ' . htmlspecialchars(basename($_SERVER['PHP_SELF'])) . '?header=false');
                exit;

                break;

	default:
		top_header();
		data_list();
		bottom_footer();

		break;
}

function form_actions() {
	global $motd_actions_menu;

	// ================= input validation =================
	get_filter_request_var('drp_action', FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/^([a-zA-Z0-9_]+)$/']]);
	// ====================================================

	// if we are to save this form, instead of display it
	if (isset_request_var('selected_items')) {
		$selected_items = sanitize_unserialize_selected_items(get_nfilter_request_var('selected_items'));

		if ($selected_items != false) {
			if (get_filter_request_var('drp_action') == 1) { //Delete Selected Item
				if (cacti_sizeof($selected_items)) {
					foreach ($selected_items as $item) {
						db_execute_prepared('DELETE FROM plugin_motd_message WHERE id = ?', [$item]);
					}
				}
			} elseif (get_filter_request_var('drp_action') == 2) { // Disable Selected Item
				foreach ($selected_items as $item) {
					db_execute_prepared('UPDATE plugin_motd_message SET enabled = "" WHERE id = ?', [$item]);
				}
			} elseif (get_filter_request_var('drp_action') == 3) { // Enable Selected Item
				foreach ($selected_items as $item) {
					db_execute_prepared('UPDATE plugin_motd_message SET enabled = "on" WHERE id = ?', [$item]);
				}
			} elseif (get_filter_request_var('drp_action') == 4) { //Duplicate Selected Item
				$newid = 1;

				foreach ($selected_items as $item) {
					$save = db_fetch_row_prepared('SELECT * FROM plugin_motd_message WHERE id = ?', [$item]);

					$save['id']		= 0;
					$save['title']		= 'New motd (' . $newid . ')';
					$save['enabled']	= '';
					$save['ack_required']	= '';

					$id = sql_save($save, 'plugin_motd_message');

					$newid++;
				}
			}
		}

		header('Location: ' . htmlspecialchars(basename($_SERVER['PHP_SELF'])) . '?header=false');
		exit;
	}

	// setup some variables
	$item_list   = '';
	$items_array = [];

	// loop through each of the graphs selected on the previous page and get more info about them
        foreach ($_POST as $var => $val) {
                if (preg_match('/^chk_([0-9]+)$/', $var, $matches)) {
                        // ================= input validation =================
                        input_validate_input_number($matches[1]);
                        // ====================================================

                        $item_list .= '<li>' . db_fetch_cell_prepared('SELECT title FROM plugin_motd_message WHERE id = ?', [$matches[1]]) . '</li>';
                        $items_array[] = $matches[1];
                }
        }


	top_header();

	form_start(htmlspecialchars(basename($_SERVER['PHP_SELF'])));

	html_start_box($motd_actions_menu[get_filter_request_var('drp_action')], '60%', '', '3', 'center', '');

	if (cacti_sizeof($items_array) > 0) {
		if (get_filter_request_var('drp_action') == 1) {
			print "	<tr>
					<td class='topBoxAlt'>
						<p>" . __n('Click \'Continue\' to delete the following items.', 'Click \'Continue\' to delete following items.', cacti_sizeof($items_array)) . "</p>
						<div class='itemlist'><ul>$item_list</ul></div>
					</td>
				</tr>";

			$save_html = "<input type='button' value='" . __esc('Cancel') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __esc('Continue') . "' title='" . __esc_n('Delete item', 'Delete items', cacti_sizeof($items_array)) . "'>";
                } elseif (get_filter_request_var('drp_action') == 2) { // disable
                        print " <tr>
                                        <td class='topBoxAlt'>
                                                <p>" . __n('Click \'Continue\' to disable the following items.', 'Click \'Continue\' to disable following items.', cacti_sizeof($items_array)) . "</p>
                                                <div class='itemlist'><ul>$item_list</ul></div>
                                        </td>
                                </tr>";

                        $save_html = "<input type='button' value='" . __esc('Cancel') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __esc('Continue') . "' title='" . __esc_n('Disable item', 'Disable items', cacti_sizeof($items_array)) . "'>";
                } elseif (get_filter_request_var('drp_action') == 3) { // enable
                        print " <tr>
                                        <td class='topBoxAlt'>
                                                <p>" . __n('Click \'Continue\' to enable the following items.', 'Click \'Continue\' to enable following items.', cacti_sizeof($items_array)) . "</p>
                                                <div class='itemlist'><ul>$item_list</ul></div>
                                        </td>
                                </tr>";

                        $save_html = "<input type='button' value='" . __esc('Cancel') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __esc('Continue') . "' title='" . __esc_n('Enable item', 'Enable items', cacti_sizeof($items_array)) . "'>";
                } elseif (get_filter_request_var('drp_action') == 4) { // duplicate
                        print " <tr>
                                        <td class='topBoxAlt'>
                                                <p>" . __n('Click \'Continue\' to duplicate the following items.', 'Click \'Continue\' to duplicate following items.', cacti_sizeof($items_array)) . "</p>
                                                <div class='itemlist'><ul>$item_list</ul></div>
                                        </td>
                                </tr>";
			 $save_html = "<input type='button' value='" . __esc('Cancel') . "' onClick='cactiReturnTo()'>&nbsp;<input type='submit' value='" . __esc('Continue') . "' title='" . __esc_n('Duplicate item', 'Duplicate items', cacti_sizeof($items_array)) . "'>";
		}
	} else {
		raise_message(40);
		header('Location: ' . htmlspecialchars(basename($_SERVER['PHP_SELF'])) . '?header=false');
		exit;
	}
	
	print "<tr>
		<td class='saveRow'>
			<input type='hidden' name='action' value='actions'>
			<input type='hidden' name='selected_items' value='" . (isset($items_array) ? serialize($items_array) : '') . "'>
			<input type='hidden' name='drp_action' value='" . get_request_var('drp_action') . "'>
			$save_html
		</td>
	</tr>";

	html_end_box();

	form_end();

	bottom_footer();
}

function form_save() {
	if (isset_request_var('save_component')) {
		$save['id']		= get_filter_request_var('id');
		$save['title']		= form_input_validate(get_nfilter_request_var('title'), 'title', '', false, 3);
		$save['message']	= form_input_validate(get_nfilter_request_var('message'), 'message', '', false, 3);
		$save['type']		= form_input_validate(get_nfilter_request_var('type'), 'type', '^(pre|post)', false, 3);

		if ($save['id'] > 0 && isset($_SESSION['sess_error_fields']) && cacti_sizeof($_SESSION['sess_error_fields'])) {
			foreach ($_SESSION['sess_error_fields'] as $item) {
				unset($save[$item], $_SESSION['sess_error_fields'][$item]);
			}
			clear_messages();
		}

		if (isset_request_var('enabled')) {
                        $save['enabled'] = 'on';
                } else {
                        $save['enabled'] = '';
                }
		
		if (isset_request_var('ack_required')) {
                        $save['ack_required'] = 'on';
                } else {
                        $save['ack_required'] = '';
                }
		
		if (!is_error_message()) {
			$saved_id = sql_save($save, 'plugin_motd_message');

			if ($saved_id) {
				raise_message(1);
			} else {
				raise_message(2);
			}
		}

		if (is_error_message()) {
			header('Location: ' . htmlspecialchars(basename($_SERVER['PHP_SELF'])) . '?header=false&action=edit&id=' . (empty($saved_id) ? get_nfilter_request_var('id') : $saved_id));
		} else {
			header('Location: ' . htmlspecialchars(basename($_SERVER['PHP_SELF'])) . '?header=false');
		}
	}
	exit;
}

function motd_data_edit() {
	global $motd_fields;

	// ================= input validation =================
	get_filter_request_var('id');
	// ====================================================

	$data = [];

	if (!isempty_request_var('id')) {
		$data = db_fetch_row_prepared('SELECT *
			FROM plugin_motd_message
			WHERE id = ?',
			[get_request_var('id')]);

		$header_label = __('motd [edit: %s]', $data['title']);
	} else {
		$header_label = __('motd [new]');
	}

	$_SESSION['cur_device_id'] = get_request_var('id');

	form_start(htmlspecialchars(basename($_SERVER['PHP_SELF'])));

	html_start_box($header_label, '100%', true, '3', 'center', '');

	draw_edit_form(
		[
			'config' => ['no_form_tag' => true],
			'fields' => inject_form_variables($motd_fields, $data)
		]
	);

	form_hidden_box('save_component', '1', '');

	html_end_box(true, true);

	form_save_button(htmlspecialchars(basename($_SERVER['PHP_SELF'])));

	?>
        <script type='text/javascript'>

        function () {
		$('#row_title').show();
		$('#row_message').show();
		$('#row_enabled').show();
		$('#row_ack_required').show();
		$('#row_type').show();
        }

        </script>
        <?php
}

function request_validation() {
	$filters = [
		'rows' => [
			'filter'  => FILTER_VALIDATE_INT,
			'pageset' => true,
			'default' => '-1'
			],
		'page' => [
			'filter'  => FILTER_VALIDATE_INT,
			'default' => '1'
			],
		'filter' => [
			'filter'  => FILTER_DEFAULT,
			'pageset' => true,
			'default' => ''
			],
		'sort_column' => [
			'filter'  => FILTER_CALLBACK,
			'default' => 'name',
			'options' => ['options' => 'sanitize_search_string']
			],
		'sort_direction' => [
			'filter'  => FILTER_CALLBACK,
			'default' => 'ASC',
			'options' => ['options' => 'sanitize_search_string']
			]
	];

	validate_store_request_vars($filters, 'sess_motd_proxy');
}

function motd_filter() {
	global $item_rows;

	html_start_box(__('motd Banner Management', 'motd') , '100%', '', '3', 'center', htmlspecialchars(basename($_SERVER['PHP_SELF'])) . '?action=edit');

	?>
	<tr class='even'>
		<td>
		<form id='form_motd_item' action='<?php print htmlspecialchars(basename($_SERVER['PHP_SELF'])); ?>'>
			<table class='filterTable'>
				<tr>
					<td>
						<?php print __('Search', 'motd'); ?>
					</td>
					<td>
						<input type='text' class='ui-state-default ui-corner-all' id='filter' name='filter' size='25' value='<?php print html_escape_request_var('filter'); ?>'>
					</td>
					<td>
						<span class='nowrap'>
							<input type='button' class='ui-button ui-corner-all ui-widget' id='refresh' value='<?php print __esc('Go'); ?>' title='<?php print __esc('Set/Refresh Filters', 'motd'); ?>'>
							<input type='button' class='ui-button ui-corner-all ui-widget' id='clear' value='<?php print __esc('Clear'); ?>' title='<?php print __esc('Clear Filters', 'motd'); ?>'>
						</span>
					</td>
				</tr>
			</table>
		</form>
		<script type='text/javascript'>

		function applyFilter() {
			strURL  = '<?php print htmlspecialchars(basename($_SERVER['PHP_SELF'])); ?>?header=false';
			strURL += '&filter=' + $('#filter').val();
			strURL += '&rows=' + $('#rows').val();
			loadPageNoHeader(strURL);
		}

		function clearFilter() {
			strURL = '<?php print htmlspecialchars(basename($_SERVER['PHP_SELF'])); ?>?clear=1&header=false';
			loadPageNoHeader(strURL);
		}

		$(function() {
			$('#rows').click(function() {
				applyFilter();
			});

			$('#clear').click(function() {
				clearFilter();
			});

			$('#form_motd_item').submit(function(event) {
				event.preventDefault();
				applyFilter();
			});
		});

		</script>
		</td>
	</tr>
	<?php
	html_end_box();
}

function data_list() {
        global $config, $motd_actions_menu, $refresh, $text_result_search;
	
	require($config['base_path'] . '/plugins/motd/includes/functions.php');
	
	request_validation();
	
        if (get_request_var('rows') == '-1') {
                $rows = read_config_option('num_rows_table');
        } else {
                $rows = get_request_var('rows');
        }

        motd_show_tab(htmlspecialchars(basename($_SERVER['PHP_SELF'])));

        motd_filter();

        $total_rows = db_fetch_cell("SELECT COUNT(id) FROM plugin_motd_message");

	$result = db_fetch_assoc("SELECT * FROM plugin_motd_message ORDER BY `title` ASC");

        $display_text = [
                'title' => [
                        'display'	=> __('Name','motd'),
                        'sort'		=> 'ASC',
                        'align'		=> 'left'
                ],
               'message' => [
                        'display'	=> __('motd Banner','motd'),
                        'sort'		=> 'ASC',
                        'align'		=> 'left'
                ],
		'type' => [
			'display'	=> __('Pre/Post Login','motd'),
			'sort'		=> 'ASC',
			'align'		=> 'left'
		],
		'ack_required' => [
			'display'	=> __('Acknowledgement Required','motd'),
			'sort'		=> 'ASC',
			'align'		=> 'left'
		],
		'enabled' => [
			'display'	=> __('Enabled','motd'),
			'sort'		=> 'ASC',
			'align'		=> 'left'
		]
        ];

	$columns = cacti_sizeof($display_text);
	
	if ($total_rows == 1) {
	        $nav = html_nav_bar(htmlspecialchars(basename($_SERVER['PHP_SELF'])) . '?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, $columns, __('motd', 'motd'), 'page', 'main');
	} else {
	        $nav = html_nav_bar(htmlspecialchars(basename($_SERVER['PHP_SELF'])) . '?filter=' . get_request_var('filter'), MAX_DISPLAY_PAGES, get_request_var('page'), $rows, $total_rows, $columns, __('motds', 'motd'), 'page', 'main');
	}

        form_start(htmlspecialchars(basename($_SERVER['PHP_SELF'])), 'chk');
	
	if (cacti_sizeof($result)) {
		print $nav;
	}

        html_start_box('', '100%', '', '3', 'center', '');

        html_header_sort_checkbox($display_text, get_request_var('sort_column'), get_request_var('sort_direction'), false);

	if (cacti_sizeof($result)) {
		foreach ($result as $row) {
			form_selectable_cell(filter_value($row['title'],get_request_var('filter'),'motd_banners.php?action=edit&id=' . $row['id']),'','left');

			if (strlen($row['message']) > 100) {
				$row['message'] = substr($row['message'],0,100) . ". . .";
			}
			
			form_selectable_cell($row['message'],$row['id'],'','left');
			form_selectable_cell($row['type'],$row['id'],'','center');
			
			if ($row['ack_required']) {
				$row['ack_required'] = "Yes";
			} else {
				$row['ack_required'] = "No";
			}

			form_selectable_cell($row['ack_required'],$row['id'],'','center');
			
			if ($row['enabled']) {
				$row['enabled'] = "Yes";
			} else {
				$row['enabled'] = "No";
			}
			
			form_selectable_cell($row['enabled'],$row['id'],'','center');
			form_checkbox_cell($row['title'],$row['id']);
			form_end_row();
		}
	} else {
                print "<tr class='tableRow'><td colspan='" . $columns . "'><em>" . __('No motds found', 'motd') . "</em></td></tr>\n";
	}

        html_end_box(false);

        draw_actions_dropdown($motd_actions_menu, 1);

        form_end();
}
