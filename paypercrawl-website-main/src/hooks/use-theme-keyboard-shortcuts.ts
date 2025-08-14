"use client";

import { useEffect } from "react";
import { useTheme } from "next-themes";

export function useThemeKeyboardShortcuts() {
  const { setTheme, theme } = useTheme();

  useEffect(() => {
    const handleKeyDown = (event: KeyboardEvent) => {
      // Only trigger on Ctrl+Shift+T (or Cmd+Shift+T on Mac)
      if (
        (event.ctrlKey || event.metaKey) &&
        event.shiftKey &&
        event.code === "KeyT"
      ) {
        event.preventDefault();

        // Cycle through themes: light -> dark -> dim -> system -> light
        switch (theme) {
          case "light":
            setTheme("dark");
            break;
          case "dark":
            setTheme("dim");
            break;
          case "dim":
            setTheme("system");
            break;
          default:
            setTheme("light");
        }
      }
    };

    document.addEventListener("keydown", handleKeyDown);
    return () => document.removeEventListener("keydown", handleKeyDown);
  }, [theme, setTheme]);
}
