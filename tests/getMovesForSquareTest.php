<?php
require_once 'kamisado.php';

/**
 * getMovesForSquare() test case.
 */
class getMovesForSquareTest extends PHPUnit_Framework_TestCase
{

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp()
    {
        parent::setUp();
        
        // TODO Auto-generated getMovesForSquareTest::setUp()
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown()
    {
        // TODO Auto-generated getMovesForSquareTest::tearDown()
        parent::tearDown();
    }

    /**
     * Constructs the test case.
     */
    public function __construct()
    {
        // TODO Auto-generated constructor
    }
    
    public function testGetMovesRestricted()
    {
        $board = readBoard(dirname(__FILE__) . '/fixtures/board1.txt');
    
        $this->assertEquals('[7 5 6 5][7 5 5 5][7 5 4 5][7 5 3 5][7 5 2 5][7 5 1 5][7 5 6 4][7 5 5 3][7 5 4 2][7 5 3 1][7 5 2 0][7 5 6 6]', testMovesToString(getMoves($board)));
    }

    public function testGetMovesFirstMove()
    {
        $board = readBoard(dirname(__FILE__) . '/fixtures/board1.txt');
        
        $board['lastColour'] = '-'; 
    
        $this->assertEquals('[5 7 4 7][5 7 3 7][5 7 2 7][5 7 1 7][5 7 4 6][5 7 3 5][7 0 6 0][7 0 5 0][7 0 4 0][7 0 3 0][7 0 2 0][7 0 1 0][7 0 6 1][7 0 5 2][7 0 4 3][7 0 3 4][7 0 2 5][7 0 1 6][7 1 6 1][7 1 5 1][7 1 4 1][7 1 3 1][7 1 2 1][7 1 1 1][7 1 6 0][7 1 6 2][7 1 5 3][7 1 4 4][7 1 3 5][7 1 2 6][7 1 1 7][7 2 6 2][7 2 5 2][7 2 4 2][7 2 3 2][7 2 2 2][7 2 1 2][7 2 6 1][7 2 5 0][7 2 6 3][7 2 5 4][7 2 4 5][7 2 3 6][7 2 2 7][7 3 6 3][7 3 5 3][7 3 4 3][7 3 3 3][7 3 2 3][7 3 1 3][7 3 6 2][7 3 5 1][7 3 4 0][7 3 6 4][7 3 5 5][7 3 4 6][7 3 3 7][7 4 6 4][7 4 5 4][7 4 4 4][7 4 3 4][7 4 6 3][7 4 5 2][7 4 4 1][7 4 3 0][7 4 6 5][7 4 5 6][7 4 4 7][7 5 6 5][7 5 5 5][7 5 4 5][7 5 3 5][7 5 2 5][7 5 1 5][7 5 6 4][7 5 5 3][7 5 4 2][7 5 3 1][7 5 2 0][7 5 6 6][7 6 6 6][7 6 5 6][7 6 4 6][7 6 3 6][7 6 2 6][7 6 1 6][7 6 0 6][7 6 6 5][7 6 5 4][7 6 4 3][7 6 3 2][7 6 2 1][7 6 1 0][7 6 6 7]', testMovesToString(getMoves($board)));
    }
    
    public function testGetMovesForSquarePlayer2()
    {
        $board = readBoard(dirname(__FILE__) . '/fixtures/board1.txt');
        
        $board['mover'] = 2;
        $this->assertEquals('[0 0 1 0][0 0 2 0][0 0 3 0][0 0 4 0][0 0 5 0][0 0 6 0][0 0 1 1][0 0 2 2][0 0 3 3][0 0 4 4][0 0 5 5][0 0 6 6][0 0 7 7]', testMovesToString(getMovesForSquare($board, 0,0)));
    }
    
    /**
     * @expectedException Exception
     */
    public function testGetMovesForSquarePlayer1Exception()
    {
        $board = readBoard(dirname(__FILE__) . '/fixtures/board1.txt');
        getMovesForSquare($board, 0,0);
    }
}

