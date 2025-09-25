<?php

namespace Database\Seeders;

use App\Models\Credential;
use App\Models\Verification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class VerificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $without = Credential::doesntHave('verification')->get();

        foreach ($without as $credential) {
            $status = Arr::random(['pending', 'approved', 'rejected']);
            Verification::create([
                'credential_id' => $credential->id,
                'status' => $status,
                'notes'  => $status === 'approved'
                    ? 'Naknadno odobren kredencijal.'
                    : ($status === 'rejected'
                        ? 'Dokaz nije prihvaćen.'
                        : 'Na čekanju.'),
            ]);

            if ($status === 'approved' && !$credential->is_verified) {
                $credential->is_verified = true;
                $credential->save();
            }
        }
    }
}
