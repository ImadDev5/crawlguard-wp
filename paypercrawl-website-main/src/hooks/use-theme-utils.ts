"use client";

import { useTheme } from "next-themes";
import { useEffect, useState } from "react";

export function useThemeUtils() {
  const { theme, setTheme, resolvedTheme } = useTheme();
  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    setMounted(true);
  }, []);

  const isDark = mounted && (resolvedTheme === "dark" || theme === "dark");
  const isDim = mounted && (resolvedTheme === "dim" || theme === "dim");
  const isLight = mounted && (resolvedTheme === "light" || theme === "light");
  const isSystem = mounted && theme === "system";

  const toggleTheme = () => {
    if (!mounted) return;

    switch (theme) {
      case "light":
        setTheme("dark");
        break;
      case "dark":
        setTheme("dim");
        break;
      case "dim":
        setTheme("light");
        break;
      default:
        setTheme("light");
    }
  };

  const cycleThemes = () => {
    if (!mounted) return;

    const themes = ["light", "dark", "dim", "system"];
    const currentIndex = themes.indexOf(theme || "system");
    const nextIndex = (currentIndex + 1) % themes.length;
    setTheme(themes[nextIndex]);
  };

  return {
    theme,
    setTheme,
    resolvedTheme,
    mounted,
    isDark,
    isDim,
    isLight,
    isSystem,
    toggleTheme,
    cycleThemes,
  };
}
