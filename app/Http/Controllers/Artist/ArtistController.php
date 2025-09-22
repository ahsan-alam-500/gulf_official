<?php

namespace App\Http\Controllers\Artist;

use App\Http\Controllers\Controller;
use App\Models\Artist;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ArtistController extends Controller
{
    /**
     * Display the authenticated artist profile.
     */
    public function index()
    {
        try {
            $artist = Auth::user()
                ->artist()
                ->with(['photos', 'songs'])
                ->first();

            if (!$artist) {
                return response()->json([
                    'error' => 'Please complete your artist profile first.'
                ], 404);
            }

            $artist->email = Auth::user()->email;
            // convert image urls
            $artist->image_url = $artist->image ? url('public/'.Storage::url($artist->image)) : null;
            $artist->cover_photo_url = $artist->cover_photo ? url('public/'.Storage::url($artist->cover_photo)) : null;
            // returning response
            return response()->json([
                'data' => $artist,
                'success' => true,
                'status' => 200,
                'message' => 'Artist profile fetched successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'An error occurred while fetching the artist profile.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created artist profile.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'        => 'required|string|max:255',
                'bio'         => 'nullable|string',
                'city'        => 'nullable|string|max:255',
                'image'       => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
                'cover_photo' => 'nullable|image|mimes:jpg,jpeg,png|max:4096',
            ]);

            $data = [
                'user_id' => Auth::id(),
                'name'    => $validated['name'],
                'bio'     => $validated['bio'] ?? null,
                'city'    => $validated['city'] ?? null,
            ];

            if ($request->hasFile('image')) {
                $data['image'] = $request->file('image')->store('artist/images', 'public');
            }
            if ($request->hasFile('cover_photo')) {
                $data['cover_photo'] = $request->file('cover_photo')->store('artist/covers', 'public');
            }

            $artist = Artist::create($data);

            return response()->json([
                'data'    => $artist,
                'success' => true,
                'status'  => 201,
                'message' => 'Artist profile created successfully.',
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'error'   => 'Validation failed',
                'message' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'An error occurred while creating the artist profile.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Display the specified artist.
     */
    public function show(Artist $artist)
    {
        try {
            $artist->load(['photos', 'songs', 'genres']);
            $artist->image_url = $artist->image ? Storage::url($artist->image) : null;
            $artist->cover_photo_url = $artist->cover_photo ? Storage::url($artist->cover_photo) : null;

            return response()->json([
                'data'    => $artist,
                'success' => true,
                'status'  => 200,
                'message' => 'Artist profile fetched successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'An error occurred while fetching the artist.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified artist profile.
     */
public function updateProfile(Request $request, $id)
{

    $artist = Artist::with('user')->findOrFail($id); // lookup by artist.id

    try {
        // Ownership check
        if ($artist->user_id !== Auth::id()) {
            return response()->json([
                'message' => 'Unauthorized to update this profile.'
            ], 403);
        }

        // Validate input
        $validated = $request->validate([
            'name'        => 'sometimes|required|string|max:255',
            'email'       => 'sometimes|required|email|max:255', // belongs to users
            'genre'       => 'nullable|string',
            'bio'         => 'nullable|string',
            'city'        => 'nullable|string|max:255',
            'image'       => 'nullable|string',       // base64
            'cover_photo' => 'nullable|string',       // base64
        ]);

            return response()->json([
        "message" => "passing id is " .$validated
    ]);

        // Update user info (from relation)
        if (isset($validated['name']) || isset($validated['email'])) {
            $artist->user->update([
                'name'  => $validated['name'] ?? $artist->user->name,
                'email' => $validated['email'] ?? $artist->user->email,
            ]);
        }

        // Remove `email` so it's not filled into Artist table
        unset($validated['email']);

        // Update artist fields
        $artist->fill($validated);

        // Handle Base64 images
        if (!empty($validated['image'])) {
            if ($artist->image) Storage::disk('public')->delete($artist->image);
            $artist->image = $this->saveBase64Image($validated['image'], 'artist/images');
        }

        if (!empty($validated['cover_photo'])) {
            if ($artist->cover_photo) Storage::disk('public')->delete($artist->cover_photo);
            $artist->cover_photo = $this->saveBase64Image($validated['cover_photo'], 'artist/covers');
        }

        $artist->save();

        // Return with URLs
        $artist->refresh();
        $artist->image_url = $artist->image ? url(Storage::url($artist->image)) : null;
        $artist->cover_photo_url = $artist->cover_photo ? url(Storage::url($artist->cover_photo)) : null;

        return response()->json([
            'data'    => $artist,
            'success' => true,
            'status'  => 200,
            'message' => 'Artist profile updated successfully.',
        ]);
    } catch (ValidationException $e) {
        return response()->json([
            'error'   => 'Validation failed',
            'message' => $e->errors(),
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'error'   => 'An error occurred while updating the artist profile.',
            'message' => $e->getMessage(),
        ], 500);
    }
}


    /**
     * Save Base64 encoded image to storage and return path
     */
    private function saveBase64Image($base64Image, $folder)
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $base64Image, $type)) {
            $image = substr($base64Image, strpos($base64Image, ',') + 1);
            $extension = strtolower($type[1]); // jpg, png, gif
        } else {
            throw new \Exception('Invalid image data');
        }

        $image = str_replace(' ', '+', $image);
        $imageData = base64_decode($image);

        if ($imageData === false) {
            throw new \Exception('Base64 decode failed');
        }

        $fileName = $folder . '/' . uniqid() . '.' . $extension;
        Storage::disk('public')->put($fileName, $imageData);

        return $fileName;
    }


    /**
     * Remove the specified artist profile.
     */
    public function destroy(Artist $artist)
    {
        try {
            if ($artist->user_id !== Auth::id()) {
                return response()->json([
                    'error' => 'Unauthorized to delete this profile.'
                ], 403);
            }

            if ($artist->image) {
                Storage::disk('public')->delete($artist->image);
            }
            if ($artist->cover_photo) {
                Storage::disk('public')->delete($artist->cover_photo);
            }

            $artist->delete();

            return response()->json([
                'success' => true,
                'status'  => 200,
                'message' => 'Artist profile deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error'   => 'An error occurred while deleting the artist profile.',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
