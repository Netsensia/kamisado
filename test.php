<?php

function test() {
    global $colours;
    global $g_evaluationFunction, $g_getMovesFunction, $g_nodes, $g_maxTime;

    $board = readBoard('input.txt', $colours);
    
    $whiteWins = 0;
    $testWins = 0;
    $testWinsAsWhite = 0;
    $testWinsAsBlack = 0;
    $blackTestGames = 0;
    $whiteTestGames = 0;
    $draws = 0;
    $originalBoard = $board;

    $totalMoves = 0;
    $totalTime = 0;
    
    $g_nodes = 0;
    $testStartTime = microtime(true);
    
    for ($i=0; $i<1000; $i++) {

        $board = $originalBoard;
        echo "GAME " . ($i + 1) . PHP_EOL;
        $g_maxTime = 0.1 + (($i % 90) / 10);

        echo "Max time = " . $g_maxTime . PHP_EOL;

        if ($i % 2 == 0) {
            $whiteEvaluationFunction = "evaluate";
            $blackEvaluationFunction = "evaluateTest";
            $whiteGetMovesFunction = "getMoves";
            $blackGetMovesFunction = "getMovesTest";
            $blackTestGames ++;
        } else {
            $whiteEvaluationFunction = "evaluateTest";
            $blackEvaluationFunction = "evaluate";
            $whiteGetMovesFunction = "getMovesTest";
            $blackGetMovesFunction = "getMoves";
            $whiteTestGames ++;
        }
        
        echo "White evaluation function = $whiteEvaluationFunction" . PHP_EOL;
        echo "Black evaluation function = $blackEvaluationFunction" . PHP_EOL;
        
        $moveCount = 0;
        do {

            if ($moveCount % 2 == 0) {
                $g_evaluationFunction = $whiteEvaluationFunction;
                $g_getMovesFunction = $whiteGetMovesFunction;
            } else {
                $g_evaluationFunction = $blackEvaluationFunction;
                $g_getMovesFunction = $blackGetMovesFunction;
            }

            $t = microtime(true);

            if ($g_evaluationFunction == 'evaluateTest') {
                //$g_evaluationFunction = "evaluate";
                $result = getOpeningMoveTest($board);
                if ($result == null) {
                    $result = getBestMove($board);
                }
            } else {
                if ($moveCount == 0) {
                    $result = getOpeningMoveTest($board);
                } else {
                    $result = null;
                }
                
                if ($result == null) {
                    $result = getBestMove($board);
                }
            }

            if ($result['move'] == null) {
                throw new Exception('No move found for board');
            }

            $move = $result['move'];
            $depth = $result['depth'];

            //echo str_pad($g_evaluationFunction, 13) . " reached depth " . $depth . ", evaluating " . $g_nodes . " positions" . PHP_EOL;
            $board = makeMove($board, $move);
            $moveCount ++;

            if (isGameOver($board, $move)) {
                if ($board['mover'] == 2) {
                    $whiteWins ++;
                    if ($whiteEvaluationFunction == "evaluateTest") {
                        $testWins ++;
                        $testWinsAsWhite ++;
                    }
                } else {
                    if ($blackEvaluationFunction == "evaluateTest") {
                        $testWins ++;
                        $testWinsAsBlack ++;
                    }
                }
                break;
            }
        } while (true);

        $totalMoves += $moveCount;
        $totalPlayed = $i + 1;
        $nonDraws = $totalPlayed - $draws;
        echo "Draws = " . $draws . ' (' . number_format(100 * ($draws / $totalPlayed)) . '% of all games)' . PHP_EOL;
        echo "Moves made = " . $moveCount . PHP_EOL;
        if ($nonDraws > 0) {
            echo "White wins = " . $whiteWins . ' (' . number_format(100 * ($whiteWins / $nonDraws), 2) . '% of ' . $nonDraws . ' non draws)' . PHP_EOL;
            echo "Test function wins = " . $testWins . ' (' . number_format(100 * ($testWins / $nonDraws), 2) . '% of ' . $nonDraws . ' non draws)' . PHP_EOL;
            if ($i > 1) {
                echo "As white = " . $testWinsAsWhite . ' (' . number_format(100 * ($testWinsAsWhite / $whiteTestGames), 2) . '%)' . PHP_EOL;
                echo "As black = " . $testWinsAsBlack . ' (' . number_format(100 * ($testWinsAsBlack / $blackTestGames), 2) . '%)' . PHP_EOL;
            }
        }
        echo str_pad('', 60, '-') . PHP_EOL;
        $timeSoFar = microtime(true) - $testStartTime;
        $nodesPerSecond = $g_nodes / $timeSoFar;
        
        echo "Nodes per second = " . number_format($nodesPerSecond, 2) . PHP_EOL;
        echo str_pad('', 60, '-') . PHP_EOL;
        

    }
}

function isGameOver($board, $move) {
    $moves = getMoves($board);
    if (count($moves) == 0) {
        echo ($board['mover'] == 1 ? "Black" : "White") . " wins, opponent has no moves" . PHP_EOL;
        return true;
    }
    if ($move['row'] == 0 || $move['row'] == 7) {
        echo ($board['mover'] == 1 ? "Black" : "White") . " wins, reached final rank" . PHP_EOL;
        return true;
    }
    return false;
}

function printBoard($board) {
    global $colours;

    echo $board['mover'] . PHP_EOL;
    for ($row=0; $row<8; $row++) {
        for ($col=0; $col<8; $col++) {
            echo $colours[1][$row][$col];
            echo $board[$row][$col];
        }
        echo PHP_EOL;
    }
    echo $board['lastColour'] . PHP_EOL;
    echo 'WHITE' . PHP_EOL;
    foreach ($board['whitelocations'] as $location) {
        echo '[' . $location['row'] . ',' . $location['col'] . ']';
    }
    echo PHP_EOL;
    echo 'BLACK' . PHP_EOL;
    foreach ($board['blacklocations'] as $location) {
        echo '[' . $location['row'] . ',' . $location['col'] . ']';
    }
    echo PHP_EOL;

}

function evaluateTest($board) {
    
    $whiteScore = 0;

    $currentMover = $board['mover'];
    $lastColour = $board['lastColour'];

    foreach ($board['whitelocations'] as $piece => $location) {
        $col = $location['col'];
        $row = $location['row'];

        $found = false;
        foreach ([0,-1,1] as $xDir) {
            for ($y=$row-1, $x=$col+$xDir; isset($board[$y][$x]) && $board[$y][$x] == '-'; $y--, $x+=$xDir) {
                if ($y == 0) {
                    if ($currentMover == 1 && $lastColour == $piece) {
                        return VICTORY;
                    }
                    $found = true;
                    $whiteScore ++;
                    break;
                }
            }
            if ($found) {
                break;
            }
        }
    }

    foreach ($board['blacklocations'] as $piece => $location) {
        $col = $location['col'];
        $row = $location['row'];

        $found = false;
        foreach ([0,-1,1] as $xDir) {
            for ($y=$row+1, $x=$col+$xDir; isset($board[$y][$x]) && $board[$y][$x] == '-'; $y++, $x+=$xDir) {
                if ($y == 7) {
                    if ($currentMover == 2 && $lastColour == $piece) {
                        return VICTORY;
                    }
                    $found = true;
                    $whiteScore --;
                    break;
                }
            }
            if ($found) {
                break;
            }
        }
    }

    if ($board['mover'] == 1) {
        return $whiteScore;
    } else {
        return -$whiteScore;
    }
    
}

function getBestMoveTest($board) {

    global $g_maxTime;

    $moveStartTime = microtime(true);

    $deepestResultSoFar = null;

    $start = microtime(true);
    
    $moves = getMoves($board);
    
    foreach ($moves as $move) {
        if ($move['row'] == 0 || $move['row'] == 7) {
            return [
                'score' => VICTORY,
                'move' => $move,
                'depth' => 0,
                'elapsed' => microtime(true) - $start,
            ];
        }
    }

    $deepestResultSoFar = [
        'move' => $moves[0],
        'depth' => -1,
        'elapsed' => 0,
    ];
    
    for ($depth = 1; $depth <= MAX_DEPTH; $depth ++) {
        
        $bestScore = -PHP_INT_MAX;
        
        for ($i=0; $i<count($moves); $i++) {
            
            $newBoard = makeMove($board, $moves[$i]);
            $result = negamax($newBoard, 1, -PHP_INT_MAX, -$bestScore, $depth, $moveStartTime, $g_maxTime);
            
            if ($result == STATUS_GETOUT) {
                return $deepestResultSoFar;
            }
            
            $elapsed = microtime(true) - $start;
            
            $moves[$i]['score'] = -$result['score'];
            
            if ($moves[$i]['score'] > $bestScore) {
                $bestScore = $moves[$i]['score'];
                $deepestResultSoFar['move'] = $moves[$i];
                $deepestResultSoFar['depth'] = $depth;
                $deepestResultSoFar['elapsed'] = $elapsed;
            }
            
            if ($elapsed > $g_maxTime || $depth == MAX_DEPTH || $bestScore == VICTORY) {
                return $deepestResultSoFar;
            }
            
            
        }
        
        usort($moves, function($a, $b) {
              return $a['score'] > $b['score'] ? -1 :
                     ($a['score'] < $b['score'] ? 1 : 0);
        });
        
        assert($bestScore == $moves[0]['score']);
        
        if ($bestScore == -VICTORY) {
            return $deepestResultSoFar;
        }
        
    }
    
}

function boardToStringTest($board) {
    $s = '';
    for ($i=0; $i<8; $i++) {
        for ($j=0; $j<8; $j++) {
            $s .= $board[$i][$j];
        }
    }
    
    $s .= $board['lastColour'];
    
    return $s;
}

function getOpeningMoveTest($board) {
    
    $library = [
        'olmpyrgb------------------------------------------------BGRYPMLO-' => [
            ['fromRow' => 7, 'fromCol' => 0, 'row' => 3, 'col' => 0],
            ['fromRow' => 7, 'fromCol' => 1, 'row' => 1, 'col' => 1],
            ['fromRow' => 7, 'fromCol' => 3, 'row' => 2, 'col' => 3],
            ['fromRow' => 7, 'fromCol' => 3, 'row' => 3, 'col' => 3],
            ['fromRow' => 7, 'fromCol' => 4, 'row' => 2, 'col' => 4],
            ['fromRow' => 7, 'fromCol' => 4, 'row' => 3, 'col' => 4],
            ['fromRow' => 7, 'fromCol' => 6, 'row' => 1, 'col' => 6],
            ['fromRow' => 7, 'fromCol' => 7, 'row' => 3, 'col' => 7],
        ],
        'olmpyrgb----------------B--------------------------------GRYPMLOp' => [
            ['fromRow' => 0, 'fromCol' => 3, 'row' => 5, 'col' => 3],
        ],
        'olmpyrgb-G----------------------------------------------B-RYPMLOo' => [
            ['fromRow' => 0, 'fromCol' => 0, 'row' => 5, 'col' => 0],
        ],
        'olmpyrgb-----------Y------------------------------------BGR-PMLOr' => [
            ['fromRow' => 0, 'fromCol' => 5, 'row' => 5, 'col' => 5],
        ],
        'olmpyrgb-------------------Y----------------------------BGR-PMLOo' => [
            ['fromRow' => 0, 'fromCol' => 0, 'row' => 2, 'col' => 0],
        ],
        'olmpyrgb------------P-----------------------------------BGRY-MLOm' => [
            ['fromRow' => 0, 'fromCol' => 2, 'row' => 5, 'col' => 2],
        ],
        'olmpyrgb--------------------P---------------------------BGRY-MLOb' => [
            ['fromRow' => 0, 'fromCol' => 7, 'row' => 2, 'col' => 7],
        ],
        'olmpyrgb------L-----------------------------------------BGRYPM-Ob' => [
            ['fromRow' => 0, 'fromCol' => 7, 'row' => 5, 'col' => 7],
        ],
        'olmpyrgb-----------------------O------------------------BGRYPML-y' => [
            ['fromRow' => 0, 'fromCol' => 4, 'row' => 5, 'col' => 4],
        ],
    ];
    
    $boardString = boardToStringTest($board);
    
    if (isset($library[$boardString])) {
    
        $openingMoves = $library[$boardString];
        
        $openingMoveNumber = rand(0,count($openingMoves)-1);
        
        return [
            'score' => VICTORY,
            'move' => $openingMoves[$openingMoveNumber],
            'depth' => 0,
            'elapsed' => 0,
        ];
    }
    
    return null;
}

function generateOpeningLibrary($board) {
    global $g_maxTime;
    
    $g_maxTime = 120;
    
    $library = [
        'olmpyrgb------------------------------------------------BGRYPMLO-' => [
            ['fromRow' => 7, 'fromCol' => 0, 'row' => 3, 'col' => 0],
            ['fromRow' => 7, 'fromCol' => 1, 'row' => 1, 'col' => 1],
            ['fromRow' => 7, 'fromCol' => 3, 'row' => 2, 'col' => 3],
            ['fromRow' => 7, 'fromCol' => 3, 'row' => 3, 'col' => 3],
            ['fromRow' => 7, 'fromCol' => 4, 'row' => 2, 'col' => 4],
            ['fromRow' => 7, 'fromCol' => 4, 'row' => 3, 'col' => 4],
            ['fromRow' => 7, 'fromCol' => 6, 'row' => 1, 'col' => 6],
            ['fromRow' => 7, 'fromCol' => 7, 'row' => 3, 'col' => 7],
        ],
        'olmpyrgb----------------B--------------------------------GRYPMLOp' => [
            ['fromRow' => 0, 'fromCol' => 3, 'row' => 5, 'col' => 3],
        ],
        'olmpyrgb-G----------------------------------------------B-RYPMLOo' => [
            ['fromRow' => 0, 'fromCol' => 0, 'row' => 5, 'col' => 0],
        ],
        'olmpyrgb-----------Y------------------------------------BGR-PMLOr' => [
            ['fromRow' => 0, 'fromCol' => 5, 'row' => 5, 'col' => 5],
        ],
        'olmpyrgb-------------------Y----------------------------BGR-PMLOo' => [
            ['fromRow' => 0, 'fromCol' => 0, 'row' => 2, 'col' => 0],
        ],
        'olmpyrgb------------P-----------------------------------BGRY-MLOm' => [
            ['fromRow' => 0, 'fromCol' => 2, 'row' => 5, 'col' => 2],
        ],
        'olmpyrgb--------------------P---------------------------BGRY-MLOb' => [
            ['fromRow' => 0, 'fromCol' => 7, 'row' => 2, 'col' => 7],
        ],
        'olmpyrgb------L-----------------------------------------BGRYPM-Ob' => [
            ['fromRow' => 0, 'fromCol' => 7, 'row' => 5, 'col' => 7],
        ],
        'olmpyrgb-----------------------O------------------------BGRYPML-y' => [
            ['fromRow' => 0, 'fromCol' => 4, 'row' => 5, 'col' => 4],
        ],
    ];

    
    foreach ($library['olmpyrgb------------------------------------------------BGRYPMLO-'] as $move) {
        $newBoard = makeMove($board, $move);
        $result = getBestMoveTest($newBoard);
        $library[boardToString($newBoard)] = [$result['move']];
    }

    echo '$library = [' . PHP_EOL;
    foreach ($library as $position => $moves) {
        echo "    '$position' => [" . PHP_EOL;
        foreach ($moves as $move) {
            echo "        ['fromRow' => " . $move['fromRow'] . ", 'fromCol' => " . $move['fromCol'] . ", 'row' => " . $move['row'] . ", 'col' => " . $move['col'] . "]," . PHP_EOL;
        }
        echo "    ]," . PHP_EOL;
    }
    echo "]" . PHP_EOL;
    
}

function getMovesTest($board) {

    $moves = [];

    if ($board['mover'] == 1) {
        $yDir = -1;
        $colourIndex = 'whitelocations';
    } else {
        $yDir = 1;
        $colourIndex = 'blacklocations';
    }

    if ($board['lastColour'] == '-') {
        foreach ($board[$colourIndex] as $location) {
            $col = $location['col'];
            $row = $location['row'];

            foreach ([0,-1,1] as $xDir) {
                for ($y=$row+$yDir, $x=$col+$xDir; isset($board[$y][$x]) && $board[$y][$x] == '-'; $y+=$yDir, $x+=$xDir) {
                    $moves[] = [
                        'fromRow' => $row,
                        'fromCol' => $col,
                        'row' => $y,
                        'col' => $x,
                    ];
                }
            }
        }
    } else {
        $location = $board[$colourIndex][$board['lastColour']];

        $col = $location['col'];
        $row = $location['row'];

        foreach ([0,-1,1] as $xDir) {
            for ($y=$row+$yDir, $x=$col+$xDir; isset($board[$y][$x]) && $board[$y][$x] == '-'; $y+=$yDir, $x+=$xDir) {
                if ($y == 0 || $y == 7) {
                    return [
                        [
                            'fromRow' => $row,
                            'fromCol' => $col,
                            'row' => $y,
                            'col' => $x,
                        ],
                    ];
                }
                $moves[] = [
                    'fromRow' => $row,
                    'fromCol' => $col,
                    'row' => $y,
                    'col' => $x,
                ];
            }
        }
    }

    return $moves;
}