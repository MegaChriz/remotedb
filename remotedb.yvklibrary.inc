<?php

/**
 * @file
 * Hooks for yvklibrary module.
 */

/**
 * Implements hook_yvklibrary_make_testsite().
 */
function remotedb_yvklibrary_make_testsite() {
  // Set db in staging mode.
  if (variable_get('remotedb_mode') == REMOTEDB_LIVE) {
    variable_set('remotedb_mode', REMOTEDB_STAGING);
  }
}