<?php

/**
 * ExpressionEngine Pro
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
*/

namespace ExpressionEngine\Addons\Pro\Service\Access;

/**
 * Frontend edit service
 */
class Access
{

    protected static $hasValidLicense = null;

    public function __construct()
    {
    }

    /**
     * Check if user has edit permission for given channel/entry
     */
    public function hasFrontEditPermission($channel_id, $entry_id)
    {
        $has_permission = ee('Permission')->can('edit_other_entries_channel_id_' . $channel_id);
        if (!$has_permission) {
            $author_id = ee()->db->select('author_id')
                ->where('entry_id', $entry_id)
                ->where('channel_id', $channel_id)
                ->where('author_id', ee()->session->userdata('member_id'))
                ->get('channel_titles');
            if ($author_id->num_rows() > 0) {
                $has_permission = ee('Permission')->can('edit_self_entries_channel_id_' . $channel_id);
            }
        }

        return $has_permission;
    }

    /**
     * Check if user has edit permission for any of provided channel/entry
     * if caching is enabled, we'll do a lot of guessing here
     */
    public function hasAnyFrontEditPermission() {
        if (ee('Permission')->isSuperAdmin()) {
            return true;
        }

        //the cache below is not reliable since it only had data from the last query
        //so we return true if they have at least single channel permission
        if (ee('Permission')->hasAny('can_edit_other_entries', 'can_edit_self_entries')) {
            return true;
        }

        ee()->session->cache['disable_frontedit'] = true;
        return false;

        $channel_ids = isset(ee()->session->cache['channel']['channel_ids']) ? ee()->session->cache['channel']['channel_ids'] : [];
        $entry_ids = isset(ee()->session->cache['channel']['entry_ids']) ? ee()->session->cache['channel']['entry_ids'] : [];

        // if we don't have $channel_ids this most likely means the request is cached
        // let's grab ALL channels then since we're not particularly sure which one to check against
        if (empty($channel_ids) && empty($entry_ids)) {
            $channel_ids = ee('Model')->get('Channel')->fields('channel_id')->all()->pluck('channel_id');
        }

        $has_permission = false;
        if (!empty($channel_ids)) {
            foreach ($channel_ids as $channel_id) {
                $has_permission = ee('Permission')->can('edit_other_entries_channel_id_' . $channel_id);
                if ($has_permission) {
                    return $has_permission;
                }
            }
        }

        if (!empty($entry_ids)) {
            $check_q = ee()->db->select('channel_id')
                ->where_in('entry_id', $entry_ids)
                ->where('author_id', ee()->session->userdata('member_id'))
                ->get('channel_titles');
            if ($check_q->num_rows() > 0) {
                foreach ($check_q->result_array() as $row) {
                    $has_permission = ee('Permission')->can('edit_self_entries_channel_id_' . $row['channel_id']);
                    if ($has_permission) {
                        return $has_permission;
                    }
                }
            }
        }

        return $has_permission;
    }

    /**
     * Checks whether member can use the Dock
     *
     * @return boolean Dock access allowed
     */
    public function hasDockPermission()
    {
        if (ee()->config->item('enable_dock') !== false && ee()->config->item('enable_dock') != 'y') {
            return false;
        }

        if (ee('Permission')->canUsePro()) {
            return true;
        }

        return false;
    }

    /**
     * Checks whether license/subscription is valid and active
     * 
     * @param bool $showAlert whether to show alert in CP if license is not valid
     *
     * @return boolean the license is valid and active
     */
    public function hasValidLicense($showAlert = false)
    {
        if (is_null(static::$hasValidLicense)) {
            $addon = ee('Addon')->get('pro');
            $licenseResponse = $addon->checkCachedLicenseResponse();
            switch ($licenseResponse) {
                case 'valid':
                case 'trial':
                case 'update_available':
                    static::$hasValidLicense = true;
                    break;
                
                case 'na':
                    $this->logLicenseError('pro_license_error_na', $showAlert);
                    static::$hasValidLicense = false;
                    break;

                case 'invalid':
                    $this->logLicenseError('pro_license_error_invalid', $showAlert);
                    static::$hasValidLicense = false;
                    break;

                case 'expired':
                    $this->logLicenseError('pro_license_error_expired', $showAlert);
                    static::$hasValidLicense = false;
                    break;

                default:
                    static::$hasValidLicense = false;
                    break;
            }
        }
        return static::$hasValidLicense;
    }

    /**
     * Checks whether front-end editing links should be injected
     *
     * @return boolean
     */
    public function shouldInjectLinks() {
        if ($this->hasValidLicense() && $this->hasDockPermission() && $this->hasAnyFrontEditPermission() && ee()->input->cookie('frontedit') != 'off') {
            return true;
        }
        return false;
    }

    /**
     * Log license error to developer log and display alert in CP
     *
     * @param [type] $message
     * @return void
     */
    private function logLicenseError($message, $showAlert = false)
    {
        ee()->load->library('logger');
        ee()->lang->load('addons');
        ee()->lang->load('pro', ee()->session->get_language(), false, true, PATH_ADDONS . 'pro/');
        $message = sprintf(lang('pro_license_check_instructions'), lang($message));
        ee()->logger->developer($message, true);
        if (REQ == 'CP' && $showAlert) {
            ee('CP/Alert')->makeBanner('pro-license-error')
                ->asIssue()
                ->canClose()
                ->withTitle(lang('license_error'))
                ->addToBody($message)
                ->now();
        }
    }

}

// EOF
