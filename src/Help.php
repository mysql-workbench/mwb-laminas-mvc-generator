<?php

declare(strict_types=1);

namespace Mwb\LaminasGenerator;

use function fwrite;
use function is_resource;

/** @final */
class Help
{
    private string $message = <<<EOH
Select generator module.

Usage:

mwb-generator [-h|--help] <module>

--help|-h                    Print this usage message.
<module>=application         Generate `Application` module from *.mwb.

To generate Application module, the following file MUST exist:

- <root>/data/sakila_full.mwb

EOH;

    /**
     * Emit the help message.
     *
     * @param null|resource $stream Defaults to STDOUT
     */
    public function __invoke($stream = null)
    {
        if (! is_resource($stream)) {
            echo $this->message;
            return;
        }

        fwrite($stream, $this->message);
    }
}
