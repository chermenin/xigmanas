<?php
/*
	shared_toolbox.php

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

namespace system\webgui;

use common\arr;
use common\sphere as mys;
use DOMDocument;

use function updatenotify_clear;

/**
 *	Wrapper class for autoloading functions
 */
class shared_toolbox {
	private const NOTIFICATION_NAME = __NAMESPACE__;
	private const NOTIFICATION_PROCESSOR = 'process_notification';
/**
 *	Process notifications
 *	@param int $mode
 *	@param string $data
 *	@return int
 */
	public static function process_notification(int $mode,string $data) {
		$retval = 0;
		$sphere = setting_toolbox::init_sphere();
		updatenotify_clear($sphere->get_notifier(),$data);
		return $retval;
	}
/**
 *	Configure shared sphere settings
 *	@global array $config
 *	@param mys\root $sphere
 */
	public static function init_sphere(mys\root $sphere) {
		global $config;

		$sphere->
			set_notifier(self::NOTIFICATION_NAME)->
			set_notifier_processor(sprintf('%s::%s',self::class,self::NOTIFICATION_PROCESSOR))->
			set_enadis(false)->
			add_page_title(gettext('System'),gettext('General'),gettext('WebGUI'));
		$sphere->grid = &arr::make_branch($config,'system','webgui');
	}
/**
 *	Add the tab navigation menu of this sphere
 *	@param DOMDocument $document
 *	@return int
 */
	public static function add_tabnav(DOMDocument $document) {
		$retval = 0;
		$document->
			add_area_tabnav()->
				add_tabnav_upper()->
					ins_tabnav_record('system.php',gettext('General'))->
					ins_tabnav_record('system_webgui.php',gettext('WebGUI'),gettext('Reload page'),true)->
					ins_tabnav_record('system_password.php',gettext('Password'));
		return $retval;
	}
}
