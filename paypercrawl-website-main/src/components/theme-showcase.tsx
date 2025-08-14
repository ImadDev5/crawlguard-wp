"use client";

import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { useThemeClasses, ThemeConditional } from "@/lib/theme-utils";
import { useThemeUtils } from "@/hooks/use-theme-utils";
import { Sun, Moon, SunDim, Check, Star, Code, Palette } from "lucide-react";

/**
 * Example component demonstrating theme system best practices
 * This showcases all three themes with proper semantic tokens
 */
export function ThemeShowcaseCard() {
  const { theme, toggleTheme } = useThemeUtils();
  const { surface, text, muted, accent, interactive, glass, getVariant } =
    useThemeClasses();

  // Custom variant example
  const featureCardStyle = getVariant({
    light: "bg-gradient-to-br from-blue-50 to-indigo-50 border-blue-200",
    dark: "bg-gradient-to-br from-blue-950/50 to-indigo-950/50 border-blue-800",
    dim: "bg-gradient-to-br from-accent/30 to-primary/10 border-accent",
    default: "bg-gradient-to-br from-accent to-primary/20",
  });

  const statusBadgeStyle = getVariant({
    light: "bg-green-100 text-green-800 border-green-200",
    dark: "bg-green-900/30 text-green-400 border-green-800",
    dim: "bg-green-500/20 text-green-400 border-green-500/30",
    default: "bg-accent text-accent-foreground",
  });

  return (
    <div className="space-y-6">
      {/* Theme Status Header */}
      <Card className={`${surface} transition-all duration-300`}>
        <CardHeader>
          <div className="flex items-center justify-between">
            <div className="space-y-2">
              <CardTitle className={text}>
                <ThemeConditional
                  light={
                    <span className="flex items-center gap-2">
                      <Sun className="h-5 w-5" /> Light Theme Active
                    </span>
                  }
                  dark={
                    <span className="flex items-center gap-2">
                      <Moon className="h-5 w-5" /> Dark Theme Active
                    </span>
                  }
                  dim={
                    <span className="flex items-center gap-2">
                      <SunDim className="h-5 w-5" /> GitHub Dark Dimmed Active
                    </span>
                  }
                  fallback={<span>Theme Loading...</span>}
                />
              </CardTitle>
              <CardDescription className={muted}>
                Current theme:{" "}
                <code className={`${accent} px-2 py-1 rounded text-sm`}>
                  {theme}
                </code>
              </CardDescription>
            </div>
            <Badge className={statusBadgeStyle}>
              <Check className="h-3 w-3 mr-1" />
              Active
            </Badge>
          </div>
        </CardHeader>
        <CardContent>
          <div className="flex flex-wrap gap-3">
            <Button onClick={toggleTheme} className={interactive}>
              <Palette className="h-4 w-4 mr-2" />
              Toggle Theme
            </Button>
            <Button
              variant="outline"
              className={`${surface} ${text} hover:${accent}`}
            >
              <Star className="h-4 w-4 mr-2" />
              Secondary Action
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Feature Cards Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {/* Card 1: Theme Features */}
        <Card className={featureCardStyle}>
          <CardHeader>
            <CardTitle className={`${text} text-lg`}>
              <Code className="h-5 w-5 inline mr-2" />
              Theme Features
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-3">
            <div className={`${glass} p-3 rounded-lg`}>
              <p className={text}>✓ VS Code Accurate Colors</p>
            </div>
            <div className={`${glass} p-3 rounded-lg`}>
              <p className={text}>✓ Smooth Transitions</p>
            </div>
            <div className={`${glass} p-3 rounded-lg`}>
              <p className={text}>✓ Keyboard Shortcuts</p>
            </div>
          </CardContent>
        </Card>

        {/* Card 2: Color Palette */}
        <Card className={surface}>
          <CardHeader>
            <CardTitle className={text}>Color Palette</CardTitle>
            <CardDescription className={muted}>
              Current theme colors
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-4 gap-2">
              <div className="space-y-1">
                <div className="h-8 bg-background border rounded"></div>
                <p className={`${muted} text-xs`}>Background</p>
              </div>
              <div className="space-y-1">
                <div className="h-8 bg-card border rounded"></div>
                <p className={`${muted} text-xs`}>Card</p>
              </div>
              <div className="space-y-1">
                <div className="h-8 bg-primary rounded"></div>
                <p className={`${muted} text-xs`}>Primary</p>
              </div>
              <div className="space-y-1">
                <div className="h-8 bg-accent border rounded"></div>
                <p className={`${muted} text-xs`}>Accent</p>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Card 3: Interactive Elements */}
        <Card className={surface}>
          <CardHeader>
            <CardTitle className={text}>Interactive Demo</CardTitle>
            <CardDescription className={muted}>
              Hover and click states
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-3">
            <Button className="w-full" variant="default">
              Primary Button
            </Button>
            <Button className="w-full" variant="secondary">
              Secondary Button
            </Button>
            <div
              className={`${interactive} p-3 rounded-lg border cursor-pointer`}
            >
              <p className={text}>Hover me!</p>
              <p className={muted}>Interactive surface</p>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Typography Showcase */}
      <Card className={surface}>
        <CardHeader>
          <CardTitle className={text}>Typography Showcase</CardTitle>
          <CardDescription className={muted}>
            Text styles in current theme
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div>
            <h1 className={`${text} text-3xl font-bold`}>Heading 1</h1>
            <h2 className={`${text} text-2xl font-semibold`}>Heading 2</h2>
            <h3 className={`${text} text-xl font-medium`}>Heading 3</h3>
          </div>
          <div className="space-y-2">
            <p className={text}>
              This is regular body text in the current theme. It should be
              highly readable with proper contrast.
            </p>
            <p className={muted}>
              This is muted text, typically used for descriptions, captions, and
              secondary information.
            </p>
            <p className={`${text} font-mono text-sm ${accent} p-2 rounded`}>
              <code>const themeCode = "syntax-highlighted";</code>
            </p>
          </div>
        </CardContent>
      </Card>

      {/* Theme Instructions */}
      <Card className={`${glass} border-dashed`}>
        <CardContent className="pt-6">
          <div className="text-center space-y-2">
            <p className={text}>
              <kbd className={`${accent} px-2 py-1 rounded text-sm`}>Ctrl</kbd>{" "}
              +
              <kbd className={`${accent} px-2 py-1 rounded text-sm mx-1`}>
                Shift
              </kbd>{" "}
              +<kbd className={`${accent} px-2 py-1 rounded text-sm`}>T</kbd>
            </p>
            <p className={muted}>Press to cycle through themes</p>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
