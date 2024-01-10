<?php namespace Starutils\Starcorn\enum;


enum EnumHTTPMethod: string
{
    case get    = 'GET';
    case post   = 'POST';
    case put    = 'PUT';
    case patch  = 'PATCH';
    case delete = 'DELETE';
    case head   = 'HEAD';
    case option = 'OPTION';
    case trace  = 'TRACE';
}
