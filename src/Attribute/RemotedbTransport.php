<?php

namespace Drupal\remotedb\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a RemotedbTransport attribute object.
 *
 * @see \Drupal\remotedb\Plugin\RemotedbTransportInterface
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class RemotedbTransport extends Plugin {

  /**
   * Constructs a RemotedbTransport attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $title
   *   The human-readable name of the transport.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|string $description
   *   A brief description of the transport. Defaults to empty.
   * @param array $settings
   *   The default settings for the transport. Defaults to empty array.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $title = NULL,
    public readonly TranslatableMarkup|string $description = '',
    public readonly array $settings = [],
    public readonly ?string $deriver = NULL,
  ) {}

}
