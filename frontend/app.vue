<template>
  <v-app>
    <v-app-bar color="primary" prominent>
      <v-app-bar-title class="text-h4 font-weight-bold">
        <v-icon icon="mdi-newspaper" class="mr-2"></v-icon>
        Latest News Headlines
      </v-app-bar-title>
      
      <template v-slot:append>
        <v-chip v-if="data?.cached" color="success" variant="flat" class="mr-4">
          <v-icon start icon="mdi-database"></v-icon>
          Cached • Updates every 15 min
        </v-chip>
      </template>
    </v-app-bar>

    <v-main>
      <v-container fluid class="pa-6">
        <!-- Filter Bar -->
        <v-row justify="center" class="mb-6">
          <v-col cols="12" md="6" lg="4">
            <v-select
              v-model="selectedCategory"
              :items="categories"
              label="Filter by Category"
              variant="outlined"
              density="comfortable"
              prepend-inner-icon="mdi-filter-variant"
              @update:model-value="fetchNewsByCategory"
              hide-details
            ></v-select>
          </v-col>
        </v-row>

        <!-- Loading State -->
        <v-row v-if="pending" justify="center">
          <v-col cols="12" class="text-center py-16">
            <v-progress-circular
              indeterminate
              color="primary"
              size="64"
            ></v-progress-circular>
            <p class="text-h6 mt-4 text-grey">Loading news...</p>
          </v-col>
        </v-row>

        <!-- Error State -->
        <v-row v-else-if="error" justify="center">
          <v-col cols="12" md="8">
            <v-alert
              type="error"
              variant="tonal"
              prominent
              border="start"
              icon="mdi-alert-circle"
            >
              <v-alert-title>Error Loading News</v-alert-title>
              <div>{{ error.message }}</div>
              <div class="mt-2 text-caption">
                Make sure Laravel backend is running and NEWS_API_KEY is set in .env
              </div>
            </v-alert>
          </v-col>
        </v-row>

        <!-- News Grid -->
        <v-row v-else-if="data && data.success && data.articles">
          <v-col
            v-for="(article, index) in data.articles"
            :key="index"
            cols="12"
            sm="6"
            md="4"
            lg="3"
          >
            <v-card
              :href="article.url"
              target="_blank"
              rel="noopener noreferrer"
              hover
              class="news-card h-100"
              elevation="2"
            >
              <v-img
                v-if="article.urlToImage"
                :src="article.urlToImage"
                height="200"
                cover
                @error="handleImageError"
              >
                <template v-slot:placeholder>
                  <v-row class="fill-height ma-0" align="center" justify="center">
                    <v-progress-circular indeterminate color="grey-lighten-5"></v-progress-circular>
                  </v-row>
                </template>
              </v-img>
              
              <v-chip
                class="ma-2"
                color="primary"
                size="small"
                label
              >
                {{ article.source?.name || 'Unknown Source' }}
              </v-chip>

              <v-card-title class="text-h6 font-weight-bold">
                {{ article.title }}
              </v-card-title>

              <v-card-text>
                <p class="text-body-2 text-grey-darken-1">
                  {{ article.description }}
                </p>
              </v-card-text>

              <v-card-actions class="px-4 pb-4">
                <v-chip
                  v-if="article.author"
                  size="small"
                  variant="text"
                  prepend-icon="mdi-account"
                  class="text-caption"
                >
                  {{ truncateAuthor(article.author) }}
                </v-chip>
                <v-spacer></v-spacer>
                <v-chip
                  size="small"
                  variant="text"
                  prepend-icon="mdi-clock-outline"
                  class="text-caption"
                >
                  {{ formatDate(article.publishedAt) }}
                </v-chip>
              </v-card-actions>
            </v-card>
          </v-col>
        </v-row>

        <!-- No Results -->
        <v-row v-else justify="center">
          <v-col cols="12" md="6">
            <v-alert
              type="info"
              variant="tonal"
              icon="mdi-information"
            >
              No news articles found
            </v-alert>
          </v-col>
        </v-row>
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup lang="ts">
const config = useRuntimeConfig()
const selectedCategory = ref('')

const categories = [
  { title: 'All Categories', value: '' },
  { title: 'Business', value: 'business' },
  { title: 'Entertainment', value: 'entertainment' },
  { title: 'Health', value: 'health' },
  { title: 'Science', value: 'science' },
  { title: 'Sports', value: 'sports' },
  { title: 'Technology', value: 'technology' },
]

// Fetch latest news headlines from Laravel API
const { data, pending, error, refresh } = await useFetch(
  () => {
    if (selectedCategory.value) {
      return `${config.public.apiBase}/news/category/${selectedCategory.value}`
    }
    return `${config.public.apiBase}/news`
  },
  {
    watch: [selectedCategory]
  }
)

const fetchNewsByCategory = () => {
  refresh()
}

const formatDate = (dateString: string) => {
  const date = new Date(dateString)
  const now = new Date()
  const diffInHours = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60))
  
  if (diffInHours < 24) {
    return `${diffInHours}h ago`
  }
  
  return date.toLocaleDateString('en-US', { 
    month: 'short', 
    day: 'numeric',
  })
}

const truncateAuthor = (author: string) => {
  if (!author) return ''
  return author.length > 20 ? author.substring(0, 20) + '...' : author
}

const handleImageError = (e: Event) => {
  (e.target as HTMLImageElement).style.display = 'none'
}
</script>

<style scoped>
.news-card {
  display: flex;
  flex-direction: column;
  transition: transform 0.2s ease-in-out;
}

.news-card:hover {
  transform: translateY(-4px);
}

.h-100 {
  height: 100%;
}
</style>
