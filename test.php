<?php

function test() {
    global $colours;
    global $g_evaluationFunction, $g_nodes, $g_maxTime;

    $board = readBoard('input.txt', $colours);

    $whiteWins = 0;
    $testWins = 0;
    $draws = 0;
    $originalBoard = $board;

    $totalMoves = 0;
    $totalTime = 0;
    for ($i=0; $i<1000; $i++) {

        $board = $originalBoard;
        echo "GAME " . ($i + 1) . PHP_EOL;
        $g_maxTime = 0.1 + (($i % 5) / 10);

        echo "Max time = " . $g_maxTime . PHP_EOL;

        if ($i % 2 == 0) {
            $whiteEvaluationFunction = "evaluate";
            $blackEvaluationFunction = "evaluateTest";
        } else {
            $whiteEvaluationFunction = "evaluateTest";
            $blackEvaluationFunction = "evaluate";
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
            if ($moveCount < 4) {
                $moves = getMoves($board);
                if (count($moves) == 0) {
                    throw new Exception('No move found for board');
                }

                $r = rand(0, count($moves)-1);
                $move = $moves[$r];
                $depth = 0;
            } else {
                if ($g_evaluationFunction == 'evaluateTest') {
                    $result = getBestMoveTest($board);
//                    echo "Move to play = " . moveToString($result['move']) . PHP_EOL;
//                     $tempResult = getBestMoveDebug($board);
//                     echo "Other function makes " . moveToString($tempResult['move']) . ' at depth ' . $tempResult['depth'] . PHP_EOL;
//                     if (moveToString($tempResult['move']) != moveToString($result['move'])) {
//                         die;
//                     }
                } else {
                    $result = getBestMove($board);
                }

                if ($result['move'] == null) {
                    throw new Exception('No move found for board');
                }

                $move = $result['move'];
                $depth = $result['depth'];
            }

            //echo str_pad($g_evaluationFunction, 13) . " reached depth " . $depth . ", evaluation " . $g_nodes . " positions" . PHP_EOL;
            $board = makeMove($board, $move);
            $moveCount ++;

            if (isGameOver($board, $move)) {
                if ($moveCount > 6) {
                    if ($board['mover'] == 2) {
                        $whiteWins ++;
                        if ($whiteEvaluationFunction == "evaluateTest") {
                            $testWins ++;
                        }
                    } else {
                        if ($blackEvaluationFunction == "evaluateTest") {
                            $testWins ++;
                        }
                    }
                } else {
                    $draws ++;
                }
                break;
            }
        } while (true);

        echo PHP_EOL;

        $totalMoves += $moveCount;
        $totalPlayed = $i + 1;
        $nonDraws = $totalPlayed - $draws;
        echo "Games played = " . $totalPlayed . PHP_EOL;
        echo "Draws = " . $draws . ' (' . number_format(100 * ($draws / $totalPlayed)) . '% of all games)' . PHP_EOL;
        echo "Moves made = " . $moveCount . PHP_EOL;
        if ($nonDraws > 0) {
            echo "White wins = " . $whiteWins . ' (' . number_format(100 * ($whiteWins / $nonDraws), 2) . '% of ' . $nonDraws . ' non draws)' . PHP_EOL;
            echo "Test function wins = " . $testWins . ' (' . number_format(100 * ($testWins / $nonDraws), 2) . '% of ' . $nonDraws . ' non draws)' . PHP_EOL;
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

    foreach ($board['whitelocations'] as $piece => $location) {
        $col = $location['col'];
        $row = $location['row'];

        $found = false;
        foreach ([0,-1,1] as $xDir) {
            if ($found) {
                break;
            }
            for ($y=$row-1, $x=$col+$xDir; isset($board[$y][$x]) && $board[$y][$x] == '-'; $y--, $x+=$xDir) {
                if ($y == 0) {
                    $found = true;
                    if ($currentMover == 1 && $lastColour == $piece) {
                        return VICTORY;
                    }
                    $whiteScore ++;
                    break;
                }
            }
        }
    }

    foreach ($board['blacklocations'] as $piece => $location) {
        $col = $location['col'];
        $row = $location['row'];

        $found = false;
        foreach ([0,-1,1] as $xDir) {
            if ($found) {
                break;
            }
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
        }
    }

    if ($board['mover'] == 1) {
        return $whiteScore;
    } else {
        return -$whiteScore;
    }

}

function evaluateTest2($board) {

    $whiteScore = 0;

    for ($col=0; $col<8; $col++) {
        for ($row=0, $blocked=false; $row<8 && !$blocked; $row++) {
            $piece = $board[$row][$col];
            if ($piece != '-') {
                $blocked = true;
                if ($board[$row][$col] <= 'Z') {
                    $whiteScore ++;
                }
            }
        }
        for ($row=7, $blocked=false; $row>=0 && !$blocked; $row--) {
            $piece = $board[$row][$col];
            if ($piece != '-') {
                $blocked = true;
                if ($board[$row][$col] >= 'a') {
                    $whiteScore --;
                }
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
            
            if ($elapsed > $g_maxTime || $depth == MAX_DEPTH) {
                return $deepestResultSoFar;
            }
            
        }
        
        usort($moves, function($a, $b) {
              return $a['score'] > $b['score'] ? -1 :
                     ($a['score'] < $b['score'] ? 1 : 0);
        });
        
        assert($bestScore == $moves[0]['score']);
        
    }
    
}

function getBestMoveDebug($board) {

    global $g_maxTime;

    $moveStartTime = microtime(true);

    $deepestResultSoFar = null;

    $start = microtime(true);

    for ($depth = 1; $depth <= MAX_DEPTH; $depth ++) {
        $result = negamax($board, 0, -PHP_INT_MAX, PHP_INT_MAX, $depth, $moveStartTime, $g_maxTime);

        if ($result == -1 && $depth == 1) {
            throw new Exception("No move could be found in time");
        }
        if ($result == STATUS_GETOUT) {
            return $deepestResultSoFar;
        } else {
            $elapsed = microtime(true) - $start;
            $deepestResultSoFar = $result;

            $deepestResultSoFar['depth'] = $depth;
            $deepestResultSoFar['elapsed'] = $elapsed;

            if ($elapsed > $g_maxTime || $depth == MAX_DEPTH) {
                return $deepestResultSoFar;
            }
        }
    }
}

