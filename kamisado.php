<?php

if (file_exists('test.php')) {
    include 'test.php';
}
    
const VICTORY = 1000000;
const MAX_DEPTH = 50;
const STATUS_GETOUT = -1;

$g_evaluationFunction = "evaluate";

$colours = [];

if (count($argv) > 1 && $argv[1] == 'test') {
    test();
} else {
    $g_maxTime = 8.5;
    run();
}

function run() {
    global $colours;
    
    $board = readBoard('php://stdin', $colours);
    
    $result = getOpeningMove($board);
    if ($result == null) {
        $result = getBestMove($board);
    }
    echo moveToString($result['move']);
    echo PHP_EOL;
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

function negamax($board, $depth, $alpha, $beta, $maxDepth, $moveStartTime, $maxTime) {
    
    global $g_evaluationFunction, $g_getMovesFunction;
    
    if (microtime(true) - $moveStartTime > $maxTime) {
        return STATUS_GETOUT;    
    }
    
    $bestMove = null;
    
    if ($depth > 400) {
        throw new Exception('Maximum depth reached');    
    }
    
    if ($depth == $maxDepth) {
        global $g_nodes;
        
        $g_nodes ++;
        
        return [
            'score' => $g_evaluationFunction($board),
            'move' => null,
        ];
    }

    $bestScore = -PHP_INT_MAX;
    
    $moves = $g_getMovesFunction($board);
    
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
    
    global $g_maxTime, $g_getMovesFunction;

    $moveStartTime = microtime(true);

    $deepestResultSoFar = null;

    $start = microtime(true);
    
    $moves = $g_getMovesFunction($board);
    
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

function boardToString($board) {
    $s = '';
    for ($i=0; $i<8; $i++) {
        for ($j=0; $j<8; $j++) {
            $s .= $board[$i][$j];
        }
    }

    $s .= $board['lastColour'];

    return $s;
}

function getOpeningMove($board) {
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
    
    $boardString = boardToString($board);
    
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