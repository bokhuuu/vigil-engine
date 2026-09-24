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

    /**
     * Drive subfolders an ACT must have: every tracked subfolder that has no
     * checkbox (always required, e.g. სკანი) or whose document is required.
     *
     * @param  array<string, mixed>  $fields  Airtable record fields
     * @return array<int, string>
     */
    public function requiredSubfolders(array $fields): array
    {
        $requiredDocs = $this->requiredDocFields($fields);
        $folders = [];

        foreach (config('vigil.drive.tracked_subfolders') as $folder => $docField) {
            if ($docField === null || in_array($docField, $requiredDocs, true)) {
                $folders[] = $folder;
            }
        }

        return $folders;
    }
}
