<?php

declare(strict_types=1);

namespace Drupal\remotedbuser\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Views hook implementations for remotedbuser.
 */
class RemotedbuserViewsHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_views_api().
   */
  #[Hook('views_api')]
  public function viewsApi(): array {
    return [
      'api' => 3,
    ];
  }

  /**
   * Implements hook_views_data().
   */
  #[Hook('views_data')]
  public function viewsData(): array {
    $data = [];
    $data['users']['remotedb_uid'] = [
      'title' => $this->t('Remote database user ID'),
      'help' => $this->t('ID of the user in the remote database.'),
      'field' => [
        'handler' => 'views_handler_field_numeric',
        'click sortable' => TRUE,
      ],
      'filter' => [
        'handler' => 'views_handler_filter_numeric',
      ],
      'sort' => [
        'handler' => 'views_handler_sort',
      ],
    ];

    return $data;
  }

}
