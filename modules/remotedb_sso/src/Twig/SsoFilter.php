<?php

namespace Drupal\remotedb_sso\Twig;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Render\Markup;
use Drupal\filter\Plugin\FilterInterface;
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
   * @return string|\Drupal\Component\Render\MarkupInterface
   *   The text with the SSO filter applied.
   */
  public function applyFilter(string $text): string|MarkupInterface {
    $filter = $this->filterManager->createInstance('remotedb_sso');
    if (!$filter instanceof FilterInterface) {
      throw new \LogicException('Expected remotedb_sso filter plugin.');
    }
    $result = $filter->process($text, 'nl')->getProcessedText();
    return Markup::create($result);
  }

}
