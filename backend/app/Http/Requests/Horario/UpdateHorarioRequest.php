<?php

namespace App\Http\Requests\Horario;

use App\Models\Horario;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHorarioRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'laboratorio_id' => [
                'required',
                'integer',
                Rule::exists('laboratorios', 'id')->whereNull('deleted_at'),
            ],
            'docente_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->whereNull('deleted_at'),
                function ($attribute, $value, $fail) {
                    $user = \App\Models\User::find($value, ['id']);
                    if (!$user || !$user->hasRole('docente')) {
                        $fail('El usuario seleccionado no tiene el rol de docente.');
                    }
                },
            ],
            'grupo_id' => [
                'required',
                'integer',
                Rule::exists('grupos', 'id')->whereNull('deleted_at'),
            ],
            'dia_semana' => 'required|integer|between:1,7',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
        ];
    }

    public function withValidator($validator)
    {
        $horario = is_object($this->route('horario')) ? $this->route('horario') : \App\Models\Horario::find($this->route('horario'));

        $validator->after(function ($validator) use ($horario) {
            // Check original group
            if ($horario && $horario->grupo && $horario->grupo->materia && $horario->grupo->materia->semestreAcademico && $horario->grupo->materia->semestreAcademico->isClosed()) {
                $validator->errors()->add('grupo_id', 'No se puede editar un horario que pertenece a un semestre cerrado.');
            }

            // Check new group (if changing)
            $newGrupoId = $this->input('grupo_id');
            if ($newGrupoId && (!$horario || $newGrupoId != $horario->grupo_id)) {
                $newGrupo = \App\Models\Grupo::with('materia.semestreAcademico')->find($newGrupoId);
                if ($newGrupo && $newGrupo->materia && $newGrupo->materia->semestreAcademico && $newGrupo->materia->semestreAcademico->isClosed()) {
                    $validator->errors()->add('grupo_id', 'No se puede mover el horario a un semestre cerrado.');
                }
            }

            if ($validator->errors()->any()) {
                return;
            }

            $horarioId = $this->route('horario')->id;

            $solapamiento = Horario::query()
                ->where('id', '!=', $horarioId)

                ->where('dia_semana', $this->dia_semana)

                // Cruce de fechas
                ->where('fecha_inicio', '<=', $this->fecha_fin)
                ->where('fecha_fin', '>=', $this->fecha_inicio)

                // Cruce de horas
                ->where('hora_inicio', '<', $this->hora_fin)
                ->where('hora_fin', '>', $this->hora_inicio)

                // Conflictos
                ->where(function ($q) {
                    $q->where('laboratorio_id', $this->laboratorio_id)
                        ->orWhere('docente_id', $this->docente_id)
                        ->orWhere('grupo_id', $this->grupo_id);
                })

                ->first();

            if (!$solapamiento) {
                return;
            }

            if ($solapamiento->laboratorio_id == $this->laboratorio_id) {
                $validator->errors()->add(
                    'laboratorio_id',
                    'El laboratorio ya está ocupado en ese rango de fecha y hora.'
                );
            }

            if ($solapamiento->docente_id == $this->docente_id) {
                $validator->errors()->add(
                    'docente_id',
                    'El docente ya tiene otra clase asignada en este horario.'
                );
            }

            if ($solapamiento->grupo_id == $this->grupo_id) {
                $validator->errors()->add(
                    'grupo_id',
                    'El grupo ya tiene otra clase asignada en este horario.'
                );
            }
        });
    }
}
