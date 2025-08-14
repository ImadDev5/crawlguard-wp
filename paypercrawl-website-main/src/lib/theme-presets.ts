/**
 * Theme Presets - Predefined color schemes for different use cases
 */

export interface ThemePreset {
  name: string;
  displayName: string;
  description: string;
  colors: {
    light: Record<string, string>;
    dark: Record<string, string>;
    dim: Record<string, string>;
  };
}

export const themePresets: Record<string, ThemePreset> = {
  // Default GitHub-inspired theme
  github: {
    name: "github",
    displayName: "GitHub",
    description: "GitHub's signature color palette with dark dimmed variant",
    colors: {
      light: {
        background: "0 0% 100%",
        foreground: "222.2 84% 4.9%",
        card: "0 0% 100%",
        "card-foreground": "222.2 84% 4.9%",
        popover: "0 0% 100%",
        "popover-foreground": "222.2 84% 4.9%",
        primary: "222.2 47.4% 11.2%",
        "primary-foreground": "210 40% 98%",
        secondary: "210 40% 96%",
        "secondary-foreground": "222.2 47.4% 11.2%",
        muted: "210 40% 96%",
        "muted-foreground": "215.4 16.3% 46.9%",
        accent: "210 40% 96%",
        "accent-foreground": "222.2 47.4% 11.2%",
        destructive: "0 84.2% 60.2%",
        "destructive-foreground": "210 40% 98%",
        border: "214.3 31.8% 91.4%",
        input: "214.3 31.8% 91.4%",
        ring: "222.2 84% 4.9%",
      },
      dark: {
        background: "222.2 84% 4.9%",
        foreground: "210 40% 98%",
        card: "222.2 84% 6.9%",
        "card-foreground": "210 40% 98%",
        popover: "222.2 84% 4.9%",
        "popover-foreground": "210 40% 98%",
        primary: "210 40% 98%",
        "primary-foreground": "222.2 47.4% 11.2%",
        secondary: "217.2 32.6% 19.5%",
        "secondary-foreground": "210 40% 98%",
        muted: "217.2 32.6% 19.5%",
        "muted-foreground": "215 20.2% 70.1%",
        accent: "217.2 32.6% 19.5%",
        "accent-foreground": "210 40% 98%",
        destructive: "0 62.8% 45.6%",
        "destructive-foreground": "210 40% 98%",
        border: "217.2 32.6% 19.5%",
        input: "217.2 32.6% 19.5%",
        ring: "212.7 26.8% 83.9%",
      },
      dim: {
        background: "214 16% 16%",
        foreground: "214 14% 72%",
        card: "214 16% 20%",
        "card-foreground": "214 14% 72%",
        popover: "214 16% 18%",
        "popover-foreground": "214 14% 74%",
        primary: "214 82% 63%",
        "primary-foreground": "214 20% 12%",
        secondary: "214 13% 25%",
        "secondary-foreground": "214 14% 72%",
        muted: "214 11% 31%",
        "muted-foreground": "214 11% 52%",
        accent: "214 13% 27%",
        "accent-foreground": "214 14% 74%",
        destructive: "0 60% 55%",
        "destructive-foreground": "0 0% 100%",
        border: "214 11% 31%",
        input: "214 13% 25%",
        ring: "214 82% 63%",
      },
    },
  },

  // Warm theme inspired by Monokai
  monokai: {
    name: "monokai",
    displayName: "Monokai",
    description:
      "Warm, dark theme with vibrant accents inspired by Monokai Pro",
    colors: {
      light: {
        background: "45 100% 98%",
        foreground: "0 0% 15%",
        card: "45 100% 96%",
        "card-foreground": "0 0% 15%",
        popover: "45 100% 98%",
        "popover-foreground": "0 0% 15%",
        primary: "282 100% 76%",
        "primary-foreground": "0 0% 15%",
        secondary: "45 20% 90%",
        "secondary-foreground": "0 0% 15%",
        muted: "45 20% 92%",
        "muted-foreground": "0 0% 45%",
        accent: "326 100% 74%",
        "accent-foreground": "0 0% 15%",
        destructive: "0 84% 60%",
        "destructive-foreground": "0 0% 98%",
        border: "45 20% 85%",
        input: "45 20% 88%",
        ring: "282 100% 76%",
      },
      dark: {
        background: "60 2% 11%",
        foreground: "60 30% 96%",
        card: "60 2% 13%",
        "card-foreground": "60 30% 96%",
        popover: "60 2% 11%",
        "popover-foreground": "60 30% 96%",
        primary: "282 100% 76%",
        "primary-foreground": "60 2% 11%",
        secondary: "60 2% 17%",
        "secondary-foreground": "60 30% 96%",
        muted: "60 2% 17%",
        "muted-foreground": "60 5% 64%",
        accent: "326 100% 74%",
        "accent-foreground": "60 2% 11%",
        destructive: "0 84% 60%",
        "destructive-foreground": "60 30% 96%",
        border: "60 2% 20%",
        input: "60 2% 17%",
        ring: "282 100% 76%",
      },
      dim: {
        background: "60 3% 15%",
        foreground: "60 15% 85%",
        card: "60 3% 18%",
        "card-foreground": "60 15% 85%",
        popover: "60 3% 16%",
        "popover-foreground": "60 15% 87%",
        primary: "282 85% 70%",
        "primary-foreground": "60 3% 15%",
        secondary: "60 3% 22%",
        "secondary-foreground": "60 15% 85%",
        muted: "60 3% 25%",
        "muted-foreground": "60 5% 60%",
        accent: "326 85% 68%",
        "accent-foreground": "60 15% 85%",
        destructive: "0 75% 55%",
        "destructive-foreground": "60 15% 95%",
        border: "60 3% 28%",
        input: "60 3% 22%",
        ring: "282 85% 70%",
      },
    },
  },

  // Cool blue theme
  ocean: {
    name: "ocean",
    displayName: "Ocean",
    description: "Cool blue theme inspired by deep ocean colors",
    colors: {
      light: {
        background: "200 100% 98%",
        foreground: "210 100% 8%",
        card: "200 100% 96%",
        "card-foreground": "210 100% 8%",
        popover: "200 100% 98%",
        "popover-foreground": "210 100% 8%",
        primary: "210 100% 50%",
        "primary-foreground": "0 0% 98%",
        secondary: "200 20% 90%",
        "secondary-foreground": "210 100% 8%",
        muted: "200 20% 92%",
        "muted-foreground": "210 20% 45%",
        accent: "185 100% 45%",
        "accent-foreground": "0 0% 98%",
        destructive: "0 84% 60%",
        "destructive-foreground": "0 0% 98%",
        border: "200 20% 85%",
        input: "200 20% 88%",
        ring: "210 100% 50%",
      },
      dark: {
        background: "210 100% 4%",
        foreground: "200 50% 90%",
        card: "210 100% 6%",
        "card-foreground": "200 50% 90%",
        popover: "210 100% 4%",
        "popover-foreground": "200 50% 90%",
        primary: "200 100% 60%",
        "primary-foreground": "210 100% 4%",
        secondary: "210 50% 12%",
        "secondary-foreground": "200 50% 90%",
        muted: "210 50% 12%",
        "muted-foreground": "200 20% 65%",
        accent: "185 100% 50%",
        "accent-foreground": "210 100% 4%",
        destructive: "0 84% 60%",
        "destructive-foreground": "200 50% 90%",
        border: "210 50% 15%",
        input: "210 50% 12%",
        ring: "200 100% 60%",
      },
      dim: {
        background: "210 60% 8%",
        foreground: "200 30% 82%",
        card: "210 60% 11%",
        "card-foreground": "200 30% 82%",
        popover: "210 60% 9%",
        "popover-foreground": "200 30% 84%",
        primary: "200 80% 55%",
        "primary-foreground": "210 60% 8%",
        secondary: "210 40% 16%",
        "secondary-foreground": "200 30% 82%",
        muted: "210 40% 20%",
        "muted-foreground": "200 15% 58%",
        accent: "185 80% 45%",
        "accent-foreground": "200 30% 82%",
        destructive: "0 75% 55%",
        "destructive-foreground": "200 30% 92%",
        border: "210 40% 23%",
        input: "210 40% 16%",
        ring: "200 80% 55%",
      },
    },
  },

  // Sunset theme with warm oranges and purples
  sunset: {
    name: "sunset",
    displayName: "Sunset",
    description: "Warm sunset colors with orange and purple accents",
    colors: {
      light: {
        background: "30 100% 98%",
        foreground: "330 50% 10%",
        card: "30 100% 96%",
        "card-foreground": "330 50% 10%",
        popover: "30 100% 98%",
        "popover-foreground": "330 50% 10%",
        primary: "25 100% 60%",
        "primary-foreground": "0 0% 98%",
        secondary: "30 20% 90%",
        "secondary-foreground": "330 50% 10%",
        muted: "30 20% 92%",
        "muted-foreground": "330 20% 45%",
        accent: "290 100% 70%",
        "accent-foreground": "0 0% 98%",
        destructive: "0 84% 60%",
        "destructive-foreground": "0 0% 98%",
        border: "30 20% 85%",
        input: "30 20% 88%",
        ring: "25 100% 60%",
      },
      dark: {
        background: "330 50% 4%",
        foreground: "30 80% 90%",
        card: "330 50% 6%",
        "card-foreground": "30 80% 90%",
        popover: "330 50% 4%",
        "popover-foreground": "30 80% 90%",
        primary: "25 100% 65%",
        "primary-foreground": "330 50% 4%",
        secondary: "330 30% 12%",
        "secondary-foreground": "30 80% 90%",
        muted: "330 30% 12%",
        "muted-foreground": "30 20% 65%",
        accent: "290 100% 75%",
        "accent-foreground": "330 50% 4%",
        destructive: "0 84% 60%",
        "destructive-foreground": "30 80% 90%",
        border: "330 30% 15%",
        input: "330 30% 12%",
        ring: "25 100% 65%",
      },
      dim: {
        background: "330 40% 8%",
        foreground: "30 50% 82%",
        card: "330 40% 11%",
        "card-foreground": "30 50% 82%",
        popover: "330 40% 9%",
        "popover-foreground": "30 50% 84%",
        primary: "25 85% 60%",
        "primary-foreground": "330 40% 8%",
        secondary: "330 25% 16%",
        "secondary-foreground": "30 50% 82%",
        muted: "330 25% 20%",
        "muted-foreground": "30 20% 58%",
        accent: "290 85% 68%",
        "accent-foreground": "30 50% 82%",
        destructive: "0 75% 55%",
        "destructive-foreground": "30 50% 92%",
        border: "330 25% 23%",
        input: "330 25% 16%",
        ring: "25 85% 60%",
      },
    },
  },
};

export function applyThemePreset(
  presetName: string,
  currentTheme: "light" | "dark" | "dim"
) {
  const preset = themePresets[presetName];
  if (!preset) {
    console.warn(`Theme preset "${presetName}" not found`);
    return;
  }

  const colors = preset.colors[currentTheme];
  const root = document.documentElement;

  Object.entries(colors).forEach(([key, value]) => {
    root.style.setProperty(`--${key}`, value);
  });
}

export function getAvailablePresets() {
  return Object.values(themePresets);
}
