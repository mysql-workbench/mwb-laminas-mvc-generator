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
    public $arguments;
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

/*
        $option = array_shift($arguments);
	while($option->next) {
		try {
		}catch (Option('s--source:string[]', $argument)) {
		}catch (Option('t--type:string', $argument)) {
		}catch (Option('o--output?string', $argument)) {
		}catch (Option('e--?bool', $argument)) {
		}catch (Option('h--help', $argument)) {
		}catch (Option('application', $argument)) {
		}
	}

        switch (GetOption($option)) {
            case '-h':
            case '--help':
                $help();
                return 0;
            case OptionCatch('s--source:string[]', $argument):
            case OptionCatch('t--type:string', $argument):
            case OptionCatch('o--output?string', $argument):
            case OptionCatch('e--?bool', $argument):
            case OptionCatch('h--help', $argument):
            case OptionCatch('application', $argument):

		//$generator = new UnitGenerator(getcwd() . '/data/sakila_full.mwb');
		//$count = $generator->generate(getcwd() . '/tmp', True);
		  $count = 0;
		return "\e[32mSuccess\e[0m : ".$count." files generated in ./tmp\n";
            default:
                fwrite(STDERR, 'Unrecognized argument.' . PHP_EOL . PHP_EOL);
                $help(STDERR);
                return 1;
        }
    }
*/
}
