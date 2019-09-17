<?php

namespace App\Http\Controllers;

use Log;
use Illuminate\Http\Request;

class Game extends Controller
{
    public function createGame(Request $request) {
        // Read users participating to the game
        $request_users = $request->input('users');
        if (is_null($request_users) || !is_array($request_users)) {
            return [
                'status' => 'error',
                'error' => 'Provided users are unreadable. Please specify a user phone, a user email or a user username.'
            ];
        }
        $userCount = count( $request_users );

        $game = [
            'users' => []
        ];

        foreach ($request_users as $request_user) {
            $db_user = \DB::table('users');
            $db_users = [];
            if (isset($request_user['email']) && !is_null($request_user['email'])) {
                Log::debug('Searching for user with email: '.$request_user['email']);
                $db_user->where('email', $request_user['email']);
                $db_users = $db_user->get();
            } elseif( isset($request_user['phone']) && !is_null($request_user['phone'])) {
                Log::debug('Searching for user with phone: '.$request_user['phone']);
                $db_user->where('phone', $request_user['phone']);
                $db_users = $db_user->get();
            }

            if (count($db_users) > 0) {
                Log::debug('Found at least one user corresponding to the email or phone');
                // Log::debug(print_r($db_users[0], 1));
                array_push( $game['users'], ['user_id' => $db_users[0]->id] );
            } else {
                // Try to add a guest user based on his username
                if (isset( $request_user['username'] ) ) {
                    Log::debug('Add a guest user ('.$request_user['username'].')');
                    array_push( $game['users'], ['username' => $request_user['username'], 'type' => 'guest'] );
                } else {
                    return [
                        'status' => 'error',
                        'error' => 'User not found with provided email or phone ('.print_r($request_user,1)
                    ];
                }
            }
        }

        $db_game_id = \DB::table('games')->insertGetId(
            ['status' => 'started']
        );
        foreach ($game['users'] as $game_user) {
            $game_user['game_id'] = $db_game_id;
            \DB::table('games_users')->insert(
                $game_user
            );
        }

        return [
            'status' => 'ok',
            'message' => 'Game created',
            'game_id' => $db_game_id
        ];

        Log::debug('Playing users: '. print_r( $user_playing, 1 ));
    }

        /**
     * Return all users stored in database.
     */
    public function get_all()
    {
        $db_games = \DB::table('games')->get();
        
        // Only returns some fields
        $games = [];
        foreach ($db_games as $game) {
            $db_games_users = \DB::table('games_users')
                ->where('game_id', '=', $game->id)
                ->get();

            $games[] = [
                'id' => $game->id,
                'users' => $db_games_users->map(
                    function ($item) {return ($item->type == 'guest') 
                        ? ['type' => 'guest', 'username' => $item->username]
                        : ['type' => 'user', 'user_id' => $item->user_id];
                    }
                )
            ];
        }
        return [
            "status" => "ok",
            "games" => $games
        ];
    }

}
