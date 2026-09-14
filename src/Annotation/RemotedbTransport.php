<?php

namespace Drupal\remotedb\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a transport plugin annotation object.
 *
 * @Annotation
 */
class RemotedbTransport extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the transport.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * A brief description of the transport.
   *
   * @var \Drupal\Core\Annotation\Translation|string
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

  /**
   * The default settings for the transport.
   *
   * @var array
   */
  public $settings = [];

}
