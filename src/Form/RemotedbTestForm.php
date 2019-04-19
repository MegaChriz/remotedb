<?php

namespace Drupal\remotedb\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\remotedb\Component\StringLib;
use Drupal\remotedb\Entity\RemotedbStorageInterface;
use Drupal\remotedb\Exception\RemotedbException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for manually performing a request to the remote database.
 */
class RemotedbTestForm extends FormBase {

  /**
   * The remote database storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Construct a new RemotedbTestForm object.
   *
   * @param \Drupal\Core\Entity\RemotedbStorageInterface $storage
   *   The remote database storage.
   */
  public function __construct(RemotedbStorageInterface $storage) {
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('remotedb')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'remotedb_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $result = $form_state->get(['remotedb_result']);
    if ($result) {
      $form['remotedb_result'] = $this->dump($result);
    }

    $form['remotedb'] = [
      '#type' => 'select',
      '#options' => $this->storage->options(),
      '#title' => t('Database'),
      '#required' => TRUE,
      '#description' => t('The remote database.'),
    ];

    $form['method'] = [
      '#type' => 'textfield',
      '#title' => t('Method'),
      '#required' => TRUE,
      '#description' => t('The method to call.'),
    ];
    $form['params'] = [
      '#type' => 'textarea',
      '#title' => t('Parameters'),
      '#description' => t('Specify the parameters to use, one on each line.'),
    ];
    $form['execute'] = [
      '#type' => 'submit',
      '#value' => t('Send request'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $method = $form_state->getValue(['method']);
    $string = new StringLib();
    $params = $string->textToArray($form_state->getValue(['params']));
    $remotedb = $this->storage->load($form_state->getValue(['remotedb']));
    if ($remotedb) {
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
   */
  protected function dump(&$data) {
    if (\Drupal::hasService('devel.dumper')) {
      return \Drupal::service('devel.dumper')->exportAsRenderable($data);
    }
    $this->messenger()->addMessage($this->t('Enable the Devel module to get a more human readable representation of the response from the remote database.'));
    return [
      '#type' => 'textarea',
      '#title' => t('Result'),
      '#value' => print_r($data, TRUE),
    ];
  }

}
