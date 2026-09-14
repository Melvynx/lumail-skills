<?php
/**
 * Drop plugin options on uninstall. WordPress loads this only on delete.
 *
 * @package Lumail
 */

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_option('lumail_settings');
