<?php

namespace Drupal\Tests\remotedb_webhook\Functional;

use Behat\Mink\Driver\GoutteDriver;
use Drupal\Tests\remotedb\Functional\RemotedbBrowserTestBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a base class for Remotedb Webhook functional tests.
 */
abstract class RemotedbWebhookBrowserTestBase extends RemotedbBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'remotedb',
    'remotedb_test',
    'remotedb_webhook',
  ];

  /**
   * Sends a post request to the webhook endpoint url.
   *
   * @param array $edit
   *   The data to post to the webhook.
   *
   * @return string
   *   The retrieved HTML string, also available as $this->getRawContent().
   */
  protected function webhookPost(array $edit) {
    $this->prepareRequest();

    $url = $this->buildUrl($this->getWebhookEndpointPath(), ['absolute' => TRUE]);

    $this->getSession()->getDriver()->getClient()->request('POST', $url, $edit);
    $out = $this->getSession()->getPage()->getContent();

    // Log only for JavascriptTestBase tests because for Goutte we log with
    // ::getResponseLogHandler.
    if ($this->htmlOutputEnabled && !($this->getSession()->getDriver() instanceof GoutteDriver)) {
      $html_output = 'POST request to: ' . $url .
        '<hr />Ending URL: ' . $this->getSession()->getCurrentUrl();
      $html_output .= '<hr />' . $out;
      $html_output .= $this->getHtmlOutputHeaders();
      $this->htmlOutput($html_output);
    }

    return $out;
  }

  /**
   * Generates path for webhook endpoint.
   *
   * @return string
   *   The webhook endpoint path.
   */
  protected function getWebhookEndpointPath() {
    $key = $this->container->get('remotedb_webhook.webhook')->getKey();
    return 'remotedb/webhook/' . $key;
  }

}
