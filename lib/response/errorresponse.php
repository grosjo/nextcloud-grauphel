<?php
namespace OCA\Grauphel\Lib\Response;

class ErrorResponse extends \OCP\AppFramework\Http\Response
{
    protected $error;

    public function __construct($error)
    {
        $this->setStatus(\OCP\AppFramework\Http::STATUS_BAD_REQUEST);
        $this->addHeader('Content-Type', 'text/plain; charset=utf-8');
        $this->error = $error;
    }

    public function render()
    {
        return $this->error . "\n";
    }
}
?>
