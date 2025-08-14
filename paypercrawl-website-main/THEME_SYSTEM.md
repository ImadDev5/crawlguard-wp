# Theme System - GitHub Dark Dimmed

A comprehensive three-theme system implementing VS Code's GitHub Dark Dimmed aesthetic alongside traditional light and dark themes.

## 🎨 Available Themes

### 1. Light Theme

- **Use Case**: Daytime use, bright environments
- **Characteristics**: Clean, bright, high contrast
- **Colors**: White backgrounds, dark text

### 2. Dark Theme

- **Use Case**: Low-light environments, traditional dark mode users
- **Characteristics**: High contrast, pure blacks/whites
- **Colors**: Dark backgrounds, light text

### 3. Dark Dimmed (GitHub Style)

- **Use Case**: Extended coding sessions, reduced eye strain
- **Characteristics**: Softer contrast, muted colors, GitHub aesthetic
- **Colors**: Blue-gray backgrounds (#22272e), softer text (#adbac7)

### 4. System Theme

- **Use Case**: Automatic preference detection
- **Characteristics**: Follows OS theme setting
- **Behavior**: Switches between light/dark based on system

## 🚀 Features

- ✅ **VS Code GitHub Dark Dimmed** - Pixel-perfect color matching
- ✅ **Smooth Transitions** - 200ms ease-in-out animations
- ✅ **Keyboard Shortcuts** - Ctrl+Shift+T to cycle themes
- ✅ **Theme Persistence** - Remembers user preference
- ✅ **System Integration** - Respects OS theme preference
- ✅ **No Hardcoded Colors** - All components use semantic tokens
- ✅ **Accessibility** - WCAG AA contrast ratios maintained
- ✅ **Developer Tools** - Theme debugger and documentation

## 🎛️ Usage

### Basic Theme Switching

```tsx
import { ModeToggle } from "@/components/mode-toggle";

export function Header() {
  return (
    <header>
      <ModeToggle />
    </header>
  );
}
```

### Using Theme Utilities

```tsx
import { useThemeUtils } from "@/hooks/use-theme-utils";

export function Component() {
  const { isDim, isLight, isDark, theme } = useThemeUtils();

  return (
    <div
      className={cn(
        "rounded-lg",
        isDim && "bg-card border-border",
        isLight && "bg-white border-gray-200"
      )}
    >
      Current theme: {theme}
    </div>
  );
}
```

### Keyboard Shortcuts

```tsx
import { useThemeKeyboardShortcuts } from "@/hooks/use-theme-keyboard-shortcuts";

export function App() {
  useThemeKeyboardShortcuts(); // Enables Ctrl+Shift+T
  return <div>Your app</div>;
}
```

## 🎨 Color System

### Semantic Color Tokens

All colors use CSS custom properties that automatically adapt to the current theme:

```css
/* Use these semantic tokens */
bg-background      /* Main app background */
text-foreground    /* Primary text color */
bg-card           /* Card/elevated surface background */
text-muted-foreground  /* Secondary text */
border-border     /* Border color */
bg-primary        /* Brand/primary color */
text-primary-foreground  /* Text on primary */
```

### GitHub Dark Dimmed Colors

The dim theme uses these specific GitHub colors:

```css
--background: 214 16% 16%; /* #22272e */
--foreground: 214 14% 72%; /* #adbac7 */
--card: 214 16% 20%; /* #2d333b */
--border: 214 11% 31%; /* #444c56 */
--primary: 214 82% 63%; /* #4493f8 */
```

## ⌨️ Keyboard Shortcuts

| Shortcut           | Action                       |
| ------------------ | ---------------------------- |
| `Ctrl + Shift + T` | Cycle through themes         |
| `Cmd + Shift + T`  | Cycle through themes (macOS) |

## 🛠️ Development

### Testing Themes

Visit `/theme-test` to see all components in all themes:

```bash
npm run dev
# Navigate to http://localhost:3000/theme-test
```

### Theme Debugger

Add the theme debugger to any page:

```tsx
import { ThemeDebugger } from "@/components/theme-debugger";

export function Page() {
  return (
    <div>
      {/* Your content */}
      <ThemeDebugger show={process.env.NODE_ENV === "development"} />
    </div>
  );
}
```

## 📱 Implementation Details

### Tailwind Configuration

```typescript
// tailwind.config.ts
const config: Config = {
  darkMode: ["class", '[data-theme="dark"]'],
  plugins: [
    function ({ addVariant }) {
      addVariant("dim", ".dim &");
    },
  ],
};
```

### Theme Provider Setup

```tsx
// app/layout.tsx
<ThemeProvider
  themes={["light", "dark", "dim"]}
  attribute="class"
  defaultTheme="system"
  enableSystem
  disableTransitionOnChange={false}
>
  <ThemeKeyboardShortcuts />
  {children}
</ThemeProvider>
```

### CSS Custom Properties

```css
/* Automatic theme switching */
.dim {
  --background: 214 16% 16%;
  --foreground: 214 14% 72%;
  /* ... more colors */
}

.dark {
  --background: 222.2 84% 4.9%;
  --foreground: 210 40% 98%;
  /* ... more colors */
}

:root {
  --background: 0 0% 100%;
  --foreground: 222.2 84% 4.9%;
  /* ... more colors */
}
```

## 🎯 Best Practices

### ✅ Do

- Use semantic color tokens (`bg-background`, `text-foreground`)
- Test components in all three themes
- Use the theme debugger during development
- Follow GitHub's color specifications for dim theme

### ❌ Don't

- Use hardcoded colors (`bg-white`, `text-black`)
- Skip testing in dim theme
- Override theme colors with arbitrary values
- Use non-semantic color classes for core UI

## 🔧 Troubleshooting

### Theme Not Switching

1. Check if `ThemeProvider` wraps your app
2. Verify `themes={["light", "dark", "dim"]}` prop
3. Ensure components use semantic color tokens

### Colors Look Wrong

1. Check for hardcoded color classes
2. Verify CSS custom properties are defined
3. Test with theme debugger enabled

### Keyboard Shortcuts Not Working

1. Add `<ThemeKeyboardShortcuts />` to your layout
2. Check for conflicting keyboard event handlers
3. Ensure component is client-side (`"use client"`)

## 📚 Related Files

- `/src/components/mode-toggle.tsx` - Theme switcher component
- `/src/components/theme-provider.tsx` - Theme context provider
- `/src/hooks/use-theme-utils.ts` - Theme utility hooks
- `/src/app/globals.css` - Theme color definitions
- `/tailwind.config.ts` - Tailwind theme configuration
- `/src/app/theme-test/page.tsx` - Theme testing page
