<?php

namespace App\Http\Controllers;

use Log;
use Illuminate\Http\Request;

class Game extends Controller
{
    public function createOrUpdate($id = NULL, Request $request) {

        // Load the api_token from the database
        $api_token = \DB::table('api_tokens')
            ->where('value', $request->header('api_token'))
            ->get();

        // (Get the logged in user)
        // Get the id from the token
        $user_id = NULL;
        if (count( $api_token ) >= 0 ) {
            $user_id = $api_token[0]->user_id;
        }

        if (is_null($id)) {
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

            // TODO: store the position
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
                    $is_owner = FALSE;
                    if ($user_id === $db_users[0]->id) {
                        $is_owner = TRUE;
                    }
                    array_push( $game['users'], ['user_id' => $db_users[0]->id, "is_owner" => $is_owner] );
                } else {
                    // Try to add a guest user based on his username
                    // TODO: Also store the avatar and color in the relation
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

            $i = 0;
            foreach ($game['users'] as $game_user) {
                $game_user['game_id'] = $db_game_id;
                $game_user['position'] = $i++;
                \DB::table('games_users')->insert(
                    $game_user
                );
            }

            // return [
            //     'status' => 'ok',
            //     'message' => 'Game created',
            //     'game_id' => $db_game_id
            // ];
        } else {
            $db_game_id = $id;
    }
        $db_game = \DB::table('games')
            ->where('id', '=', intval($db_game_id))
            ->first();
        if (is_null( $db_game ) ) {
            return [
                'status' => 'error',
                'error' => 'Game not found in database'
            ];
        }

        $db_game_players = \DB::table('games_users')
            ->where('game_id', '=', intval($db_game_id))
            ->get();
        // dd( $db_game_players );

        // Create or update turns
        $db_game_turns = \DB::table('games_turns')
            ->where('game_id', '=', intval( $db_game_id ))
            ->get();
        // dd( $db_game_turns );
        $request_turns = $request->input('turns');
        // dd( $request_turns );
        
        if (count($db_game_turns) < count($request_turns)) {
            // Insert missing turns
            for ($i=count($db_game_turns); $i < count($request_turns); $i++) {
                $req_turn = $request_turns[$i];
                Log::debug('Insert turn '.$i);
                \DB::table('games_turns')->insert(
                    [
                        'game_id' => $db_game_id,
                        'preneur' => $db_game_players[$req_turn['preneur']-1]->user_id,
                        'partenaire' => (is_null($req_turn['partenaire']))
                            ? NULL
                            : $db_game_players[$req_turn['partenaire']-1]->user_id,
                        'type' => $req_turn['type'],
                        'roi' => $req_turn['roi'],
                        'victoire' => boolval($req_turn['victoire']),
                        'score' => intval($req_turn['score']),
                        'autre_score' => intval($req_turn['autre_score'])
                    ]
                );
            }
        }
        // Update only turns and scores
        $db_game_turns = \DB::table('games_turns')
            ->where('game_id', '=', intval( $db_game_id ))
            ->get();

        return [
            'status' => 'ok',
            'game' => [
                'gamed_id' => intval($db_game->id),
                'status' => $db_game->status,
                'users' => $db_game_players,
                'turns' => $db_game_turns
            ]
        ];
    }

    public function get($id) {
        $db_game = \DB::table('games')->where('id', '=', $id)->first();

        // Check if the game exists or not
        $users = [];
        $creator_id = NULL;

        $db_games_users = \DB::table('games_users')
            ->where('game_id', '=', $id)
            ->get();

        foreach ($db_games_users as $item) {
            if ($item->type == 'guest') {
                array_push( $users, ['type' => 'guest', 'username' => $item->username] );
            } else {
                $user = \App\User::where('id', $item->user_id)->first();
                array_push( $users, [
                    'type' => 'user', 'username' => $item->username, 'user_id' => intval($item->user_id),
                    'avatar' => $user->avatar, 'color' => $user->color
                ]);
            }
            if ($item->is_owner) {
                $creator_id = $item->user_id;
            }
        }
        $db_game_turns = \DB::table('games_turns')
            ->where('game_id', '=', $id)
            ->get();

        return [
            'game_id' => intval($id),
            'users' => $users,
            'creator_id' => intval($creator_id),
            'turns' => $db_game_turns->all()
        ];

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

            $users = [];
            $creator_id = NULL;
            foreach ($db_games_users as $item) {
                if ($item->type == 'guest') {
                    array_push( $users, ['type' => 'guest', 'username' => $item->username] );
                } else {
                    $user = \App\User::where('id', $item->user_id)->first();
                    array_push( $users, [
                        'type' => 'user', 'username' => $item->username, 'user_id' => intval($item->user_id),
                        'avatar' => $user->avatar, 'color' => $user->color
                    ]);
                }
                if ($item->is_owner) {
                    $creator_id = $item->user_id;
                }
            }
            $games[] = [
                'game_id' => intval($game->id),
                'users' => $users,
                'creator_id' => intval($creator_id)
            ];
        }
        return [
            "status" => "ok",
            "games" => $games
        ];
    }

}
