<?php

namespace Drupal\remotedb_sso\Filter;

use stdClass;
use Drupal\remotedb\Exception\RemotedbException;
use Drupal\remotedb_sso\Url;
use Drupal\remotedb_sso\Util;

/**
 * Provides a filter to limit allowed HTML tags.
 *
 * @Filter(
 *   id = "remotedb_sso",
 *   title = @Translation("SSO Link filter"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   settings = {
 *     "websites" = ""
 *   }
 * )
 */
class Sso {
  /**
   * An associative array containing the configured settings of this filter.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * {@inheritdoc}
   */
  public function __construct($filter) {
    $this->settings = $filter->settings;
  }

  /**
   * Creates SSO links from links to certain external websites using the global
   * settings.
   *
   * @param string $text
   *   The text to filter.
   *
   * @return string
   *   The filtered text.
   */
  public static function processDefault($text) {
    $filter = new stdClass();
    $filter->settings = [];
    $sso_filter = new static($filter);
    return $sso_filter->process($text);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, $form_state) {
    $element = [];
    // @FIXME
    // l() expects a Url object, created from a route name or external URI.
    // $element['websites'] = [
    //       '#type' => 'textarea',
    //       '#title' => t('Websites'),
    //       '#description' => t('Specify to which external websites an SSO link automatically must created, one on each line. Omit the http://, but include the subdomain if necassery, such as "www".') . ' ' . t('Leave empty to use the defaults which can be set at !remotedb_sso_settings_url page.', ['!remotedb_sso_settings_url' => l(t('RemoteDB settings'), 'admin/config/services/remotedb/sso')]),
    //       '#default_value' => $this->settings['websites'],
    //     ];
    return $element;
  }

  /**
   * Creates SSO links from links to certain external websites.
   *
   * @param string $text
   *   The text to filter.
   *
   * @return string
   *   The filtered text.
   */
  public function process($text) {
    if (\Drupal::currentUser()->isAnonymous()) {
      // Do not rewrite links if the user is not logged in.
      return $text;
    }

    try {
      // Check if the ticket service is available.
      $ticket_service = Util::getTicketService();
      if (!$ticket_service) {
        // No ticket service available. Abort.
        return $text;
      }

      if (!empty($this->settings['websites'])) {
        $sites = $this->settings['websites'];
      }
      else {
        $sites = Util::variableGet('websites');
      }

      $sites = explode("\n", $sites);

      // @FIXME
      // url() expects a route name or an external URI.
      // $sso_url = url('sso/goto/', ['absolute' => TRUE]);
      foreach ($sites as $site) {
        $text = Url::createSSOGotoUrl($site, $text);
      }
    }
    catch (RemotedbException $e) {
      // Ignore any remote database exceptions.
    }
    return $text;
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return t('SSO links to certain external websites will be automatically created.');
  }

}
