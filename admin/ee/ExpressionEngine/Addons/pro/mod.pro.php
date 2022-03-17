<?php

/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
*/

class Pro
{
    public function __construct()
    {
    }

    /**
     * Sets allowed cookie
     * example: ?ACT=XX&frontedit=off
     *
     * @return void
     */
    public function setCookie()
    {
        $allowedCookies = ee('Addon')->get('pro')->get('cookies.functionality');
        if (!empty($allowedCookies) && is_array($allowedCookies))
        {
            foreach ($allowedCookies as $cookie) {
                if (ee()->input->get($cookie) != '') {
                    $value = ee('Security/XSS')->clean(ee()->input->get($cookie));
                    ee()->input->set_cookie($cookie, $value, 31104000);
                    ee()->output->send_ajax_response(['success' => true, $cookie => $value]);
                }
            }
        }

        ee()->output->send_ajax_response(['error']);
    }

}
