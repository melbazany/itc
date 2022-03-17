<?php

/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
*/

use ExpressionEngine\Service\JumpMenu\AbstractJumpMenu;

class Pro_jump extends AbstractJumpMenu
{

    protected static $items = [
        'cookies' => [
            'icon' => 'fa-wrench',
            'command' => 'cookie_settings',
            'command_title' => 'cookie_settings',
            'dynamic' => false,
            'addon' => false,
            'target' => 'cookies',
            'permission' => 'super_admin'
        ]
    ];

    public function getItems()
    {
        if (!IS_PRO || !ee('pro:Access')->hasValidLicense() || !ee('Permission')->canUsePro()) {
            return [];
        }
        return self::$items;
    }

}
