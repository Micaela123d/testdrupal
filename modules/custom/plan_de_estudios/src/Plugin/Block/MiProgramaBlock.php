<?php

namespace Drupal\plan_de_estudios\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provee un bloque con los planes de estudios del programa actual.
 *
 * @Block(
 *   id = "plan_de_estudios_mi_programa",
 *   admin_label = @Translation("Planes de Estudio (Programa Actual)"),
 *   category = @Translation("Plan de Estudios")
 * )
 */
class MiProgramaBlock extends BlockBase
{

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    // 1. Obtener la ruta del sitio para determinar qué programa corresponde.
    // Esto maneja la lógica de multisitio: cada carpeta en /sites/ es un ID de programa.
    $site_path = \Drupal::service('kernel')->getSitePath();
    $parts = explode('/', str_replace('\\', '/', $site_path));
    $programa_actual = end($parts);

    // Si estamos en el sitio "default" o no es numérico, ocultamos el bloque.
    if ($programa_actual === 'default' || !is_numeric($programa_actual)) {
      return [];
    }

    // 2. Solicitar la lista de planes de estudio disponibles para este programa a la API Flask.
    $data = $this->fetchDataFromApi($programa_actual);

    if ($data === NULL || !isset($data['planes'])) {
      return ['#markup' => '<div class="plan-de-estudios-error">' . $this->t('Error al conectar con la API de planes.') . '</div>'];
    }

    $planes = $data['planes'];

    if (empty($planes)) {
      return ['#markup' => '<div class="plan-de-estudios-block-empty">' . $this->t('No hay planes de estudio disponibles para este programa.') . '</div>'];
    }

    $nombre_programa = $data['nombre_programa'] ?? '';

    // 3. Construir el marcado HTML del bloque.
    $output = '<div class="block-plan-de-estudios">';

    // Header banner premium (Clickable toggle)
    $output .= '<div class="program-block-banner toggle-block-trigger" title="' . $this->t('Click para desplegar/contraer') . '">';
    $output .= '  <div class="banner-content">';
    $output .= '    <div class="banner-left">';
    $output .= '      <div class="program-title-main">' . htmlspecialchars('Planes disponibles') . '</div>';
    $output .= '    </div>';
    $output .= '    <div class="banner-right-icon"><i class="fas fa-chevron-down toggle-icon"></i></div>';
    $output .= '  </div>';
    $output .= '</div>';

    // Collapsible wrapper
    $output .= '<div class="plans-collapsible-wrapper">';


    $output .= '<div class="plans-list-container">';
    foreach ($planes as $plan) {
      $v_uid = $plan['uid'];
      $v_label_limpio = $plan['nombre_limpio'];

      // Generar URL hacia el detalle del programa usando el sistema de rutas de Drupal.
      try {
        $url = \Drupal\Core\Url::fromRoute('plan_de_estudios.programa', ['id_programa' => $v_uid])->toString();
      }
      catch (\Exception $e) {
        $url = '/plan-de-estudios/' . $v_uid;
      }

      // Estructura de "Card" para cada plan.
      $output .= '<div class="plan-card-item">';
      $output .= '  <div class="plan-card-body">';
      $output .= '    <span class="plan-name">' . htmlspecialchars($v_label_limpio) . '</span>';
      $output .= '    <a href="' . $url . '" class="btn-ingresar">Ingresar <i class="fas fa-chevron-right"></i></a>';
      $output .= '</div>';
    }
    $output .= '</div>'; // End plans-list-container
    $output .= '</div>'; // End plans-collapsible-wrapper
    $output .= '</div>'; // End block-plan-de-estudios

    // 4. Adjuntar librerías necesarias.
    $build = ['#markup' => $output];
    $build['#attached']['library'][] = 'plan_de_estudios/styles';
    $build['#attached']['library'][] = 'plan_de_estudios/block-js';
    $build['#attached']['library'][] = 'plan_de_estudios/open-sans';
    $build['#attached']['library'][] = 'plan_de_estudios/font-awesome';

    return $build;
  }

  /**
   * Helper: Obtiene datos de la API Flask (localhost:5000) usando el servicio Guzzle de Drupal.
   */
  private function fetchDataFromApi($id_programa)
  {
    try {
      $client = \Drupal::httpClient();
      $response = $client->request('GET', 'http://localhost:5000/programas/' . $id_programa . '/planes', [
        'timeout' => 10,
      ]);
      return json_decode($response->getBody()->getContents(), TRUE);
    }
    catch (\Exception $e) {
      \Drupal::logger('plan_de_estudios')->error('Error fetching data from API (Block): @error', ['@error' => $e->getMessage()]);
      return NULL;
    }
  }

}