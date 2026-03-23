<?php

namespace Drupal\plan_de_estudios\Controller;

use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Controller principal para la gestión y visualización de planes de estudios.
 */
class PlanDeEstudiosController extends ControllerBase {

  protected $httpClient;
  protected $apiUrl = 'http://localhost:5000';

  /**
   * Constructor: Inyecta el cliente HTTP de Drupal para comunicaciones externas.
   */
  public function __construct(ClientInterface $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * Dependecy Injection: Carga el servicio http_client desde el contenedor de Drupal.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client')
    );
  }

  /**
   * Obtiene el ID del programa basado en la ruta del sitio (multisite).
   * Ejemplo: 'sites/1' -> devuelve '1'
   */
  private function getCurrentProgramId() {
    // Si es 'kernel', obtener la ruta de nuevo para asegurar consistencia
    $site_path = \Drupal::service('kernel')->getSitePath();
    $parts = explode('/', str_replace('\\', '/', $site_path));
    $folder = end($parts);
    
    // Si es 'default' o no es un número, no hay un programa específico
    return ($folder === 'default' || !is_numeric($folder)) ? NULL : $folder;
  }

  /**
   * Valida si el plan solicitado pertenece al programa actual del sitio.
   */
  private function isValidPlanForCurrentProgram($id_programa) {
    $id_programa_actual = $this->getCurrentProgramId();
    
    // Si no se puede determinar el programa actual, por seguridad denegamos.
    if (!$id_programa_actual) {
      return FALSE;
    }

    try {
      // Consultar a la API los planes autorizados para este programa.
      $response = $this->httpClient->request('GET', $this->apiUrl . '/programas/' . $id_programa_actual . '/planes', [
        'timeout' => 10,
      ]);
      $data = json_decode($response->getBody()->getContents(), TRUE);
      
      if ($data === NULL || !isset($data['planes'])) {
        return FALSE;
      }

      // El UID del plan solicitado debe estar en la lista de planes del programa.
      foreach ($data['planes'] as $plan) {
        if ($plan['uid'] === $id_programa) {
          return TRUE;
        }
      }
    } catch (\Exception $e) {
      \Drupal::logger('plan_de_estudios')->error('Security check failed: @error', ['@error' => $e->getMessage()]);
    }

    return FALSE;
  }





  /**
   * Obtiene y valida los datos de un programa específico (datos preprocesados).
   */
  private function getProgramaCompleto($id_programa) {
    try {
      $response = $this->httpClient->request('GET', $this->apiUrl . '/plan/' . $id_programa, [
        'timeout' => 15,
      ]);
      $data = json_decode($response->getBody()->getContents(), TRUE);
      
      if ($data === NULL || isset($data['error'])) {
          return NULL;
      }
      
      return $data;
    } catch (\Exception $e) {
      \Drupal::logger('plan_de_estudios')->error('Error fetching processed plan from API: @error', ['@error' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Genera la respuesta para descargar el plan de estudios en formato PDF.
   * 
   * @param string $id_programa
   *   El identificador del curso al que pertenece el plan.
   */
  public function descargarPdf($id_programa) {
    // Validar acceso antes de cualquier procesamiento.
    if (!$this->isValidPlanForCurrentProgram($id_programa)) {
      throw new AccessDeniedHttpException();
    }

    // 1. Obtener todos los datos necesarios procesados desde Flask
    $programa_data = $this->getProgramaCompleto($id_programa);
    
    // Redirigir si no hay datos
    if ($programa_data === NULL) return $this->redirect('<front>');

    // 2. Preparar objetos y variables para la plantilla PDF
    $programa = (object) $programa_data['programa'];
    $cursos_por_semestre = [];
    foreach ($programa_data['cursos_por_semestre'] as $sem => $cursos) {
      $cursos_por_semestre[$sem] = array_map(function($c) { return (object) $c; }, $cursos);
    }
    $stats = $programa_data['stats'];
    
    // Desglosar estadísticas para facilitar su uso en PHP puro
    $total_creditos = $stats['total_creditos'];
    $total_cursos = $stats['total_cursos'];
    $total_ht = $stats['total_ht'];
    $total_hp = $stats['total_hp'];
    $total_th = $stats['total_th'];
    $estadisticas = array_merge($stats['estudios'], $stats['creditos_estudios'], $stats['condicion'], $stats['creditos_condicion']);

    // 3. Cargar la plantilla y capturar su salida (buffer)
    ob_start();
    include __DIR__ . '/../../templates/pdf_template.php';
    $html = ob_get_clean();

    // 4. Inicializar DOMPDF para convertir HTML a PDF
    require_once DRUPAL_ROOT . '/vendor/autoload.php';
    $dompdf = new \Dompdf\Dompdf(['defaultFont' => 'Helvetica', 'isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // 5. Enviar el stream al navegador para descarga
    $dompdf->stream('plan_' . $programa->codigo . '_' . date('d-m-Y') . '.pdf', array('Attachment' => 1));
    exit;
  }

  /**
   * Muestra el detalle del programa en la web utilizando los componentes de Drupal.
   * 
   * @param string $id_programa
   *   El ID del programa a visualizar.
   * 
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Render array para la página o una redirección.
   */
  public function programaDetail($id_programa) {
    // Validar acceso antes de cualquier procesamiento.
    if (!$this->isValidPlanForCurrentProgram($id_programa)) {
      throw new AccessDeniedHttpException();
    }

    // 1. Obtener datos procesados
    $programa_data = $this->getProgramaCompleto($id_programa);
    if ($programa_data === NULL) return $this->redirect('<front>');

    try {
      // 2. Desglosar datos
      $programa = (object) $programa_data['programa'];
      $cursos_por_semestre = [];
      foreach ($programa_data['cursos_por_semestre'] as $sem => $cursos) {
        $cursos_por_semestre[$sem] = array_map(function($c) { return (object) $c; }, $cursos);
      }
      $stats = $programa_data['stats'];
      
      $estadisticas_badge = array_merge($stats['estudios'], $stats['condicion']);
      $estadisticas_resumen = array_merge($stats['estudios'], $stats['creditos_estudios'], $stats['condicion'], $stats['creditos_condicion']);

      $build = [];

      // 3. Construir Bloque de Información del Programa
      $build['info_programa'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('INFORMACIÓN DEL PROGRAMA'),
        '#attributes' => ['class' => ['programa-info']],
        'content' => $this->renderizarInformacionPrograma($programa, $stats['total_creditos'], $stats['total_ht'], $stats['total_hp'], $stats['total_th'], $estadisticas_badge),
      ];

      // 4. Construir Contenedor de Semestres
      $build['plan_semestres'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('PLAN DE ESTUDIOS POR SEMESTRE'),
        '#attributes' => ['class' => ['plan-semestres']],
        'content' => ['#type' => 'container', '#attributes' => ['class' => ['semestres-container']]],
      ];

      // Renderizar cada semestre y acumular créditos
      $creditos_acumulados = 0;
      for ($semestre = 1; $semestre <= $programa->duracion_semestres; $semestre++) {
        $res = $this->renderizarSemestre($semestre, $cursos_por_semestre[$semestre] ?? [], $creditos_acumulados);
        $build['plan_semestres']['content']['semestre_' . $semestre] = $res['render'];
        $creditos_acumulados = $res['creditos_acumulados'];
      }

      // 5. Construir Bloque de Resumen General al final de la tabla
      $build['resumen_general'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('RESUMEN GENERAL DEL PROGRAMA'),
        '#attributes' => ['class' => ['resumen-general']],
        'content' => $this->renderizarResumenGeneral($programa, $stats['total_creditos'], $stats['total_ht'], $stats['total_hp'], $stats['total_th'], $estadisticas_resumen),
      ];

      // 6. Botones de acción (Descarga y Volver)
      $build['actions'] = [
        '#type' => 'container',
        '#attributes' => ['style' => 'display: flex; gap: 10px; margin-top: 20px;'],
        'download' => [
          '#type' => 'link',
          '#title' => ['#markup' => '<i class="fas fa-file-pdf"></i> ' . $this->t('Descargar Plan en PDF')],
          '#url' => \Drupal\Core\Url::fromRoute('plan_de_estudios.descargar_pdf', ['id_programa' => $id_programa]),
          '#attributes' => ['class' => ['btn-download']],
        ],
        'back' => [
          '#type' => 'link',
          '#title' => ['#markup' => '<i class="fas fa-arrow-left"></i> ' . $this->t('Volver')],
          '#url' => \Drupal\Core\Url::fromRoute('<front>'),
          '#attributes' => ['class' => ['back-link']],
        ],
      ];

      // Adjuntar librerías (Assets) definidos en plan_de_estudios.libraries.yml
      $build['#attached']['library'] = ['plan_de_estudios/styles', 'plan_de_estudios/open-sans', 'plan_de_estudios/font-awesome'];
      $build['#title'] = $programa->plan_nombre;

      return $build;
    } catch (\Exception $e) {
      return ['#markup' => $this->t('Error al cargar el programa: @error', ['@error' => $e->getMessage()])];
    }
  }

  /**
   * Genera el render array para la sección superior de información del programa.
   * 
   * @return array
   *   Componente visual con GRID de detalles y BADGES de estadísticas.
   */
  private function renderizarInformacionPrograma($programa, $total_creditos, $total_horas_teoricas, $total_horas_practicas, $total_horas, $estadisticas) {
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['programa-details']],
      'grid' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['programa-details-grid']],
        // Renderizar cada item de la cuadrícula superior
        'programa' => $this->buildDetailItem('fa-graduation-cap', 'Programa', $programa->nombre, 'Código: ' . $programa->codigo),
        'duracion' => $this->buildDetailItem('fa-calendar-alt', 'Duración', $programa->duracion_semestres . ' semestres'),
        'creditos' => $this->buildDetailItem('fa-star', 'Créditos', $total_creditos),
        'horas' => $this->buildDetailItem('fa-clock', 'Horas', $total_horas . ' h', 'HT: ' . $total_horas_teoricas . ' | HP: ' . $total_horas_practicas),
      ],
    ];
  }

  /**
   * Construye un item de detalle para la cuadrícula.
   */
  private function buildDetailItem($icono, $label, $valor, $detalle = '') {
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['detail-item']],
      'icono' => ['#markup' => '<i class="fas ' . $icono . '"></i>'],
      'titulo' => ['#markup' => '<div class="detail-label">' . $this->t($label) . '</div>'],
      'valor' => ['#markup' => '<div class="detail-value">' . $valor . '</div>'],
      'detalle' => $detalle ? ['#markup' => '<div>' . $detalle . '</div>'] : [],
    ];
  }

  

  /**
   * Genera el render array para un semestre individual (Header + Tabla + Footer).
   */
  private function renderizarSemestre($semestre, $cursos, &$creditos_acumulados) {
    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['semestre-wrapper']],
    ];
    
    // Header del semestre con fondo blanco y acento lateral
    $build['header'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['semestre-header']],
      'icono' => ['#markup' => '<i class="fas fa-book"></i>'],
      'titulo' => ['#markup' => '<h3>SEMESTRE ' . $semestre . '</h3>'],
    ];
    
    // Manejo de caso sin cursos
    if (empty($cursos)) {
      $build['empty'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['semestre-vacio']],
        'icono' => ['#markup' => '<i class="fas fa-info-circle"></i>'],
        'mensaje' => ['#markup' => '<p>No hay cursos asignados para este semestre.</p>'],
      ];
      return ['render' => $build, 'creditos_acumulados' => $creditos_acumulados];
    }

    $rows = [];
    $total_creditos_semestre = 0;
    $total_horas_teoricas = 0;
    $total_horas_practicas = 0;
    $numero_orden = 1;
    
    // Construir filas de la tabla
    foreach ($cursos as $curso) {
      $rows[] = $this->buildFilaCurso($curso, $numero_orden);

      $total_creditos_semestre += $curso->creditos;
      $total_horas_teoricas += $curso->horas_teoricas;
      $total_horas_practicas += $curso->horas_practicas;
      $numero_orden++;
    }

    $creditos_acumulados += $total_creditos_semestre;

    // Tabla de cursos con cabeceras agrupadas para horas
    $build['table_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['table-responsive']],
    ];

    $build['table_wrapper']['table'] = [
      '#type' => 'table',
      '#attributes' => ['class' => ['semestre-cursos-table']],
      '#header' => [
        ['data' => 'N°', 'class' => ['orden-header']],
        ['data' => 'Código', 'class' => ['codigo-header']],
        ['data' => 'Curso', 'class' => ['curso-header']],
        ['data' => 'Créd.', 'class' => ['credito-header']],
        ['data' => 'N° HORAS', 'class' => ['horas-agrupadas-header'], 'colspan' => 3],
        ['data' => 'HV', 'class' => ['hv-header']],
        ['data' => 'Área', 'class' => ['tipo-header']],
        ['data' => 'Condición', 'class' => ['condicion-header']],
        ['data' => 'Prerrequisito', 'class' => ['prerrequisito-header']],
      ],
      '#rows' => $rows,
    ];

    // Footer del semestre con subtotales y acumulado
    $build['footer'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['semestre-footer']],
      'left' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['footer-left']],
        'label' => ['#markup' => '<span class="footer-label">' . $this->t('SUBTOTALES:') . '</span>'],
        'cursos' => ['#markup' => '<span class="footer-item footer-value">' . count($cursos) . ' ' . $this->t('cursos') . '</span>'],
        'creditos' => ['#markup' => '<span class="footer-item footer-value">' . $total_creditos_semestre . ' ' . $this->t('créditos') . '</span>'],
        'ht' => ['#markup' => '<span class="footer-item footer-value">HT:' . $total_horas_teoricas . '</span>'],
        'hp' => ['#markup' => '<span class="footer-item footer-value">HP:' . $total_horas_practicas . '</span>'],
        'total' => ['#markup' => '<span class="footer-item footer-value">TH:' . ($total_horas_teoricas + $total_horas_practicas) . '</span>'],
      ],
      'right' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['footer-right']],
        'acumulado' => ['#markup' => '<span class="acumulado-badge"><span class="footer-label">' . $this->t('CRÉDITOS ACUMULADOS:') . '</span> <span class="acumulado-valor">' . $creditos_acumulados . '</span></span>'],
      ],
    ];

    return ['render' => $build, 'creditos_acumulados' => $creditos_acumulados];
  }

  /**
   * Formatea la cadena de prerrequisitos convirtiéndola en badges HTML.
   */
  private function formatPrerequisitos($prerequisitos) {
    $pre = trim($prerequisitos);
    if (!empty($pre) && strtoupper($pre) !== 'NINGUNO' && strtoupper($pre) !== 'NONE') {
      // Manejar múltiples prerrequisitos separados por coma
      if (strpos($pre, ',') !== false) {
        $codigos = explode(',', $pre);
        $badges = [];
        foreach ($codigos as $codigo) {
          $badges[] = '<span class="prerrequisito-badge">' . trim($codigo) . '</span>';
        }
        return implode(' ', $badges);
      }
      return '<span class="prerrequisito-badge">' . $pre . '</span>';
    }
    return '<span class="prerrequisito-none">Ninguno</span>';
  }

  /**
   * Calcula y formatea el valor de las Horas Virtuales (HV).
   */
  private function getHvValor($curso) {
    if (empty($curso->hv_requerido)) {
      return '<span class="hv-none">0</span>';
    }
    $horas_virtuales = $curso->horas_virtuales ?? 0;
    // Si no hay horas definidas pero es requerido, se asume un valor base de 0
    $valor = ($horas_virtuales > 0) ? (string) $horas_virtuales : '0';
    return '<span class="hv-numero">' . $valor . '</span>';
  }

  /**
   * Construye una fila de curso confiando en los datos de la estructura Flask.
   */
  private function buildFilaCurso($curso, $numero_orden) {
    return [
      'data' => [
        ['data' => ['#markup' => '<span class="orden-numero">' . $numero_orden . '</span>']],
        ['data' => ['#markup' => '<span class="codigo-curso">' . $curso->codigo_curso . '</span>']],
        ['data' => ['#markup' => '<span class="curso-nombre">' . $curso->nombre . '</span>']],
        ['data' => ['#markup' => '<span class="credito-valor">' . $curso->creditos . '</span>']],
        ['data' => ['#markup' => '<span class="hora-valor hora-teorica">' . $curso->horas_teoricas . '</span>']],
        ['data' => ['#markup' => '<span class="hora-valor hora-practica">' . $curso->horas_practicas . '</span>']],
        ['data' => ['#markup' => '<span class="hora-valor hora-total">' . $curso->total_horas . '</span>']],
        ['data' => ['#markup' => $this->getHvValor($curso)]],
        ['data' => ['#markup' => '<span class="tipo-badge">' . $curso->area . '</span>']],
        ['data' => ['#markup' => '<span class="condicion-badge">' . $curso->condicion . '</span>']],
        ['data' => ['#markup' => $this->formatPrerequisitos($curso->prerequisitos)]],
      ],
    ];
  }

  /**
   * Renderiza resumen general.
   */
  private function renderizarResumenGeneral($programa, $total_creditos, $total_horas_teoricas, $total_horas_practicas, $total_horas, $estadisticas) {
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['resumen-content']],
      'grid' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['resumen-grid']],
        'General' => $this->buildResumenCard('fa-check-circle', $estadisticas['Estudios Generales'] ?? 0, 'Estudios Generales', ($estadisticas['creditos_General'] ?? 0) . ' créd.'),
        'Específico' => $this->buildResumenCard('fa-check-square', $estadisticas['Estudios Específicos'] ?? 0, 'Estudios Específicos', ($estadisticas['creditos_Específico'] ?? 0) . ' créd.'),
        'Especialidad' => $this->buildResumenCard('fa-star', $estadisticas['Estudios de Especialidad'] ?? 0, 'Estudios de Especialidad', ($estadisticas['creditos_Especialidad'] ?? 0) . ' créd.'),
        'Obligatorio' => $this->buildResumenCard('fa-check-circle', $estadisticas['Obligatorio'] ?? 0, 'Obligatorios', ($estadisticas['creditos_Obligatorio'] ?? 0) . ' créd.'),
        'Electivo' => $this->buildResumenCard('fa-check-square', $estadisticas['Electivo'] ?? 0, 'Electivos', ($estadisticas['creditos_Electivo'] ?? 0) . ' créd.'),
      ],
    ];
  }

  /**
   * Construye una tarjeta de resumen.
   */
  private function buildResumenCard($icono, $numero, $etiqueta, $detalle = '') {
    $card = [
      '#type' => 'container',
      '#attributes' => ['class' => ['resumen-card']],
      'icono' => ['#markup' => '<div class="resumen-icon"><i class="fas ' . $icono . '"></i></div>'],
      'numero' => ['#markup' => '<div class="resumen-numero">' . $numero . '</div>'],
      'etiqueta' => ['#markup' => '<div class="resumen-etiqueta">' . $etiqueta . '</div>'],
    ];
    if ($detalle) {
      $card['detalle'] = ['#markup' => '<div class="resumen-detalle">' . $detalle . '</div>'];
    }
    return $card;
  }


}
