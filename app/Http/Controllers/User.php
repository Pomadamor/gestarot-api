<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;

use Log;
use Illuminate\Http\Request;
use \Str;

class User extends Controller
{
    /**
     * Returns a user.
     */
    public function get($id = NULL, Request $request) {

        // Check if the logged in user is the same as the one we want to modify
        // Load the api_token from the database
        $api_token = \DB::table('api_tokens')
            ->where('value', $request->header('api_token'))
            ->get();

        // If no id is specified in the route, guess it from the api_token
        // (Get the logged in user)
        if (is_null($id)) {
            Log::debug('Loading user from token');
            // Get the id from the token
            if (count( $api_token ) >= 0 ) {
                $id = $api_token[0]->user_id;
            }
        }

        $user = \App\User::where('id', $id)->first();
        if (!$user) {
            return [
                "status" => "error", 
                "error" => "User $id not found"
            ];
        }
        
        // Check if token belongs to the user with the specified id
        if (count( $api_token ) == 0 || $api_token[0]->user_id != $id ) {
            Log::debug('user id and token user id same');
            return response()
                ->json([
                    'status' => 'error',
                    'error' => 'You are not allowed to get another user'
                ]);
        }

        return [
            'status' => "ok",
            "user" => [
                'id' => $user->id,
                'email' => $user->email,
                'phone' => $user->phone,
                'username' => $user->username
            ]
        ];

    }

    /**
     * Return all users stored in database.
     */
    public function get_all()
    {
        $db_users = DB::table('users')->get();
        
        // Only returns some fields
        $users = [];
        foreach ($db_users as $user) {
            $users[] = [
                'id' => $user->id,
                'email' => $user->email,
                'phone' => $user->phone,
                'username' => $user->username
            ];
        }
        return [
            "status" => "ok",
            "users" => $users
        ];
    }

    /**
     * Create a new user.
     */
    public function create( Request $request )
    {
        // Automatically decode json input, depending on the content-type
        $username = $request->input('username');
        $password = $request->input('password');
        $email    = $request->input('email');
        $phone    = $request->input('phone');

        if (empty( $username ) ) {
            return response()
                ->json( ["status" => "error", 'error' => '"username" field is empty'], 500 );
        }
        if (empty( $password ) ) {
            return response()
                ->json( ["status" => "error", 'error' => '"password" field is empty'], 500 );
        };
        if (empty( $email ) && empty( $phone ) ) {
            return response()
                ->json( ["status" => "error", 'error' => '"email" and "phone" fields are empty'], 500);
        };
        if(!preg_match('/^[a-z0-9]+([_|\.|-]{1}[a-z0-9]+)*@[a-z0-9]+([_|\.|-]{1}[a-z0-9]+)*[\.]{1}[a-z]{2,6}$/', $email)){
            return response()
                ->json( ["status" => "error", 'error' => '"email" is invalid'], 500);
        };

        if(!preg_match('/^(01|02|03|04|05|06|08)[0-9]{8}$/', $phone)){
            return response()
                ->json( ["status" => "error", 'error' => '"phone" is invalid'], 500);
        };

        // Check for user existence in database
        $user = \App\User::where('email', $email)->orWhere('phone', $phone)->first();
        if ($user) {
            return response()
                ->json([
                    'status' => 'error',
                    'error' => 'email or phone field already exists'
                ]);
        }
        $user_to_insert = [
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'phone' => $phone,
        ];
        
        $user_id = null;
        try {
            $user_id = DB::table('users')->insertGetId(
                $user_to_insert
            );
        } catch (\Illuminate\Database\QuerusernameusernameusernameyException $e) {
            return response()
                ->json(
                [
                    'status' => 'error',
                    'error' =>'Unable to create the user',
                ]
            );
        }

        return response()
            ->json(
                ['status' => 'ok'],
                200
            );
    }

    public function updateUser( $id = NULL, Request $request ) {
        // TODO: check if logged in user is the same as $email

        // Load the api_token from database
        $api_token = \DB::table('api_tokens')
            ->where('value', $request->header('api_token'))
            ->get();

        // If no id is specified in the route, guess it from the api_token
        // (Get the logged in user)
        if (is_null($id)) {
            Log::debug('Loading user from token');
            // Get the id from the token
            if (count( $api_token ) >= 0 ) {
                $id = $api_token[0]->user_id;
            }
        }

        $user = \App\User::where('id', $id)->first();
        if (!$user) {
            return [
                "status" => "error", 
                "error" => "User $id not found"
            ];
        }
        
        // Check if the logged in user is the same as the one we want to modify
        // Check if token belongs to the user with the specified id
        if (count( $api_token ) == 0 || $api_token[0]->user_id != $id ) {
            Log::debug('user id and token user id same');
            return response()
                ->json([
                    'status' => 'error',
                    'error' => 'You are not allowed to modify another user'
                ]);
        }

        if ($request->input('username') ) {
            Log::debug("Updating ".$request->input('username'));
            $user->username = $request->input('username');
        }

        if ($request->input('password') ) {
            Log::debug("Updating ".$request->input('password'));
            $user->password = $request->input('password');
        }

        if ($request->input('phone') ) {
            Log::debug("Updating ".$request->input('phone'));
            $user->phone = $request->input('phone');
        }

        if ($request->input('email') ) {
            Log::debug("Updating ".$request->input('email'));
            $user->email = $request->input('email');
        }

        $user->save();
        return ["status" => "ok", "message" => "Updated user $id"];
    }

    public function login(Request $request) {
        $email = $request->input('email');
        $phone = $request->input('phone');
        $password = $request->input('password');

        $user = NULL;
        if ($email) {
            $user = \App\User::where('email', $email)
                ->where('password', $password)
                ->first();
        } elseif( $phone ) {
            $user = \App\User::where('phone', $phone)
                ->where('password', $password)
                ->first();
        } else {
            return ["status" => "error", 'error' => 'Please specify an email or phone'];
        }
        
        if ($user) {
            // There is a user, login ok
            // Create a token
            $expiration = new \DateTime();
            $expiration->add( new \DateInterval('P1D') );
            $token_value = Str::random(60);

            DB::table('api_tokens')->insert([
                "user_id" => $user->id,
                "expires" => $expiration,
                "value" => $token_value
            ]);

            // Return some user informations, and the token
            $result = [
                "id" => $user->id,
                "email" => $user->email,
                "phone" => $user->phone,
                "api_token" => $token_value
            ];
        } else {
            $result = ["status" => "error", 'error' => 'Unable to login, wrong username/password'];
        }

        return $result;
    }

    public function deleteUser( $id, Request $request ) {
        $user = \App\User::where('id', $id)->first();


        // Check if the logged in user is the same as the one we want to modify
        $api_token = \DB::table('api_tokens')
            ->where('value', $request->header('api_token'))
            ->get();
        
        // Check if token belongs to the user with the specified id
        if (count( $api_token ) == 0 || $api_token[0]->user_id != $id ) {
            Log::debug('user id and token user id differs');
            return response()
                ->json([
                    'status' => 'error',
                    'error' => 'You are not allowed to delete another user'
                ]);
        }

        // TODO: check if logged in user is the same as $email
        if ($user) {
            $user->delete();
            return ["status" => "ok", 'message' => "User $id deleted"];
        } else {
            return ["status" => "error", 'error' => "Cannot delete user $id"];
        }
    }

    public function getFriends( $id = NULL, Request $request ) {

        // Check if the logged in user is the same as the one we want to modify
        // Load the token from database
        $api_token = \DB::table('api_tokens')
            ->where('value', $request->header('api_token'))
            ->get();

        // If no id is specified in the route, guess it from the api_token
        // (Get the logged in user)
        if (is_null($id)) {
            Log::debug('Loading user from token');
            // Get the id from the token
            if (count( $api_token ) >= 0 ) {
                $id = $api_token[0]->user_id;
            }
        }

        // Check if token belongs to the user with the specified id
        if (count( $api_token ) == 0 || $api_token[0]->user_id != $id ) {
            Log::debug('user id and token user id differs');
            return response()
                ->json([
                    'status' => 'error',
                    'error' => 'You are not allowed to view friends of another user'
                ]);
        }

        $friends = \DB::table('friends')
            ->where('user_id_1', $id)
            ->join('users', 'friends.user_id_2', '=', 'users.id');

        $all_friends = \DB::table('friends')
            ->where('user_id_2', $id)
            ->join('users', 'friends.user_id_1', '=', 'users.id')
            ->union( $friends )
            ->get();

        $result_friends = [];
        foreach ($all_friends as $friend) {
            // Get the id of the user, not from the friends
            // Laravel automatically rename duplicates fields
            $user_id_field = "id:1";
            $result_friends[] = [
                'id' => $friend->$user_id_field,
                'username' => $friend->username,
                'email' => $friend->email,
                'phone' => $friend->phone
            ];
        }


        return ["status" => "ok", "friends" => $result_friends];
    }

    public function addFriend( $id, Request $request ) {
        
        // Check if the logged in user is the same as the one we want to modify
        $api_token = \DB::table('api_tokens')
            ->where('value', $request->header('api_token'))
            ->get();

        // Check if token belongs to the user with the specified id
        if (count( $api_token ) == 0 || $api_token[0]->user_id != $id ) {
            Log::debug('user id and token user id differs');
            return response()
                ->json([
                    'status' => 'error',
                    'error' => 'You are not allowed to add friends to another user'
                ]);
        }

        $user_request = \DB::table('users');
        
        if ($request->input('email') ) {
            $user_request->where('email', $request->input('email'));
        } elseif( $request->input('phone') ) {
            $user_request->where('phone', $request->input('phone'));
        } else {
            return ["status" => "error", 'error' => 'Please specify an email or phone'];
        }
        
        $user = $user_request->first();
        
        if ($user) {
            // Search if users are already friends
            
            $friends = \DB::table('friends')
                ->where([
                    ['user_id_1', '=', $user->id],
                    ['user_id_2', '=', $id],
                ])
                ->orWhere([
                    ['user_id_1', '=', $id],
                    ['user_id_2', '=', $user->id],
                ])->get();

            if (count($friends) == 0) {
                // If not, insert one relation
                try {
                    $friend_id = \DB::table('friends')->insert([
                        "user_id_1" => $id,
                        "user_id_2" => $user->id,
                    ]);
                } catch (\Illuminate\Database\QuerusernameusernameusernameyException $e) {
                    return ["status" => "error", "error" => "Unable to add a friend in database (".$e->getMessage().")"];
                }
            }
        } else {
            return ["status" => "error", "error" => "Friend not found with provided email or phone"];
        }

        return ["status" => "ok", "message" => "Friend added"];
    }
}
