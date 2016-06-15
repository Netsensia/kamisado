<?php
const IS_TEST = true;
const VICTORY = 1000000;
const PENALISE = 100;
const STD_DEPTH = 5;

$colours = [];

if (IS_TEST) {
    $board = readBoard('input.txt', $colours);
    
    $whiteWins = 0;
    $originalBoard = $board;

    $totalMoves = 0;
    $totalTime = 0;
    for ($i=0; $i<1000; $i++) {
        
        $t = microtime(true);
        $board = $originalBoard;
        if ($i % 2 == 0) {
            $board['mover'] = 2;
        }
        echo "GAME $i" . PHP_EOL;
        //printBoard($board);
        
        $moveCount = 0;
        do {
            if ($board['mover'] == 1) {
                $result = negamax($board, 0, -PHP_INT_MAX, PHP_INT_MAX, $board['lastColour'] == '-' ? 3 : 5, "evaluate");
            } else {
                $result = negamax($board, 0, -PHP_INT_MAX, PHP_INT_MAX, $board['lastColour'] == '-' ? 3 : 5, "evaluateRand");
            }
                        
            if ($result['move'] == null) {
                printBoard($board);
                throw new Exception('No move found for board');
            }
            
            $board = makeMove($board, $result['move']);
            
            //printBoard($board);
    //         echo evaluate($board) . PHP_EOL;
            
            $moveCount ++;
            
            if (isGameOver($board, $result['move'])) {
                if ($board['mover'] == 2) {
                    $whiteWins ++;
                }
                break;
            }
        } while (true);
        
        $totalTime += (microtime(true) - $t);
        $totalMoves += $moveCount;
        
        echo "Moves made = " . $moveCount . PHP_EOL;
        echo "White wins = " . number_format(100 * ($whiteWins / ($i+1)), 2) . '%' . PHP_EOL;
        echo "Average move time = " . number_format($totalTime / $totalMoves, 4) . PHP_EOL;
        
    }
    
    die;
}

$board = readBoard('php://stdin', $colours);

$result = negamax($board, 0, -PHP_INT_MAX, PHP_INT_MAX, $board['lastColour'] == '-' ? 1 : STD_DEPTH, "evaluateRand");
echo moveToString($result['move']);

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

function readBoard($input, &$colours) {

    $f = fopen($input, 'r');
    
    $board['mover'] = trim(fgets($f));
    
    for ($row=0; $row<8; $row++) {
        $rowString = fgets($f);
        for ($col=0; $col<8; $col++) {
            $board[$row][$col] = $rowString[$col * 2 + 1];
            $colours[$row][$col] = $rowString[$col * 2];
        }
    }
    
    $board['lastColour'] = trim(fgets($f));
    
    return $board;
}

function run() {
    $board = readBoard('php://stdin');
}

function moveToString($move) {
    return $move['fromRow'] . ' ' . $move['fromCol'] . ' ' . $move['row'] . ' ' . $move['col'];
}

function printBoard($board) {
    global $colours;
    
    echo $board['mover'] . PHP_EOL;
    for ($row=0; $row<8; $row++) {
        for ($col=0; $col<8; $col++) {
            echo $colours[$row][$col];
            echo $board[$row][$col];
        }
        echo PHP_EOL;
    }
    echo $board['lastColour'] . PHP_EOL;
}

function getMovesForSquare($board, $row, $col) {
    
    $piece = $board[$row][$col];

    $moves = [];
    $yDir = $board['mover'] == 1 ? -1 : 1;

    foreach ([0,-1,1] as $xDir) {
        for ($y=$row+$yDir, $x=$col+$xDir; $x<8 && $x>-1 && $y<8 && $y>-1 && $board[$y][$x] == '-'; $y+=$yDir, $x+=$xDir) {
            $moves[] = [
                'fromRow' => $row,
                'fromCol' => $col,
                'row' => $y,
                'col' => $x,
            ];
        }
    }
    
    return $moves;
}

function getMoves($board) {
    $moves = [];
    for ($row = 0; $row < 8; $row ++) {
        for ($col = 0; $col < 8; $col ++) {
            $square = $board[$row][$col];
            if ($square != '-') {
                if ($board['lastColour'] == $square || $board['lastColour'] == '-') {
                    $piece = $square;
                    $mover = $board['mover'];
                    if ($mover == 1 && strtoupper($piece) != $piece || $mover == 2 && strtolower($piece) != $piece) {
                        continue;
                    }
                    $squareMoves = getMovesForSquare($board, $row, $col);
                    
                    $test = count($squareMoves);
                    
                    if ($mover == 1) {
                        usort($squareMoves, function ($a, $b) {
                            if (rand(1,2) == 1) {
                                return rand(-1,1);
                            }
                            return $a['row'] < $b['row'] ? -1 : ($a['row'] == $b['row'] ? 0 : 1);
                        });
                    } else {
                        usort($squareMoves, function ($a, $b) {
                            if (rand(1,2) == 1) {
                                return rand(-1,1);
                            }
                            return $a['row'] > $b['row'] ? -1 : ($a['row'] == $b['row'] ? 0 : 1);
                        });
                    }
                    
                    assert($test == count($squareMoves));
                    
                    foreach ($squareMoves as $move) {
                        $moves[] = $move;
                    }
                }
            }
        }
    }
    
    return $moves;
}

function negamax($board, $depth, $alpha, $beta, $maxDepth, $evaluationFunction) {
    
    //echo 'Depth = ' . $depth . PHP_EOL;
    $bestMove = null;
    
    if ($depth > 400) {
        throw new Exception('Maximum depth reached');    
    }
    
    if ($depth == $maxDepth) {
        return $evaluationFunction($board);
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
        //echo "Making move " . moveToString($move) . PHP_EOL;
        //printBoard($newBoard);
        if ($move['row'] == 0 || $move['row'] == 7) {
            return [
                'score' => VICTORY,
                'move' => $move,
            ];
        }
                
        $newBoard = makeMove($board, $move);
        $result = negamax($newBoard, $depth + 1, -$beta, -$alpha, $maxDepth, $evaluationFunction);
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

function makeMove($board, $move)
{
    global $colours;
    
    $newBoard = $board;
    
    $newBoard[$move['row']][$move['col']] = $board[$move['fromRow']][$move['fromCol']];
    $newBoard[$move['fromRow']][$move['fromCol']] = '-';
    $newBoard['mover'] = $newBoard['mover'] == 1 ? 2 : 1;
    $newBoard['lastColour'] = $newBoard['mover'] == 1 ? strtoupper($colours[$move['row']][$move['col']]) : strtolower($colours[$move['row']][$move['col']]);
    
    return $newBoard;
}

function evaluateRand($board) {
    return 0;
}

function evaluate($board) {

    $score = 0;

    for ($row=0; $row<8; $row++) {
        for ($col=0; $col<8; $col++) {
            $piece = $board[$row][$col];
            if ($piece != '-') {
                if (strtoupper($piece) == $piece) {
                    // penalise if this piece can't move
                    $board['mover'] = 1;
                    $score -= count(getMovesForSquare($board, $row, $col)) == 0 ? PENALISE : 0;
                } else {
                    $board['mover'] = 2;
                    $score += count(getMovesForSquare($board, $row, $col)) == 0 ? PENALISE : 0;
                }
            }
        }
    }
    
    $board['mover'] = 1;
    $whiteMoves = count(getMoves($board));
    $board['mover'] = 2;
    $blackMoves = count(getMoves($board));
    
    $score += $whiteMoves;
    $score -= $blackMoves;
    
    $score = $board['mover'] == 1 ? $score : -$score;
    
    return $score;
}