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

        $db_game_players = [];

        if (is_null($id)) {
            // Read users participating to the game
            // $request_users = $request->input('users');
            // if (is_null($request_users) || !is_array($request_users)) {
            //     return [
            //         'status' => 'error',
            //         'error' => 'Provided users are unreadable. Please specify a user phone, a user email or a user username.'
            //     ];
            // }
            $request_users = [];
            foreach ([1,2,3,4,5] as $req_user_position)
            if (!is_null($request->input('Joueur'.$req_user_position) ) ) {
                $request_users[] = $request->input('Joueur'.$req_user_position);
            }
            $userCount = count( $request_users );

            $game = [
                'users' => []
            ];

            $nb_joueurs = $request->input('nb_joueurs');

            // First player is always the logged-in one
            $db_user = \DB::table('users')
                ->where('id', '=', intval($user_id))
                ->first();
            array_push( $game['users'], [
                'user_id' => $user_id, "username" => $db_user->username, "is_owner" => TRUE
            ]);

            for($i=2; $i<=$nb_joueurs; $i++) {

                $request_user = $request->input('Joueur'.$i);
                $db_user = \DB::table('users');
                $db_users = [];
                if (isset($request_user['id']) && !is_null($request_user['id'])) {
                    Log::debug('Searching for user with id: '.$request_user['id']);
                    $db_user->where('id', $request_user['id']);
                    $db_users = $db_user->get();
                }
                // elseif( isset($request_user['phone']) && !is_null($request_user['phone'])) {
                //     Log::debug('Searching for user with phone: '.$request_user['phone']);
                //     $db_user->where('phone', $request_user['phone']);
                //     $db_users = $db_user->get();
                // }

                if (count($db_users) > 0) {
                    Log::debug('Found at least one user corresponding to the email or phone');
                    // Log::debug(print_r($db_users[0], 1));
                    $is_owner = FALSE;
                    if ($user_id == $db_users[0]->id) {
                        $is_owner = TRUE;
                    } else {
                        // Check if the user is a friend
                        $db_user_friend = \DB::table('friends')
                            ->where(function ($query) use ($user_id, $db_users) {
                                $query->where('user_id_1', '=', $user_id)
                                    ->where('user_id_2', '=', $db_users[0]->id);
                            })
                            ->orWhere(function ($query) use ($user_id, $db_users) {
                                $query->where('user_id_1', '=', $db_users[0]->id)
                                    ->where('user_id_2', '=', $user_id);
                            })
                            ->get();
                        if (count($db_user_friend) != 2 ) {
                            return [
                                'status' => 'error',
                                'error' => 'User '.$db_users[0]->id.' is not your friend'
                            ];
                        }
                    }

                    array_push( $game['users'], ['user_id' => $db_users[0]->id, "username" => $request_user['pseudo'], "is_owner" => $is_owner] );
                } else {
                    // Try to add a guest user based on his pseudo
                    // TODO: Also store the avatar and color in the relation
                    if (isset( $request_user['pseudo'] ) ) {
                        Log::debug('Add a guest user ('.$request_user['pseudo'].')');
                        array_push( $game['users'], [
                            'username' => $request_user['pseudo'], 'image' => $request_user['image'],
                            'color' => $request_user['color'], 'type' => 'guest'
                        ]);
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
        } else {
            // Game already exists
            $db_game_id = $id;

            // Check if the logged in user is the owner of the game
            $db_game_owner = \DB::table('games_users')
                ->where('game_id', '=', intval( $db_game_id ))
                ->where('user_id', '=', $user_id)
                ->where('is_owner', '=', TRUE)
                ->first();

            if (is_null( $db_game_owner ) ) {
                return [
                    'status' => 'error',
                    'error' => 'You are not the creator of this game'
                ];
            }
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

        // Load the players from database
        $db_game_players = \DB::table('games_users')
            ->where('game_id', '=', intval($db_game_id))
            ->get();
        // dd( $db_game_players );

        $game_return = [];
        $game_return['creator_id'] = intval($user_id);
        
        for ($i = 1; $i <= count($db_game_players); $i++) {
        // foreach ($db_game_players as $db_game_user) {
            $db_game_user = $db_game_players[$i-1];
            $user_to_add = $db_game_user;

            $user_to_add->id = $i;

            if (intval($db_game_user->user_id)) {
                $db_user = \DB::table('users')
                    ->where('id', '=', intval($db_game_user->user_id))
                    ->first();
    
                $user_to_add->avatar = $db_user->avatar;
                $user_to_add->image = $db_user->image;
                $user_to_add->color = $db_user->color;
            }

            $game_return["Joueur$i"] = $user_to_add;
        }
        $game_return['nb_joueurs'] = $i-1;

        // Create or update turns
        $db_game_turns = \DB::table('games_turns')
            ->where('game_id', '=', intval( $db_game_id ))
            ->get();
        // dd( $db_game_turns );
        $request_turns = $request->input('turns');
        
        if (count($db_game_turns) < count($request_turns)) {
            // Insert missing turns
            for ($i=count($db_game_turns); $i < count($request_turns); $i++) {
                $req_turn = $request_turns[$i];
                Log::debug('Insert turn '.$i);
                \DB::table('games_turns')->insert(
                    [
                        'id' => $req_turn['id'],
                        'game_id' => $db_game_id,
                        'preneur' => $req_turn['preneur'],
                        'partenaire' => $req_turn['partenaire'],
                        'type' => $req_turn['type'],
                        'roi' => $req_turn['roi'],
                        'victoire' => boolval($req_turn['victoire']),
                        'score' => intval($req_turn['score']),
                        'autre_score' => intval($req_turn['autre_score']),
                        'scoreJ1' => (is_null($req_turn['scoreJ1'])) ? 0 : intval($req_turn['scoreJ1']),
                        'scoreJ2' => (is_null($req_turn['scoreJ2'])) ? 0 : intval($req_turn['scoreJ2']),
                        'scoreJ3' => (is_null($req_turn['scoreJ3'])) ? 0 : intval($req_turn['scoreJ3']),
                        'scoreJ4' => (!isset($req_turn['scoreJ4'])) ? 0 : intval($req_turn['scoreJ4']),
                        'scoreJ5' => (!isset($req_turn['scoreJ5'])) ? 0 : intval($req_turn['scoreJ5']),
                    ]
                );
            }
        }
        // Update only turns and scores
        $db_game_turns = \DB::table('games_turns')
            ->where('game_id', '=', intval( $db_game_id ))
            ->get();

        $game_return['game_id'] = intval($db_game->id);
        $game_return['turns'] = $db_game_turns;

        return [
            'status' => 'ok',
            'game' => $game_return
        ];
    }

    public function get($id) {
        $db_game = \DB::table('games')->where('id', '=', $id)->first();

        $game_return = [];

        // Check if the game exists or not
        $users = [];
        $creator_id = NULL;

        $db_games_users = \DB::table('games_users')
            ->where('game_id', '=', $id)
            ->get();

        for ($i=1; $i <= count( $db_games_users ); $i++) {
            $db_game_player = $db_games_users[$i-1];

            // dd( is_null( $db_game_player->user_id) );
            $user_to_add = $db_game_player;
            if ( intval( $db_game_player->user_id ) ) {
                // Load the user from database to get his informations
                // TODO: check if its a friend of the logged in user to insert his avatar, image and color
                $db_user = \DB::table('users')
                    ->where('id', '=', intval($db_game_player->user_id))
                    ->first();
                // dd( $db_user );
                // $user_to_add->email = $db_user->email;
                // $user_to_add->phone = $db_user->phone;
                $user_to_add->avatar = $db_user->avatar;
                $user_to_add->image = $db_user->image;
                $user_to_add->color = $db_user->color;
            }
            $game_return["Joueur$i"] = $user_to_add;
            if ($db_game_player->is_owner) {
                $game_return['creator_id'] = intval($db_game_player->user_id);
            }
        }
        $game_return['nb_joueurs'] = $i-1;
        $db_game_turns = \DB::table('games_turns')
            ->where('game_id', '=', $id)
            ->get();

        $game_return['game_id'] = intval($id);
        // $game_return['creator_id'] = intval($creator_id);
        $game_return['turns'] = $db_game_turns->all();
        return $game_return;
        // return [
        //     'game_id' => intval($id),
        //     'creator_id' => intval($creator_id),
        //     'turns' => $db_game_turns->all()
        // ];

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

    public function getAllLoggedUser(Request $request) {
        // Load the logged in user
        // Load the games he's participating
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

        $db_game_playing = \DB::table('games_users')
            ->where('user_id', '=', $user_id)
            ->get();

        $db_game_ids = array_map(
            function ($item) { return $item->game_id; },
            $db_game_playing->all()
        );

        $db_games = \DB::table('games')
            ->whereIn('id', array_values( $db_game_ids ))
            ->get();

        // dd( $db_games );

        $return = [
            'status' => 'ok',
            'games' => []
        ];
        foreach ($db_games as $db_game ) {
            $result_game = [];
            // Load the players from database
            $db_game_players = \DB::table('games_users')
                ->where('game_id', '=', intval($db_game->id))
                ->get();
            // dd( $db_game_players );

            for ($i=1; $i <= count( $db_game_players ); $i++) {
                $db_game_player = $db_game_players[$i-1];

                // dd( is_null( $db_game_player->user_id) );
                $user_to_add = $db_game_player;
                $user_to_add->id = $i;

                if ( intval( $db_game_player->user_id ) ) {
                    // Load the user from database to get his informations
                    // TODO: check if its a friend of the logged in user to insert his avatar, image and color
                    $db_user = \DB::table('users')
                        ->where('id', '=', intval($db_game_player->user_id))
                        ->first();

                    // dd( $db_user );
                    $user_to_add->email = $db_user->email;
                    $user_to_add->phone = $db_user->phone;
                    $user_to_add->avatar = $db_user->avatar;
                    $user_to_add->image = $db_user->image;
                    $user_to_add->color = $db_user->color;
                } else {
                    // Add image and color coming from relation
                    $user_to_add->image = $db_game_player->image;
                    $user_to_add->color = $db_game_player->color;
                }
                $game_return["Joueur$i"] = $user_to_add;
                if ($db_game_player->is_owner) {
                    $game_return['creator_id'] = intval($db_game_player->user_id);
                }
            }
            $game_return['nb_joueurs'] = $i-1;

            $db_game_turns = \DB::table('games_turns')
                ->where('game_id', '=', intval( $db_game->id ))
                ->get();

            $game_return['game_id'] = $db_game->id;
            $game_return['turns'] = $db_game_turns->all();
            array_push( $return['games'], $game_return );
        }
        return $return;
    }
}
