<?php
class RequestBody
{
    public static function parse()
    {
        return json_decode(file_get_contents('php://input'), true);
    }
}