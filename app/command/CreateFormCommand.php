<?php

namespace app\command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use fa;

class CreateFormCommand extends AbstractCommand
{
    public function configure()
    {
        $this
            ->setName('form:create')
            ->setDescription('Create form')
            ->addOption('del', 'd', InputOption::VALUE_NONE, 'Delete all existed form')
            ->addOption('no-create', 'o', InputOption::VALUE_NONE, 'Do not create form')
            ->addArgument('filter', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Entity to exclude out', [])
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->configureIO($input, $output);

        $base = $this->base();
        $finder = new Finder;
        $path = $base->get('ROOTDIR').'app/entity/';
        $forms = $finder->files()->in($path);
        $filter = $this->input->getArgument('filter');
        foreach ($forms as $form) {
            $basename = $form->getBasename('.php');
            if (in_array($basename, $filter)) {
                continue;
            }
            $this->createForm($basename);
        }

        $this->reallyDone('Forms created');
    }

    private function createForm($form)
    {
        $base = $this->base();
        $path = $base->get('ROOTDIR').'app/form/';
        $formName = $form.'Form';

        $file = $path.$formName.'.php';

        if ($this->input->getOption('del') && file_exists($file)) {
            unlink($file);
        }

        if ($this->input->getOption('no-create')) {
            return;
        }

        $content = <<<'CONTENT'
<?php

namespace app\form;

use Base;
use app\core\html\BootstrapHorizontalForm;

class %s extends BootstrapHorizontalForm
{
    protected $ignores = ['id'];
    protected $labels = [];

    protected function init()
    {
        parent::init();

        $base = Base::instance();
        $fields = $this->map->fields(false);
        foreach ($fields as $field) {
            $this->labels[$field] = $base->get('all.'.$field);
        }

        $this->validation
            ->setLabels($this->labels)
            ->add('code', 'unique')
            ->add('name', 'unique')
            ->remove('default', ['required'])
        ;
    }
}

CONTENT;

        return file_exists($file)?null:file_put_contents($file, sprintf($content, $formName));
    }
}
