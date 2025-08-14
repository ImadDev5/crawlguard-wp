"use client";

import { useThemeKeyboardShortcuts } from "@/hooks/use-theme-keyboard-shortcuts";

export function ThemeKeyboardShortcuts() {
  useThemeKeyboardShortcuts();
  return null; // This component doesn't render anything, it just adds keyboard shortcuts
}
