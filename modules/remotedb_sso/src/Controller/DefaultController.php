<?php /**
 * @file
 * Contains \Drupal\remotedb_sso\Controller\DefaultController.
 */

namespace Drupal\remotedb_sso\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Default controller for the remotedb_sso module.
 */
class DefaultController extends ControllerBase {

  public function remotedb_sso_login($remotedb_uid, $timestamp, $hashed_pass) {
    $user = \Drupal::currentUser();

    $target_path = remotedb_sso_target_path(func_get_args(), 3);
    $options = [];

    if (!empty($_GET)) {
      $query = $_GET;
      unset($query['q']);
    }
    if (!empty($query)) {
      $options['query'] = $query;
    }

    if ($user->uid) {
      // An user is already logged in, so ignore the attempt and go to the target
    // url.
      drupal_goto($target_path, $options);
    }

    if (!$remotedb_uid) {
      // The user anonymous, so ignore the attempt and go to the target url.
      drupal_goto($target_path, $options);
    }

    try {
      $ticket_service = Util::getTicketService();
      if ($ticket_service) {
        $remote_account = $ticket_service->validateTicket($remotedb_uid, $timestamp, $hashed_pass);
        if ($remote_account) {
          // Ticket is valid. Update account data in local database.
          $account = $remote_account->toAccount();
          $account->save();

          // Reload the user's account object to ensure a full user object is
          // passed along to the various hooks.
          $account = // @FIXME
            // To reset the user cache, use EntityStorageInterface::resetCache().
\Drupal::entityManager()->getStorage('user')->load($account->uid);

          // Now login the user.
          $user = $account;
          user_login_finalize();
        }
      }
    }
    

      catch (RemotedbException $e) {
      // Log any remote database exceptions.
      $e->logError(WATCHDOG_WARNING);
    }
    drupal_goto($target_path, $options);
  }

  public function remotedb_sso_goto() {
    $user = \Drupal::currentUser();

    if (empty($_GET['site'])) {
      return MENU_NOT_FOUND;
    }

    try {
      // Request a ticket from the central database.
      $ticket_service = Util::getTicketService();
      if ($ticket_service) {
        $site = $_GET['site'];
        $path = '';
        if (!empty($_GET['path'])) {
          $path = '/' . $_GET['path'];
        }
        $ticket = $ticket_service->getTicket($user);
        // Generate target path.
        $target_path = remotedb_sso_target_path(func_get_args());
        // Redirect.
        $url = 'http://' . $site . '/sso/login/' . $ticket . $path;
        drupal_goto($url);
      }
    }
    
      catch (RemotedbException $e) {
      $e->printMessage();
      return '';
    }
  }

}
