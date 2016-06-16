<?php
const VICTORY = 1000000;
const PENALISE = 100;
const STD_DEPTH = 8;

$colours = [];

test();

function run() {
    $board = readBoard('php://stdin', $colours);
    $result = negamax($board, 0, -PHP_INT_MAX, PHP_INT_MAX, $board['lastColour'] == '-' ? 3 : STD_DEPTH);
    echo moveToString($result['move']);
    echo PHP_EOL;
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

function getMoves($board) {
    
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
                $moves[] = [
                    'fromRow' => $row,
                    'fromCol' => $col,
                    'row' => $y,
                    'col' => $x,
                ];
            }
        }
    }

    if ($board['mover'] == 1) {
        usort($moves, function ($a, $b) {
            return $a['row'] < $b['row'] ? -1 : ($a['row'] == $b['row'] ? 0 : 1);
        });
    } else {
        usort($moves, function ($a, $b) {
            return $a['row'] > $b['row'] ? -1 : ($a['row'] == $b['row'] ? 0 : 1);
        });
    }
    
    return $moves;
}

function makeMove($board, $move)
{
    global $colours;

    $colourIndex = $board['mover'] == 1 ? 'whitelocations' : 'blacklocations';
    foreach ($board[$colourIndex] as &$location) {
        if ($location['row'] == $move['fromRow'] && $location['col'] == $move['fromCol']) {
            $location['row'] = $move['row'];
            $location['col'] = $move['col'];
        }
    }
    
    $board[$move['row']][$move['col']] = $board[$move['fromRow']][$move['fromCol']];
    $board[$move['fromRow']][$move['fromCol']] = '-';
    $board['mover'] = $board['mover'] == 1 ? 2 : 1;
    $board['lastColour'] = $colours[$board['mover']][$move['row']][$move['col']];
        
    return $board;
}

function negamax($board, $depth, $alpha, $beta, $maxDepth) {
    
    $bestMove = null;
    
    if ($depth > 400) {
        throw new Exception('Maximum depth reached');    
    }
    
    if ($depth == $maxDepth) {
        return 0;
    }

    $bestScore = -PHP_INT_MAX;
    
    $moves = getMoves($board);
    
    if (count($moves) == 0) {
        return [
            'score' => -VICTORY,
            'move' => null,
        ];
    }
    
    foreach ($moves as $move) {
        if ($move['row'] == 0 || $move['row'] == 7) {
            return [
                'score' => VICTORY,
                'move' => $move,
            ];
        }
                
        $newBoard = makeMove($board, $move);
        $result = negamax($newBoard, $depth + 1, -$beta, -$alpha, $maxDepth);
        $score = -$result['score'];

        if ($score > $bestScore) {
            $bestMove = $move;
            $bestScore = $score;
        }

        $alpha = max($alpha, $score);
        
        if ($alpha > $beta) {
            break;
        }
    }
    
    return [
        'score' => $bestScore,
        'move' => $bestMove,
    ];
}

function moveToString($move) {
    return $move['fromRow'] . ' ' . $move['fromCol'] . ' ' . $move['row'] . ' ' . $move['col'];
}

function readBoard($input, &$colours) {

    $f = fopen($input, 'r');

    $board['mover'] = trim(fgets($f));

    for ($row=0; $row<8; $row++) {
        $rowString = fgets($f);
        for ($col=0; $col<8; $col++) {
            $piece = $rowString[$col * 2 + 1];
            $colour = $rowString[$col * 2];
            $board[$row][$col] = $piece;
            $colours[1][$row][$col] = $colour;
            if ($piece != '-') {
                $player = $piece == strtoupper($piece) ? 'white' : 'black';
                $board[$player . 'locations'][$piece] = [
                    'row' => $row,
                    'col' => $col,
                ];
            }
        }
    }

    $board['lastColour'] = trim(fgets($f));

    return $board;
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

function test() {
    global $colours;

    $board = readBoard('input.txt', $colours);
    
    // setup lowercase colours for quick lookup
    for ($i=0; $i<8; $i++) {
        for ($j=0; $j<8; $j++) {
            $colours[2][$i][$j] = strtolower($colours[1][$i][$j]);
        }
    }

    $whiteWins = 0;
    $originalBoard = $board;

    $totalMoves = 0;
    $totalTime = 0;
    for ($i=0; $i<1; $i++) {

        $t = microtime(true);
        $board = $originalBoard;
        echo "GAME $i" . PHP_EOL;

        $moveCount = 0;
        do {
            if ($board['mover'] == 1) {
                $result = negamax($board, 0, -PHP_INT_MAX, PHP_INT_MAX, $board['lastColour'] == '-' ? 3 : STD_DEPTH);
            } else {
                $result = negamax($board, 0, -PHP_INT_MAX, PHP_INT_MAX, $board['lastColour'] == '-' ? 3 : STD_DEPTH);
            }

            if ($result['move'] == null) {
                printBoard($board);
                throw new Exception('No move found for board');
            }

            $board = makeMove($board, $result['move']);

            $moveCount ++;
            echo '.';

            if (isGameOver($board, $result['move'])) {
                if ($board['mover'] == 2) {
                    $whiteWins ++;
                }
                break;
            }
        } while (true);

        echo PHP_EOL;

        $totalTime += (microtime(true) - $t);
        $totalMoves += $moveCount;

        echo "Moves made = " . $moveCount . PHP_EOL;
        echo "White wins = " . number_format(100 * ($whiteWins / ($i+1)), 2) . '%' . PHP_EOL;
        echo "Average move time = " . number_format($totalTime / $totalMoves, 4) . PHP_EOL;

    }
}