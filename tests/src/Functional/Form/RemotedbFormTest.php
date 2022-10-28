<?php

namespace Drupal\Tests\remotedb\Functional\Form;

use Drupal\remotedb\Entity\Remotedb;
use Drupal\Tests\remotedb\Functional\RemotedbBrowserTestBase;

/**
 * Tests adding and editing remote database entities.
 *
 * @group remotedb
 */
class RemotedbFormTest extends RemotedbBrowserTestBase {

  /**
   * The user that may administer remote database settings.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $remotedbAdminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->remotedbAdminUser = $this->drupalCreateUser(['remotedb.administer']);
  }

  /**
   * Ensure anonymous users cannot add/edit remote databases.
   */
  public function testNoAccess() {
    $this->drupalGet('admin/config/services/remotedb');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalGet('admin/config/services/remotedb/add');
    $this->assertSession()->statusCodeEquals(403);

    $remotedb = $this->createRemotedb();
    $this->drupalGet(sprintf('admin/config/services/remotedb/manage/%s', $remotedb->id()));
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests adding a new remote database.
   *
   * @dataProvider remotedbDataProvider
   */
  public function testAddRemotedb($expected, $edit) {
    $this->drupalLogin($this->remotedbAdminUser);

    $edit += [
      'label' => 'Foo',
      'name' => 'foo',
    ];
    $this->drupalGet('admin/config/services/remotedb/add');
    $this->submitForm($edit, 'Save');

    $expected += [
      'label' => 'Foo',
      'name' => 'foo',
      'url' => '',
      'status' => TRUE,
      'dependencies' => [],
      'authentication_methods' => [],
      'langcode' => 'en',
    ];
    $data = Remotedb::load('foo')->toArray();
    unset($data['uuid']);
    $this->assertEquals($expected, $data);

    $this->assertSession()->pageTextContains('Added remote database Foo.');
  }

  /**
   * Data provider for ::testAddRemotedb().
   */
  public function remotedbDataProvider() {
    return [
      'url_only' => [
        ['url' => 'http://www.example.com'],
        ['url' => 'http://www.example.com'],
      ],
      'csrf' => [
        [
          'authentication_methods' => [
            'csrf' => [
              'status' => TRUE,
              'weight' => 0,
              'id' => 'csrf',
              'provider' => 'remotedb',
              'settings' => [],
            ],
          ],
        ],
        ['authentication_methods[csrf][status]' => TRUE],
      ],
      'login' => [
        [
          'authentication_methods' => [
            'login' => [
              'status' => TRUE,
              'weight' => 0,
              'id' => 'login',
              'provider' => 'remotedb',
              'settings' => [
                'username' => 'bar',
                'password' => 'foobar',
              ],
            ],
          ],
        ],
        [
          'authentication_methods[login][status]' => TRUE,
          'authentication_methods[login][settings][username]' => 'bar',
          'authentication_methods[login][settings][password]' => 'foobar',
        ],
      ],
      'all' => [
        [
          'url' => 'http://www.example.com',
          'authentication_methods' => [
            'csrf' => [
              'status' => TRUE,
              'weight' => 1,
              'id' => 'csrf',
              'provider' => 'remotedb',
              'settings' => [],
            ],
            'login' => [
              'status' => TRUE,
              'weight' => 0,
              'id' => 'login',
              'provider' => 'remotedb',
              'settings' => [
                'username' => 'bar',
                'password' => 'foobar',
              ],
            ],
          ],
        ],
        [
          'url' => 'http://www.example.com',
          'authentication_methods[csrf][status]' => TRUE,
          'authentication_methods[csrf][weight]' => 1,
          'authentication_methods[login][status]' => TRUE,
          'authentication_methods[login][settings][username]' => 'bar',
          'authentication_methods[login][settings][password]' => 'foobar',
          'authentication_methods[login][weight]' => 0,
        ],
      ],
    ];
  }

  /**
   * Tests editing a remote database.
   */
  public function testEditRemotedb() {
    $this->drupalLogin($this->remotedbAdminUser);

    $remotedb = $this->createRemotedb([
      'label' => 'Foo',
      'name' => 'foo',
      'url' => 'http://www.example.com',
      'authentication_methods' => [
        'csrf' => [
          'status' => TRUE,
          'weight' => 1,
        ],
        'login' => [
          'status' => TRUE,
          'weight' => 0,
          'settings' => [
            'username' => 'bar',
            'password' => 'foobar',
          ],
        ],
      ],
    ]);

    // Post form without changing anything.
    $this->drupalGet('admin/config/services/remotedb/manage/foo');
    $this->submitForm([], 'Save');

    $expected = [
      'label' => 'Foo',
      'name' => 'foo',
      'url' => 'http://www.example.com',
      'authentication_methods' => [
        'csrf' => [
          'status' => TRUE,
          'weight' => 1,
          'id' => 'csrf',
          'provider' => 'remotedb',
          'settings' => [],
        ],
        'login' => [
          'status' => TRUE,
          'weight' => 0,
          'id' => 'login',
          'provider' => 'remotedb',
          'settings' => [
            'username' => 'bar',
            'password' => 'foobar',
          ],
        ],
      ],
      'status' => TRUE,
      'dependencies' => [],
      'langcode' => 'en',
    ];
    $remotedb = $this->reloadEntity($remotedb);
    $data = $remotedb->toArray();
    unset($data['uuid']);
    $this->assertEquals($expected, $data);

    $this->assertSession()->pageTextContains('Updated remote database Foo.');
  }

}
