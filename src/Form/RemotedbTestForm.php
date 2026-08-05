<?php

namespace Drupal\remotedb\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\devel\DevelDumperManagerInterface;
use Drupal\remotedb\Component\StringLib;
use Drupal\remotedb\Entity\RemotedbInterface;
use Drupal\remotedb\Entity\RemotedbStorageInterface;
use Drupal\remotedb\Exception\RemotedbException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for manually performing a request to the remote database.
 */
class RemotedbTestForm extends FormBase implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The string utility library.
   *
   * @var \Drupal\remotedb\Component\StringLib
   */
  protected $stringLib;

  /**
   * Optional devel dumper service.
   *
   * @var \Drupal\devel\DevelDumperManagerInterface|null
   */
  protected $dumper;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new RemotedbTestForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\remotedb\Component\StringLib $stringLib
   *   The string utility.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\devel\DevelDumperManagerInterface|null $dumper
   *   The optional devel dumper service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, StringLib $stringLib, MessengerInterface $messenger, ?DevelDumperManagerInterface $dumper = NULL) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringLib = $stringLib;
    $this->messenger = $messenger;
    $this->dumper = $dumper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $dumper = $container->get('devel.dumper', ContainerInterface::NULL_ON_INVALID_REFERENCE);
    if (!$dumper instanceof DevelDumperManagerInterface) {
      $dumper = NULL;
    }

    return new static(
      $container->get('entity_type.manager'),
      $container->get('remotedb.string_lib'),
      $container->get('messenger'),
      $dumper
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'remotedb_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $result = $form_state->get(['remotedb_result']);
    if ($result !== NULL) {
      $form['remotedb_result'] = $this->dump($result);
    }

    $form['remotedb'] = [
      '#type' => 'select',
      '#options' => $this->getRemotedbStorage()->options(),
      '#title' => $this->t('Database'),
      '#required' => TRUE,
      '#description' => $this->t('The remote database.'),
    ];

    $form['method'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Method'),
      '#required' => TRUE,
      '#description' => $this->t('The method to call.'),
    ];
    $form['params'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Parameters'),
      '#description' => $this->t('Specify the parameters to use, one on each line.'),
    ];
    $form['execute'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send request'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $method = $form_state->getValue('method');
    $params_value = $form_state->getValue('params');
    $remotedb_id = $form_state->getValue('remotedb');
    if (!is_string($method) || !is_string($remotedb_id)) {
      return;
    }
    $params = $this->stringLib->textToArray(is_string($params_value) ? $params_value : '');
    $remotedb = $this->getRemotedbStorage()->load($remotedb_id);
    if ($remotedb instanceof RemotedbInterface) {
      try {
        $form_state->set(['remotedb_result'], $remotedb->sendRequest($method, $params));
      }
      catch (RemotedbException $e) {
        $e->printMessage();
      }
    }
    $form_state->setRebuild(TRUE);
  }

  /**
   * Dumps data.
   *
   * @param mixed $data
   *   The data to dump.
   *
   * @return array
   *   A render array.
   */
  protected function dump(mixed &$data): array {
    if ($this->dumper instanceof DevelDumperManagerInterface) {
      return $this->dumper->exportAsRenderable($data);
    }
    $this->messenger->addMessage($this->t('Enable the Devel module to get a more human readable representation of the response from the remote database.'));
    return [
      '#type' => 'textarea',
      '#title' => $this->t('Result'),
      '#value' => print_r($data, TRUE),
    ];
  }

  /**
   * Gets the remotedb storage handler.
   */
  protected function getRemotedbStorage(): RemotedbStorageInterface {
    $storage = $this->entityTypeManager->getStorage('remotedb');
    if (!$storage instanceof RemotedbStorageInterface) {
      throw new \LogicException('Expected remotedb storage to implement RemotedbStorageInterface.');
    }
    return $storage;
  }

}
