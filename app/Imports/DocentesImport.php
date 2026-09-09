<?php

namespace App\Imports;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

final class DocentesImport implements ToCollection, WithHeadingRow
{
    public int $createdCount = 0;

    public int $skippedCount = 0;

    /**
     * @param  Collection<int, array{apellido_y_nombre?: mixed, dni?: mixed, correo?: mixed}>  $rows
     */
    public function collection(Collection $rows): void
    {
        if ($rows->isEmpty()) {
            throw ValidationException::withMessages([
                'docentesFile' => 'El archivo no contiene docentes para importar.',
            ]);
        }

        DB::transaction(function () use ($rows): void {
            foreach ($rows as $index => $row) {
                $teacher = $this->validatedTeacher($row instanceof Collection ? $row->all() : $row, $index + 2);

                $alreadyExists = User::query()
                    ->where('email', $teacher['email'])
                    ->orWhere('dni', $teacher['dni'])
                    ->exists();

                if ($alreadyExists) {
                    $this->skippedCount++;

                    continue;
                }

                User::query()->create([
                    'name' => $teacher['name'],
                    'dni' => $teacher['dni'],
                    'email' => $teacher['email'],
                    'rol' => UserRole::Profesor,
                    'activo' => true,
                    'password' => Hash::make($teacher['dni']),
                ]);

                $this->createdCount++;
            }
        });
    }

    /**
     * @param  array{apellido_y_nombre?: mixed, dni?: mixed, correo?: mixed}  $row
     * @return array{name: string, dni: string, email: string}
     */
    private function validatedTeacher(array $row, int $rowNumber): array
    {
        $name = trim((string) ($row['apellido_y_nombre'] ?? ''));
        $dni = preg_replace('/\D/', '', (string) ($row['dni'] ?? ''));
        $email = mb_strtolower(trim((string) ($row['correo'] ?? '')));

        $validator = Validator::make(
            ['name' => $name, 'dni' => $dni, 'email' => $email],
            [
                'name' => ['required', 'string', 'max:255'],
                'dni' => ['required', 'digits_between:7,10'],
                'email' => ['required', 'email:rfc', 'max:255'],
            ],
        );

        if ($validator->fails()) {
            throw ValidationException::withMessages([
                'docentesFile' => "La fila {$rowNumber} contiene datos inválidos.",
            ]);
        }

        return $validator->validated();
    }
}
