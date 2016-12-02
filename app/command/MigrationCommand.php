<?php

namespace app\command;

use fa;
use app\core\SQLTool;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MigrationCommand extends AbstractCommand
{
    public function configure()
    {
        $this
            ->setName('migrate')
            ->setDescription('Perform migration')
            ->addArgument('mode', InputArgument::REQUIRED, 'Migration mode')
            ->addArgument('scripts', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Script to performed')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->configureIO($input, $output);
        set_time_limit(600);

        $tool = new SQLTool(fa::db());
        $migrationDir = $this->base()->get('APPDIR').'migration/'.$input->getArgument('mode').'/';
        foreach ($input->getArgument('scripts') as $script) {
            $tool->import($migrationDir.$script.'.sql');
        }

        $this->reallyDone('Migration has been performed');
    }
}
