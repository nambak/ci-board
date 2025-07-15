/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./application/views/**/*.php"],
  theme: {
    extend: {
      fontFamily: {
        'sans': ['Noto Sans', 'Helvetica', 'Arial', 'sans-serif'],
      }
    },
  },
  plugins: [],
}