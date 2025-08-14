import type { Metadata } from "next";
import { Geist, Geist_Mono } from "next/font/google";
import "./globals.css";
import { Toaster } from "@/components/ui/toaster";
import { ThemeProvider } from "@/components/theme-provider";
import { ThemeKeyboardShortcuts } from "@/components/theme-keyboard-shortcuts";
import { AuthProvider } from "@/contexts/AuthContext";

const geistSans = Geist({
  variable: "--font-geist-sans",
  subsets: ["latin"],
});

const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: "PayPerCrawl - AI Content Monetization Platform",
  description:
    "Turn AI bot traffic into revenue. PayPerCrawl detects AI bots crawling your content and converts them into paying customers. Built for WordPress with enterprise-grade security.",
  keywords: [
    "AI monetization",
    "WordPress",
    "content protection",
    "bot detection",
    "revenue generation",
    "AI training data",
  ],
  authors: [{ name: "PayPerCrawl Team" }],
  icons: {
    icon: [
      {
        url: "/shield-icon.svg",
        type: "image/svg+xml",
      },
    ],
    shortcut: "/shield-icon.svg",
    apple: "/shield-icon.svg",
  },
  openGraph: {
    title: "PayPerCrawl - AI Content Monetization Platform",
    description:
      "Turn AI bot traffic into revenue with advanced bot detection and monetization",
    url: "https://paypercrawl.tech",
    siteName: "PayPerCrawl",
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "PayPerCrawl - AI Content Monetization Platform",
    description:
      "Turn AI bot traffic into revenue with advanced bot detection and monetization",
  },
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en" suppressHydrationWarning>
      <body
        className={`${geistSans.variable} ${geistMono.variable} antialiased bg-background text-foreground`}
      >
        <ThemeProvider
          attribute="class"
          defaultTheme="system"
          enableSystem
          disableTransitionOnChange={false}
        >
          <AuthProvider>
            <ThemeKeyboardShortcuts />
            {children}
          </AuthProvider>
        </ThemeProvider>
        <Toaster />
      </body>
    </html>
  );
}
