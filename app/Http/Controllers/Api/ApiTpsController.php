<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FarmerFeedback;
use App\Models\FcaAlternativeContact;
use App\Models\FcaDamageRecord;
use App\Models\FcaDraft;
use App\Models\FcaMachineHour;
use App\Models\FcaPmsRecord;
use App\Models\FcaProfilePhoto;
use App\Models\FcaSurveyAnswer;
use App\Models\FcaTractorDetail;
use App\Models\Maintenance;
use App\Models\Notification;
use App\Models\Ticket;
use App\Models\Tractor;
use App\Models\TractorDistribution;
use App\Models\User;
use App\Models\UserFca;
use App\Services\M360SmsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Throwable;

class ApiTpsController extends Controller
{
    private const FCA_PMS_CATEGORIES = [
        'ENGINE OIL',
        'OIL FILTER',
        'HYDRAULIC OIL',
        'HYDRAULIC FILTER',
        'FUEL FILTER',
        'GREASING',
        'BRAKE INSPECTION',
        'TIRE',
        'BATTERY',
    ];

    private const FCA_PMS_PERFORMED_BY_OPTIONS = [
        'LEADS',
        'SELF PMS',
        'THIRD-PARTY',
    ];

    private const FCA_DAMAGE_UNITS = [
        'Tractor',
        'Front Loader',
        'Rotavator',
        'Disc Plow',
        'Disc Harrow',
        'Moldboard Plow',
        'Cultivator',
        'Boom Sprayer',
        'Seed Drill / Seeder',
        'Trailer',
        'Mower / Slasher',
        'Subsoiler',
        'Land Leveler',
        'Ridger',
        'Post Hole Digger',
        'Cage Wheel',
        'Chisel Plow',
    ];

    private const FCA_DAMAGE_OPERATIONAL_OPTIONS = ['Yes', 'No'];

    /**
     * Dashboard summary for TPS user.
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $visibleTractorIds = $this->visibleTractorIdsForTps($user);
        $manageableTractorIds = $this->manageableTractorIdsForTps($user);

        return response()->json([
            'tractors_count' => Tractor::whereIn('id', $visibleTractorIds)
                ->whereHas('device', fn ($q) => $q->notStale())
                ->count(),
            'open_tickets' => Ticket::whereIn('tractor_id', $visibleTractorIds)->whereIn('status', ['open', 'in_progress'])->count(),
            'pending_maintenance' => Maintenance::whereIn('tractor_id', $visibleTractorIds)->where('status', 'pending')->count(),
            'active_distributions' => TractorDistribution::whereIn('tractor_id', $manageableTractorIds)->where('status', 'distributed')->count(),
        ]);
    }

    /**
     * List tickets visible to the TPS user.
     */
    public function tickets(Request $request)
    {
        $tractorIds = $this->visibleTractorIdsForTps($request->user());
        $search = trim((string) $request->input('search', ''));

        $tickets = Ticket::query()
            ->with([
                'tractor:id,no_plate,brand,model',
                'submitter:id,name',
                'assignee:id,name',
                'latestComment.user:id,name',
            ])
            ->withMax('comments', 'created_at')
            ->whereIn('tractor_id', $tractorIds)
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->status))
            ->when($request->filled('priority'), fn (Builder $q) => $q->where('priority', $request->priority))
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $searchQuery) use ($search) {
                    $searchQuery->where('subject', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('tractor', fn (Builder $tractorQuery) => $tractorQuery
                            ->where('no_plate', 'like', "%{$search}%")
                            ->orWhere('brand', 'like', "%{$search}%")
                            ->orWhere('model', 'like', "%{$search}%"))
                        ->orWhereHas('submitter', fn (Builder $submitterQuery) => $submitterQuery
                            ->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('assignee', fn (Builder $assigneeQuery) => $assigneeQuery
                            ->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderByRaw('coalesce(comments_max_created_at, tickets.created_at) desc')
            ->orderByDesc('tickets.id')
            ->paginate($request->per_page ?? 15);

        return response()->json($tickets);
    }

    /**
     * List maintenances (PMS) visible to the TPS user.
     */
    public function maintenances(Request $request)
    {
        $tractorIds = $this->visibleTractorIdsForTps($request->user());

        $maintenances = Maintenance::with(['tractor:id,no_plate,brand,model', 'issueType:id,name', 'performer:id,name'])
            ->whereIn('tractor_id', $tractorIds)
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->status))
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json($maintenances);
    }

    /**
     * List farmer feedbacks visible to the TPS user.
     */
    public function feedbacks(Request $request)
    {
        $tractorIds = $this->visibleTractorIdsForTps($request->user());
        $search = trim((string) $request->input('search', ''));

        $feedbacks = FarmerFeedback::with(['tractor:id,no_plate,brand,model', 'submitter:id,name', 'booking:id,booking_date,purpose'])
            ->whereIn('tractor_id', $tractorIds)
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->status))
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $searchQuery) use ($search) {
                    $searchQuery->where('feedback', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhereHas('tractor', fn (Builder $tractorQuery) => $tractorQuery
                            ->where('no_plate', 'like', "%{$search}%")
                            ->orWhere('brand', 'like', "%{$search}%")
                            ->orWhere('model', 'like', "%{$search}%"))
                        ->orWhereHas('submitter', fn (Builder $submitterQuery) => $submitterQuery
                            ->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('booking', fn (Builder $bookingQuery) => $bookingQuery
                            ->where('purpose', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json($feedbacks);
    }

    /**
     * List active TPS users for assignment and in-charge suggestions.
     */
    public function users(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        $users = User::query()
            ->role('tps')
            ->where('is_active', true)
            ->select('id', 'name', 'email', 'phone')
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $searchQuery) use ($search) {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->get()
            ->sortBy(fn (User $user) => mb_strtolower($user->name))
            ->values()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
            ]);

        return response()->json(['data' => $users]);
    }

    /**
     * List tractors visible on the TPS account.
     */
    public function tractors(Request $request)
    {
        $tractorIds = $this->visibleTractorIdsForTps($request->user());
        $search = trim((string) $request->input('search', ''));

        $tractors = Tractor::with(['device:id,imei,device_name,sim,sim_iccid', 'groups:id,name'])
            ->whereIn('id', $tractorIds)
            // Exclude tractors with devices stale >365 days
            ->whereHas('device', fn ($q) => $q->notStale())
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $searchQuery) use ($search) {
                    $searchQuery->where('no_plate', 'like', "%{$search}%")
                        ->orWhere('imei', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%")
                        ->orWhere('model', 'like', "%{$search}%")
                        ->orWhereHas('groups', fn (Builder $groupQuery) => $groupQuery
                            ->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('device', fn (Builder $deviceQuery) => $deviceQuery
                            ->where('imei', 'like', "%{$search}%")
                            ->orWhere('device_name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json($tractors);
    }

    /**
     * List distributions for tractors in TPS user's assigned scope.
     */
    public function distributions(Request $request)
    {
        $user = $request->user();
        $tractorIds = $this->manageableTractorIdsForTps($user);
        $search = trim((string) $request->input('search', ''));

        $distributions = TractorDistribution::with(['tractor:id,no_plate,brand,model', 'distributedToUser:id,name,email'])
            ->whereIn('tractor_id', $tractorIds)
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->status), fn (Builder $q) => $q->where('status', '!=', 'returned'))
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $searchQuery) use ($search) {
                    $searchQuery->where('area', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('tractor', fn (Builder $tractorQuery) => $tractorQuery
                            ->where('no_plate', 'like', "%{$search}%")
                            ->orWhere('brand', 'like', "%{$search}%")
                            ->orWhere('model', 'like', "%{$search}%"))
                        ->orWhereHas('distributedToUser', fn (Builder $userQuery) => $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($request->per_page ?? 15);

        return response()->json($distributions);
    }

    /**
     * List FCA users for the TPS dashboard.
     */
    public function fcas(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        $fcas = User::query()
            ->role('fca')
            ->where('is_active', true)
            ->with([
                'fcaProfile.profilePhotos',
                'fcaProfile.machineHours.photos',
                'fcaProfile.machineHours.inChargeUser:id,name,email,phone',
            ])
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $searchQuery) use ($search) {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhereHas('fcaProfile', function (Builder $profileQuery) use ($search) {
                            $profileQuery->where('organization_name', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('province', 'like', "%{$search}%")
                                ->orWhere('city_town', 'like', "%{$search}%")
                                ->orWhere('barangay', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate($request->per_page ?? 20);

        $fcas->setCollection(
            $fcas->getCollection()->map(fn (User $user) => $this->formatFca($user))
        );

        return response()->json($fcas);
    }

    /**
     * Province options for the TPS FCA create form.
     */
    public function fcaLocationProvinces()
    {
        $provinces = DB::table('philippine_provinces')
            ->select('province_code', 'province_description')
            ->get()
            ->sortBy(fn ($province) => mb_strtolower((string) $province->province_description))
            ->unique('province_code')
            ->values()
            ->map(fn ($province) => [
                'code' => (string) $province->province_code,
                'name' => (string) $province->province_description,
            ]);

        return response()->json(['data' => $provinces]);
    }

    /**
     * City / municipality options for the selected province.
     */
    public function fcaLocationCities(Request $request)
    {
        $validated = $request->validate([
            'province_code' => ['required', 'string', Rule::exists('philippine_provinces', 'province_code')],
        ]);

        $cities = DB::table('philippine_cities')
            ->select('city_municipality_code', 'city_municipality_description')
            ->where('province_code', $validated['province_code'])
            ->get()
            ->sortBy(fn ($city) => mb_strtolower((string) $city->city_municipality_description))
            ->map(fn ($city) => [
                'code' => (string) $city->city_municipality_code,
                'name' => (string) $city->city_municipality_description,
            ]);

        return response()->json(['data' => $cities]);
    }

    /**
     * Barangay options for the selected city / municipality.
     */
    public function fcaLocationBarangays(Request $request)
    {
        $validated = $request->validate([
            'city_municipality_code' => ['required', 'string', Rule::exists('philippine_cities', 'city_municipality_code')],
        ]);

        $barangays = DB::table('philippine_barangays')
            ->select('psgc_code', 'barangay_description')
            ->where('city_municipality_code', $validated['city_municipality_code'])
            ->get()
            ->sortBy(fn ($barangay) => mb_strtolower((string) $barangay->barangay_description))
            ->map(fn ($barangay) => [
                'code' => (string) $barangay->psgc_code,
                'name' => (string) $barangay->barangay_description,
            ]);

        return response()->json(['data' => $barangays]);
    }

    /**
     * Store or update an FCA draft for the current TPS user.
     */
    public function storeFcaDraft(Request $request)
    {
        $validated = $request->validate([
            'draft_id' => ['nullable', 'integer', Rule::exists('fca_drafts', 'id')],
            'organization_name' => ['nullable', 'string', 'max:255'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'payload' => ['required', 'array'],
        ]);

        $draftAttributes = [
            'organization_name' => $validated['organization_name'] ?? null,
            'first_name' => $validated['first_name'] ?? null,
            'last_name' => $validated['last_name'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'payload' => $validated['payload'],
        ];

        $status = 200;

        if (isset($validated['draft_id'])) {
            $draft = FcaDraft::query()
                ->where('submitted_by_user_id', $request->user()->id)
                ->findOrFail((int) $validated['draft_id']);

            $draft->fill($draftAttributes);
            $draft->save();
        } else {
            $draft = FcaDraft::query()->create([
                'submitted_by_user_id' => $request->user()->id,
                ...$draftAttributes,
            ]);

            $status = 201;
        }

        return response()->json([
            'message' => $status === 201 ? 'FCA draft saved.' : 'FCA draft updated.',
            'data' => $this->formatFcaDraft($draft->fresh()),
        ], $status);
    }

    /**
     * Delete an FCA draft owned by the current TPS user.
     */
    public function destroyFcaDraft(Request $request, FcaDraft $draft)
    {
        abort_unless($draft->submitted_by_user_id === $request->user()->id, 404);

        $draft->delete();

        return response()->json(['message' => 'FCA draft deleted.']);
    }

    /**
     * Show a single FCA entry for editing.
     */
    public function showFca(User $fca)
    {
        $fca = $this->ensureEditableFca($fca);

        return response()->json([
            'data' => $this->formatFca($this->loadFcaForResponse($fca)),
        ]);
    }

    /**
     * Update an existing FCA entry.
     */
    public function updateFca(Request $request, User $fca)
    {
        $fca = $this->ensureEditableFca($fca);

        $visibleTractorIds = $this->visibleTractorIdsForTps($request->user());
        $validated = $this->validateFcaSubmission($request, $visibleTractorIds, $fca);
        $locationNames = $this->resolveFcaLocationNames($validated);

        $user = $this->persistFcaSubmission(
            $request,
            $validated,
            $locationNames,
            $request->boolean('survey_has_pms'),
            $fca,
        );

        return response()->json([
            'message' => 'FCA updated successfully.',
            'data' => $this->formatFca($user),
        ]);
    }

    /**
     * Store a new FCA user with its profile record.
     */
    public function storeFca(Request $request)
    {
        $visibleTractorIds = $this->visibleTractorIdsForTps($request->user());

        $validated = $this->validateFcaSubmission($request, $visibleTractorIds);
        $locationNames = $this->resolveFcaLocationNames($validated);

        $user = $this->persistFcaSubmission(
            $request,
            $validated,
            $locationNames,
            $request->boolean('survey_has_pms'),
        );

        return response()->json([
            'message' => 'FCA created successfully.',
            'data' => $this->formatFca($user),
        ], 201);
    }

    /**
     * @param  array<int>  $visibleTractorIds
     * @return array<string, mixed>
     */
    private function validateFcaSubmission(Request $request, array $visibleTractorIds, ?User $existingFca = null): array
    {
        $phoneUniqueRule = Rule::unique('users', 'phone');
        $emailUniqueRule = Rule::unique('users', 'email');

        if ($existingFca) {
            $phoneUniqueRule = $phoneUniqueRule->ignore($existingFca->id);
            $emailUniqueRule = $emailUniqueRule->ignore($existingFca->id);
        }

        return $request->validate([
            'organization_name' => ['required', 'string', 'min:5', 'max:255', 'regex:/\p{L}/u'],
            'phone' => ['required', 'string', 'regex:/^09\d{9}$/', $phoneUniqueRule],
            'email' => ['nullable', 'email', 'max:255', $emailUniqueRule],
            'last_name' => ['required', 'string', 'min:2', 'max:255'],
            'first_name' => ['required', 'string', 'min:2', 'max:255'],
            'parking_latitude' => ['required', 'numeric', 'between:-90,90'],
            'parking_longitude' => ['required', 'numeric', 'between:-180,180'],
            'province_code' => ['required', 'string', Rule::exists('philippine_provinces', 'province_code')],
            'city_municipality_code' => [
                'required',
                'string',
                Rule::exists('philippine_cities', 'city_municipality_code')->where(
                    fn ($query) => $query->where('province_code', (string) $request->input('province_code'))
                ),
            ],
            'barangay_code' => [
                'required',
                'string',
                Rule::exists('philippine_barangays', 'psgc_code')->where(
                    fn ($query) => $query->where('city_municipality_code', (string) $request->input('city_municipality_code'))
                ),
            ],
            'date_received' => ['required', 'date'],
            'tractor_details' => ['required', 'array:selected_tractor_id,tractor_model,front_loader_serial_number,dr_number,rotavator_serial_number,serial_number,disk_plow_serial_number,engine_number,gps_imei,gps_sim_number,gps_mobile_number'],
            'tractor_details.selected_tractor_id' => [
                'nullable',
                'integer',
                Rule::exists('tractors', 'id')->where(fn ($query) => $query->whereIn('id', $visibleTractorIds)),
            ],
            'tractor_details.tractor_model' => ['required', 'string', 'min:2', 'max:255'],
            'tractor_details.front_loader_serial_number' => ['nullable', 'string', 'max:255'],
            'tractor_details.dr_number' => ['nullable', 'string', 'max:255'],
            'tractor_details.rotavator_serial_number' => ['nullable', 'string', 'max:255'],
            'tractor_details.serial_number' => ['required', 'string', 'min:2', 'max:255'],
            'tractor_details.disk_plow_serial_number' => ['nullable', 'string', 'max:255'],
            'tractor_details.engine_number' => ['required', 'string', 'min:2', 'max:255'],
            'tractor_details.gps_imei' => ['nullable', 'string', 'max:255'],
            'tractor_details.gps_sim_number' => ['nullable', 'string', 'regex:/^\d{16}$/'],
            'tractor_details.gps_mobile_number' => ['nullable', 'string', 'regex:/^09\d{9}$/'],
            'alternative_contacts' => ['nullable', 'array'],
            'alternative_contacts.*' => ['array:entry_order,phone,last_name,first_name,position'],
            'alternative_contacts.*.entry_order' => ['required', 'integer', 'min:0'],
            'alternative_contacts.*.phone' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'alternative_contacts.*.last_name' => ['required', 'string', 'min:2', 'max:255'],
            'alternative_contacts.*.first_name' => ['required', 'string', 'min:2', 'max:255'],
            'alternative_contacts.*.position' => ['required', 'string', 'min:2', 'max:255'],
            'survey_has_pms' => ['required', 'boolean'],
            'survey_answers' => ['nullable', 'array'],
            'survey_answers.*' => ['array:question_number,entry_order,answer_text'],
            'survey_answers.*.question_number' => ['required', 'integer', Rule::in([1, 2, 3, 4])],
            'survey_answers.*.entry_order' => ['required', 'integer', 'min:0'],
            'survey_answers.*.answer_text' => ['required', 'string', 'min:1'],
            'pms_records' => ['nullable', 'array'],
            'pms_records.*' => ['array:column_order,actual_hours,performed_by,in_charge_user_id,categories'],
            'pms_records.*.column_order' => ['required', 'integer', 'min:0'],
            'pms_records.*.actual_hours' => ['required', 'integer', 'min:0'],
            'pms_records.*.performed_by' => ['required', 'string', Rule::in(self::FCA_PMS_PERFORMED_BY_OPTIONS)],
            'pms_records.*.in_charge_user_id' => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_active', true))],
            'pms_records.*.categories' => ['required', 'array', 'min:1'],
            'pms_records.*.categories.*' => ['required', 'string', Rule::in(self::FCA_PMS_CATEGORIES)],
            'damage_records' => ['nullable', 'array'],
            'damage_records.*' => ['array:entry_order,unit,operational_after_repair,date_damaged,date_repaired,nature_of_problem,cause_of_damage,parts_replaced,in_charge_user_id'],
            'damage_records.*.entry_order' => ['required', 'integer', 'min:0'],
            'damage_records.*.unit' => ['required', 'string', Rule::in(self::FCA_DAMAGE_UNITS)],
            'damage_records.*.operational_after_repair' => ['required', 'string', Rule::in(self::FCA_DAMAGE_OPERATIONAL_OPTIONS)],
            'damage_records.*.date_damaged' => ['required', 'date', 'before_or_equal:today'],
            'damage_records.*.date_repaired' => ['required', 'date', 'before_or_equal:today'],
            'damage_records.*.nature_of_problem' => ['required', 'string', 'min:1'],
            'damage_records.*.cause_of_damage' => ['required', 'string', 'min:1'],
            'damage_records.*.parts_replaced' => ['required', 'string', 'min:1'],
            'damage_records.*.in_charge_user_id' => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_active', true))],
            'machine_hours' => ['nullable', 'array'],
            'machine_hours.*' => ['array:entry_order,date_visited,machine_hours,gps_status,in_charge_user_id,inspection_photos'],
            'machine_hours.*.entry_order' => ['required', 'integer', 'min:0'],
            'machine_hours.*.date_visited' => ['required', 'date', 'before_or_equal:today'],
            'machine_hours.*.machine_hours' => ['required', 'integer', 'min:0'],
            'machine_hours.*.gps_status' => ['required', 'string', Rule::in(['Active', 'Inactive', 'No GPS'])],
            'machine_hours.*.in_charge_user_id' => ['required', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('is_active', true))],
            'machine_hours.*.inspection_photos' => ['nullable', 'array', 'max:2'],
            'machine_hours.*.inspection_photos.*' => ['required', File::image()->max(10 * 1024)],
            'tractor_photos' => ['nullable', 'array', 'max:2'],
            'tractor_photos.*' => ['required', File::image()->max(10 * 1024)],
            'logbook_photos' => ['nullable', 'array'],
            'logbook_photos.*' => ['required', File::image()->max(10 * 1024)],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, string|null>
     */
    private function resolveFcaLocationNames(array $validated): array
    {
        $provinceName = DB::table('philippine_provinces')
            ->where('province_code', $validated['province_code'])
            ->get(['province_description'])
            ->sortBy(fn ($province) => mb_strtolower((string) $province->province_description))
            ->first()?->province_description;

        $cityName = DB::table('philippine_cities')
            ->where('city_municipality_code', $validated['city_municipality_code'])
            ->value('city_municipality_description');

        $barangayName = DB::table('philippine_barangays')
            ->where('psgc_code', $validated['barangay_code'])
            ->value('barangay_description');

        return [
            'province' => $provinceName,
            'city_town' => $cityName,
            'barangay' => $barangayName,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @param  array<string, string|null>  $locationNames
     */
    private function persistFcaSubmission(
        Request $request,
        array $validated,
        array $locationNames,
        bool $hasPmsSchedule,
        ?User $existingFca = null,
    ): User {
        $storedPaths = [];
        $existingProfile = $existingFca
            ? $this->loadFcaForResponse($existingFca)->fcaProfile
            : null;

        $existingTractorPhotoPaths = $existingProfile
            ? $this->existingProfilePhotoPaths($existingProfile, FcaProfilePhoto::CATEGORY_TRACTOR, 'tractor_photo_paths')
            : [];

        $existingLogbookPhotoPaths = $existingProfile
            ? $this->existingProfilePhotoPaths($existingProfile, FcaProfilePhoto::CATEGORY_LOGBOOK, 'logbook_photo_paths')
            : [];

        $existingMachineHoursByEntryOrder = $existingProfile?->machineHours->keyBy('entry_order');

        $existingMachineHourPhotoPaths = $existingProfile
            ? $existingProfile->machineHours
                ->flatMap(fn (FcaMachineHour $machineHour) => $machineHour->photos->pluck('path'))
                ->values()
                ->all()
            : [];

        try {
            $tractorPhotoPaths = $this->resolveUploadedOrExistingPaths(
                $request->file('tractor_photos', []),
                'fcas/tractor-photos',
                $storedPaths,
                $existingTractorPhotoPaths,
            );

            $logbookPhotoPaths = $this->resolveUploadedOrExistingPaths(
                $request->file('logbook_photos', []),
                'fcas/logbook-photos',
                $storedPaths,
                $existingLogbookPhotoPaths,
            );

            $machineHours = collect($validated['machine_hours'] ?? [])
                ->values()
                ->map(function (array $machineHour, int $index) use ($request, &$storedPaths, $existingMachineHoursByEntryOrder) {
                    $entryOrder = (int) $machineHour['entry_order'];
                    $existingInspectionPhotoPaths = $existingMachineHoursByEntryOrder?->get($entryOrder)?->photos
                        ?->pluck('path')
                        ->values()
                        ->all() ?? [];

                    $inspectionPhotoPaths = $this->resolveUploadedOrExistingPaths(
                        $request->file("machine_hours.{$index}.inspection_photos", []),
                        'fcas/machine-hour-photos',
                        $storedPaths,
                        $existingInspectionPhotoPaths,
                    );

                    return [
                        'entry_order' => $entryOrder,
                        'date_visited' => $machineHour['date_visited'],
                        'machine_hours' => (int) $machineHour['machine_hours'],
                        'gps_status' => $machineHour['gps_status'],
                        'in_charge_user_id' => (int) $machineHour['in_charge_user_id'],
                        'inspection_photo_paths' => $inspectionPhotoPaths,
                    ];
                });

            $user = DB::transaction(function () use (
                $validated,
                $locationNames,
                $hasPmsSchedule,
                $tractorPhotoPaths,
                $logbookPhotoPaths,
                $machineHours,
                $existingFca,
                $existingProfile,
            ) {
                if ($existingFca) {
                    $user = $existingFca;
                    $user->update([
                        'name' => trim($validated['first_name'].' '.$validated['last_name']),
                        'email' => $validated['email'] ?? null,
                        'phone' => $validated['phone'] ?? null,
                    ]);
                } else {
                    $user = User::create([
                        'name' => trim($validated['first_name'].' '.$validated['last_name']),
                        'email' => $validated['email'] ?? null,
                        'phone' => $validated['phone'] ?? null,
                        'password' => 'tanod2026',
                        'is_active' => true,
                        'must_change_password' => true,
                    ]);

                    $user->assignRole('fca');
                }

                $profileAttributes = [
                    'organization_name' => $validated['organization_name'],
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'parking_latitude' => $validated['parking_latitude'],
                    'parking_longitude' => $validated['parking_longitude'],
                    'province' => $locationNames['province'],
                    'city_town' => $locationNames['city_town'],
                    'barangay' => $locationNames['barangay'],
                    'date_received' => $validated['date_received'],
                    'tractor_photo_paths' => $tractorPhotoPaths === [] ? null : $tractorPhotoPaths,
                    'logbook_photo_paths' => $logbookPhotoPaths === [] ? null : $logbookPhotoPaths,
                ];

                if ($existingProfile) {
                    $profile = $existingProfile;
                    $profile->update($profileAttributes);
                    $profile->profilePhotos()->delete();
                    $profile->alternativeContacts()->delete();
                    $profile->surveyAnswers()->delete();
                    $profile->pmsRecords->each(fn (FcaPmsRecord $pmsRecord) => $pmsRecord->categories()->delete());
                    $profile->pmsRecords()->delete();
                    $profile->machineHours->each(fn (FcaMachineHour $machineHour) => $machineHour->photos()->delete());
                    $profile->machineHours()->delete();
                    $profile->damageRecords()->delete();
                } else {
                    $profile = $user->fcaProfile()->create($profileAttributes);
                }

                $profile->tractorDetail()->updateOrCreate([], [
                    'tractor_id' => isset($validated['tractor_details']['selected_tractor_id'])
                        ? (int) $validated['tractor_details']['selected_tractor_id']
                        : null,
                    'tractor_model' => $validated['tractor_details']['tractor_model'],
                    'front_loader_serial_number' => $validated['tractor_details']['front_loader_serial_number'] ?? null,
                    'dr_number' => $validated['tractor_details']['dr_number'] ?? null,
                    'rotavator_serial_number' => $validated['tractor_details']['rotavator_serial_number'] ?? null,
                    'serial_number' => $validated['tractor_details']['serial_number'],
                    'disk_plow_serial_number' => $validated['tractor_details']['disk_plow_serial_number'] ?? null,
                    'engine_number' => $validated['tractor_details']['engine_number'],
                    'gps_imei' => $validated['tractor_details']['gps_imei'] ?? null,
                    'gps_sim_number' => $validated['tractor_details']['gps_sim_number'] ?? null,
                    'gps_mobile_number' => $validated['tractor_details']['gps_mobile_number'] ?? null,
                ]);

                if ($tractorPhotoPaths !== []) {
                    $profile->profilePhotos()->createMany(
                        $this->buildProfilePhotoPayload(FcaProfilePhoto::CATEGORY_TRACTOR, $tractorPhotoPaths)
                    );
                }

                if ($logbookPhotoPaths !== []) {
                    $profile->profilePhotos()->createMany(
                        $this->buildProfilePhotoPayload(FcaProfilePhoto::CATEGORY_LOGBOOK, $logbookPhotoPaths)
                    );
                }

                $alternativeContacts = collect($validated['alternative_contacts'] ?? [])
                    ->values()
                    ->map(fn (array $contact) => [
                        'entry_order' => (int) $contact['entry_order'],
                        'phone' => $contact['phone'],
                        'last_name' => $contact['last_name'],
                        'first_name' => $contact['first_name'],
                        'position' => $contact['position'],
                    ])
                    ->all();

                if ($alternativeContacts !== []) {
                    $profile->alternativeContacts()->createMany($alternativeContacts);
                }

                $surveyAnswers = collect($validated['survey_answers'] ?? [])
                    ->values()
                    ->map(fn (array $answer) => [
                        'question_number' => (int) $answer['question_number'],
                        'entry_order' => (int) $answer['entry_order'],
                        'answer_text' => $answer['answer_text'],
                        'boolean_answer' => null,
                    ])
                    ->push([
                        'question_number' => 5,
                        'entry_order' => 0,
                        'answer_text' => null,
                        'boolean_answer' => $hasPmsSchedule,
                    ])
                    ->all();

                $profile->surveyAnswers()->createMany($surveyAnswers);

                collect($validated['pms_records'] ?? [])
                    ->values()
                    ->each(function (array $pmsRecordData) use ($profile) {
                        $pmsRecord = $profile->pmsRecords()->create([
                            'column_order' => (int) $pmsRecordData['column_order'],
                            'actual_hours' => (int) $pmsRecordData['actual_hours'],
                            'performed_by' => $pmsRecordData['performed_by'],
                            'in_charge_user_id' => (int) $pmsRecordData['in_charge_user_id'],
                        ]);

                        $pmsRecord->categories()->createMany(
                            collect($pmsRecordData['categories'])
                                ->values()
                                ->map(fn (string $category, int $categoryIndex) => [
                                    'category' => $category,
                                    'sort_order' => $categoryIndex,
                                ])
                                ->all()
                        );
                    });

                $machineHours->each(function (array $machineHourData) use ($profile) {
                    $machineHour = $profile->machineHours()->create([
                        'entry_order' => $machineHourData['entry_order'],
                        'date_visited' => $machineHourData['date_visited'],
                        'machine_hours' => $machineHourData['machine_hours'],
                        'gps_status' => $machineHourData['gps_status'],
                        'in_charge_user_id' => $machineHourData['in_charge_user_id'],
                    ]);

                    $photoPayload = collect($machineHourData['inspection_photo_paths'])
                        ->values()
                        ->map(fn (string $path, int $photoIndex) => [
                            'path' => $path,
                            'sort_order' => $photoIndex,
                        ])
                        ->all();

                    if ($photoPayload !== []) {
                        $machineHour->photos()->createMany($photoPayload);
                    }
                });

                $damageRecords = collect($validated['damage_records'] ?? [])
                    ->values()
                    ->map(fn (array $damageRecord) => [
                        'entry_order' => (int) $damageRecord['entry_order'],
                        'unit' => $damageRecord['unit'],
                        'operational_after_repair' => $damageRecord['operational_after_repair'],
                        'date_damaged' => $damageRecord['date_damaged'],
                        'date_repaired' => $damageRecord['date_repaired'],
                        'nature_of_problem' => $damageRecord['nature_of_problem'],
                        'cause_of_damage' => $damageRecord['cause_of_damage'],
                        'parts_replaced' => $damageRecord['parts_replaced'],
                        'in_charge_user_id' => (int) $damageRecord['in_charge_user_id'],
                    ])
                    ->all();

                if ($damageRecords !== []) {
                    $profile->damageRecords()->createMany($damageRecords);
                }

                return $this->loadFcaForResponse($user->fresh());
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($storedPaths);

            throw $exception;
        }

        if ($existingProfile) {
            $stalePaths = collect([
                ...$existingTractorPhotoPaths,
                ...$existingLogbookPhotoPaths,
                ...$existingMachineHourPhotoPaths,
            ])
                ->diff([
                    ...$tractorPhotoPaths,
                    ...$logbookPhotoPaths,
                    ...$machineHours->flatMap(fn (array $machineHour) => $machineHour['inspection_photo_paths'])->all(),
                ])
                ->unique()
                ->values()
                ->all();

            if ($stalePaths !== []) {
                Storage::disk('public')->delete($stalePaths);
            }
        }

        return $user;
    }

    private function ensureEditableFca(User $fca): User
    {
        abort_unless($fca->is_active && $fca->hasRole('fca') && $fca->fcaProfile()->exists(), 404);

        return $fca;
    }

    private function loadFcaForResponse(User $user): User
    {
        return $user->load([
            'fcaProfile.tractorDetail',
            'fcaProfile.alternativeContacts',
            'fcaProfile.profilePhotos',
            'fcaProfile.surveyAnswers',
            'fcaProfile.pmsRecords.categories',
            'fcaProfile.pmsRecords.inChargeUser:id,name,email,phone',
            'fcaProfile.machineHours.photos',
            'fcaProfile.machineHours.inChargeUser:id,name,email,phone',
            'fcaProfile.damageRecords.inChargeUser:id,name,email,phone',
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function existingProfilePhotoPaths(UserFca $profile, string $category, string $legacyColumn): array
    {
        if ($profile->relationLoaded('profilePhotos')) {
            return $profile->profilePhotos
                ->where('category', $category)
                ->pluck('path')
                ->values()
                ->all();
        }

        return collect($profile->{$legacyColumn} ?? [])->values()->all();
    }

    /**
     * @param  array<int, string>  $existingPaths
     * @return array<int, string>
     */
    private function resolveUploadedOrExistingPaths(
        mixed $files,
        string $directory,
        array &$storedPaths,
        array $existingPaths = [],
    ): array {
        $uploadedPaths = $this->storeUploadedFiles($files, $directory, $storedPaths);

        return $uploadedPaths === [] ? $existingPaths : $uploadedPaths;
    }

    /**
     * Get tractor IDs visible to all TPS users.
     *
     * @return array<int>
     */
    private function visibleTractorIdsForTps(User $user): array
    {
        return $user->accessibleTractorIds();
    }

    /**
     * Get tractor IDs that remain manageable for the TPS user.
     *
     * @return array<int>
     */
    private function manageableTractorIdsForTps(\App\Models\User $user): array
    {
        return $user->accessibleTractorIds();
    }

    /**
     * Form data for creating a ticket (available tractors for dropdown).
     */
    public function ticketFormData(Request $request)
    {
        $tractorIds = $this->visibleTractorIdsForTps($request->user());

        $tractors = Tractor::whereIn('id', $tractorIds)
            // Exclude tractors with devices stale >365 days
            ->whereHas('device', fn ($q) => $q->notStale())
            ->select('id', 'no_plate', 'brand', 'model')
            ->get();

        return response()->json(['tractors' => $tractors]);
    }

    /**
     * Show a single ticket detail for TPS user.
     */
    public function ticketDetail(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        $tractorIds = $this->visibleTractorIdsForTps($user);

        abort_unless(
            in_array($ticket->tractor_id, $tractorIds) || $ticket->submitted_by === $user->id,
            403,
            'You do not have access to this ticket.'
        );

        $ticket->load([
            'tractor:id,no_plate,brand,model',
            'submitter:id,name',
            'assignees:id,name',
            'resolver:id,name',
            'comments.user:id,name',
            'damagePhotos',
        ]);

        return response()->json(['data' => $this->formatTicket($ticket)]);
    }

    /**
     * Request assistance from admin for a ticket.
     * Notifies admins via in-app notification and SMS.
     */
    public function requestAssistance(Request $request, Ticket $ticket)
    {
        $user = $request->user();
        $tractorIds = $this->visibleTractorIdsForTps($user);

        abort_unless(
            in_array($ticket->tractor_id, $tractorIds) || $ticket->submitted_by === $user->id,
            403,
            'You do not have access to this ticket.'
        );

        $data = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $admins = User::role(['super-admin', 'sub-admin'])
            ->where('is_active', true)
            ->get(['id', 'name', 'phone']);

        $tractorLabel = $ticket->tractor
            ? $ticket->tractor->no_plate
            : 'N/A';

        // Create in-app notifications for all admins
        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'type' => 'assistance_requested',
                'title' => 'Assistance Requested',
                'body' => "{$user->name} needs assistance on ticket \"{$ticket->subject}\" (Tractor: {$tractorLabel}): {$data['message']}",
                'data' => ['ticket_id' => $ticket->id],
            ]);
        }

        // Broadcast to admin notification channels
        $adminIds = $admins->pluck('id')->all();
        \App\Events\TicketStatusUpdated::dispatch($ticket, 'assistance_requested', $adminIds);

        // Send SMS to admins with phone numbers
        $smsService = app(M360SmsService::class);
        $smsMessage = "TANOD Alert: TPS {$user->name} requests assistance for ticket \"{$ticket->subject}\" (Tractor: {$tractorLabel}). Message: {$data['message']}";

        foreach ($admins as $admin) {
            if (! empty($admin->phone)) {
                $smsService->send($admin->phone, $smsMessage);
            }
        }

        return response()->json(['message' => 'Assistance request sent to admins.']);
    }

    /**
     * Transform a ticket model into the API response format.
     *
     * @return array<string, mixed>
     */
    private function formatTicket(Ticket $ticket): array
    {
        $latestComment = $ticket->latestComment;
        $lastActivityAt = $latestComment?->created_at ?? $ticket->created_at;

        $data = [
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'description' => $ticket->description,
            'priority' => $ticket->priority,
            'status' => $ticket->status,
            'category' => $ticket->category,
            'photo_url' => $this->storageUrl($ticket->photo_path),
            'tractor' => $ticket->tractor ? [
                'id' => $ticket->tractor->id,
                'no_plate' => $ticket->tractor->no_plate,
                'brand' => $ticket->tractor->brand,
                'model' => $ticket->tractor->model,
            ] : null,
            'submitted_by' => $ticket->submitter ? [
                'id' => $ticket->submitter->id,
                'name' => $ticket->submitter->name,
            ] : null,
            'nameplate_photo_url' => $this->storageUrl($ticket->nameplate_photo_path),
            'dashboard_photo_url' => $this->storageUrl($ticket->dashboard_photo_path),
            'damage_photos' => $ticket->relationLoaded('damagePhotos')
                ? $ticket->damagePhotos->map(fn ($dp) => [
                    'id' => $dp->id,
                    'photo_url' => $this->storageUrl($dp->photo_path),
                    'sort_order' => $dp->sort_order,
                ])->values()->all()
                : [],
            'created_at' => $ticket->created_at?->toIso8601String(),
            'last_activity_at' => $lastActivityAt?->toIso8601String(),
            'last_comment' => $latestComment ? [
                'id' => $latestComment->id,
                'ticket_id' => $latestComment->ticket_id,
                'body' => $latestComment->body,
                'attachment_url' => $this->storageUrl($latestComment->attachment_path),
                'user' => $latestComment->user ? [
                    'id' => $latestComment->user->id,
                    'name' => $latestComment->user->name,
                ] : null,
                'created_at' => $latestComment->created_at?->toIso8601String(),
            ] : null,
            'resolution_notes' => $ticket->resolution_notes,
            'resolution_photo_url' => $this->storageUrl($ticket->resolution_photo_path),
            'resolved_by' => $ticket->resolver ? [
                'id' => $ticket->resolver->id,
                'name' => $ticket->resolver->name,
            ] : null,
            'resolved_at' => $ticket->resolved_at?->toIso8601String(),
            'assignees' => $ticket->assignees?->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
            ])->values()->all() ?? [],
            'comments' => $ticket->comments?->map(fn ($c) => [
                'id' => $c->id,
                'body' => $c->body,
                'attachment_url' => $this->storageUrl($c->attachment_path),
                'user' => ['id' => $c->user->id, 'name' => $c->user->name],
                'created_at' => $c->created_at?->toIso8601String(),
            ])->all() ?? [],
        ];

        return $data;
    }

    /**
     * Build a storage URL that uses the incoming request's host.
     */
    private function storageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return request()->getSchemeAndHttpHost().'/storage/'.$path;
    }

    /**
     * Form data for creating a distribution (available tractors + FCA users).
     */
    public function distributionFormData(Request $request)
    {
        $user = $request->user();
        $tractorIds = $this->manageableTractorIdsForTps($user);

        // Tractors already actively distributed
        $distributedTractorIds = TractorDistribution::whereIn('tractor_id', $tractorIds)
            ->where('status', 'distributed')
            ->pluck('tractor_id')
            ->all();

        $tractors = Tractor::whereIn('id', $tractorIds)
            // Exclude tractors with devices stale >365 days
            ->whereHas('device', fn ($q) => $q->notStale())
            ->select('id', 'no_plate', 'brand', 'model')
            ->get()
            ->map(fn (Tractor $t) => [
                'id' => $t->id,
                'no_plate' => $t->no_plate,
                'brand' => $t->brand,
                'model' => $t->model,
                'is_distributed' => in_array($t->id, $distributedTractorIds),
            ]);

        $fcaUsers = User::role('fca')
            ->where('is_active', true)
            ->get(['id', 'name', 'email']);

        return response()->json([
            'tractors' => $tractors,
            'fca_users' => $fcaUsers,
        ]);
    }

    /**
     * Store a new tractor distribution from the TPS mobile app.
     */
    public function storeDistribution(Request $request)
    {
        $user = $request->user();
        $tractorIds = $this->manageableTractorIdsForTps($user);

        $validated = $request->validate([
            'tractor_id' => ['required', 'integer', 'exists:tractors,id'],
            'distributed_to' => ['required', 'integer', 'exists:users,id'],
            'area' => ['required', 'string', 'max:255'],
            'distribution_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'proof_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        // Distribution remains limited to the TPS user's assignment scope.
        if (! in_array((int) $validated['tractor_id'], $tractorIds)) {
            return response()->json(['message' => 'You can view this tractor, but only tractors in your TPS assignment scope can be distributed.'], 403);
        }

        // Ensure the tractor is not already actively distributed
        $alreadyDistributed = TractorDistribution::where('tractor_id', $validated['tractor_id'])
            ->where('status', 'distributed')
            ->exists();

        if ($alreadyDistributed) {
            return response()->json(['message' => 'This tractor is already distributed.'], 422);
        }

        $proofPath = null;
        if ($request->hasFile('proof_photo')) {
            $proofPath = $request->file('proof_photo')->store('distributions/proofs', 'public');
        }

        $distribution = TractorDistribution::create([
            'tractor_id' => $validated['tractor_id'],
            'tractor_ids' => [$validated['tractor_id']],
            'distributed_to' => $validated['distributed_to'],
            'distributed_by' => $user->id,
            'tps_id' => $user->id,
            'area' => $validated['area'],
            'distribution_date' => $validated['distribution_date'],
            'notes' => $validated['notes'] ?? null,
            'proof_photo' => $proofPath,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'status' => 'distributed',
        ]);

        $distribution->load(['tractor:id,no_plate,brand,model', 'distributedToUser:id,name,email']);

        return response()->json([
            'message' => 'Tractor distributed successfully.',
            'distribution' => $distribution,
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatFca(User $user): array
    {
        $profile = $user->fcaProfile;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'organization_name' => $profile?->organization_name,
            'first_name' => $profile?->first_name,
            'last_name' => $profile?->last_name,
            'phone' => $user->phone,
            'email' => $user->email,
            'province' => $profile?->province,
            'city_town' => $profile?->city_town,
            'barangay' => $profile?->barangay,
            'date_received' => $profile?->date_received?->toDateString(),
            'tractor_details' => $profile && $profile->relationLoaded('tractorDetail')
                ? $this->formatFcaTractorDetail($profile->tractorDetail)
                : null,
            'alternative_contacts' => $profile && $profile->relationLoaded('alternativeContacts')
                ? $profile->alternativeContacts
                    ->map(fn (FcaAlternativeContact $contact) => [
                        'id' => $contact->id,
                        'entry_order' => $contact->entry_order,
                        'phone' => $contact->phone,
                        'last_name' => $contact->last_name,
                        'first_name' => $contact->first_name,
                        'position' => $contact->position,
                    ])
                    ->values()
                    ->all()
                : [],
            'survey' => $this->formatFcaSurvey($profile),
            'tractor_photo_urls' => $this->formatFcaProfilePhotoUrls(
                $profile,
                FcaProfilePhoto::CATEGORY_TRACTOR,
                'tractor_photo_paths',
            ),
            'logbook_photo_urls' => $this->formatFcaProfilePhotoUrls(
                $profile,
                FcaProfilePhoto::CATEGORY_LOGBOOK,
                'logbook_photo_paths',
            ),
            'pms_records' => $profile && $profile->relationLoaded('pmsRecords')
                ? $profile->pmsRecords
                    ->map(fn (FcaPmsRecord $pmsRecord) => $this->formatFcaPmsRecord($pmsRecord))
                    ->values()
                    ->all()
                : [],
            'machine_hours' => collect($profile?->machineHours ?? [])
                ->map(fn (FcaMachineHour $machineHour) => $this->formatFcaMachineHour($machineHour))
                ->values()
                ->all(),
            'damage_records' => $profile && $profile->relationLoaded('damageRecords')
                ? $profile->damageRecords
                    ->map(fn (FcaDamageRecord $damageRecord) => $this->formatFcaDamageRecord($damageRecord))
                    ->values()
                    ->all()
                : [],
            'parking_location' => $profile ? [
                'latitude' => $profile->parking_latitude,
                'longitude' => $profile->parking_longitude,
            ] : null,
            'created_at' => $user->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatFcaDraft(FcaDraft $draft): array
    {
        return [
            'id' => $draft->id,
            'organization_name' => $draft->organization_name,
            'first_name' => $draft->first_name,
            'last_name' => $draft->last_name,
            'phone' => $draft->phone,
            'payload' => $draft->payload ?? [],
            'created_at' => $draft->created_at?->toIso8601String(),
            'updated_at' => $draft->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatFcaMachineHour(FcaMachineHour $machineHour): array
    {
        return [
            'id' => $machineHour->id,
            'entry_order' => $machineHour->entry_order,
            'date_visited' => $machineHour->date_visited?->toDateString(),
            'machine_hours' => $machineHour->machine_hours,
            'gps_status' => $machineHour->gps_status,
            'in_charge' => $this->formatFcaRelatedUser($machineHour->inChargeUser),
            'inspection_photos' => $machineHour->photos
                ->map(fn ($photo) => [
                    'id' => $photo->id,
                    'sort_order' => $photo->sort_order,
                    'url' => $this->storageUrl($photo->path),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  array<int, string>  $storedPaths
     * @return array<int, string>
     */
    private function storeUploadedFiles(mixed $files, string $directory, array &$storedPaths): array
    {
        return collect(is_array($files) ? $files : [$files])
            ->filter()
            ->values()
            ->map(function ($file) use ($directory, &$storedPaths) {
                $path = $file->store($directory, 'public');
                $storedPaths[] = $path;

                return $path;
            })
            ->all();
    }

    /**
     * @param  array<int, string>  $paths
     * @return array<int, array<string, mixed>>
     */
    private function buildProfilePhotoPayload(string $category, array $paths): array
    {
        return collect($paths)
            ->values()
            ->map(fn (string $path, int $index) => [
                'category' => $category,
                'path' => $path,
                'sort_order' => $index,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function formatFcaTractorDetail(?FcaTractorDetail $tractorDetail): ?array
    {
        if (! $tractorDetail) {
            return null;
        }

        return [
            'selected_tractor_id' => $tractorDetail->tractor_id,
            'tractor_model' => $tractorDetail->tractor_model,
            'front_loader_serial_number' => $tractorDetail->front_loader_serial_number,
            'dr_number' => $tractorDetail->dr_number,
            'rotavator_serial_number' => $tractorDetail->rotavator_serial_number,
            'serial_number' => $tractorDetail->serial_number,
            'disk_plow_serial_number' => $tractorDetail->disk_plow_serial_number,
            'engine_number' => $tractorDetail->engine_number,
            'gps_imei' => $tractorDetail->gps_imei,
            'gps_sim_number' => $tractorDetail->gps_sim_number,
            'gps_mobile_number' => $tractorDetail->gps_mobile_number,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatFcaSurvey(?UserFca $profile): array
    {
        if (! $profile || ! $profile->relationLoaded('surveyAnswers')) {
            return [
                'has_pms_schedule' => null,
                'answers' => [],
            ];
        }

        $questionFive = $profile->surveyAnswers->firstWhere('question_number', 5);

        return [
            'has_pms_schedule' => $questionFive?->boolean_answer,
            'answers' => $profile->surveyAnswers
                ->filter(fn (FcaSurveyAnswer $answer) => $answer->question_number !== 5)
                ->map(fn (FcaSurveyAnswer $answer) => [
                    'id' => $answer->id,
                    'question_number' => $answer->question_number,
                    'entry_order' => $answer->entry_order,
                    'answer_text' => $answer->answer_text,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function formatFcaProfilePhotoUrls(?UserFca $profile, string $category, string $legacyColumn): array
    {
        if (! $profile) {
            return [];
        }

        if ($profile->relationLoaded('profilePhotos')) {
            return $profile->profilePhotos
                ->where('category', $category)
                ->map(fn (FcaProfilePhoto $photo) => $this->storageUrl($photo->path))
                ->values()
                ->all();
        }

        return collect($profile->{$legacyColumn} ?? [])
            ->map(fn (string $path) => $this->storageUrl($path))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatFcaPmsRecord(FcaPmsRecord $pmsRecord): array
    {
        return [
            'id' => $pmsRecord->id,
            'column_order' => $pmsRecord->column_order,
            'actual_hours' => $pmsRecord->actual_hours,
            'performed_by' => $pmsRecord->performed_by,
            'categories' => $pmsRecord->categories->pluck('category')->values()->all(),
            'in_charge' => $this->formatFcaRelatedUser($pmsRecord->inChargeUser),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function formatFcaDamageRecord(FcaDamageRecord $damageRecord): array
    {
        return [
            'id' => $damageRecord->id,
            'entry_order' => $damageRecord->entry_order,
            'unit' => $damageRecord->unit,
            'operational_after_repair' => $damageRecord->operational_after_repair,
            'date_damaged' => $damageRecord->date_damaged?->toDateString(),
            'date_repaired' => $damageRecord->date_repaired?->toDateString(),
            'nature_of_problem' => $damageRecord->nature_of_problem,
            'cause_of_damage' => $damageRecord->cause_of_damage,
            'parts_replaced' => $damageRecord->parts_replaced,
            'in_charge' => $this->formatFcaRelatedUser($damageRecord->inChargeUser),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function formatFcaRelatedUser(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
        ];
    }
}
