"use client";

import React, { createContext, useContext, useEffect, useState } from "react";
import { useTheme } from "next-themes";

interface ExtendedThemeContextValue {
  // Basic theme info
  theme: string | undefined;
  setTheme: (theme: string) => void;
  resolvedTheme: string | undefined;

  // Enhanced utilities
  isDark: boolean;
  isDim: boolean;
  isLight: boolean;
  isSystem: boolean;
  mounted: boolean;

  // Theme actions
  toggleTheme: () => void;
  cycleThemes: () => void;

  // Theme history
  previousTheme: string | null;

  // Theme preferences
  autoSwitchTime?: {
    darkStart: string;
    lightStart: string;
  };

  // Analytics
  themeChangeCount: number;
  lastThemeChange: Date | null;
}

const ExtendedThemeContext = createContext<
  ExtendedThemeContextValue | undefined
>(undefined);

interface ExtendedThemeProviderProps {
  children: React.ReactNode;
  enableAutoSwitch?: boolean;
  darkStart?: string; // e.g., "18:00"
  lightStart?: string; // e.g., "06:00"
}

export function ExtendedThemeProvider({
  children,
  enableAutoSwitch = false,
  darkStart = "18:00",
  lightStart = "06:00",
}: ExtendedThemeProviderProps) {
  const { theme, setTheme, resolvedTheme } = useTheme();
  const [mounted, setMounted] = useState(false);
  const [previousTheme, setPreviousTheme] = useState<string | null>(null);
  const [themeChangeCount, setThemeChangeCount] = useState(0);
  const [lastThemeChange, setLastThemeChange] = useState<Date | null>(null);

  useEffect(() => {
    setMounted(true);

    // Load analytics from localStorage
    const savedCount = localStorage.getItem("theme-change-count");
    const savedLastChange = localStorage.getItem("theme-last-change");

    if (savedCount) setThemeChangeCount(parseInt(savedCount, 10));
    if (savedLastChange) setLastThemeChange(new Date(savedLastChange));
  }, []);

  // Theme state calculations
  const isDark = mounted && (resolvedTheme === "dark" || theme === "dark");
  const isDim = mounted && (resolvedTheme === "dim" || theme === "dim");
  const isLight = mounted && (resolvedTheme === "light" || theme === "light");
  const isSystem = mounted && theme === "system";

  // Enhanced setTheme with analytics
  const handleSetTheme = (newTheme: string) => {
    if (theme !== newTheme) {
      setPreviousTheme(theme || null);
      setTheme(newTheme);

      // Update analytics
      const newCount = themeChangeCount + 1;
      const changeTime = new Date();

      setThemeChangeCount(newCount);
      setLastThemeChange(changeTime);

      // Persist analytics
      localStorage.setItem("theme-change-count", newCount.toString());
      localStorage.setItem("theme-last-change", changeTime.toISOString());
    }
  };

  const toggleTheme = () => {
    if (!mounted) return;

    switch (theme) {
      case "light":
        handleSetTheme("dark");
        break;
      case "dark":
        handleSetTheme("dim");
        break;
      case "dim":
        handleSetTheme("light");
        break;
      default:
        handleSetTheme("light");
    }
  };

  const cycleThemes = () => {
    if (!mounted) return;

    const themes = ["light", "dark", "dim", "system"];
    const currentIndex = themes.indexOf(theme || "system");
    const nextIndex = (currentIndex + 1) % themes.length;
    handleSetTheme(themes[nextIndex]);
  };

  // Auto-switch based on time (optional feature)
  useEffect(() => {
    if (!enableAutoSwitch || !mounted || theme !== "system") return;

    const checkTimeBasedTheme = () => {
      const now = new Date();
      const currentTime = `${now.getHours().toString().padStart(2, "0")}:${now.getMinutes().toString().padStart(2, "0")}`;

      const isDarkTime = currentTime >= darkStart || currentTime < lightStart;
      const shouldBeDark = isDarkTime ? "dim" : "light"; // Use dim for dark hours

      if (resolvedTheme !== shouldBeDark) {
        handleSetTheme(shouldBeDark);
      }
    };

    // Check every minute
    const interval = setInterval(checkTimeBasedTheme, 60000);
    checkTimeBasedTheme(); // Check immediately

    return () => clearInterval(interval);
  }, [enableAutoSwitch, darkStart, lightStart, mounted, theme, resolvedTheme]);

  const value: ExtendedThemeContextValue = {
    theme,
    setTheme: handleSetTheme,
    resolvedTheme,
    isDark,
    isDim,
    isLight,
    isSystem,
    mounted,
    toggleTheme,
    cycleThemes,
    previousTheme,
    autoSwitchTime: enableAutoSwitch ? { darkStart, lightStart } : undefined,
    themeChangeCount,
    lastThemeChange,
  };

  return (
    <ExtendedThemeContext.Provider value={value}>
      {children}
    </ExtendedThemeContext.Provider>
  );
}

export function useExtendedTheme() {
  const context = useContext(ExtendedThemeContext);
  if (!context) {
    throw new Error(
      "useExtendedTheme must be used within an ExtendedThemeProvider"
    );
  }
  return context;
}
