<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\FilterUserRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index(FilterUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $search = $validated['search'] ?? null;
        $isBanned = array_key_exists('is_banned', $validated) && $validated['is_banned'] !== null
            ? (bool) $validated['is_banned']
            : null;
        $userType = $validated['user_type'] ?? null;
        $getAll = array_key_exists('get_all', $validated) && $validated['get_all'] !== null
            ? (bool) $validated['get_all']
            : false;

        $page = (int) ($validated['page'] ?? 1);
        $limit = (int) ($validated['limit'] ?? 10);

        $query = User::query()->orderByDesc('created_at');

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'ILIKE', "%{$search}%")
                    ->orWhere('email', 'ILIKE', "%{$search}%");
            });
        }

        if ($isBanned !== null) {
            $query->where('is_banned', $isBanned);
        }

        if ($userType !== null && $userType !== '') {
            $query->where('user_type', $userType);
        }

        if ($getAll) {
            $users = $query->get();
            $users = $this->withProfileImageUrls($users);

            return response()->json([
                'data' => $users,
                'message' => 'All users retrieved successfully',
            ]);
        }

        $paginator = $query->paginate($limit, ['*'], 'page', $page);
        $items = $this->withProfileImageUrls(collect($paginator->items()));

        return response()->json([
            'data' => $items,
            'pagination' => [
                'total' => $paginator->total(),
                'page' => $paginator->currentPage(),
                'limit' => $paginator->perPage(),
            ],
            'message' => 'Users retrieved successfully',
        ]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();

        $profileImageUrl = $data['profile_image_url'] ?? null;

        if ($request->hasFile('profile_image')) {
            $path = Storage::disk('s3')->putFile('users/profile', $request->file('profile_image'));
            $profileImageUrl = $path;
        }

        $data['profile_image_url'] = $profileImageUrl;
        unset($data['profile_image']);

        if (! array_key_exists('is_banned', $data)) {
            $data['is_banned'] = false;
        }

        $user = User::create($data);

        return response()->json([
            'data' => $user,
            'message' => 'User created successfully',
        ], 201);
    }

    public function show(string $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'message' => "User with ID '{$id}' not found",
            ], 404);
        }

        $userWithUrl = $this->withProfileImageUrls(collect([$user]))->first();

        return response()->json([
            'data' => $userWithUrl,
            'message' => "User retrieved by ID {$id} successfully",
        ]);
    }

    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'message' => "User with ID '{$id}' not found",
            ], 404);
        }

        $data = $request->validated();

        $profileImageUrl = $user->profile_image_url;

        if ($request->hasFile('profile_image')) {
            $path = Storage::disk('s3')->putFile('users/profile', $request->file('profile_image'));

            if ($profileImageUrl) {
                Storage::disk('s3')->delete($profileImageUrl);
            }

            $profileImageUrl = $path;
        } elseif (array_key_exists('profile_image_url', $data)) {
            $profileImageUrl = $data['profile_image_url'];
        }

        $data['profile_image_url'] = $profileImageUrl;
        unset($data['profile_image']);

        $user->fill($data);
        $user->save();

        return response()->json([
            'data' => $this->withProfileImageUrls(collect([$user]))->first(),
            'message' => 'User updated successfully',
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'message' => "User with ID '{$id}' not found",
            ], 404);
        }

        if ($user->profile_image_url) {
            Storage::disk('s3')->delete($user->profile_image_url);
        }

        $user->delete();

        return response()->json([
            'message' => "User with ID '{$id}' has been successfully deleted",
        ]);
    }

    private function withProfileImageUrls($users)
    {
        $ttl = (int) env('S3_URL_TTL_SECONDS', 3600);
        $expiresAt = Carbon::now()->addSeconds($ttl);

        return $users->map(function (User $user) use ($expiresAt) {
            if ($user->profile_image_url) {
                $user->profile_image_url = Storage::disk('s3')->temporaryUrl(
                    $user->profile_image_url,
                    $expiresAt,
                );
            }

            return $user;
        });
    }
}
