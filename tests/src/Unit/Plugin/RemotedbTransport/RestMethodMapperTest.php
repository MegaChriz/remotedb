<?php

namespace Drupal\Tests\remotedb\Unit\Plugin\RemotedbTransport;

use Drupal\remotedb\Plugin\RemotedbTransport\RestMethodMapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests REST method-to-route mapping.
 */
#[CoversClass(RestMethodMapper::class)]
#[Group('remotedb')]
class RestMethodMapperTest extends TestCase {

  /**
   * Tests positional dbuser.retrieve params become path and query.
   */
  public function testDbuserRetrieve(): void {
    $mapper = new RestMethodMapper();
    $mapped = $mapper->map('dbuser.retrieve', ['user@example.com', 'mail']);
    $this->assertSame('GET', $mapped['http_method']);
    $this->assertSame('/rest/dbuser/user%40example.com', $mapped['path']);
    $this->assertSame(['argtype' => 'mail'], $mapped['query']);
    $this->assertNull($mapped['body']);
  }

  /**
   * Tests dbuser.save uses the first parameter as the JSON body.
   */
  public function testDbuserSaveRawBody(): void {
    $mapper = new RestMethodMapper();
    $account = ['name' => 'alice', 'mail' => 'a@example.com'];
    $mapped = $mapper->map('dbuser.save', [$account]);
    $this->assertSame('POST', $mapped['http_method']);
    $this->assertSame('/rest/dbuser', $mapped['path']);
    $this->assertSame($account, $mapped['body']);
  }

  /**
   * Tests core user.login maps onto name/pass and the session response type.
   */
  public function testUserLoginCoreRoute(): void {
    $mapper = new RestMethodMapper();
    $mapped = $mapper->map('user.login', [
      'username' => 'service',
      'password' => 'secret',
    ]);
    $this->assertSame('POST', $mapped['http_method']);
    $this->assertSame('/user/login', $mapped['path']);
    $this->assertSame(['_format' => 'json'], $mapped['query']);
    $this->assertSame(['name' => 'service', 'pass' => 'secret'], $mapped['body']);
    $this->assertSame('session', $mapped['response']);
  }

  /**
   * Tests webhook delete uses the positional ID in the path.
   */
  public function testWebhookDelete(): void {
    $mapper = new RestMethodMapper();
    $mapped = $mapper->map('kkbservices_webhook.delete', [12]);
    $this->assertSame('DELETE', $mapped['http_method']);
    $this->assertSame('/rest/kkbservices_webhook/12', $mapped['path']);
  }

  /**
   * Tests afasuser.retrieve puts the uid in the path.
   */
  public function testAfasuserRetrieve(): void {
    $mapper = new RestMethodMapper();
    $mapped = $mapper->map('afasuser.retrieve', [42]);
    $this->assertSame('GET', $mapped['http_method']);
    $this->assertSame('/rest/afasuser/42', $mapped['path']);
    $this->assertNull($mapped['body']);
  }

  /**
   * Tests afasuser.getcontacts sends uid and exclude_primary.
   */
  public function testAfasuserGetcontacts(): void {
    $mapper = new RestMethodMapper();
    $mapped = $mapper->map('afasuser.getcontacts', [42, TRUE]);
    $this->assertSame('POST', $mapped['http_method']);
    $this->assertSame('/rest/afasuser/getcontacts', $mapped['path']);
    $this->assertSame(['uid' => 42, 'exclude_primary' => TRUE], $mapped['body']);
  }

  /**
   * Tests unknown methods raise.
   */
  public function testUnknownMethod(): void {
    $this->expectException(\InvalidArgumentException::class);
    (new RestMethodMapper())->map('cronapi.run', ['job']);
  }

}
