# GitHub Dark Dimmed Theme System

## Overview

This project implements a comprehensive three-theme system with **Light**, **Dark**, and **GitHub Dark Dimmed** themes, matching VS Code's GitHub Dark Dimmed color scheme.

## Features

✅ **Three Theme Support**: Light, Dark, and GitHub Dark Dimmed  
✅ **VS Code Accurate Colors**: Exact color matching with GitHub Dark Dimmed  
✅ **Smooth Transitions**: Animated theme switching  
✅ **Keyboard Shortcuts**: `Ctrl+Shift+T` to toggle themes  
✅ **System Detection**: Automatic theme detection from system preferences  
✅ **Persistence**: Theme choice saved across sessions  
✅ **SSR Safe**: No hydration mismatches  
✅ **No Hardcoded Colors**: All colors use semantic tokens

## Quick Start

### Theme Toggle

Use the theme toggle button in the header or press `Ctrl+Shift+T` to cycle through themes:

- 🌞 Light Theme
- 🌙 Dark Theme
- 🌙 GitHub Dark Dimmed

### Testing

Visit `/theme-test` to see all components in all three themes and test the customization tools.

## Implementation Details

### Core Files

#### 1. `tailwind.config.ts`

```typescript
// Adds dim variant support
addVariant("dim", ".dim &");
```

#### 2. `src/app/globals.css`

```css
/* GitHub Dark Dimmed color scheme */
.dim {
  --background: 220 13% 9%;
  --foreground: 220 14% 71%;
  --primary: 217 91% 60%;
  /* ... more colors */
}
```

#### 3. `src/components/theme-provider.tsx`

```typescript
// Three-theme configuration
<ThemeProvider themes={["light", "dark", "dim"]}>
```

#### 4. `src/components/mode-toggle.tsx`

Enhanced toggle with visual indicators for current theme.

### Utility Hooks

#### `useThemeUtils()` - Core Theme Hook

```typescript
import { useThemeUtils } from "@/hooks/use-theme-utils";

function MyComponent() {
  const { isLight, isDark, isDim, theme, toggleTheme } = useThemeUtils();

  return (
    <div className={isDim ? "dim-specific-style" : "default-style"}>
      Current theme: {theme}
    </div>
  );
}
```

#### `useThemeClasses()` - Styling Utilities

```typescript
import { useThemeClasses } from "@/lib/theme-utils";

function MyComponent() {
  const { surface, text, muted, accent, interactive, getVariant } = useThemeClasses();

  return (
    <div className={surface}>
      <h1 className={text}>Title</h1>
      <p className={muted}>Description</p>
      <button className={`${accent} ${interactive}`}>
        Action
      </button>
    </div>
  );
}
```

### Theme-Aware Components

#### Conditional Rendering

```typescript
import { ThemeConditional } from "@/lib/theme-utils";

<ThemeConditional
  light={<LightIcon />}
  dark={<DarkIcon />}
  dim={<DimIcon />}
  fallback={<DefaultIcon />}
/>
```

#### Custom Variants

```typescript
const { getVariant } = useThemeClasses();

const customStyle = getVariant({
  light: "bg-blue-50 text-blue-900",
  dark: "bg-blue-950 text-blue-100",
  dim: "bg-accent text-accent-foreground",
  default: "bg-gray-100 text-gray-900",
});
```

## Color Specifications

### GitHub Dark Dimmed Colors

Based on VS Code's GitHub Dark Dimmed theme:

```css
:root.dim {
  /* Backgrounds */
  --background: 220 13% 9%; /* #16181d */
  --card: 217 19% 12%; /* #1c2128 */
  --popover: 217 19% 12%; /* #1c2128 */

  /* Text */
  --foreground: 220 14% 71%; /* #adbac7 */
  --muted-foreground: 217 10% 52%; /* #768390 */

  /* Interactive */
  --primary: 217 91% 60%; /* #316dca */
  --accent: 213 13% 19%; /* #2d333b */

  /* Borders */
  --border: 217 19% 17%; /* #2d333b */
  --input: 217 19% 17%; /* #2d333b */
}
```

## Advanced Features

### Theme Customization System

Located at `/theme-test`, includes:

- **Theme Presets**: GitHub, Monokai, Ocean, Sunset
- **Live Preview**: Real-time color customization
- **Export/Import**: Save custom themes
- **Color Picker**: Visual theme builder

### Performance Optimization

- **Memoized Hooks**: `useThemeOptimized()` for animations
- **CSS Custom Properties**: Efficient color token system
- **No Runtime Classes**: Pre-computed theme variants

### Keyboard Navigation

- `Ctrl+Shift+T`: Cycle through themes
- `Escape`: Close theme customizer
- Arrow keys: Navigate theme options

## Browser Support

- ✅ Chrome/Edge 88+
- ✅ Firefox 85+
- ✅ Safari 14+
- ✅ Mobile browsers

## Best Practices

### 1. Use Semantic Tokens

```typescript
// ❌ Avoid hardcoded colors
<div className="bg-gray-800 text-white" />

// ✅ Use semantic tokens
<div className="bg-card text-foreground" />
```

### 2. Theme-Aware Conditionals

```typescript
// ❌ Manual theme checking
{theme === 'dim' && <DimComponent />}

// ✅ Use ThemeConditional
<ThemeConditional dim={<DimComponent />} />
```

### 3. Performance for Animations

```typescript
// ❌ Regular hook in animations
const { isDim } = useThemeUtils();

// ✅ Optimized hook for performance
const { isDarkMode } = useThemeOptimized();
```

## Troubleshooting

### Common Issues

1. **Server-side errors with theme hooks**
   - Add `"use client"` directive to components using theme hooks

2. **Theme not persisting**
   - Check localStorage permissions
   - Verify ThemeProvider wraps the app

3. **Colors not updating**
   - Ensure CSS custom properties are defined
   - Check for hardcoded color classes

### Debug Mode

Set `DEBUG_THEME=true` in environment to enable debug logging:

```bash
DEBUG_THEME=true npm run dev
```

## Migration Guide

### From Basic Dark/Light to Three Themes

1. **Update Theme Provider**

```typescript
// Before
<ThemeProvider themes={["light", "dark"]}>

// After
<ThemeProvider themes={["light", "dark", "dim"]}>
```

2. **Replace Hardcoded Colors**

```typescript
// Before
className = "bg-gray-900 text-white";

// After
className = "bg-background text-foreground";
```

3. **Update Theme Toggle**

```typescript
// Before: Binary toggle
setTheme(theme === "dark" ? "light" : "dark");

// After: Cycle through three themes
const { toggleTheme } = useThemeUtils();
toggleTheme();
```

## Development Commands

```bash
# Start development server
npm run dev

# Build for production
npm run build

# Test theme system
npm run dev
# Navigate to http://localhost:3000/theme-test

# Lint theme-related files
npm run lint
```

## File Structure

```
src/
├── app/
│   ├── globals.css              # Theme CSS custom properties
│   └── theme-test/             # Theme testing page
├── components/
│   ├── mode-toggle.tsx         # Theme toggle button
│   ├── theme-provider.tsx      # Theme context provider
│   └── ui/                     # Theme-aware UI components
├── hooks/
│   └── use-theme-utils.ts      # Core theme utilities
├── lib/
│   ├── theme-utils.ts          # Advanced theme utilities
│   ├── theme-presets.ts        # Theme preset definitions
│   └── theme-customizer.tsx    # Theme customization tools
└── tailwind.config.ts          # Tailwind theme configuration
```

## Contributing

When adding new components:

1. Use semantic color tokens instead of hardcoded colors
2. Test in all three themes
3. Add to `/theme-test` page for validation
4. Follow accessibility guidelines for color contrast

## License

This theme system is part of the PayPerCrawl project and follows the same license terms.
