<?php

namespace Drupal\phpstan_tests\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\phpstan_tests\PhpstanTestsFileResourceService;
use Drupal\phpstan_tests\PhpstanTestsResourceService;
use Drupal\testsuite\BaseTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the database logging filter form.
 *
 * @internal
 */
class PhpstanTestsFilterForm extends FormBase {
  use BaseTrait;

  /**
   * The array of filters.
   *
   * @var array
   */
  public $filters = [
    'action',
    'level',
    'area',
    'module',
  ];

  /**
   * The database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Load the Resource Service.
   *
   * @var \Drupal\phpstan_tests\PhpstanTestsResourceService
   */
  protected $phpstanTestsResourceService;

  /**
   * The time interface.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $timeInterface;

  /**
   * Initializer Service.
   *
   * @var \Drupal\phpstan_tests\PhpstanTestsFileResourceService
   */
  protected $phpstanTestsFileResourceService;

  /**
   * PhpstanTestsFilterForm constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Load messenger service.
   * @param \Drupal\phpstan_tests\PhpstanTestsResourceService $phpstanTestsResourceService
   *   The load resource service.
   * @param \Drupal\Component\Datetime\TimeInterface $timeInterface
   *   The load resource service.
   * @param \Drupal\phpstan_tests\PhpstanTestsFileResourceService $phpstanTestsFileResourceService
   *   The initializer service that creates configeration files.
   */
  public function __construct(
    Connection $connection,
    MessengerInterface $messenger,
    PhpstanTestsResourceService $phpstanTestsResourceService,
    TimeInterface $timeInterface,
    PhpstanTestsFileResourceService $phpstanTestsFileResourceService,
  ) {
    $this->connection = $connection;
    $this->messenger = $messenger;
    $this->phpstanTestsResourceService = $phpstanTestsResourceService;
    $this->timeInterface = $timeInterface;
    $this->phpstanTestsFileResourceService = $phpstanTestsFileResourceService;
    ini_set('max_execution_time', 0);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('messenger'),
      $container->get('phpstan_tests.load_resource.service'),
      $container->get('datetime.time'),
      $container->get('phpstan_tests.file_resource.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'phpstan_tests_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $session_filters = $this->getRequest()->getSession()->get('phpstan_tests_overview_filter', []);

    $form['filters'] = [
      '#type' => 'details',
      '#title' => $this->t('Filter log messages'),
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

    $levelDropdown = $form_state->hasValue('level') ? $form_state->getValue('level') : '';
    $level_dropdown = !empty($session_filters['level']) ? $session_filters['level'] : $levelDropdown;

    $form['filters']['filters-container']['level-container'] = [
      '#type' => 'container',
    ];
    $form['filters']['filters-container']['level-container']['level'] = [
      '#title' => 'Level',
      '#type' => 'select',
      '#multiple' => FALSE,
      '#size' => 4,
      '#options' => [
        1 => $this->t('Level 1'),
        2 => $this->t('Level 2'),
        3 => $this->t('Level 3'),
        4 => $this->t('Level 4'),
        5 => $this->t('Level 5'),
        6 => $this->t('Level 6'),
        7 => $this->t('Level 7'),
        8 => $this->t('Level 8'),
        9 => $this->t('Level 9'),
      ],
      '#default_value' => $level_dropdown,
    ];

    $areaDropdown = $form_state->hasValue('area') ? $form_state->getValue('area') : '';
    $area_dropdown = !empty($session_filters['area']) ? $session_filters['area'] : $areaDropdown;

    $form['filters']['filters-container']['area-container'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'testsuite-tests-areas-container',
      ],
    ];
    $form['filters']['filters-container']['area-container']['area'] = [
      '#title' => 'Area',
      '#type' => 'select',
      '#multiple' => FALSE,
      '#size' => 4,
      '#options' => [
        'core' => $this->t('Core'),
        'contrib' => $this->t('Contrib'),
        'custom' => $this->t('Custom'),
      ],
      '#default_value' => $area_dropdown,
    ];

    if ($modules = $this->getModules()) {
      $moduleDropdown = $form_state->hasValue('module') ? $form_state->getValue('module') : '';
      $module_dropdown = !empty($session_filters['module']) ? $session_filters['module'] : $moduleDropdown;

      $form['filters']['filters-container']['module-container'] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'testsuite-tests-modules-container',
        ],
      ];
      $form['filters']['filters-container']['module-container']['module'] = [
        '#title' => 'Module',
        '#type' => 'select',
        '#multiple' => FALSE,
        '#size' => 4,
        '#options' => $modules,
        '#default_value' => $module_dropdown,
      ];
    }

    $form['filters']['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['container-inline']],
    ];
    $form['filters']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    ];
    if (!empty($session_filters)) {
      $form['filters']['actions']['reset'] = [
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#limit_validation_errors' => [],
        '#submit' => ['::resetForm'],
      ];
    }
    return $form;
  }

  /**
   * Gathers a list of uniquely defined database library module names.
   *
   * @return array
   *   List of uniquely defined database library module names.
   */
  private function getModules() {
    return $this->connection->query("SELECT DISTINCT([module]) FROM {phpstan_test_item} ORDER BY [module]")->fetchAllKeyed(0, 0);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('level') != NULL) {
      if (!in_array($form_state->getValue('level'), [1, 2, 3, 4, 5, 6, 7, 8, 9])) {
        $form_state->setErrorByName('level', $this->t('Invalid option.'));
      }
    }
    if ($form_state->getValue('area') != NULL) {
      if (!in_array($form_state->getValue('area'), $this->areas)) {
        $form_state->setErrorByName('area', $this->t('Invalid option.'));
      }
    }
    if ($form_state->getValue('module') != NULL) {
      if (!preg_match($this->regex['string_space'], $form_state->getValue('module'))) {
        $form_state->setErrorByName('module', $this->t('Invalid option.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $session_filters = $this->getRequest()->getSession()->get('phpstan_tests_overview_filter', []);

    foreach ($this->filters as $name) {
      if ($form_state->hasValue($name)) {
        $session_filters[$name] = $form_state->getValue($name);
      }
    }
    $this->getRequest()->getSession()->set('phpstan_tests_overview_filter', $session_filters);
  }

  /**
   * Resets the filter form.
   */
  public function resetForm() {
    $this->getRequest()->getSession()->remove('phpstan_tests_overview_filter');
  }

}
