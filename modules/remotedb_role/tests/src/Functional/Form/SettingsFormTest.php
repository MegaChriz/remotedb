<?php

namespace Drupal\Tests\remotedb_role\Functional\Form;

use Drupal\Tests\remotedb_role\Functional\RemotedbRoleBrowserTestBase;

/**
 * @coversDefaultClass \Drupal\remotedb_role\Form\SettingsForm
 * @group remotedb_role
 */
class SettingsFormTest extends RemotedbRoleBrowserTestBase {

  /**
   * A test user with administrative privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create an user with admin privileges.
    $this->adminUser = $this->drupalCreateUser([], NULL, FALSE, [
      'roles' => ['admin'],
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests filling in settings form for the first time.
   */
  public function testNew() {
    $edit = [
      'remotedb' => $this->remotedb->id(),
      'roles[foo_bar][status]' => 1,
      'roles[foo_bar][subscriptions]' => "123\n\r456",
    ];
    $this->drupalPostForm('admin/config/services/remotedb/roles', $edit, 'Save configuration');

    // Assert the created configuration.
    $expected = [
      'remotedb' => $this->remotedb->id(),
      'roles' => [
        'admin' => [
          'status' => FALSE,
          'subscriptions' => [],
        ],
        'foo' => [
          'status' => FALSE,
          'subscriptions' => [],
        ],
        'foo_bar' => [
          'status' => TRUE,
          'subscriptions' => [
            '123',
            '456',
          ],
        ],
      ],
      'debug' => FALSE,
    ];
    $config = $this->config('remotedb_role.settings')->getRawData();
    $this->assertSame($expected, $config);
  }

  /**
   * Tests editing existing configuration.
   */
  public function testEditSettings() {
    // Create config.
    $this->config('remotedb_role.settings')
      ->set('remotedb', $this->remotedb->id())
      ->set('roles', [
        'foo_bar' => [
          'status' => TRUE,
          'subscriptions' => [
            '123',
            '456',
          ],
        ],
      ])
      ->save();

    $this->drupalPostForm('admin/config/services/remotedb/roles', [], 'Save configuration');

    // Assert that the configuration stayed the same.
    $expected = [
      'remotedb' => $this->remotedb->id(),
      'roles' => [
        'admin' => [
          'status' => FALSE,
          'subscriptions' => [],
        ],
        'foo' => [
          'status' => FALSE,
          'subscriptions' => [],
        ],
        'foo_bar' => [
          'status' => TRUE,
          'subscriptions' => [
            '123',
            '456',
          ],
        ],
      ],
      'debug' => FALSE,
    ];
    $config = $this->config('remotedb_role.settings')->getRawData();
    $this->assertSame($expected, $config);
  }

}
