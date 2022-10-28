<?php

namespace Drupal\Tests\remotedb_sso\Unit\Filter;

use Drupal\Core\Config\ConfigBase;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\remotedb_sso\Plugin\Filter\SsoFilter;
use Drupal\remotedb_sso\TicketServiceInterface;
use Drupal\remotedb_sso\UrlInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Test if the SSO Filter works as expected.
 *
 * @coversDefaultClass \Drupal\remotedb_sso\Plugin\Filter\SsoFilter
 * @group remotedb_sso
 */
class SsoFilterTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The text to filter.
   *
   * @var string
   */
  const TEXT = 'At http://www.example.com there is a place at <a href="http://www.example2.com/subsite/place">.';

  /**
   * The current active user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $currentUser;

  /**
   * The remotedb_sso configuration.
   *
   * @var \Drupal\Core\Config\ConfigBase|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $config;

  /**
   * The SSO url generator.
   *
   * @var \Drupal\remotedb_sso\UrlInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $urlGenerator;

  /**
   * The service for requesting tickets from the remote database.
   *
   * @var \Drupal\remotedb_sso\TicketServiceInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $ticketService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->currentUser = $this->prophesize(AccountProxyInterface::class);
    $this->config = $this->prophesize(ConfigBase::class);
    $this->urlGenerator = $this->prophesize(UrlInterface::class);
    $this->ticketService = $this->prophesize(TicketServiceInterface::class);
  }

  /**
   * Creates a new SSO filter.
   *
   * @param array $configuration
   *   (optional) The filter's configuration.
   */
  protected function createFilter(array $configuration = []) {
    $filter = new SsoFilter($configuration, 'remotedb_sso', ['provider' => 'test'], $this->currentUser->reveal(), $this->config->reveal(), $this->urlGenerator->reveal(), $this->ticketService->reveal());
    $filter->setStringTranslation($this->getStringTranslationStub());

    return $filter;
  }

  /**
   * @covers ::process
   */
  public function testProcess() {
    $this->currentUser->isAnonymous()->willReturn(FALSE);
    $this->config->get()->shouldNotBeCalled();
    $this->urlGenerator->createSsoGotoUrl(Argument::type('string'), Argument::type('string'))->will(function ($args) {
      return str_replace($args[0], 'foo', $args[1]);
    })->shouldBeCalledTimes(3);

    $configuration['settings'] = [
      'websites' => "www.example.com\nwww.example2.com/subsite\nwww.example3.com",
    ];
    $expected = new FilterProcessResult('At http://foo there is a place at <a href="http://foo/place">.');
    $actual = $this->createFilter($configuration)->process(static::TEXT, 'und');
    $this->assertEquals($expected, $actual);
  }

  /**
   * Test processing using the remotedb_sso configuration.
   *
   * @covers ::process
   */
  public function testProcessWithSsoConfig() {
    $this->currentUser->isAnonymous()->willReturn(FALSE);
    $this->config->get('websites')->willReturn([
      'www.example.com',
      'www.example2.com/subsite',
      'www.example3.com',
    ]);
    $this->urlGenerator->createSsoGotoUrl(Argument::type('string'), Argument::type('string'))->will(function ($args) {
      return str_replace($args[0], 'foo', $args[1]);
    })->shouldBeCalledTimes(3);

    $expected = new FilterProcessResult('At http://foo there is a place at <a href="http://foo/place">.');
    $actual = $this->createFilter()->process(static::TEXT, 'und');
    $this->assertEquals($expected, $actual);
  }

  /**
   * Tests that text does not change when the current user is anonymous.
   *
   * @covers ::process
   */
  public function testProcessWithAnonymousUser() {
    $this->currentUser->isAnonymous()->willReturn(TRUE);
    $this->config->get()->shouldNotBeCalled();
    $this->urlGenerator->createSsoGotoUrl()->shouldNotBeCalled();

    $expected = new FilterProcessResult(static::TEXT);
    $actual = $this->createFilter()->process(static::TEXT, 'und');
    $this->assertEquals($expected, $actual);
  }

  /**
   * Tests that text does not change when the ticket service is not available.
   *
   * @covers ::process
   */
  public function testProcessWithoutTicketService() {
    $this->currentUser->isAnonymous()->shouldNotBeCalled();
    $this->config->get()->shouldNotBeCalled();
    $this->urlGenerator->createSsoGotoUrl()->shouldNotBeCalled();

    $filter = new SsoFilter([], 'remotedb_sso', ['provider' => 'test'], $this->currentUser->reveal(), $this->config->reveal(), $this->urlGenerator->reveal(), NULL);

    $expected = new FilterProcessResult(static::TEXT);
    $actual = $filter->process(static::TEXT, 'und');
    $this->assertEquals($expected, $actual);
  }

}
