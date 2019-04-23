<?php

namespace Drupal\remotedbuser\Exception;

use Drupal\remotedb\Exception\RemotedbException;

/**
 * Thrown when there is a conflict between the remote and local user storage.
 */
class RemotedbExistingUserException extends RemotedbException {}
