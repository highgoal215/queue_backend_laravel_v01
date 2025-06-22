<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use OpenApi\Generator;
use OpenApi\Util;

class GenerateSwaggerDocs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swagger:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Swagger documentation from annotations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating Swagger documentation...');

        try {
            // Create the docs directory if it doesn't exist
            $docsPath = storage_path('api-docs');
            if (!file_exists($docsPath)) {
                mkdir($docsPath, 0755, true);
            }

            // Generate OpenAPI specification
            $openapi = Generator::scan([app_path()]);

            // Convert to JSON
            $json = $openapi->toJson();

            // Save to file
            $jsonPath = public_path('api-docs.json');
            file_put_contents($jsonPath, $json);

            $this->info('Swagger documentation generated successfully!');
            $this->info('JSON file saved to: ' . $jsonPath);
            $this->info('You can view the documentation at: http://localhost:8000/swagger.html');

        } catch (\Exception $e) {
            $this->error('Failed to generate Swagger documentation: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
} 