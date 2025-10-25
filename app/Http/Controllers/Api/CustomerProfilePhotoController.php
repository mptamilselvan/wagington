<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Services\ImageService;

/**
 * @OA\Tag(
 *     name="Customer Profile Photo",
 *     description="Customer profile photo management APIs"
 * )
 */
class CustomerProfilePhotoController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/customer/profile-photo",
     *     tags={"Customer Profile Photo"},
     *     summary="Upload customer profile photo",
     *     description="Upload a profile photo for the authenticated customer",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(
     *                     property="profile_photo",
     *                     type="string",
     *                     format="binary",
     *                     description="Profile photo file (JPEG, PNG, JPG, GIF, max 2MB)"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Profile photo uploaded successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Profile photo uploaded successfully"),
     *             @OA\Property(property="image_url", type="string", example="https://wagginton-staging.sgp1.digitaloceanspaces.com/customers/123/profile_1642678901.jpg")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="Validation failed"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="User not authenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="error"),
     *             @OA\Property(property="message", type="string", example="An error occurred while uploading the profile photo")
     *         )
     *     )
     * )
     */
    public function upload(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Validate the uploaded file
            $validator = Validator::make($request->all(), [
                'profile_photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $file = $request->file('profile_photo');
            
            // Delete old profile photo if exists
            if ($user->image) {
                ImageService::deleteCustomerProfileImage($user->image, $user->id);
            }

            // Upload new profile photo
            $imagePath = ImageService::uploadCustomerProfileImage($file, $user->id);

            if (!$imagePath) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to upload profile photo'
                ], 500);
            }

            // Update user's image path
            $user->update(['image' => $imagePath]);

            // Get the full URL
            $imageUrl = ImageService::getImageUrl($imagePath);


            return response()->json([
                'status' => 'success',
                'message' => 'Profile photo uploaded successfully',
                'image_url' => $imageUrl
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while uploading the profile photo'
            ], 500);
        }
    }

    // /**
    //  * @OA\Delete(
    //  *     path="/api/customer/profile-photo",
    //  *     tags={"Customer Profile Photo"},
    //  *     summary="Delete customer profile photo",
    //  *     description="Delete the profile photo of the authenticated customer",
    //  *     security={{"bearerAuth":{}}},
    //  *     @OA\Response(
    //  *         response=200,
    //  *         description="Profile photo deleted successfully",
    //  *         @OA\JsonContent(
    //  *             @OA\Property(property="status", type="string", example="success"),
    //  *             @OA\Property(property="message", type="string", example="Profile photo deleted successfully")
    //  *         )
    //  *     ),
    //  *     @OA\Response(
    //  *         response=401,
    //  *         description="Unauthorized"
    //  *     )
    //  * )
    //  */
    // public function delete()
    // {
    //     try {
    //         $user = Auth::user();
            
    //         if (!$user) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'User not authenticated'
    //             ], 401);
    //         }

    //         if (!$user->image) {
    //             return response()->json([
    //                 'status' => 'success',
    //                 'message' => 'No profile photo to delete'
    //             ]);
    //         }

    //         // Delete the profile photo
    //         $deleted = ImageService::deleteCustomerProfileImage($user->image, $user->id);

    //         if ($deleted) {
    //             // Clear the image path from user record
    //             $user->update(['image' => null]);


    //             return response()->json([
    //                 'status' => 'success',
    //                 'message' => 'Profile photo deleted successfully'
    //             ]);
    //         } else {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'Failed to delete profile photo'
    //             ], 500);
    //         }

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'An error occurred while deleting the profile photo'
    //         ], 500);
    //     }
    // }

    /**
     * @OA\Get(
     *     path="/api/customer/profile-photo",
     *     tags={"Customer Profile Photo"},
     *     summary="Get customer profile photo URL",
     *     description="Get the profile photo URL of the authenticated customer",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Profile photo URL retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="message", type="string", example="Profile photo URL retrieved successfully"),
     *             @OA\Property(property="image_url", type="string", example="https://wagginton-staging.sgp1.digitaloceanspaces.com/customers/123/profile_1642678901.jpg"),
     *             @OA\Property(property="has_image", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function get()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ], 401);
            }

            $imageUrl = null;
            $hasImage = false;

            if ($user->image) {
                $imageUrl = ImageService::getImageUrl($user->image, null, $user->first_name, $user->last_name);
                $hasImage = true;
            } else {
                // Return initials data if no image
                $imageUrl = ImageService::getImageUrl(null, null, $user->first_name, $user->last_name);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Profile photo URL retrieved successfully',
                'image_url' => $imageUrl,
                'has_image' => $hasImage
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while retrieving the profile photo'
            ], 500);
        }
    }
}