"use client";

import { useThemeUtils } from "@/hooks/use-theme-utils";
import { cn } from "@/lib/utils";

/**
 * Theme-aware utility functions for developers
 */

export interface ThemeVariants {
  light?: string;
  dark?: string;
  dim?: string;
  default?: string;
}

/**
 * Applies theme-specific classes based on current theme
 */
export function useThemeVariants(variants: ThemeVariants) {
  const { isLight, isDark, isDim } = useThemeUtils();

  return cn(
    variants.default,
    isLight && variants.light,
    isDark && variants.dark,
    isDim && variants.dim
  );
}

/**
 * Hook for theme-aware styling
 */
export function useThemeClasses() {
  const { isLight, isDark, isDim, theme } = useThemeUtils();

  // Pre-computed variants
  const surface = useThemeVariants({
    light: "bg-white border-gray-200",
    dark: "bg-gray-900 border-gray-800",
    dim: "bg-card border-border",
    default: "bg-card border-border",
  });

  const text = useThemeVariants({
    light: "text-gray-900",
    dark: "text-gray-100",
    dim: "text-foreground",
    default: "text-foreground",
  });

  const muted = useThemeVariants({
    light: "text-gray-600",
    dark: "text-gray-400",
    dim: "text-muted-foreground",
    default: "text-muted-foreground",
  });

  const accent = useThemeVariants({
    light: "bg-blue-50 text-blue-900 border-blue-200",
    dark: "bg-blue-950 text-blue-100 border-blue-800",
    dim: "bg-accent text-accent-foreground border-accent/40",
    default: "bg-accent text-accent-foreground",
  });

  const interactive = useThemeVariants({
    light: "hover:bg-gray-50 active:bg-gray-100",
    dark: "hover:bg-gray-800 active:bg-gray-700",
    dim: "hover:bg-accent/50 active:bg-accent/70",
    default: "hover:bg-accent/50",
  });

  const glass = useThemeVariants({
    light: "bg-white/80 backdrop-blur-lg border-white/20",
    dark: "bg-gray-900/80 backdrop-blur-lg border-white/10",
    dim: "bg-card/80 backdrop-blur-lg border-border/30",
    default: "bg-card/80 backdrop-blur-lg",
  });

  return {
    // Base theme classes
    themeClass: theme,
    isLight,
    isDark,
    isDim,

    // Pre-built common variants
    surface,
    text,
    muted,
    accent,
    interactive,
    glass,

    // Utility function for custom variants
    getVariant: (variants: ThemeVariants) => {
      return cn(
        variants.default,
        isLight && variants.light,
        isDark && variants.dark,
        isDim && variants.dim
      );
    },
  };
}

/**
 * Helper component for theme-aware conditional rendering
 */
interface ThemeConditionalProps {
  light?: React.ReactNode;
  dark?: React.ReactNode;
  dim?: React.ReactNode;
  fallback?: React.ReactNode;
}

export function ThemeConditional({
  light,
  dark,
  dim,
  fallback,
}: ThemeConditionalProps) {
  const { isLight, isDark, isDim } = useThemeUtils();

  if (isLight && light) return light as React.ReactElement;
  if (isDark && dark) return dark as React.ReactElement;
  if (isDim && dim) return dim as React.ReactElement;
  return fallback as React.ReactElement;
}

/**
 * Theme-aware CSS custom property getter
 */
export function useThemeTokens() {
  const { theme } = useThemeUtils();

  const getToken = (tokenName: string) => {
    if (typeof document === "undefined") return "";
    return getComputedStyle(document.documentElement)
      .getPropertyValue(`--${tokenName}`)
      .trim();
  };

  return {
    // Color tokens
    background: getToken("background"),
    foreground: getToken("foreground"),
    primary: getToken("primary"),
    primaryForeground: getToken("primary-foreground"),
    secondary: getToken("secondary"),
    secondaryForeground: getToken("secondary-foreground"),
    muted: getToken("muted"),
    mutedForeground: getToken("muted-foreground"),
    accent: getToken("accent"),
    accentForeground: getToken("accent-foreground"),
    border: getToken("border"),
    input: getToken("input"),
    ring: getToken("ring"),

    // Helper to get any token
    get: getToken,

    // Current theme info
    currentTheme: theme,
  };
}

/**
 * Performance-optimized theme detection for animations
 */
export function useThemeOptimized() {
  const { theme, resolvedTheme, mounted } = useThemeUtils();

  // Memoized theme state for performance-critical components
  const themeState = {
    current: theme,
    resolved: resolvedTheme,
    mounted,
    isDarkMode: resolvedTheme === "dark" || resolvedTheme === "dim",
    isLightMode: resolvedTheme === "light",
    prefersDark: resolvedTheme !== "light",
  };

  return themeState;
}
