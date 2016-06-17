<?php

function test() {
    global $colours;
    global $g_evaluationFunction, $g_nodes, $g_maxTime;

    $board = readBoard('input.txt', $colours);

    $whiteWins = 0;
    $draws = 0;
    $originalBoard = $board;

    $totalMoves = 0;
    $totalTime = 0;
    for ($i=0; $i<1000; $i++) {

        $board = $originalBoard;
        echo "GAME " . ($i + 1) . PHP_EOL;
        $g_maxTime = 0.1 + (($i % 20) / 10);

        echo "Max time = " . $g_maxTime . PHP_EOL;

        $moveCount = 0;
        do {

            if ($moveCount % 2 == 0) {
                $g_evaluationFunction = "evaluateTest";
            } else {
                $g_evaluationFunction = "evaluate";
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
                $result = getBestMove($board);

                if ($result['move'] == null) {
                    throw new Exception('No move found for board');
                }

                $move = $result['move'];
                $depth = $result['depth'];
            }

            echo (($moveCount % 2 == 0) ? "White" : "Black") . " reached depth " . $depth . ", evaluation " . $g_nodes . " positions" . PHP_EOL;
            $board = makeMove($board, $move);
            $moveCount ++;

            if (isGameOver($board, $move)) {
                if ($moveCount > 6) {
                    if ($board['mover'] == 2) {
                        $whiteWins ++;
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
        echo "Draws = " . $draws . ' (' . number_format(100 * ($draws / $totalPlayed)) . '% of all games)' . PHP_EOL;
        echo "Moves made = " . $moveCount . PHP_EOL;
        if ($nonDraws > 0) {
            echo "White wins = " . $whiteWins . ' (' . number_format(100 * ($whiteWins / $nonDraws), 2) . '% of ' . $nonDraws . ' non draws)' . PHP_EOL;
        }
        echo str_pad('', 60, '-') . PHP_EOL;

    }
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


