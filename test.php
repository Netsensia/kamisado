<?php

function test() {
    global $colours;
    global $g_evaluationFunction, $g_nodes, $g_maxTime;

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
    for ($i=0; $i<1000; $i++) {

        $board = $originalBoard;
        echo "GAME " . ($i + 1) . PHP_EOL;
        $g_maxTime = 0.1 + (($i % 30) / 10);

        echo "Max time = " . $g_maxTime . PHP_EOL;

        if ($i % 2 == 0) {
            $whiteEvaluationFunction = "evaluate";
            $blackEvaluationFunction = "evaluateTest";
            $blackTestGames ++;
        } else {
            $whiteEvaluationFunction = "evaluateTest";
            $blackEvaluationFunction = "evaluate";
            $whiteTestGames ++;
        }
        
        echo "White evaluation function = $whiteEvaluationFunction" . PHP_EOL;
        echo "Black evaluation function = $blackEvaluationFunction" . PHP_EOL;
        
        $moveCount = 0;
        do {

            if ($moveCount % 2 == 0) {
                $g_evaluationFunction = $whiteEvaluationFunction;
            } else {
                $g_evaluationFunction = $blackEvaluationFunction;
            }

            $t = microtime(true);

            $g_nodes = 0;

            if ($g_evaluationFunction == 'evaluateTest') {
                $g_maxTime = 5 * (0.1 + (($i % 30) / 10));
                
                if ($moveCount == 0) {
                    $result = getOpeningMoveTest();
                } else {
                    $result = getBestMove($board);
                }
            } else {
                $g_maxTime = (0.1 + (($i % 30) / 10));
                
                if ($moveCount == 0) {
                    $result = getOpeningMoveTest();
                } else {
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

function evaluateZero($board) {
    return 0;
}

function evaluateTest($board) {

    $whiteScore = 0;

    $currentMover = $board['mover'];
    $lastColour = $board['lastColour'];
    
    $whiteMoveCount = 0;
    foreach ($board['whitelocations'] as $piece => $location) {
        $found = false;
        foreach ([0,-1,1] as $xDir) {
            for ($y=$location['row']-1, $x=$location['col']+$xDir; isset($board[$y][$x]) && $board[$y][$x] == '-'; $y--, $x+=$xDir) {
                $whiteMoveCount ++;
                if ($y == 0) {
                    if ($currentMover == 1 && $lastColour == $piece) {
                        return VICTORY;
                    }
                    
                    if (!$found) {
                        $whiteScore += 100;
                    }
                    
                    $found = true;
                    break;
                }
            }
        }
    }

    $blackMoveCount = 0;
    foreach ($board['blacklocations'] as $piece => $location) {
        $found = false;
        foreach ([0,-1,1] as $xDir) {
            for ($y=$location['row']+1, $x=$location['col']+$xDir; isset($board[$y][$x]) && $board[$y][$x] == '-'; $y++, $x+=$xDir) {
                $blackMoveCount ++;
                if ($y == 7) {
                    if ($currentMover == 2 && $lastColour == $piece) {
                        return VICTORY;
                    }
                    if (!$found) {
                        $whiteScore -= 100;
                    }
                    $found = true;
                    break;
                }
            }
        }
    }
    
    if ($currentMover == 1) {
        if ($whiteMoveCount == 0) {
            return -VICTORY;
        }
    } else {
        if ($blackMoveCount == 0) {
            return -VICTORY;
        }
    }
    
    $whiteScore += $whiteMoveCount;
    $whiteScore -= $blackMoveCount;
    
    return ($currentMover == 1) ? $whiteScore : -$whiteScore;
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

function getOpeningMoveTest() {
    $openingMoves = [
        ['fromRow' => 7, 'fromCol' => 0, 'row' => 3, 'col' => 0],
        ['fromRow' => 7, 'fromCol' => 1, 'row' => 1, 'col' => 1],
        ['fromRow' => 7, 'fromCol' => 3, 'row' => 2, 'col' => 3],
        ['fromRow' => 7, 'fromCol' => 3, 'row' => 3, 'col' => 3],
        ['fromRow' => 7, 'fromCol' => 4, 'row' => 2, 'col' => 4],
        ['fromRow' => 7, 'fromCol' => 4, 'row' => 3, 'col' => 4],
        ['fromRow' => 7, 'fromCol' => 6, 'row' => 1, 'col' => 6],
        ['fromRow' => 7, 'fromCol' => 7, 'row' => 3, 'col' => 7],
    ];

    $openingMoveNumber = rand(0,count($openingMoves)-1);

    return [
        'score' => VICTORY,
        'move' => $openingMoves[$openingMoveNumber],
        'depth' => 0,
        'elapsed' => 0,
    ];
}