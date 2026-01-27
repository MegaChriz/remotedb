<?php

namespace Drupal\remotedb\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a RemotedbAuthentication attribute object.
 *
 * @see \Drupal\remotedb\Plugin\AuthenticationInterface
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class RemotedbAuthentication extends Plugin {

  /**
   * Constructs a RemotedbAuthentication attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $title
   *   The human-readable name of the authentication method.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|string $description
   *   A brief description of the authentication method. Defaults to empty.
   * @param int $weight
   *   A default weight used for presentation in the UI. Defaults to 0.
   * @param bool $status
   *   Whether this method is enabled or disabled by default. Defaults to FALSE.
   * @param array $settings
   *   The default settings for the method. Defaults to empty array.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $title = NULL,
    public readonly TranslatableMarkup|string $description = '',
    public readonly int $weight = 0,
    public readonly bool $status = FALSE,
    public readonly array $settings = [],
    public readonly ?string $deriver = NULL,
  ) {}

}
