<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * @OA\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="API Token",
 *     in="header"
 * )
 */

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="Sanctum Auth API Documentation",
 *      description="API documentation for Register, Login, Logout",
 *      @OA\Contact(email="you@example.com")
 * )
 *
 * @OA\Server(
 *      url=L5_SWAGGER_CONST_HOST,
 *      description="Local server"
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *      path="/api/register",
     *      summary="Register a new user",
     *      tags={"Auth"},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"name","email","password","password_confirmation"},
     *              @OA\Property(property="name", type="string"),
     *              @OA\Property(property="email", type="string", format="email"),
     *              @OA\Property(property="password", type="string", format="password"),
     *              @OA\Property(property="password_confirmation", type="string", format="password")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="User registered successfully",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="user", type="object"),
     *              @OA\Property(property="token", type="string")
     *          )
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Validation Error",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="message", type="string"),
     *              @OA\Property(property="errors", type="object")
     *          )
     *      )
     * )
     */
public function register(Request $request)
{
    $f = $request->validate([
        'name' => 'required|string|max:255|unique:users,name',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|confirmed|min:4',
        'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
    ]);

    $avatarPath = null;
    if ($request->hasFile('avatar')) {
        $avatarPath = $request->file('avatar')->store('avatars', 'public'); // saved in storage/app/public/avatars
    }

    $user = User::create([
        'name' => $f['name'],
        'email' => $f['email'],
        'password' => bcrypt($f['password']),
        'role' => $request->role ?? 'user',
        'avatar' => $avatarPath
    ]);

    $token = $user->createToken($user->name)->plainTextToken;

    $userData = $user->toArray();
    if ($user->avatar) {
       $userData['avatar_url'] = asset('storage/' . $user->avatar);
    } else {
       $userData['avatar_url'] = null;
     }

    return response()->json([
        'user' => $user,
        'token' => $token
    ]);
}


    /**
 * @OA\Get(
 *      path="/api/users",
 *      summary="Get all users",
 *      tags={"User"},
 *      security={{"sanctum":{}}},
 *      @OA\Response(
 *          response=200,
 *          description="List of users",
 *          @OA\JsonContent(
 *              type="object",
 *              @OA\Property(
 *                  property="users",
 *                  type="array",
 *                  @OA\Items(
 *                      @OA\Property(property="id", type="integer"),
 *                      @OA\Property(property="name", type="string"),
 *                      @OA\Property(property="email", type="string"),
 *                      @OA\Property(property="created_at", type="string", format="date-time")
 *                  )
 *              )
 *          )
 *      ),
 *      @OA\Response(
 *          response=401,
 *          description="Unauthenticated"
 *      )
 * )
 */
public function getAllUsers(Request $request)
{
    $user = $request->user();

    if ($user->role !== 'admin') {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    $users = User::select('id', 'name', 'email', 'role', 'avatar', 'created_at')->get();

// Add avatar_url for each user
$users = $users->map(function($u){
    $u->avatar_url = $u->avatar ? asset('storage/' . $u->avatar) : null;
    return $u;
});

return response()->json(['users' => $users]);

}


/**
 * @OA\Get(
 *      path="/api/users/{id}",
 *      summary="Get user by ID",
 *      tags={"User"},
 *      security={{"sanctum":{}}},
 *      @OA\Parameter(
 *          name="id",
 *          in="path",
 *          required=true,
 *          @OA\Schema(type="integer")
 *      ),
 *      @OA\Response(
 *          response=200,
 *          description="User found",
 *          @OA\JsonContent(
 *              type="object",
 *              @OA\Property(property="user", type="object",
 *                  @OA\Property(property="id", type="integer"),
 *                  @OA\Property(property="name", type="string"),
 *                  @OA\Property(property="email", type="string"),
 *                  @OA\Property(property="created_at", type="string", format="date-time")
 *              )
 *          )
 *      ),
 *      @OA\Response(
 *          response=404,
 *          description="User not found"
 *      ),
 *      @OA\Response(
 *          response=401,
 *          description="Unauthenticated"
 *      )
 * )
 */
public function getUserById(Request $request, $id)
{
    $user = $request->user();

    if ($user->role !== 'admin') {
        return response()->json([
            'message' => 'Unauthorized'
        ], 403);
    }

    $targetUser = User::select('id', 'name', 'email', 'created_at')->find($id);

    if (!$targetUser) {
        return response()->json([
            'message' => 'User not found'
        ], 404);
    }

    return response()->json([
        'user' => $targetUser
    ]);
}



    /**
     * @OA\Post(
     *      path="/api/login",
     *      summary="Login user",
     *      tags={"Auth"},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"email","password"},
     *              @OA\Property(property="email", type="string", format="email"),
     *              @OA\Property(property="password", type="string", format="password")
     *          )
     *      ),
     *      @OA\Response(response=200, description="User logged in successfully"),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */


    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
        return response()->json([
            'message' => 'User not found'
        ], 404);
    }

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'The provided credentials are not correct'
            ], 401);
        }

        $token = $user->createToken($user->name)->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }


    /**
     * @OA\Post(
     *      path="/api/logout",
     *      summary="Logout user",
     *      tags={"Auth"},
     *      security={{"sanctum":{}}},
     *      @OA\Response(response=200, description="User logged out successfully"),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function logout(Request $request)
{
    $user = $request->user();

    if (!$user) {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    // delete only current token (safer)
    $user->currentAccessToken()->delete();

    return response()->json([
        'message' => 'You are logged out'
    ]);
}
/**
     * @OA\Put(
     *      path="/api/users/{id}",
     *      summary="Update an existing user",
     *      tags={"User"},
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="name", type="string"),
     *              @OA\Property(property="email", type="string", format="email"),
     *              @OA\Property(property="password", type="string", format="password"),
     *              @OA\Property(property="password_confirmation", type="string", format="password"),
     *              @OA\Property(property="role", type="string", enum={"admin","user"})
     *          )
     *      ),
     *      @OA\Response(response=200, description="User updated successfully"),
     *      @OA\Response(response=404, description="User not found"),
     *      @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function updateUser(Request $request, $id)
    {
        $admin = $request->user();
        if ($admin->role !== 'admin') return response()->json(['message' => 'Unauthorized'], 403);

        $user = User::find($id);
        if (!$user) return response()->json(['message' => 'User not found'], 404);

        $f = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:users,name,' . $id,
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'password' => 'nullable|confirmed|min:4',
            'role' => 'sometimes|required|string|in:admin,user'
        ]);

        if (isset($f['name'])) $user->name = $f['name'];
        if (isset($f['email'])) $user->email = $f['email'];
        if (isset($f['password'])) $user->password = bcrypt($f['password']);
        if (isset($f['role'])) $user->role = $f['role'];

       // Handle avatar upload
    if ($request->hasFile('avatar')) {
        // Optional: delete old avatar if exists
        if ($user->avatar && file_exists(storage_path('app/public/' . $user->avatar))) {
            unlink(storage_path('app/public/' . $user->avatar));
        }

        $user->avatar = $request->file('avatar')->store('avatars', 'public');
    }

    $user->save();

    return response()->json($user);
    }

    // ------------------- Delete User -------------------
    /**
     * @OA\Delete(
     *      path="/api/users/{id}",
     *      summary="Delete a user",
     *      tags={"User"},
     *      security={{"sanctum":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(response=200, description="User deleted successfully"),
     *      @OA\Response(response=404, description="User not found"),
     *      @OA\Response(response=403, description="Unauthorized")
     * )
     */
    public function deleteUser(Request $request, $id)
    {
        $admin = $request->user();
        if ($admin->role !== 'admin') return response()->json(['message' => 'Unauthorized'], 403);

        $user = User::find($id);
        if (!$user) return response()->json(['message' => 'User not found'], 404);
        // Delete avatar from storage if exists
    if ($user->avatar && file_exists(storage_path('app/public/' . $user->avatar))) {
        unlink(storage_path('app/public/' . $user->avatar));
    }
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }


    // ------------------- UPDATE PROFILE -------------------
    /**
     * @OA\Put(
     *      path="/api/user/profile",
     *      summary="Update own profile",
     *      tags={"Profile"},
     *      security={{"sanctum":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="name", type="string"),
     *              @OA\Property(property="email", type="string", format="email"),
     *              @OA\Property(property="password", type="string", format="password"),
     *              @OA\Property(property="password_confirmation", type="string", format="password"),
     *              @OA\Property(property="avatar", type="string", format="binary")
     *          )
     *      ),
     *      @OA\Response(response=200, description="Profile updated successfully"),
     *      @OA\Response(response=401, description="Unauthenticated")
     * )
     */


public function updateProfile(Request $request)
{
    $user = $request->user(); // logged-in user

    $f = $request->validate([
        'name' => 'sometimes|required|string|max:255|unique:users,name,' . $user->id,
        'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
        'password' => 'nullable|confirmed|min:4',
    ]);
    $p=false;
    if (isset($f['name'])) $user->name = $f['name'];
    if (isset($f['email'])) $user->email = $f['email'];
    if (isset($f['password'])) $user->password = bcrypt($f['password']);

    // Handle avatar upload
    if ($request->hasFile('avatar')) {
        // Optional: delete old avatar if exists
        if ($user->avatar && file_exists(storage_path('app/public/' . $user->avatar))) {
            unlink(storage_path('app/public/' . $user->avatar));
        }

        $user->avatar = $request->file('avatar')->store('avatars', 'public');
    }

    $user->save();

    // Return updated user with avatar URL
    return response()->json([
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'avatar_url' => $user->avatar ? asset('storage/' . $user->avatar) : null,
    ]);
}



}
