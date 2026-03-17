<?php
namespace BM\Core\Exceptions;

class DatabaseException extends \Exception
{
    protected $context;

    public function __construct($message, $context = [], $code = 0, \Throwable $previous = null)
    {
        $this->context = $context;
        parent::__construct($message, $code, $previous);
    }

    public function getContext()
    {
        return $this->context;
    }

    public function toArray()
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'context' => $this->context,
            'file' => $this->getFile(),
            'line' => $this->getLine()
        ];
    }
}
