<?php

namespace App\Console\Commands;

use App\Services\CrudGenerator\CrudGenerator;
use App\Services\CrudGenerator\CrudGeneratorException;
use App\Services\CrudGenerator\FileOperation;
use App\Services\CrudGenerator\ModuleSpec;
use Illuminate\Console\Command;

class CrudMakeCommand extends Command
{
    protected $signature = 'crud:make
        {--spec= : Path to a JSON module specification}
        {--dry-run : Show the files that would be created or modified without writing}
        {--migrate : Run the new migrations after generation}
        {--seed : Run PermissionSeeder after generation}';

    protected $description = 'Generate a translatable admin CRUD module following the Blog conventions';

    public function handle()
    {
        if (!config('crud-generator.enabled')) {
            $this->error('The CRUD generator is disabled (CRUD_GENERATOR_ENABLED).');

            return self::FAILURE;
        }

        $path = (string) $this->option('spec');

        if ($path === '' || !is_file($path)) {
            $this->error('Provide an existing JSON file with --spec=path.json');

            return self::FAILURE;
        }

        $data = json_decode(file_get_contents($path), true);

        if (!is_array($data)) {
            $this->error('The spec file is not valid JSON.');

            return self::FAILURE;
        }

        $spec = ModuleSpec::fromArray($data);
        $generator = CrudGenerator::make();

        if ($this->option('dry-run')) {
            return $this->dryRun($generator, $spec);
        }

        try {
            $result = $generator->generate($spec, (bool) $this->option('migrate'), (bool) $this->option('seed'));
        } catch (CrudGeneratorException $e) {
            $this->printErrors($e->errors ?: ['generator' => [$e->getMessage()]]);

            return self::FAILURE;
        }

        foreach ($result['created'] as $file) {
            $this->line("<info>created</info>  {$file}");
        }

        foreach ($result['modified'] as $file) {
            $this->line("<comment>modified</comment> {$file}");
        }

        foreach ($result['artisan'] as $command => $run) {
            $this->newLine();
            $this->line($run['successful'] ? "<info>{$command}</info>" : "<error>{$command} failed</error>");
            $this->line($run['output']);
        }

        $this->newLine();
        $this->info("Module [{$spec->name}] generated.");

        return self::SUCCESS;
    }

    private function dryRun(CrudGenerator $generator, ModuleSpec $spec): int
    {
        $plan = $generator->plan($spec);

        if ($plan['errors'] || $plan['conflicts']) {
            $this->printErrors($plan['errors'] + ($plan['conflicts'] ? ['files' => $plan['conflicts']] : []));

            return self::FAILURE;
        }

        foreach ($plan['preview'] as $item) {
            $operation = $item['operation'];

            if ($operation->type === FileOperation::CREATE) {
                $this->line("<info>create</info> {$operation->path}");

                continue;
            }

            $this->line("<comment>modify</comment> {$operation->path}");

            foreach ($item['diff'] as $line) {
                $prefix = ['add' => '+', 'remove' => '-', 'context' => ' '][$line['type']];
                $this->line(sprintf('  %5d %s %s', $line['line'], $prefix, $line['text']));
            }
        }

        $this->newLine();
        $this->info('Dry run: nothing was written.');

        return self::SUCCESS;
    }

    private function printErrors(array $errors): void
    {
        foreach ($errors as $key => $messages) {
            foreach ((array) $messages as $message) {
                $this->error("{$key}: {$message}");
            }
        }
    }
}
