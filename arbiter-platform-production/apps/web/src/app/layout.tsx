import type { Metadata } from 'next';
import { Inter } from 'next/font/google';
import './globals.css';
import { Providers } from './providers';

const inter = Inter({ subsets: ['latin'] });

export const metadata: Metadata = {
  title: 'Arbiter Platform - Content Creator & AI Company Marketplace',
  description: 'The premier marketplace connecting content creators with AI companies for training data licensing.',
  keywords: ['AI', 'marketplace', 'content creators', 'machine learning', 'training data', 'licensing'],
  authors: [{ name: 'Arbiter Platform Team' }],
  openGraph: {
    title: 'Arbiter Platform - Content Creator & AI Company Marketplace',
    description: 'The premier marketplace connecting content creators with AI companies for training data licensing.',
    url: process.env.NEXT_PUBLIC_APP_URL,
    siteName: 'Arbiter Platform',
    images: [
      {
        url: '/og-image.jpg',
        width: 1200,
        height: 630,
        alt: 'Arbiter Platform',
      },
    ],
    locale: 'en_US',
    type: 'website',
  },
  twitter: {
    card: 'summary_large_image',
    title: 'Arbiter Platform - Content Creator & AI Company Marketplace',
    description: 'The premier marketplace connecting content creators with AI companies for training data licensing.',
    images: ['/og-image.jpg'],
  },
};

export default function RootLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <html lang="en" className="dark">
      <body className={`${inter.className} bg-background text-foreground`}>
        <Providers>
          {children}
        </Providers>
      </body>
    </html>
  );
}
