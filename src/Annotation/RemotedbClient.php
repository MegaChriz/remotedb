<?php

namespace Drupal\remotedb\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a way to interact with a server.
 *
 * @Annotation
 */
class RemotedbClient extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the client.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A brief description of the client.
   *
   * @var \Drupal\Core\Annotation\Translation|string
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

}
