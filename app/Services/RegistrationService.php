<?php

namespace App\Services;

use App\Models\LegacyRegistration;
use App\Models\LegacySchoolClass;
use App\User;
use clsModulesAuditoriaGeral;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class RegistrationService
{
    /**
     * @var User
     */
    private $user;

    /**
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * @param array $ids
     *
     * @return Collection
     */
    public function findAll(array $ids)
    {
        return LegacyRegistration::query()
            ->whereIn('cod_matricula', $ids)
            ->get();
    }

    /**
     * @param LegacySchoolClass $schoolClass
     *
     * @return Collection
     */
    public function getRegistrationsNotEnrolled($schoolClass)
    {
        return LegacyRegistration::query()
            ->with('student.person', 'lastEnrollment')
            ->where('ref_cod_curso', $schoolClass->course_id)
            ->where('ref_ref_cod_serie', $schoolClass->grade_id)
            ->where('ref_ref_cod_escola', $schoolClass->school_id)
            ->where('ativo', 1)
            ->where('ultima_matricula', 1)
            ->where('ano', $schoolClass->year)
            ->whereIn('aprovado', [1, 2, 3])
            ->whereDoesntHave('enrollments', function ($query) use ($schoolClass) {
                /** @var Builder $query */
                $query->where('ativo', 1);
                $query->whereHas('schoolClass', function ($query) use ($schoolClass) {
                    /** @var Builder $query */
                    $query->where('ref_ref_cod_escola', $schoolClass->school_id);
                    $query->where('ativo', 1);
                });
            })
            ->get();
    }

    /**
     * Atualiza a situação de uma matrícula
     *
     * @param $registration
     * @param $status
     */
    public function updateStatus(LegacyRegistration $registration, $status)
    {
        $auditoria = new clsModulesAuditoriaGeral('update_registration_status', $this->user->getKey());
        $auditoria->alteracao(
            ['aprovado' => $registration->aprovado],
            ['aprovado' => $status]
        );

        $registration->aprovado = $status;
        $registration->save();
    }
}
