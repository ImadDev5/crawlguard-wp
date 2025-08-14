import * as React from "react";
import { Slot } from "@radix-ui/react-slot";
import { cva, type VariantProps } from "class-variance-authority";

import { cn } from "@/lib/utils";

const buttonVariants = cva(
  "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium select-none disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none relative overflow-hidden transition-all duration-200 focus-visible:outline-none aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive border border-border/60 dark:border-border",
  {
    variants: {
      variant: {
        default:
          "bg-primary text-primary-foreground shadow-[0_1px_0_0_hsl(var(--primary)/0.4),0_1px_2px_0_hsl(var(--primary)/0.3)] hover:bg-primary/90 active:scale-[0.985] focus-visible:ring-2 focus-visible:ring-ring/60 focus-visible:ring-offset-2 focus-visible:ring-offset-background border-primary/20 dark:border-primary/40",
        destructive:
          "bg-destructive text-white shadow-xs hover:bg-destructive/90 focus-visible:ring-2 focus-visible:ring-destructive/40 focus-visible:ring-offset-2 focus-visible:ring-offset-background dark:bg-destructive/70 border-destructive/20 dark:border-destructive/40",
        outline:
          "border-border/80 dark:border-border bg-background/60 backdrop-blur-sm shadow-xs hover:bg-accent/60 hover:text-accent-foreground hover:border-border dark:bg-input/30 dark:hover:bg-input/50 dark:hover:border-border/80 focus-visible:ring-2 focus-visible:ring-ring/50 focus-visible:ring-offset-2 focus-visible:ring-offset-background",
        secondary:
          "bg-secondary text-secondary-foreground shadow-xs hover:bg-secondary/80 focus-visible:ring-2 focus-visible:ring-ring/50 focus-visible:ring-offset-2 focus-visible:ring-offset-background border-border/40 dark:border-border/60",
        ghost:
          "hover:bg-accent/60 hover:text-accent-foreground dark:hover:bg-accent/40 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:ring-offset-2 focus-visible:ring-offset-background border-transparent hover:border-border/40 dark:hover:border-border/60",
        link: "text-primary underline-offset-4 hover:underline hover:opacity-85 focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:ring-offset-2 focus-visible:ring-offset-background border-transparent",
        subtle:
          "bg-muted/60 text-foreground/90 hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring/40 focus-visible:ring-offset-2 focus-visible:ring-offset-background dark:bg-muted/40 dark:hover:bg-muted/60 border-border/30 dark:border-border/50",
      },
      size: {
        default: "h-9 px-4 py-2 has-[>svg]:px-3",
        sm: "h-8 rounded-md gap-1.5 px-3 has-[>svg]:px-2.5",
        lg: "h-11 rounded-md px-6 has-[>svg]:px-4 text-base",
        icon: "size-9",
      },
      elevation: {
        none: "shadow-none",
        sm: "shadow-sm",
        md: "shadow-md hover:shadow-lg",
      },
      round: {
        md: "rounded-md",
        full: "rounded-full",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
      elevation: "sm",
      round: "md",
    },
  }
);

function Button({
  className,
  variant,
  size,
  elevation,
  round,
  asChild = false,
  ...props
}: React.ComponentProps<"button"> &
  VariantProps<typeof buttonVariants> & {
    asChild?: boolean;
  }) {
  const Comp = asChild ? Slot : "button";

  return (
    <Comp
      data-slot="button"
      className={cn(
        buttonVariants({ variant, size, elevation, round, className })
      )}
      {...props}
    />
  );
}

export { Button, buttonVariants };
