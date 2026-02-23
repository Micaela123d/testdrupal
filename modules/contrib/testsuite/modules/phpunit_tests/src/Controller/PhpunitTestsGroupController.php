<?php

namespace Drupal\phpunit_tests\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\PagerSelectExtender;
use Drupal\Core\Database\Query\TableSortExtender;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\phpunit_tests\PhpunitTestsRepositoryService;
use Drupal\phpunit_tests\PhpunitTestsResourceService;
use Drupal\testsuite\BaseTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for dblog routes.
 */
class PhpunitTestsGroupController extends ControllerBase {
  use BaseTrait;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The resourse service.
   *
   * @var \Drupal\phpunit_tests\PhpunitTestsResourceService
   */
  protected $phpunitTestsResourceService;

  /**
   * The resourse service.
   *
   * @var \Drupal\phpunit_tests\PhpunitTestsRepositoryService
   */
  protected $phpunitTestsRepositoryService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('messenger'),
      $container->get('database'),
      $container->get('module_handler'),
      $container->get('date.formatter'),
      $container->get('form_builder'),
      $container->get('phpunit_tests.load_resource.service'),
      $container->get('phpunit_tests.repository.service')
    );
  }

  /**
   * Constructs a PhpunitTestsController object.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Load messenger service.
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   A module handler.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   * @param \Drupal\phpunit_tests\PhpunitTestsResourceService $phpunitTestsResourceService
   *   A database connection.
   * @param \Drupal\phpunit_tests\PhpunitTestsRepositoryService $phpunitTestsRepositoryService
   *   A database connection.
   */
  public function __construct(
    MessengerInterface $messenger,
    Connection $database,
    ModuleHandlerInterface $module_handler,
    DateFormatterInterface $date_formatter,
    FormBuilderInterface $form_builder,
    PhpunitTestsResourceService $phpunitTestsResourceService,
    PhpunitTestsRepositoryService $phpunitTestsRepositoryService,
  ) {
    $this->messenger = $messenger;
    $this->database = $database;
    $this->moduleHandler = $module_handler;
    $this->dateFormatter = $date_formatter;
    $this->formBuilder = $form_builder;
    $this->phpunitTestsResourceService = $phpunitTestsResourceService;
    $this->phpunitTestsRepositoryService = $phpunitTestsRepositoryService;
  }

  /**
   * Displays a listing of database log messages.
   *
   * @return array
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   *
   * @see Drupal\phpunit_tests\Controller\PhpunitTestsGroupController::eventDetails()
   */
  public function overview() {
    $rows = [];

    $build['phpunit_test_create_group_form'] = $this->formBuilder()->getForm('Drupal\phpunit_tests\Form\PhpunitTestsCreateGroupForm');
    $header = [
      [
        'data' => $this->t('Date Created'),
        'field' => 'putg.created',
        'sort' => 'desc',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      [
        'data' => $this->t('Name'),
        'field' => 'putg.name',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      [
        'data' => $this->t('Actions'),
        'class' => [
          RESPONSIVE_PRIORITY_MEDIUM,
          'testsuite-text-right',
        ],
      ],
    ];

    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->database->select('phpunit_test_group', 'putg')
      ->extend(PagerSelectExtender::class)
      ->extend(TableSortExtender::class);
    $query->fields(
      'putg',
      [
        'id',
        'name',
        'created',
      ]
    );

    $result = $query
      ->limit(50)
      ->orderByHeader($header)
      ->execute();

    foreach ($result as $testItem) {
      $name = Html::escape($testItem->name);
      $testLink = Link::fromTextAndUrl(
        $name, new Url(
          'phpunit_tests.group_event', ['event_id' => $testItem->id], [
            'attributes' => [
              'title' => 'Lings to group ' . $name . ' items.',
            ],
          ]
        )
      )->toString();
      // A Link for Running the results in a slideshow?
      $runLink = Link::fromTextAndUrl(
        'Test', new Url(
          'phpunit_tests.run_tests_group', ['groupid' => $testItem->id], [
            'attributes' => [
              'title' => 'Execute tests in ' . $name . '.',
              'class' => [
                'testsuite-green',
              ],
            ],
          ]
        )
      )->toString();
      $deleteLink = Link::fromTextAndUrl(
        'Delete', new Url(
          'phpunit_tests.group_delete_confirm', ['id' => $testItem->id], [
            'attributes' => [
              'title' => 'Delete ' . $name . '.',
              'class' => [
                'testsuite-red',
              ],
            ],
          ]
        )
      )->toString();
      $rows[] = [
        'created' => [
          'data' => [
            '#markup' => $this->dateFormatter->format($testItem->created, 'short'),
          ],
        ],
        'name' => [
          'data' => [
            '#markup' => $testLink,
          ],
        ],
        'action' => [
          'data' => [
            '#markup' => $runLink . " / " . $deleteLink,
          ],
          'class' => [
            'testsuite-text-right',
          ],
        ],
      ];
    }

    $build['phpunit_group_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No log messages available.'),
    ];

    $build['phpunit_group_pager'] = ['#type' => 'pager'];

    return $build;

  }

  /**
   * Runs the tests assigned to a group and saves them to the database.
   *
   * @param int $groupid
   *   Unique ID of the group.
   */
  public function runGroupTests($groupid) {
    $db = $this->database->query("SELECT * FROM {phpunit_test_group_item} WHERE [phpunit_test_group_id] = :id", [':id' => $groupid])->fetchAllAssoc('test', \PDO::FETCH_ASSOC);
    // dd($db);
    $ifError = FALSE;
    $errorCount = 0;
    $testCount = 0;
    foreach ($db as $test => $item) {
      $results = $this->phpunitTestsResourceService->getPhpunitStatement(
            $item['area'],
            $item['module'],
            $item['directory'],
            $test
        );

      if ($results != NULL) {
        if ($this->phpunitTestsRepositoryService->createLog(
              $item['area'],
              $item['module'],
              $item['directory'],
              $test,
              $results
          )) {
          $testCount++;
        }
        else {
          $ifError = TRUE;
          $errorCount++;
        }
      }
      else {
        $this->messenger->addStatus('Phpunit test could not be recorded. Please be sure all packages are loaded and the phpunit.xml is created with the right data.');
      }
    }
    if (!$ifError) {
      $this->messenger->addStatus($testCount . ' phpunit test has been recorded.');
    }
    else {
      $this->messenger->addStatus($errorCount . ' phpunit test could not not been recorded. ' . $testCount . ' phpunit test has been recorded.. Please be sure all packages are loaded and the phpunit.xml is created with the right data.');
    }

    return $this->redirect('phpunit_tests.phpunit_report');
  }

  /**
   * Displays details about a specific database log message.
   *
   * @param Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param int $event_id
   *   Unique ID of the database log message.
   *
   * @return array
   *   If the ID is located in the Database Logging table, a build array in the
   *   format expected by \Drupal\Core\Render\RendererInterface::render().
   *
   * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
   *   If no event found for the given ID.
   */
  public function eventDetails(Request $request, $event_id) {
    $testItem = $this->database->query('SELECT [putg].* FROM {phpunit_test_group} [putg] WHERE [putg].[id] = :id', [':id' => $event_id])->fetchObject();

    if (empty($testItem)) {
      throw new NotFoundHttpException();
    }

    $build = [];
    $rows = [
      [
      ['data' => $this->t('Id'), 'header' => TRUE],
        Html::escape($testItem->id),
      ],
      [
      ['data' => $this->t('Date Created'), 'header' => TRUE],
        $this->dateFormatter->format($testItem->created, 'long'),
      ],
      [
      ['data' => $this->t('Name'), 'header' => TRUE],
        Html::escape($testItem->name),
      ],
    ];

    $build['phpunit_group_items_table'] = [
      '#type' => 'table',
      '#rows' => $rows,
    ];

    /** @var \Symfony\Component\HttpFoundation\Request $request */
    $filter = $this->buildFilterQuery($request, $event_id);

    // dd($filter);
    $build['create_phpunit_add_group_item_form'] = $this->formBuilder()->getForm('Drupal\phpunit_tests\Form\PhpunitTestsAddToGroupMultistepForm');
    $rows = [];

    $build['phpunit_group_item_filter_form'] = $this->formBuilder()->getForm('Drupal\phpunit_tests\Form\PhpunitTestsGroupItemFilterForm');
    $header = [
      [
        'data' => $this->t('Date Created'),
        'field' => 'putgi.created',
        'sort' => 'desc',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      [
        'data' => $this->t('Test Name'),
        'field' => 'putgi.test',
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ],
      [
        'data' => $this->t('Area'),
        'field' => 'putgi.area',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      [
        'data' => $this->t('Module'),
        'field' => 'putgi.module',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      [
        'data' => $this->t('Test Type'),
        'field' => 'putgi.directory',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      [
        'data' => $this->t('Actions'),
        'class' => [
          RESPONSIVE_PRIORITY_MEDIUM,
          'testsuite-text-right',
        ],
      ],
    ];

    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $this->database->select('phpunit_test_group_item', 'putgi')
      ->extend(PagerSelectExtender::class)
      ->extend(TableSortExtender::class);
    $query->fields(
      'putgi',
      [
        'id',
        'test',
        'area',
        'module',
        'directory',
        'created',
      ]
    );

    if (!empty($filter['where'])) {
      $query->where($filter['where'], $filter['args']);
    }

    $result = $query
      ->limit(50)
      ->orderByHeader($header)
      ->execute();

    foreach ($result as $testItem) {
      $test = Html::escape($testItem->test);
      $deleteLink = Link::fromTextAndUrl(
        'Delete', new Url(
          'phpunit_tests.group_item_delete_confirm', ['id' => $event_id . "-" . $testItem->id], [
            'attributes' => [
              'title' => $test,
              'class' => [
                'testsuite-red',
              ],
            ],
          ]
        )
      )->toString();
      $rows[] = [
        'created' => [
          'data' => [
            '#markup' => $this->dateFormatter->format($testItem->created, 'short'),
          ],
        ],
        'test' => [
          'data' => [
            '#markup' => $test,
          ],
        ],
        'area' => [
          'data' => [
            '#markup' => Html::escape($testItem->area),
          ],
        ],
        'module' => [
          'data' => [
            '#markup' => Html::escape($testItem->module),
          ],
        ],
        'directory' => [
          'data' => [
            '#markup' => Html::escape($testItem->directory),
          ],
        ],
        'action' => [
          'data' => [
            '#markup' => $deleteLink,
          ],
          'class' => [
            'testsuite-text-right',
          ],
        ],
      ];
    }

    $build['phpunit_group_item_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No log messages available.'),
    ];

    $build['phpunit_group_item_pager'] = ['#type' => 'pager'];

    return $build;
  }

  /**
   * Builds a query for database log administration filters based on session.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   * @param int $event_id
   *   Unique ID of the database log message.
   *
   * @return array|null
   *   An associative array with keys 'where' and 'args' or NULL if there were
   *   no filters set.
   */
  protected function buildFilterQuery(Request $request, $event_id) {
    $session_filters = $request->getSession()->get('phpunit_tests_group_item_overview_filter', []);
    if (empty($session_filters)) {
      return;
    }

    $session_filters['phpunit_test_group_id'] = $event_id;

    // dd($session_filters);
    $filters = [];
    foreach ($session_filters as $key => $filter) {
      if ($filter != "") {
        $filters[$key]['where'] = 'putgi.' . $key . ' = ?';
      }
    }

    // Build query.
    $where = $args = [];
    foreach ($session_filters as $key => $filter) {
      if ($filter != "") {
        $filter_where = [];
        $filter_where[] = $filters[$key]['where'];
        $args[] = $filter;

        if (count($filter_where) > 0) {
          $where[] = '(' . implode(' AND ', $filter_where) . ')';
        }
      }
    }
    $where = !empty($where) ? implode(' AND ', $where) : '';

    return [
      'where' => $where,
      'args' => $args,
    ];
  }

}
