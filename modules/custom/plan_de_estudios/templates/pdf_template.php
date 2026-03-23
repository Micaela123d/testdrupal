<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Plan de Estudios - <?php echo $programa->nombre; ?></title>
  <style>
    /* Configuración de página y estilos base para DOMPDF */
    @page { margin: 1cm; }
    body { 
      font-family: Helvetica, Arial, sans-serif;
      font-size: 8.5pt;
      line-height: 1.3;
      color: #2c3e50;
      margin: 0;
      padding: 0;
    }
    
    /* Cabecera del documento */
    .header-pdf {
      text-align: center;
      border-bottom: 3px solid #316C9E;
      padding-bottom: 10px;
      margin-bottom: 20px;
    }
    
    h1 {
      font-size: 18pt;
      margin: 0;
      color: #316C9E;
      text-transform: uppercase;
    }
    
    .programa-titulo {
      font-size: 13pt;
      color: #316C9E;
      margin-top: 5px;
      font-weight: normal;
    }
    
    /* Cuadrícula de información general del programa */
    .info-grid {
      width: 100%;
      margin-bottom: 20px;
      background: #F8F9FA;
      border: 1px solid #C0C0C0;
    }
    
    .info-grid td {
      padding: 8px 12px;
      border: 1px solid #C0C0C0;
      width: 25%;
    }
    
    .info-label {
      font-size: 7pt;
      color: #6C757D;
      text-transform: uppercase;
      display: block;
      margin-bottom: 2px;
      font-weight: bold;
    }
    
    .info-value {
      font-size: 9pt;
      color: #2C3E50;
      font-weight: bold;
    }
    
    /* Estilos de la tabla principal de cursos */
    table.plan-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
    }
    
    .plan-table th {
      background: #316C9E;
      color: white;
      padding: 7px 4px;
      font-size: 7.5pt;
      text-transform: uppercase;
      border: 1px solid #316C9E;
    }
    
    .plan-table td {
      padding: 6px 4px;
      border: 1px solid #C0C0C0;
      vertical-align: middle;
    }
    
    /* Separador visual para cada semestre */
    .separador-semestre {
      background: #EDF2F7;
      font-weight: bold;
      color: #2D3748;
      font-size: 9.5pt;
      padding: 8px 12px !important;
      border-left: 5px solid #316C9E !important;
    }
    
    /* Formateo de nombres de cursos */
    .curso-texto-pdf {
      text-transform: lowercase;
      font-weight: bold;
      color: #2C3E50;
    }
    .curso-texto-pdf::first-letter {
      text-transform: uppercase;
    }
    
    .codigo-curso {
      font-family: monospace;
      color: #4A5568;
    }
    
    /* Fila de totales por semestre */
    .totales-semestre {
      background-color: #F8F9FA;
      font-weight: bold;
      font-size: 8pt;
    }
    
    /* Sección de estadísticas al final del documento */
    .footer-stats {
      margin-top: 20px;
      border-top: 2px solid #316C9E;
      padding-top: 15px;
    }
    
    .stats-box {
      background: #F8F9FA;
      padding: 10px;
      border: 1px solid #C0C0C0;
      margin-bottom: 10px;
    }
    
    .stats-title {
      font-weight: bold;
      font-size: 8pt;
      color: #316C9E;
      margin-bottom: 5px;
      border-bottom: 1px solid #C0C0C0;
      padding-bottom: 3px;
    }
    
    .pie-pagina {
      text-align: center;
      font-size: 7pt;
      color: #A0AEC0;
      margin-top: 30px;
    }
  </style>
</head>
<body>
  
  <!-- Encabezado Institucional -->
  <div class="header-pdf">
    <div style="font-size: 10pt; color: #718096; margin-bottom: 5px;">UNIVERSIDAD NACIONAL DEL ALTIPLANO</div>
    <h1>PLAN DE ESTUDIOS</h1>
    <div style="font-size: 14pt; color: #2D3748; font-weight: bold; margin-top: 5px;"><?php echo mb_strtoupper(str_ireplace('Plan de estudios', '', $programa->plan_nombre)); ?></div>
    <div class="programa-titulo"><?php echo $programa->nombre; ?></div>
  </div>
  
  <!-- Resumen Ejecutivo Superior -->
  <table class="info-grid">
    <tr>
      <td><span class="info-label">Programa</span><span class="info-value"><?php echo $programa->codigo; ?></span></td>
      <td><span class="info-label">Duración</span><span class="info-value"><?php echo $programa->duracion_semestres; ?> Semestres</span></td>
      <td><span class="info-label">Créditos Totales</span><span class="info-value"><?php echo $total_creditos; ?></span></td>
      <td><span class="info-label">Cursos Totales</span><span class="info-value"><?php echo $total_cursos; ?></span></td>
    </tr>
    <tr>
      <td><span class="info-label">Horas Teóricas</span><span class="info-value"><?php echo $total_ht; ?> h</span></td>
      <td><span class="info-label">Horas Prácticas</span><span class="info-value"><?php echo $total_hp; ?> h</span></td>
      <td><span class="info-label">Horas Totales</span><span class="info-value"><?php echo $total_th; ?> h</span></td>
      <td><span class="info-label">Generado</span><span class="info-value"><?php echo date('d/m/Y'); ?></span></td>
    </tr>
  </table>
  
  <!-- Tabla Detallada por Semestres -->
  <table class="plan-table">
    <thead>
      <tr>
        <th width="4%">N°</th>
        <th width="10%">Código</th>
        <th width="32%">Denominación del Curso</th>
        <th width="5%">Cr.</th>
        <th width="4%">HT</th>
        <th width="4%">HP</th>
        <th width="4%">TH</th>
        <th width="4%">HV</th>
        <th width="11%">Área</th>
        <th width="11%">Cond.</th>
        <th width="11%">Pre</th>
      </tr>
    </thead>
    <tbody>
      <?php
      $contador_global = 1;
      for ($semestre = 1; $semestre <= $programa->duracion_semestres; $semestre++):
        $cursos_sem = $cursos_por_semestre[$semestre] ?? [];
        ?>
        <!-- Fila de Cabecera de Semestre -->
        <tr>
          <td colspan="11" class="separador-semestre">SEMESTRE <?php echo $semestre; ?></td>
        </tr>
        <?php if (empty($cursos_sem)): ?>
          <tr><td colspan="11" style="text-align: center; color: #718096; height: 30px;">Sin cursos programados</td></tr>
        <?php else: 
          $cr_sem = 0; $ht_sem = 0; $hp_sem = 0; $th_sem = 0;
          foreach ($cursos_sem as $c):
            ?>
            <tr>
              <td style="text-align: center; color: #718096;"><?php echo $contador_global++; ?></td>
              <td style="text-align: center;" class="codigo-curso"><?php echo $c->codigo_curso; ?></td>
              <td><span class="curso-texto-pdf"><?php echo $c->nombre; ?></span></td>
              <td style="text-align: center; font-weight: bold;"><?php echo $c->creditos; ?></td>
              <td style="text-align: center;"><?php echo $c->horas_teoricas; ?></td>
              <td style="text-align: center;"><?php echo $c->horas_practicas; ?></td>
              <td style="text-align: center; font-weight: bold;"><?php echo $c->total_horas; ?></td>
              <td style="text-align: center; color: #718096;"><?php echo $c->horas_virtuales; ?></td>
              <td style="text-align: center;"><?php echo $c->area; ?></td>
              <td style="text-align: center;"><?php echo $c->condicion; ?></td>
              <td style="text-align: center; font-size: 7pt; color: #718096;">
                <?php echo !empty($c->prerequisitos) ? strtoupper($c->prerequisitos) : 'Ninguno'; ?>
              </td>
            </tr>
            <?php
            $cr_sem += $c->creditos; $ht_sem += $c->horas_teoricas; 
            $hp_sem += $c->horas_practicas; $th_sem += $c->total_horas;
          endforeach;
          ?>
          <!-- Subtotales del Semestre -->
          <tr class="totales-semestre">
            <td colspan="3" style="text-align: right; padding-right: 10px;">Subtotal Semestre <?php echo $semestre; ?>:</td>
            <td style="text-align: center;"><?php echo $cr_sem; ?></td>
            <td style="text-align: center;"><?php echo $ht_sem; ?></td>
            <td style="text-align: center;"><?php echo $hp_sem; ?></td>
            <td style="text-align: center;"><?php echo $th_sem; ?></td>
            <td colspan="4"></td>
          </tr>
        <?php endif; ?>
      <?php endfor; ?>
    </tbody>
  </table>
  
  <!-- Resumen de Totales por Áreas y Condiciones -->
  <div class="footer-stats">
    <div class="stats-box">
      <div class="stats-title">RESUMEN POR ÁREAS DE ESTUDIO</div>
      <table width="100%" style="border: none;">
        <tr>
          <td style="border: none;"><strong>Generales:</strong> <?php echo $estadisticas['Estudios Generales']; ?> cursos (<?php echo $estadisticas['creditos_General']; ?> créd.)</td>
          <td style="border: none;"><strong>Específicos:</strong> <?php echo $estadisticas['Estudios Específicos']; ?> cursos (<?php echo $estadisticas['creditos_Específico']; ?> créd.)</td>
          <td style="border: none;"><strong>Especialidad:</strong> <?php echo $estadisticas['Estudios de Especialidad']; ?> cursos (<?php echo $estadisticas['creditos_Especialidad']; ?> créd.)</td>
        </tr>
      </table>
    </div>
    
    <div class="stats-box">
      <div class="stats-title">RESUMEN POR CONDICIÓN Y TOTALES</div>
      <table width="100%" style="border: none;">
        <tr>
          <td style="border: none;"><strong>Obligatorios:</strong> <?php echo $estadisticas['Obligatorio']; ?> (<?php echo $estadisticas['creditos_Obligatorio']; ?> cr.)</td>
          <td style="border: none;"><strong>Electivos:</strong> <?php echo $estadisticas['Electivo']; ?> (<?php echo $estadisticas['creditos_Electivo']; ?> cr.)</td>
          <td style="border: none; text-align: right; color: #2C3E50; font-size: 11pt;"><strong>TOTAL CRÉDITOS: <?php echo $total_creditos; ?></strong></td>
        </tr>
      </table>
    </div>
  </div>
  
  <!-- Pie de página con marca de tiempo -->
  <div class="pie-pagina">
    Documento oficial generado el <?php echo date('d/m/Y H:i:s'); ?> | Universidad Nacional del Altiplano
  </div>
  
</body>
</html>
