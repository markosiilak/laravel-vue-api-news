<template>
  <v-app>
    <v-app-bar color="primary" prominent>
      <v-app-bar-title class="text-h4 font-weight-bold">
        <v-icon icon="mdi-newspaper" class="mr-2"></v-icon>
        Saved News Articles
      </v-app-bar-title>
      
      <template v-slot:append>
        <v-btn
          @click="importNews"
          :loading="importing"
          :disabled="importing"
          variant="elevated"
          color="white"
          class="mr-2"
        >
          <v-icon start>mdi-download</v-icon>
          Import New News
        </v-btn>
        <v-chip 
          color="purple" 
          variant="flat" 
          class="mr-4"
        >
          <v-icon start icon="mdi-database-check"></v-icon>
          {{ dbStats?.total_articles || 0 }} Articles Saved
        </v-chip>
      </template>
    </v-app-bar>

    <v-main>
      <v-container fluid class="pa-6">
        <!-- Import Success Snackbar -->
        <v-snackbar
          v-model="showImportSuccess"
          :timeout="4000"
          color="success"
          location="top"
        >
          <v-icon start>mdi-check-circle</v-icon>
          {{ importMessage }}
        </v-snackbar>

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
        <v-row v-else-if="displayArticles && displayArticles.length > 0">
          <v-col
            v-for="(article, index) in displayArticles"
            :key="article.id || index"
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
                v-if="article.urlToImage || article.url_to_image"
                :src="getImageUrl(article)"
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
                {{ getSourceName(article) }}
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
                  {{ formatDate(article.publishedAt || article.published_at) }}
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
const importing = ref(false)
const showImportSuccess = ref(false)
const importMessage = ref('')

const categories = [
  { title: 'All Categories', value: '' },
  { title: 'Business', value: 'business' },
  { title: 'Entertainment', value: 'entertainment' },
  { title: 'Health', value: 'health' },
  { title: 'Science', value: 'science' },
  { title: 'Sports', value: 'sports' },
  { title: 'Technology', value: 'technology' },
]

// Fetch saved news from database
const { data, pending, error, refresh } = await useFetch(
  () => {
    const params = selectedCategory.value ? `?category=${selectedCategory.value}` : ''
    return `${config.public.apiBase}/news/saved${params}`
  },
  {
    watch: [selectedCategory]
  }
)

// Fetch database stats
const { data: dbStats, refresh: refreshStats } = await useFetch(
  `${config.public.apiBase}/news/stats`
)

const displayArticles = computed(() => {
  if (!data.value) return []
  return data.value.articles || []
})

const importNews = async () => {
  importing.value = true
  try {
    const response = await $fetch(`${config.public.apiBase}/news/import`, {
      method: 'POST'
    })
    
    if (response.success) {
      importMessage.value = `✓ Imported ${response.new_articles || 0} new articles!`
      showImportSuccess.value = true
      
      // Refresh the news list and stats
      await refresh()
      await refreshStats()
    }
  } catch (err) {
    importMessage.value = '✗ Failed to import news. Please try again.'
    showImportSuccess.value = true
  } finally {
    importing.value = false
  }
}

const fetchNewsByCategory = () => {
  refresh()
}

const getSourceName = (article: any) => {
  if (article.source?.name) return article.source.name
  if (article.source_name) return article.source_name
  return 'Unknown Source'
}

const getImageUrl = (article: any) => {
  const imageUrl = article.urlToImage || article.url_to_image
  
  // If it's a local path (starts with /storage/), prepend the backend URL
  if (imageUrl && imageUrl.startsWith('/storage/')) {
    return `http://localhost:8000${imageUrl}`
  }
  
  // Otherwise return as-is (external URL or no image)
  return imageUrl
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
  const target = e.target as HTMLImageElement
  if (target && target.style) {
    target.style.display = 'none'
  }
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
