import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // Production optimizations for Hostinger
  output: 'standalone',
  trailingSlash: true,

  // TypeScript configuration
  typescript: {
    ignoreBuildErrors: true,
  },

  // ESLint configuration
  eslint: {
    ignoreDuringBuilds: true,
  },

  // React configuration
  reactStrictMode: false,

  // Image optimization (compatible with Hostinger)
  images: {
    unoptimized: false,
    domains: ['paypercrawl.tech', 'localhost'],
    formats: ['image/webp', 'image/avif'],
  },

  // Webpack configuration
  webpack: (config, { dev, isServer }) => {
    if (dev) {
      config.watchOptions = {
        ignored: ['**/*'],
      };
    }

    // Hostinger compatibility fixes
    if (!isServer) {
      config.resolve.fallback = {
        ...config.resolve.fallback,
        fs: false,
        net: false,
        tls: false,
      };
    }

    return config;
  },

  // Security headers
  async headers() {
    return [
      {
        source: '/(.*)',
        headers: [
          {
            key: 'X-Frame-Options',
            value: 'DENY',
          },
          {
            key: 'X-Content-Type-Options',
            value: 'nosniff',
          },
          {
            key: 'Referrer-Policy',
            value: 'origin-when-cross-origin',
          },
          {
            key: 'X-XSS-Protection',
            value: '1; mode=block',
          },
        ],
      },
    ];
  },

  // Redirects for SEO
  async redirects() {
    return [
      {
        source: '/home',
        destination: '/',
        permanent: true,
      },
    ];
  },
};

export default nextConfig;
