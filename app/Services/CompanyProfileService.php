<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use App\Support\CompanyAccess;
use App\Support\PublicDisk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CompanyProfileService
{
    /**
     * @return array<string, mixed>
     */
    public function show(User $user, Company $company): array
    {
        return $this->payload($user, $company);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(User $user, Company $company, array $data, ?UploadedFile $logo = null, bool $removeLogo = false): array
    {
        if (array_key_exists('billing_email', $data) && $data['billing_email'] !== $company->billing_email) {
            if (! CompanyAccess::canManageMoney($user, $company)) {
                abort(403, 'Only owners can manage billing.');
            }
        }

        if (! CompanyAccess::canManageMoney($user, $company)) {
            unset($data['billing_email']);
        }

        if ($logo instanceof UploadedFile) {
            $path = $logo->store('companies/'.$company->id, 'public');

            if (! is_string($path) || $path === '') {
                throw ValidationException::withMessages([
                    'logo' => 'The logo could not be stored.',
                ]);
            }

            if (is_string($company->logo_path) && $company->logo_path !== '') {
                Storage::disk('public')->delete($company->logo_path);
            }

            $data['logo_path'] = $path;
        } elseif ($removeLogo) {
            if (is_string($company->logo_path) && $company->logo_path !== '') {
                Storage::disk('public')->delete($company->logo_path);
            }

            $data['logo_path'] = null;
        }

        $company->fill($data);
        $company->save();

        return $this->payload($user, $company->fresh() ?? $company);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(User $user, Company $company): array
    {
        return [
            'id' => $company->id,
            'name' => $company->name,
            'website' => $company->website,
            'logo_url' => PublicDisk::url($company->logo_path),
            'billing_email' => $company->billing_email,
            'country' => $company->country,
            'value_proposition' => $company->value_proposition,
            'can_manage_money' => CompanyAccess::canManageMoney($user, $company),
        ];
    }
}
