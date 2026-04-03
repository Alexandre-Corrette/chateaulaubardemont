/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./layouts/**/*.html",
    "./assets/**/*.js",
  ],
  theme: {
    extend: {
      screens: {
        'desktop': '900px',
      },
    },
  },
  plugins: [],
}
