"use client";

import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { Code2, Palette, Keyboard, Eye } from "lucide-react";

export function ThemeDocumentation() {
  return (
    <div className="space-y-6">
      <div className="space-y-2">
        <h1 className="text-3xl font-bold text-foreground">Theme System</h1>
        <p className="text-muted-foreground">
          Complete guide to using the GitHub Dark Dimmed theme system
        </p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Available Themes */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Palette className="h-5 w-5" />
              Available Themes
            </CardTitle>
            <CardDescription>
              Three themes with automatic system detection
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-3">
            <div className="space-y-2">
              <div className="flex items-center justify-between">
                <span className="font-medium">Light Theme</span>
                <Badge variant="outline">Default</Badge>
              </div>
              <p className="text-sm text-muted-foreground">
                Clean, bright theme for daytime use
              </p>
            </div>

            <Separator />

            <div className="space-y-2">
              <div className="flex items-center justify-between">
                <span className="font-medium">Dark Theme</span>
                <Badge variant="outline">High Contrast</Badge>
              </div>
              <p className="text-sm text-muted-foreground">
                Traditional dark theme with high contrast
              </p>
            </div>

            <Separator />

            <div className="space-y-2">
              <div className="flex items-center justify-between">
                <span className="font-medium">Dark Dimmed</span>
                <Badge variant="outline">GitHub Style</Badge>
              </div>
              <p className="text-sm text-muted-foreground">
                VS Code GitHub Dark Dimmed - easier on the eyes
              </p>
            </div>

            <Separator />

            <div className="space-y-2">
              <div className="flex items-center justify-between">
                <span className="font-medium">System</span>
                <Badge variant="outline">Auto</Badge>
              </div>
              <p className="text-sm text-muted-foreground">
                Follows system preference automatically
              </p>
            </div>
          </CardContent>
        </Card>

        {/* Usage Guidelines */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Code2 className="h-5 w-5" />
              Usage Guidelines
            </CardTitle>
            <CardDescription>
              Best practices for theme-aware components
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-3">
            <div className="space-y-2">
              <h4 className="font-medium">Semantic Colors</h4>
              <p className="text-sm text-muted-foreground">
                Use semantic color tokens like{" "}
                <code className="bg-muted px-1 rounded">bg-background</code>,
                <code className="bg-muted px-1 rounded">text-foreground</code>
              </p>
            </div>

            <div className="space-y-2">
              <h4 className="font-medium">Avoid Hardcoded Colors</h4>
              <p className="text-sm text-muted-foreground">
                Don't use{" "}
                <code className="bg-muted px-1 rounded">bg-white</code> or
                <code className="bg-muted px-1 rounded">text-black</code> - use
                semantic tokens instead
              </p>
            </div>

            <div className="space-y-2">
              <h4 className="font-medium">Test All Themes</h4>
              <p className="text-sm text-muted-foreground">
                Always test components in all three themes to ensure proper
                contrast
              </p>
            </div>
          </CardContent>
        </Card>

        {/* Keyboard Shortcuts */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Keyboard className="h-5 w-5" />
              Keyboard Shortcuts
            </CardTitle>
            <CardDescription>Quick theme switching</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              <div className="flex items-center justify-between p-2 bg-muted rounded">
                <span className="text-sm">Cycle Themes</span>
                <Badge variant="outline">Ctrl + Shift + T</Badge>
              </div>
              <p className="text-xs text-muted-foreground">
                Cycles through: Light → Dark → Dim → System → Light
              </p>
            </div>
          </CardContent>
        </Card>

        {/* Color Tokens */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Eye className="h-5 w-5" />
              Color Tokens
            </CardTitle>
            <CardDescription>Available CSS custom properties</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-2 text-xs font-mono">
              <div className="grid grid-cols-2 gap-1">
                <code className="text-muted-foreground">--background</code>
                <code className="text-muted-foreground">--foreground</code>
                <code className="text-muted-foreground">--card</code>
                <code className="text-muted-foreground">--card-foreground</code>
                <code className="text-muted-foreground">--primary</code>
                <code className="text-muted-foreground">
                  --primary-foreground
                </code>
                <code className="text-muted-foreground">--secondary</code>
                <code className="text-muted-foreground">
                  --secondary-foreground
                </code>
                <code className="text-muted-foreground">--muted</code>
                <code className="text-muted-foreground">
                  --muted-foreground
                </code>
                <code className="text-muted-foreground">--accent</code>
                <code className="text-muted-foreground">
                  --accent-foreground
                </code>
                <code className="text-muted-foreground">--border</code>
                <code className="text-muted-foreground">--input</code>
                <code className="text-muted-foreground">--ring</code>
                <code className="text-muted-foreground">--destructive</code>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
