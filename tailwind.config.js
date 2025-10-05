/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./**/*.{php,html,js}",
    "./includes/**/*.{php,html,js}",
    "./admin/**/*.{php,html,js}",
    "./account/**/*.{php,html,js}",
    "./auth/**/*.{php,html,js}",
  ],
  theme: {
    extend: {
      colors: {
        primary: '#C1272D',    // Deep Red - main buttons, highlights, active states
        secondary: '#FF8C00',  // Orange - secondary buttons, icons, highlights, active navigation
        dark: '#1A1A1A',       // Black - headers, navigation, strong text
        light: '#F5E6D3',      // Warm Cream - backgrounds, section alternates
        accent: '#2E5E3A',     // Dark Green - success states, callouts, special highlights
        neutral: '#212121',    // Body text color
      },
      fontFamily: {
        heading: ['Poppins', 'Inter', 'sans-serif'],
        body: ['Open Sans', 'Roboto', 'sans-serif'],
      },
      boxShadow: {
        'card': '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)',
        'button': '0 2px 4px 0 rgba(0, 0, 0, 0.1)',
      },
      borderRadius: {
        'card': '0.5rem',
        'button': '0.375rem',
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
  ],
}
