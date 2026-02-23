<?php

namespace Drupal\phpunit_tests\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\phpunit_tests\PhpunitTestsRepositoryService;
use Drupal\phpunit_tests\PhpunitTestsResourceService;
use Drupal\testsuite\BaseTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the database logging filter form.
 *
 * @internal
 */
class PhpunitTestsCreateGroupForm extends FormBase {
  use BaseTrait;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Load the Resource Service.
   *
   * @var \Drupal\phpunit_tests\PhpunitTestsResourceService
   */
  protected $phpunitTestsResourceService;

  /**
   * Load the Repository Service.
   *
   * @var \Drupal\phpunit_tests\PhpunitTestsRepositoryService
   */
  protected $phpunitTestsRepositoryService;

  /**
   * PhpunitTestsCreateGroupForm constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Load messenger service.
   * @param \Drupal\phpunit_tests\PhpunitTestsResourceService $phpunitTestsResourceService
   *   The load resource service.
   * @param \Drupal\phpunit_tests\PhpunitTestsRepositoryService $phpunitTestsRepositoryService
   *   The load resource service.
   */
  public function __construct(
    MessengerInterface $messenger,
    PhpunitTestsResourceService $phpunitTestsResourceService,
    PhpunitTestsRepositoryService $phpunitTestsRepositoryService,
  ) {
    $this->messenger = $messenger;
    $this->phpunitTestsResourceService = $phpunitTestsResourceService;
    $this->phpunitTestsRepositoryService = $phpunitTestsRepositoryService;
    ini_set('max_execution_time', 0);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('messenger'),
          $container->get('phpunit_tests.load_resource.service'),
          $container->get('phpunit_tests.repository.service'),
          $container->get('config.factory')
      );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'create_group_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = [
      '#type' => 'details',
      '#title' => $this->t('Create Groups'),
      '#open' => TRUE,
      '#attached' => [
        'library' => [
          'testsuite/testsuite.filters',
        ],
      ],
    ];

    $form['filters']['filters-container'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'testsuite-tests-filter-form-wrapper',
      ],
    ];

    $form['filters']['filters-container']['name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Group Name'),
      '#pattern' => '[a-zA-Z0-9_\-\s]+',
      '#default' => $form_state->hasValue('name') ? $form_state->getValue('name') : '',
      '#required' => TRUE,
    ];

    $form['filters']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Create Group'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->phpunitTestsRepositoryService->createGroup($form_state->getValue('name'))) {
      $this->messenger->addStatus('Group added.');
    }
    else {
      $this->messenger->addStatus('Group failed to save.');
    }
  }

}
