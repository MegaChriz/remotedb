<?php

namespace Drupal\Tests\remotedb_role\Unit\Plugin\Action;

use Drupal\remotedb_role\Plugin\Action\AssignRoles;
use Drupal\remotedb_role\SubscriptionServiceInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\remotedb_role\Plugin\Action\AssignRoles
 * @group remotedb_role
 */
class AssignRolesTest extends UnitTestCase {

  /**
   * The mocked account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->account = $this->createMock('Drupal\user\Entity\User');
  }

  /**
   * Creates a subscription service mock.
   *
   * @param array $subscriptions
   *   The subscriptions by the service to return, keyed by ID.
   *
   * @return \Drupal\remotedb_role\SubscriptionServiceInterface
   *   The mocked subscription service.
   */
  protected function createSubscriptionServiceMock(array $subscriptions) {
    $return = [];
    foreach ($subscriptions as $id => $title) {
      $return[$id] = [
        'subscription_id' => $id,
        'title' => $title,
      ];
    }

    // Mock a subscription service.
    $subscription_service = $this->createMock(SubscriptionServiceInterface::class);
    $subscription_service->expects($this->once())
      ->method('getSubscriptions')
      ->willReturn($return);

    return $subscription_service;
  }

  /**
   * Tests the execute method on a user without a specific role.
   *
   * @covers ::execute
   */
  public function testExecuteAddNonExistingRole() {
    $this->account->expects($this->once())
      ->method('addRole')
      ->with('test_role_1');
    $this->account->expects($this->never())
      ->method('removeRole');
    $this->account->expects($this->once())
      ->method('save');

    $this->account->expects($this->any())
      ->method('hasRole')
      ->with($this->equalTo('test_role_1'))
      ->will($this->returnValue(FALSE));

    // Mock a subscription service.
    $subscription_service = $this->createSubscriptionServiceMock(['1001' => 'Foo']);

    $config = [
      'roles' => [
        'test_role_1' => [
          'status' => TRUE,
          'subscriptions' => [
            '1001',
            '1002',
          ],
        ],
      ],
    ];

    $plugin = new AssignRoles($config, 'remotedb_role_assign_roles', ['type' => 'user'], $subscription_service);
    $plugin->execute($this->account);
  }

  /**
   * Tests the execute method on a user with a role.
   *
   * @covers ::execute
   */
  public function testExecuteAddExistingRole() {
    $this->account->expects($this->never())
      ->method('addRole');
    $this->account->expects($this->never())
      ->method('removeRole');
    $this->account->expects($this->never())
      ->method('save');

    $this->account->expects($this->any())
      ->method('hasRole')
      ->with($this->equalTo('test_role_1'))
      ->will($this->returnValue(TRUE));

    // Mock a subscription service.
    $subscription_service = $this->createSubscriptionServiceMock(['1001' => 'Foo']);

    $config = [
      'roles' => [
        'test_role_1' => [
          'status' => TRUE,
          'subscriptions' => [
            '1001',
            '1002',
          ],
        ],
      ],
    ];

    $plugin = new AssignRoles($config, 'remotedb_role_assign_roles', ['type' => 'user'], $subscription_service);
    $plugin->execute($this->account);
  }

  /**
   * Tests removing a role for an user that has that role.
   *
   * @covers ::execute
   */
  public function testExecuteRemoveExistingRole() {
    $this->account->expects($this->once())
      ->method('removeRole');
    $this->account->expects($this->never())
      ->method('addRole');
    $this->account->expects($this->once())
      ->method('save');

    $this->account->expects($this->any())
      ->method('hasRole')
      ->with($this->equalTo('test_role_1'))
      ->will($this->returnValue(TRUE));

    // Mock a subscription service.
    $subscription_service = $this->createSubscriptionServiceMock([]);

    $config = [
      'roles' => [
        'test_role_1' => [
          'status' => TRUE,
          'subscriptions' => [
            '1001',
            '1002',
          ],
        ],
      ],
    ];

    $plugin = new AssignRoles($config, 'remotedb_role_assign_roles', ['type' => 'user'], $subscription_service);
    $plugin->execute($this->account);
  }

  /**
   * Tests the execute method on a user without a specific role.
   *
   * @covers ::execute
   */
  public function testExecuteRemoveNonExistingRole() {
    $this->account->expects($this->never())
      ->method('removeRole');
    $this->account->expects($this->never())
      ->method('addRole');
    $this->account->expects($this->never())
      ->method('save');

    $this->account->expects($this->any())
      ->method('hasRole')
      ->with($this->equalTo('test_role_1'))
      ->will($this->returnValue(FALSE));

    // Mock a subscription service.
    $subscription_service = $this->createSubscriptionServiceMock([]);

    $config = [
      'roles' => [
        'test_role_1' => [
          'status' => TRUE,
          'subscriptions' => [
            '1001',
            '1002',
          ],
        ],
      ],
    ];

    $plugin = new AssignRoles($config, 'remotedb_role_assign_roles', ['type' => 'user'], $subscription_service);
    $plugin->execute($this->account);
  }

  /**
   * Test adding roles with two subscriptions for the same role.
   *
   * When two subscriptions would result into the same role being added, the
   * role should only be added once.
   *
   * @covers ::execute
   */
  public function testExecuteWithTwoSubscriptionsForSameRole() {
    $this->account->expects($this->once())
      ->method('addRole')
      ->with('test_role_1');
    $this->account->expects($this->never())
      ->method('removeRole');
    $this->account->expects($this->once())
      ->method('save');

    $this->account->expects($this->any())
      ->method('hasRole')
      ->with($this->equalTo('test_role_1'))
      ->will($this->returnValue(FALSE));

    // Mock a subscription service.
    $subscription_service = $this->createSubscriptionServiceMock([
      '1001' => 'Foo',
      '1002' => 'Bar',
    ]);

    $config = [
      'roles' => [
        'test_role_1' => [
          'status' => TRUE,
          'subscriptions' => [
            '1001',
            '1002',
          ],
        ],
      ],
    ];

    $plugin = new AssignRoles($config, 'remotedb_role_assign_roles', ['type' => 'user'], $subscription_service);
    $plugin->execute($this->account);
  }

  /**
   * Tests adding/removing roles at the same time.
   */
  public function testExecuteWithMultipleSubscriptions() {
    $this->account->expects($this->once())
      ->method('removeRole')
      ->with('test_role_1');
    $this->account->expects($this->once())
      ->method('addRole')
      ->with('test_role_2');
    $this->account->expects($this->once())
      ->method('save');

    $this->account->expects($this->any())
      ->method('hasRole')
      ->will($this->returnCallback(function ($rid) {
        switch ($rid) {
          case 'test_role_1':
            return TRUE;

          case 'test_role_2':
            return FALSE;

          case 'test_role_3':
            return FALSE;
        }
      }));

    // Mock a subscription service.
    $subscription_service = $this->createSubscriptionServiceMock(['1003' => 'Qux']);

    $config = [
      'roles' => [
        'test_role_1' => [
          'status' => TRUE,
          'subscriptions' => [
            '1001',
          ],
        ],
        'test_role_2' => [
          'status' => TRUE,
          'subscriptions' => [
            '1003',
            '1004',
          ],
        ],
        'test_role_3' => [
          'status' => TRUE,
          'subscriptions' => [
            '1005',
            '1006',
          ],
        ],
      ],
    ];

    $plugin = new AssignRoles($config, 'remotedb_role_assign_roles', ['type' => 'user'], $subscription_service);
    $plugin->execute($this->account);
  }

}
