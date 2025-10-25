<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pet;
use App\Models\Species;
use Auth;
use App\Services\PetService;
use App\Services\BloodTestRecordService;
use App\Services\DewormingRecordService;
use App\Services\MedicalHistoryRecordService;
use App\Services\DietaryPreferencesService;
use App\Services\MedicationSupplementService;
use App\Services\VaccinationRecordService;
use App\Traits\PetTrait;
use Carbon\Carbon;
use App\Rules\PetRules;
use App\Rules\BreedBelongsToSpecies;


/**
 * @OA\Tag(
 *     name="Pets",
 *     description="Pets-specific APIs"
 * )
 */
class PetProfileController extends Controller
{
    use PetTrait;
    protected $petService,$bloodTestRecordService;

    public function __construct(Request $request,PetService $petService, BloodTestRecordService $bloodTestRecordService,DewormingRecordService $dewormingRecordService,MedicalHistoryRecordService $medicalHistoryRecordService,DietaryPreferencesService $dietaryPreferencesService,MedicationSupplementService $medicationSupplementService,VaccinationRecordService $vaccinationRecordService)
    {
        $this->petService = $petService;
        $this->bloodTestRecordService = $bloodTestRecordService;
        $this->dewormingRecordService = $dewormingRecordService;
        $this->medicalHistoryRecordService = $medicalHistoryRecordService;
        $this->dietaryPreferencesService = $dietaryPreferencesService;
        $this->medicationSupplementService = $medicationSupplementService;
        $this->vaccinationRecordService = $vaccinationRecordService;

        $routeName = $request->route()->getName();

        if ($request->has('pet_id')) {
            $this->pet = Pet::where('id', $request->input('pet_id'))
                ->where('user_id', Auth::id())
                ->first();

            if (!$this->pet) {
                abort(response()->json([
                    'status' => 'forbidden',
                    'message' => 'This pet does not belong to the logged-in user.'
                ], 403));
            }
        } else if ($routeName === 'pets.update') {
            $this->pet = Pet::where('id', $request->route('id'))
                ->where('user_id', Auth::id())
                ->first();

            if (!$this->pet) {
                abort(response()->json([
                    'status' => 'forbidden',
                    'message' => 'This pet does not belong to the logged-in user.'
                ], 403));
            }
        }
    }
     /**
     * @OA\Get(
     * path="/api/pets",
     * tags={"Pets"},
     * summary="Get Pet Profile",
     * description="Get all pet profile for the authenticated customer",
     * security={{"bearerAuth":{}}},
     * @OA\Response(
     *     response=200,
     *     description="Pet profile retrieved successfully",
     *     @OA\JsonContent(
     *         @OA\Property(property="status", type="string", example="success"),
     *         @OA\Property(property="pets", type="array", @OA\Items(type="object"))
     *     )
     * )
     * )
     */
    public function index()
    {
        $user = Auth::user();
            
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not authenticated'
            ], 401);
        }

        $data = $this->petService->getPetProfile($user_id = $user->id);
        if ($data['status'] === 'success') {
            return response()->json([
                'status' => 'success',
                'data' => $data['data']
            ]);
        }

        return response()->json([
            'status' => $data['status'],
            'message' => $data['message']
        ], 400);
    }

    /**
 * @OA\Post(
 *     path="/api/pets",
 *     summary="Create a pet",
 *     tags={"Pets"},
 *     security={{"bearerAuth":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={
 *                     "name","gender","species_id","breed_id","color",
 *                     "date_of_birth","sterilisation_status"
 *                 },
 *                 @OA\Property(property="name", type="string", maxLength=50, example="Buddy"),
 *                 @OA\Property(property="profile_image", type="string", format="binary", nullable=true, description="Pet profile image",nullable=true),
 *                 @OA\Property(property="gender", type="string", enum={"male","female","other"}, example="male"),
 *                 @OA\Property(property="species_id", type="integer", example=2),
 *                 @OA\Property(property="breed_id", type="integer", example=10),
 *                 @OA\Property(property="color", type="string", maxLength=50, example="Brown"),
 *                 @OA\Property(property="date_of_birth", type="string", format="date", example=""),
 *                 @OA\Property(property="sterilisation_status", type="boolean", example=true),
 *                 @OA\Property(property="microchip_number", type="string", nullable=true, example="MC123456789"),
 *                 @OA\Property(property="length_cm", type="number", format="float", nullable=true, example=45.5),
 *                 @OA\Property(property="height_cm", type="number", format="float", nullable=true, example=30.2),
 *                 @OA\Property(property="weight_kg", type="number", format="float", nullable=true, example=12.4),
 *                 @OA\Property(property="avs_license_number", type="string", nullable=true, example="AVS-987654"),
 *                 @OA\Property(property="date_expiry", type="string", format="date", nullable=true, example=""),
 *                 @OA\Property(property="document", type="string", format="binary", nullable=true, description="other document"),
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Pet created successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="success"),
 *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
 *         )
 *     ),
 *     @OA\Response(response=400, description="Invalid request or file type"),
 *     @OA\Response(response=401, description="Unauthorized")
 * )
 */
    public function store(Request $request)
    {
        $user = Auth::user();
            
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not authenticated'
            ], 401);
        }

        $request['user_id'] = $user->id;
        $request['created_by'] = $user->id;
        $request['updated_by'] = $user->id;

        $dogId = Species::whereRaw("LOWER(name) = ?", ['dog'])->value('id');
        

        $validator = \Validator::make($request->all(), [
            'user_id'   => 'required|exists:users,id',
            'name'      => 'required|string|max:50',
            'gender'    => 'required',
            'species_id'=> 'required|exists:species,id',
            'breed_id'  => ['required','exists:breeds,id',new BreedBelongsToSpecies($request->species_id)],
            'color'     => 'required|string|max:50',
            'date_of_birth' => 'required|date',
            'sterilisation_status' => 'required',
            'profile_image' => 'nullable|mimes:jpg,jpeg,png|max:2048',
            'microchip_number' => "nullable|string|max:50|required_if:species_id,{$dogId}",
            'length_cm' => 'nullable|numeric',
            'height_cm' => 'nullable|numeric',
            'weight_kg' => 'nullable|numeric',
            'avs_license_number' => 'nullable|string|max:50',
            'date_expiry' => 'nullable|date',
            'document'       => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            ],
            ['microchip_number.required_if' => "The microchip number field is required when species is Dogs."]
        );

        if ($validator->fails()) {
            return response()->json(["status"=> "error",
            "message"=> "Validation failed",'errors' => $validator->errors()], 422);
        }

        // $validated = $request->validate(PetRules::rules());

        $data = $request->only([
            'user_id',
            'name',
            'gender',
            'species_id',
            'breed_id',
            'color',
            'date_of_birth',
            'sterilisation_status',
            'profile_image',
            'created_by',
            'updated_by'
        ]);

        $pet_data = $request->only([
            'microchip_number', 'length_cm', 'height_cm', 'weight_kg', 'avs_license_number', 'date_expiry','document',
        ]);

        $dateExpiry = Carbon::parse($request->date_expiry);

        $result = $this->petService->savePet('',$data,$pet_data);

        if ($result['status'] === 'success') {
            return response()->json([
                'status' => 'success',
                'data' => $result['pet']
            ]);
        }

        return response()->json([
            'status' => $result['status'],
            'message' => $result['message']
        ], 400);
        
    }

   /**
 * @OA\Post(
 *     path="/api/pets/{id}",
 *     summary="Update a pet",
 *     tags={"Pets"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *          name="id",
 *          in="path",
 *          required=true,
 *          description="ID of the pet to update",
 *          @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                 required={
 *                     "name","gender","species_id","breed_id","color","date_of_birth","sterilisation_status"
 *                 },
 *                 @OA\Property(property="name", type="string", maxLength=50, example="Buddy"),
 *                 @OA\Property(property="profile_image", type="string", format="binary", nullable=true, description="Pet profile image",nullable=true),
 *                 @OA\Property(property="gender", type="string", enum={"male","female","other"}, example="male"),
 *                 @OA\Property(property="species_id", type="integer", example=2),
 *                 @OA\Property(property="breed_id", type="integer", example=10),
 *                 @OA\Property(property="color", type="string", maxLength=50, example="Brown"),
 *                 @OA\Property(property="date_of_birth", type="string", format="date", example=""),
 *                 @OA\Property(property="sterilisation_status", type="boolean", example=true),
 *                 @OA\Property(property="microchip_number", type="string", nullable=true, example="MC123456789"),
 *                 @OA\Property(property="length_cm", type="number", format="float", nullable=true, example=45.5),
 *                 @OA\Property(property="height_cm", type="number", format="float", nullable=true, example=30.2),
 *                 @OA\Property(property="weight_kg", type="number", format="float", nullable=true, example=12.4),
 *                 @OA\Property(property="avs_license_number", type="string", nullable=true, example="AVS-987654"),
 *                 @OA\Property(property="date_expiry", type="string", format="date", nullable=true, example=""),
 *                 @OA\Property(property="document", type="string", format="binary", nullable=true, description="other document"),
 *             )
 *         )
 *     ),
 *      @OA\Response(
 *      response=200,
 *      description="Update Pet successfully",
 *          @OA\JsonContent(
 *              @OA\Property(property="status", type="string", example="success"),
 *              @OA\Property(property="data", type="array", @OA\Items(type="object"))
 *          )
 *      ),
 *     @OA\Response(response=422, description="Validation error"),
 *     @OA\Response(response=401, description="Unauthorized"),
 *     @OA\Response(response=404, description="Pet not found")
 * )
 */

    public function update(Request $request, $id)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not authenticated'
            ], 401);
        }

        // Attach user data
        $request->merge([
            'user_id'    => $user->id,
            'updated_by' => $user->id,
        ]);

        $dogId = Species::whereRaw("LOWER(name) = ?", ['dog'])->value('id');

        // Validation
        $validator = \Validator::make($request->all(), [
            'user_id'   => 'required|exists:users,id',
            'name'      => 'required|string|max:50',
            'gender'    => 'required',
            'species_id'=> 'required|exists:species,id',
            'breed_id'  => ['required','exists:breeds,id',new BreedBelongsToSpecies($request->species_id)],
            'color'     => 'required|string|max:50',
            'date_of_birth' => 'required|date',
            'sterilisation_status' => 'required',
            'profile_image' => 'nullable|mimes:jpg,jpeg,png|max:2048',
            'microchip_number' => 'nullable|string|max:50|required_if:specie_id,$dogId"',
            'length_cm' => 'nullable|numeric',
            'height_cm' => 'nullable|numeric',
            'weight_kg' => 'nullable|numeric',
            'avs_license_number' => 'nullable|string|max:50',
            'date_expiry' => 'nullable|date',
            'document'   => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json(["status"=> "error",
            "message"=> "Validation failed",'errors' => $validator->errors()], 422);
        }

        // Data
        $data = $request->only([
            'user_id', 'name', 'gender', 'species_id', 'breed_id',
            'color', 'date_of_birth', 'sterilisation_status',
            'profile_image', 'created_by', 'updated_by'
        ]);

        $pet_data = $request->only([
            'microchip_number', 'length_cm', 'height_cm', 'weight_kg',
            'avs_license_number', 'date_expiry', 'document',
        ]);

        // Save via service
        $result = $this->petService->savePet($id, $data, $pet_data);

        if ($result['status'] === 'success') {
            return response()->json([
                'status' => 'success',
                'result' => $result['pet']
            ]);
        }

        return response()->json([
            'status' => $result['status'],
            'message' => $result['message']
        ], 400);
    }

    /**
     * @OA\Delete(
     * path="/api/pets/{id}",
     * tags={"Pets"},
     * summary="Delete Pet",
     * security={{"bearerAuth":{}}},
     * description="Delete an pet for the authenticated customer",
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     *         response=200,
     *         description="Pet deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Pet deleted")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Pet deletion failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Failed to delete Pet. Please try again or contact support.")
     *         )
     *     )
     * )
     */

    public function destroy($id)
    {
        $result = $this->petService->deletePet($id);

        if ($result['status'] === 'success') {
            return response()->json([
                'status' => 'success',
                'message' => $result['message']
            ]);
        }

        return response()->json([
            'status' => $result['status'],
            'message' => $result['message']
        ], 400);
    }

    // vaccination-records
/**
     * @OA\Post(
     *     path="/api/vaccination-records",
     *     summary="Create a pet's vaccination record",
     *     tags={"Pets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"pet_id"},
     *                 @OA\Property(property="pet_id", type="integer", example="", description="Enter pet ID"),
     *                 @OA\Property(property="vaccination_id", type="integer", example="", description="Enter Vaccination ID",nullable=true),
     *                 @OA\Property(property="date", type="string", format="date", description="Not a future date",nullable=true,example=""),
     *                 @OA\Property(property="document", type="string", format="binary", description="vaccination Document",nullable=true),
     *                 @OA\Property(property="notes", type="string", description="Notes",nullable=true,example=""),
     *                 @OA\Property(property="cannot_vaccinate", type="boolean", enum={"true","false"}, description="My pet cannot have any vaccinations.",nullable=true,example = false),
     *             )
     *         )
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="Update Vaccination record successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *          )
     *      ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     * )
     *
     * @OA\Post(
     *     path="/api/vaccination-records/{id}",
     *     summary="Update a pet's vaccination record",
     *     tags={"Pets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the vaccination record to update",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"pet_id"},
     *                 @OA\Property(property="pet_id", type="integer", example="", description="Enter pet ID"),
     *                 @OA\Property(property="vaccination_id", type="integer", example=2, description="Enter Vaccination ID",nullable=true,example=""),
     *                 @OA\Property(property="date", type="string", format="date", description="Not a future date",nullable=true,example=""),
     *                 @OA\Property(property="document", type="string", format="binary", description="vaccination Document",nullable=true),
     *                 @OA\Property(property="notes", type="string", description="Notes",nullable=true,example=""),
     *                 @OA\Property(property="cannot_vaccinate", type="boolean", description="My pet cannot have any vaccinations.",nullable=true,example=false),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *      response=200,
     *      description="Update Vaccination record successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *          )
     *      ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="vaccination record not found")
     * )
     */
    public function saveVaccinationRecord(Request $request,$id = null)
    {
        try {
            // Add common fields
            $request['customer_id'] = Auth::user()->id;
            $request['updated_by'] = Auth::user()->id;
            $request['created_by'] = Auth::user()->id;

            // Validate
            $validator = \Validator::make($request->all(), \App\Rules\VaccinationRecordRules::rules($request['cannot_vaccinate'],$id));

            if ($validator->fails()) {
                return response()->json(["status"=> "error",
                "message"=> "Validation failed",'errors' => $validator->errors()], 422);
            }

            $request['cannot_vaccinate'] = filter_var($request['cannot_vaccinate'], FILTER_VALIDATE_BOOLEAN);
            
            if($request['cannot_vaccinate'])
            {
                $request->merge([
                    'date' => null,
                    'vaccination_id' => null,
                    'notes' => null,
                    'document' => null,
                ]);
            }

            // Service handles both create & update            
            $result = $this->vaccinationRecordService->saveVaccinationRecord($id, $request->all());

            if ($result['status'] === 'success') {
                return response()->json([
                    'status' => 'success',
                    'result' => $result['vaccination_record']
                ], 201);
            }

            return response()->json([
                'status' => 'error',
                'message' => $result['message']
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save record: ' . $e->getMessage()
            ], 400);
        }
    }


    /**
     * @OA\Delete(
     *     path="/api/vaccination-records/{id}",
     *     tags={"Pets"},
     *     summary="Delete a pet's vaccination record (Soft Delete)",
     *     description="Delete an pet's vaccination record for the authenticated customer",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="vaccination record deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="vaccination record deleted")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="vaccination record deletion failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Failed to delete vaccination record. Please try again or contact support.")
     *         )
     *     )
     * )
     */

    public function deleteVaccinationRecord($id)
    {
        try {
            $result = $this->vaccinationRecordService->deleteVaccinationRecord($id);

            if ($result['status'] === 'success') {
                return response()->json([
                    'status' => 'success',
                    'message' => $result['message']
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => $result['message']
            ], 400);
        }
        catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get record: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/download/vaccination-records/{id}",
     *     tags={"Pets"},
     *     summary="Download a pet's vaccination record",
     *     description="Download a pet's vaccination record in PDF format for the authenticated customer.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the vaccination record to download",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vaccination record PDF file",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="Download file"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Vaccination record download failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Failed to download vaccination record. Please try again or contact support.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Vaccination record not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Vaccination record not found.")
     *         )
     *     )
     * )
     */


    public function downloadVaccinationRecord($id)
    {
        try {
            
            $result = $this->vaccinationRecordService->downloadVaccinationRecord($id);

            if ($result['status'] === 'success') {
                $pdfBytes = $result['pdfBytes'];

                return response($pdfBytes, 200, [
                    'Content-Type'        => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="vaccination_record.pdf"',
                ]);
            }else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'PDF generation failed.'
                ], 400);
            }
        }
        catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to download record: ' . $e->getMessage()
            ], 400);
        }
    }

    // Blood Test
    /**
     * @OA\Post(
     *     path="/api/blood-test-records",
     *     summary="Create a pet's blood test record",
     *     tags={"Pets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"blood_test_id","status","pet_id","date"},
     *                 @OA\Property(property="pet_id", type="integer", example=2, description="Enter pet ID",example=""),
     *                 @OA\Property(property="blood_test_id", type="integer", example=2, description="Enter blood test ID",example=""),
     *                 @OA\Property(property="date", type="string", format="date", example="2025-09-04", description="Not a future date",example=""),
     *                 @OA\Property(property="status", type="string", enum={"positive","negative"}, example="positive"),
     * *                 @OA\Property(property="notes", type="string", description="Notes",nullable=true,example=""),
     *                 @OA\Property(property="document", type="string", format="binary", description="Blood Test Document",nullable=true),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="Blood Test record created successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *          )
     *      ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     * )
     *
     * @OA\Post(
     *     path="/api/blood-test-records/{id}",
     *     summary="Update a pet's blood test record",
     *     tags={"Pets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the blood test record to update",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"blood_test_id","status","pet_id","date"},
     *                 @OA\Property(property="pet_id", type="integer", example=2, description="Enter pet ID",example=""),
     *                 @OA\Property(property="blood_test_id", type="integer", example=2, description="Enter blood test ID",example=""),
     *                 @OA\Property(property="date", type="string", format="date", description="Not a future date",example=""),
     *                 @OA\Property(property="status", type="string", enum={"positive","negative"}, example="positive"),
     * *                 @OA\Property(property="notes", type="string", description="Notes",nullable=true,example=""),
     *                 @OA\Property(property="document", type="string", format="binary", description="Blood Test Document"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="Blood Test record updated successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *          )
     *      ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *      @OA\Response(response=404, description="Blood test record not found")
     * )
     */
    public function saveBloodTest(Request $request,$id = null)
    {
        try {
            // Validate
            $validator = \Validator::make($request->all(), \App\Rules\BloodTestRecordRules::rules());

            if ($validator->fails()) {
                return response()->json(["status"=> "error",
                "message"=> "Validation failed",'errors' => $validator->errors()], 422);
            }

            // Add common fields
            $request['customer_id'] = Auth::user()->id;
            $request['updated_by'] = Auth::user()->id;
            $request['created_by'] = Auth::user()->id;

            // Service handles both create & update

            
            $result = $this->bloodTestRecordService->saveBloodTestRecord($id, $request->all());

            if ($result['status'] === 'success') {
                return response()->json([
                    'status' => 'success',
                    'result' => $result['blood_test_record']
                ], 201);
            }

            return response()->json([
                'status' => 'error',
                'message' => $result['message']
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save record: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Delete(
     * path="/api/blood-test-records/{id}",
     * tags={"Pets"},
     * summary="Delete a pet's blood test record",
     * description="Delete an pet's blood test record for the authenticated customer",
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     * ),
     * security={{"bearerAuth":{}}},
     * @OA\Response(
     *         response=200,
     *         description="Blood test record deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Blood test deleted")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Blood test deletion failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Failed to delete Blood test. Please try again or contact support.")
     *         )
     *     )
     * )
     */

    public function deleteBloodTest($id)
    {
        $result = $this->bloodTestRecordService->deleteBloodTestRecord($id);

        if ($result['status'] === 'success') {
            return response()->json([
                'status' => 'success',
                'message' => $result['message']
            ]);
        }

        return response()->json([
            'status' => $result['status'],
            'message' => $result['message']
        ], 400);
    }

    /**
     * @OA\Post(
     * path="/api/download/blood-test-records/{id}",
     * tags={"Pets"},
     * summary="Download a pet's blood test record",
     * description="Download an pet's blood test record for the authenticated customer",
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     * ),
     * security={{"bearerAuth":{}}},
     * @OA\Response(
     *         response=200,
     *         description="Blood test record PDF file",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="Download file"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Blood test download failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Failed to download Blood test. Please try again or contact support.")
     *         )
     *     ),
     *   @OA\Response(
     *         response=404,
     *         description="Blood test record not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Blood test record not found.")
     *         )
     *     )
     * )
     */


    public function downloadBloodTestRecord($id)
    {
        try {
            
            $result = $this->bloodTestRecordService->downloadBloodTestRecord($id);

            if ($result['status'] === 'success') {
                $pdfBytes = $result['pdfBytes'];

                return response($pdfBytes, 200, [
                    'Content-Type'        => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="blood-test_record.pdf"',
                ]);
            }else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'PDF generation failed.'
                ], 400);
            }
        }
        catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to download record: ' . $e->getMessage()
            ], 400);
        }
    }

    
    /**
     * @OA\Post(
     *     path="/api/deworming-records",
     *     summary="Create a pet's deworming record",
     *     tags={"Pets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"brand_name","pet_id","date"},
     *                 @OA\Property(property="pet_id", type="integer", example="", description="Enter pet ID"),
     *                 @OA\Property(property="date", type="string", format="date", example="", description="Not a future date"),
     *                 @OA\Property(property="brand_name", type="string", example="", description="Enter brand name"),
     *                 @OA\Property(property="notes", type="string", nullable=true, example=""),
     *                 @OA\Property(property="document", type="string", format="binary", description="Deworming Record Document",nullable=true),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="Deworming Record created successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *          )
     *      ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     * )
     *
     * @OA\Post(
     *     path="/api/deworming-records/{id}",
     *     summary="Update a pet's deworming record",
     *     tags={"Pets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the deworming record to update",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"brand_name","pet_id","date"},
     *                 @OA\Property(property="pet_id", type="integer", example="", description="Enter pet ID"),
     *                 @OA\Property(property="date", type="string", format="date", example="", description="Not a future date"),
     *                 @OA\Property(property="brand_name", type="string", example="", description="Enter brand name"),
     *                 @OA\Property(property="notes", type="string", nullable=true, example=""),
     *                 @OA\Property(property="document", type="string", format="binary", description="Deworming Record Document",nullable=true),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *          response=200,
     *          description="Deworming Record updated successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *          )
     *      ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     * @OA\Response(response=404, description="Deworming record not found")
     * )
     */
    public function saveDeworming(Request $request,$id = null)
    {
        try {
            // Validate
            $validator = \Validator::make($request->all(), \App\Rules\DewormingRecordRules::rules($id));

            if ($validator->fails()) {
                return response()->json(["status"=> "error",
                "message"=> "Validation failed",'errors' => $validator->errors()], 422);
            }

            // Add common fields
            $request['customer_id'] = Auth::user()->id;
            $request['updated_by'] = Auth::user()->id;
            $request['created_by'] = Auth::user()->id;

            // Service handles both create & update
            $result = $this->dewormingRecordService->saveDewormingRecord($id, $request->all());

            if ($result['status'] === 'success') {
                return response()->json([
                    'status' => 'success',
                    'result' => $result['deworming_record']
                ], 201);
            }

            return response()->json([
                'status' => 'error',
                'message' => $result['message']
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save record: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Delete(
     * path="/api/deworming-records/{id}",
     * tags={"Pets"},
     * summary="Delete a pet's deworming record",
     * description="Delete an pet's deworming record for the authenticated customer",
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     *         response=200,
     *         description="Deworming record deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="deworming record deleted")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Deworming record deletion failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Failed to delete deworming record. Please try again or contact support.")
     *         )
     *     )
     * )
     */

    public function deleteDeworming($id)
    {
        $result = $this->dewormingRecordService->deleteDewormingRecord($id);

        if ($result['status'] === 'success') {
            return response()->json([
                'status' => 'success',
                'message' => $result['message']
            ]);
        }

        return response()->json([
            'status' => $result['status'],
            'message' => $result['message']
        ], 400);
    }

    /**
     * @OA\Post(
     * path="/api/download/deworming-records/{id}",
     * tags={"Pets"},
     * summary="Download a pet's Deworming record",
     * description="Download an pet's Deworming record for the authenticated customer",
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     * ),
     * security={{"bearerAuth":{}}},
     * @OA\Response(
     *         response=200,
     *         description="Deworming record PDF file",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="Download file"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Deworming download failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Failed to download Deworming. Please try again or contact support.")
     *         )
     *     ),
     *      @OA\Response(
     *         response=404,
     *         description="Deworming record not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Deworming record not found.")
     *         )
     *     )
     * 
     * )
     */


    public function downloadDeworming($id)
    {
        try {
            
            $result = $this->dewormingRecordService->downloadDewormingRecord($id);

            if ($result['status'] === 'success') {
                $pdfBytes = $result['pdfBytes'];

                return response($pdfBytes, 200, [
                    'Content-Type'        => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="Deworming_record.pdf"',
                ]);
            }else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'PDF generation failed.'
                ], 400);
            }
        }
        catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to download record: ' . $e->getMessage()
            ], 400);
        }
    }

// medical-history-records
/**
     * @OA\Post(
     *     path="/api/medical-history-records",
     *     summary="Create a pet's Medical History record",
     *     tags={"Pets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name","pet_id"},
     *                 @OA\Property(property="pet_id", type="integer", example="", description="Enter pet ID"),
     *                 @OA\Property(property="name", type="string", example="", description="Enter brand name"),
     *                 @OA\Property(property="notes", type="string", nullable=true, example=""),
     *                 @OA\Property(property="document", type="string", format="binary", description="Medical History Record Document",nullable=true),
     *             )
     *         )
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="Created Medical History successfully",
     *              @OA\JsonContent(
     *                  @OA\Property(property="status", type="string", example="success"),
     *                  @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *              )
     *      ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     * )
     *
     * @OA\Post(
     *     path="/api/medical-history-records/{id}",
     *     summary="Update a pet's Medical History record",
     *     tags={"Pets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the Medical History record to update",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name","pet_id"},
     *                 @OA\Property(property="pet_id", type="integer", example="", description="Enter pet ID"),
     *                 @OA\Property(property="name", type="string", example="", description="Enter brand name"),
     *                 @OA\Property(property="notes", type="string", nullable=true, example=""),
     *                 @OA\Property(property="document", type="string", format="binary", description="Medical History Record Document",nullable=true),
     *             )
     *         )
     *     ),
     *      @OA\Response(
     *      response=200,
     *      description="Update Medical History successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *          )
     *      ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Medical History not found")
     * )
     */
    public function saveMedicalHistoryRecord(Request $request,$id = null)
    {
        try {
            // Validate
            $validator = \Validator::make($request->all(), \App\Rules\MedicalHistoryRecordRules::rules($id));

            if ($validator->fails()) {
                return response()->json(["status"=> "error",
                "message"=> "Validation failed",'errors' => $validator->errors()], 422);
            }

            // Add common fields
            $request['customer_id'] = Auth::user()->id;
            $request['updated_by'] = Auth::user()->id;
            $request['created_by'] = Auth::user()->id;

            // Service handles both create & update
            $result = $this->medicalHistoryRecordService->saveMedicalHistoryRecord($id, $request->all());

            if ($result['status'] === 'success') {
                return response()->json([
                    'status' => 'success',
                    'result' => $result['medical_history_record']
                ], 201);
            }

            return response()->json([
                'status' => 'error',
                'message' => $result['message']
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save record: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Delete(
     * path="/api/medical-history-records/{id}",
     * tags={"Pets"},
     * summary="Delete a pet's Medical History record",
     * description="Delete an pet's Medical History record for the authenticated customer",
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     *         response=200,
     *         description="Medical History record deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Medical History record deleted")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Medical History record deletion failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Failed to delete Medical History record. Please try again or contact support.")
     *         )
     *     )
     * )
     */

    public function deleteMedicalHistoryRecord($id)
    {
        $result = $this->medicalHistoryRecordService->deleteMedicalHistoryRecord($id);

        if ($result['status'] === 'success') {
            return response()->json([
                'status' => 'success',
                'message' => $result['message']
            ]);
        }

        return response()->json([
            'status' => $result['status'],
            'message' => $result['message']
        ], 400);
    }

/**
     * @OA\Post(
     * path="/api/download/medical-history-records/{id}",
     * tags={"Pets"},
     * summary="Download a pet's Medical History record",
     * description="Download an pet's Medical History record for the authenticated customer",
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     * ),
     * security={{"bearerAuth":{}}},
     * @OA\Response(
     *         response=200,
     *         description="Medical History record PDF file",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="Download file"),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Deworming download failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Failed to download Deworming. Please try again or contact support.")
     *         )
     *     ),
     *      @OA\Response(
     *         response=404,
     *         description="Medical History record not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Medical History record not found.")
     *         )
     *     )
     * 
     * )
     */


    public function downloadMedicalHistoryRecord($id)
    {
        try {
            
            $result = $this->medicalHistoryRecordService->downloadMedicalHistoryRecord($id);

            if ($result['status'] === 'success') {
                $pdfBytes = $result['pdfBytes'];

                return response($pdfBytes, 200, [
                    'Content-Type'        => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="medical-history-records.pdf"',
                ]);
            }else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'PDF generation failed.'
                ], 400);
            }
        }
        catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to download record: ' . $e->getMessage()
            ], 400);
        }
    }

// dietary-preferences-records
/**
     * @OA\Post(
     *     path="/api/dietary-preferences-records",
     *     summary="Create a pet's Dietary Preferences record",
     *     tags={"Pets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"feed_time","pet_id","allergies"},
     *                 @OA\Property(property="pet_id", type="integer", example="", description="Enter pet ID"),
     *                 @OA\Property(property="feed_time", type="string", enum={"morning","evening"}, example="morning"),
     *                 @OA\Property(property="allergies", type="string", example="",description="Enter allergies"),
     * *                  @OA\Property(property="is_active",type="boolean",description="Status of the record, true = active, false = inactive",example=true,default=true),
     *                 @OA\Property(property="notes", type="string", nullable=true, example=""),
     *             )
     *         )
     *     ),
     * @OA\Response(
     *          response=200,
     *          description="Created Dietary Preferences successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *          )
     *      ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     * )
     *
     * @OA\Post(
     *     path="/api/dietary-preferences-records/{id}",
     *     summary="Update a pet's Dietary Preferences record",
     *     tags={"Pets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the Dietary Preferences record to update",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"feed_time","pet_id","allergies"},
     *                 @OA\Property(property="pet_id", type="integer", example="", description="Enter pet ID"),
     *                 @OA\Property(property="feed_time", type="string", enum={"morning","evening"}, example="morning"),
     *                 @OA\Property(property="allergies", type="string", example="",description="Enter allergies"),
     * *                  @OA\Property(property="is_active",type="boolean",description="Status of the record, true = active, false = inactive",example=true,default=true),
     *                 @OA\Property(property="notes", type="string", nullable=true, example=""),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *      response=200,
     *      description="Update Dietary Preferences successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *          )
     *      ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Dietary Preferences not found")
     * )
     */
    public function saveDietaryPreferences(Request $request,$id = null)
    {
        try {
            // Validate
            $validator = \Validator::make($request->all(), \App\Rules\DietaryPreferencesRules::rules($id));

            if ($validator->fails()) {
                return response()->json(["status"=> "error",
                "message"=> "Validation failed",'errors' => $validator->errors()], 422);
            }

            // Add common fields
            $request['customer_id'] = Auth::user()->id;
            $request['updated_by'] = Auth::user()->id;
            $request['created_by'] = Auth::user()->id;

            // Service handles both create & update
            $result = $this->dietaryPreferencesService->saveDietaryPreferences($id, $request->all());

            if ($result['status'] === 'success') {
                return response()->json([
                    'status' => 'success',
                    'result' => $result['dietary_preferences_record']
                ], 201);
            }

            return response()->json([
                'status' => 'error',
                'message' => $result['message']
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save record: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Delete(
     * path="/api/dietary-preferences-records/{id}",
     * tags={"Pets"},
     * summary="Delete a pet's Dietary Preferences record record",
     * description="Delete an pet's Dietary Preferences record record for the authenticated customer",
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     *         response=200,
     *         description="Dietary Preferences deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Dietary Preferences deleted")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Dietary Preferences deletion failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Failed to delete Dietary Preferences. Please try again or contact support.")
     *         )
     *     )
     * )
     */

    public function deleteDietaryPreferences($id)
    {
        $result = $this->dietaryPreferencesService->deleteDietaryPreferences($id);

        if ($result['status'] === 'success') {
            return response()->json([
                'status' => 'success',
                'message' => $result['message']
            ]);
        }

        return response()->json([
            'status' => $result['status'],
            'message' => $result['message']
        ], 400);
    }

    // medication-supplements-records
/**
     * @OA\Post(
     *     path="/api/medication-supplements-records",
     *     summary="Create a pet's MedicationSupplement record",
     *     tags={"Pets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"type","pet_id","name","dosage"},
     *                 @OA\Property(property="pet_id", type="integer", example="", description="Enter pet ID"),
     *                 @OA\Property(property="type", type="string", enum={"medications","supplements"}, example="morning"),
     *                 @OA\Property(property="name", type="string", example="",description="Enter name"),
     *                 @OA\Property(property="dosage", type="string", example="",description="Enter dosage"),
     *                  @OA\Property(property="is_active",type="boolean",description="Status of the record, true = active, false = inactive",example=true,default=true),
     *                 @OA\Property(property="notes", type="string", nullable=true, example=""),
     *             )
     *         )
     *     ),
     *  @OA\Response(
     *          response=200,
     *          description="Update Medication Supplement successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *          )
     *      ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     * )
     *
     * @OA\Post(
     *     path="/api/medication-supplements-records/{id}",
     *     summary="Update a pet's MedicationSupplement record",
     *     tags={"Pets"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the MedicationSupplement record to update",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                required={"type","pet_id","name","dosage"},
     *                 @OA\Property(property="pet_id", type="integer", example="", description="Enter pet ID"),
     *                 @OA\Property(property="type", type="string", enum={"medications","supplements"}, example="morning"),
     *                 @OA\Property(property="name", type="string", example="",description="Enter name"),
     *                 @OA\Property(property="dosage", type="string", example="",description="Enter dosage"),
     * *                  @OA\Property(property="is_active",type="boolean",description="Status of the record, true = active, false = inactive",example=true,default=true),
     *                 @OA\Property(property="notes", type="string", nullable=true, example=""),
     *             )
     *         )
     *     ),
     * @OA\Response(
     *      response=200,
     *      description="Updated Medication Supplement successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="status", type="string", example="success"),
     *              @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *          )
     *      ),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=401, description="Unauthorized"),
     *     @OA\Response(response=404, description="Medication Supplement not found")
     * )
     */
    public function saveMedicationSupplement(Request $request,$id = null)
    {
        try {
            // Validate
            $validator = \Validator::make($request->all(), \App\Rules\MedicationSupplementRules::rules($id));

            if ($validator->fails()) {
                return response()->json(["status"=> "error",
                "message"=> "Validation failed",'errors' => $validator->errors()], 422);
            }

            // Add common fields
            $request['customer_id'] = Auth::user()->id;
            $request['updated_by'] = Auth::user()->id;
            $request['created_by'] = Auth::user()->id;

            // Service handles both create & update
            $result = $this->medicationSupplementService->saveMedicationSupplement($id, $request->all());

            if ($result['status'] === 'success') {
                return response()->json([
                    'status' => 'success',
                    'result' => $result['medication_supplement']
                ], 201);
            }

            return response()->json([
                'status' => 'error',
                'message' => $result['message']
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save record: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * @OA\Delete(
     * path="/api/medication-supplements-records/{id}",
     * tags={"Pets"},
     * summary="Delete a pet's MedicationSupplement recors record",
     * description="Delete an pet's MedicationSupplement record record for the authenticated customer",
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     *         response=200,
     *         description="Medication Supplement deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Medication Supplement deleted")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Medication Supplement deletion failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Failed to delete Medication Supplement. Please try again or contact support.")
     *         )
     *     )
     * )
     */

    public function deleteMedicationSupplement($id)
    {
        $result = $this->medicationSupplementService->deleteMedicationSupplement($id);

        if ($result['status'] === 'success') {
            return response()->json([
                'status' => 'success',
                'message' => $result['message']
            ]);
        }

        return response()->json([
            'status' => $result['status'],
            'message' => $result['message']
        ], 400);
    }


}
