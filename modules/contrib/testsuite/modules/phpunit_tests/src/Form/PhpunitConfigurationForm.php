<?php

namespace Drupal\phpunit_tests\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\phpunit_tests\PhpunitTestsFileResourceService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines a form that configures forms module settings.
 */
class PhpunitConfigurationForm extends ConfigFormBase {

  /**
   * The list of directories represent the directories where phpunit tests are.
   *
   * @var array
   */
  private $directories = [
    'Unit',
    'Kernel',
    'Functional',
    'FunctionalJavascript',
  ];

  /**
   * Initializer Service.
   *
   * @var \Drupal\phpunit_tests\PhpunitTestsFileResourceService
   */
  protected $fileResourceService;

  /**
   * The messenger service.
   *
   * @var Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Request object.
   *
   * @var object
   */
  protected $request;

  /**
   * Database object.
   *
   * @var object
   */
  protected $connection;

  /**
   * ConfigurationForm constructor.
   *
   * @param \Drupal\phpunit_tests\PhpunitTestsFileResourceService $fileResourceService
   *   The initializer object.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger interface.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(
    PhpunitTestsFileResourceService $fileResourceService,
    MessengerInterface $messenger,
    RequestStack $requestStack,
    Connection $connection,
  ) {
    $this->fileResourceService = $fileResourceService;
    $this->messenger = $messenger;
    $this->request = $requestStack->getCurrentRequest();
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('phpunit_tests.file_resource.service'),
          $container->get('messenger'),
          $container->get('request_stack'),
          $container->get('database')
      );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'phpunit_tests_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'phpunit_tests.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('phpunit_tests.settings');

    $form['phpunit'] = [
      '#type' => 'details',
      '#title' => $this->t('PHPUnit settings'),
      '#description' => $this->t('Setting related to creating the phpunit.xml file. To get rolling quick select Drupal settings.php and click save. This sets your database credentials to the ones you created on install.'),
      '#open' => TRUE,
    ];

    $form['phpunit']['testsuite_loaded_test_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Loaded Test Types'),
      '#options' => [
        'Unit' => $this->t('Unit'),
        'Kernel' => $this->t('Kernel'),
        'Functional' => $this->t('Functional'),
        'FunctionalJavascript' => $this->t('FunctionalJavascript'),
      ],
      '#default_value' => $config->get('testsuite_loaded_test_types') ? $config->get('testsuite_loaded_test_types') : ['Unit'],
    ];

    $form['phpunit']['testsuite_mysql_storage_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Host and Db connection variables.'),
      '#options' => [
        $this->t('Test Suite Settings'),
        $this->t('Drupal settings.php'),
      ],
      '#default_value' => $config->get('testsuite_mysql_storage_type') ? $config->get('testsuite_mysql_storage_type') : 0,
      '#attributes' => [
        'id' => 'test-suite-mysql-storage-type',
      ],
    ];

    $form['phpunit']['testsuite_simple_base_url'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('SIMPLETEST_BASE_URL'),
      '#description' => $this->t(
          'Example SIMPLETEST_BASE_URL<br />
                Example value: http://localhost<br />
                Your domain - @domain', ['@domain' => $this->request->getSchemeAndHttpHost()]
      ),
      '#default_value' => $config->get('testsuite_simple_base_url') ? $config->get('testsuite_simple_base_url') : $this->request->getSchemeAndHttpHost(),
    ];

    $form['phpunit']['database'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Host and Database Credentials'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#states' => [
        'visible' => [
              [
                  [':input[id="test-suite-mysql-storage-type"]' => ['value' => 0]],
                'or',
                  [':input[id="test-suite-mysql-storage-type"]' => ['value' => 2]],
              ],
        ],
      ],
    ];

    $form['phpunit']['database']['testsuite_mysql_host'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Mysql Host'),
      '#default_value' => $config->get('testsuite_mysql_host') ? $config->get('testsuite_mysql_host') : 'localhost',
    ];
    $form['phpunit']['database']['testsuite_mysql_username'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Mysql Username'),
      '#default_value' => $config->get('testsuite_mysql_username') ? $config->get('testsuite_mysql_username') : 'root',
    ];
    $form['phpunit']['database']['testsuite_mysql_password'] = [
      '#type' => 'textfield',
      '#required' => FALSE,
      '#title' => $this->t('Mysql Password'),
      '#default_value' => $config->get('testsuite_mysql_password') ? $config->get('testsuite_mysql_password') : '',
    ];
    $form['phpunit']['database']['testsuite_mysql_port'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Mysql Port'),
      '#default_value' => $config->get('testsuite_mysql_port') ? $config->get('testsuite_mysql_port') : '3306',
    ];
    $form['phpunit']['database']['testsuite_mysql_database_name'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Mysql Database Name'),
      '#default_value' => $config->get('testsuite_mysql_database_name') ? $config->get('testsuite_mysql_database_name') : 'drupal',
    ];

    $form['phpunit']['testsuite_browser_output_directory'] = [
      '#type' => 'textfield',
      '#title' => $this->t('BROWSERTEST_OUTPUT_DIRECTORY'),
      '#description' => $this->t(
          'Example BROWSERTEST_OUTPUT_DIRECTORY<br />
                value: /path/to/webroot/sites/simpletest/browser_output.<br />
                BROWSERTEST_OUTPUT_DIRECTORY is used as the directory where<br />
                the output data will be saved by PHPUnit and needs to be an<br />
                absolute local path.'
      ),
      '#default_value' => $config->get('testsuite_browser_output_directory') ? $config->get('testsuite_browser_output_directory') : $this->fileResourceService->browserOutputDirectory,
    ];

    $form['phpunit']['testsuite_browser_output_base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('BROWSERTEST_OUTPUT_BASE_URL'),
      '#description' => $this->t(
          'By default, browser tests will output links that use the base URL set<br />
                in SIMPLETEST_BASE_URL. However, if your SIMPLETEST_BASE_URL is an internal<br />
                path (such as may be the case in a virtual or Docker-based environment),<br />
                you can set the base URL used in the browser test output links to something<br />
                reachable from your host machine here. This will allow you to follow them<br />
                directly and view the output.<br />
                Your domain - @domain', ['@domain' => $this->request->getSchemeAndHttpHost()]
      ),
      '#default_value' => $config->get('testsuite_browser_output_base_url') ? $config->get('testsuite_browser_output_base_url') : $this->request->getSchemeAndHttpHost(),
    ];

    $form['phpunit']['testsuite_disable_deprecation_testing'] = [
      '#type' => 'radios',
      '#title' => $this->t('Disable deprecation testing.'),
      '#description' => $this->t(
          "Deprecation testing is managed through Symfony's PHPUnit Bridge.<br />
                    The environment variable SYMFONY_DEPRECATIONS_HELPER is used to<br />
                    configure the behaviour of the deprecation tests. See <a href=\"https://symfony.com/doc/current/components/phpunit_bridge.html#configuration\" target=\"_blank\">configuration</a><br />
                    Drupal core's testing framework is setting this variable to its default No change<br />
                    Projects with their own requirements need to manage this variable<br />
                    explicitly. To disable deprecation testing completely select Disable.<br />
                    To set a value for deprecation testing select Configure and add a file to SYMFONY_DEPRECATIONS_HELPER"
      ),
      '#options' => [
        0 => $this->t('No change'),
        1 => $this->t('Disable'),
        2 => $this->t('Configure'),
      ],
      '#default_value' => $config->get('testsuite_disable_deprecation_testing') ? $config->get('testsuite_disable_deprecation_testing') : 0,
      '#attributes' => [
        'id' => 'test-suite-disable-deprecation',
      ],
    ];

    $form['phpunit']['testsuite_configure_deprecation_helper'] = [
      '#type' => 'textfield',
      '#title' => $this->t('SYMFONY_DEPRECATIONS_HELPER'),
      '#default_value' => $config->get('testsuite_configure_deprecation_helper'),
      '#description' => $this->t(
          "Deprecation errors can be selectively ignored by specifying a file of<br />
                    regular expression patterns for exclusion.<br />
                    Example Value: ignoreFile=.deprecation-ignore.txt<br />
                    See https://symfony.com/doc/current/components/phpunit_bridge.html#ignoring-deprecations<br />
                    NOTE: it may be required to specify the full path to the file to run tests<br />
                    correctly."
      ),
      '#states' => [
        'visible' => [
          ':input[id="test-suite-disable-deprecation"]' => ['value' => 2],
        ],
      ],
    ];

    $form['phpunit']['testsuite_mink_driver_class'] = [
      '#type' => 'textfield',
      '#title' => $this->t('MINK_DRIVER_CLASS'),
      '#description' => $this->t(
          "Example for changing the driver class for mink tests MINK_DRIVER_CLASS<br />
                value: 'Drupal\FunctionalJavascriptTests\DrupalSelenium2Driver'"
      ),
      '#default_value' => $config->get('testsuite_mink_driver_class'),
    ];

    $form['phpunit']['testsuite_mink_driver_args'] = [
      '#type' => 'textfield',
      '#title' => $this->t('MINK_DRIVER_ARGS'),
      '#description' => $this->t(
          "Example for changing the driver args to mink tests<br />
                MINK_DRIVER_ARGS value: '[\"http://127.0.0.1:8510\"]"
      ),
      '#default_value' => $config->get('testsuite_mink_driver_args'),
    ];

    $form['phpunit']['testsuite_mink_driver_args_webdriver'] = [
      '#type' => 'textfield',
      '#title' => $this->t('MINK_DRIVER_ARGS_WEBDRIVER'),
      '#description' => $this->t(
          "Example for changing the driver args to webdriver tests<br />
                MINK_DRIVER_ARGS_WEBDRIVER value: <br />
                '[\"chrome\", { \"chromeOptions\": { \"w3c\": false } }, \"http://localhost:4444/wd/hub\"]'<br />
                For using the Firefox browser, replace \"chrome\" with \"firefox\""
      ),
      '#default_value' => $config->get('testsuite_mink_driver_args_webdriver'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('testsuite_loaded_test_types') as $loadedDirectory => $selected) {
      if (!in_array($loadedDirectory, $this->directories)) {
        $form_state->setErrorByName(
              'testsuite_loaded_test_types',
              $this->t('The test types are invalid. Please enter valid test types.')
          );
      }
    }
    if (strlen($form_state->getValue('testsuite_mysql_storage_type')) > 1) {
      $form_state->setErrorByName(
            'testsuite_mysql_storage_type',
            $this->t('Incorrect storage type value submitted.')
        );
    }
    if ($form_state->hasValue('testsuite_simple_base_url') && $form_state->hasValue('testsuite_simple_base_url') != '') {
      if (filter_var($form_state->getValue('testsuite_simple_base_url'), FILTER_VALIDATE_URL) === FALSE) {
        $form_state->setErrorByName(
              'testsuite_simple_base_url',
              $this->t('The base url is invalid. Please enter a valid url.')
          );
      }
    }
    if ($form_state->hasValue('testsuite_mysql_host') && $form_state->hasValue('testsuite_mysql_host') != '') {
      if ($form_state->getValue('testsuite_mysql_host') !== 'localhost' && filter_var($form_state->getValue('testsuite_mysql_host'), FILTER_VALIDATE_IP) === FALSE) {
        $form_state->setErrorByName(
              'testsuite_mysql_host',
              $this->t('The host is invalid. Please enter a valid host name.')
          );
      }
    }
    if ($form_state->hasValue('testsuite_mysql_username') && $form_state->hasValue('testsuite_mysql_username') != '') {
      if (!preg_match('/^[a-z]\w{2,23}[^_]+$/i', $form_state->getValue('testsuite_mysql_username'))) {
        $form_state->setErrorByName(
              'testsuite_mysql_username',
              $this->t('The username is invalid. Please enter a valid username.')
          );
      }
    }
    if ($form_state->hasValue('testsuite_mysql_password') && $form_state->getValue('testsuite_mysql_password') != "") {
      if (!preg_match('/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/i', $form_state->getValue('testsuite_mysql_password'))) {
        $form_state->setErrorByName(
              'testsuite_mysql_password',
              $this->t('Minimum eight characters, at least one letter and one number.')
          );
      }
    }
    if ($form_state->hasValue('testsuite_mysql_port') && $form_state->getValue('testsuite_mysql_port') != "") {
      if (!is_numeric($form_state->getValue('testsuite_mysql_port'))) {
        $form_state->setErrorByName('testsuite_mysql_port', $this->t('Numbers only.'));
      }
    }
    if ($form_state->hasValue('testsuite_mysql_database_name') && $form_state->getValue('testsuite_mysql_database_name') != "") {
      if (!preg_match('/^[a-zA-Z0-9_]+$/i', $form_state->getValue('testsuite_mysql_database_name'))) {
        $form_state->setErrorByName(
              'testsuite_mysql_database_name',
              $this->t('The database name is invalid. Please enter a valid database name.')
          );
      }
    }
    if ($form_state->hasValue('testsuite_browser_output_directory') && $form_state->getValue('testsuite_browser_output_directory') != "") {
      if (!preg_match('/^(.+)((\/|\\\)(.+))$/i', $form_state->getValue('testsuite_browser_output_directory'))) {
        $form_state->setErrorByName(
              'testsuite_browser_output_directory',
              $this->t('The browser output directory is invalid. Please enter a valid browser output directory.')
          );
      }
    }
    if ($form_state->hasValue('testsuite_browser_output_base_url') && $form_state->getValue('testsuite_browser_output_base_url') != "") {
      if (filter_var($form_state->getValue('testsuite_browser_output_base_url'), FILTER_VALIDATE_URL) === FALSE) {
        $form_state->setErrorByName(
              'testsuite_browser_output_base_url',
              $this->t('The browser output base url is invalid. Please enter a valid browser output base url.')
          );
      }
    }
    if (strlen($form_state->getValue('testsuite_disable_deprecation_testing')) > 1) {
      $form_state->setErrorByName(
            'testsuite_disable_deprecation_testing',
            $this->t('Incorrect disable deprecation testing value submitted.')
        );
    }
    if ($form_state->hasValue('testsuite_configure_deprecation_helper') && $form_state->getValue('testsuite_configure_deprecation_helper') != "") {
      if (!preg_match('/^(.+)\.txt$/', $form_state->getValue('testsuite_configure_deprecation_helper'))) {
        $form_state->setErrorByName(
              'testsuite_configure_deprecation_helper',
              $this->t('This field must end with a txt file.')
          );
      }
    }

    if ($form_state->hasValue('testsuite_mink_driver_class') && $form_state->getValue('testsuite_mink_driver_class') != "") {
      if (!preg_match('/^(.+)(\\\[a-zA-Z0-9]+)$/i', $form_state->getValue('testsuite_mink_driver_class'))) {
        $form_state->setErrorByName(
              'testsuite_mink_driver_class',
              $this->t('This field must contain a class definition.')
          );
      }
    }
    if ($form_state->hasValue('testsuite_mink_driver_args') && $form_state->getValue('testsuite_mink_driver_args') != "") {
      if (!preg_match('/^\[(.+)\]$/', $form_state->getValue('testsuite_mink_driver_args'))) {
        $form_state->setErrorByName(
              'testsuite_mink_driver_args',
              $this->t('This field must contain an array of values.')
          );
      }
    }
    if ($form_state->hasValue('testsuite_mink_driver_args_webdriver') && $form_state->getValue('testsuite_mink_driver_args_webdriver') != "") {
      if (!preg_match('/^\[(.+)\]$/', $form_state->getValue('testsuite_mink_driver_args_webdriver'))) {
        $form_state->setErrorByName(
              'testsuite_mink_driver_args_webdriver',
              $this->t('This field must contain an array of values.')
          );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $data = [];
    $settings = $this->config('phpunit_tests.settings');
    $settings->set('testsuite_mysql_storage_type', $form_state->getValue('testsuite_mysql_storage_type'))
      ->set('testsuite_simple_base_url', $form_state->getValue('testsuite_simple_base_url'))
      ->set('testsuite_mysql_host', $form_state->getValue('testsuite_mysql_host'))
      ->set('testsuite_mysql_username', $form_state->getValue('testsuite_mysql_username'))
      ->set('testsuite_mysql_password', $form_state->getValue('testsuite_mysql_password'))
      ->set('testsuite_mysql_port', $form_state->getValue('testsuite_mysql_port'))
      ->set('testsuite_mysql_database_name', $form_state->getValue('testsuite_mysql_database_name'))
      ->set('testsuite_browser_output_directory', $form_state->getValue('testsuite_browser_output_directory'))
      ->set('testsuite_browser_output_base_url', $form_state->getValue('testsuite_browser_output_base_url'))
      ->set('testsuite_disable_deprecation_testing', $form_state->getValue('testsuite_disable_deprecation_testing'))
      ->set('testsuite_selectively_ignore_deprecation', $form_state->getValue('testsuite_selectively_ignore_deprecation'))
      ->set('testsuite_loaded_test_types', $form_state->getValue('testsuite_loaded_test_types'))
      ->save();
    if ($form_state->getValue('testsuite_mysql_storage_type') == 1) {
      $connection_info = $this->connection->getConnectionOptions();
      $data['mysql_host'] = $connection_info['host'];
      $data['mysql_username'] = $connection_info['username'];
      $data['mysql_password'] = $connection_info['password'];
      $data['mysql_port'] = $connection_info['port'];
      $data['mysql_database_name'] = $connection_info['database'];
    }
    else {
      $data['mysql_host'] = $form_state->getValue('testsuite_mysql_host');
      $data['mysql_username'] = $form_state->getValue('testsuite_mysql_username');
      $data['mysql_password'] = $form_state->getValue('testsuite_mysql_password');
      $data['mysql_port'] = $form_state->getValue('testsuite_mysql_port');
      $data['mysql_database_name'] = $form_state->getValue('testsuite_mysql_database_name');
    }
    $data['simple_base_url'] = $form_state->getValue('testsuite_simple_base_url');
    $data['browser_output_directory'] = $form_state->getValue('testsuite_browser_output_directory');
    $data['browser_output_base_url'] = $form_state->getValue('testsuite_browser_output_base_url');
    $data['disable_deprecation_testing'] = $form_state->getValue('testsuite_disable_deprecation_testing');
    if ($form_state->getValue('testsuite_disable_deprecation_testing') == 2) {
      $settings->set('testsuite_configure_deprecation_helper', $form_state->getValue('testsuite_configure_deprecation_helper'))->save();
      $data['configure_deprecation_helper'] = $form_state->getValue('testsuite_configure_deprecation_helper');
    }
    $settings->set('testsuite_mink_driver_class', $form_state->getValue('testsuite_mink_driver_class'))
      ->set('testsuite_mink_driver_args', $form_state->getValue('testsuite_mink_driver_args'))
      ->set('testsuite_mink_driver_args_webdriver', $form_state->getValue('testsuite_mink_driver_args_webdriver'))
      ->save();
    $data['mink_driver_class'] = $form_state->getValue('testsuite_mink_driver_class');
    $data['mink_driver_args'] = $form_state->getValue('testsuite_mink_driver_args');
    $data['mink_driver_args_webdriver'] = $form_state->getValue('testsuite_mink_driver_args_webdriver');

    if ($this->fileResourceService->ifCoreIsWritable()) {
      $this->fileResourceService->rebuildPhpUnitXml($data);
    }
    else {
      $this->messenger->addStatus('The phpunit.xml file was unable to be created. This is most likely due to permissions on the core folder.');
    }
    if ($form_state->getValue('testsuite_browser_output_directory') != "") {
      $this->fileResourceService->createBrowserOutputFolderIfNotExists($form_state->getValue('testsuite_browser_output_directory'));
    }

    parent::submitForm($form, $form_state);
  }

}
