"use client";

import { useState } from "react";
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { RingLoader } from "@/components/RingLoader";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Sheet, SheetContent, SheetTrigger } from "@/components/ui/sheet";
import {
  Shield,
  Calendar,
  User,
  Search,
  ArrowRight,
  Clock,
  Tag,
  Star,
  Twitter,
  Github,
  BookOpen,
  Menu,
  X,
  Home as HomeIcon,
  Info,
  Briefcase,
  Mail,
  LayoutDashboard,
} from "lucide-react";
import Link from "next/link";
import { ModeToggle } from "@/components/mode-toggle";

export default function BlogPage() {
  const [isMenuOpen, setIsMenuOpen] = useState(false);

  const blogPosts = [
    {
      id: "ai-revolutionizing-content-revenue",
      title: "How AI is Revolutionizing Content Revenue Models",
      excerpt:
        "The shift from ad-based revenue to AI licensing: How ChatGPT and Perplexity are changing the way publishers monetize content.",
      author: "Md Imad",
      date: "July 31, 2025",
      readTime: "8 min read",
      tags: ["AI", "Revenue Models", "Content Licensing"],
      featured: true,
    },
    {
      id: "blog-readers-to-ai-models",
      title: "From Blog Readers to AI Models: The New Attention Economy",
      excerpt:
        "Understanding how AI assistants are becoming the primary consumers of content and what it means for publisher revenue streams.",
      author: "Md Imad",
      date: "July 31, 2025",
      readTime: "6 min read",
      tags: ["AI", "Attention Economy", "Publishing"],
      featured: false,
    },
    {
      id: "cloudflare-http-402-payment-layer",
      title: "Cloudflare's HTTP 402: Building the Payment Layer for AI Content",
      excerpt:
        "How Cloudflare's Pay-Per-Crawl initiative is creating the infrastructure for the future of content monetization.",
      author: "Md Imad",
      date: "July 31, 2025",
      readTime: "7 min read",
      tags: ["Cloudflare", "HTTP 402", "AI Payments"],
      featured: false,
    },
    {
      id: "decline-ad-revenue-ai-licensing",
      title: "The Decline of Ad Revenue: Why AI Licensing is the Future",
      excerpt:
        "As users shift from traditional browsing to AI queries, publishers need new monetization strategies beyond display advertising.",
      author: "Md Imad",
      date: "July 31, 2025",
      readTime: "9 min read",
      tags: ["Ad Revenue", "AI Licensing", "Publishing Strategy"],
      featured: false,
    },
    {
      id: "chatgpt-vs-google-ai-search",
      title: "ChatGPT vs. Google: How AI Search is Changing Content Discovery",
      excerpt:
        "The fundamental shift in how users find and consume information, and what it means for content creators and publishers.",
      author: "Md Imad",
      date: "July 31, 2025",
      readTime: "10 min read",
      tags: ["ChatGPT", "AI Search", "Content Discovery"],
      featured: false,
    },
    {
      id: "building-sustainable-revenue-ai",
      title: "Building Sustainable Revenue in the Age of AI",
      excerpt:
        "Practical strategies for WordPress publishers to thrive as AI becomes the primary interface for information consumption.",
      author: "Md Imad",
      date: "July 31, 2025",
      readTime: "7 min read",
      tags: ["WordPress", "AI Strategy", "Revenue"],
      featured: false,
    },
    {
      id: "ethics-ai-content-licensing",
      title:
        "The Ethics of AI Content Licensing: Fair Compensation for Creators",
      excerpt:
        "Exploring the moral imperative of ensuring content creators are compensated when AI models use their work.",
      author: "Md Imad",
      date: "July 31, 2025",
      readTime: "8 min read",
      tags: ["AI Ethics", "Content Rights", "Fair Compensation"],
      featured: false,
    },
    {
      id: "perplexity-claude-ai-research",
      title: "Perplexity, Claude, and the Rise of AI Research Assistants",
      excerpt:
        "How AI research tools are changing academic and professional research, creating new opportunities for specialized content.",
      author: "Md Imad",
      date: "July 31, 2025",
      readTime: "6 min read",
      tags: ["Perplexity", "Claude", "AI Research"],
      featured: false,
    },
    {
      id: "wordpress-ai-content-economy",
      title: "Preparing Your WordPress Site for the AI Content Economy",
      excerpt:
        "Technical and strategic preparations WordPress publishers need to make to capitalize on the AI content licensing revolution.",
      author: "Md Imad",
      date: "July 31, 2025",
      readTime: "5 min read",
      tags: ["WordPress", "AI Preparation", "Technical Guide"],
      featured: false,
    },
  ];

  const categories = [
    { name: "AI & Machine Learning", count: 15 },
    { name: "Revenue Models", count: 12 },
    { name: "Content Strategy", count: 10 },
    { name: "WordPress", count: 8 },
    { name: "Cloudflare & Infrastructure", count: 6 },
    { name: "AI Ethics", count: 4 },
  ];

  return (
    <div className="min-h-screen bg-background">
      {/* Navigation */}
      <nav className="sticky top-0 z-50 bg-background/80 backdrop-blur-md border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <Link href="/" className="flex items-center space-x-2">
              <Shield className="h-8 w-8 text-primary" />
              <span className="text-xl font-bold text-foreground">
                PayPerCrawl
              </span>
            </Link>

            {/* Desktop Navigation */}
            <div className="hidden md:flex items-center space-x-8">
              <Link
                href="/"
                className="text-muted-foreground hover:text-foreground transition-colors"
              >
                Home
              </Link>
              <Link
                href="/features"
                className="text-muted-foreground hover:text-foreground transition-colors"
              >
                Features
              </Link>
              <Link
                href="/about"
                className="text-muted-foreground hover:text-foreground transition-colors"
              >
                About
              </Link>
              <Link href="/blog" className="text-primary font-medium">
                Blog
              </Link>
              <Link
                href="/careers"
                className="text-muted-foreground hover:text-foreground transition-colors"
              >
                Careers
              </Link>
              <Link
                href="/dashboard"
                className="text-muted-foreground hover:text-foreground transition-colors"
              >
                Dashboard
              </Link>
              <Link href="/waitlist">
                <Button>Join Beta</Button>
              </Link>
              <ModeToggle />
            </div>

            {/* Mobile Navigation */}
            <div className="md:hidden flex items-center space-x-2">
              <ModeToggle />
              <Sheet open={isMenuOpen} onOpenChange={setIsMenuOpen}>
                <SheetTrigger asChild>
                  <Button variant="ghost" size="icon">
                    <Menu className="h-6 w-6" />
                  </Button>
                </SheetTrigger>
                <SheetContent
                  side="right"
                  className="w-[320px] sm:w-[380px] p-0 bg-background/95 backdrop-blur-2xl border-l border-border/50 shadow-2xl"
                >
                  {/* Header */}
                  <div className="flex items-center justify-between p-6 border-b border-border/30 bg-background/80 backdrop-blur-xl">
                    <div className="flex items-center space-x-2">
                      <Shield className="h-6 w-6 text-primary drop-shadow-sm" />
                      <span className="text-lg font-bold text-foreground drop-shadow-sm">
                        PayPerCrawl
                      </span>
                    </div>
                  </div>

                  {/* Navigation */}
                  <div className="flex flex-col h-full bg-background/70 backdrop-blur-md">
                    <nav className="flex-1 p-6 bg-background/50 backdrop-blur-sm">
                      <div className="space-y-1">
                        <Link
                          href="/"
                          className="flex items-center space-x-3 px-4 py-3 rounded-xl text-foreground hover:bg-accent/80 hover:text-accent-foreground transition-all duration-200 group backdrop-blur-sm hover:shadow-md"
                          onClick={() => setIsMenuOpen(false)}
                        >
                          <HomeIcon className="h-5 w-5 text-muted-foreground group-hover:text-primary transition-colors drop-shadow-sm" />
                          <span className="font-medium drop-shadow-sm">
                            Home
                          </span>
                        </Link>
                        <Link
                          href="/features"
                          className="flex items-center space-x-3 px-4 py-3 rounded-xl text-foreground hover:bg-accent/80 hover:text-accent-foreground transition-all duration-200 group backdrop-blur-sm hover:shadow-md"
                          onClick={() => setIsMenuOpen(false)}
                        >
                          <Star className="h-5 w-5 text-muted-foreground group-hover:text-primary transition-colors drop-shadow-sm" />
                          <span className="font-medium drop-shadow-sm">
                            Features
                          </span>
                        </Link>
                        <Link
                          href="/about"
                          className="flex items-center space-x-3 px-4 py-3 rounded-xl text-foreground hover:bg-accent/80 hover:text-accent-foreground transition-all duration-200 group backdrop-blur-sm hover:shadow-md"
                          onClick={() => setIsMenuOpen(false)}
                        >
                          <Info className="h-5 w-5 text-muted-foreground group-hover:text-primary transition-colors drop-shadow-sm" />
                          <span className="font-medium drop-shadow-sm">
                            About
                          </span>
                        </Link>
                        <Link
                          href="/blog"
                          className="flex items-center space-x-3 px-4 py-3 rounded-xl text-primary bg-primary/10 border border-primary/20 transition-all duration-200 group backdrop-blur-sm shadow-lg"
                          onClick={() => setIsMenuOpen(false)}
                        >
                          <BookOpen className="h-5 w-5 text-primary transition-colors drop-shadow-sm" />
                          <span className="font-semibold drop-shadow-sm">
                            Blog
                          </span>
                        </Link>
                        <Link
                          href="/careers"
                          className="flex items-center space-x-3 px-4 py-3 rounded-xl text-foreground hover:bg-accent/80 hover:text-accent-foreground transition-all duration-200 group backdrop-blur-sm hover:shadow-md"
                          onClick={() => setIsMenuOpen(false)}
                        >
                          <Briefcase className="h-5 w-5 text-muted-foreground group-hover:text-primary transition-colors drop-shadow-sm" />
                          <span className="font-medium drop-shadow-sm">
                            Careers
                          </span>
                        </Link>
                        <Link
                          href="/contact"
                          className="flex items-center space-x-3 px-4 py-3 rounded-xl text-foreground hover:bg-accent/80 hover:text-accent-foreground transition-all duration-200 group backdrop-blur-sm hover:shadow-md"
                          onClick={() => setIsMenuOpen(false)}
                        >
                          <Mail className="h-5 w-5 text-muted-foreground group-hover:text-primary transition-colors drop-shadow-sm" />
                          <span className="font-medium drop-shadow-sm">
                            Contact
                          </span>
                        </Link>
                        <Link
                          href="/dashboard"
                          className="flex items-center space-x-3 px-4 py-3 rounded-xl text-foreground hover:bg-accent/80 hover:text-accent-foreground transition-all duration-200 group backdrop-blur-sm hover:shadow-md"
                          onClick={() => setIsMenuOpen(false)}
                        >
                          <LayoutDashboard className="h-5 w-5 text-muted-foreground group-hover:text-primary transition-colors drop-shadow-sm" />
                          <span className="font-medium drop-shadow-sm">
                            Dashboard
                          </span>
                        </Link>
                      </div>
                    </nav>

                    {/* CTA Section */}
                    <div className="p-6 border-t border-border/40 bg-accent/30 backdrop-blur-lg">
                      <div className="space-y-3">
                        <p className="text-sm font-medium text-foreground drop-shadow-sm">
                          Ready to monetize your content?
                        </p>
                        <Link
                          href="/waitlist"
                          onClick={() => setIsMenuOpen(false)}
                        >
                          <Button className="w-full bg-primary/90 hover:bg-primary text-primary-foreground font-semibold shadow-xl hover:shadow-2xl transition-all duration-200 backdrop-blur-sm border border-primary/20">
                            Join Beta Program
                            <ArrowRight className="ml-2 h-4 w-4" />
                          </Button>
                        </Link>
                      </div>
                    </div>
                  </div>
                </SheetContent>
              </Sheet>
            </div>
          </div>
        </div>
      </nav>

      {/* Hero Section */}
      <section className="relative overflow-hidden bg-primary text-primary-foreground">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 lg:py-32">
          <div className="text-center">
            <div className="relative inline-block mb-4">
              <Badge variant="secondary" className="relative z-10">
                <span className="flex items-center gap-2">
                  <span className="w-2 h-2 bg-primary-foreground rounded-full animate-pulse"></span>
                  BETA PROGRAM
                </span>
                Resources & Blog • 100% Revenue Share
              </Badge>
              <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
                <RingLoader
                  width={380}
                  height={44}
                  strokeWidth={2}
                  animationSpeed={5}
                  borderRadius={22}
                />
              </div>
            </div>
            <h1 className="text-4xl lg:text-5xl font-bold mb-6">
              Insights for WordPress
              <span className="text-primary-foreground/80">
                {" "}
                Content Creators
              </span>
            </h1>
            <p className="text-xl text-primary-foreground/90 mb-8 max-w-3xl mx-auto">
              Exclusive insights for our invite-only beta participants on AI
              monetization, WordPress optimization, and content licensing
              strategies.
            </p>
            <div className="relative max-w-md mx-auto">
              <Input
                placeholder="Search articles..."
                className="bg-primary/50 border-primary-foreground/30 text-primary-foreground placeholder:text-primary-foreground/70"
              />
              <Search className="absolute right-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-primary-foreground/70" />
            </div>
          </div>
        </div>
      </section>

      {/* Featured Post */}
      <section className="py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="bg-secondary rounded-2xl p-8 lg:p-12">
            <div className="flex items-center gap-2 mb-4">
              <Star className="h-5 w-5 text-primary" />
              <span className="text-sm font-medium text-primary">
                Featured Article
              </span>
            </div>
            <div className="grid lg:grid-cols-2 gap-8 items-center">
              <div>
                <h2 className="text-3xl lg:text-4xl font-bold text-foreground mb-4">
                  How AI is Revolutionizing Content Revenue Models
                </h2>
                <p className="text-lg text-muted-foreground mb-6">
                  The shift from ad-based revenue to AI licensing: How ChatGPT
                  and Perplexity are changing the way publishers monetize
                  content. Discover why traditional advertising is declining and
                  what the future holds for content creators.
                </p>
                <div className="flex items-center gap-4 text-sm text-muted-foreground mb-6">
                  <div className="flex items-center gap-2">
                    <User className="h-4 w-4" />
                    <span>Md Imad</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <Calendar className="h-4 w-4" />
                    <span>July 31, 2025</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <Clock className="h-4 w-4" />
                    <span>8 min read</span>
                  </div>
                </div>
                <Link href={`/blog/${blogPosts[0].id}`}>
                  <Button size="lg">
                    Read Article
                    <ArrowRight className="ml-2 h-4 w-4" />
                  </Button>
                </Link>
              </div>
              <div className="hidden lg:block">
                <div className="bg-gradient-to-br from-primary/20 to-primary/5 rounded-lg shadow-lg aspect-video flex items-center justify-center">
                  <div className="text-center">
                    <BookOpen className="h-16 w-16 text-primary mx-auto mb-4" />
                    <p className="text-sm text-muted-foreground font-medium">
                      Featured Article
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Main Content */}
      <main className="py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid lg:grid-cols-4 gap-12">
            {/* Blog Posts */}
            <div className="lg:col-span-3">
              <div className="grid md:grid-cols-2 gap-8">
                {blogPosts.slice(1).map((post) => (
                  <Card
                    key={post.id}
                    className="shadow-lg hover:shadow-xl transition-shadow dark:bg-card"
                  >
                    <CardHeader>
                      <CardTitle>{post.title}</CardTitle>
                      <CardDescription>{post.excerpt}</CardDescription>
                    </CardHeader>
                    <CardContent>
                      <div className="flex items-center justify-between text-sm text-muted-foreground">
                        <div className="flex items-center gap-2">
                          <Clock className="h-4 w-4" />
                          <span>{post.readTime}</span>
                        </div>
                        <Link
                          href={`/blog/${post.id}`}
                          className="text-primary font-medium hover:underline"
                        >
                          Read more
                        </Link>
                      </div>
                    </CardContent>
                  </Card>
                ))}
              </div>
              <div className="mt-12 text-center">
                <Button variant="outline">Load More Articles</Button>
              </div>
            </div>

            {/* Sidebar */}
            <aside className="lg:col-span-1 space-y-8">
              <Card className="dark:bg-card">
                <CardHeader>
                  <CardTitle>Categories</CardTitle>
                </CardHeader>
                <CardContent>
                  <ul className="space-y-2">
                    {categories.map((category) => (
                      <li
                        key={category.name}
                        className="flex justify-between items-center text-sm"
                      >
                        <Link
                          href="#"
                          className="text-muted-foreground hover:text-primary"
                        >
                          {category.name}
                        </Link>
                        <Badge variant="secondary">{category.count}</Badge>
                      </li>
                    ))}
                  </ul>
                </CardContent>
              </Card>
              <Card className="dark:bg-card">
                <CardHeader>
                  <CardTitle>Tags</CardTitle>
                </CardHeader>
                <CardContent className="flex flex-wrap gap-2">
                  {blogPosts
                    .flatMap((p) => p.tags)
                    .filter((v, i, a) => a.indexOf(v) === i)
                    .slice(0, 10)
                    .map((tag) => (
                      <Badge key={tag} variant="outline">
                        {tag}
                      </Badge>
                    ))}
                </CardContent>
              </Card>
              <Card className="bg-primary text-primary-foreground text-center p-6">
                <CardHeader>
                  <CardTitle>Join the Beta</CardTitle>
                </CardHeader>
                <CardContent>
                  <p className="mb-4">
                    Get exclusive access and help shape the future of content
                    monetization.
                  </p>
                  <Button variant="secondary" className="w-full">
                    Request Invite
                  </Button>
                </CardContent>
              </Card>
            </aside>
          </div>
        </div>
      </main>

      <footer className="bg-background border-t">
        <div className="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center">
            <p className="text-muted-foreground">
              &copy; 2024 PayPerCrawl. All rights reserved.
            </p>
            <div className="flex items-center space-x-4">
              <Link
                href="/admin"
                className="text-sm text-muted-foreground hover:text-foreground"
              >
                Admin
              </Link>
              <Link
                href="#"
                className="text-muted-foreground hover:text-foreground"
              >
                <Twitter className="h-5 w-5" />
              </Link>
              <Link
                href="#"
                className="text-muted-foreground hover:text-foreground"
              >
                <Github className="h-5 w-5" />
              </Link>
            </div>
          </div>
        </div>
      </footer>
    </div>
  );
}
