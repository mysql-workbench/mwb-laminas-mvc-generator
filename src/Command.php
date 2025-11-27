<?php

declare(strict_types=1);

namespace Mwb\LaminasGenerator;

use function array_shift;
use function count;
use function fwrite;

use const PHP_EOL;
use const STDERR;

/** @final */
class Command
{
    /**
     * Handle the CLI arguments.
     *
     * @return int
     */
    public function __invoke(array $arguments)
    {
        $help = new Help();

        // Called without arguments
        if (count($arguments) < 1) {
            fwrite(STDERR, 'No arguments provided.' . PHP_EOL . PHP_EOL);
            $help(STDERR);
            return 1;
        }

        $argument = array_shift($arguments);

        switch ($argument) {
            case '-h':
            case '--help':
                $help();
                return 0;
            case 'application':
		$generator = new UnitGenerator(getcwd() . '/data/sakila_full.mwb');
		$generator->generate(getcwd() . '/tmp', True);
		return "\e[32mSuccess\e[0m : 31 files generated in ./tmp\n";
            default:
                fwrite(STDERR, 'Unrecognized argument.' . PHP_EOL . PHP_EOL);
                $help(STDERR);
                return 1;
        }
    }
}
