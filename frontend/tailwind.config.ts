import type { Config } from 'tailwindcss';

export default {
  content: [
    './components/**/*.{vue,js,ts}',
    './layouts/**/*.{vue,js,ts}',
    './pages/**/*.{vue,js,ts}',
    './app.{vue,js,ts}',
    './plugins/**/*.{js,ts}'
  ],
  theme: {
    extend: {
      colors: {
        midnight: '#0b1220',
        neon: '#7dd3fc'
      },
      fontFamily: {
        display: ['"Orbitron"', 'ui-sans-serif', 'system-ui'],
        sans: ['"Inter"', 'ui-sans-serif', 'system-ui']
      }
    }
  },
  plugins: []
} satisfies Config;
