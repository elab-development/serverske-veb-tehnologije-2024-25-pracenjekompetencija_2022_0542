<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Credential;
use App\Models\User;
use App\Models\Verification;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;

class CredentialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = Category::all()->keyBy('name');

        $catalog = [
            'Cloud & DevOps' => [
                [
                    'title' => 'AWS Certified Cloud Practitioner',
                    'issuer' => 'Amazon Web Services'
                ],
                [
                    'title' => 'Microsoft Certified: Azure Fundamentals (AZ-900)',
                    'issuer' => 'Microsoft'
                ],
                [
                    'title' => 'Google Associate Cloud Engineer',
                    'issuer' => 'Google Cloud'
                ],
                [
                    'title' => 'Docker Certified Associate',
                    'issuer' => 'Docker'
                ],
            ],
            'Backend Development' => [
                [
                    'title' => 'Oracle Certified Professional, Java SE Programmer',
                    'issuer' => 'Oracle'
                ],
                [
                    'title' => 'Zend Certified PHP Engineer',
                    'issuer' => 'Zend'
                ],
                [
                    'title' => 'MongoDB Developer Associate',
                    'issuer' => 'MongoDB University'
                ],
                [
                    'title' => 'Node.js Application Developer (JSNAD)',
                    'issuer' => 'OpenJS Foundation'
                ],
            ],
            'Data & AI' => [
                [
                    'title' => 'IBM Data Science Professional Certificate',
                    'issuer' => 'IBM'
                ],
                [
                    'title' => 'TensorFlow Developer Certificate',
                    'issuer' => 'TensorFlow'
                ],
                [
                    'title' => 'Google Professional Data Engineer',
                    'issuer' => 'Google Cloud'
                ],
            ],
            'Cybersecurity' => [
                [
                    'title' => 'CompTIA Security+',
                    'issuer' => 'CompTIA'
                ],
                [
                    'title' => 'Certified Ethical Hacker (CEH)',
                    'issuer' => 'EC-Council'
                ],
                [
                    'title' => '(ISC)² Certified Information Systems Security Professional (CISSP)',
                    'issuer' => '(ISC)²'
                ],
            ],
            'Project & Product' => [
                [
                    'title' => 'Project Management Professional (PMP)',
                    'issuer' => 'PMI'
                ],
                [
                    'title' => 'Professional Scrum Master I (PSM I)',
                    'issuer' => 'Scrum.org'
                ],
                [
                    'title' => 'PRINCE2 Foundation',
                    'issuer' => 'AXELOS'
                ],
            ],
            'UX & Design' => [
                [
                    'title' => 'NN/g UX Certification',
                    'issuer' => 'Nielsen Norman Group'
                ],
                [
                    'title' => 'Google UX Design Professional Certificate',
                    'issuer' => 'Google'
                ],
            ],
        ];

        $makeDates = function () {
            $issued = Carbon::now()->subDays(rand(60, 5 * 365));
            $expires = rand(0, 1) ? (clone $issued)->addYears(rand(1, 3)) : null;
            return [$issued->toDateString(), $expires ? $expires->toDateString() : null];
        };

        $perUserMin = 2;
        $perUserMax = 4;

        $users = User::all();
        foreach ($users as $user) {
            $num = rand($perUserMin, $perUserMax);

            // Izaberi nasumične kategorije i kredencijale iz kataloga
            $chosenCategories = Arr::random($categories->keys()->toArray(), min($num, $categories->count()));

            foreach ($chosenCategories as $catName) {
                $category = $categories[$catName];
                $item = Arr::random($catalog[$catName]); // jedan kredencijal iz kategorije

                [$issuedAt, $expiresAt] = $makeDates();

                $credential = Credential::create([
                    'user_id'     => $user->id,
                    'category_id' => $category->id,
                    'title'       => $item['title'],
                    'issuer_name' => $item['issuer'],
                    'issued_at'   => $issuedAt,
                    'expires_at'  => $expiresAt,
                    'is_verified' => false,
                ]);

                // Napravi verifikaciju sa realističnom raspodelom statusa
                // ~65% approved, 25% pending, 10% rejected
                $roll = rand(1, 100);
                $status = $roll <= 65 ? 'approved' : ($roll <= 90 ? 'pending' : 'rejected');

                Verification::create([
                    'credential_id' => $credential->id,
                    'status'        => $status,
                    'notes'         => $status === 'approved'
                        ? 'Dokument proveren i prihvaćen.'
                        : ($status === 'rejected'
                            ? 'Neusklađen format dokaza ili nevažeći link.'
                            : 'Zahtev čeka reviziju administratora.'),
                ]);

                if ($status === 'approved') {
                    $credential->is_verified = true;
                    $credential->save();
                }
            }
        }
    }
}
