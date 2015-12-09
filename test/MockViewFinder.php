<?php

use mindplay\kisstpl\ViewFinder;

/**
 * Mock view-finder; always "finds" the exact same template
 */
class MockViewFinder implements ViewFinder
{
    private $mock_path;

    public function __construct($mock_path)
    {
        $this->mock_path = $mock_path;
    }

    public function findTemplate($view_model, $type)
    {
        return $this->mock_path;
    }

    public function listSearchPaths($view_model, $type)
    {
        return array($this->mock_path);
    }
}
