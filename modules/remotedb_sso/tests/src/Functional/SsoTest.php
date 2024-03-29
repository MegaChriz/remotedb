<?php

namespace Drupal\Tests\remotedb_sso\Functional;

use Drupal\user\Entity\User;

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
    $this->drupalGet('user');
    $this->submitForm($edit, t('Log in'));

    // Follow a link to an "external" site.
    $ext_url = $this->getAbsoluteUrl('user');
    $site = $this->getAbsoluteUrl('');
    // Remove https from it.
    $site = preg_replace('/^https?\:\/\//', '', $site);
    $url = $this->urlGenerator->createSsoGotoUrl($site, $ext_url);

    // Assert that the generated url contains "sso/goto".
    $this->assertStringContainsString('sso/goto', $url);

    // Follow url and assert that the user got on their account page.
    $this->drupalGet($url);
    $this->assertSession()->pageTextContains($remote_account->name);
  }

  /**
   * Tests if an anonymous user is redirected to the right page.
   */
  public function testAnonymousRedirect() {
    // Follow a link to an "external" site.
    $ext_url = $this->getAbsoluteUrl('user');
    $site = $this->getAbsoluteUrl('');
    // Remove https from it.
    $site = preg_replace('/^https?\:\/\//', '', $site);
    $url = $this->urlGenerator->createSsoGotoUrl($site, $ext_url);

    // Follow url.
    $this->drupalGet($url);
    $this->assertSession()->pageTextContains('Username');
    $this->assertSession()->pageTextContains('Password');
  }

  /**
   * Tests if an authenticated user gets logged in when following a SSO link.
   */
  public function testSsoLogin() {
    // Create a remote user.
    $remote_account = $this->createRemoteUser();

    // Copy over the account to the local database.
    $account = $remote_account->toAccount();
    $account->save();

    // Generate a ticket.
    $ticket = $this->ticketService->getTicket($account);

    // Generate the url to follow.
    $url = $this->getAbsoluteUrl('sso/login/') . $ticket . '/user';

    // Follow url and assert that the user got on their account page.
    $this->drupalGet($url);
    $this->assertSession()->pageTextContains($remote_account->name);
  }

  /**
   * Tests logging in a new remote user using a SSO link.
   *
   * When an user that only exists remotely, an user account is expected to be
   * created locally and a login should be successful.
   */
  public function testSsoLoginNewUser() {
    // Create a remote user.
    $remote_account = $this->createRemoteUser();

    // Create a fake account to generate a ticket for.
    $account = User::create([
      'name' => $remote_account->name,
      'mail' => $remote_account->mail,
      'remotedb_uid' => $remote_account->uid,
    ]);

    // Generate a ticket.
    $ticket = $this->ticketService->getTicket($account);

    // Generate the url to follow.
    $url = $this->getAbsoluteUrl('sso/login/') . $ticket . '/user';

    // Follow url and assert that the user got on their account page.
    $this->drupalGet($url);
    $this->assertSession()->pageTextContains($remote_account->name);
  }

  /**
   * Tests if the user is redirected to a 404 page in case of a invalid SSO url.
   */
  public function testInvalidSso() {
    $this->drupalGet('sso/goto/www.example.com');
    $this->assertSession()->statusCodeEquals(404);
  }

}
