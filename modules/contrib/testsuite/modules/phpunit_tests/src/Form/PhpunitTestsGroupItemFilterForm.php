<?php

namespace Drupal\phpunit_tests\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
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
class PhpunitTestsGroupItemFilterForm extends FormBase {
  use BaseTrait;

  /**
   * The array of filters.
   *
   * @var array
   */
  protected $filters = [
    'area',
    'module',
    'directory',
  ];

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
   * Load Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * PhpunitTestsFilterForm constructor.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Load messenger service.
   * @param \Drupal\phpunit_tests\PhpunitTestsResourceService $phpunitTestsResourceService
   *   The load resource service.
   * @param \Drupal\phpunit_tests\PhpunitTestsRepositoryService $phpunitTestsRepositoryService
   *   The load resource service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config service.
   */
  public function __construct(
    MessengerInterface $messenger,
    PhpunitTestsResourceService $phpunitTestsResourceService,
    PhpunitTestsRepositoryService $phpunitTestsRepositoryService,
    ConfigFactoryInterface $configFactory,
  ) {
    $this->messenger = $messenger;
    $this->phpunitTestsResourceService = $phpunitTestsResourceService;
    $this->phpunitTestsRepositoryService = $phpunitTestsRepositoryService;
    $this->configFactory = $configFactory;
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
    return 'phpunit_tests_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

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

    $session_filters = $this->getRequest()->getSession()->get('phpunit_tests_group_item_overview_filter', []);

    $form['filters']['filters-container'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'testsuite-tests-filter-form-wrapper',
      ],
    ];

    $testDirectoryDropdown = $form_state->hasValue('directory') ? $form_state->getValue('directory') : '';
    $test_directory_dropdown = !empty($session_filters['directory']) ? $session_filters['directory'] : $testDirectoryDropdown;

    $form['filters']['filters-container']['test-directory-container'] = [
      '#type' => 'container',
    ];
    $form['filters']['filters-container']['test-directory-container']['directory'] = [
      '#title' => 'Test Directory',
      '#type' => 'select',
      '#multiple' => FALSE,
      '#size' => 4,
      '#options' => $this->getEnabledTestDirectories(),
      '#default_value' => $test_directory_dropdown,
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

    if ($modules = $this->phpunitTestsRepositoryService->getItemsModules()) {
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
   * Gets an array of enabled test folders.
   *
   * @return array
   *   The array of enabled test folders.
   */
  private function getEnabledTestDirectories() {
    $enabledTests = [];
    $config = $this->configFactory->get('phpunit_tests.settings');
    foreach ($config->get('testsuite_loaded_test_types') as $test => $enabled) {
      if ($enabled != 0) {
        $enabledTests[$test] = $test;
      }
    }
    return $enabledTests;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('area') != NULL) {
      if (!in_array($form_state->getValue('area'), $this->areas)) {
        $form_state->setErrorByName('area', $this->t('Invalid option.'));
      }
    }
    if ($form_state->getValue('module') != NULL) {
      if ($form_state->isValueEmpty('module')) {
        $form_state->setErrorByName('module', $this->t('You must select something to filter by.'));
      }
      if (!preg_match($this->regex['string_space'], $form_state->getValue('module'))) {
        $form_state->setErrorByName('module', $this->t('Invalid option.'));
      }
    }
    if ($form_state->getValue('directory') != NULL) {
      if (!in_array($form_state->getValue('directory'), $this->directories)) {
        $form_state->setErrorByName('directory', $this->t('Invalid option.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $session_filters = $this->getRequest()->getSession()->get('phpunit_tests_group_item_overview_filter', []);

    foreach ($this->filters as $name) {
      if ($form_state->hasValue($name)) {
        $session_filters[$name] = $form_state->getValue($name);
      }
    }
    $this->getRequest()->getSession()->set('phpunit_tests_group_item_overview_filter', $session_filters);
    $this->messenger->addStatus('Filters updated.');
  }

  /**
   * Resets the filter form.
   */
  public function resetForm() {
    $this->getRequest()->getSession()->remove('phpunit_tests_group_item_overview_filter');
  }

}
