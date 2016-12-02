<?php

namespace app\command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use app\core\SQLTool;
use fa;

class CreateEntityCommand extends AbstractCommand
{
    public function configure()
    {
        $this
            ->setName('entity:create')
            ->setDescription('Create entity')
            ->addOption('del', 'd', InputOption::VALUE_NONE, 'Delete all existed entity')
            ->addOption('no-create', 'o', InputOption::VALUE_NONE, 'Do not create entity')
            ->addArgument('filter', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Tables to exclude out', [])
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->configureIO($input, $output);

        $tables = SQLTool::instance(fa::db())->tables();
        $filter = $this->input->getArgument('filter');
        foreach ($tables as $table) {
            if (in_array($table, $filter)) {
                continue;
            }
            $this->createEntity($table);
        }

        $this->reallyDone('Entities created');
    }

    private function createEntity($table)
    {
        $base = $this->base();
        $entityName = ucfirst($base->camelcase($table));

        $path = $base->get('ROOTDIR').'app/entity/';
        $file = $path.$entityName.'.php';

        if ($this->input->getOption('del') && file_exists($file)) {
            unlink($file);
        }

        if ($this->input->getOption('no-create')) {
            return;
        }

        $content = <<<'CONTENT'
<?php

namespace app\entity;

use Base;
use app\core\SQLMapper;

class %s extends SQLMapper
{
    public function __construct()
    {
        parent::__construct();
        $this->aftersave(function() {
            if ($this->get('default')) {
                $this->db->exec("update {$this->table} set `default` = 0 where id <> ?", $this->get('id'));
            }
        });
    }

    public function listing()
    {
        $filter = [self::TS_DELETE.' is null'];
        if (isset($_GET['keyword']) && $_GET['keyword']) {
            $filter[0] .= ' and (code like :keyword or name like :keyword)';
            $filter[':keyword'] = '%%'.$_GET['keyword'].'%%';
        }
        $option = ['order'=>'id'];

        return $this->apaginate($filter, $option);
    }
}

CONTENT;

        return file_exists($file)?null:file_put_contents($file, sprintf($content, $entityName));
    }
}
