<?php

namespace Tests\Feature\Api;

use App\Models\Device;
use App\Models\FcaDraft;
use App\Models\Tractor;
use App\Models\TractorGroup;
use App\Models\User;
use App\Models\UserFca;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TpsFcaManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    #[Test]
    public function tps_can_search_fcas(): void
    {
        $tpsUser = $this->createTpsUser(assignAllTractors: true);

        $visibleFca = User::factory()->create([
            'name' => 'Maria Clara',
            'email' => 'maria@example.com',
            'phone' => '09171234567',
            'is_active' => true,
        ]);
        $visibleFca->assignRole('fca');
        UserFca::create([
            'user_id' => $visibleFca->id,
            'organization_name' => 'Green Fields Cooperative',
            'first_name' => 'Maria',
            'last_name' => 'Clara',
            'parking_latitude' => 15.4850000,
            'parking_longitude' => 120.9660000,
            'province' => 'Nueva Ecija',
            'city_town' => 'Science City of Munoz',
            'barangay' => 'San Andres',
            'date_received' => '2026-05-31',
        ]);

        $hiddenFca = User::factory()->create([
            'name' => 'Pedro Santos',
            'email' => 'pedro@example.com',
            'phone' => '09179876543',
            'is_active' => true,
        ]);
        $hiddenFca->assignRole('fca');
        UserFca::create([
            'user_id' => $hiddenFca->id,
            'organization_name' => 'Sunrise Farmers Association',
            'first_name' => 'Pedro',
            'last_name' => 'Santos',
            'parking_latitude' => 14.5995000,
            'parking_longitude' => 120.9842000,
            'province' => 'Bulacan',
            'city_town' => 'San Rafael',
            'barangay' => 'Poblacion',
            'date_received' => '2026-05-30',
        ]);

        Sanctum::actingAs($tpsUser);

        $response = $this->getJson('/api/v1/tps/fcas?search=Green');

        $response->assertOk();

        $returnedIds = collect($response->json('data'))->pluck('id')->all();

        $this->assertSame([$visibleFca->id], $returnedIds);
        $response->assertJsonFragment([
            'organization_name' => 'Green Fields Cooperative',
            'first_name' => 'Maria',
            'last_name' => 'Clara',
        ]);
    }

    #[Test]
    public function tps_can_fetch_active_tps_user_options(): void
    {
        $requestingUser = $this->createTpsUser(assignAllTractors: true);

        $visibleTps = User::factory()->create([
            'name' => 'Aaron Dizon',
            'email' => 'aaron@example.com',
            'phone' => '09170000001',
            'is_active' => true,
        ]);
        $visibleTps->assignRole('tps');

        $hiddenTps = User::factory()->create([
            'name' => 'Zenaida Cruz',
            'email' => 'zenaida@example.com',
            'phone' => '09170000002',
            'is_active' => false,
        ]);
        $hiddenTps->assignRole('tps');

        $otherRole = User::factory()->create([
            'name' => 'Felipe Farmer',
            'email' => 'felipe@example.com',
            'phone' => '09170000003',
            'is_active' => true,
        ]);
        $otherRole->assignRole('fca');

        Sanctum::actingAs($requestingUser);

        $response = $this->getJson('/api/v1/tps/users');

        $response->assertOk();
        $response->assertJsonFragment([
            'id' => $visibleTps->id,
            'name' => 'Aaron Dizon',
            'email' => 'aaron@example.com',
            'phone' => '09170000001',
        ]);
        $response->assertJsonMissing([
            'id' => $hiddenTps->id,
            'name' => 'Zenaida Cruz',
        ]);
        $response->assertJsonMissing([
            'id' => $otherRole->id,
            'name' => 'Felipe Farmer',
        ]);

        $returnedNames = collect($response->json('data'))->pluck('name')->all();

        $this->assertSame('Aaron Dizon', $returnedNames[0] ?? null);
        $this->assertContains($requestingUser->name, $returnedNames);
    }

    #[Test]
    public function tps_can_create_fca_records_with_required_phone_and_optional_email(): void
    {
        $tpsUser = $this->createTpsUser(assignAllTractors: true);
        $this->seedLocationLookups();

        Sanctum::actingAs($tpsUser);

        $response = $this->postJson('/api/v1/tps/fcas', $this->baseFcaPayload([
            'organization_name' => 'Nueva Ecija Growers Alliance',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'phone' => '09171234567',
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.organization_name', 'Nueva Ecija Growers Alliance')
            ->assertJsonPath('data.first_name', 'Juan')
            ->assertJsonPath('data.last_name', 'Dela Cruz')
            ->assertJsonPath('data.province', 'Nueva Ecija')
            ->assertJsonPath('data.city_town', 'Talavera')
            ->assertJsonPath('data.barangay', 'Sampaloc')
            ->assertJsonPath('data.tractor_details.tractor_model', 'Kubota L4708')
            ->assertJsonPath('data.survey.has_pms_schedule', false);

        $createdUserId = $response->json('data.id');

        $this->assertDatabaseHas('users', [
            'id' => $createdUserId,
            'name' => 'Juan Dela Cruz',
            'email' => null,
            'phone' => '09171234567',
            'must_change_password' => true,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('users_fca', [
            'user_id' => $createdUserId,
            'organization_name' => 'Nueva Ecija Growers Alliance',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'province' => 'Nueva Ecija',
            'city_town' => 'Talavera',
            'barangay' => 'Sampaloc',
            'date_received' => '2026-05-31 00:00:00',
        ]);

        $user = User::findOrFail($createdUserId);

        $this->assertTrue($user->hasRole('fca'));
    }

    #[Test]
    public function tps_cannot_create_fca_records_with_invalid_details_payload(): void
    {
        $tpsUser = $this->createTpsUser(assignAllTractors: true);
        $this->seedLocationLookups();

        $existingUser = User::factory()->create([
            'email' => 'duplicate-fca@example.com',
            'phone' => '09179990000',
            'is_active' => true,
        ]);
        $existingUser->assignRole('fca');

        Sanctum::actingAs($tpsUser);

        $payload = $this->baseFcaPayload([
            'organization_name' => '1234',
            'first_name' => 'J',
            'last_name' => 'D',
            'phone' => '08123456789',
            'email' => 'duplicate-fca@example.com',
        ]);
        unset($payload['survey_has_pms']);
        $payload['tractor_details'] = [];

        $response = $this->postJson('/api/v1/tps/fcas', $payload);

        $response->assertUnprocessable()->assertInvalid([
            'organization_name',
            'first_name',
            'last_name',
            'phone',
            'email',
            'tractor_details',
            'tractor_details.tractor_model',
            'tractor_details.serial_number',
            'tractor_details.engine_number',
            'survey_has_pms',
        ]);
    }

    #[Test]
    public function tps_can_create_fca_records_with_verified_photo_uploads(): void
    {
        Storage::fake('public');

        $tpsUser = $this->createTpsUser(assignAllTractors: true);
        $this->seedLocationLookups();

        Sanctum::actingAs($tpsUser);

        $response = $this->post('/api/v1/tps/fcas', $this->baseFcaPayload([
            'organization_name' => 'Central Luzon Tractor Cooperative',
            'first_name' => 'Liza',
            'last_name' => 'Ramos',
            'phone' => '09175551234',
            'tractor_photos' => [
                UploadedFile::fake()->image('tractor-front.png', 1200, 900),
                UploadedFile::fake()->image('tractor-side.png', 1200, 900),
            ],
            'logbook_photos' => [
                UploadedFile::fake()->image('logbook-page-1.png', 1200, 900),
                UploadedFile::fake()->image('logbook-page-2.png', 1200, 900),
                UploadedFile::fake()->image('logbook-page-3.png', 1200, 900),
            ],
        ]));

        $response->assertCreated()
            ->assertJsonCount(2, 'data.tractor_photo_urls')
            ->assertJsonCount(3, 'data.logbook_photo_urls');

        $createdUserId = $response->json('data.id');
        $profile = UserFca::query()
            ->with('profilePhotos')
            ->where('user_id', $createdUserId)
            ->firstOrFail();

        $tractorPhotos = $profile->profilePhotos->where('category', 'tractor')->values();
        $logbookPhotos = $profile->profilePhotos->where('category', 'logbook')->values();

        $this->assertCount(2, $tractorPhotos);
        $this->assertCount(3, $logbookPhotos);
        $this->assertDatabaseCount('fca_profile_photos', 5);

        foreach ($profile->profilePhotos as $photo) {
            Storage::disk('public')->assertExists($photo->path);
        }

        foreach (($profile->tractor_photo_paths ?? []) as $path) {
            Storage::disk('public')->assertExists($path);
        }

        foreach (($profile->logbook_photo_paths ?? []) as $path) {
            Storage::disk('public')->assertExists($path);
        }

        $this->assertStringContainsString(
            '/storage/fcas/tractor-photos/',
            (string) ($response->json('data.tractor_photo_urls.0') ?? '')
        );
        $this->assertStringContainsString(
            '/storage/fcas/logbook-photos/',
            (string) ($response->json('data.logbook_photo_urls.0') ?? '')
        );
    }

    #[Test]
    public function tps_can_create_fca_records_with_normalized_machine_hours_and_photos(): void
    {
        Storage::fake('public');

        $tpsUser = $this->createTpsUser(assignAllTractors: true);
        $this->seedLocationLookups();

        $secondaryTpsUser = User::factory()->create([
            'name' => 'Celia Mendoza',
            'email' => 'celia@example.com',
            'phone' => '09175550123',
            'is_active' => true,
        ]);
        $secondaryTpsUser->assignRole('tps');

        Sanctum::actingAs($tpsUser);

        $response = $this->post('/api/v1/tps/fcas', $this->baseFcaPayload([
            'organization_name' => 'Machine Hours Cooperative',
            'first_name' => 'Rogelio',
            'last_name' => 'Rivera',
            'phone' => '09175550999',
            'machine_hours' => [
                [
                    'entry_order' => 0,
                    'date_visited' => '2026-05-29',
                    'machine_hours' => 128,
                    'gps_status' => 'Active',
                    'in_charge_user_id' => $tpsUser->id,
                    'inspection_photos' => [
                        UploadedFile::fake()->image('visit-one-a.png', 1200, 900),
                        UploadedFile::fake()->image('visit-one-b.png', 1200, 900),
                    ],
                ],
                [
                    'entry_order' => 1,
                    'date_visited' => '2026-05-30',
                    'machine_hours' => 140,
                    'gps_status' => 'Inactive',
                    'in_charge_user_id' => $secondaryTpsUser->id,
                ],
            ],
        ]));

        $response->assertCreated()
            ->assertJsonCount(2, 'data.machine_hours')
            ->assertJsonPath('data.machine_hours.0.entry_order', 0)
            ->assertJsonPath('data.machine_hours.0.machine_hours', 128)
            ->assertJsonPath('data.machine_hours.0.gps_status', 'Active')
            ->assertJsonPath('data.machine_hours.0.in_charge.id', $tpsUser->id)
            ->assertJsonCount(2, 'data.machine_hours.0.inspection_photos')
            ->assertJsonPath('data.machine_hours.1.entry_order', 1)
            ->assertJsonPath('data.machine_hours.1.machine_hours', 140)
            ->assertJsonPath('data.machine_hours.1.gps_status', 'Inactive')
            ->assertJsonPath('data.machine_hours.1.in_charge.id', $secondaryTpsUser->id)
            ->assertJsonCount(0, 'data.machine_hours.1.inspection_photos');

        $createdUserId = $response->json('data.id');
        $profile = UserFca::query()
            ->with('machineHours.photos')
            ->where('user_id', $createdUserId)
            ->firstOrFail();

        $this->assertCount(2, $profile->machineHours);
        $this->assertSame([0, 1], $profile->machineHours->pluck('entry_order')->all());
        $this->assertSame([128, 140], $profile->machineHours->pluck('machine_hours')->all());
        $this->assertSame([$tpsUser->id, $secondaryTpsUser->id], $profile->machineHours->pluck('in_charge_user_id')->all());
        $this->assertDatabaseCount('fca_machine_hours', 2);
        $this->assertDatabaseCount('fca_machine_hour_photos', 2);

        foreach ($profile->machineHours as $machineHour) {
            foreach ($machineHour->photos as $photo) {
                Storage::disk('public')->assertExists($photo->path);
            }
        }

        $this->assertStringContainsString(
            '/storage/fcas/machine-hour-photos/',
            (string) ($response->json('data.machine_hours.0.inspection_photos.0.url') ?? '')
        );
    }

    #[Test]
    public function tps_can_create_fca_records_with_normalized_related_submission_sections(): void
    {
        $tpsUser = $this->createTpsUser(assignAllTractors: true);
        $this->seedLocationLookups();

        $selectedTractor = $this->createTractor('TRC-FCA-DETAIL-01');

        $secondaryTpsUser = User::factory()->create([
            'name' => 'Mila Santiago',
            'email' => 'mila@example.com',
            'phone' => '09175550088',
            'is_active' => true,
        ]);
        $secondaryTpsUser->assignRole('tps');

        Sanctum::actingAs($tpsUser);

        $response = $this->postJson('/api/v1/tps/fcas', $this->baseFcaPayload([
            'organization_name' => 'Normalized FCA Cooperative',
            'first_name' => 'Elena',
            'last_name' => 'Santos',
            'phone' => '09175556789',
            'survey_has_pms' => true,
            'tractor_details' => [
                'selected_tractor_id' => $selectedTractor->id,
                'tractor_model' => 'Kubota L4708',
                'serial_number' => 'SER-4421',
                'engine_number' => 'ENG-5521',
                'front_loader_serial_number' => 'FL-9901',
                'dr_number' => 'DR-8882',
                'rotavator_serial_number' => 'RT-1122',
                'disk_plow_serial_number' => 'DP-2211',
                'gps_imei' => '123456789012345',
                'gps_sim_number' => '1234567890123456',
                'gps_mobile_number' => '09171239876',
            ],
            'alternative_contacts' => [
                [
                    'entry_order' => 0,
                    'phone' => '09170000011',
                    'last_name' => 'Garcia',
                    'first_name' => 'Paolo',
                    'position' => 'Chairperson',
                ],
                [
                    'entry_order' => 1,
                    'phone' => '09170000012',
                    'last_name' => 'Lopez',
                    'first_name' => 'Mina',
                    'position' => 'Secretary',
                ],
            ],
            'survey_answers' => [
                [
                    'question_number' => 1,
                    'entry_order' => 0,
                    'answer_text' => 'Sa bukid tuwing umaga.',
                ],
                [
                    'question_number' => 2,
                    'entry_order' => 0,
                    'answer_text' => 'Apat na oras kada araw.',
                ],
                [
                    'question_number' => 3,
                    'entry_order' => 0,
                    'answer_text' => 'Minsang mahirap paandarin.',
                ],
            ],
            'pms_records' => [
                [
                    'column_order' => 0,
                    'actual_hours' => 250,
                    'performed_by' => 'LEADS',
                    'in_charge_user_id' => $tpsUser->id,
                    'categories' => ['ENGINE OIL', 'OIL FILTER'],
                ],
                [
                    'column_order' => 1,
                    'actual_hours' => 500,
                    'performed_by' => 'THIRD-PARTY',
                    'in_charge_user_id' => $secondaryTpsUser->id,
                    'categories' => ['BATTERY'],
                ],
            ],
            'damage_records' => [
                [
                    'entry_order' => 0,
                    'unit' => 'Tractor',
                    'operational_after_repair' => 'Yes',
                    'date_damaged' => '2026-05-20',
                    'date_repaired' => '2026-05-22',
                    'nature_of_problem' => 'Hydraulic leak near the front axle.',
                    'cause_of_damage' => 'Loose fitting after field work.',
                    'parts_replaced' => 'Hydraulic hose and fitting',
                    'in_charge_user_id' => $secondaryTpsUser->id,
                ],
            ],
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.tractor_details.selected_tractor_id', $selectedTractor->id)
            ->assertJsonPath('data.tractor_details.gps_sim_number', '1234567890123456')
            ->assertJsonPath('data.survey.has_pms_schedule', true)
            ->assertJsonCount(3, 'data.survey.answers')
            ->assertJsonCount(2, 'data.alternative_contacts')
            ->assertJsonCount(2, 'data.pms_records')
            ->assertJsonPath('data.pms_records.0.categories.0', 'ENGINE OIL')
            ->assertJsonCount(1, 'data.damage_records');

        $createdUserId = $response->json('data.id');

        $profile = UserFca::query()
            ->with([
                'tractorDetail',
                'alternativeContacts',
                'surveyAnswers',
                'pmsRecords.categories',
                'damageRecords',
            ])
            ->where('user_id', $createdUserId)
            ->firstOrFail();

        $this->assertSame($selectedTractor->id, $profile->tractorDetail?->tractor_id);
        $this->assertCount(2, $profile->alternativeContacts);
        $this->assertTrue((bool) $profile->surveyAnswers->firstWhere('question_number', 5)?->boolean_answer);
        $this->assertDatabaseCount('fca_tractor_details', 1);
        $this->assertDatabaseCount('fca_alternative_contacts', 2);
        $this->assertDatabaseCount('fca_survey_answers', 4);
        $this->assertDatabaseCount('fca_pms_records', 2);
        $this->assertDatabaseCount('fca_pms_record_categories', 3);
        $this->assertDatabaseCount('fca_damage_records', 1);
    }

    #[Test]
    public function tps_can_fetch_fca_detail_for_editing(): void
    {
        $tpsUser = $this->createTpsUser(assignAllTractors: true);
        $this->seedLocationLookups();

        $secondaryTpsUser = User::factory()->create([
            'name' => 'Clarita Torres',
            'email' => 'clarita@example.com',
            'phone' => '09175550021',
            'is_active' => true,
        ]);
        $secondaryTpsUser->assignRole('tps');

        Sanctum::actingAs($tpsUser);

        $createdFcaId = $this->postJson('/api/v1/tps/fcas', $this->baseFcaPayload([
            'organization_name' => 'Editable FCA Cooperative',
            'first_name' => 'Marissa',
            'last_name' => 'Tolentino',
            'phone' => '09175557771',
            'survey_has_pms' => true,
            'alternative_contacts' => [
                [
                    'entry_order' => 0,
                    'phone' => '09175557772',
                    'last_name' => 'Tolentino',
                    'first_name' => 'Paulo',
                    'position' => 'Secretary',
                ],
            ],
            'survey_answers' => [
                [
                    'question_number' => 1,
                    'entry_order' => 0,
                    'answer_text' => 'Regularly used in rice fields.',
                ],
            ],
            'pms_records' => [
                [
                    'column_order' => 0,
                    'actual_hours' => 200,
                    'performed_by' => 'LEADS',
                    'in_charge_user_id' => $secondaryTpsUser->id,
                    'categories' => ['ENGINE OIL'],
                ],
            ],
            'damage_records' => [
                [
                    'entry_order' => 0,
                    'unit' => 'Tractor',
                    'operational_after_repair' => 'Yes',
                    'date_damaged' => '2026-05-20',
                    'date_repaired' => '2026-05-22',
                    'nature_of_problem' => 'Starter motor issue.',
                    'cause_of_damage' => 'Worn electrical contacts.',
                    'parts_replaced' => 'Starter relay',
                    'in_charge_user_id' => $tpsUser->id,
                ],
            ],
            'machine_hours' => [
                [
                    'entry_order' => 0,
                    'date_visited' => '2026-05-29',
                    'machine_hours' => 88,
                    'gps_status' => 'Active',
                    'in_charge_user_id' => $secondaryTpsUser->id,
                ],
            ],
        ]))->json('data.id');

        $response = $this->getJson("/api/v1/tps/fcas/{$createdFcaId}");

        $response->assertOk()
            ->assertJsonPath('data.id', $createdFcaId)
            ->assertJsonPath('data.organization_name', 'Editable FCA Cooperative')
            ->assertJsonPath('data.survey.has_pms_schedule', true)
            ->assertJsonCount(1, 'data.alternative_contacts')
            ->assertJsonCount(1, 'data.pms_records')
            ->assertJsonCount(1, 'data.damage_records')
            ->assertJsonCount(1, 'data.machine_hours')
            ->assertJsonPath('data.machine_hours.0.in_charge.id', $secondaryTpsUser->id)
            ->assertJsonPath('data.machine_hours.0.machine_hours', 88);
    }

    #[Test]
    public function tps_can_update_fca_records_without_reuploading_existing_photos(): void
    {
        Storage::fake('public');

        $tpsUser = $this->createTpsUser(assignAllTractors: true);
        $this->seedLocationLookups();

        $secondaryTpsUser = User::factory()->create([
            'name' => 'Julio Ferrer',
            'email' => 'julio@example.com',
            'phone' => '09175550031',
            'is_active' => true,
        ]);
        $secondaryTpsUser->assignRole('tps');

        Sanctum::actingAs($tpsUser);

        $createdFcaId = $this->post('/api/v1/tps/fcas', $this->baseFcaPayload([
            'organization_name' => 'Preserved Photos Cooperative',
            'first_name' => 'Nina',
            'last_name' => 'Vergara',
            'phone' => '09175557781',
            'email' => 'nina.vergara@example.com',
            'tractor_photos' => [
                UploadedFile::fake()->image('tractor-edit.png', 1200, 900),
            ],
            'logbook_photos' => [
                UploadedFile::fake()->image('logbook-edit.png', 1200, 900),
            ],
            'machine_hours' => [
                [
                    'entry_order' => 0,
                    'date_visited' => '2026-05-28',
                    'machine_hours' => 128,
                    'gps_status' => 'Active',
                    'in_charge_user_id' => $tpsUser->id,
                    'inspection_photos' => [
                        UploadedFile::fake()->image('machine-edit.png', 1200, 900),
                    ],
                ],
            ],
        ]))->json('data.id');

        $profileBeforeUpdate = UserFca::query()
            ->with(['profilePhotos', 'machineHours.photos'])
            ->where('user_id', $createdFcaId)
            ->firstOrFail();

        $expectedTractorPhotoPaths = $profileBeforeUpdate->profilePhotos
            ->where('category', 'tractor')
            ->pluck('path')
            ->values()
            ->all();
        $expectedLogbookPhotoPaths = $profileBeforeUpdate->profilePhotos
            ->where('category', 'logbook')
            ->pluck('path')
            ->values()
            ->all();
        $expectedMachineHourPhotoPaths = $profileBeforeUpdate->machineHours
            ->flatMap(fn ($machineHour) => $machineHour->photos->pluck('path'))
            ->values()
            ->all();

        $response = $this->putJson("/api/v1/tps/fcas/{$createdFcaId}", $this->baseFcaPayload([
            'organization_name' => 'Preserved Photos Cooperative Updated',
            'first_name' => 'Nina Mae',
            'last_name' => 'Vergara',
            'phone' => '09175557782',
            'email' => 'nina.mae.vergara@example.com',
            'survey_has_pms' => true,
            'alternative_contacts' => [
                [
                    'entry_order' => 0,
                    'phone' => '09175557783',
                    'last_name' => 'Vergara',
                    'first_name' => 'Leo',
                    'position' => 'Chairperson',
                ],
            ],
            'survey_answers' => [
                [
                    'question_number' => 1,
                    'entry_order' => 0,
                    'answer_text' => 'Updated answer for editing.',
                ],
            ],
            'pms_records' => [
                [
                    'column_order' => 0,
                    'actual_hours' => 256,
                    'performed_by' => 'THIRD-PARTY',
                    'in_charge_user_id' => $secondaryTpsUser->id,
                    'categories' => ['BATTERY'],
                ],
            ],
            'damage_records' => [
                [
                    'entry_order' => 0,
                    'unit' => 'Tractor',
                    'operational_after_repair' => 'No',
                    'date_damaged' => '2026-05-25',
                    'date_repaired' => '2026-05-27',
                    'nature_of_problem' => 'Updated nature of problem.',
                    'cause_of_damage' => 'Updated cause of damage.',
                    'parts_replaced' => 'Updated parts replaced.',
                    'in_charge_user_id' => $secondaryTpsUser->id,
                ],
            ],
            'machine_hours' => [
                [
                    'entry_order' => 0,
                    'date_visited' => '2026-05-30',
                    'machine_hours' => 256,
                    'gps_status' => 'Inactive',
                    'in_charge_user_id' => $secondaryTpsUser->id,
                ],
            ],
        ]));

        $response->assertOk()
            ->assertJsonPath('data.id', $createdFcaId)
            ->assertJsonPath('data.organization_name', 'Preserved Photos Cooperative Updated')
            ->assertJsonPath('data.first_name', 'Nina Mae')
            ->assertJsonPath('data.survey.has_pms_schedule', true)
            ->assertJsonPath('data.machine_hours.0.machine_hours', 256)
            ->assertJsonPath('data.machine_hours.0.gps_status', 'Inactive')
            ->assertJsonPath('data.machine_hours.0.in_charge.id', $secondaryTpsUser->id)
            ->assertJsonCount(count($expectedTractorPhotoPaths), 'data.tractor_photo_urls')
            ->assertJsonCount(count($expectedLogbookPhotoPaths), 'data.logbook_photo_urls')
            ->assertJsonCount(count($expectedMachineHourPhotoPaths), 'data.machine_hours.0.inspection_photos');

        $profileAfterUpdate = UserFca::query()
            ->with(['profilePhotos', 'machineHours.photos'])
            ->where('user_id', $createdFcaId)
            ->firstOrFail();

        $this->assertDatabaseHas('users', [
            'id' => $createdFcaId,
            'name' => 'Nina Mae Vergara',
            'email' => 'nina.mae.vergara@example.com',
            'phone' => '09175557782',
        ]);
        $this->assertDatabaseHas('users_fca', [
            'user_id' => $createdFcaId,
            'organization_name' => 'Preserved Photos Cooperative Updated',
            'first_name' => 'Nina Mae',
            'last_name' => 'Vergara',
        ]);
        $this->assertSame($expectedTractorPhotoPaths, $profileAfterUpdate->profilePhotos->where('category', 'tractor')->pluck('path')->values()->all());
        $this->assertSame($expectedLogbookPhotoPaths, $profileAfterUpdate->profilePhotos->where('category', 'logbook')->pluck('path')->values()->all());
        $this->assertSame($expectedMachineHourPhotoPaths, $profileAfterUpdate->machineHours->flatMap(fn ($machineHour) => $machineHour->photos->pluck('path'))->values()->all());
        $this->assertDatabaseCount('fca_profile_photos', count($expectedTractorPhotoPaths) + count($expectedLogbookPhotoPaths));
        $this->assertDatabaseCount('fca_machine_hour_photos', count($expectedMachineHourPhotoPaths));

        foreach ([
            ...$expectedTractorPhotoPaths,
            ...$expectedLogbookPhotoPaths,
            ...$expectedMachineHourPhotoPaths,
        ] as $path) {
            Storage::disk('public')->assertExists($path);
        }
    }

    #[Test]
    public function tps_can_fetch_fca_location_options(): void
    {
        $tpsUser = $this->createTpsUser(assignAllTractors: true);
        $this->seedLocationLookups();

        Sanctum::actingAs($tpsUser);

        $provinceResponse = $this->getJson('/api/v1/tps/fca-locations/provinces');
        $provinceResponse->assertOk()->assertJsonFragment([
            'code' => '0349',
            'name' => 'Nueva Ecija',
        ]);

        $cityResponse = $this->getJson('/api/v1/tps/fca-locations/cities?province_code=0349');
        $cityResponse->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'code' => '034932',
                'name' => 'Talavera',
            ])
            ->assertJsonMissing([
                'code' => '042101',
                'name' => 'Bacoor City',
            ]);

        $barangayResponse = $this->getJson('/api/v1/tps/fca-locations/barangays?city_municipality_code=034932');
        $barangayResponse->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'code' => '034932001',
                'name' => 'Sampaloc',
            ])
            ->assertJsonMissing([
                'code' => '042101001',
                'name' => 'Bayanan',
            ]);
    }

    #[Test]
    public function tps_can_create_fca_drafts(): void
    {
        $tpsUser = $this->createTpsUser(assignAllTractors: true);

        Sanctum::actingAs($tpsUser);

        $response = $this->postJson('/api/v1/tps/fca-drafts', [
            'organization_name' => 'Draft Ready Cooperative',
            'first_name' => 'Jessa',
            'last_name' => 'Mercado',
            'phone' => '09179998888',
            'payload' => [
                'active_tab_index' => 2,
                'organization_name' => 'Draft Ready Cooperative',
                'tractor_details' => [
                    'tractor_model' => 'Kubota L4708',
                    'serial_number' => 'SER-DRAFT-1',
                ],
                'alternative_contacts' => [
                    [
                        'entry_order' => 0,
                        'first_name' => 'Mario',
                    ],
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.organization_name', 'Draft Ready Cooperative')
            ->assertJsonPath('data.first_name', 'Jessa')
            ->assertJsonPath('data.payload.active_tab_index', 2)
            ->assertJsonPath('data.payload.tractor_details.serial_number', 'SER-DRAFT-1');

        $draft = FcaDraft::query()->firstOrFail();

        $this->assertSame($tpsUser->id, $draft->submitted_by_user_id);
        $this->assertSame('Draft Ready Cooperative', $draft->organization_name);
        $this->assertSame('Jessa', $draft->first_name);
        $this->assertSame('SER-DRAFT-1', $draft->payload['tractor_details']['serial_number'] ?? null);
        $this->assertModelExists($draft);
    }

    #[Test]
    public function tps_can_update_existing_fca_drafts(): void
    {
        $tpsUser = $this->createTpsUser(assignAllTractors: true);

        $draft = FcaDraft::query()->create([
            'submitted_by_user_id' => $tpsUser->id,
            'organization_name' => 'Initial Draft Cooperative',
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'phone' => '09170000010',
            'payload' => [
                'active_tab_index' => 1,
            ],
        ]);

        Sanctum::actingAs($tpsUser);

        $response = $this->postJson('/api/v1/tps/fca-drafts', [
            'draft_id' => $draft->id,
            'organization_name' => 'Updated Draft Cooperative',
            'first_name' => 'Ana',
            'last_name' => 'Lopez',
            'phone' => '09170000011',
            'payload' => [
                'active_tab_index' => 5,
                'survey_has_pms' => true,
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id', $draft->id)
            ->assertJsonPath('data.organization_name', 'Updated Draft Cooperative')
            ->assertJsonPath('data.payload.active_tab_index', 5)
            ->assertJsonPath('data.payload.survey_has_pms', true);

        $draft->refresh();

        $this->assertSame('Updated Draft Cooperative', $draft->organization_name);
        $this->assertSame('09170000011', $draft->phone);
        $this->assertSame(5, $draft->payload['active_tab_index'] ?? null);
        $this->assertTrue((bool) ($draft->payload['survey_has_pms'] ?? false));
        $this->assertDatabaseCount('fca_drafts', 1);
    }

    #[Test]
    public function tps_can_delete_owned_fca_drafts(): void
    {
        $tpsUser = $this->createTpsUser(assignAllTractors: true);

        $draft = FcaDraft::query()->create([
            'submitted_by_user_id' => $tpsUser->id,
            'organization_name' => 'Delete Me Cooperative',
            'payload' => [
                'active_tab_index' => 6,
            ],
        ]);

        Sanctum::actingAs($tpsUser);

        $this->deleteJson('/api/v1/tps/fca-drafts/'.$draft->id)
            ->assertOk()
            ->assertJson(['message' => 'FCA draft deleted.']);

        $this->assertModelMissing($draft);
    }

    private function createTpsUser(bool $assignAllTractors = false): User
    {
        Role::findOrCreate('tps');
        Role::findOrCreate('fca');

        $tpsUser = User::factory()->create([
            'is_active' => true,
            'tps_assign_all_tractors' => $assignAllTractors,
        ]);
        $tpsUser->assignRole('tps');

        $this->createTractor('TRC-FCA-BASE-01', assignToUser: $tpsUser);

        return $tpsUser;
    }

    private function createTractor(string $plate, ?User $assignToUser = null): Tractor
    {
        $device = Device::create([
            'imei' => '8690660'.str_pad((string) random_int(1000000, 9999999), 7, '0', STR_PAD_LEFT),
            'device_name' => $plate,
            'is_active' => true,
        ]);

        $tractor = Tractor::create([
            'device_id' => $device->id,
            'imei' => $device->imei,
            'no_plate' => $plate,
            'brand' => 'Kubota',
            'model' => 'L4708',
            'is_active' => true,
        ]);

        $group = TractorGroup::create([
            'name' => 'Group '.$plate,
            'is_active' => true,
        ]);

        $tractor->groups()->attach($group->id);

        if ($assignToUser) {
            $group->users()->attach($assignToUser->id, ['role' => 'tps']);
        }

        return $tractor;
    }

    private function seedLocationLookups(): void
    {
        DB::table('philippine_provinces')->insert([
            [
                'psgc_code' => '034900000',
                'province_description' => 'Nueva Ecija',
                'region_code' => '03',
                'province_code' => '0349',
            ],
            [
                'psgc_code' => '042100000',
                'province_description' => 'Cavite',
                'region_code' => '04',
                'province_code' => '0421',
            ],
        ]);

        DB::table('philippine_cities')->insert([
            [
                'psgc_code' => '034932000',
                'city_municipality_description' => 'Talavera',
                'region_code' => '03',
                'province_code' => '0349',
                'city_municipality_code' => '034932',
            ],
            [
                'psgc_code' => '042101000',
                'city_municipality_description' => 'Bacoor City',
                'region_code' => '04',
                'province_code' => '0421',
                'city_municipality_code' => '042101',
            ],
        ]);

        DB::table('philippine_barangays')->insert([
            [
                'psgc_code' => '034932001',
                'barangay_description' => 'Sampaloc',
                'region_code' => '03',
                'province_code' => '0349',
                'city_municipality_code' => '034932',
            ],
            [
                'psgc_code' => '042101001',
                'barangay_description' => 'Bayanan',
                'region_code' => '04',
                'province_code' => '0421',
                'city_municipality_code' => '042101',
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function baseFcaPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'organization_name' => 'Base FCA Cooperative',
            'first_name' => 'Maria',
            'last_name' => 'Santiago',
            'phone' => '09170010001',
            'parking_latitude' => 15.4892000,
            'parking_longitude' => 120.9721000,
            'province_code' => '0349',
            'city_municipality_code' => '034932',
            'barangay_code' => '034932001',
            'date_received' => '2026-05-31',
            'survey_has_pms' => false,
            'tractor_details' => [
                'tractor_model' => 'Kubota L4708',
                'serial_number' => 'SER-1001',
                'engine_number' => 'ENG-1001',
            ],
        ], $overrides);
    }
}
