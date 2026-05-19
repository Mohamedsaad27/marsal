<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
#[Signature('make:module-repository {module} {name}')]
#[Description('Create a new Repository inside a module — Usage: make:module-repository Student StudentRepository')]
class MakeModuleRepository extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $module   = Str::studly($this->argument('module'));
        $name     = Str::studly($this->argument('name'));
        $basePath = base_path("App/Modules/{$module}");
 
        if (!File::exists($basePath)) {
            $this->error("Module [{$module}] does not exist. Run make:module first ❌");
            return;
        }
 
        $this->createInterface($module, $name, $basePath);
        $this->createRepository($module, $name, $basePath);
 
        $this->info("Repository + Interface [{$name}] created successfully ✅");
    }
 
    private function createInterface(string $module, string $name, string $basePath): void
    {
        $filePath = "{$basePath}/Domain/Interfaces/{$name}.php";
 
        if (File::exists($filePath)) {
            $this->warn("Interface [{$name}] already exists — skipped ⚠️");
            return;
        }
 
        $content = <<<PHP
        <?php
 
        namespace App\Modules\\{$module}\Domain\Interfaces;
 
        interface {$name}
        {
            public function getAll(): mixed;
 
            public function findById(int \$id): mixed;
 
            public function create(array \$data): mixed;
 
            public function update(int \$id, array \$data): mixed;
 
            public function delete(int \$id): bool;
        }
        PHP;
 
        File::put($filePath, $content);
 
        $this->line("  ✔ Interface created at Domain/Interfaces/{$name}Interface.php");
    }
 
    private function createRepository(string $module, string $name, string $basePath): void
    {
        $filePath = "{$basePath}/Infrastructure/Persistence/Repositories/{$name}.php";
 
        if (File::exists($filePath)) {
            $this->warn("Repository [{$name}] already exists — skipped ⚠️");
            return;
        }
 
        $content = <<<PHP
        <?php
 
        namespace App\Modules\\{$module}\Infrastructure\Persistence\Repositories;
 
        use App\Modules\\{$module}\Domain\Interfaces\\{$name};
        use App\Modules\\{$module}\Infrastructure\Persistence\Models\\{$name};
 
        class {$name} implements {$name}
        {
            public function __construct(
                private readonly {$name} \$model,
            ) {}
 
            public function getAll(): mixed
            {
                return \$this->model->all();
            }
 
            public function findById(int \$id): mixed
            {
                return \$this->model->findOrFail(\$id);
            }
 
            public function create(array \$data): mixed
            {
                return \$this->model->create(\$data);
            }
 
            public function update(int \$id, array \$data): mixed
            {
                \$record = \$this->model->findOrFail(\$id);
                \$record->update(\$data);
 
                return \$record;
            }
 
            public function delete(int \$id): bool
            {
                return \$this->model->findOrFail(\$id)->delete();
            }
        }
        PHP;
 
        File::put($filePath, $content);
 
        $this->line("  ✔ Repository created at Infrastructure/Persistence/Repositories/{$name}.php");
    }
}
