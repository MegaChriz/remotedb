<?php

namespace Drupal\remotedb_sso\Plugin\Filter;

use Drupal\Core\Config\ConfigBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\remotedb\Exception\RemotedbException;
use Drupal\remotedb_sso\TicketServiceInterface;
use Drupal\remotedb_sso\UrlInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a filter to transform external urls into sso links.
 *
 * @Filter(
 *   id = "remotedb_sso",
 *   title = @Translation("SSO Link filter"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   settings = {
 *     "websites" = ""
 *   }
 * )
 */
class SsoFilter extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The current active user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The remotedb_sso configuration.
   *
   * @var \Drupal\Core\Config\ConfigBase
   */
  protected $config;

  /**
   * The SSO url generator.
   *
   * @var \Drupal\remotedb_sso\UrlInterface
   */
  protected $urlGenerator;

  /**
   * The service for requesting tickets from the remote database.
   *
   * @var \Drupal\remotedb_sso\TicketServiceInterface|null
   */
  protected $ticketService;

  /**
   * Constructs a new Sso object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current active user.
   * @param \Drupal\Core\Config\ConfigBase $config
   *   The remotedb_sso configuration.
   * @param \Drupal\remotedb_sso\UrlInterface $url_generator
   *   The SSO url generator.
   * @param \Drupal\remotedb_sso\TicketServiceInterface $ticket_service
   *   (optional) The service for requesting tickets from the remote database.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountProxyInterface $current_user, ConfigBase $config, UrlInterface $url_generator, TicketServiceInterface $ticket_service = NULL) {
    $this->currentUser = $current_user;
    $this->config = $config;
    $this->urlGenerator = $url_generator;
    $this->ticketService = $ticket_service;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    try {
      $ticket_service = $container->get('remotedb_sso.ticket');
    }
    catch (RemotedbException $e) {
      // Ignore remotedb exceptions.
      $ticket_service = NULL;
    }

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('config.factory')->get('remotedb_sso.settings'),
      $container->get('remotedb_sso.url'),
      $ticket_service
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['websites'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Websites'),
      '#description' => $this->t('Specify to which external websites an SSO link automatically must created, one on each line. Omit the http://, but include the subdomain if necessary, such as "www".') . ' ' . $this->t('Leave empty to use the defaults which can be set at @remotedb_sso_settings_url page.', [
        '@remotedb_sso_settings_url' => Link::createFromRoute($this->t('SSO settings'), 'remotedb_sso.admin_settings_form')->toString(),
      ]),
      '#default_value' => $this->settings['websites'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    if (empty($this->ticketService)) {
      // Bail out if the ticket service is not available.
      return new FilterProcessResult($text);
    }

    // Do not rewrite links if the user is not logged in.
    if ($this->currentUser->isAnonymous()) {
      return new FilterProcessResult($text);
    }

    try {
      if (!empty($this->settings['websites'])) {
        $sites = $this->settings['websites'];
        $sites = explode("\n", $sites);
      }
      else {
        $sites = $this->config->get('websites');
      }

      foreach ($sites as $site) {
        $text = $this->urlGenerator->createSsoGotoUrl($site, $text);
      }
    }
    catch (RemotedbException $e) {
      // Ignore any remote database exceptions.
    }

    return new FilterProcessResult($text);
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    return $this->t('SSO links to certain external websites will be automatically created.');
  }

}
