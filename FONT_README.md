# Font Configuration

This project now uses **Noto Sans** as the primary font with **Helvetica** and **Arial** as fallbacks, configured through Tailwind CSS.

## Font Setup

- **Primary Font**: Noto Sans (Google Fonts web font)
- **Fallback Fonts**: Helvetica, Arial, sans-serif
- **Source**: Google Fonts CDN
- **Configuration**: Tailwind CSS

## Files Modified

1. `tailwind.config.js` - Tailwind CSS configuration with font family settings
2. `assets/css/input.css` - Input CSS file with Google Fonts import and custom styles
3. `assets/css/tailwind.css` - Generated Tailwind CSS file (includes Noto Sans)
4. `application/views/header_v.php` - Updated to use Tailwind CSS instead of ci_board.css
5. `package.json` - Added Tailwind CSS as dependency

## Building CSS

To rebuild the CSS after making changes to `assets/css/input.css`:

```bash
npx tailwindcss -i ./assets/css/input.css -o ./assets/css/tailwind.css
```

Or for development with watch mode:

```bash
npm run build-css
```

## Testing

A test page is available at `/font-test.html` to verify font loading and appearance.

## Font Loading

The Noto Sans font is loaded via Google Fonts CDN:
```css
@import url('https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,100..900;1,100..900&display=swap');
```

The font family is configured in Tailwind CSS:
```javascript
fontFamily: {
  'sans': ['Noto Sans', 'Helvetica', 'Arial', 'sans-serif'],
}
```