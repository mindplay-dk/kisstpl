<?php

class MockViewModel
{
    public $value;

    /**
     * @var MockLayout
     */
    public $layout;

    const EXPECTED_VALUE = 'hello_world';

    public function __construct()
    {
        $this->value = self::EXPECTED_VALUE;
        $this->layout = new MockLayout();
    }
}
