"""
Backend Flask - Servidor de API para gestión de planes de estudio UNAP.
Centraliza el procesamiento pesado, normalización y cálculos estadísticos.
"""

from flask import Flask, jsonify, abort
from flask_cors import CORS
import requests
import re

app = Flask(__name__)
CORS(app) 

# CONFIGURACIÓN
API_URL = 'http://api-gestioncurricular-pregrado.unap.edu.pe/cursos_carga'

# ==========================================
# 1. FUNCIONES AUXILIARES (HELPERS)
# ==========================================

def get_raw_data():
    """Conexión base con la API de la UNAP."""
    try:
        response = requests.get(API_URL, timeout=15)
        response.raise_for_status()
        data = response.json()
        if isinstance(data, dict) and 'data' in data:
            return data['data']
        return data
    except Exception as e:
        print(f"Error de conexión API UNAP: {e}")
        return None

def mapCiclo(ciclo):
    """Convierte ciclos (Romanos o texto) a enteros."""
    if not ciclo: return 99
    ciclo = str(ciclo).upper().strip()
    
    # Mapeo directo para casos comunes
    romans = {
        'M': 1000, 'CM': 900, 'D': 500, 'CD': 400,
        'C': 100, 'XC': 90, 'L': 50, 'XL': 40,
        'X': 10, 'IX': 9, 'V': 5, 'IV': 4, 'I': 1,
    }
    result = 0
    temp_ciclo = ciclo
    for key, value in romans.items():
        while temp_ciclo.startswith(key):
            result += value
            temp_ciclo = temp_ciclo[len(key):]
            
    return result if result > 0 else 99

def normalizeArea(area):
    """Estandariza las áreas de estudio."""
    if not area: return 'Estudios Específicos'
    area = str(area).upper().strip()
    if area == 'G' or 'GENERAL' in area: return 'Estudios Generales'
    if area == 'S' or area == 'ESP' or 'ESPECIALIDAD' in area: return 'Estudios de Especialidad'
    if area == 'E' or 'ESPEC' in area: return 'Estudios Específicos'
    return area.capitalize()

def normalizeCondicion(condicion):
    """Estandariza la condición del curso (Obligatorio/Electivo)."""
    if not condicion: return 'Obligatorio'
    condicion = str(condicion).upper().strip()
    if condicion == 'O' or 'OBLIGATORIO' in condicion: return 'Obligatorio'
    if condicion == 'E' or 'ELECTIVO' in condicion: return 'Electivo'
    return condicion.capitalize()

def cleanNombrePlan(nombre):
    """Limpia nombres de planes redundantes."""
    if not nombre: return "Plan de estudios"
    # Reemplaza frases largas por algo más amigable
    limpio = re.sub(r'(?i)CURRÍCULO FLEXIBLE POR COMPETENCIAS', 'Plan de estudios', nombre)
    return limpio.strip()

def generateUid(item):
    """Genera un identificador único consistente para un plan."""
    prog_id = item.get('programa', '0')
    version = item.get('version_curricula') or item.get('version_currciula') or '1'
    return f"{prog_id}-{version}"

# ==========================================
# 2. FUNCIONES DE PROCESAMIENTO
# ==========================================

def filter_by_programa(data, programa_id):
    """Filtra la lista completa por ID de programa."""
    return [item for item in data if str(item.get('programa')) == str(programa_id)]

def extract_program_info(item):
    """Extrae metadatos del programa."""
    duracion_str = str(item.get('duracion', '10'))
    duracion_digits = re.sub(r'[^0-9]', '', duracion_str)
    
    return {
        'nombre': item.get('nombre_programa') or item.get('nombre'),
        'plan_nombre': cleanNombrePlan(item.get('nombre', '')),
        'codigo': item.get('programa'),
        'duracion_semestres': int(duracion_digits) if duracion_digits else 10
    }

def process_curso(c):
    """Procesa y normaliza un curso individual."""
    h_teoricas = 0
    h_practicas = 0
    h_virtuales = 0
    
    try: h_teoricas = int(c.get('curso_ht', 0))
    except: pass
    try: h_practicas = int(c.get('curso_hp', 0))
    except: pass
    try: h_virtuales = int(c.get('curso_hvir', 0))
    except: pass

    # Manejo de créditos (int o float)
    credito_raw = c.get('curso_credito', 0)
    try:
        creditos = float(credito_raw) if '.' in str(credito_raw) else int(credito_raw)
    except:
        creditos = 0

    return {
        'codigo_curso': c.get('curso_codigo', ''),
        'nombre': c.get('curso_nombre', ''),
        'semestre': mapCiclo(c.get('curso_ciclo')),
        'creditos': creditos,
        'prerequisitos': str(c.get('curso_pre', '')).strip() if str(c.get('curso_pre')).lower() != 'none' else '',
        'horas_teoricas': h_teoricas,
        'horas_practicas': h_practicas,
        'horas_virtuales': h_virtuales,
        'total_horas': h_teoricas + h_practicas,
        'area': normalizeArea(c.get('curso_areac')),
        'condicion': normalizeCondicion(c.get('curso_tipo')),
        'hv_requerido': h_virtuales > 0
    }

def calculate_stats(cursos):
    """Calcula todas las estadísticas agregadas de un conjunto de cursos."""
    stats = {
        'total_creditos': 0, 'total_ht': 0, 'total_hp': 0, 'total_th': 0, 'total_cursos': 0,
        'estudios': {'Estudios Generales': 0, 'Estudios Específicos': 0, 'Estudios de Especialidad': 0},
        'creditos_estudios': {'creditos_General': 0, 'creditos_Específico': 0, 'creditos_Especialidad': 0},
        'condicion': {'Obligatorio': 0, 'Electivo': 0},
        'creditos_condicion': {'creditos_Obligatorio': 0, 'creditos_Electivo': 0},
    }

    for c in cursos:
        stats['total_creditos'] += c['creditos']
        stats['total_ht'] += c['horas_teoricas']
        stats['total_hp'] += c['horas_practicas']
        stats['total_th'] += c['total_horas']
        stats['total_cursos'] += 1
        
        # Stats por Area
        if c['area'] in stats['estudios']:
            stats['estudios'][c['area']] += 1
            key_map = {
                'Estudios Generales': 'creditos_General',
                'Estudios de Especialidad': 'creditos_Especialidad',
                'Estudios Específicos': 'creditos_Específico'
            }
            stats['creditos_estudios'][key_map[c['area']]] += c['creditos']
            
        # Stats por Condición
        if c['condicion'] in stats['condicion']:
            stats['condicion'][c['condicion']] += 1
            stats['creditos_condicion']['creditos_' + c['condicion']] += c['creditos']
            
    return stats

def group_courses_by_semester(cursos):
    """Agrupa una lista de cursos procesados por su número de semestre."""
    grouped = {}
    for c in cursos:
        sem = str(c['semestre'])
        if sem not in grouped:
            grouped[sem] = []
        grouped[sem].append(c)
    return grouped

# ==========================================
# 3. ENDPOINTS PRINCIPALES
# ==========================================

@app.route('/programas')
def list_programas():
    """Retorna la lista de todos los programas únicos disponibles."""
    data = get_raw_data()
    if data is None: return jsonify({'error': 'API offline'}), 500
    
    programas = {}
    for item in data:
        pid = str(item.get('programa'))
        if pid not in programas:
            programas[pid] = item.get('nombre_programa') or item.get('nombre')
            
    return jsonify([{'id': k, 'nombre': v} for k, v in programas.items()])

@app.route('/programas/<id_programa>/planes')
def get_planes_por_programa(id_programa):
    """Retorna planes simplificados para el bloque de Drupal."""
    data = get_raw_data()
    if data is None: return jsonify({'error': 'API offline'}), 500
    
    filtered = filter_by_programa(data, id_programa)
    if not filtered: return jsonify({'nombre_programa': '', 'planes': []})
    
    prog_info = extract_program_info(filtered[0])
    planes = [{
        'uid': generateUid(item),
        'nombre_limpio': cleanNombrePlan(item.get('nombre', '')),
        'nombre_original': item.get('nombre', '')
    } for item in filtered]

    return jsonify({
        'nombre_programa': prog_info['nombre'],
        'planes': planes
    })

@app.route('/plan/<id_plan>')
def get_plan_completo(id_plan):
    """Endpoint principal: Retorna el plan procesado al 100%."""
    data = get_raw_data()
    if data is None: return jsonify({'error': 'API offline'}), 500
    
    # Buscar el item que corresponde al ID (que puede ser programa-version)
    target = None
    for item in data:
        if generateUid(item) == str(id_plan):
            target = item
            break
            
    if not target: abort(404, description="Plan no encontrado")

    # Procesamiento en cadena
    info = extract_program_info(target)
    cursos_raw = target.get('cursos', [])
    cursos_procesados = [process_curso(c) for c in cursos_raw]
    
    return jsonify({
        'programa': info,
        'cursos_por_semestre': group_courses_by_semester(cursos_procesados),
        'stats': calculate_stats(cursos_procesados)
    })

@app.route('/plan/<id_plan>/resumen')
def get_plan_resumen(id_plan):
    """Retorna solo la info básica y estadísticas (vista rápida)."""
    data = get_raw_data()
    if data is None: return jsonify({'error': 'API offline'}), 500
    
    target = next((item for item in data if generateUid(item) == str(id_plan)), None)
    if not target: abort(404)
    
    info = extract_program_info(target)
    cursos = [process_curso(c) for c in target.get('cursos', [])]
    
    return jsonify({
        'programa': info,
        'stats': calculate_stats(cursos)
    })

@app.route('/')
@app.route('/health')
def health_check():
    return jsonify({'status': 'ok', 'service': 'UNAP Plan Processor'})

if __name__ == '__main__':
    app.run(debug=True, port=5000)