<?php

if (file_exists('test.php')) {
    include 'test.php';
}
    
const VICTORY = 1000000;
const MAX_DEPTH = 50;
const STATUS_GETOUT = -1;

$colours = [];

$globalBest = null;

if (count($argv) > 1 && $argv[1] == 'test') {
    $g_maxTime = 0.5;
    test();
} else {
    $g_maxTime = 8.5;
    $g_evaluationFunction = "evaluate2";
    run();
}

function run() {
    global $colours;
    
    $board = readBoard('php://stdin', $colours);
    
    $result = getBestMove($board);
    echo moveToString($result['move']);
    echo PHP_EOL;
}

function getMoves($board) {
    
    global $globalBest;
    
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
        usort($moves, function ($a, $b) use ($globalBest) {
            if ($globalBest == $a) {
                return -1;
            }
            if ($globalBest == $b) {
                return 1;
            }
            return $a['row'] < $b['row'] ? -1 : ($a['row'] == $b['row'] ? 0 : 1);
        });
    } else {
        usort($moves, function ($a, $b) use ($globalBest) {
            if ($globalBest == $a) {
                return -1;
            }
            if ($globalBest == $b) {
                return 1;
            }
            return $a['row'] > $b['row'] ? -1 : ($a['row'] == $b['row'] ? 0 : 1);
        });
    }
    
    return $moves;
}

function makeMove($board, $move)
{
    global $colours, $zobristValues;

    $colourIndex = $board['mover'] == 1 ? 'whitelocations' : 'blacklocations';
    foreach ($board[$colourIndex] as &$location) {
        if ($location['row'] == $move['fromRow'] && $location['col'] == $move['fromCol']) {
            $location['row'] = $move['row'];
            $location['col'] = $move['col'];
            break;
        }
    }
    
    $piece = $board[$move['fromRow']][$move['fromCol']];
    
    $board[$move['row']][$move['col']] = $piece;
    $board[$move['fromRow']][$move['fromCol']] = '-';
    $board['mover'] = $board['mover'] == 1 ? 2 : 1;
    $board['lastColour'] = $colours[$board['mover']][$move['row']][$move['col']];
        
    return $board;
}

function evaluate($board) {
    
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

function evaluateWrapper($board) {
    global $g_evaluationFunction, $g_nodes;
    
    $g_nodes ++;
    return $g_evaluationFunction($board);
}

function negamax($board, $depth, $alpha, $beta, $maxDepth, $moveStartTime, $maxTime) {
    
    if (microtime(true) - $moveStartTime > $maxTime) {
        return STATUS_GETOUT;    
    }
    
    $bestMove = null;
    
    if ($depth > 400) {
        throw new Exception('Maximum depth reached');    
    }
    
    if ($depth == $maxDepth) {
        return [
            'score' => evaluateWrapper($board),
            'move' => null,
        ];
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
        $result = negamax($newBoard, $depth + 1, -$beta, -$alpha, $maxDepth, $moveStartTime, $maxTime);
        
        if ($result == STATUS_GETOUT) {
            return STATUS_GETOUT;
        }
        
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
    
    // setup lowercase colours for quick lookup
    for ($i=0; $i<8; $i++) {
        for ($j=0; $j<8; $j++) {
            $colours[2][$i][$j] = strtolower($colours[1][$i][$j]);
        }
    }
    
    return $board;
}

function getBestMove($board) {
    
    global $globalBest, $g_maxTime;
    
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
            $globalBest = $result['move'];
            
            $deepestResultSoFar['depth'] = $depth;
            $deepestResultSoFar['elapsed'] = $elapsed;
        
            if ($elapsed > $g_maxTime || $depth == MAX_DEPTH) {
                return $deepestResultSoFar;
            }
        }
    }
}
