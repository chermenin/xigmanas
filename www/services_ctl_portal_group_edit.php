<?php
/*
	services_ctl_portal_group_edit.php

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
use services\ctld\hub\portal_group\row_toolbox as toolbox;
use services\ctld\hub\portal_group\shared_toolbox;

//	init indicators
$input_errors = [];
$prerequisites_ok = true;
//	preset $savemsg when a reboot is pending
if(file_exists($d_sysrebootreqd_path)):
	$savemsg = get_std_save_message(0);
endif;
//	init properties and sphere
$cop = toolbox::init_properties();
$sphere = toolbox::init_sphere();
$rmo = toolbox::init_rmo();
[$page_method,$page_action,$page_mode] = $rmo->validate();
//	determine page mode and validate resource id
switch($page_method):
	case 'GET':
		switch($page_action):
			case 'add': // bring up a form with default values and let the user modify it
				$sphere->row[$sphere->get_row_identifier()] = $cop->get_row_identifier()->get_defaultvalue();
				break;
			case 'edit': // modify the data of the provided resource id and let the user modify it
				$sphere->row[$sphere->get_row_identifier()] = $cop->get_row_identifier()->validate_input(INPUT_GET);
				break;
		endswitch;
		break;
	case 'POST':
		switch($page_action):
			case 'add': // bring up a form with default values and let the user modify it
				$sphere->row[$sphere->get_row_identifier()] = $cop->get_row_identifier()->get_defaultvalue();
				break;
			case 'cancel': // cancel - nothing to do
				$sphere->row[$sphere->get_row_identifier()] = null;
				break;
			case 'clone':
				$sphere->row[$sphere->get_row_identifier()] = $cop->get_row_identifier()->get_defaultvalue();
				break;
			case 'edit': // edit requires a resource id, get it from input and validate
				$sphere->row[$sphere->get_row_identifier()] = $cop->get_row_identifier()->validate_input();
				break;
			case 'save': // modify requires a resource id, get it from input and validate
				$sphere->row[$sphere->get_row_identifier()] = $cop->get_row_identifier()->validate_input();
				break;
		endswitch;
		break;
endswitch;
/*
 *	exit if $sphere->row[$sphere->row_identifier()] is null
 */
if(is_null($sphere->get_row_identifier_value())):
	header($sphere->get_parent()->get_location());
	exit;
endif;
/*
 *	search resource id in sphere
 */
$sphere->row_id = arr::search_ex($sphere->get_row_identifier_value(),$sphere->grid,$sphere->get_row_identifier());
/*
 *	start determine record update mode
 */
$updatenotify_mode = updatenotify_get_mode($sphere->get_notifier(),$sphere->get_row_identifier_value()); // get updatenotify mode
$record_mode = RECORD_ERROR;
if($sphere->row_id === false): // record does not exist in config
	if(in_array($page_mode,[PAGE_MODE_ADD,PAGE_MODE_CLONE,PAGE_MODE_POST],true)): // ADD or CLONE or POST
		switch($updatenotify_mode):
			case UPDATENOTIFY_MODE_UNKNOWN:
				$record_mode = RECORD_NEW;
				break;
		endswitch;
	endif;
else: // record found in configuration
	if(in_array($page_mode,[PAGE_MODE_EDIT,PAGE_MODE_POST,PAGE_MODE_VIEW],true)): // EDIT or POST or VIEW
		switch($updatenotify_mode):
			case UPDATENOTIFY_MODE_NEW:
				$record_mode = RECORD_NEW_MODIFY;
				break;
			case UPDATENOTIFY_MODE_MODIFIED:
				$record_mode = RECORD_MODIFY;
				break;
			case UPDATENOTIFY_MODE_UNKNOWN:
				$record_mode = RECORD_MODIFY;
				break;
		endswitch;
	endif;
endif;
if($record_mode === RECORD_ERROR): // oops, something went wrong
	header($sphere->get_parent()->get_location());
	exit;
endif;
$isrecordnew = ($record_mode === RECORD_NEW);
$isrecordnewmodify = ($record_mode === RECORD_NEW_MODIFY);
$isrecordmodify = ($record_mode === RECORD_MODIFY);
$isrecordnewornewmodify = ($isrecordnew || $isrecordnewmodify);
/*
 *	end determine record update mode
 */
$cops = [
	$cop->get_enable(),
	$cop->get_name(),
	$cop->get_description(),
	$cop->get_discovery_auth_group(),
	$cop->get_discovery_filter(),
	$cop->get_foreign(),
	$cop->get_offload(),
	$cop->get_redirect(),
	$cop->get_tag(),
	$cop->get_auxparam()
];
//	Add options for discovery auth group from auth groups, ignore enable flag
$ctl_auth_groups = &arr::make_branch($config,'ctld','ctl_auth_group','param');
foreach($ctl_auth_groups as $ctl_auth_group):
	$key = $ctl_auth_group['name'] ?? null;
	if(isset($key)):
		$description = $ctl_auth_group['description'] ?? '';
		if(preg_match('/\S/',$description)):
			$value = sprintf('%s - %s',$key,$description);
		else:
			$value = $key;
		endif;
		$cop->get_discovery_auth_group()->upsert_option($key,$value);
	endif;
endforeach;
switch($page_mode):
	case PAGE_MODE_ADD:
		foreach($cops as $cops_element):
			$sphere->row[$cops_element->get_name()] = $cops_element->get_defaultvalue();
		endforeach;
		break;
	case PAGE_MODE_CLONE:
		foreach($cops as $cops_element):
			$name = $cops_element->get_name();
			$sphere->row[$name] = $cops_element->validate_input() ?? $cops_element->get_defaultvalue();
		endforeach;
//		adjust page mode
		$page_mode = PAGE_MODE_ADD;
		break;
	case PAGE_MODE_EDIT:
		$source = $sphere->grid[$sphere->row_id];
		foreach($cops as $cops_element):
			$name = $cops_element->get_name();
			switch($name):
				case $cop->get_auxparam()->get_name():
					if(array_key_exists($name,$source) && is_array($source[$name])):
						$source[$name] = implode("\n",$source[$name]);
					endif;
					break;
			endswitch;
			$sphere->row[$name] = $cops_element->validate_config($source);
		endforeach;
		break;
	case PAGE_MODE_POST:
//		apply post values that are applicable for all record modes
		foreach($cops as $cops_element):
			$name = $cops_element->get_name();
			$sphere->row[$name] = $cops_element->validate_input();
			if(!isset($sphere->row[$name])):
				$sphere->row[$name] = $_POST[$name] ?? '';
				$input_errors[] = $cops_element->get_message_error();
			endif;
		endforeach;
		if($prerequisites_ok && empty($input_errors)):
			$name = $cop->get_auxparam()->get_name();
			if(array_key_exists($name,$sphere->row)):
				$sphere->row[$name] = array_map(fn($element) => trim($element,"\n\r\t"),explode("\n",$sphere->row[$name]));
			endif;
			$sphere->upsert();
			if($isrecordnew):
				updatenotify_set($sphere->get_notifier(),UPDATENOTIFY_MODE_NEW,$sphere->get_row_identifier_value(),$sphere->get_notifier_processor());
			elseif($updatenotify_mode === UPDATENOTIFY_MODE_UNKNOWN):
				updatenotify_set($sphere->get_notifier(),UPDATENOTIFY_MODE_MODIFIED,$sphere->get_row_identifier_value(),$sphere->get_notifier_processor());
			endif;
			write_config();
			header($sphere->get_parent()->get_location()); // cleanup
			exit;
		endif;
		break;
endswitch;
$sphere->add_page_title($isrecordnew ? gettext('Add') : gettext('Edit'));
$document = new_page($sphere->get_page_title(),$sphere->get_script()->get_scriptname(),'tablesort','sorter-checkbox');
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
$content->add_table_data_settings()->
	ins_colgroup_data_settings()->
	push()->
	addTHEAD()->
		c2($cop->get_enable(),$sphere,false,false,gettext('Configuration'))->
	pop()->
	addTBODY()->
		c2($cop->get_name(),$sphere,true,false)->
		c2($cop->get_description(),$sphere,false,false)->
		c2($cop->get_discovery_auth_group(),$sphere,false,false)->
		c2($cop->get_discovery_filter(),$sphere,false,false)->
		c2($cop->get_foreign(),$sphere,false,false)->
		c2($cop->get_offload(),$sphere,false,false)->
		c2($cop->get_redirect(),$sphere,false,false)->
		c2($cop->get_tag(),$sphere,false,false)->
		c2($cop->get_auxparam(),$sphere,false,false);
if($isrecordmodify):
	$table = $content->add_table_data_settings();
	$table->ins_colgroup_data_settings();
	$thead = $table->addTHEAD();
	$tbody = $table->addTBODY();
	$thead->
		c2_titleline(gettext('Additional Information'));
	$iam = $sphere->row[$cop->get_name()->get_name()];
	$ai1 = toolbox::get_listen_info($iam);
	$tbody->c2($ai1['property'],$ai1['selected'],false,true,true);
	unset($ai1);
	$ai2 = toolbox::get_option_info($iam);
	$tbody->c2($ai2['property'],$ai2['selected'],false,true,true);
	unset($ai2);
endif;
$buttons = $document->add_area_buttons();
if($isrecordnew):
	$buttons->ins_button_add();
else:
	$buttons->ins_button_save();
	if($prerequisites_ok && empty($input_errors)):
		$buttons->ins_button_clone();
	endif;
endif;
$buttons->ins_button_cancel();
$buttons->ins_input_hidden($sphere->get_row_identifier(),$sphere->get_row_identifier_value());
//	additional javascript code
$body->ins_javascript($sphere->get_js());
$body->add_js_on_load($sphere->get_js_on_load());
$body->add_js_document_ready($sphere->get_js_document_ready());
$document->render();
