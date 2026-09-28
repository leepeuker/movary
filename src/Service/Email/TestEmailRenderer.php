<?php declare(strict_types=1);

namespace Movary\Service\Email;

class TestEmailRenderer extends AbstractEmailRenderer
{
    public function render() : string
    {
        return $this->renderTemplate('test');
    }
}
