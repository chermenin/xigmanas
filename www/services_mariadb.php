<?php
/*
	services_mariadb.php

	Part of XigmaNAS® (https://www.xigmanas.com).
	Copyright © 2018-2023 XigmaNAS® <info@xigmanas.com>.
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice, this
	   list of conditions and the following disclaimer.

	2. Redistributions in binary form must reproduce the above copyright notice,
	   this list of conditions and the following disclaimer in the documentation
	   and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
	ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
	WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
	DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
	ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
	(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
	LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
	ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
	(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
	SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

	The views and conclusions contained in the software and documentation are those
	of the authors and should not be interpreted as representing official policies
	of XigmaNAS®, either expressed or implied.
*/

require_once 'autoload.php';
require_once 'auth.inc';
require_once 'guiconfig.inc';

use common\arr;
use services\mariadb\setting_toolbox as toolbox;
use services\mariadb\shared_toolbox;

//	init indicators
$input_errors = [];
//	preset $savemsg when a reboot is pending
if(file_exists($d_sysrebootreqd_path)):
	$savemsg = get_std_save_message(0);
endif;
//	init properties, sphere and rmo
$cop = toolbox::init_properties();
$sphere = toolbox::init_sphere();
$rmo = toolbox::init_rmo($cop,$sphere);
$cops = [
	$cop->get_enable(),
	$cop->get_homedir(),
	$cop->get_auxparam(),
	$cop->get_phrasecookieauth()
];
$pending_changes = updatenotify_exists($sphere->get_notifier());
[$page_method,$page_action,$page_mode] = $rmo->validate();
switch($page_method):
	case 'SESSION':
		switch($page_action):
			case $sphere->get_script()->get_basename():
				$retval = filter_var($_SESSION[$sphere->get_script()->get_basename()],FILTER_VALIDATE_INT,['options' => ['default' => 0]]);
				unset($_SESSION['submit'],$_SESSION[$sphere->get_script()->get_basename()]);
				$savemsg = get_std_save_message($retval);
				if($retval !== 0):
					$page_action = 'edit';
					$page_mode = PAGE_MODE_EDIT;
				else:
					$page_action = 'view';
					$page_mode = PAGE_MODE_VIEW;
				endif;
				break;
		endswitch;
		break;
	case 'POST':
		switch($page_action):
			case 'apply':
				$retval = 0;
				$retval |= updatenotify_process($sphere->get_notifier(),$sphere->get_notifier_processor());
				config_lock();
				$retval |= rc_exec_service('userdb');
				$retval |= rc_update_service('mysqldb');
				config_unlock();
				$_SESSION['submit'] = $sphere->get_script()->get_basename();
				$_SESSION[$sphere->get_script()->get_basename()] = $retval;
				header($sphere->get_script()->get_location());
				exit;
				break;
/*
			case 'reload':
				$retval = 0;
				$name = $cop->get_enable()->get_name();
				$sphere->grid[$name] ??= false;
				if($sphere->grid[$name] && !$pending_changes):
					config_lock();
					$retval |= rc_update_service('mysqldb',true);
					config_unlock();
					$_SESSION['submit'] = $sphere->get_script()->get_basename();
					$_SESSION[$sphere->get_script()->get_basename()] = $retval;
					header($sphere->get_script()->get_location());
				else:
					$page_action = 'view';
					$page_mode = PAGE_MODE_VIEW;
				endif;
				exit;
				break;
 */
			case 'restart':
				$retval = 0;
				$name = $cop->get_enable()->get_name();
				$sphere->grid[$name] ??= false;
				if($sphere->grid[$name] && !$pending_changes):
					config_lock();
					$retval |= rc_update_service('mysqldb');
					config_unlock();
					$_SESSION['submit'] = $sphere->get_script()->get_basename();
					$_SESSION[$sphere->get_script()->get_basename()] = $retval;
					header($sphere->get_script()->get_location());
					exit;
				else:
					$page_action = 'view';
					$page_mode = PAGE_MODE_VIEW;
				endif;
				break;
			case 'disable':
				$retval = 0;
				$name = $cop->get_enable()->get_name();
				$sphere->grid[$name] ??= false;
				if($sphere->grid[$name]):
					$sphere->grid[$name] = false;
					write_config();
					config_lock();
					$retval |= rc_update_service('mysqldb');
					config_unlock();
					$_SESSION['submit'] = $sphere->get_script()->get_basename();
					$_SESSION[$sphere->get_script()->get_basename()] = $retval;
					header($sphere->get_script()->get_location());
					exit;
				else:
					$page_action = 'view';
					$page_mode = PAGE_MODE_VIEW;
				endif;
				break;
			case 'enable':
				$retval = 0;
				$name = $cop->get_enable()->get_name();
				$sphere->grid[$name] ??= false;
				if($sphere->grid[$name] || $pending_changes):
					$page_action = 'view';
					$page_mode = PAGE_MODE_VIEW;
				else:
					$sphere->grid[$name] = true;
					write_config();
					config_lock();
					$retval |= rc_update_service('mysqldb');
					config_unlock();
					$_SESSION['submit'] = $sphere->get_script()->get_basename();
					$_SESSION[$sphere->get_script()->get_basename()] = $retval;
					header($sphere->get_script()->get_location());
					exit;
				endif;
				break;
		endswitch;
		break;
endswitch;
//	validate
switch($page_action):
	case 'edit':
	case 'view':
		$source = $sphere->grid;
		foreach($cops as $cops_element):
			$name = $cops_element->get_name();
			switch($cops_element->get_input_type()):
				case $cops_element::INPUT_TYPE_TEXTAREA:
					if(array_key_exists($name,$source) && is_array($source[$name])):
						$source[$name] = implode("\n",$source[$name]);
					endif;
					break;
			endswitch;
			$sphere->row[$name] = $cops_element->validate_array_element($source);
			if(is_null($sphere->row[$name])):
				if(array_key_exists($name,$source) && is_scalar($source[$name])):
					$sphere->row[$name] = $source[$name];
				else:
					$sphere->row[$name] = $cops_element->get_defaultvalue();
				endif;
			endif;
		endforeach;
		break;
	case 'save':
		$source = $_POST;
		foreach($cops as $cops_element):
			$name = $cops_element->get_name();
			$sphere->row[$name] = $cops_element->validate_input();
			if(is_null($sphere->row[$name])):
				$input_errors[] = $cops_element->get_message_error();
				if(array_key_exists($name,$source) && is_scalar($source[$name])):
					$sphere->row[$name] = $source[$name];
				else:
					$sphere->row[$name] = $cops_element->get_defaultvalue();
				endif;
			endif;
		endforeach;
		if(empty($input_errors)):
			foreach($cops as $cops_element):
				$name = $cops_element->get_name();
				switch($cops_element->get_input_type()):
					case $cops_element::INPUT_TYPE_TEXTAREA:
						$sphere->row[$name] = array_map(fn($element) => trim($element,"\n\r\t"),explode("\n",$sphere->row[$name]));
						break;
				endswitch;
				$sphere->grid[$name] = $sphere->row[$name];
			endforeach;
			$usermysqlhomedir = $sphere->row[$cop->get_homedir()->get_name()] ?? '';
			if((preg_match('/\S/',$usermysqlhomedir) === 1) && file_exists($usermysqlhomedir)):
			else:
				$usermysqlhomedir = '/nonexistent';
			endif;
//			update user mysql home dir
			$extraoptions_changed = false;
			$users = &arr::make_branch($config,'system','usermanagement','user');
			$index = arr::search_ex('mysql',$users,'name');
			if($index !== false):
				$extraoptions_new = sprintf('-c "MySQL user" -d "%s" -s /usr/sbin/nologin',$usermysqlhomedir);
				$extraoptions_current = $users[$index]['extraoptions'] ?? '';
				$extraoptions_changed = $extraoptions_current !== $extraoptions_new;
				if($extraoptions_changed):
					$users[$index]['extraoptions'] = $extraoptions_new;
				endif;
			endif;
			write_config();
			updatenotify_set($sphere->get_notifier(),UPDATENOTIFY_MODE_MODIFIED,'SERVICE',$sphere->get_notifier_processor());
			header($sphere->get_script()->get_location());
			exit;
		else:
			$page_mode = PAGE_MODE_EDIT;
		endif;
		break;
endswitch;
//	determine final page mode and calculate readonly flag
[$page_mode,$is_readonly] = calc_skipviewmode($page_mode);
$is_enabled = $sphere->row[$cop->get_enable()->get_name()];
$is_running = (rc_is_service_running('mysqldb') === 0);
$is_running_message = $is_running ? gettext('Yes') : gettext('No');
$input_errors_found = count($input_errors) > 0;
//	create document
$document = new_page($sphere->get_page_title(),$sphere->get_script()->get_scriptname());
//	add tab navigation
shared_toolbox::add_tabnav($document);
//	get areas
$body = $document->getElementById('main');
$pagecontent = $document->getElementById('pagecontent');
//	create data area
$content = $pagecontent->add_area_data();
//	display information, warnings and errors
$content->
	ins_input_errors($input_errors)->
	ins_info_box($savemsg)->
	ins_error_box($errormsg);
if($pending_changes && !$input_errors_found):
	$content->ins_config_has_changed_box();
endif;
//	add content
$tds1 = $content->add_table_data_settings();
$tds1->ins_colgroup_data_settings();
$thead1 = $tds1->addTHEAD();
$tbody1 = $tds1->addTBODY();
switch($page_mode):
	case PAGE_MODE_VIEW:
		$thead1->c2_titleline(gettext('MariaDB'));
		break;
	case PAGE_MODE_EDIT:
		$thead1->c2($cop->get_enable(),$sphere,false,$is_readonly,gettext('MariaDB'));
		break;
endswitch;
$tbody1->
	c2_textinfo('running',gettext('Service Active'),$is_running_message)->
	c2($cop->get_homedir(),$sphere->row[$cop->get_homedir()->get_name()],true,$is_readonly)->
	c2($cop->get_auxparam(),$sphere,false,$is_readonly);
$tds2 = $content->add_table_data_settings();
$tds2->ins_colgroup_data_settings();
$thead2 = $tds2->addTHEAD()->c2_titleline(sprintf('%s (%s)',gettext('Administrative WebGUI'),gettext('phpMyAdmin')));
$tbody2 = $tds2->addTBODY();
$tbody2->c2($cop->get_phrasecookieauth(),$sphere,false,$is_readonly);
if($is_running):
	$tbody2->c2_textinfo('url1',gettext('URL'),sprintf('<a href="%1$s" id="a_url1" target="_blank">%1$s</a>',htmlspecialchars('/phpMyAdmin/index.php')));
endif;
//	add buttons
$buttons = $document->add_area_buttons();
switch($page_mode):
	case PAGE_MODE_VIEW:
		$buttons->ins_button_edit();
		if($pending_changes && $is_enabled):
			$buttons->ins_button_enadis(!$is_enabled);
		elseif(!$pending_changes):
			$buttons->ins_button_enadis(!$is_enabled);
			$buttons->ins_button_restart($is_enabled);
//			$buttons->ins_button_reload($is_enabled);
		endif;
		break;
	case PAGE_MODE_EDIT:
		$buttons->ins_button_save();
		$buttons->ins_button_cancel();
		break;
endswitch;
/*
//	additional javascript code
$js_code = [];
$js_code[PAGE_MODE_VIEW] = '';
$js_code[PAGE_MODE_EDIT] = '';
//	additional javascript code
$js_on_load = [];
$js_on_load[PAGE_MODE_EDIT] = '';
$js_on_load[PAGE_MODE_VIEW] = '';
//	additional javascript code
$js_document_ready = [];
$js_document_ready[PAGE_MODE_EDIT] = '';
$js_document_ready[PAGE_MODE_VIEW] = '';
//	add additional javascript code
$body->ins_javascript($js_code[$page_mode]);
$body->add_js_on_load($js_on_load[$page_mode]);
$body->add_js_document_ready($js_document_ready[$page_mode]);
 */
//	showtime
$document->render();