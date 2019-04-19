<?php

namespace Drupal\remotedbuser\Tests;

use Drupal\remotedbuser\Tests\RemotedbUserTestBase;

class UserRegistrationTestCase extends RemotedbUserTestBase {
  public static function getInfo() {
    return array(
      'name' => 'User: Registration',
      'description' => 'Test registration of users.',
      'group' => 'Remote database',
    );
  }

  /**
   * Tests if user is saved to remote database on a succesful register.
   */
  public function testRegistration() {
    // Don't require e-mail verification.
    // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// variable_set('user_email_verification', FALSE);


    // Allow registration by site visitors without administrator approval.
    // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// variable_set('user_register', USER_REGISTER_VISITORS);


    // Register.
    $edit = array();
    $edit['name'] = $name = $this->randomName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';
    $edit['pass[pass1]'] = $new_pass = $this->randomName();
    $edit['pass[pass2]'] = $new_pass;
    $this->drupalPost('user/register', $edit, t('Create new account'));

    // Assert the account exists local.
    $accounts = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(array('name' => $name, 'mail' => $mail));
    $new_user = reset($accounts);
    $this->assertNotNull($new_user, 'The account was created succesfully.');

    // Assert the remote account exists.
    $remote_account = $this->controller->loadBy($name, 'name');
    $this->assertNotNull($remote_account, 'A remote account was created.');

    if ($remote_account && $new_user) {
      // Assert that the remote account has an uid.
      $this->assertNotNull($remote_account->uid, 'The remote account has an ID.');

      // Assert that remotedb_uid was saved on the new user.
      $this->assertEqual($new_user->remotedb_uid, $remote_account->uid, 'The local user account has save the remote user ID.');
    }
  }

  /**
   * Tests if registration fails if the user's name already exists remotely.
   */
  public function testRegistrationNameDuplicates() {
    // Don't require e-mail verification.
    // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// variable_set('user_email_verification', FALSE);


    // Allow registration by site visitors without administrator approval.
    // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// variable_set('user_register', USER_REGISTER_VISITORS);


    // Create a remote user.
    $remote_account = $this->remotedbCreateRemoteUser();

    // Register.
    $edit = array();
    $edit['name'] = $remote_account->name;
    $edit['mail'] = $this->randomName() . '@example.com';
    $this->drupalPost('user/register', $edit, t('Create new account'));
    $this->assertRaw(t('The name %name is already taken.', array('%name' => $remote_account->name)));
  }

  /**
   * Tests if registration fails if the user's mail address already exists remotely.
   */
  public function testRegistrationEmailDuplicates() {
    // Don't require e-mail verification.
    // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// variable_set('user_email_verification', FALSE);


    // Allow registration by site visitors without administrator approval.
    // @FIXME
// // @FIXME
// // This looks like another module's variable. You'll need to rewrite this call
// // to ensure that it uses the correct configuration object.
// variable_set('user_register', USER_REGISTER_VISITORS);


    // Create a remote user.
    $remote_account = $this->remotedbCreateRemoteUser();

    $edit = array();
    $edit['name'] = $this->randomName();
    $edit['mail'] = $remote_account->mail;

    // Attempt to create a new account using an existing e-mail address.
    $this->drupalPost('user/register', $edit, t('Create new account'));
    $this->assertText(t('The e-mail address @email is already registered.', array('@email' => $remote_account->mail)), 'Supplying an exact duplicate email address displays an error message.');

    // Attempt to bypass duplicate email registration validation by adding spaces.
    $edit['mail'] = '   ' . $remote_account->mail . '   ';

    $this->drupalPost('user/register', $edit, t('Create new account'));
    $this->assertText(t('The e-mail address @email is already registered.', array('@email' => $remote_account->mail)), 'Supplying a duplicate email address with added whitespace displays an error message.');
  }
}
