<?php

namespace app\command;

use Base;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

abstract class AbstractCommand extends Command
{
    protected $io;
    protected $input;
    protected $output;

    protected function process($command, $cwd, $throwsError = false)
    {
        $process = new Process($command, $cwd);
        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            if ($throwsError) {
                throw new ProcessFailedException($process);
            } else {
                return false;
            }
        }

        return $process;
    }

    protected function configureIO(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->input = $input;
        $this->output = $output;

        return $this;
    }

    protected function reallyDone($message)
    {
        $this->io->success($message);
    }

    protected function info($info, $line = 1)
    {
        $this->output->write("<fg=yellow>{$info}...</>".str_repeat(PHP_EOL, $line));
    }

    protected function error($error, $line = 1)
    {
        $this->output->write("<fg=red>$error</>".str_repeat(PHP_EOL, $line));
    }

    protected function done($line = 1)
    {
        $this->output->write('<fg=green>done</>'.str_repeat(PHP_EOL, $line));
    }

    protected function base()
    {
        return Base::instance();
    }
}
