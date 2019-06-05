<?php

namespace Drupal\Tests\remotedb_webhook\Kernel;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\user\UserInterface;

/**
 * Tests the webhook service.
 *
 * @coversDefaultClass \Drupal\remotedb_webhook\Webhook
 * @group remotedb_webhook
 */
class WebhookTest extends RemotedbWebhookKernelTestBase {

  use AssertMailTrait;

  /**
   * The webhook service.
   *
   * @var \Drupal\remotedb_webhook\Webhook
   */
  protected $webhookService;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->webhookService = $this->container->get('remotedb_webhook.webhook');
  }

  /**
   * Tests processing an user update that does not exists locally.
   *
   * No users should be added.
   *
   * @covers ::process
   */
  public function testProcessNonExistingUserAccount() {
    // Create a remote user.
    $remote_user = $this->createRemoteUser();

    $this->webhookService->process('user__update', $remote_user->uid);

    // Assert that the remote user does not exist locally.
    $this->assertFalse(user_load_by_name($remote_user->name));
  }

  /**
   * Tests processing an user update.
   *
   * @covers ::process
   */
  public function testProcessUserAccountUpdate() {
    // Create a remote user.
    $remote_user = $this->createRemoteUser();

    // Create an account linked to this remote user.
    $account = $this->createUser([
      'name' => 'ipsum',
      'remotedb_uid' => $remote_user->uid,
    ]);

    // Update remote user.
    $remote_user->name = 'lorem';
    $remote_user->mail = 'lorem@example.com';
    $remote_user->save();

    // Process update hook.
    $this->webhookService->process('user__update', $remote_user->uid);

    // Reload original account.
    $account = $this->reloadEntity($account);
    // Assert expected values.
    $expected_values = [
      'name' => 'lorem',
      'mail' => 'lorem@example.com',
      'status' => 1,
      'remotedb_uid' => $remote_user->uid,
    ];
    foreach ($expected_values as $key => $expected_value) {
      $this->assertEquals($expected_value, $account->{$key}->value);
    }
  }

  /**
   * Tests sending a new user a welcome mail.
   *
   * @covers ::process
   */
  public function testProcessNewUserWelcomeEmail() {
    // Create a remote user.
    $remote_user = $this->createRemoteUser();

    // Process welcome email hook.
    $this->webhookService->process('user__welcome_email', $remote_user->uid);

    // Assert that an user has been created.
    $account = user_load_by_name($remote_user->name);
    $this->assertInstanceOf(UserInterface::class, $account);

    // Assert that a welcome email was sent.
    $this->assertNotEmpty($this->getMails(['key' => 'register_admin_created']));
  }

  /**
   * Tests sending an existing user a welcome mail.
   *
   * @covers ::process
   */
  public function testProcessExistingUserWelcomeEmail() {
    // Create a remote user.
    $remote_user = $this->createRemoteUser();

    // Create an account linked to this remote user.
    $account = $this->createUser([
      'name' => 'ipsum',
      'remotedb_uid' => $remote_user->uid,
    ]);

    // Update remote user.
    $remote_user->name = 'lorem';
    $remote_user->mail = 'lorem@example.com';
    $remote_user->save();

    // Process welcome email hook.
    $this->webhookService->process('user__welcome_email', $remote_user->uid);

    // Reload original account.
    $account = $this->reloadEntity($account);
    // Assert expected values.
    $expected_values = [
      'name' => 'lorem',
      'mail' => 'lorem@example.com',
      'status' => 1,
      'remotedb_uid' => $remote_user->uid,
    ];
    foreach ($expected_values as $key => $expected_value) {
      $this->assertEquals($expected_value, $account->{$key}->value);
    }

    // Assert that a welcome email was sent.
    $this->assertNotEmpty($this->getMails(['key' => 'register_admin_created']));
  }

}
