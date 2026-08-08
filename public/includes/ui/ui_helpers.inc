<?php

/*
 * Displays an error and terminates the page if $posted_id doesn't match $cart_id,
 * indicating the session was replaced by opening a second browser tab.
 */

use App\Foundation\Framework\DTO\ValidationResult;

function check_cart_edit_conflict(string $posted_id, string $cart_id): void
{
    global $Ajax, $SysPrefs;

    if (!$posted_id || $posted_id === $cart_id) return;
    if (isset($SysPrefs->no_check_edit_conflicts) && $SysPrefs->no_check_edit_conflicts) return;

    display_error(__('This edit session has been abandoned by opening this document in another browser tab. You cannot edit more than one document at once.'));
    $Ajax->activate('_page_body');
    display_footer_exit();
}

function display_validation_error_and_set_focus(ValidationResult $result)
{
    if ($result->error) display_error($result->error);
    if ($result->field) set_focus($result->field);
}