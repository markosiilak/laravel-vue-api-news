<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\DataController;

class FetchNews extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'news:fetch {--category=all : Specify category or "all" for multiple categories}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch latest news from NewsAPI and save to database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fetching news from NewsAPI...');

        $controller = new DataController();
        $category = $this->option('category');

        if ($category === 'all') {
            // Fetch from multiple categories
            $categories = ['business', 'entertainment', 'health', 'science', 'sports', 'technology'];

            foreach ($categories as $cat) {
                $this->info("Fetching {$cat} news...");
                $controller->getNewsByCategory($cat);
                $this->line("✓ {$cat} news saved");
            }

            // Also fetch general headlines
            $this->info("Fetching general headlines...");
            $controller->getAllNews();
            $this->line("✓ General headlines saved");

        } else {
            // Fetch specific category or general headlines
            if (in_array($category, ['business', 'entertainment', 'health', 'science', 'sports', 'technology'])) {
                $this->info("Fetching {$category} news...");
                $controller->getNewsByCategory($category);
            } else {
                $this->info("Fetching general headlines...");
                $controller->getAllNews();
            }
            $this->line("✓ News saved to database");
        }

        $this->newLine();
        $this->info('✓ News fetch completed successfully!');

        return Command::SUCCESS;
    }
}
