"use client";

import React from "react";

interface RingLoaderProps {
  width?: number;
  height?: number;
  strokeWidth?: number;
  colors?: string[];
  animationSpeed?: number;
  className?: string;
  style?: React.CSSProperties;
  borderRadius?: number;
}

export const RingLoader: React.FC<RingLoaderProps> = ({
  width = 200,
  height = 60,
  strokeWidth = 2,
  colors = [],
  animationSpeed = 4,
  className = "",
  style = {},
  borderRadius = 30,
}) => {
  // Calculate rounded rectangle properties
  const effectiveWidth = width - strokeWidth;
  const effectiveHeight = height - strokeWidth;
  const x = strokeWidth / 2;
  const y = strokeWidth / 2;
  const rx = Math.min(borderRadius, effectiveHeight / 2);

  // Calculate perimeter of rounded rectangle
  const perimeter =
    2 * (effectiveWidth + effectiveHeight - 4 * rx) + 2 * Math.PI * rx;

  // Create unique IDs for gradients to avoid conflicts
  const gradientId = `ring-gradient-${Math.random().toString(36).substr(2, 9)}`;

  // Default theme-aware colors if none provided
  const defaultColors = [
    "hsl(var(--primary))",
    "hsl(var(--ring))",
    "hsl(var(--primary) / 0.5)",
  ];

  const gradientColors = colors.length > 0 ? colors : defaultColors;

  return (
    <div
      className={`inline-flex items-center justify-center ${className}`}
      style={
        {
          width,
          height,
          "--animation-duration": `${animationSpeed}s`,
          "--perimeter": perimeter,
          ...style,
        } as React.CSSProperties
      }
    >
      <svg
        width={width}
        height={height}
        viewBox={`0 0 ${width} ${height}`}
        className="ring-loader-svg"
        style={{
          transform: "rotate(0deg)", // Start from left side
        }}
      >
        {/* Define gradient */}
        <defs>
          <linearGradient id={gradientId} x1="0%" y1="0%" x2="100%" y2="0%">
            {gradientColors.map((color, index) => (
              <stop
                key={index}
                offset={`${(index / (gradientColors.length - 1)) * 100}%`}
                stopColor={color}
                stopOpacity={1}
              />
            ))}
          </linearGradient>
        </defs>

        {/* Background rounded rectangle (subtle) */}
        <rect
          x={x}
          y={y}
          width={effectiveWidth}
          height={effectiveHeight}
          rx={rx}
          ry={rx}
          fill="none"
          stroke="hsl(var(--border))"
          strokeWidth={strokeWidth}
          opacity="0.2"
        />

        {/* Animated highlight arc */}
        <rect
          x={x}
          y={y}
          width={effectiveWidth}
          height={effectiveHeight}
          rx={rx}
          ry={rx}
          fill="none"
          stroke={`url(#${gradientId})`}
          strokeWidth={strokeWidth}
          strokeLinecap="round"
          strokeDasharray={`${perimeter * 0.25} ${perimeter * 0.75}`}
          className="ring-loader-path"
          style={{
            strokeDashoffset: perimeter,
          }}
        />
      </svg>
    </div>
  );
};

export default RingLoader;
