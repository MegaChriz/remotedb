<?php

declare(strict_types=1);

namespace Drupal\remotedb_role\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * General hook implementations for remotedb_role.
 */
class RemotedbRoleHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help(string $route_name, RouteMatchInterface $route_match): ?string {
    if ($route_name === 'remotedb_role.admin_settings_form') {
      $output = '<p>' . $this->t("On this page you can configure which roles a user should be assigned when they own a certain subscription. <strong>Warning</strong>: when a user doesn't own one of the specified subscriptions for a certain role, this role will be automatically revoked for that user.") . '</p>';
      $output .= '<p>' . $this->t('The roles are assigned/unassigned upon user login.') . '</p>';
      return $output;
    }
    return NULL;
  }

}
