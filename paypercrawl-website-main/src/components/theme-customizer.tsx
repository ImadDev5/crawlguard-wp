"use client";

import { useState } from "react";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Label } from "@/components/ui/label";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { Switch } from "@/components/ui/switch";
import { Separator } from "@/components/ui/separator";
import { useThemeUtils } from "@/hooks/use-theme-utils";
import {
  themePresets,
  applyThemePreset,
  getAvailablePresets,
} from "@/lib/theme-presets";
import {
  Palette,
  RotateCcw,
  Download,
  Upload,
  Eye,
  Settings,
  Sparkles,
} from "lucide-react";

export function ThemeCustomizer() {
  const { theme, setTheme, isDim, isDark, isLight } = useThemeUtils();
  const [selectedPreset, setSelectedPreset] = useState("github");
  const [previewMode, setPreviewMode] = useState(false);
  const [autoApply, setAutoApply] = useState(false);

  const availablePresets = getAvailablePresets();

  const handlePresetChange = (presetName: string) => {
    setSelectedPreset(presetName);
    if (autoApply && theme) {
      applyThemePreset(presetName, theme as "light" | "dark" | "dim");
    }
  };

  const applyPreset = () => {
    if (theme) {
      applyThemePreset(selectedPreset, theme as "light" | "dark" | "dim");
    }
  };

  const resetToDefault = () => {
    // Reset to default GitHub theme
    setSelectedPreset("github");
    if (theme) {
      applyThemePreset("github", theme as "light" | "dark" | "dim");
    }
  };

  const exportTheme = () => {
    const root = document.documentElement;
    const computedStyles = getComputedStyle(root);

    const themeData = {
      name: `custom-${Date.now()}`,
      preset: selectedPreset,
      currentTheme: theme,
      colors: {} as Record<string, string>,
    };

    // Extract current CSS custom properties
    const cssProperties = [
      "background",
      "foreground",
      "card",
      "card-foreground",
      "popover",
      "popover-foreground",
      "primary",
      "primary-foreground",
      "secondary",
      "secondary-foreground",
      "muted",
      "muted-foreground",
      "accent",
      "accent-foreground",
      "destructive",
      "destructive-foreground",
      "border",
      "input",
      "ring",
    ];

    cssProperties.forEach((prop) => {
      const value = computedStyles.getPropertyValue(`--${prop}`).trim();
      if (value) themeData.colors[prop] = value;
    });

    // Download as JSON
    const blob = new Blob([JSON.stringify(themeData, null, 2)], {
      type: "application/json",
    });
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = `theme-${themeData.name}.json`;
    a.click();
    URL.revokeObjectURL(url);
  };

  const currentPreset = themePresets[selectedPreset];

  return (
    <Card className="w-full max-w-2xl">
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Palette className="h-5 w-5" />
          Theme Customizer
        </CardTitle>
        <CardDescription>
          Experiment with different color presets and customize your theme
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-6">
        {/* Current Theme Info */}
        <div className="space-y-2">
          <Label className="text-sm font-medium">Current Theme</Label>
          <div className="flex items-center gap-2">
            <Badge variant="outline">{theme}</Badge>
            <Badge variant={isLight ? "default" : "secondary"}>
              {isLight ? "Light" : isDark ? "Dark" : isDim ? "Dim" : "Unknown"}
            </Badge>
          </div>
        </div>

        <Separator />

        {/* Preset Selection */}
        <div className="space-y-3">
          <Label className="text-sm font-medium">Theme Preset</Label>
          <Select value={selectedPreset} onValueChange={handlePresetChange}>
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              {availablePresets.map((preset) => (
                <SelectItem key={preset.name} value={preset.name}>
                  <div className="flex items-center gap-2">
                    <div
                      className="w-3 h-3 rounded-full border"
                      style={{
                        backgroundColor: `hsl(${preset.colors[theme as keyof typeof preset.colors]?.primary || preset.colors.light.primary})`,
                      }}
                    />
                    {preset.displayName}
                  </div>
                </SelectItem>
              ))}
            </SelectContent>
          </Select>

          {currentPreset && (
            <p className="text-xs text-muted-foreground">
              {currentPreset.description}
            </p>
          )}
        </div>

        {/* Options */}
        <div className="space-y-3">
          <div className="flex items-center justify-between">
            <div className="space-y-0.5">
              <Label className="text-sm">Auto Apply</Label>
              <p className="text-xs text-muted-foreground">
                Automatically apply preset when selected
              </p>
            </div>
            <Switch checked={autoApply} onCheckedChange={setAutoApply} />
          </div>

          <div className="flex items-center justify-between">
            <div className="space-y-0.5">
              <Label className="text-sm">Preview Mode</Label>
              <p className="text-xs text-muted-foreground">
                Show live preview while selecting
              </p>
            </div>
            <Switch checked={previewMode} onCheckedChange={setPreviewMode} />
          </div>
        </div>

        <Separator />

        {/* Actions */}
        <div className="flex flex-wrap gap-2">
          <Button
            onClick={applyPreset}
            disabled={autoApply}
            className="flex items-center gap-2"
          >
            <Sparkles className="h-4 w-4" />
            Apply Preset
          </Button>

          <Button
            variant="outline"
            onClick={resetToDefault}
            className="flex items-center gap-2"
          >
            <RotateCcw className="h-4 w-4" />
            Reset
          </Button>

          <Button
            variant="outline"
            onClick={exportTheme}
            className="flex items-center gap-2"
          >
            <Download className="h-4 w-4" />
            Export
          </Button>
        </div>

        {/* Color Preview */}
        {currentPreset && theme && (
          <div className="space-y-3">
            <Label className="text-sm font-medium">Color Preview</Label>
            <div className="grid grid-cols-4 gap-2">
              {Object.entries(
                currentPreset.colors[
                  theme as keyof typeof currentPreset.colors
                ] || {}
              ).map(([key, value]) => (
                <div key={key} className="space-y-1">
                  <div
                    className="w-full h-8 rounded border shadow-sm"
                    style={{ backgroundColor: `hsl(${value})` }}
                  />
                  <p
                    className="text-xs text-muted-foreground truncate"
                    title={key}
                  >
                    {key}
                  </p>
                </div>
              ))}
            </div>
          </div>
        )}

        {/* Theme Switching Test */}
        <div className="space-y-3">
          <Label className="text-sm font-medium">Test Theme Switching</Label>
          <div className="flex gap-2">
            <Button
              size="sm"
              variant={isLight ? "default" : "outline"}
              onClick={() => setTheme("light")}
            >
              Light
            </Button>
            <Button
              size="sm"
              variant={isDark ? "default" : "outline"}
              onClick={() => setTheme("dark")}
            >
              Dark
            </Button>
            <Button
              size="sm"
              variant={isDim ? "default" : "outline"}
              onClick={() => setTheme("dim")}
            >
              Dim
            </Button>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
