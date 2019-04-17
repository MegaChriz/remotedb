<?php

namespace Drupal\remotedb\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an authentication method annotation object.
 *
 * @Annotation
 */
class RemotedbAuthentication extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the provider that owns the authentication method.
   *
   * @var string
   */
  public $provider;

  /**
   * The human-readable name of the authentication method.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $title;

  /**
   * A brief description of the authentication method.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation (optional)
   */
  public $description = '';

  /**
   * A default weight used for presentation in the user interface only.
   *
   * @var int (optional)
   */
  public $weight = 0;

  /**
   * Whether this method is enabled or disabled by default.
   *
   * @var bool (optional)
   */
  public $status = FALSE;

  /**
   * The default settings for the method.
   *
   * @var array (optional)
   */
  public $settings = [];

}
