"use client";

import { useThemeUtils } from "@/hooks/use-theme-utils";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Info, Keyboard } from "lucide-react";
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/ui/tooltip";

interface ThemeDebuggerProps {
  show?: boolean;
}

export function ThemeDebugger({ show = false }: ThemeDebuggerProps) {
  const { theme, resolvedTheme, isDark, isDim, isLight, mounted, cycleThemes } =
    useThemeUtils();

  if (!show || !mounted) return null;

  return (
    <TooltipProvider>
      <div className="fixed bottom-4 right-4 z-50 flex flex-col gap-2 p-3 bg-card border border-border rounded-lg shadow-lg backdrop-blur-sm">
        <div className="flex items-center gap-2 text-sm">
          <Info className="h-4 w-4" />
          <span className="font-medium">Theme Debug</span>
        </div>
        <div className="flex flex-col gap-1 text-xs">
          <div className="flex items-center gap-2">
            <span>Current:</span>
            <Badge variant="outline">{theme}</Badge>
          </div>
          <div className="flex items-center gap-2">
            <span>Resolved:</span>
            <Badge variant="outline">{resolvedTheme}</Badge>
          </div>
          <div className="flex items-center gap-2">
            <span>State:</span>
            <Badge variant={isLight ? "default" : "secondary"}>
              {isLight ? "Light" : isDark ? "Dark" : isDim ? "Dim" : "Unknown"}
            </Badge>
          </div>
        </div>
        <Tooltip>
          <TooltipTrigger asChild>
            <Button
              variant="outline"
              size="sm"
              onClick={cycleThemes}
              className="text-xs"
            >
              <Keyboard className="h-3 w-3 mr-1" />
              Cycle Themes
            </Button>
          </TooltipTrigger>
          <TooltipContent>
            <p>Ctrl+Shift+T to cycle themes</p>
          </TooltipContent>
        </Tooltip>
      </div>
    </TooltipProvider>
  );
}
