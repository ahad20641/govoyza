<?php

namespace App\Services\Auth;

use App\Enums\AgencyStatus;
use App\Enums\RoleSlug;
use App\Enums\UserStatus;
use App\Models\Agency;
use App\Models\Client;
use App\Models\Role;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class RegistrationService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function registerClient(array $data): User
    {
        $user = DB::transaction(function () use ($data) {
            $user = $this->newUser($data, RoleSlug::Client, UserStatus::Active);

            $client = new Client(['nationality_id' => $data['nationality_id'] ?? null]);
            $client->user()->associate($user);
            $client->save();

            return $user;
        });

        $this->audit->log('auth.registered_client', $user, userId: $user->id);
        event(new Registered($user));

        return $user;
    }

    /**
     * @param  array<string, UploadedFile|null>  $documents
     */
    public function registerAgency(array $data, array $documents): User
    {
        $stored = [];

        try {
            $user = DB::transaction(function () use ($data, $documents, &$stored) {
                // Agency owners stay "pending" until a Super Admin approves the agency.
                $user = $this->newUser($data, RoleSlug::Agency, UserStatus::Pending);

                $agency = new Agency([
                    'name' => $data['agency_name'],
                    'slug' => $this->uniqueSlug($data['agency_name']),
                    'trade_license_number' => $data['trade_license_number'],
                    'business_email' => $data['business_email'],
                    'business_phone' => $data['business_phone'],
                    'address' => $data['address'],
                    'district_id' => $data['district_id'] ?? null,
                    'website' => $data['website'] ?? null,
                ]);
                $agency->status = AgencyStatus::Pending;
                $agency->owner()->associate($user);
                $agency->save();

                foreach ($documents as $type => $file) {
                    if (! $file instanceof UploadedFile) {
                        continue;
                    }
                    $path = $this->storePrivately($file, "agencies/{$agency->id}");
                    $stored[] = $path;

                    $agency->documents()->create([
                        'document_type' => $type,
                        'disk' => 'private',
                        'path' => $path,
                        'original_name' => Str::limit($file->getClientOriginalName(), 200, ''),
                        'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                        'size' => $file->getSize(),
                        'sha256' => hash_file('sha256', $file->getRealPath()),
                    ]);
                }

                return $user;
            });
        } catch (Throwable $e) {
            // Don't leave orphaned files behind if the DB transaction failed.
            Storage::disk('private')->delete($stored);
            throw $e;
        }

        $this->audit->log('auth.registered_agency', $user->agency, userId: $user->id);
        event(new Registered($user));

        return $user;
    }

    private function newUser(array $data, RoleSlug $role, UserStatus $status): User
    {
        $user = new User([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
        ]);
        $user->role_id = Role::forSlug($role)->id;
        $user->status = $status;
        $user->save();

        return $user;
    }

    /** Random, non-guessable filename; extension derived from detected MIME, not the client name. */
    private function storePrivately(UploadedFile $file, string $dir): string
    {
        $ext = $file->guessExtension() ?: 'bin';

        return Storage::disk('private')->putFileAs($dir, $file, Str::random(40).'.'.$ext);
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'agency';
        $slug = $base;
        $i = 2;
        while (Agency::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
