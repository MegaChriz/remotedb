<?php
/**
 * @file
 * Test cases for RemoteDB SSO.
 * @todo Candidate for SimpleTest?
 */

function remotedb_test_ticket() {
  $user = user_load(8);
  $ticket = remotedb_sso_generate_ticket($user);
  print $ticket . "\n";

  $ticket_data = explode('/', $ticket);

  $remotedb_uid = $ticket_data[0];
  $timestamp = $ticket_data[1];
  $hashed_pass = $ticket_data[2];

  print_r($ticket_data) . "\n";

  $account = RemoteDB::get()->sendRequest('ticket.validate', $remotedb_uid, $timestamp, $hashed_pass)->getResult();

  print_r($account) . "\n";
}
