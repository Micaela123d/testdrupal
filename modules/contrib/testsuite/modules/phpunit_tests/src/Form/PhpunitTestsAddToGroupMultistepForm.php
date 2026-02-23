<?php

namespace Drupal\phpunit_tests\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Markup;
use Drupal\phpunit_tests\PhpunitTestsRepositoryService;
use Drupal\phpunit_tests\PhpunitTestsResourceService;
use Drupal\testsuite\BaseTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds test items to a group.
 */
class PhpunitTestsAddToGroupMultistepForm extends FormBase {
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
   * Load Config Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * PhpunitTestsAddToGroupMultistepForm constructor.
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
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'phpunit_tests_run_tests_multistep';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Set the page for the first time.
    if (!$form_state->has('page')) {
      $form_state->set('page', 1);
    }

    // Empty container to display the error messges.
    $form['messages'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'form-errors',
      ],
    ];

    switch ($form_state->get('page')) {
      case 2:
        $form['content'] = $this->buildSecondPage($form, $form_state);
        break;

      case 3:
        $form['content'] = $this->buildThirdPage($form, $form_state);
        break;

      default:
        $form['content'] = $this->buildFirstPage($form, $form_state);
        break;
    }

    $form['content']['#type'] = 'container';
    $form['content']['#attributes']['id'] = 'form-content';

    return $form;
  }

  /**
   * Validation handler for page 1.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form structure.
   */
  public function formFirstNextValidate(array &$form, FormStateInterface $form_state) {
    // Check directory.
    if ($form_state->isValueEmpty('directory')) {
      $form_state->setErrorByName('directory', $this->t('You must select something to filter by.'));
    }
    if (!in_array($form_state->getValue('directory'), $this->directories)) {
      $form_state->setErrorByName('directory', $this->t('Invalid option.'));
    }

    // Check area.
    if ($form_state->isValueEmpty('area')) {
      $form_state->setErrorByName('area', $this->t('You must select something to filter by.'));
    }
    if (!in_array($form_state->getValue('area'), $this->areas)) {
      $form_state->setErrorByName('area', $this->t('Invalid option.'));
    }
  }

  /**
   * Submission handler function for page 1.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form structure.
   */
  public function formFirstNextSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->set(
          'stored_values',
          array_merge(
              $form_state->get('stored_values') ?? [],
              [
                'directory' => $form_state->getValue('directory'),
                'area' => $form_state->getValue('area'),
              ]
          )
      );
    $form_state->setValues($form_state->get('stored_values'));
    $form_state->set('page', 2);
    // Set the form to rebuild so the form shows the next page when using Ajax.
    $form_state->setRebuild(TRUE);
  }

  /**
   * Submission handler for back button of the page 2.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form structure.
   */
  public function formSecondPageTwoBack(array &$form, FormStateInterface $form_state) {
    $form_user_inputs = $form_state->getUserInput();
    $form_state->set('stored_values', array_merge(
          $form_state->get('stored_values') ?? [],
          [
            'module' => $form_user_inputs['module'],
          ]
      ));
    $form_state->setValues($form_state->get('stored_values'));
    $form_state->set('page', 1);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Validation handler for page 2.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form structure.
   */
  public function formSecondNextValidate(array &$form, FormStateInterface $form_state) {
    // Check module.
    if ($form_state->isValueEmpty('module')) {
      $form_state->setErrorByName('module', $this->t('You must select something to filter by.'));
    }
    if (!preg_match($this->regex['string_space'], $form_state->getValue('module'))) {
      $form_state->setErrorByName('module', $this->t('Invalid option.'));
    }
  }

  /**
   * Submission handler function for page 2.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form structure.
   */
  public function formSecondNextSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->set(
          'stored_values',
          array_merge(
              $form_state->get('stored_values') ?? [],
              [
                'module' => $form_state->getValue('module'),
              ]
          )
      );
    $form_state->setValues($form_state->get('stored_values'));
    $form_state->set('page', 3);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Submission handler for back button of the page 3.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form structure.
   */
  public function formThirdPageTwoBack(array &$form, FormStateInterface $form_state) {
    $form_user_inputs = $form_state->getUserInput();
    $form_state->set('stored_values', array_merge(
          $form_state->get('stored_values') ?? [],
          [
            'test' => $form_user_inputs['test'],
            'group' => $form_user_inputs['group'],
          ]
      ));
    $form_state->setValues($form_state->get('stored_values'));
    $form_state->set('page', 2);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Validation handler for page 3.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form structure.
   */
  public function formThirdNextValidate(array &$form, FormStateInterface $form_state) {
    // Check test.
    if ($form_state->getValue('test') == []) {
      $form_state->setErrorByName('test', $this->t('You must select something to filter by.'));
    }
    foreach ($form_state->getValue('test') as $test) {
      if (!preg_match($this->regex['string'], $test)) {
        $form_state->setErrorByName('test', $this->t('Invalid option.'));
      }
    }

    // Check group.
    if ($form_state->getValue('group') == "") {
      $form_state->setErrorByName('group', $this->t('You must select something to filter by.'));
    }
    if (!preg_match($this->regex['number_single'], $form_state->getValue('group'))) {
      $form_state->setErrorByName('group', $this->t('Invalid option.'));
    }
  }

  /**
   * Submission handler function for page 3.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form structure.
   */
  public function formThirdNextSubmit(array &$form, FormStateInterface $form_state) {
    $form_values = array_merge(
          $form_state->get('stored_values') ?? [],
          [
            'test' => $form_state->getValue('test'),
            'group' => $form_state->getValue('group'),
          ]
      );

    // Run the test or save items under group.
    $values = $this->mapFormValues($form_values);

    $form_state->set('group_added', $this->addGroupItemsFromFormValues($values));
    // $form_state->setValues($form_state->get('stored_values'));
    $form_state->set('page', 1);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Callback function to handler the ajax behavior of the buttons.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form structure.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   With the commands to be executed by the Drupal Ajax API.
   */
  public function formAjaxChangePage(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Dsiplay the form error messages if it has any.
    if ($form_state->hasAnyErrors()) {
      $messages = $this->messenger->deleteAll();
      $form['messages']['content'] = [
        '#theme'        => 'status_messages',
        '#message_list' => $messages,
      ];
    }

    $response->addCommand(new ReplaceCommand('#form-content', $form['content']));
    $response->addCommand(new ReplaceCommand('#form-errors', $form['messages']));

    return $response;
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Rerturn to the first page.
    $form_state->set('page', 1);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Builds the first page of the form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form structure.
   *
   * @return array
   *   The render array with the items on this page.
   */
  private function buildFirstPage(array &$form, FormStateInterface $form_state) {
    $updatesLink = Link::createFromRoute('PHPUnit Config', 'phpunit_tests.settings')
      ->toString()
      ->getGeneratedLink();

    $build['filters'] = [
      '#type' => 'details',
      '#title' => $this->t('Add Tests To Group Part 1'),
      '#description' => Markup::create('Use ' . $updatesLink . ' to configure your phpunit.xml.'),
      '#open' => TRUE,
      '#attached' => [
        'library' => [
          'testsuite/testsuite.filters',
        ],
      ],
    ];

    $build['filters']['filters-container'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'testsuite-tests-filter-form-wrapper',
      ],
    ];

    $testDirectoryDropdown = $form_state->hasValue('directory') ? $form_state->getValue('directory') : '';

    $build['filters']['filters-container']['test-directory-container'] = [
      '#type' => 'container',
    ];
    $build['filters']['filters-container']['test-directory-container']['directory'] = [
      '#title' => 'Test Directory',
      '#type' => 'select',
      '#multiple' => FALSE,
      '#size' => 4,
      '#options' => $this->getEnabledTestDirectories(),
      '#default_value' => $testDirectoryDropdown,
    ];

    $areaDropdown = $form_state->hasValue('area') ? $form_state->getValue('area') : '';

    $build['filters']['filters-container']['area-container'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'testsuite-tests-areas-container',
      ],
    ];
    $build['filters']['filters-container']['area-container']['area'] = [
      '#title' => 'Area',
      '#type' => 'select',
      '#multiple' => FALSE,
      '#size' => 4,
      '#options' => [
        'core' => $this->t('Core'),
        'contrib' => $this->t('Contrib'),
        'custom' => $this->t('Custom'),
      ],
      '#default_value' => $areaDropdown,
    ];

    $build['filters']['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['container-inline']],
    ];

    $build['filters']['actions']['next'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Next'),
      '#submit' => ['::formFirstNextSubmit'],
      '#validate' => ['::formFirstNextValidate'],
      '#ajax' => [
        'callback' => '::formAjaxChangePage',
        'event' => 'click',
        'progress' => [
          'type' => 'fullScreen',
        ],
      ],
    ];

    return $build;
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
   * Builds the second page of the form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form structure.
   *
   * @return array
   *   The render array with the items on this page.
   */
  private function buildSecondPage(array &$form, FormStateInterface $form_state) {
    $build['filters'] = [
      '#type' => 'details',
      '#title' => $this->t('Add Tests To Group Part 2'),
      '#open' => TRUE,
      '#attached' => [
        'library' => [
          'testsuite/testsuite.filters',
        ],
      ],
    ];

    $build['filters']['filters-container'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'testsuite-tests-filter-form-wrapper',
      ],
    ];

    $moduleDropdown = $form_state->hasValue('module') ? $form_state->getValue('module') : '';
    $areaDropdown = $form_state->hasValue('area') ? $form_state->getValue('area') : '';
    $directoryDropdown = $form_state->hasValue('directory') ? $form_state->getValue('directory') : '';

    $build['filters']['filters-container']['module-container'] = [
      '#type' => 'container',
    ];
    $build['filters']['filters-container']['module-container']['module'] = [
      '#title' => 'Module',
      '#type' => 'select',
      '#multiple' => FALSE,
      '#size' => 4,
      '#options' => $this->phpunitTestsResourceService->getResource('module', $areaDropdown, 'all', $directoryDropdown),
      '#default_value' => $moduleDropdown,
    ];

    $build['filters']['back'] = [
      '#type' => 'submit',
      '#value' => $this->t('Back'),
      '#submit' => ['::formSecondPageTwoBack'],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => '::formAjaxChangePage',
        'event' => 'click',
        'progress' => [
          'type' => 'fullScreen',
        ],
      ],
    ];

    $build['filters']['actions']['next'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Next'),
      '#submit' => ['::formSecondNextSubmit'],
      '#validate' => ['::formSecondNextValidate'],
      '#ajax' => [
        'callback' => '::formAjaxChangePage',
        'event' => 'click',
        'progress' => [
          'type' => 'fullScreen',
        ],
      ],
    ];

    return $build;
  }

  /**
   * Builds the second page of the form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form structure.
   *
   * @return array
   *   The render array with the items on this page.
   */
  private function buildThirdPage(array &$form, FormStateInterface $form_state) {
    $build['filters'] = [
      '#type' => 'details',
      '#title' => $this->t('Add Tests To Group Part 3'),
      '#description' => $this->t('This could take a minute or two..'),
      '#open' => TRUE,
      '#attached' => [
        'library' => [
          'testsuite/testsuite.filters',
        ],
      ],
    ];

    $build['filters']['filters-container'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'testsuite-tests-filter-form-wrapper',
      ],
    ];

    $moduleDropdown = $form_state->hasValue('module') ? $form_state->getValue('module') : '';
    $areaDropdown = $form_state->hasValue('area') ? $form_state->getValue('area') : '';
    $directoryDropdown = $form_state->hasValue('directory') ? $form_state->getValue('directory') : '';
    $testDropdown = $form_state->hasValue('test') ? $form_state->getValue('test') : '';

    $build['filters']['filters-container']['tests-container'] = [
      '#type' => 'container',
    ];
    $build['filters']['filters-container']['tests-container']['test'] = [
      '#title' => 'Tests',
      '#type' => 'select',
      '#multiple' => TRUE,
      '#size' => 4,
      '#options' => $this->phpunitTestsResourceService->getResource('test', $areaDropdown, $moduleDropdown, $directoryDropdown),
      '#default_value' => $testDropdown,
    ];

    $build['filters']['filters-container']['groups-container'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'testsuite-tests-areas-container',
      ],
    ];
    $build['filters']['filters-container']['groups-container']['group'] = [
      '#title' => 'Groups',
      '#type' => 'select',
      '#multiple' => FALSE,
      '#size' => 4,
      '#options' => $this->phpunitTestsRepositoryService->getGroups(),
    ];

    $build['filters']['back'] = [
      '#type' => 'submit',
      '#value' => $this->t('Back'),
      '#submit' => ['::formThirdPageTwoBack'],
      '#limit_validation_errors' => [],
      '#ajax' => [
        'callback' => '::formAjaxChangePage',
        'event' => 'click',
        'progress' => [
          'type' => 'fullScreen',
        ],
      ],
    ];

    $build['filters']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Submit'),
      '#submit' => ['::formThirdNextSubmit'],
      '#validate' => ['::formThirdNextValidate'],
    ];

    return $build;
  }

  /**
   * Trim and map the form values to by save in the user fields.
   *
   * @param array $form_values
   *   Array with the values passed by the form.
   *
   * @return array
   *   Array of mapped values.
   */
  private function mapFormValues(array $form_values) {
    $values['group'] = trim($form_values['group']);
    $values['directory'] = trim($form_values['directory']);
    $values['area'] = trim($form_values['area']);
    $values['module'] = trim($form_values['module']);
    $tests = [];
    foreach ($form_values['test'] as $test) {
      $tests[] = $test;
    }
    $values['test'] = $tests;

    return $values;
  }

  /**
   * Adds test items to a group.
   *
   * @param array $values
   *   Array with the values to set.
   *
   * @return bool
   *   True or False if there was a problem creating a test.
   */
  private function addGroupItemsFromFormValues(array $values) {
    $ifError = FALSE;
    $errorCount = 0;
    $testCount = 0;
    foreach ($values['test'] as $test) {
      if ($this->phpunitTestsRepositoryService->createGroupItem([
        'id' => $values['group'],
        'area' => $values['area'],
        'module' => $values['module'],
        'directory' => $values['directory'],
        'test' => $test,
      ])) {
        $testCount++;
      }
      else {
        $ifError = TRUE;
        $errorCount++;
      }
    }
    if (!$ifError) {
      $this->messenger->addStatus($testCount . ' group item has been recorded.');
      return TRUE;
    }
    else {
      $this->messenger->addStatus($errorCount . ' group item could not not been recorded. ' . $testCount . ' group item has been recorded.. Please be sure all packages are loaded and the phpunit.xml is created with the right data.');
      return FALSE;
    }
  }

}
