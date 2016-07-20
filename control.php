<?php

$board['mover'] = 1;
$board['lastColour'] = '-';

$boardText[] = 'olmpyrgb';
$boardText[] = '--------';
$boardText[] = '--------';
$boardText[] = '--------';
$boardText[] = '--------';
$boardText[] = '--------';
$boardText[] = '--------';
$boardText[] = 'BGRYPMLO';

$colours[] = 'OLMPYRGB';
$colours[] = 'ROPGLYBM';
$colours[] = 'GPORMBYL';
$colours[] = 'PMLOBGRY';
$colours[] = 'YRGBOLMP';
$colours[] = 'LYBMROPG';
$colours[] = 'MBYLGPOR';
$colours[] = 'BGRYPMLO';

$row = 0;
foreach ($boardText as $boardTextLine) {
    for ($col=0; $col<8; $col++) {
        $board[$row][$col] = $boardTextLine[$col];
    }
    $row ++;
}

$originalBoard = $board;

$challengerWinsWhite = 0;
$challengerGamesWhite = 0;
$challengerWinsBlack = 0;
$challengerGamesBlack = 0;
$gamesPlayed = 0;

$descriptorspec = array(
    0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
    1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
    2 => array("file", "/tmp/error-output.txt", "a") // stderr is a file to write to
);

for ($gamesPlayed=1; $gamesPlayed<1000; $gamesPlayed++) {
    
    $board = $originalBoard;
    
    if ($gamesPlayed % 2 == 0) {
        $whitePlayer = 'kamisado.php';
        $blackPlayer = 'kamisado_challenger.php';
        $challengerGamesBlack ++;
    } else {
        $whitePlayer = 'kamisado_challenger.php';
        $blackPlayer = 'kamisado.php';
        $challengerGamesWhite ++;
    }
    
    $gameOver = false;
    while (!$gameOver) {
        $player = $board['mover'] == 1 ? $whitePlayer : $blackPlayer;
        
        $secondsPerMove = $gamesPlayed / 100;
        $champion = proc_open('php ' . $player . ' ' . $secondsPerMove, $descriptorspec, $pipes);
        
        fwrite($pipes[0], getBoard($board));
        
        $move = null;
        do {
            $move = fgets($pipes[1]);
        } while ($move == null);
        
        if ($move == '-1') {
            $gameOver = true;
            if ($player == 'kamisado.php') {
                if ($player == $whitePlayer) {
                    $challengerWinsWhite ++;
                } else {
                    $challengerWinsBlack ++;
                }
            }
            echo PHP_EOL;
            echo getBoard($board);
            
            echo 'Games played = ' . $gamesPlayed . PHP_EOL;
            if ($challengerGamesWhite > 0) {
                echo 'Challenger wins white = ' . $challengerWinsWhite . ' (' . number_format(($challengerWinsWhite / $challengerGamesWhite) * 100, 2) . '%)' . PHP_EOL;
            }
            if ($challengerGamesBlack > 0) {
                echo 'Challenger wins black = ' . $challengerWinsBlack . ' (' . number_format(($challengerWinsBlack / $challengerGamesBlack) * 100, 2) . '%)' . PHP_EOL;
            }
            echo 'Challenger wins = ' . ($challengerWinsWhite + $challengerWinsBlack) . ' (' . number_format((($challengerWinsWhite + $challengerWinsBlack) / ($challengerGamesWhite + $challengerGamesBlack)) * 100, 2) . '%)' . PHP_EOL;
        } else {
            echo '.';
            $board = makeMove($board, $move);
        }
        
    }
}

function makeMove($board, $move) {
    global $colours;
    
    $parts = explode(' ', trim($move));
    $fromRow = $parts[0];
    $fromCol = $parts[1];
    $toRow = $parts[2];
    $toCol = $parts[3];
    
    $board[$toRow][$toCol] = $board[$fromRow][$fromCol];
    $board[$fromRow][$fromCol] = '-';
    
    $board['lastColour'] = $colours[$toRow][$toCol];
    $board['mover'] = $board['mover'] == 1 ? 2 : 1;
    if ($board['mover'] == 2) {
        $board['lastColour'] = strtolower($board['lastColour']);
    }
    
    return $board;
}

function getBoard($board) {

    global $colours;
    $s = $board['mover'] . PHP_EOL;

    for ($row=0; $row<8; $row++) {
        for ($col=0; $col<8; $col++) {
            $s .= $colours[$row][$col];
            $s .= $board[$row][$col];
        }
        $s .= PHP_EOL;
    }

    $s .= $board['lastColour'];
    
    $s .= PHP_EOL;
    
    return $s;
}