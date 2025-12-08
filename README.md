# Laravel + Nuxt 3 News Portal

This project demonstrates a full-stack news portal application with Laravel (backend) and Nuxt 3 (frontend) that fetches real-time news from NewsAPI.

## Project Structure

```
laravel/
├── backend/      # Laravel PHP backend
└── frontend/     # Nuxt 3 (Vue 3) frontend
```

## How This Application Was Built

### Step 1: Create Laravel Backend

1. **Initialize Laravel Project**
   ```bash
   composer create-project laravel/laravel backend
   cd backend
   ```

2. **Install API Routes**
   ```bash
   php artisan install:api
   ```
   This creates the `routes/api.php` file and installs Laravel Sanctum.

3. **Configure CORS** (`backend/config/cors.php`)
   - Create `config/cors.php` to allow frontend requests
   - Set `'allowed_origins' => ['*']` for development

4. **Create News Controller** (`backend/app/Http/Controllers/DataController.php`)
   ```bash
   php artisan make:controller DataController
   ```
   - Location: `app/Http/Controllers/DataController.php`
   - Implements methods to fetch news from NewsAPI
   - Uses `Http` facade for API calls
   - Implements smart caching with `Cache` facade

5. **Add API Routes** (`backend/routes/api.php`)
   - Edit `routes/api.php`
   - Define routes for news endpoints:
     - `GET /api/news` - Latest headlines
     - `GET /api/news/category/{category}` - Category news
     - `GET /api/news/search` - Search news
     - `POST /api/news/clear-cache` - Clear cache

6. **Configure Environment** (`backend/.env`)
   - Add `NEWS_API_KEY` to `.env` file
   - Register for free API key at [newsapi.org/register](https://newsapi.org/register)

7. **Implement Caching System** (`backend/app/Http/Controllers/DataController.php`)
   - Use Laravel's `Cache::get()` and `Cache::put()`
   - Set 15-minute cache duration
   - Create unique cache keys for each endpoint
   - Return `cached: true/false` in API responses

### Step 2: Create Nuxt 3 Frontend

1. **Initialize Nuxt Project**
   ```bash
   npx nuxi@latest init frontend
   cd frontend
   npm install
   ```

2. **Configure API Base URL** (`frontend/nuxt.config.ts`, `frontend/.env`)
   - Edit `nuxt.config.ts`
   - Add `runtimeConfig` for API base URL
   - Create `.env` file with `NUXT_PUBLIC_API_BASE=http://localhost:8000/api`

3. **Install Vuetify 3**
   ```bash
   npm install -D vuetify vite-plugin-vuetify @mdi/font
   ```

4. **Configure Vuetify** (`frontend/nuxt.config.ts`, `frontend/plugins/vuetify.ts`)
   - Update `nuxt.config.ts` with Vuetify plugin configuration
   - Create `plugins/vuetify.ts` for Vuetify initialization
   - Import Material Design Icons CSS
   - Configure theme colors

5. **Build Main App Component** (`frontend/app.vue`)
   - Edit `app.vue`
   - Use Vuetify components (`v-app`, `v-app-bar`, `v-card`, etc.)
   - Implement `useFetch` composable to call Laravel API
   - Add category filter dropdown
   - Display news in responsive grid
   - Show loading states and error handling
   - Display cache indicator badge

6. **Remove Default Template**
   - Delete `app/app.vue` (default welcome page)
   - Keep root `app.vue` as main component

### Step 3: Database Integration

1. **Create Database Migration** (`backend/database/migrations/..._create_news_articles_table.php`)
   ```bash
   php artisan make:migration create_news_articles_table
   ```
   - Define table schema with columns for all article fields
   - Include source, author, title, description, URL, image, published date, content, category
   - Make URL unique to prevent duplicates

2. **Create NewsArticle Model** (`backend/app/Models/NewsArticle.php`)
   ```bash
   php artisan make:model NewsArticle
   ```
   - Define fillable fields for mass assignment
   - Add `published_at` to casts as datetime

3. **Run Migration**
   ```bash
   php artisan migrate
   ```

4. **Update Controller to Save Data** (`backend/app/Http/Controllers/DataController.php`)
   - Add `saveArticlesToDatabase()` method
   - Add `downloadImage()` method for local image storage
   - Use `updateOrCreate()` to prevent duplicates (match by URL)
   - Download images from external URLs to `storage/app/public/news-images/`
   - Call after successful API fetch in all news methods
   - Handle save errors gracefully

5. **Setup Storage Link**
   ```bash
   php artisan storage:link
   ```
   - Creates symbolic link from `public/storage` to `storage/app/public`
   - Enables public access to downloaded images

6. **Add Database Endpoints** (`backend/routes/api.php`)
   - `GET /api/news/saved` - Get articles from database
   - `GET /api/news/stats` - Get database statistics
   - `POST /api/news/import` - Import new news from all categories

### Step 4: Automated News Importing

1. **Create Artisan Command** (`backend/app/Console/Commands/FetchNews.php`)
   ```bash
   php artisan make:command FetchNews
   ```
   - Command signature: `news:fetch {--category=all}`
   - Fetches news from all categories or specific category
   - Displays progress and results

2. **Add Scheduled Task** (`backend/bootstrap/app.php`)
   - Configure `withSchedule()` method
   - Schedule `news:fetch --category=all` to run hourly
   - Use `withoutOverlapping()` to prevent concurrent runs
   - Run in background mode

3. **Manual Import Endpoint** (`backend/app/Http/Controllers/DataController.php`)
   - `importNews()` method
   - Fetches from all 6 categories + general headlines
   - Returns count of newly imported articles
   - Preserves existing articles (no duplicates)

### Step 5: Key Features Implementation

1. **News Fetching** (`backend/app/Http/Controllers/DataController.php`)
   - Backend: `Http::get()` to call NewsAPI
   - Parse response and return formatted JSON
   - Automatically save to database
   - Handle errors with try-catch

2. **Caching System** (`backend/app/Http/Controllers/DataController.php`)
   - Check cache before API call: `Cache::get($cacheKey)`
   - Store results: `Cache::put($cacheKey, $data, now()->addMinutes(15))`
   - Use MD5 hash for search query cache keys
   - Separate cache for each category

3. **Database Storage** (`backend/app/Http/Controllers/DataController.php`)
   - Save all fetched articles to `news_articles` table
   - Use `updateOrCreate()` to avoid duplicates
   - Store category information with each article
   - Continue on individual save errors

4. **Frontend Data Fetching** (`frontend/app.vue`)
   - Use `useFetch()` to fetch saved news from database
   - Watch category changes to refetch data
   - Display all saved articles (up to 1000)
   - Show real-time article count

5. **UI Components** (`frontend/app.vue`)
   - **App Bar**: Shows title, icon, article count, and import button
   - **Import Button**: One-click import with loading state and success notification
   - **Category Filter**: `v-select` with all news categories
   - **Database Stats**: Show total saved articles count
   - **News Cards**: `v-card` with locally stored images, title, description, metadata
   - **Image Handler**: `getImageUrl()` function to properly load local images
   - **Loading State**: `v-progress-circular` spinner
   - **Error State**: `v-alert` with helpful messages
   - **Success Snackbar**: Shows import results

6. **Responsive Design** (`frontend/app.vue`)
   - Vuetify's grid system (`v-container`, `v-row`, `v-col`)
   - Breakpoints: 4 columns on large screens, 1 on mobile
   - Card hover effects with elevation changes

### Step 5: Testing & Refinement

1. **Start Backend**
   ```bash
   cd backend
   php artisan serve
   ```

2. **Start Frontend**
   ```bash
   cd frontend
   npm run dev
   ```

3. **Test Features**
   - Click "Import New News" button to fetch articles
   - Watch as articles are downloaded with images
   - View imported articles displayed from database
   - Switch categories to filter saved articles
   - Check article count badge updates
   - Verify images load from local storage
   - Test manual import: `php artisan news:fetch --category=all`
   - Optionally start scheduler: `php artisan schedule:work`

### Step 6: Documentation

1. **Create Comprehensive README**
   - Setup instructions
   - API endpoints documentation
   - Features list
   - Troubleshooting guide
   - Caching system explanation
   - Customization options

## Features

- **Laravel Backend**: RESTful API that fetches news from NewsAPI.org with smart caching
- **Database Storage**: Automatically saves all fetched news articles to SQLite database
- **Local Image Storage**: Downloads and stores news images locally on your server
- **One-Click Import**: Import new news articles with a single button click
- **Automated Updates**: Scheduled hourly news fetching from all categories
- **Nuxt 3 Frontend**: Vue 3 application with Vuetify Material Design UI
- **News Categories**: Filter by Business, Entertainment, Health, Science, Sports, Technology
- **Real-time News**: Get latest headlines from 150,000+ sources worldwide
- **Smart Caching**: 15-minute cache to minimize API requests and stay within free tier limits
- **Database Statistics**: View total saved articles and category distribution
- **Vuetify UI**: Modern Material Design interface with beautiful components
- **Responsive Design**: Adapts seamlessly to all screen sizes
- **Fast Performance**: Display up to 1000 articles instantly from database

## Setup Instructions

### 1. Get Your Free NewsAPI Key

1. Visit [https://newsapi.org/register](https://newsapi.org/register)
2. Create a free account
3. Copy your API key

### 2. Backend (Laravel) Setup

```bash
cd backend

# Update .env file with your NewsAPI key
# Open .env and replace 'your_api_key_here' with your actual API key
NEWS_API_KEY=your_actual_api_key_here

# Start Laravel server
php artisan serve
```

The Laravel API will be available at `http://localhost:8000`

### 3. Frontend (Nuxt 3) Setup

```bash
cd frontend

# Install dependencies (if not already done)
npm install

# Start development server
npm run dev
```

The Nuxt application will be available at `http://localhost:3000`

## API Endpoints

### Backend Laravel API

**Live API Endpoints (fetch from NewsAPI):**
- `GET /api/news` - Get latest top headlines (cached 15 min)
- `GET /api/news/search?q=keyword` - Search news by keyword (cached 15 min)
- `GET /api/news/category/{category}` - Get news by category (cached 15 min)
  - Valid categories: business, entertainment, general, health, science, sports, technology
- `POST /api/news/clear-cache` - Clear all news cache

**Database Endpoints (fetch saved articles):**
- `GET /api/news/saved` - Get all saved news from database (default: 1000 articles)
- `GET /api/news/saved?category={category}` - Get saved news filtered by category
- `GET /api/news/saved?per_page=50` - Get saved news with custom page size
- `GET /api/news/saved?per_page=all` - Get all saved articles without pagination
- `GET /api/news/stats` - Get database statistics (total articles, category counts, date ranges)
- `POST /api/news/import` - Import new news from all categories (6 categories + general)
- `POST /api/news/clear-cache` - Clear all news cache

### Example API Calls

**Live API (NewsAPI):**
```bash
# Get latest headlines
curl http://localhost:8000/api/news

# Search for specific topic
curl http://localhost:8000/api/news/search?q=bitcoin

# Get technology news
curl http://localhost:8000/api/news/category/technology
```

**Database API:**
```bash
# Get all saved articles (up to 1000)
curl http://localhost:8000/api/news/saved

# Get saved business articles
curl http://localhost:8000/api/news/saved?category=business

# Get ALL articles without limit
curl http://localhost:8000/api/news/saved?per_page=all

# Import new news from all categories
curl -X POST http://localhost:8000/api/news/import

# Get database statistics
curl http://localhost:8000/api/news/stats
```

**Artisan Commands:**
```bash
# Import news from all categories
php artisan news:fetch --category=all

# Import news from specific category
php artisan news:fetch --category=technology

# Start the scheduler (runs hourly import automatically)
php artisan schedule:work
```

## Frontend Features

- **Material Design UI**: Built with Vuetify 3 components
- **One-Click Import**: "Import New News" button fetches and saves articles from all categories
- **Professional App Bar**: Header with icon, article count badge, and import button
- **Category Filter**: Elegant dropdown with Material Design styling
- **Real-time Statistics**: Display total saved articles count that updates after import
- **Local Images**: All images downloaded and served from your own server
- **News Cards**: Beautiful cards with locally stored images, hover effects, and smooth transitions
- **Smart Image Loading**: Lazy loading with placeholder spinners
- **Image Error Handling**: Gracefully handles missing or broken images
- **Read More**: Cards link directly to full articles
- **Responsive Grid**: Adapts seamlessly to mobile, tablet, and desktop
- **Success Notifications**: Green snackbar shows import results
- **Error Handling**: User-friendly alerts with helpful messages
- **Loading States**: Circular progress indicators during data fetch and import
- **Relative Time Display**: Shows "5h ago" for recent articles

## How It Works

1. **NewsAPI Integration**: Laravel backend fetches real-time news from NewsAPI.org (150,000+ sources)
2. **Automatic Database Storage**: Every fetched article is automatically saved to SQLite database
3. **Local Image Download**: Images are downloaded from external sources and stored in `storage/app/public/news-images/`
4. **Smart Caching**: News data is cached for 15 minutes to minimize API calls during fetching
5. **Import Options**:
   - **Manual Import**: Click "Import New News" button in frontend
   - **CLI Import**: Run `php artisan news:fetch --category=all` command
   - **Automated Import**: Scheduled task runs hourly (`php artisan schedule:work`)
6. **Database-Only View**: Frontend displays only saved articles from local database (fast, no API calls)
7. **Laravel API**: Processes and returns news data through clean REST endpoints
8. **Nuxt Frontend**: Fetches data from Laravel API and displays in Material Design UI with Vuetify
9. **Database Statistics**: Real-time stats show how many articles are stored and category distribution

## Technologies Used

### Backend
- Laravel 12
- PHP 8.x
- Guzzle HTTP Client
- Laravel Sanctum
- Laravel Cache (Database driver)

### Frontend
- Nuxt 3
- Vue 3
- TypeScript
- Composition API
- Vuetify 3 (Material Design)
- Material Design Icons (@mdi/font)
- useFetch composable

## NewsAPI Free Tier Limits

- **100 requests per day**
- **Delay**: News delayed by 15 minutes
- **Smart Caching Solution**: With 15-minute cache, 100 requests can serve thousands of page views
- For production use, consider upgrading to a paid plan at [newsapi.org/pricing](https://newsapi.org/pricing)

## Caching & Database System

The application implements both caching and database storage for optimal performance:

### Caching (15-minute memory cache)
- **Cache Duration**: 15 minutes per request
- **Cache Keys**: Separate caches for headlines, categories, and search queries
- **Benefits**: 100 API requests can serve unlimited page refreshes within cache window
- **Manual Clear**: Use `POST /api/news/clear-cache` endpoint to force refresh

### Database Storage (Persistent SQLite)
- **Auto-Save**: Every API fetch automatically saves articles to database
- **Duplicate Prevention**: Uses `updateOrCreate()` with URL as unique key
- **Persistent Storage**: Articles remain available even when cache expires
- **Offline Access**: View saved articles without making API calls
- **Category Tracking**: Each article tagged with its category
- **Statistics**: Track total articles, category distribution, and date ranges

### How It Works Together

1. **First Request**: 
   - Fetches from NewsAPI
   - Saves to database
   - Stores in cache (15 min)
   
2. **Subsequent Requests (within 15 min)**: 
   - Returns cached data (no API call)
   - No database write needed
   
3. **After 15 Minutes**: 
   - Cache expires
   - Next request fetches fresh data from NewsAPI
   - Updates database with any new articles
   - Creates new cache
   
4. **Frontend Display**:
   - Always reads from database
   - No API calls needed
   - No cache dependency
   - Instant access to all saved articles (up to 1000)
   - Images served from local storage

### Image Storage

- **Download Process**: Images downloaded during news import
- **Storage Location**: `storage/app/public/news-images/`
- **Public Access**: Symlinked to `public/storage/` via `php artisan storage:link`
- **Fallback**: If image download fails, original URL is stored
- **Performance**: Local images load faster than external sources

### Visual Indicators

- **Purple Badge**: Shows total number of saved articles in app bar
- **Success Snackbar**: Green notification after successful import with article count

### Customize Cache Duration

Edit `backend/app/Http/Controllers/DataController.php`:

```php
private $cacheDuration = 15; // Change to 30, 60, etc. (minutes)
```

## Customization Options

### Change Import Schedule

Edit `backend/bootstrap/app.php`:

```php
->withSchedule(function (Schedule $schedule): void {
    $schedule->command('news:fetch --category=all')
             ->daily() // Change to: hourly(), twiceDaily(), weekly(), etc.
             ->withoutOverlapping()
             ->runInBackground();
})
```

### Change Article Limit

Edit `backend/app/Http/Controllers/DataController.php` in `getSavedNews()` method:

```php
$perPage = $request->input('per_page', 1000); // Change default limit
```

### Change News Country

Edit `backend/app/Http/Controllers/DataController.php`:

```php
'country' => 'gb', // UK news
// Available: us, gb, ca, au, de, fr, etc.
```

### Change Number of News

```php
'pageSize' => 50, // Get up to 100 articles
```

### Add More Categories

The frontend dropdown already includes all available categories:
- Business
- Entertainment  
- Health
- Science
- Sports
- Technology

## Troubleshooting

### "Failed to fetch news from API"

1. Check that your NewsAPI key is set correctly in `backend/.env`
2. Verify Laravel backend is running (`php artisan serve`)
3. Check you haven't exceeded the 100 requests/day limit
4. Try clearing cache: `curl -X POST http://localhost:8000/api/news/clear-cache`

### Vuetify Not Loading

If Vuetify styles are missing:
1. Stop the frontend server
2. Delete `frontend/.nuxt` folder
3. Run `npm install` again
4. Restart with `npm run dev`

### "CORS Error"

Make sure `backend/config/cors.php` allows requests from your frontend origin.

### Images Not Loading

1. Make sure storage link exists: `php artisan storage:link`
2. Check `storage/app/public/news-images/` directory exists
3. Verify images are being downloaded during import
4. Check Laravel backend is serving images from `http://localhost:8000/storage/`
5. Look for image download errors in Laravel logs

### Import Not Working

1. Verify NewsAPI key is set in `backend/.env`
2. Check you haven't exceeded 100 requests/day limit
3. Try manual import: `php artisan news:fetch --category=technology`
4. Check Laravel logs for errors: `backend/storage/logs/laravel.log`

### Scheduler Not Running

1. Start scheduler manually: `php artisan schedule:work`
2. For production, add to crontab: `* * * * * cd /path/to/project/backend && php artisan schedule:run`
3. Verify scheduled task: `php artisan schedule:list`

## Alternative News APIs

If you want to try other news sources:

- **GNews API**: [gnews.io](https://gnews.io/) - 100 requests/day free
- **Currents API**: [currentsapi.services](https://currentsapi.services/) - Free tier available
- **The Guardian API**: [open-platform.theguardian.com](https://open-platform.theguardian.com/)

## Production Deployment

### Backend
```bash
cd backend
composer install --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Frontend
```bash
cd frontend
npm run build
npm run preview
```

## License

This project is open-source and available for educational purposes.
