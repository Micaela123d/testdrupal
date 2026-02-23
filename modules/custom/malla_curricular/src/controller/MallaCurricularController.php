<?php

namespace Drupal\malla_curricular\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller para mostrar la malla curricular.
 */
class MallaCurricularController extends ControllerBase {

  protected $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Muestra la lista de carreras.
   */
  public function display() {
    // Verificar si la tabla existe
    if (!$this->database->schema()->tableExists('carreras')) {
      return ['#markup' => $this->t('La tabla "carreras" no existe.')];
    }

    try {
      $query = $this->database->select('carreras', 'c')
        ->fields('c', [
          'id_carrera', 'nombre', 'codigo', 'duracion_semestres',
          'total_creditos', 'total_horas_teoricas', 'total_horas_practicas', 'total_horas'
        ])
        ->orderBy('c.nombre', 'ASC');
      
      $result = $query->execute();

      $rows = [];
      $total_general_creditos = 0;
      
      foreach ($result as $carrera) {
        $rows[] = [
          'data' => [
            [
              'data' => [
                '#markup' => '<div class="carrera-name-cell">' . $carrera->nombre . '</div>',
              ],
              'class' => ['carrera-name-cell'],
            ],
            [
              'data' => ['#markup' => '<span class="codigo-text">' . $carrera->codigo . '</span>'],
              'class' => ['codigo-cell'],
            ],
            [
              'data' => [
                '#markup' => '<div class="semestre-count"><i class="fas fa-clock"></i> ' . $carrera->duracion_semestres . ' semestres</div>',
              ],
              'class' => ['duracion-cell'],
            ],
            [
              'data' => [
                '#markup' => '<div class="creditos-totales"><i class="fas fa-star"></i> ' . ($carrera->total_creditos ?: 0) . ' créditos</div>',
              ],
              'class' => ['creditos-cell'],
            ],
            [
              'data' => [
                '#markup' => '<div class="horas-totales"><i class="fas fa-clock"></i> T:' . ($carrera->total_horas_teoricas ?: 0) . ' P:' . ($carrera->total_horas_practicas ?: 0) . ' H:' . ($carrera->total_horas ?: 0) . '</div>',
              ],
              'class' => ['horas-cell'],
            ],
            [
              'data' => [
                '#type' => 'link',
                '#title' => $this->t('Ver Malla <i class="fas fa-arrow-right"></i>'),
                '#url' => \Drupal\Core\Url::fromRoute('malla_curricular.carrera', ['id_carrera' => $carrera->id_carrera]),
                '#attributes' => ['class' => ['btn-view-malla']],
              ],
              'class' => ['acciones-cell'],
            ],
          ],
        ];
        
        $total_general_creditos += $carrera->total_creditos ?: 0;
      }

      $build = [];

      $build['table_wrapper'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['table-wrapper']],
      ];

      $build['table_wrapper']['header'] = [
        '#markup' => '<div class="table-header"><h2><i class="fas fa-book-open"></i> ' . $this->t('Programas Disponibles') . '</h2></div>',
      ];

      $build['table_wrapper']['table'] = [
        '#type' => 'table',
        '#attributes' => ['class' => ['carreras-table']],
        '#header' => [
          ['data' => $this->t('Nombre del programa'), 'class' => ['carrera-name-header']],
          ['data' => $this->t('Código'), 'class' => ['codigo-header']],
          ['data' => $this->t('Duración'), 'class' => ['duracion-header']],
          ['data' => $this->t('Créditos'), 'class' => ['creditos-header']],
          ['data' => $this->t('Horas (T/P/H)'), 'class' => ['horas-header']],
          ['data' => $this->t('Acciones'), 'class' => ['acciones-header']],
        ],
        '#rows' => $rows,
        '#empty' => $this->t('No hay programas registrados.'),
      ];

      $build['table_wrapper']['footer'] = [
        '#markup' => '<div class="table-footer"><div class="carrera-count"><i class="fas fa-info-circle"></i> ' . $this->t('Total: @count carreras | Créditos totales: @creditos', ['@count' => count($rows), '@creditos' => $total_general_creditos]) . '</div><div><i class="fas fa-sync-alt"></i> ' . $this->t('Actualizado: @fecha', ['@fecha' => date('d/m/Y')]) . '</div></div>',
      ];

      $build['#attached']['library'][] = 'malla_curricular/styles';

      return $build;
      
    } catch (\Exception $e) {
      return ['#markup' => $this->t('Error al cargar las carreras: @error', ['@error' => $e->getMessage()])];
    }
  }

  /**
   * Descarga la malla curricular en PDF (vertical, limpio y ordenado)
   */
  public function descargarPdf($id_carrera) {
    // ============================================
    // OBTENER LOS DATOS
    // ============================================
    $carrera = $this->database->select('carreras', 'c')
      ->fields('c', [
        'nombre', 'codigo', 'duracion_semestres',
        'total_creditos', 'total_horas_teoricas', 'total_horas_practicas', 'total_horas'
      ])
      ->condition('c.id_carrera', $id_carrera)
      ->execute()
      ->fetchObject();

    if (!$carrera) {
      $this->messenger()->addError('Carrera no encontrada.');
      return $this->redirect('malla_curricular.display');
    }

    // Obtener cursos
    $query = $this->database->select('cursos', 'cu')
      ->fields('cu', [
        'codigo_curso', 'nombre', 'semestre', 'creditos',
        'prerequisitos', 'numero_orden', 'horas_teoricas', 'horas_practicas',
        'total_horas', 'area', 'condicion', 'hv_requerido', 'horas_virtuales'
      ])
      ->condition('cu.id_carrera', $id_carrera)
      ->orderBy('cu.semestre', 'ASC')
      ->orderBy('cu.numero_orden', 'ASC');
    
    $result = $query->execute();

    $cursos_por_semestre = [];
    $total_creditos = 0;
    $total_horas_teoricas = 0;
    $total_horas_practicas = 0;
    $total_horas_generales = 0;
    $total_cursos = 0;
    
    $estadisticas = [
      'General' => 0,
      'Específico' => 0,
      'Especialidad' => 0,
      'creditos_General' => 0,
      'creditos_Específico' => 0,
      'creditos_Especialidad' => 0,
      'Obligatorio' => 0,
      'Electivo' => 0,
      'creditos_Obligatorio' => 0,
      'creditos_Electivo' => 0,
    ];
    
    foreach ($result as $curso) {
      $semestre = $curso->semestre;
      if (!isset($cursos_por_semestre[$semestre])) {
        $cursos_por_semestre[$semestre] = [];
      }
      $cursos_por_semestre[$semestre][] = $curso;
      
      $total_creditos += $curso->creditos;
      $total_horas_teoricas += $curso->horas_teoricas ?? 0;
      $total_horas_practicas += $curso->horas_practicas ?? 0;
      $total_horas_generales += $curso->total_horas ?? ($curso->horas_teoricas + $curso->horas_practicas);
      $total_cursos++;
      
      $area = $curso->area ?? 'Específico';
      $condicion = $curso->condicion ?? 'Obligatorio';
      
      if ($area == 'General') {
        $estadisticas['General']++;
        $estadisticas['creditos_General'] += $curso->creditos;
      } elseif ($area == 'Específico') {
        $estadisticas['Específico']++;
        $estadisticas['creditos_Específico'] += $curso->creditos;
      } elseif ($area == 'Especialidad') {
        $estadisticas['Especialidad']++;
        $estadisticas['creditos_Especialidad'] += $curso->creditos;
      }
      
      if ($condicion == 'Obligatorio') {
        $estadisticas['Obligatorio']++;
        $estadisticas['creditos_Obligatorio'] += $curso->creditos;
      } elseif ($condicion == 'Electivo') {
        $estadisticas['Electivo']++;
        $estadisticas['creditos_Electivo'] += $curso->creditos;
      }
    }

    // ============================================
    // GENERAR HTML CON TABLA VERTICAL Y ORDEN LIMPIO
    // ============================================
    
    // Obtener el servicio de renderizado
    $renderer = \Drupal::service('renderer');
    
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="UTF-8">
      <title>Malla Curricular - <?php echo $carrera->nombre; ?></title>
      <style>
        <?php
        // Cargar el CSS original
        $css_path = DRUPAL_ROOT . '/modules/custom/malla_curricular/css/estilos-mejorados.css';
        if (file_exists($css_path)) {
          echo file_get_contents($css_path);
        }
        ?>
        
        /* ===== ESTILOS PARA PDF VERTICAL Y LIMPIO ===== */
        body { 
          margin: 2cm 1.5cm; 
          font-family: 'Times New Roman', Times, serif;
          font-size: 10pt;
          line-height: 1.4;
          color: #2C3E50;
        }
        
        /* Ocultar elementos de navegación */
        .btn-view-malla, .back-link, .btn-download { 
          display: none !important; 
        }
        
        /* Títulos */
        h1 {
          text-align: center;
          color: #2C3E50;
          font-size: 22pt;
          margin: 0 0 5px 0;
          font-weight: 700;
          letter-spacing: 1px;
        }
        
        h2 {
          text-align: center;
          color: #34495E;
          font-size: 16pt;
          margin: 0 0 20px 0;
          font-weight: 500;
          border-bottom: 2px solid #34495E;
          padding-bottom: 10px;
        }
        
        /* Tabla principal */
        table {
          width: 100%;
          border-collapse: collapse;
          margin: 15px 0;
          font-size: 9pt;
        }
        
        th {
          background: #2C3E50;
          color: white;
          padding: 8px 4px;
          font-weight: 700;
          text-align: center;
          border: 1px solid #34495E;
          font-size: 8pt;
          text-transform: uppercase;
        }
        
        td {
          border: 1px solid #7F8C8D;
          padding: 6px 4px;
          vertical-align: middle;
        }
        
        /* Separadores de semestre */
        .separador-semestre {
          background-color: #34495E;
          color: white;
        }
        .separador-semestre td {
          background-color: #34495E;
          color: white;
          padding: 8px 10px;
          font-weight: 700;
          font-size: 11pt;
          border: 1px solid #2C3E50;
        }
        
        /* Texto sin cuadros */
        .orden-numero {
          display: inline-block;
          width: 24px;
          text-align: center;
          font-weight: 700;
        }
        
        .codigo-curso {
          font-family: 'Courier New', Courier, monospace;
          font-weight: 600;
        }
        
        .credito-valor {
          font-weight: 700;
        }
        
        .hora-valor {
          font-weight: 600;
        }
        
        .hora-total {
          font-weight: 700;
        }
        
        .hv-numero {
          font-weight: 600;
        }
        
        .hv-none {
          font-style: italic;
        }
        
        .tipo-badge, .condicion-badge {
          text-transform: uppercase;
        }
        
        .prerrequisito-badge {
          display: inline-block;
          margin: 0 1px;
        }
        
        .prerrequisito-none {
          font-style: italic;
        }
        
        /* Totales del semestre */
        .totales-semestre {
          background-color: #F8F9F9;
        }
        .totales-semestre td {
          background-color: #F8F9F9;
          padding: 6px 8px;
          font-weight: 600;
          border: 1px solid #7F8C8D;
        }
        
        /* Totales generales */
        .totales-generales-pdf {
          background-color: #2C3E50;
          color: white;
        }
        .totales-generales-pdf td {
          background-color: #2C3E50;
          color: white;
          padding: 8px 10px;
          font-weight: 700;
          border: 1px solid #34495E;
        }
        
        /* Información de carrera en PDF */
        .info-carrera-pdf {
          background: #F8F9F9;
          padding: 12px 15px;
          margin: 15px 0 20px 0;
          border: 1px solid #7F8C8D;
          display: grid;
          grid-template-columns: repeat(4, 1fr);
          gap: 10px;
          font-size: 9pt;
        }
        
        .info-carrera-pdf div {
          padding: 3px 0;
        }
        
        .info-carrera-pdf strong {
          color: #2C3E50;
          font-weight: 700;
        }
        
        /* Pie de página */
        .footer-pdf {
          text-align: center;
          margin-top: 25px;
          font-size: 8pt;
          color: #7F8C8D;
          border-top: 1px solid #D5DBDB;
          padding-top: 8px;
        }
      </style>
    </head>
    <body>
      
      <!-- Título principal -->
      <h1>MALLA CURRICULAR</h1>
      <h2><?php echo $carrera->nombre; ?> (<?php echo $carrera->codigo; ?>)</h2>
      
      <!-- Información de la carrera en formato limpio -->
      <div class="info-carrera-pdf">
        <div><strong>Duración:</strong> <?php echo $carrera->duracion_semestres; ?> semestres</div>
        <div><strong>Créditos totales:</strong> <?php echo $total_creditos; ?></div>
        <div><strong>Horas teóricas:</strong> <?php echo $total_horas_teoricas; ?></div>
        <div><strong>Horas prácticas:</strong> <?php echo $total_horas_practicas; ?></div>
        <div><strong>Total horas:</strong> <?php echo $total_horas_generales; ?></div>
        <div><strong>Cursos totales:</strong> <?php echo $total_cursos; ?></div>
        <div><strong>Áreas:</strong> G:<?php echo $estadisticas['General']; ?> E:<?php echo $estadisticas['Específico']; ?> Esp:<?php echo $estadisticas['Especialidad']; ?></div>
        <div><strong>Condición:</strong> Ob:<?php echo $estadisticas['Obligatorio']; ?> El:<?php echo $estadisticas['Electivo']; ?></div>
      </div>
      
      <!-- ============================================ -->
      <!-- TABLA VERTICAL CON TODOS LOS SEMESTRES -->
      <!-- ============================================ -->
      <table>
        <thead>
          <tr>
            <th style="width: 4%;">N°</th>
            <th style="width: 8%;">Código</th>
            <th style="width: 25%;">Curso</th>
            <th style="width: 4%;">Cr.</th>
            <th style="width: 4%;">HT</th>
            <th style="width: 4%;">HP</th>
            <th style="width: 4%;">TH</th>
            <th style="width: 4%;">HV</th>
            <th style="width: 7%;">Área</th>
            <th style="width: 7%;">Cond.</th>
            <th style="width: 15%;">Pre</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $contador_global = 1;
          
          for ($semestre = 1; $semestre <= $carrera->duracion_semestres; $semestre++) {
            $cursos_semestre = $cursos_por_semestre[$semestre] ?? [];
            
            // Fila de separación de semestre
            echo '<tr class="separador-semestre">';
            echo '<td colspan="11" style="text-align: left; padding-left: 12px;">SEMESTRE ' . $semestre . '</td>';
            echo '</tr>';
            
            if (!empty($cursos_semestre)) {
              $total_creditos_sem = 0;
              $total_ht_sem = 0;
              $total_hp_sem = 0;
              $total_th_sem = 0;
              
              foreach ($cursos_semestre as $curso) {
                $horas_teoricas = $curso->horas_teoricas ?? 0;
                $horas_practicas = $curso->horas_practicas ?? 0;
                $horas_totales = $curso->total_horas ?? ($horas_teoricas + $horas_practicas);
                $horas_virtuales = $curso->horas_virtuales ?? 0;
                $prerequisitos = $curso->prerequisitos ?? '';
                
                echo '<tr>';
                echo '<td style="text-align: center;"><span class="orden-numero">' . $contador_global . '</span></td>';
                echo '<td style="text-align: center;"><span class="codigo-curso">' . $curso->codigo_curso . '</span></td>';
                echo '<td>' . $curso->nombre . '</td>';
                echo '<td style="text-align: center;"><span class="credito-valor">' . $curso->creditos . '</span></td>';
                echo '<td style="text-align: center;"><span class="hora-valor">' . $horas_teoricas . '</span></td>';
                echo '<td style="text-align: center;"><span class="hora-valor">' . $horas_practicas . '</span></td>';
                echo '<td style="text-align: center;"><span class="hora-valor hora-total">' . $horas_totales . '</span></td>';
                
                if ($horas_virtuales > 0) {
                  echo '<td style="text-align: center;"><span class="hv-numero">' . $horas_virtuales . '</span></td>';
                } else {
                  echo '<td style="text-align: center;"><span class="hv-none">0</span></td>';
                }
                
                echo '<td style="text-align: center;"><span class="tipo-badge">' . ($curso->area ?? '—') . '</span></td>';
                echo '<td style="text-align: center;"><span class="condicion-badge">' . ($curso->condicion ?? '—') . '</span></td>';
                
                if (!empty($prerequisitos)) {
                  if (strpos($prerequisitos, ',') !== false) {
                    $codigos = explode(',', $prerequisitos);
                    echo '<td>';
                    foreach ($codigos as $cod) {
                      echo '<span class="prerrequisito-badge">' . trim($cod) . '</span> ';
                    }
                    echo '</td>';
                  } else {
                    echo '<td><span class="prerrequisito-badge">' . $prerequisitos . '</span></td>';
                  }
                } else {
                  echo '<td><span class="prerrequisito-none">Ninguno</span></td>';
                }
                
                echo '</tr>';
                
                $total_creditos_sem += $curso->creditos;
                $total_ht_sem += $horas_teoricas;
                $total_hp_sem += $horas_practicas;
                $total_th_sem += $horas_totales;
                $contador_global++;
              }
              
              // Fila de totales del semestre
              echo '<tr class="totales-semestre">';
              echo '<td colspan="3" style="text-align: right;"><strong>Total Semestre ' . $semestre . ':</strong></td>';
              echo '<td style="text-align: center;"><strong>' . $total_creditos_sem . '</strong></td>';
              echo '<td style="text-align: center;"><strong>' . $total_ht_sem . '</strong></td>';
              echo '<td style="text-align: center;"><strong>' . $total_hp_sem . '</strong></td>';
              echo '<td style="text-align: center;"><strong>' . $total_th_sem . '</strong></td>';
              echo '<td colspan="4"></td>';
              echo '</tr>';
              
            } else {
              echo '<tr><td colspan="11" style="text-align: center; padding: 12px; background: #F8F9F9;">No hay cursos asignados para este semestre.</td></tr>';
            }
          }
          ?>
          
          <!-- Fila de totales generales -->
          <tr class="totales-generales-pdf">
            <td colspan="3" style="text-align: right; font-weight: bold;">TOTALES GENERALES:</td>
            <td style="text-align: center; font-weight: bold;"><?php echo $total_creditos; ?></td>
            <td style="text-align: center; font-weight: bold;"><?php echo $total_horas_teoricas; ?></td>
            <td style="text-align: center; font-weight: bold;"><?php echo $total_horas_practicas; ?></td>
            <td style="text-align: center; font-weight: bold;"><?php echo $total_horas_generales; ?></td>
            <td colspan="4"></td>
          </tr>
        </tbody>
      </table>
      
      <!-- Resumen estadístico compacto -->
      <div style="margin-top: 15px; font-size: 9pt; color: #2C3E50;">
        <p><strong>Resumen por área:</strong> 
          General: <?php echo $estadisticas['General']; ?> cursos (<?php echo $estadisticas['creditos_General']; ?> créd.) | 
          Específico: <?php echo $estadisticas['Específico']; ?> cursos (<?php echo $estadisticas['creditos_Específico']; ?> créd.) | 
          Especialidad: <?php echo $estadisticas['Especialidad']; ?> cursos (<?php echo $estadisticas['creditos_Especialidad']; ?> créd.)
        </p>
        <p><strong>Resumen por condición:</strong> 
          Obligatorio: <?php echo $estadisticas['Obligatorio']; ?> cursos (<?php echo $estadisticas['creditos_Obligatorio']; ?> créd.) | 
          Electivo: <?php echo $estadisticas['Electivo']; ?> cursos (<?php echo $estadisticas['creditos_Electivo']; ?> créd.)
        </p>
      </div>
      
      <!-- Pie de página -->
      <div class="footer-pdf">
        Documento generado el <?php echo date('d/m/Y H:i:s'); ?> | <?php echo $carrera->nombre; ?> | Total cursos: <?php echo $contador_global-1; ?>
      </div>
      
    </body>
    </html>
    <?php
    
    $html = ob_get_clean();

    // ============================================
    // GENERAR PDF CON DOMPDF
    // ============================================
    require_once DRUPAL_ROOT . '/vendor/autoload.php';
    
    $dompdf = new \Dompdf\Dompdf();
    
    $options = $dompdf->getOptions();
    $options->set('defaultFont', 'Helvetica');
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $dompdf->setOptions($options);
    
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    
    $dompdf->stream('malla_' . $carrera->codigo . '_' . date('d-m-Y') . '.pdf', array('Attachment' => 1));
    exit;
  }

  /**
   * Muestra la malla curricular de una carrera.
   */
  public function carreraDetail($id_carrera) {
    try {
      // Obtener información de la carrera
      $carrera = $this->database->select('carreras', 'c')
        ->fields('c', [
          'nombre', 'codigo', 'duracion_semestres',
          'total_creditos', 'total_horas_teoricas', 'total_horas_practicas', 'total_horas'
        ])
        ->condition('c.id_carrera', $id_carrera)
        ->execute()
        ->fetchObject();

      if (!$carrera) {
        return ['#markup' => $this->t('Carrera no encontrada.')];
      }

      // Consultar cursos
      $query = $this->database->select('cursos', 'cu')
        ->fields('cu', [
          'codigo_curso', 'nombre', 'semestre', 'creditos',
          'prerequisitos', 'numero_orden', 'horas_teoricas', 'horas_practicas',
          'total_horas', 'area', 'condicion', 'hv_requerido',
          'horas_virtuales'
        ])
        ->condition('cu.id_carrera', $id_carrera)
        ->orderBy('cu.semestre', 'ASC')
        ->orderBy('cu.numero_orden', 'ASC');
      
      $result = $query->execute();

      $cursos_por_semestre = [];
      $total_creditos = 0;
      $total_horas_teoricas = 0;
      $total_horas_practicas = 0;
      $total_horas_generales = 0;
      
      $estadisticas = [
        'General' => 0,
        'Específico' => 0,
        'Especialidad' => 0,
        'creditos_General' => 0,
        'creditos_Específico' => 0,
        'creditos_Especialidad' => 0,
        'Obligatorio' => 0,
        'Electivo' => 0,
        'creditos_Obligatorio' => 0,
        'creditos_Electivo' => 0,
      ];
      
      foreach ($result as $curso) {
        $semestre = $curso->semestre;
        if (!isset($cursos_por_semestre[$semestre])) {
          $cursos_por_semestre[$semestre] = [];
        }
        
        $cursos_por_semestre[$semestre][] = $curso;
        
        $total_creditos += $curso->creditos;
        $total_horas_teoricas += $curso->horas_teoricas ?? 0;
        $total_horas_practicas += $curso->horas_practicas ?? 0;
        $total_horas_generales += $curso->total_horas ?? ($curso->horas_teoricas + $curso->horas_practicas);
        
        $area = $curso->area ?? 'Específico';
        $condicion = $curso->condicion ?? 'Obligatorio';
        
        if ($area == 'General') {
          $estadisticas['General']++;
          $estadisticas['creditos_General'] += $curso->creditos;
        } elseif ($area == 'Específico') {
          $estadisticas['Específico']++;
          $estadisticas['creditos_Específico'] += $curso->creditos;
        } elseif ($area == 'Especialidad') {
          $estadisticas['Especialidad']++;
          $estadisticas['creditos_Especialidad'] += $curso->creditos;
        }
        
        if ($condicion == 'Obligatorio') {
          $estadisticas['Obligatorio']++;
          $estadisticas['creditos_Obligatorio'] += $curso->creditos;
        } elseif ($condicion == 'Electivo') {
          $estadisticas['Electivo']++;
          $estadisticas['creditos_Electivo'] += $curso->creditos;
        }
      }

      $build = [];

      $build['info_carrera'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('INFORMACIÓN DEL PROGRAMA'),
        '#attributes' => ['class' => ['carrera-info']],
      ];

      $build['info_carrera']['content'] = $this->renderizarInformacionCarrera(
        $carrera, $total_creditos, $total_horas_teoricas, $total_horas_practicas, 
        $total_horas_generales, $estadisticas
      );

      $build['malla_semestres'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('MALLA CURRICULAR POR SEMESTRE'),
        '#attributes' => ['class' => ['malla-semestres']],
      ];

      $build['malla_semestres']['content'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['semestres-container']],
      ];

      $creditos_acumulados = 0;
      for ($semestre = 1; $semestre <= $carrera->duracion_semestres; $semestre++) {
        $cursos_semestre = $cursos_por_semestre[$semestre] ?? [];
        $resultado_semestre = $this->renderizarSemestre($semestre, $cursos_semestre, $creditos_acumulados);
        $build['malla_semestres']['content']['semestre_' . $semestre] = $resultado_semestre['render'];
        $creditos_acumulados = $resultado_semestre['creditos_acumulados'];
      }

      $build['resumen_general'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('RESUMEN GENERAL DEL PROGRAMA'),
        '#attributes' => ['class' => ['resumen-general']],
      ];

      $build['resumen_general']['content'] = $this->renderizarResumenGeneral(
        $carrera, $total_creditos, $total_horas_teoricas, $total_horas_practicas, 
        $total_horas_generales, $estadisticas
      );

      // ============================================
      // BOTÓN PARA DESCARGAR
      // ============================================
      $build['boton_descargar'] = [
        '#type' => 'link',
        '#title' => $this->t('Descargar Malla en PDF'),
        '#url' => \Drupal\Core\Url::fromRoute('malla_curricular.descargar_pdf', ['id_carrera' => $id_carrera]),
        '#attributes' => [
          'class' => ['btn-download'],
          'style' => 'display: inline-block; margin-right: 15px; padding: 12px 24px; background: #27AE60; color: white; border-radius: 8px; text-decoration: none; font-weight: 600; border: 1px solid #229954;',
        ],
      ];

      $build['back_link'] = [
        '#type' => 'link',
        '#title' => $this->t('← Volver a la lista de carreras'),
        '#url' => \Drupal\Core\Url::fromRoute('malla_curricular.display'),
        '#attributes' => [
          'class' => ['back-link']
        ],
      ];

      $build['#attached']['library'][] = 'malla_curricular/styles';

      return $build;
      
    } catch (\Exception $e) {
      return ['#markup' => $this->t('Error al cargar la malla: @error', ['@error' => $e->getMessage()])];
    }
  }

  /**
   * Renderiza información de carrera.
   */
  private function renderizarInformacionCarrera($carrera, $total_creditos, $total_horas_teoricas, $total_horas_practicas, $total_horas, $estadisticas) {
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['carrera-details']],
      'grid' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['carrera-details-grid']],
        'carrera' => $this->buildDetailItem('fa-graduation-cap', 'Carrera', $carrera->nombre, 'Código: ' . $carrera->codigo),
        'duracion' => $this->buildDetailItem('fa-calendar-alt', 'Duración', $carrera->duracion_semestres . ' semestres'),
        'creditos' => $this->buildDetailItem('fa-star', 'Créditos', $total_creditos),
        'horas' => $this->buildDetailItem('fa-clock', 'Horas', $total_horas . ' h', 'T: ' . $total_horas_teoricas . ' | P: ' . $total_horas_practicas),
      ],
      'badges' => $this->buildStatsBadges($estadisticas),
    ];
  }

  /**
   * Construye un item de detalle.
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
   * Construye los badges de estadísticas.
   */
  private function buildStatsBadges($estadisticas) {
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['stats-badges']],
      'General' => [
        '#markup' => '<span class="stat-badge General"><i class="fas fa-check-circle"></i> ' . ($estadisticas['General'] ?? 0) . ' General</span>',
      ],
      'Específico' => [
        '#markup' => '<span class="stat-badge Especifico"><i class="fas fa-check-square"></i> ' . ($estadisticas['Específico'] ?? 0) . ' Específico</span>',
      ],
      'Especialidad' => [
        '#markup' => '<span class="stat-badge especialidad"><i class="fas fa-star"></i> ' . ($estadisticas['Especialidad'] ?? 0) . ' Especialidad</span>',
      ],
      'Obligatorio' => [
        '#markup' => '<span class="stat-badge Obligatorio"><i class="fas fa-check-circle"></i> ' . ($estadisticas['Obligatorio'] ?? 0) . ' Obligatorios</span>',
      ],
      'Electivo' => [
        '#markup' => '<span class="stat-badge Electivo"><i class="fas fa-check-square"></i> ' . ($estadisticas['Electivo'] ?? 0) . ' Electivos</span>',
      ],
    ];
  }

  /**
   * Renderiza un semestre.
   */
  private function renderizarSemestre($semestre, $cursos, &$creditos_acumulados) {
    $build = [
      '#type' => 'container',
      '#attributes' => ['class' => ['semestre-wrapper']],
    ];
    
    $build['header'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['semestre-header']],
      'icono' => ['#markup' => '<i class="fas fa-book"></i>'],
      'titulo' => ['#markup' => '<h3>SEMESTRE ' . $semestre . '</h3>'],
    ];
    
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
    
    foreach ($cursos as $curso) {
      $horas_teoricas = $curso->horas_teoricas ?? 0;
      $horas_practicas = $curso->horas_practicas ?? 0;
      $horas_totales = $curso->total_horas ?? ($horas_teoricas + $horas_practicas);
      
      $area = $curso->area ?? 'Específico';
      $condicion = $curso->condicion ?? 'Obligatorio';
    
      $prerequisitos_html = $this->formatPrerequisitos($curso->prerequisitos);
      $hv_valor = $this->getHvValor($curso);
    
      $rows[] = $this->buildFilaCurso(
        $curso, 
        $numero_orden, 
        $horas_teoricas, 
        $horas_practicas, 
        $horas_totales, 
        $hv_valor, 
        $area,
        $condicion,
        $prerequisitos_html
      );

      $total_creditos_semestre += $curso->creditos;
      $total_horas_teoricas += $horas_teoricas;
      $total_horas_practicas += $horas_practicas;
      $numero_orden++;
    }

    $creditos_acumulados += $total_creditos_semestre;

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

    $build['footer'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['semestre-footer']],
      'left' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['footer-left']],
        'cursos' => ['#markup' => '<span><i class="fas fa-book"></i> ' . count($cursos) . ' cursos</span>'],
        'creditos' => ['#markup' => '<span><i class="fas fa-star"></i> ' . $total_creditos_semestre . ' créditos</span>'],
        'acumulado' => ['#markup' => '<span><i class="fas fa-chart-line"></i> Acumulado: ' . $creditos_acumulados . '</span>'],
      ],
      'right' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['footer-right']],
        'ht' => ['#markup' => '<span><i class="fas fa-clock"></i> T:' . $total_horas_teoricas . '</span>'],
        'hp' => ['#markup' => '<span><i class="fas fa-flask"></i> P:' . $total_horas_practicas . '</span>'],
        'total' => ['#markup' => '<span><i class="fas fa-hourglass-half"></i> H:' . ($total_horas_teoricas + $total_horas_practicas) . '</span>'],
      ],
    ];

    return ['render' => $build, 'creditos_acumulados' => $creditos_acumulados];
  }

  /**
   * Formatea los prerrequisitos.
   */
  private function formatPrerequisitos($prerequisitos) {
    if (!empty($prerequisitos)) {
      if (strpos($prerequisitos, ',') !== false) {
        $codigos = explode(',', $prerequisitos);
        $badges = [];
        foreach ($codigos as $codigo) {
          $badges[] = '<span class="prerrequisito-badge">' . trim($codigo) . '</span>';
        }
        return implode(' ', $badges);
      }
      return '<span class="prerrequisito-badge">' . $prerequisitos . '</span>';
    }
    return '<span class="prerrequisito-none">Ninguno</span>';
  }

  /**
   * Obtiene el valor de HV.
   */
  private function getHvValor($curso) {
    if (empty($curso->hv_requerido)) {
      return '<span class="hv-none">0</span>';
    }
    $horas_virtuales = $curso->horas_virtuales ?? 0;
    $valor = ($horas_virtuales > 0) ? (string) $horas_virtuales : '16';
    return '<span class="hv-numero">' . $valor . '</span>';
  }

  /**
   * Construye una fila de curso.
   */
  private function buildFilaCurso($curso, $numero_orden, $horas_teoricas, $horas_practicas, $horas_totales, $hv_valor, $area, $condicion, $prerequisitos_html) {
    return [
      'data' => [
        ['data' => ['#markup' => '<span class="orden-numero">' . ($curso->numero_orden ?: $numero_orden) . '</span>']],
        ['data' => ['#markup' => '<span class="codigo-curso">' . $curso->codigo_curso . '</span>']],
        ['data' => ['#markup' => '<span class="curso-nombre">' . $curso->nombre . '</span>']],
        ['data' => ['#markup' => '<span class="credito-valor">' . $curso->creditos . '</span>']],
        ['data' => ['#markup' => '<span class="hora-valor hora-teorica">' . $horas_teoricas . '</span>']],
        ['data' => ['#markup' => '<span class="hora-valor hora-practica">' . $horas_practicas . '</span>']],
        ['data' => ['#markup' => '<span class="hora-valor hora-total">' . $horas_totales . '</span>']],
        ['data' => ['#markup' => $hv_valor]],
        ['data' => ['#markup' => '<span class="tipo-badge">' . $area . '</span>']],
        ['data' => ['#markup' => '<span class="condicion-badge">' . $condicion . '</span>']],
        ['data' => ['#markup' => $prerequisitos_html]],
      ],
    ];
  }

  /**
   * Renderiza resumen general.
   */
  private function renderizarResumenGeneral($carrera, $total_creditos, $total_horas_teoricas, $total_horas_practicas, $total_horas, $estadisticas) {
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['resumen-content']],
      'grid' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['resumen-grid']],
        'total_creditos' => $this->buildResumenCard('fa-star', $total_creditos, 'Créditos totales'),
        'total_horas' => $this->buildResumenCard('fa-clock', $total_horas, 'Horas totales', 'T: ' . $total_horas_teoricas . ' | P: ' . $total_horas_practicas),
        'General' => $this->buildResumenCard('fa-check-circle', $estadisticas['General'] ?? 0, 'General', ($estadisticas['creditos_General'] ?? 0) . ' créd.'),
        'Específico' => $this->buildResumenCard('fa-check-square', $estadisticas['Específico'] ?? 0, 'Específico', ($estadisticas['creditos_Específico'] ?? 0) . ' créd.'),
        'Especialidad' => $this->buildResumenCard('fa-star', $estadisticas['Especialidad'] ?? 0, 'Especialidad', ($estadisticas['creditos_Especialidad'] ?? 0) . ' créd.'),
        'Obligatorio' => $this->buildResumenCard('fa-check-circle', $estadisticas['Obligatorio'] ?? 0, 'Obligatorios', ($estadisticas['creditos_Obligatorio'] ?? 0) . ' créd.'),
        'Electivo' => $this->buildResumenCard('fa-check-square', $estadisticas['Electivo'] ?? 0, 'Electivos', ($estadisticas['creditos_Electivo'] ?? 0) . ' créd.'),
      ],
      'totales' => [
        '#type' => 'container',
        '#attributes' => ['class' => ['totales-generales']],
        'info' => ['#markup' => '<div class="totales-info"><h4>' . $carrera->nombre . '</h4><p>Código: ' . $carrera->codigo . ' | ' . $carrera->duracion_semestres . ' semestres</p></div>'],
        'stats' => ['#markup' => '<div class="totales-stats"><div class="totales-stat"><div class="stat-valor">' . $total_creditos . '</div><div class="stat-etiqueta">Créditos</div></div><div class="totales-stat"><div class="stat-valor">' . $total_horas . '</div><div class="stat-etiqueta">Horas</div></div></div>'],
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