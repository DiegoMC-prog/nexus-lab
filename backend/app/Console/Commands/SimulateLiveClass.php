<?php

namespace App\Console\Commands;

use App\Models\Estacion;
use App\Models\Grupo;
use App\Models\Horario;
use App\Models\Laboratorio;
use App\Models\LogAplicacionProhibida;
use App\Models\LogsTelemetria;
use App\Models\Materia;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SimulateLiveClass extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nexus:simulate-class 
                            {--docente=docente@gmail.com : El correo del docente para la simulación} 
                            {--stations=12 : Número de estaciones a simular en el laboratorio} 
                            {--interval=3 : Intervalo en segundos para la actualización de telemetría}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simula una clase en curso para el Docente Dashboard en Nexus Lab, actualizando telemetría y generando alertas.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $docenteEmail = $this->option('docente');
        $stationCount = (int) $this->option('stations');
        $interval = (int) $this->option('interval');

        $this->info("==================================================================");
        $this->info("    NEXUS LAB - SIMULADOR DE CLASE EN VIVO (DOCENTE DASHBOARD)   ");
        $this->info("==================================================================");

        // 1. Obtener/Crear el Docente
        $docente = User::where('email', $docenteEmail)->first();
        if (!$docente) {
            $this->warn("Docente '{$docenteEmail}' no encontrado. Creando uno de demostración...");
            $docente = User::create([
                'name' => 'Docente Demo',
                'email' => $docenteEmail,
                'password' => bcrypt('password123'),
            ]);
            $docente->assignRole('docente');
        }
        $this->line("<fg=green>[Docente]</> Activo: {$docente->name} ({$docente->email})");

        // 4. Asegurar que haya un Horario activo en este mismo instante
        $hoyNum = date('N'); // 1 (Lunes) a 7 (Domingo)
        $currentTime = now();
        
        // Buscamos si ya hay un horario activo para este docente justo ahora
        $horarioActivo = Horario::with(['laboratorio', 'grupo.materia'])
            ->where('docente_id', $docente->id)
            ->where('dia_semana', $hoyNum)
            ->where('hora_inicio', '<=', $currentTime->format('H:i:s'))
            ->where('hora_fin', '>=', $currentTime->format('H:i:s'))
            ->first();

        if ($horarioActivo) {
            $laboratorio = $horarioActivo->laboratorio;
            $grupo = $horarioActivo->grupo;
            $materia = $horarioActivo->grupo?->materia;
            $this->info("Clase activa existente detectada en la base de datos para este docente.");
        } else {
            // 2. Obtener/Crear Laboratorio fallback
            $laboratorio = Laboratorio::first();
            if (!$laboratorio) {
                $this->warn("No hay laboratorios en el sistema. Creando uno de demostración...");
                $laboratorio = Laboratorio::create([
                    'nombre' => 'Laboratorio de Redes y Ciberseguridad 201',
                    'pabellon' => 'Pabellón B',
                    'piso' => 'Segundo Piso',
                    'capacidad' => 30,
                ]);
            }

            // 3. Obtener/Crear Materia y Grupo fallback
            $materia = Materia::first();
            if (!$materia) {
                $this->warn("No hay materias en el sistema. Creando una de demostración...");
                $materia = Materia::create([
                    'codigo' => 'SIS-302',
                    'nombre' => 'Sistemas Operativos II',
                    'carrera_id' => \App\Models\Carrera::first()?->id ?? \App\Models\Carrera::create([
                        'nombre' => 'Ingeniería de Sistemas',
                        'codigo' => 'SYS',
                    ])->id,
                    'semestre_id' => \App\Models\SemestreAcademico::first()?->id ?? \App\Models\SemestreAcademico::create([
                        'nombre' => 'Primer Semestre 2026',
                        'codigo' => '2026-1',
                    ])->id,
                ]);
            }

            $grupo = Grupo::where('materia_id', $materia->id)->first();
            if (!$grupo) {
                $this->warn("No hay grupos académicos. Creando uno de demostración...");
                $grupo = Grupo::create([
                    'materia_id' => $materia->id,
                    'nombre' => 'Grupo A',
                    'gestion' => '1/2026',
                    'cupo_maximo' => 40,
                ]);
            }

            $this->warn("No tienes ningún horario programado para hoy en este momento.");
            $this->info("Creando/Actualizando horario activo simulado para abarcar desde 1 hora antes hasta 2 horas después...");

            // Borramos posibles horarios conflictivos del docente hoy para la simulación
            Horario::where('docente_id', $docente->id)
                ->where('dia_semana', $hoyNum)
                ->delete();

            $horarioActivo = Horario::create([
                'docente_id' => $docente->id,
                'laboratorio_id' => $laboratorio->id,
                'grupo_id' => $grupo->id,
                'dia_semana' => $hoyNum,
                'hora_inicio' => now()->subHour()->format('H:i:s'),
                'hora_fin' => now()->addHours(2)->format('H:i:s'),
                'fecha_inicio' => now()->subDays(10)->toDateString(),
                'fecha_fin' => now()->addDays(90)->toDateString(),
            ]);
        }
        
        $this->line("<fg=green>[Laboratorio]</> Asignado: {$laboratorio->nombre} ({$laboratorio->pabellon}, {$laboratorio->piso})");
        $this->line("<fg=green>[Materia / Grupo]</> " . ($materia ? "{$materia->nombre} ({$materia->codigo})" : "No asignada") . " - " . ($grupo ? $grupo->nombre : "No asignado"));
        $this->line("<fg=green>[Clase Activa]</> De " . substr($horarioActivo->hora_inicio, 0, 5) . " a " . substr($horarioActivo->hora_fin, 0, 5) . " (Activo)");

        // 5. Garantizar Estaciones en el Laboratorio
        $estaciones = Estacion::where('laboratorio_id', $laboratorio->id)->get();
        if ($estaciones->count() < $stationCount) {
            $this->warn("Solo se encontraron {$estaciones->count()} estaciones en el laboratorio. Creando estaciones virtuales adicionales para alcanzar las {$stationCount} solicitadas...");
            for ($i = $estaciones->count() + 1; $i <= $stationCount; $i++) {
                Estacion::create([
                    'laboratorio_id' => $laboratorio->id,
                    'uuid' => (string) Str::uuid(),
                    'hostname' => "LAB-" . Str::padLeft($laboratorio->id, 2, '0') . "-PC" . Str::padLeft($i, 2, '0'),
                    'direccion_mac' => "00:1A:3F:8A:BC:" . Str::padLeft(dechex($i + 15), 2, '0', STR_PAD_LEFT),
                    'direccion_ip' => "192.168.{$laboratorio->id}." . (10 + $i),
                    'so_info' => 'Windows 11 Professional (x64) Build 22631',
                    'estado' => 'Online',
                    'version_agente' => 'v2.1.0-wpf',
                    'ultima_conexion' => now()
                ]);
            }
            // Recargar estaciones
            $estaciones = Estacion::where('laboratorio_id', $laboratorio->id)->get();
        }
        $this->line("<fg=green>[Estaciones]</> {$estaciones->count()} estaciones preparadas en el laboratorio.");

        // 6. Preparar estudiantes
        $estudiantes = User::role('estudiante')->get();
        if ($estudiantes->count() < $stationCount) {
            $this->warn("Pocos estudiantes registrados en el sistema. Creando estudiantes de demostración...");
            $nombresDemos = ["Diego Torres", "Andrea Rojas", "Carlos Mendoza", "Fernanda Ortiz", "Gael Castillo", "Hugo Romero", "Irene Vega", "Jorge Luna", "Karen Cruz", "Luis Silva", "Monica Herrera", "Nelson Vargas"];
            foreach ($nombresDemos as $k => $nombre) {
                $est = User::create([
                    'name' => $nombre,
                    'email' => 'estudiante.demo' . ($k + 1) . '@example.com',
                    'password' => bcrypt('password123'),
                ]);
                $est->assignRole('estudiante');
            }
            $estudiantes = User::role('estudiante')->get();
        }

        $this->info("\n>>> SIMULACIÓN ACTIVA EN TIEMPO REAL <<<");
        $this->info("Actualizando telemetría cada {$interval} segundos.");
        $this->info("Presiona [Ctrl + C] para detener la simulación de clase.");
        $this->line("------------------------------------------------------------------");

        $appsProhibidas = [
            'steam.exe' => 'C:\\Program Files (x86)\\Steam\\steam.exe',
            'discord.exe' => 'C:\\Users\\Estudiante\\AppData\\Local\\Discord\\app-1.0.9001\\discord.exe',
            'spotify.exe' => 'C:\\Users\\Estudiante\\AppData\\Local\\Microsoft\\WindowsApps\\Spotify.exe',
            'utorrent.exe' => 'C:\\Users\\Estudiante\\AppData\\Roaming\\uTorrent\\uTorrent.exe',
            'chrome_incognito' => 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe --incognito'
        ];

        while (true) {
            $this->line("");
            $this->line("<fg=gray>[" . now()->format('H:i:s') . "]</> Enviando actualizaciones de telemetría a PostgreSQL...");
            
            foreach ($estaciones as $estacion) {
                // Si la estación está bloqueada ('bloqueado'), respetamos el bloqueo y mantenemos el estudiante actual.
                // No la sobrescribimos con 'Online' ni cambiamos su estudiante de forma aleatoria, pero sí actualizamos
                // su latido (heartbeat) para simular que sigue conectada y enviando telemetría.
                if ($estacion->estado === 'bloqueado') {
                    $estacion->update([
                        'ultima_conexion' => now(),
                    ]);
                } else {
                    // Simular presencia de estudiante (90% de probabilidad de tener un alumno sentado)
                    $tieneEstudiante = rand(1, 100) <= 90;
                    $estudianteId = null;
                    
                    if ($tieneEstudiante) {
                        $estudianteId = $estudiantes->random()->id;
                    }

                    // Actualizar latido (Heartbeat) de conexión para que Vue la muestre "Online"
                    $estacion->update([
                        'ultima_conexion' => now(),
                        'estudiante_actual_id' => $estudianteId,
                        'estado' => 'activo'
                    ]);
                }

                // Generar telemetría realista
                $cargaCpu = rand(5, 95);
                $usoRam = rand(1500, 7600); // 1.5GB a 7.6GB
                
                LogsTelemetria::create([
                    'estacion_id' => $estacion->id,
                    'carga_cpu' => $cargaCpu,
                    'uso_ram_mb' => $usoRam,
                    'temp_cpu' => rand(40, 78),
                    'uso_disco' => rand(15, 80),
                    'latencia_red' => rand(5, 85)
                ]);
            }

            $this->line("  -> Telemetría y estado de red actualizados para {$estaciones->count()} PCs.");

            // 7. Simular Alertas de Infracción (15% de probabilidad en cada ciclo de telemetría)
            if (rand(1, 100) <= 15) {
                $estacionConInfraccion = $estaciones->whereNotNull('estudiante_actual_id')->random();
                if ($estacionConInfraccion) {
                    $nombreProceso = array_rand($appsProhibidas);
                    $ruta = $appsProhibidas[$nombreProceso];

                    $infraccion = LogAplicacionProhibida::create([
                        'estacion_id' => $estacionConInfraccion->id,
                        'usuario_id' => $estacionConInfraccion->estudiante_actual_id,
                        'nombre_proceso' => $nombreProceso,
                        'ruta_ejecutable' => $ruta,
                        'accion_tomada' => 'Proceso Bloqueado y Cerrado'
                    ]);

                    $estudianteNombre = User::find($estacionConInfraccion->estudiante_actual_id)?->name ?? 'Estudiante';

                    $this->line("");
                    $this->line("  ⚠️  <fg=red;options=bold>ALERTA DE SEGURIDAD DETECTADA</>");
                    $this->line("  Estación : <fg=cyan>{$estacionConInfraccion->hostname}</>");
                    $this->line("  Alumno   : <fg=yellow>{$estudianteNombre}</>");
                    $this->line("  Proceso  : <fg=red>{$nombreProceso}</> ({$ruta})");
                    $this->line("  Acción   : <fg=green>Terminado Automáticamente</>");
                    $this->line("------------------------------------------------------------------");
                }
            }

            sleep($interval);
        }
    }
}
