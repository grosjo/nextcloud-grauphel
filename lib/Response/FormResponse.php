<?php
namespace OCA\Grauphel\Response;

class FormResponse extends \OCP\AppFramework\Http\Response
{
    protected $data;

    public function __construct($data)
    {
        $this->setStatus(\OCP\AppFramework\Http::STATUS_OK);
        $this->addHeader('Content-Type', 'application/x-www-form-urlencoded');
        $this->data = $data;
    }

    public function render()
    {
        return http_build_query($this->data, "", '&');
    }
}
?>
