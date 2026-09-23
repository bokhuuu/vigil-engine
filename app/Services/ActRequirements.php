<?php

declare(strict_types=1);

namespace App\Services;

class ActRequirements
{
    /**
     * Doc checkbox fields required for an ACT, based on its property flags.
     */
    public function requiredDocFields(array $fields): array
    {
        $required = config('vigil.drive.requirements.always');

        foreach (config('vigil.drive.requirements.conditional') as $docField => $flags) {
            foreach ($flags as $flag) {
                if (! empty($fields[$flag])) {
                    $required[] = $docField;
                    break;
                }
            }
        }

        return $required;
    }
}
