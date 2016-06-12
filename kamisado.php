<?php
$board = readBoard('php://stdin');

$bestMove = null;

var_dump(getMoves($board)); die;

$result = negamax($board, 0, -PHP_INT_MAX, PHP_INT_MAX);


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
    
    $board['lastColour'] = strtoupper(trim(fgets($f)));
    
    return $board;
}

function run() {
    $board = readBoard('php://stdin');
}

function moveToString($move) {
    return $move['fromRow'] . ' ' . $move['fromCol'] . ' ' . $move['row'] . ' ' . $move['col'];
}

function testMovesToString($moves) {
    $s = '';
    foreach ($moves as $move) {
        $s .= '[' . moveToString($move) . ']';        
    }
    return trim($s);
}

function testGetMovesForSquare($board) {
    $caught = false;
    try {
        getMovesForSquare($board, 0,0);
    } catch (\Exception $e) {
        $caught = true;
    }
    assert($caught);
    $board['mover'] = 2;
    assert(testMovesToString(getMovesForSquare($board, 0,0)) == '1,0 2,0 3,0 4,0 5,0 6,0 1,1 2,2 3,3 4,4 5,5 6,6 7,7');
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
                if ($board['lastColour'] == $square['colour'] || $board['lastColour'] == '-') {
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

function negamax($board, $depth, $alpha, $beta) {
    
    $bestMove = null;
    
    if ($depth > 40) {
        throw new Exception('Maximum depth reached');    
    }

    $bestScore = -PHP_INT_MAX;
    
    $moves = getMoves($board);
    
    foreach ($moves as $move) {
        $newBoard = $board;
        $newBoard[$move['row']][$move['col']]['piece'] = $board[$move['fromRow']][$move['fromCol']]['piece'];
        $newBoard[$move['fromRow']][$move['fromCol']]['piece'] = '-';
        $newBoard['mover'] = $newBoard['mover'] == 1 ? 2 : 1;
        $newBoard['lastColour'] = $board[$move['fromRow']][$move['fromCol']]['colour'];
        
        if (($move['col'] == 0 && $board['mover'] == 2) ||
            ($move['col'] == 7 && $board['mover'] == 1)) {
                return [
                    'score' => PHP_INT_MAX,
                    'move' => $move,
                ];
        }
        
        $result = negamax($newBoard, $depth + 1, -$beta, -$alpha);
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
