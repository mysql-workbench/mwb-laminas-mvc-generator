<?php

declare(strict_types=1);

namespace Mwb\LaminasGenerator;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(name: 'mwb:create-crud')]
class Command_Generator extends Command
{
    protected static $defaultName = 'mwb:create-crud';

    protected function configure()
    {
        $this->setDescription('Generator : Forward Engineer MySQLWorkbench to Laminas mvc')
             ->addArgument('name', InputArgument::REQUIRED, 'Le nom du fichier MySQL Workbench')
             ->addOption('output', null, InputOption::VALUE_REQUIRED, 'Output directory')
             ->addOption('black-list', null, InputOption::VALUE_IS_ARRAY|InputOption::VALUE_OPTIONAL, 'List of tables')
             ->addOption('white-list', null, InputOption::VALUE_IS_ARRAY|InputOption::VALUE_OPTIONAL, 'La list')
            ->addOption(
                'run-dry',
                'r',
                InputOption::VALUE_NONE|InputOption::VALUE_OPTIONAL,
                'Exécute dry'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output):int
    {
	$generator = Null;
	$name = $input->getArgument('name');
	if ('/'==substr($name, 0, 1)) {
		// absolute
		$generator = new UnitGenerator($name);
	        $output->writeln("\e[32mProcessing\e[0m : ".realpath($name)."\n");
	} else {
		// relative
		$generator = new UnitGenerator(realpath(getcwd() . '/' . $name));
	        $output->writeln("\e[32mProcessing\e[0m : ".realpath(getcwd() . '/' . $name)."\n");
	}

	$opt_white_list = $input->getOption('white-list');
	if (!empty($opt_white_list)) {
		$generator->setConfigOptions('whiteTables', $opt_white_list);
	}
	$opt_black_list = $input->getOption('black-list');
	if (!empty($opt_black_list)) {
		$generator->setConfigOptions('blackTables', $opt_black_list);
	}

	$count = 0;
	$opt_output = $input->getOption('output');// default 'tmp/'
	$opt_run_dry = $input->getOption('run-dry');
	if ('/'==substr($opt_output, 0, 1)) {
		// absolute
		$generator->setConfigOptions('output', $opt_output);
		$count = $generator->generate($opt_output, !$opt_run_dry);
	} else {
		// relative
		$generator->setConfigOptions('output', getcwd() . '/' . $opt_output);
		$count = $generator->generate(getcwd() . '/' . $opt_output, !$opt_run_dry);
	}
        $output->writeln("\e[32mSuccess\e[0m : ".$count." files generated in $opt_output\n");
	/*
	*/

	/*
        $name = $input->getArgument('command');
        $output->writeln('Bonjour, ' . $name . ' - ' . $input->getArgument('name'));
        $white_list = $input->getOption('run-dry');
	$output->writeln("".gettype($white_list).(false==$white_list?" false":" true"));
        $white_list = $input->getOption('white-list');
        $output->writeln(' --white-list: '.gettype($white_list).' = '.$white_list);
        $black_list = $input->getOption('black-list');
        $output->writeln(implode(', ', $black_list));
        $output->writeln($input->getOption('output'));
	*/

        return Command::SUCCESS;
    }
/*
*/
/*
	public function initialize(InputInterface $input, OutputInterface $output): void
	{
//$name = $input->getArgument('name');
//$output->writeln('Re-Bonjour, ' . $name);
	   // ...
	}

	public function interact(InputInterface $input, OutputInterface $output): void
	{
	    // ...
	}
*/

	public function __invoke(OutputInterface $output): int
	{
	    // outputs multiple lines to the console (adding "\n" at the end of each line)
	    $output->writeln([
		'User Creator',
		'============',
		'',
	    ]);

	    // the value returned by someMethod() can be an iterator (https://php.net/iterator)
	    // that generates and returns the messages with the 'yield' PHP keyword
	    //$output->writeln($this->someMethod());

	    // outputs a message followed by a "\n"
	    $output->writeln('Whoa!');

	    // outputs a message without adding a "\n" at the end of the line
	    $output->write('You are about to ');
	    $output->write('create a user.');

	    return Command::SUCCESS;
	}
}
