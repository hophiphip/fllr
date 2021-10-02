<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use App\Models\Colors;
use App\Models\Player;
use App\Models\Field;

class ApiTest extends TestCase
{
    /**
     * Get non existent game.
     *
     * @return void
     */
    public function testGetNonExistentGame()
    {
        $response = $this->call('GET', '/api/game/aaaaaaaaaaaaaaaaaaaaaaaa');

        $response
            ->assertStatus(404)
            ->assertJson([
                'error' =>  'incorrect game id',        
            ]);
    }
    
    /**
     * Put a move in non existent game.
     *
     * @return void
     */
    public function testPutNonExistentGame()
    {
        $response = $this->call('PUT', '/api/game/aaaaaaaaaaaaaaaaaaaaaaaa', [
            'playerId' => 1,
            'color' => 'red',
        ]);

        $response
            ->assertStatus(404)
            ->assertJson([
                'error' =>  'incorrect game id',        
            ]);
    }


    /**
     * Test new game creation with missing `width`.
     */
    public function testPostNewGameWithMissingWidth()
    {
        $response = $this->json('POST', '/api/game', [
            'height' => 15,
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test new game creation with missing `height`.
     */
    public function testPostNewGameWithMissingHeight()
    {
        $response = $this->json('POST', '/api/game', [
            'width' => 25,
        ]);

        $response->assertStatus(422);
    }

    /**
     * Test new game creation with incorrect (even) `height`.
     */
    public function testPostNewGameWithIncorrectHeight()
    {
        $response = $this->json('POST', '/api/game', [
            'width' => 25,
            'height' => 14,
        ]);

        $response
            ->assertStatus(400)
            ->assertJson([
                'error' => 'incorrect field size'
            ]);
    }

    /**
     * Test new game creation.
     *
     * @return array
     */
    public function testPostNewGame(): array
    {
        $response = $this->json('POST', '/api/game', [
            'width' => 25,
            'height' => 15,
        ]);

        $response
            ->assertStatus(201);

        $this->assertTrue($response['id'] != null);

        return [
            'id' => $response['id'],
        ];
    }

    /**
     * Test getting existing game status.
     *
     * @depends testPostNewGame
     *
     * @return array
     */
    public function testGetNewGame(array $postResponse): array
    {
        $response = $this->json('GET', '/api/game/' . $postResponse['id']);

        $response
            ->assertStatus(200)
            ->assertJson([
                'id' => $postResponse['id'],
                'currentPlayerId' => 1,
                'winnerPlayerId' => 0,
            ]);

        return [
            'id' => $response['id'],
            'players' => $response['players'],
            'currentPlayerId' => $response['currentPlayerId'],
        ];
    }
    
    /**
     * Test putting a new game move.
     *
     * @depends testGetNewGame
     *
     * @return array
     */
    public function testPutGameMove(array $getResponse): array
    {
        $allowedColor = Colors::allowedColor(
            $getResponse['players'][1]['color'],
            $getResponse['players'][2]['color']
        );
        
        $this->assertTrue($allowedColor != null);

        $response = $this->json('PUT', '/api/game/' . $getResponse['id'], [
            'playerId' => $getResponse['currentPlayerId'],
            'color' => $allowedColor, 
        ]);

        $response
            ->assertStatus(201)
            ->assertJson([
                'id' => $getResponse['id'],
                'currentPlayerId' => Player::nextPlayerId($getResponse['currentPlayerId']),
            ]);

        return [
            'id' => $response['id'],
            'players' => $response['players'],
            'currentPlayerId' => $response['currentPlayerId'],
        ];
    }
    
    /**
     * Test putting a new game move with current player color.
     *
     * @depends testPutGameMove
     *
     * @return array
     */
    public function testPutGameMoveWithCurrentPlayerColor(array $putResponse): array
    {
        $response = $this->json('PUT', '/api/game/' . $putResponse['id'], [
            'playerId' => $putResponse['currentPlayerId'],
            'color' => $putResponse['players'][$putResponse['currentPlayerId']]['color'], 
        ]);

        $response
            ->assertStatus(409)
            ->assertJson([
                'error' => 'provided player can\'t choose this color',
            ]);
        
        return [
            'id' => $putResponse['id'],
            'players' => $putResponse['players'],
            'currentPlayerId' => $putResponse['currentPlayerId'],
        ];
    }
    
    /**
     * Test putting a new game move with other player color.
     *
     * @depends testPutGameMoveWithCurrentPlayerColor
     *
     * @return array
     */
    public function testPutGameMoveWithOtherPlayerColor(array $putResponse): array
    {
        $response = $this->json('PUT', '/api/game/' . $putResponse['id'], [
            'playerId' => $putResponse['currentPlayerId'],
            'color' => $putResponse['players'][Player::nextPlayerId($putResponse['currentPlayerId'])]['color'], 
        ]);

        $response
            ->assertStatus(409)
            ->assertJson([
                'error' => 'provided player can\'t choose this color',
            ]);
        
        return [
            'id' => $putResponse['id'],
            'players' => $putResponse['players'],
            'currentPlayerId' => $putResponse['currentPlayerId'],
        ];
    }


    /**
     * Test putting a new game move with other player.
     *
     * @depends testPutGameMoveWithOtherPlayerColor
     *
     * @return array
     */
    public function testPutGameMoveWithOtherPlayer(array $putResponse): array
    {
        $response = $this->json('PUT', '/api/game/' . $putResponse['id'], [
            'playerId' => Player::nextPlayerId($putResponse['currentPlayerId']),
            'color' => $putResponse['players'][Player::nextPlayerId($putResponse['currentPlayerId'])]['color'], 
        ]);

        $response
            ->assertStatus(403)
            ->assertJson([
                'error' => 'provided player can\'t move right now',
            ]);
        
        return [
            'id' => $putResponse['id'],
            'players' => $putResponse['players'],
            'currentPlayerId' => $putResponse['currentPlayerId'],
        ];
    }

    /**
     * Test putting a new game move with unsupported color.
     *
     * @depends testPutGameMoveWithOtherPlayer
     *
     * @return array
     */
    public function testPutGameMoveWithUnsupportedColor(array $putResponse): array
    {
        $response = $this->json('PUT', '/api/game/' . $putResponse['id'], [
            'playerId' => $putResponse['currentPlayerId'],
            'color' => 'orange', 
        ]);

        $response
            ->assertStatus(400);
        
        return [
            'id' => $putResponse['id'],
            'players' => $putResponse['players'],
            'currentPlayerId' => $putResponse['currentPlayerId'],
        ];
    }


    /**
     * Trying to complete a game (not optimal way).
     *
     * @depends testPutGameMoveWithUnsupportedColor
     *
     * @return array
     */
    public function testCompleteAGame(array $putResponse): array
    {
        $response = $this->json('GET', '/api/game/' . $putResponse['id']);

        $response
            ->assertStatus(200);

        while ($response['winnerPlayerId'] == 0) {
            $field = Field::fromArray($response['field']);
            $colorStats = array();

            // TODO: We do not need to user isNotPlayerCell as we check current index twice this way
            foreach ($response['field']['cells'] as $i => $cell) {
                if ($cell['playerId'] == $response['currentPlayerId']) {
                    // left
                    if (!($field->hasNoLeftCell($i))) {
                        $leftIndex = $i - $field->width;
                        if ($field->isNotPlayerCell($i, $leftIndex)) {
                            $key = Colors::$colorsTable[$field->cells[$leftIndex]["color"]]; 
                            if (array_key_exists($key, $colorStats)) {
                                $colorStats[$key] += 1;
                            } else {
                                $colorStats[$key] = 1;
                            }
                        }
                    }
                    
                    // top
                    if (!($field->hasNoTopCell($i))) {
                        $topIndex = $i - $field->width + 1;
                        if ($field->isNotPlayerCell($i, $topIndex)) {
                            $key = Colors::$colorsTable[$field->cells[$topIndex]["color"]]; 
                            if (array_key_exists($key, $colorStats)) {
                                $colorStats[$key] += 1;
                            } else {
                                $colorStats[$key] = 1;
                            }
                        }
                    }
                    
                    // right
                    if (!($field->hasNoRightCell($i))) {
                        $rightIndex = $i + $field->width;
                        if ($field->isNotPlayerCell($i, $rightIndex)) {
                            $key = Colors::$colorsTable[$field->cells[$rightIndex]["color"]]; 
                            if (array_key_exists($key, $colorStats)) {
                                $colorStats[$key] += 1;
                            } else {
                                $colorStats[$key] = 1;
                            }
                        }
                    }
                    
                    // bottom
                    if (!($field->hasNoBottomCell($i))) {
                        $bottomIndex = $i + $field->width - 1;
                        if ($field->isNotPlayerCell($i, $bottomIndex)) {
                            $key = Colors::$colorsTable[$field->cells[$bottomIndex]["color"]]; 
                            if (array_key_exists($key, $colorStats)) {
                                $colorStats[$key] += 1;
                            } else {
                                $colorStats[$key] = 1;
                            }
                        }
                    }
                }
            }

            //foreach ($colorStats as $key => $val) {
            //    print($key);
            //    print(" ");
            //    print($val);
            //    print("\n");
            //}

            // Get rid of player colors
            $colorStats[Colors::$colorsTable[$response['players'][1]['color']]] = 0;
            $colorStats[Colors::$colorsTable[$response['players'][2]['color']]] = 0;

            // Get the most popular color
            $nextColor = Colors::$colors[array_search(max($colorStats),$colorStats)];

            //print($nextColor);
            //print("\n");
            //print($response['players'][1]['color']);
            //print("\n");
            //print($response['players'][2]['color']);
            //print("\n");

            // prevent too many connections
            do {
                $response = $this->json('PUT', '/api/game/' . $response['id'], [
                    'playerId' => $response['currentPlayerId'],
                    'color' => $nextColor, 
                ]);

                // sleep for a second 
                usleep(100000);
            } while ($response->status() == 429)

            $response
                ->assertStatus(201);

            // sleep for half a second 
            //usleep(500000);
        }

        return [
            'id' => $response['id'],
        ];
    }
}