<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CustomerService;
use App\Http\Requests\CustomerValidation;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Customer",
 *     description="Customer-specific APIs"
 * )
 */
class CustomerController extends Controller
{
    protected $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    /**
     * @OA\Get(
     *     path="/api/customer",
     *     tags={"Customer"},
     *     summary="Get logged-in customer details",
     *     description="Returns the authenticated customer's details",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Authenticated user details",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="User retrieved successfully."),
     *             @OA\Property(property="user", ref="#/components/schemas/User")
     *         )
     *     )
     * )
     */
    public function me()
    {
        $user = Auth::user();
        if (!$user || !$user->is_active) {
            return response()->json([
                'status' => 'not_found',
                'message' => 'User not found.'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'User retrieved successfully.',
            'user' => $this->formatUserData($user)
        ]);
    }



    /**
     * @OA\Delete(
     *     path="/api/customer",
     *     tags={"Customer"},
     *     summary="Delete customer account (Soft Delete)",
     *     description="Soft deletes the authenticated customer's account.",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="User account deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Account deleted successfully. You can contact support to restore your account within 30 days.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Account deletion failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Failed to delete account. Please try again or contact support.")
     *         )
     *     )
     * )
     */
    public function destroy()
    {
        $result = $this->customerService->deleteAccount(Auth::id());

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

    /**
     * @OA\Put(
     * path="/api/customer/profile",
     * tags={"Customer"},
     * summary="Update customer profile",
     * description="Update customer profile with primary and secondary contact information. Handles both Form 1 (Primary Contact) and Form 2 (Secondary Contact) data from mobile screens.",
     * security={{"bearerAuth":{}}},
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="first_name", type="string", example="John"),
     *         @OA\Property(property="last_name", type="string", example="Doe"),
     *         @OA\Property(property="email", type="string", example="john@example.com"),
     *         @OA\Property(property="country_code", type="string", example="+65"),
     *         @OA\Property(property="phone", type="string", example="12345678"),
     *         @OA\Property(property="dob", type="string", format="date", example="1990-01-01"),
     *         @OA\Property(property="passport_nric_fin_number", type="string", example="1234"),
     *         @OA\Property(property="secondary_first_name", type="string", example="Jane"),
     *         @OA\Property(property="secondary_last_name", type="string", example="Doe"),
     *         @OA\Property(property="secondary_email", type="string", example="jane@example.com"),
     *         @OA\Property(property="secondary_country_code", type="string", example="+65"),
     *         @OA\Property(property="secondary_phone", type="string", example="87654321")
     *     )
     * ),
     * @OA\Response(
     *     response=200,
     *     description="Profile updated successfully",
     *     @OA\JsonContent(
     *         @OA\Property(property="status", type="string", example="success"),
     *         @OA\Property(property="message", type="string", example="Profile updated successfully"),
     *         @OA\Property(property="user", ref="#/components/schemas/User")
     *     )
     * ),
     * @OA\Response(
     *     response=422,
     *     description="Validation error",
     *     @OA\JsonContent(
     *         @OA\Property(property="status", type="string", example="error"),
     *         @OA\Property(property="message", type="string", example="Validation failed"),
     *         @OA\Property(property="errors", type="object")
     *     )
     * )
     * )
     */
    public function updateProfile(Request $request)
    {
        // Debug logging
        \Log::info('API updateProfile called', [
            'user_id' => Auth::id(),
            'request_data' => $request->all()
        ]);
        
        // Use secure method that excludes email/phone updates
        // Email/phone should only be updated after successful OTP verification
        $result = $this->customerService->updateCustomerProfileBasicInfo(Auth::id(), $request->all(), 'api');

        \Log::info('API updateProfile service result', [
            'result_status' => $result['status'],
            'result_user_id' => isset($result['user']) ? $result['user']->id : null,
            'result_user_first_name' => isset($result['user']) ? $result['user']->first_name : null
        ]);

        if ($result['status'] === 'success') {
            return response()->json([
                'status' => 'success',
                'message' => $result['message'],
                'user' => $this->formatUserData($result['user'])
            ]);
        }

        // Handle validation errors
        if (isset($result['errors'])) {
            return response()->json([
                'status' => 'error',
                'message' => $result['message'],
                'errors' => $result['errors']
            ], 422);
        }

        return response()->json([
            'status' => 'error',
            'message' => $result['message']
        ], 400);
    }

    /**
     * @OA\Post(
     * path="/api/customer/address",
     * tags={"Customer"},
     * summary="Create customer address",
     * description="Create a new address for the customer (Form 2 from mobile screens)",
     * security={{"bearerAuth":{}}},
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="address_type_id", type="integer", example=1),
     *         @OA\Property(property="label", type="string", example="Home"),
     *         @OA\Property(property="country", type="string", example="SG"),
     *         @OA\Property(property="postal_code", type="string", example="123456"),
     *         @OA\Property(property="address_line_1", type="string", example="123 Main Street"),
     *         @OA\Property(property="address_line_2", type="string", example="Apt 4B")
     *     )
     * ),
     * @OA\Response(
     *     response=201,
     *     description="Address created successfully",
     *     @OA\JsonContent(
     *         @OA\Property(property="status", type="string", example="success"),
     *         @OA\Property(property="message", type="string", example="Address created successfully"),
     *         @OA\Property(property="address", type="object")
     *     )
     * )
     * )
     */
    public function createAddress(Request $request)
    {
        // Validate the request
        $validator = \Validator::make($request->all(), [
            'address_type_id' => 'required|integer|exists:address_types,id',
            'label' => 'required|string|max:255',
            'country' => 'required|string|max:2',
            'postal_code' => 'required|string|max:10',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'sometimes|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->customerService->saveCustomerAddress(Auth::id(), $validator->validated());

        if ($result['status'] === 'success') {
            return response()->json([
                'status' => 'success',
                'message' => $result['message'],
                'address' => $result['address']
            ], 201);
        }

        return response()->json([
            'status' => 'error',
            'message' => $result['message']
        ], 400);
    }

    /**
     * @OA\Put(
     * path="/api/customer/address/{id}",
     * tags={"Customer"},
     * summary="Update customer address",
     * description="Update an existing address for the customer",
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     * ),
     * @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *         @OA\Property(property="address_type_id", type="integer", example=1),
     *         @OA\Property(property="label", type="string", example="Home"),
     *         @OA\Property(property="country", type="string", example="SG"),
     *         @OA\Property(property="postal_code", type="string", example="123456"),
     *         @OA\Property(property="address_line_1", type="string", example="123 Main Street"),
     *         @OA\Property(property="address_line_2", type="string", example="Apt 4B")
     *     )
     * ),
     * @OA\Response(
     *     response=200,
     *     description="Address updated successfully",
     *     @OA\JsonContent(
     *         @OA\Property(property="status", type="string", example="success"),
     *         @OA\Property(property="message", type="string", example="Address updated successfully"),
     *         @OA\Property(property="address", type="object")
     *     )
     * )
     * )
     */
    public function updateAddress(Request $request, $id)
    {
        // Validate the request
        $validator = \Validator::make($request->all(), [
            'address_type_id' => 'required|integer|exists:address_types,id',
            'label' => 'required|string|max:255',
            'country' => 'required|string|max:2',
            'postal_code' => 'required|string|max:10',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'sometimes|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $addressData = $validator->validated();
        $addressData['id'] = $id;

        $result = $this->customerService->saveCustomerAddress(Auth::id(), $addressData);

        if ($result['status'] === 'success') {
            return response()->json([
                'status' => 'success',
                'message' => $result['message'],
                'address' => $result['address']
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $result['message']
        ], 400);
    }

    /**
     * @OA\Get(
     * path="/api/customer/addresses",
     * tags={"Customer"},
     * summary="Get customer addresses",
     * description="Get all addresses for the authenticated customer",
     * security={{"bearerAuth":{}}},
     * @OA\Response(
     *     response=200,
     *     description="Addresses retrieved successfully",
     *     @OA\JsonContent(
     *         @OA\Property(property="status", type="string", example="success"),
     *         @OA\Property(property="message", type="string", example="Addresses retrieved successfully."),
     *         @OA\Property(property="addresses", type="array", @OA\Items(type="object"))
     *     )
     * )
     * )
     */
    public function getAddresses()
    {
        $userId = Auth::id();
        $result = $this->customerService->getCustomerAddresses($userId);

        if ($result['status'] === 'success') {
            return response()->json([
                'status' => 'success',
                'message' => 'Addresses retrieved successfully.',
                'addresses' => $result['addresses']
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => $result['message']
        ], 400);
    }

    /**
     * @OA\Delete(
     * path="/api/customer/address/{id}",
     * tags={"Customer"},
     * summary="Delete customer address",
     * description="Delete an address for the authenticated customer",
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     *     response=200,
     *     description="Address deleted successfully",
     *     @OA\JsonContent(
     *         @OA\Property(property="status", type="string", example="success"),
     *         @OA\Property(property="message", type="string", example="Address deleted successfully")
     *     )
     * ),
     * @OA\Response(
     *     response=400,
     *     description="Failed to delete address",
     *     @OA\JsonContent(
     *         @OA\Property(property="status", type="string", example="error"),
     *         @OA\Property(property="message", type="string", example="Failed to delete address")
     *     )
     * )
     * )
     */
    public function deleteAddress($id)
    {
        $result = $this->customerService->deleteCustomerAddress(Auth::id(), $id);

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

    /**
     * @OA\Get(
     * path="/api/customer/address-types",
     * tags={"Customer"},
     * summary="Get available address types",
     * description="Get all available address types for dropdown selection",
     * security={{"bearerAuth":{}}},
     * @OA\Response(
     *     response=200,
     *     description="Address types retrieved successfully",
     *     @OA\JsonContent(
     *         @OA\Property(property="status", type="string", example="success"),
     *         @OA\Property(property="message", type="string", example="Address types retrieved successfully."),
     *         @OA\Property(property="address_types", type="array", @OA\Items(type="object"))
     *     )
     * ),
     * @OA\Response(
     *     response=400,
     *     description="Failed to get address types",
     *     @OA\JsonContent(
     *         @OA\Property(property="status", type="string", example="error"),
     *         @OA\Property(property="message", type="string", example="Failed to get address types.")
     *     )
     * )
     * )
     */
    public function getAddressTypes()
    {
        try {
            $addressTypes = \App\Models\AddressType::active()->ordered()->get();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Address types retrieved successfully.',
                'address_types' => $addressTypes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get address types: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Format user data for API responses with separated country_code and phone
     */
    private function formatUserData($user)
    {
        // Build addresses array (keeps API consistent and avoids exposing internal fields)
        $addresses = $user->addresses()
            ->get()
            ->map(function ($a) {
                return [
                    'id' => $a->id,
                    'address_type_id' => $a->address_type_id,
                    'label' => $a->label,
                    'country' => $a->country,
                    'postal_code' => $a->postal_code,
                    // Use API-friendly keys
                    'address_line_1' => $a->address_line1,
                    'address_line_2' => $a->address_line2,
                    'is_billing_address' => (bool) $a->is_billing_address,
                    'is_shipping_address' => (bool) $a->is_shipping_address,
                    'full_address' => $a->full_address,
                ];
            })
            ->values()
            ->toArray();

        $userData = [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'name' => $user->name,
            'email' => $user->email,
            'country_code' => $user->country_code,
            'phone' => $user->phone,
            'dob' => $user->dob,
            'passport_nric_fin_number' => $user->passport_nric_fin_number,
            'image' => $user->image,
            'secondary_first_name' => $user->secondary_first_name,
            'secondary_last_name' => $user->secondary_last_name,
            'secondary_email' => $user->secondary_email,
            'secondary_phone' => $user->secondary_phone,
            'secondary_country_code' => $user->secondary_country_code,
            'referal_code' => $user->referal_code,
            'is_active' => $user->is_active,
            'phone_verified_at' => $user->phone_verified_at,
            'email_verified_at' => $user->email_verified_at,
            'addresses' => $addresses,
        ];

        // Remove null values to keep response clean (preserves empty arrays)
        return array_filter($userData, function($value) {
            return $value !== null;
        });
    }
}
