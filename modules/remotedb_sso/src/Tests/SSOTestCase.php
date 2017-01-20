<?php

/**
 * @file
 * Contains \Drupal\remotedb_sso\Tests\SSOTestCase.
 */

namespace Drupal\remotedb_sso\Tests;

use \stdClass;
use Drupal\remotedb_sso\Url;
use Drupal\remotedb_sso\Util;

/**
 * Test for single sign on.
 */
class SSOTestCase extends RemotedbSSOTestBase {
  public static function getInfo() {
    return array(
      'name' => 'SSO: Base',
      'description' => 'Test if single sign-on functionality works as expected.',
      'group' => 'Remote database',
    );
  }

  /**
   * Tests if an authenticated user is redirected to the right page.
   */
  public function testAuthenticatedRedirect() {
    // Create a remote user.
    $remote_account = $this->remotedbCreateRemoteUser();

    // Login using information from remote account.
    $edit = array(
      'name' => $remote_account->name,
      'pass' => $remote_account->pass_raw
    );
    $this->drupalPost('user', $edit, t('Log in'));

    // Follow a link to an "external" site.
    $ext_url = $this->getAbsoluteUrl('user');
    $site = preg_replace('/^http\:\/\/([^\/]+)\/.*/', '\\1', $ext_url);
    $url = Url::createSSOGotoUrl($site, $ext_url);

    // Follow url and assert that the user got on his account page.
    $this->drupalGet($url);
    $this->assertText($remote_account->name);
  }

  /**
   * Tests if an anonymous user is redirected to the right page.
   */
  public function testAnonymousRedirect() {
    // Log out current user if there is one logged in.
    if ($this->loggedInUser) {
      $this->drupalLogout();
    }

    // Follow a link to an "external" site.
    $ext_url = $this->getAbsoluteUrl('user');
    $site = preg_replace('/^http\:\/\/([^\/]+)\/.*/', '\\1', $ext_url);
    $url = Url::createSSOGotoUrl($site, $ext_url);

    // Follow url.
    $this->drupalGet($url);
    $this->assertText('Username');
    $this->assertText('Password');
  }

  /**
   * Tests if an authenticated user gets logged in when following a SSO link.
   */
  public function testSSOLogin() {
    // Create a remote user.
    $remote_account = $this->remotedbCreateRemoteUser();

    // Copy over the account to the local database.
    $account = $remote_account->toAccount();
    entity_save('user', $account);

    // Generate a ticket.
    $ticket_service = Util::getTicketService();
    $ticket = $ticket_service->getTicket($account);

    // Generate the url to follow.
    $url = $this->getAbsoluteUrl('sso/login/') . $ticket . '/user';

    // Follow url and assert that the user got on his account page.
    $this->drupalGet($url);
    $this->assertText($remote_account->name);
  }

  /**
   * Tests if an authenticated user gets logged in when following a SSO link
   * even when he didn't had an account on the website yet.
   */
  public function testSSOLoginNewUser() {
    // Create a remote user.
    $remote_account = $this->remotedbCreateRemoteUser();

    // Create fake account to generate ticket for.
    $account = new stdClass();
    $account->remotedb_uid = $remote_account->uid;

    // Generate a ticket.
    $ticket_service = Util::getTicketService();
    $ticket = $ticket_service->getTicket($account);

    // Generate the url to follow.
    $url = $this->getAbsoluteUrl('sso/login/') . $ticket . '/user';

    // Follow url and assert that the user got on his account page.
    $this->drupalGet($url);
    $this->assertText($remote_account->name);
  }

  /**
   * Tests if the user is redirected to a 404 page in case of a invalid SSO url.
   */
  public function testInvalidSSO() {
    $this->drupalGet('sso/goto/www.example.com');
    $this->assertResponse(404);
  }
}
