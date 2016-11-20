<?php

namespace app\command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use app\entity\User;

class CreateUserCommand extends AbstractCommand
{
    public function configure()
    {
        $this
            ->setName('user:create')
            ->setDescription('Create user')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->configureIO($input, $output);

        $map = new User;
        $map->set('username', 'admin');
        $map->set('new_password', 'admin');
        $map->set('roles', 'admin');
        $map->save();

        $this->reallyDone('User created');
    }
}
