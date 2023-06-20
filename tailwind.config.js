/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./public/assets/js/*.js"
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}