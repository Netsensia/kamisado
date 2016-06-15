<?php
$board = readBoard('php://stdin');

const VICTORY = 1000000;
const PENALISE = 5;
// printBoard($board);
// echo evaluate($board) . PHP_EOL;
// die;

$whiteWins = 0;
$originalBoard = $board;

for ($i=0; $i<1000; $i++) {
    
    $board = $originalBoard;
    if ($i % 2 == 0) {
        $board['mover'] = 2;
    }
    echo "GAME $i" . PHP_EOL;
    
    $moveCount = 0;
    do {
        if ($board['mover'] == 1) {
            $result = negamax($board, 0, -PHP_INT_MAX, PHP_INT_MAX, $board['lastColour'] == '-' ? 1 : 1, "evaluateRand");
        } else {
            $result = negamax($board, 0, -PHP_INT_MAX, PHP_INT_MAX, $board['lastColour'] == '-' ? 1 : 4, "evaluateRand");
        }
        
        
        if ($result['move'] == null) {
            printBoard($board);
            throw new Exception('No move found for board');
        }
        
        $board = makeMove($board, $result['move']);
//         printBoard($board);
//         echo evaluate($board) . PHP_EOL;
        
        $moveCount ++;
        
        if (isGameOver($board, $result['move'])) {
            if ($board['mover'] == 2) {
                $whiteWins ++;
            }
            break;
        }
        
    } while (true);
    
    echo "Moves made = " . $moveCount . PHP_EOL;
    echo "White wins = " . number_format(100 * ($whiteWins / ($i+1)), 2) . '%' . PHP_EOL;
    
}

die;

$result = negamax($board, 0, -PHP_INT_MAX, PHP_INT_MAX, 5, "evaluate");
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

function readBoard($input) {

    $f = fopen($input, 'r');
    
    $board['mover'] = trim(fgets($f));
    
    for ($row=0; $row<8; $row++) {
        $rowString = fgets($f);
        for ($col=0; $col<8; $col++) {
            $board[$row][$col] = [
                'colour' => $rowString[$col * 2],
                'piece' => $rowString[$col * 2 + 1],
            ];
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
    echo $board['mover'] . PHP_EOL;
    for ($row=0; $row<8; $row++) {
        for ($col=0; $col<8; $col++) {
            echo $board[$row][$col]['colour'];
            echo $board[$row][$col]['piece'];
        }
        echo PHP_EOL;
    }
    echo $board['lastColour'] . PHP_EOL;
}

function getMovesForSquare($board, $row, $col) {
    
    $piece = $board[$row][$col]['piece'];

    $moves = [];
    $yDir = $board['mover'] == 1 ? -1 : 1;

    foreach ([0,-1,1] as $xDir) {
        for ($y=$row+$yDir, $x=$col+$xDir; $x<8 && $x>-1 && $y<8 && $y>-1 && $board[$y][$x]['piece'] == '-'; $y+=$yDir, $x+=$xDir) {
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
            if ($square['piece'] != '-') {
                if ($board['lastColour'] == $square['piece'] || $board['lastColour'] == '-') {
                    $piece = $square['piece'];
                    if ($board['mover'] == 1 && strtoupper($piece) != $piece || $board['mover'] == 2 && strtolower($piece) != $piece) {
                        continue;
                    }
                    $squareMoves = getMovesForSquare($board, $row, $col);
                    usort($squareMoves, function ($a, $b) {
                        return rand(-1,1); 
                    });
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
        
        $newBoard = makeMove($board, $move);
        
        //printBoard($newBoard);
        if ($move['row'] == 0 || $move['row'] == 7) {
                return [
                    'score' => VICTORY,
                    'move' => $move,
                ];
        }
        
        $result = negamax($newBoard, $depth + 1, -$beta, -$alpha, $maxDepth, $evaluationFunction);
        
        //echo "We're back at depth " . $depth . PHP_EOL;
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
    $newBoard = $board;
    
    $newBoard[$move['row']][$move['col']]['piece'] = $board[$move['fromRow']][$move['fromCol']]['piece'];
    $newBoard[$move['fromRow']][$move['fromCol']]['piece'] = '-';
    $newBoard['mover'] = $newBoard['mover'] == 1 ? 2 : 1;
    $newBoard['lastColour'] = $newBoard['mover'] == 1 ? strtoupper($board[$move['row']][$move['col']]['colour']) : strtolower($board[$move['row']][$move['col']]['colour']);
    
    return $newBoard;
}

function evaluateRand($board) {
    return rand(-10,10);
}

function evaluate($board) {

    $score = 0;

    for ($row=0; $row<8; $row++) {
        for ($col=0; $col<8; $col++) {
            $piece = $board[$row][$col]['piece'];
            if ($piece != '-') {
                if (strtoupper($piece) == $piece) {
                    // penalise if this piece can't move
                    $board['mover'] = 1;
                    $score -= count(getMovesForSquare($board, $row, $col)) == 0 ? PENALISE : 0;
                    // bonus for available moves
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