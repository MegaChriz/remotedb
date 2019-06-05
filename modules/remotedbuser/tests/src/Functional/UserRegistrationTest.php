<?php

namespace Drupal\Tests\remotedbuser\Functional;

use Drupal\user\UserInterface;

/**
 * Test registration of users.
 *
 * @group remotedbuser
 */
class UserRegistrationTest extends RemotedbUserBrowserTestBase {

  /**
   * Tests if user is saved to remote database on a succesful register.
   */
  public function testRegistration() {
    // Don't require e-mail verification and allow registration by site visitors
    // without administrator approval.
    $this->config('user.settings')
      ->set('verify_mail', FALSE)
      ->set('register', UserInterface::REGISTER_VISITORS)
      ->save();

    // Register.
    $edit = [];
    $edit['name'] = $name = $this->randomMachineName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';
    $edit['pass[pass1]'] = $new_pass = $this->randomMachineName();
    $edit['pass[pass2]'] = $new_pass;
    $this->drupalPostForm('user/register', $edit, t('Create new account'));
    $this->assertText(t('Registration successful. You are now logged in.'), 'User registered successfully.');

    // Assert that the local account exists.
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('user');
    $accounts = $storage->loadByProperties(['name' => $name, 'mail' => $mail]);
    $new_user = reset($accounts);
    $this->assertTrue($new_user->isActive(), 'New account is active after registration.');

    // Assert that the remote account exists.
    $remote_account = $this->remotedbUserStorage->loadBy($name, 'name');
    $this->assertNotNull($remote_account, 'A remote account was created.');

    // Assert that the remote account has an uid.
    $this->assertNotNull($remote_account->uid, 'The remote account has an ID.');

    // Assert that remotedb_uid was saved on the new user.
    $this->assertEquals($new_user->remotedb_uid->value, $remote_account->uid, 'The local user account has saved the remote user ID.');
  }

  /**
   * Tests registration failure when username already exists remotely.
   */
  public function testRegistrationNameDuplicates() {
    // Don't require e-mail verification and allow registration by site visitors
    // without administrator approval.
    $this->config('user.settings')
      ->set('verify_mail', FALSE)
      ->set('register', UserInterface::REGISTER_VISITORS)
      ->save();

    // Create a remote user.
    $remote_account = $this->createRemoteUser();

    // Register.
    $edit = [];
    $edit['name'] = $remote_account->name;
    $edit['mail'] = $this->randomMachineName() . '@example.com';
    $this->drupalPostForm('user/register', $edit, 'Create new account');
    $this->assertRaw(t('The name %name is already taken.', ['%name' => $remote_account->name]));
  }

  /**
   * Tests registration failure when mail address already exists remotely.
   */
  public function testRegistrationEmailDuplicates() {
    // Don't require e-mail verification and allow registration by site visitors
    // without administrator approval.
    $this->config('user.settings')
      ->set('verify_mail', FALSE)
      ->set('register', UserInterface::REGISTER_VISITORS)
      ->save();

    // Create a remote user.
    $remote_account = $this->createRemoteUser();

    $edit = [];
    $edit['name'] = $this->randomMachineName();
    $edit['mail'] = $remote_account->mail;

    // Attempt to create a new account using an existing e-mail address.
    $this->drupalPostForm('user/register', $edit, t('Create new account'));
    $this->assertText(t('The e-mail address @email is already registered.', ['@email' => $remote_account->mail]), 'Supplying an exact duplicate email address displays an error message.');

    // Attempt to bypass duplicate email registration validation by adding
    // spaces.
    $edit['mail'] = '   ' . $remote_account->mail . '   ';

    $this->drupalPostForm('user/register', $edit, t('Create new account'));
    $this->assertText(t('The e-mail address @email is already registered.', ['@email' => $remote_account->mail]), 'Supplying a duplicate email address with added whitespace displays an error message.');
  }

}
