<?php

namespace gersonalves\laravelBase;

use Illuminate\Console\Command;

class CrudGenerateCommand extends Command
{
    protected $signature = 'gersonalves:resource {name}';
    protected $description = 'Gera uma Resource seguind o padrão do BaseLaravel por Gerson Alves.';

    private mixed $serviceName;
    private bool $makeRepo = false;
    private mixed $modelName;
    private bool $makeController;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->makeQuestions();
        return Command::SUCCESS;
    }

    private function makeQuestions()
    {
        $this->getFileName();
        $this->QuestionCreateRepository();
        if ($this->makeRepo)
            $this->QuestionModelName();

        $this->QuestionCreateController();

        $this->makeFiles();
        $this->info('Arquivos criados com sucesso!');
    }

    private function getFileName()
    {
        $isNotOk = true;
        while ($isNotOk) {
            $serviceName = $this->ask('Informe o nome do Service. Ex: Profile');
            if (!$serviceName || $serviceName == '') {
                $this->alert('É obrigatório informar o nome do arquivo.');
            } else {
                $this->serviceName = $serviceName;
                $isNotOk = false;
            }
        }
    }

    private function QuestionCreateRepository()
    {
        if ($this->confirm('Deseja criar também o Repositório?', true)) {
            $this->makeRepo = true;
        }
    }

    private function QuestionModelName()
    {
        $isNotOk = true;
        while ($isNotOk) {
            $modelName = $this->ask('Qual o nome da Model do Repository? ex: User', $this->serviceName);
            if (!$modelName || $modelName == '') {
                $this->alert('É obrigatório informar o nome da Model.');
            } else {
                $this->modelName = $modelName;
                $isNotOk = false;
            }
        }
    }

    private function makeFiles()
    {
        $this->createService();
        if ($this->makeRepo)
            $this->createRepository();

        if ($this->makeController)
            $this->createController();
    }

    private function createService()
    {
        $fileName = $this->serviceName . 'Service';

        $content = file_get_contents( './stubs/Service.stub');
        $content = str_replace('{{SERVICE_NAME}}', $this->serviceName, $content);

        $fp = fopen( '/app/Services/' . $fileName . '.php', 'w');
        fwrite($fp, $content);
        fclose($fp);
    }

    private function createRepository()
    {
        $fileName = $this->serviceName . 'Repository';

        $content = file_get_contents('./stubs/Repository.stub');
        $content = str_replace('{{MODEL_NAME}}', $this->modelName, $content);

        $fp = fopen(base_path() . '/app/Repositories/' . $fileName . '.php', 'w');
        fwrite($fp, $content);
        fclose($fp);
    }

    private function QuestionCreateController()
    {
        if ($this->confirm('Deseja criar também a Controller?', true)) {
            $this->makeController = true;
        }
    }

    private function createController()
    {
        $fileName = $this->serviceName . 'Controller';

        $content = file_get_contents('./stubs/Controller.stub');
        $content = str_replace('{{SERVICE_NAME}}', $this->serviceName, $content);

        $fp = fopen(base_path() . '/app/Http/Controllers/' . $fileName . '.php', 'w');
        fwrite($fp, $content);
        fclose($fp);
    }
}
