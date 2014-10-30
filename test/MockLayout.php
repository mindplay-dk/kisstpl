<?php

class MockLayout
{
    /** @var string body content will be captured to this variable */
    public $body;

    public $EXPECTED_START = '[';
    public $EXPECTED_END = ']';

    public $capture;
    /** @var string additional content will be captured to this variable */

    public $EXPECTED_CAPTURE = 'expected_capture';
}
