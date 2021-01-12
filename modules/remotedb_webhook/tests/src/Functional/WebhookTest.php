<?php

namespace Drupal\Tests\remotedb_webhook\Functional;

/**
 * Tests posting to webhook.
 *
 * @group remotedb_webhook
 */
class WebhookTest extends RemotedbWebhookBrowserTestBase {

  /**
   * The key to use for webhook endpoints.
   *
   * @var string|null
   */
  protected $webhookKey = NULL;

  /**
   * Tests successful requests.
   *
   * @param array $edit
   *   The data to post to the webhook endpoint url.
   *
   * @dataProvider dataProviderWebhookPost
   */
  public function testWebhookPost(array $edit) {
    $this->webhookPost($edit);
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Data provider for ::testPostWithUnsufficientData().
   */
  public function dataProviderWebhookPost() {
    return [
      'data-as-string' => [
        ['type' => 'foo__bar', 'data' => 'baz'],
      ],
      'data-as-array' => [
        ['type' => 'foo__bar', 'data' => ['baz']],
      ],
    ];
  }

  /**
   * Tests accessing the webhook endpoint url with no post data.
   */
  public function testGet() {
    $this->drupalGet($this->getWebhookEndpointPath());
    $this->assertSession()->statusCodeEquals(400, 'Remote database Webhook Endpoint.');
  }

  /**
   * Tests posting to webhook url with missing parameters.
   *
   * @param array $edit
   *   The data to post to the webhook endpoint url.
   *
   * @dataProvider dataProviderPostWithUnsufficientData
   */
  public function testPostWithUnsufficientData(array $edit) {
    $this->webhookPost($edit);
    $this->assertSession()->statusCodeEquals(400, "Remote database Webhook Endpoint, but missing post data for 'type' or 'data'.");
  }

  /**
   * Data provider for ::testPostWithUnsufficientData().
   */
  public function dataProviderPostWithUnsufficientData() {
    return [
      'empty' => [
        [],
      ],
      'empty-type' => [
        ['type' => '', 'data' => 'foo'],
      ],
      'empty-data' => [
        ['type' => 'foo__bar', 'data' => ''],
      ],
      'missing-type' => [
        ['data' => 'foo'],
      ],
      'missing-data' => [
        ['type' => 'foo__bar'],
      ],
    ];
  }

  /**
   * Tests with malformed type parameter.
   */
  public function testMalformedTypeParameter() {
    $this->webhookPost([
      'type' => ['foo__bar'],
      'data' => 'bar',
    ]);
    $this->assertSession()->statusCodeEquals(400, "The parameter 'type' should be a string.");
  }

  /**
   * Tests posting with an invalid key.
   */
  public function testPostWithInvalidKey() {
    $this->webhookKey = 'foo';
    $this->webhookPost([
      'type' => 'foo__bar',
      'data' => 'baz',
    ]);
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * {@inheritdoc}
   */
  protected function getWebhookEndpointPath() {
    if (empty($this->webhookKey)) {
      return parent::getWebhookEndpointPath();
    }
    return 'remotedb/webhook/' . $this->webhookKey;
  }

}
