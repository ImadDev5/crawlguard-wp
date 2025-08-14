"use client";

import { cn } from "@/lib/utils";
import { useThemeUtils } from "@/hooks/use-theme-utils";
import { Card } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { ComponentProps } from "react";

// Theme-aware Card with enhanced styling per theme
interface ThemeCardProps extends ComponentProps<typeof Card> {
  variant?: "default" | "elevated" | "glass" | "bordered";
}

export function ThemeCard({
  className,
  variant = "default",
  ...props
}: ThemeCardProps) {
  const { isDim, isDark, isLight } = useThemeUtils();

  const variants = {
    default: cn(
      "bg-card border border-border",
      isDim && "bg-card/95 border-border/60 backdrop-blur-sm",
      isDark && "bg-card border-border shadow-lg",
      isLight && "bg-card border-border/20 shadow-sm"
    ),
    elevated: cn(
      "bg-card border border-border shadow-lg",
      isDim && "bg-card/98 border-border/40 shadow-xl backdrop-blur-md",
      isDark && "bg-card border-border/30 shadow-2xl",
      isLight && "bg-white border-border/10 shadow-md"
    ),
    glass: cn(
      "bg-card/80 border border-border/50 backdrop-blur-xl",
      isDim && "bg-card/60 border-border/30 backdrop-blur-2xl",
      isDark && "bg-card/70 border-border/40 backdrop-blur-xl",
      isLight && "bg-white/90 border-border/20 backdrop-blur-lg"
    ),
    bordered: cn(
      "bg-card border-2 border-border",
      isDim && "bg-card border-border/80",
      isDark && "bg-card border-border/60",
      isLight && "bg-card border-border/30"
    ),
  };

  return <Card className={cn(variants[variant], className)} {...props} />;
}

// Theme-aware Button with enhanced hover states
interface ThemeButtonProps extends ComponentProps<typeof Button> {
  glow?: boolean;
}

export function ThemeButton({
  className,
  glow = false,
  variant = "default",
  ...props
}: ThemeButtonProps) {
  const { isDim, isDark, isLight } = useThemeUtils();

  const glowEffect = glow
    ? cn(
        "transition-all duration-300",
        isDim && "hover:shadow-lg hover:shadow-primary/25",
        isDark && "hover:shadow-lg hover:shadow-primary/30",
        isLight && "hover:shadow-md hover:shadow-primary/20"
      )
    : "";

  const themeEnhancements = cn(
    isDim && variant === "default" && "bg-primary/90 hover:bg-primary/80",
    isDark && variant === "outline" && "border-border/60 hover:border-border",
    isLight && variant === "ghost" && "hover:bg-accent/80"
  );

  return (
    <Button
      className={cn(glowEffect, themeEnhancements, className)}
      variant={variant}
      {...props}
    />
  );
}

// Theme-aware Badge with dynamic colors
interface ThemeBadgeProps extends ComponentProps<typeof Badge> {
  adaptive?: boolean;
}

export function ThemeBadge({
  className,
  adaptive = false,
  variant = "default",
  ...props
}: ThemeBadgeProps) {
  const { isDim, isDark, isLight } = useThemeUtils();

  const adaptiveStyles = adaptive
    ? cn(
        isDim && "bg-accent/80 text-accent-foreground border-accent/40",
        isDark && "bg-accent text-accent-foreground border-accent/60",
        isLight && "bg-accent/60 text-accent-foreground border-accent/30"
      )
    : "";

  return (
    <Badge
      className={cn(adaptiveStyles, className)}
      variant={variant}
      {...props}
    />
  );
}

// Theme-aware Container with responsive spacing
interface ThemeContainerProps extends React.HTMLAttributes<HTMLDivElement> {
  maxWidth?: "sm" | "md" | "lg" | "xl" | "2xl" | "full";
  padding?: "none" | "sm" | "md" | "lg" | "xl";
}

export function ThemeContainer({
  className,
  maxWidth = "lg",
  padding = "md",
  children,
  ...props
}: ThemeContainerProps) {
  const { isDim, isDark, isLight } = useThemeUtils();

  const maxWidths = {
    sm: "max-w-sm",
    md: "max-w-md",
    lg: "max-w-4xl",
    xl: "max-w-6xl",
    "2xl": "max-w-7xl",
    full: "max-w-full",
  };

  const paddings = {
    none: "",
    sm: "p-2 sm:p-4",
    md: "p-4 sm:p-6 lg:p-8",
    lg: "p-6 sm:p-8 lg:p-12",
    xl: "p-8 sm:p-12 lg:p-16",
  };

  const themeSpecificStyles = cn(
    isDim && "selection:bg-primary/20 selection:text-primary-foreground",
    isDark && "selection:bg-primary/30 selection:text-primary-foreground",
    isLight && "selection:bg-primary/10 selection:text-primary-foreground"
  );

  return (
    <div
      className={cn(
        "mx-auto",
        maxWidths[maxWidth],
        paddings[padding],
        themeSpecificStyles,
        className
      )}
      {...props}
    >
      {children}
    </div>
  );
}

// Theme-aware Text with proper contrast
interface ThemeTextProps extends React.HTMLAttributes<HTMLElement> {
  as?: "h1" | "h2" | "h3" | "h4" | "h5" | "h6" | "p" | "span" | "div";
  variant?: "default" | "muted" | "accent" | "primary";
  size?: "xs" | "sm" | "base" | "lg" | "xl" | "2xl" | "3xl" | "4xl";
}

export function ThemeText({
  as: Component = "p",
  variant = "default",
  size = "base",
  className,
  ...props
}: ThemeTextProps) {
  const { isDim, isDark, isLight } = useThemeUtils();

  const variants = {
    default: "text-foreground",
    muted: "text-muted-foreground",
    accent: "text-accent-foreground",
    primary: "text-primary",
  };

  const sizes = {
    xs: "text-xs",
    sm: "text-sm",
    base: "text-base",
    lg: "text-lg",
    xl: "text-xl",
    "2xl": "text-2xl",
    "3xl": "text-3xl",
    "4xl": "text-4xl",
  };

  const themeEnhancements = cn(
    isDim && variant === "default" && "text-foreground/95",
    isDark && variant === "muted" && "text-muted-foreground/90",
    isLight && variant === "accent" && "text-accent-foreground/95"
  );

  return (
    <Component
      className={cn(
        variants[variant],
        sizes[size],
        themeEnhancements,
        className
      )}
      {...props}
    />
  );
}
