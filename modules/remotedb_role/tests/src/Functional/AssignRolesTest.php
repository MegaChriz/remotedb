<?php

namespace Drupal\Tests\remotedb_role\Functional;

/**
 * Test assigning/unassigning roles upon login.
 *
 * @group remotedb_role
 */
class AssignRolesTest extends RemotedbRoleBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createRole([], 'qux');
  }

  /**
   * Sets return value for the subscription service.
   *
   * @param array $subscriptions
   *   The subscriptions by the service to return, keyed by ID.
   */
  protected function setSubscriptions(array $subscriptions) {
    $return = [];
    foreach ($subscriptions as $id => $title) {
      $return[$id] = [
        'subscription_id' => $id,
        'title' => $title,
      ];
    }

    \Drupal::state()->set('remotedb_role_subscriptions', $return);
  }

  /**
   * Test logging in without the debug option enabled.
   */
  public function testWithoutDebug() {
    $this->config('remotedb_role.settings')
      ->set('remotedb', $this->remotedb->id())
      ->set('roles', [
        'foo' => [
          'status' => TRUE,
          'subscriptions' => [
            '1001',
            '1002',
          ],
        ],
        'foo_bar' => [
          'status' => TRUE,
          'subscriptions' => [
            '1003',
            '1004',
          ],
        ],
      ])
      ->set('debug', FALSE)
      ->save();

    // Create an account that has the role 'foo'.
    $account = $this->drupalCreateUser([], NULL, FALSE, [
      'roles' => ['foo'],
    ]);

    // Let the subscription service return subscription 1003.
    $this->setSubscriptions(['1003' => 'FooBar']);

    $this->drupalLogin($account);

    // Assert added and removed roles.
    $account = $this->reloadEntity($account);
    $this->assertTrue($account->hasRole('foo_bar'));
    $this->assertFalse($account->hasRole('foo'));

    $this->assertSession()->pageTextNotContains('Assigned');
    $this->assertSession()->pageTextNotContains('Unassigned');
  }

  /**
   * Test logging in with the debug option enabled.
   *
   * @dataProvider dataProviderWithDebug
   */
  public function testWithDebug(array $roles, array $subscriptions, array $has_roles, array $not_has_roles, array $texts) {
    $this->config('remotedb_role.settings')
      ->set('remotedb', $this->remotedb->id())
      ->set('roles', [
        'foo' => [
          'status' => TRUE,
          'subscriptions' => [
            '1001',
            '1002',
          ],
        ],
        'foo_bar' => [
          'status' => TRUE,
          'subscriptions' => [
            '1003',
            '1004',
          ],
        ],
      ])
      ->set('debug', TRUE)
      ->save();

    // Create an account that has the role 'foo'.
    $account = $this->drupalCreateUser([], NULL, FALSE, [
      'roles' => $roles,
    ]);

    // Set subscriptions.
    $this->setSubscriptions($subscriptions);

    $this->drupalLogin($account);

    // Assert added and removed roles.
    $account = $this->reloadEntity($account);
    foreach ($has_roles as $rid) {
      $this->assertTrue($account->hasRole($rid));
    }
    foreach ($not_has_roles as $rid) {
      $this->assertFalse($account->hasRole($rid));
    }

    // Assert debug texsts.
    foreach ($texts as $text) {
      $this->assertSession()->pageTextContains($text);
    }
  }

  /**
   * Data provider for ::testWithDebug().
   */
  public function dataProviderWithDebug() {
    return [
      'role_removed_role_added' => [
        'roles' => ['foo', 'qux'],
        'subscriptions' => ['1003' => 'FooBar'],
        'has_roles' => ['foo_bar', 'qux'],
        'not_has_roles' => ['foo'],
        'texts' => [
          'Assigned: foo_bar',
          'Unassigned: foo',
        ],
      ],
      'two_roles_added' => [
        'roles' => [],
        'subscriptions' => ['1001' => 'Foo', '1003' => 'FooBar'],
        'has_roles' => ['foo', 'foo_bar'],
        'not_has_roles' => [],
        'texts' => [
          'Assigned: foo, foo_bar',
          'Unassigned: none',
        ],
      ],
      'two_roles_removed' => [
        'roles' => ['foo', 'foo_bar', 'qux'],
        'subscriptions' => [],
        'has_roles' => ['qux'],
        'not_has_roles' => ['foo', 'foo_bar'],
        'texts' => [
          'Assigned: none',
          'Unassigned: foo, foo_bar',
        ],
      ],
      'no_changes' => [
        'roles' => ['foo', 'qux'],
        'subscriptions' => ['1001' => 'Foo'],
        'has_roles' => ['foo', 'qux'],
        'not_has_roles' => ['foo_bar'],
        'texts' => [
          'Assigned: none',
          'Unassigned: none',
        ],
      ],
    ];
  }

  /**
   * Tests that roles are left untouched for users with bypass permission.
   */
  public function testWithBypassPermission() {
    // Create a role with bypass permission.
    $bypass_rid = $this->createRole(['remotedb_role.bypass']);

    // Create an account with this role and the role 'foo'.
    $account = $this->drupalCreateUser([], NULL, FALSE, [
      'roles' => ['foo', $bypass_rid],
    ]);

    // Setup config.
    $this->config('remotedb_role.settings')
      ->set('remotedb', $this->remotedb->id())
      ->set('roles', [
        'foo' => [
          'status' => TRUE,
          'subscriptions' => [
            '1001',
            '1002',
          ],
        ],
        'foo_bar' => [
          'status' => TRUE,
          'subscriptions' => [
            '1003',
            '1004',
          ],
        ],
      ])
      ->set('debug', FALSE)
      ->save();

    // Let the subscription service return subscription 1003.
    $this->setSubscriptions(['1003' => 'FooBar']);

    $this->drupalLogin($account);

    // Assert that the user still has the same roles.
    $account = $this->reloadEntity($account);
    $this->assertTrue($account->hasRole($bypass_rid));
    $this->assertTrue($account->hasRole('foo'));
    $this->assertFalse($account->hasRole('foo_bar'));
  }

}
