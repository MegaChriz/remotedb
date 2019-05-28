<?php

namespace Drupal\Tests\remotedb_sso\Functional;

/**
 * Tests for single sign on.
 *
 * @group remotedb_sso
 */
class SsoTest extends RemotedbSsoBrowserTestBase {

  /**
   * Tests if an authenticated user is redirected to the right page.
   */
  public function testAuthenticatedRedirect() {
    // Create a remote user.
    $remote_account = $this->createRemoteUser();

    // Login using information from remote account.
    $edit = [
      'name' => $remote_account->name,
      'pass' => $remote_account->pass_raw,
    ];
    $this->drupalPostForm('user', $edit, t('Log in'));

    // Follow a link to an "external" site.
    $ext_url = $this->getAbsoluteUrl('user');
    $site = preg_replace('/^https?\:\/\/([^\/]+)\/.*/', '\\1', $ext_url);
    $url = $this->urlGenerator->createSsoGotoUrl($site, $ext_url);

    // Assert that the generated url contains "sso/goto".
    $this->assertContains('sso/goto', $url);

    // Follow url and assert that the user got on his account page.
    $this->drupalGet($url);
    $this->assertText($remote_account->name);
  }

  /**
   * Tests if an anonymous user is redirected to the right page.
   */
  public function _testAnonymousRedirect() {
    // Log out current user if there is one logged in.
    if ($this->loggedInUser) {
      $this->drupalLogout();
    }

    // Follow a link to an "external" site.
    $ext_url = $this->getAbsoluteUrl('user');
    $site = preg_replace('/^https?\:\/\/([^\/]+)\/.*/', '\\1', $ext_url);
    $url = $this->urlGenerator->createSsoGotoUrl($site, $ext_url);

    // Follow url.
    $this->drupalGet($url);
    $this->assertText('Username');
    $this->assertText('Password');
  }

  /**
   * Tests if an authenticated user gets logged in when following a SSO link.
   */
  public function _testSsoLogin() {
    // Create a remote user.
    $remote_account = $this->createRemoteUser();

    // Copy over the account to the local database.
    $account = $remote_account->toAccount();
    $account->save();

    // Generate a ticket.
    $ticket = $this->ticketService->getTicket($account);

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
  public function _testSsoLoginNewUser() {
    // Create a remote user.
    $remote_account = $this->createRemoteUser();

    // Create fake account to generate ticket for.
    $account = new stdClass();
    $account->remotedb_uid = $remote_account->uid;

    // Generate a ticket.
    $ticket = $this->ticketService->getTicket($account);

    // Generate the url to follow.
    $url = $this->getAbsoluteUrl('sso/login/') . $ticket . '/user';

    // Follow url and assert that the user got on his account page.
    $this->drupalGet($url);
    $this->assertText($remote_account->name);
  }

  /**
   * Tests if the user is redirected to a 404 page in case of a invalid SSO url.
   */
  public function _testInvalidSso() {
    $this->drupalGet('sso/goto/www.example.com');
    $this->assertResponse(404);
  }

}
