// Nuxt configuration for AgentLog frontend
export default defineNuxtConfig({
  devtools: { enabled: false },
  css: ['~/assets/css/tailwind.css'],
  modules: [
    '@nuxtjs/tailwindcss',
    '@pinia/nuxt',
    '@vite-pwa/nuxt'
  ],
  runtimeConfig: {
    public: {
      apiBaseUrl: process.env.NUXT_PUBLIC_API_BASE_URL || 'http://127.0.0.1:8000',
      showDevTools: process.env.NUXT_PUBLIC_SHOW_DEV_TOOLS === 'true'
    }
  },
  app: {
    head: {
      title: 'AgentLog',
      meta: [
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        { name: 'theme-color', content: '#0b1220' },
        { name: 'description', content: 'AgentLog mission companion PWA' }
      ]
    }
  },
  tailwindcss: {
    viewer: false
  },
  pwa: {
    registerType: 'autoUpdate',
    manifest: {
      name: 'AgentLog',
      short_name: 'AgentLog',
      description: 'Agent mission logbook',
      theme_color: '#0b1220',
      background_color: '#0b1220',
      display: 'standalone',
      icons: [
        { src: '/icon.svg', sizes: 'any', type: 'image/svg+xml', purpose: 'any maskable' }
      ]
    },
    workbox: {
      navigateFallback: '/' 
    }
  }
});
