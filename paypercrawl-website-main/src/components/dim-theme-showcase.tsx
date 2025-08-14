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
import {
  Palette,
  Sparkles,
  Eye,
  Layers,
  Zap,
  MousePointer,
  Monitor,
  Smartphone,
} from "lucide-react";

export default function DimThemeShowcase() {
  const features = [
    {
      icon: <Palette className="h-5 w-5" />,
      title: "Enhanced Color Palette",
      description: "VS Code GitHub Dark Dimmed colors with improved contrast",
    },
    {
      icon: <Sparkles className="h-5 w-5" />,
      title: "Glass Morphism Effects",
      description: "Beautiful backdrop blur with enhanced transparency",
    },
    {
      icon: <Eye className="h-5 w-5" />,
      title: "Better Visibility",
      description: "Fixed icon visibility and improved readability",
    },
    {
      icon: <Layers className="h-5 w-5" />,
      title: "Enhanced Shadows",
      description: "Depth-aware shadow system for better visual hierarchy",
    },
    {
      icon: <Zap className="h-5 w-5" />,
      title: "Smooth Animations",
      description: "Pulse effects and smooth transitions",
    },
    {
      icon: <MousePointer className="h-5 w-5" />,
      title: "Interactive Hover Effects",
      description: "Glow effects and micro-interactions",
    },
  ];

  return (
    <div className="p-6 space-y-6">
      <div className="text-center space-y-4">
        <Badge className="badge-enhanced pulse-glow">
          <Monitor className="mr-2 h-4 w-4" />
          Dim Theme Enhanced
        </Badge>
        <h2 className="text-3xl font-bold">Enhanced Dim Theme Features</h2>
        <p className="text-muted-foreground max-w-2xl mx-auto">
          Experience the enhanced GitHub Dark Dimmed theme with improved visual
          appeal, better contrast, and advanced UI effects.
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {features.map((feature, index) => (
          <Card
            key={index}
            className="glass-card hover-glow transition-all duration-300"
          >
            <CardHeader className="pb-3">
              <div className="flex items-center space-x-2">
                <div className="text-primary pulse-glow">{feature.icon}</div>
                <CardTitle className="text-lg">{feature.title}</CardTitle>
              </div>
            </CardHeader>
            <CardContent>
              <CardDescription className="text-sm">
                {feature.description}
              </CardDescription>
            </CardContent>
          </Card>
        ))}
      </div>

      <div className="text-center space-y-4">
        <Card className="glass-card hover-glow max-w-md mx-auto">
          <CardContent className="p-6">
            <div className="space-y-4">
              <div className="flex items-center justify-center space-x-2">
                <Smartphone className="h-5 w-5 text-primary" />
                <span className="font-semibold">Theme Status</span>
              </div>
              <div className="flex items-center justify-center space-x-2">
                <div className="w-3 h-3 bg-green-500 rounded-full pulse-glow"></div>
                <span className="text-sm text-muted-foreground">
                  Dim theme active and enhanced
                </span>
              </div>
              <Button
                variant="outline"
                size="sm"
                className="w-full hover-glow transition-all duration-300"
              >
                <Eye className="mr-2 h-4 w-4" />
                View All Themes
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>

      <div className="mt-8 p-4 bg-gradient-to-r from-primary/5 to-primary/10 rounded-lg glass-card">
        <div className="text-center space-y-2">
          <h3 className="text-lg font-semibold">CSS Features Added</h3>
          <div className="flex flex-wrap justify-center gap-2">
            <Badge variant="secondary" className="badge-enhanced">
              Glass Morphism
            </Badge>
            <Badge variant="secondary" className="badge-enhanced">
              Enhanced Shadows
            </Badge>
            <Badge variant="secondary" className="badge-enhanced">
              Pulse Effects
            </Badge>
            <Badge variant="secondary" className="badge-enhanced">
              Hover Glows
            </Badge>
            <Badge variant="secondary" className="badge-enhanced">
              Better Scrollbars
            </Badge>
            <Badge variant="secondary" className="badge-enhanced">
              Focus Rings
            </Badge>
          </div>
        </div>
      </div>
    </div>
  );
}
