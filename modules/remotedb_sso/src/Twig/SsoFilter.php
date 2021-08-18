<?php

namespace Drupal\remotedb_sso\Twig;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Render\Markup;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Provides a filter to transform external urls into sso links.
 */
class SsoFilter extends AbstractExtension {

  /**
   * The filter plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $filterManager;

  /**
   * Constructs a new SsoFilter object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $filter_manager
   *   The filter plugin manager.
   */
  public function __construct(PluginManagerInterface $filter_manager) {
    $this->filterManager = $filter_manager;
  }

  /**
   * Returns the available twig filters that this extension provides.
   *
   * @return \Twig\TwigFilter[]
   *   A list of twig filters.
   */
  public function getFilters() {
    return [
      new TwigFilter('filter_sso', [$this, 'applyFilter']),
    ];
  }

  /**
   * Applies SSO filter to the given text.
   *
   * @param string $text
   *   The text to apply the filter on.
   *
   * @return string
   *   The text with the SSO filter applied.
   */
  public function applyFilter($text) {
    $filter = $this->filterManager->createInstance('remotedb_sso');
    $result = $filter->process($text, 'nl')
      ->getProcessedText();

    if (is_string($result)) {
      return Markup::create($result);
    }
    return $result;
  }

}
