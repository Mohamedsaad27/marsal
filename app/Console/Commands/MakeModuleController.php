<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

#[Signature('make:module-controller {module} {name}')]
#[Description('Create a new Controller inside a module — Usage: make:module-controller Teacher Teache')]
class MakeModuleController extends Command
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
 
        $filePath = "{$basePath}/Presentation/Http/Controllers/{$name}.php";
 
        if (File::exists($filePath)) {
            $this->error("Controller [{$name}] already exists ❌");
            return;
        }
 
        $content = <<<PHP
        <?php
 
        namespace App\Modules\\{$module}\Presentation\Http\Controllers;
 
        use Illuminate\Http\JsonResponse;
        use Illuminate\Routing\Controller;
        use App\Modules\\{$module}\Application\UseCases\Create{$name}UseCase;
        use App\Modules\\{$module}\Application\UseCases\Update{$name}UseCase;
        use App\Modules\\{$module}\Application\UseCases\Delete{$name}UseCase;
        use App\Modules\\{$module}\Application\UseCases\GetAll{$name}UseCase;
        use App\Modules\\{$module}\Application\UseCases\GetById{$name}UseCase;
        use App\Modules\\{$module}\Presentation\Http\Requests\Create{$name}Request;
        use App\Modules\\{$module}\Presentation\Http\Requests\Update{$name}Request;
        use App\Modules\\{$module}\Presentation\Http\Resources\\{$name}Resource;
 
        class {$name} extends Controller
        {
            public function __construct(
                private readonly Create{$name}UseCase  \$createUseCase,
                private readonly Update{$name}UseCase  \$updateUseCase,
                private readonly Delete{$name}UseCase  \$deleteUseCase,
                private readonly GetAll{$name}UseCase  \$getAllUseCase,
                private readonly GetById{$name}UseCase \$getByIdUseCase,
            ) {}
 
            public function index(): JsonResponse
            {
                \$result = \$this->getAllUseCase->handle();
 
                return {$name}Resource::collection(\$result)->response();
            }
 
            public function show(int \$id): JsonResponse
            {
                \$result = \$this->getByIdUseCase->handle(\$id);
 
                return (new {$name}Resource(\$result))->response();
            }
 
            public function store(Create{$name}Request \$request): JsonResponse
            {
                \$result = \$this->createUseCase->handle(\$request->toDTO());
 
                return (new {$name}Resource(\$result))->response()->setStatusCode(201);
            }
 
            public function update(Update{$name}Request \$request, int \$id): JsonResponse
            {
                \$result = \$this->updateUseCase->handle(\$id, \$request->toDTO());
 
                return (new {$name}Resource(\$result))->response();
            }
 
            public function destroy(int \$id): JsonResponse
            {
                \$this->deleteUseCase->handle(\$id);
 
                return response()->json(['message' => 'Deleted successfully'], 200);
            }
        }
        PHP;
 
        File::put($filePath, $content);
 
        $this->info("Controller [{$name}] created successfully at Presentation/Http/Controllers/ ✅");
    }
}
