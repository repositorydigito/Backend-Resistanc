/** @type {import('tailwindcss').Config} */
export default {
  content: [
    // Escanear TODAS las vistas en cualquier subcarpeta
    "./resources/views/**/*.blade.php",
    "./resources/**/*.js",
    "./app/**/*.php",
  ],
  theme: {
    extend: {
      fontFamily: {
        'outfit': ['Outfit', 'sans-serif'],
      },
    },
  },
  plugins: [],
}

