<?php
$board = readBoard('php://stdin');

$bestMove = null;

$result = negamax($board, 0, -PHP_INT_MAX, PHP_INT_MAX);
echo moveToString($result['move']);

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
    if ($board['mover'] == 1 && strtoupper($piece) != $piece || $board['mover'] == 2 && strtolower($piece) != $piece) {
        throw new Exception('No piece available to move on $row,$col');
    }
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
                    foreach ($squareMoves as $move) {
                        $moves[] = $move;
                    }
                }
            }
        }
    }
    
    return $moves;
}

function evaluate($board) {
    $score = 0;
    for ($row=0; $row<8; $row++) {
        for ($col=0; $col<8; $col++) {
            $piece = $board[$row][$col]['piece'];
            if ($piece != '-') {
                if ($piece <= 'Z') {
                    $score += (7-$col);
                } else {
                    $score -= $col;
                }
            }
        }
    }
    
    return $board['mover'] == 1 ? $score : -$score;
}

function negamax($board, $depth, $alpha, $beta) {
    
    //echo 'Depth = ' . $depth . PHP_EOL;
    $bestMove = null;
    
    if ($depth > 400) {
        throw new Exception('Maximum depth reached');    
    }
    
    if ($depth > 4) {
        return evaluate($board);
    }

    $bestScore = -PHP_INT_MAX;
    
    $moves = getMoves($board);
    
    if (count($moves) > 0) {
        $bestMove = $moves[0];
    } else {
        //echo 'no moves' . PHP_EOL;
    }
    
    foreach ($moves as $move) {
        //echo "Making move " . moveToString($move) . PHP_EOL;
        $newBoard = $board;
        $newBoard[$move['row']][$move['col']]['piece'] = $board[$move['fromRow']][$move['fromCol']]['piece'];
        $newBoard[$move['fromRow']][$move['fromCol']]['piece'] = '-';
        $newBoard['mover'] = $newBoard['mover'] == 1 ? 2 : 1;
        $newBoard['lastColour'] = $newBoard['mover'] == 1 ? strtoupper($board[$move['row']][$move['col']]['colour']) : strtolower($board[$move['row']][$move['col']]['colour']);
        
        //printBoard($newBoard);
        if (($move['row'] == 0 && $board['mover'] == 2) ||
            ($move['row'] == 7 && $board['mover'] == 1)) {
                return [
                    'score' => PHP_INT_MAX,
                    'move' => $move,
                ];
        }
        
        $result = negamax($newBoard, $depth + 1, -$beta, -$alpha);
        
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
