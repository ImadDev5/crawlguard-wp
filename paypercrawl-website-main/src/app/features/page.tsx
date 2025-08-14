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
import { Sheet, SheetContent, SheetTrigger } from "@/components/ui/sheet";
import {
  ArrowRight,
  Shield,
  Zap,
  DollarSign,
  Globe,
  Users,
  TrendingUp,
  Database,
  Code,
  Lock,
  BarChart3,
  Cloud,
  CheckCircle,
  Twitter,
  Github,
  BrainCircuit,
  Menu,
  X,
  Home as HomeIcon,
  Star,
  Info,
  BookOpen,
  Briefcase,
  Mail,
  LayoutDashboard,
} from "lucide-react";
import Link from "next/link";
import { ModeToggle } from "@/components/mode-toggle";
import { Progress } from "@/components/ui/progress";

export default function FeaturesPage() {
  const [isMenuOpen, setIsMenuOpen] = useState(false);

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
              <Link href="/features" className="text-primary font-medium">
                Features
              </Link>
              <Link
                href="/about"
                className="text-muted-foreground hover:text-foreground transition-colors"
              >
                About
              </Link>
              <Link
                href="/blog"
                className="text-muted-foreground hover:text-foreground transition-colors"
              >
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
                          className="flex items-center space-x-3 px-4 py-3 rounded-xl text-primary bg-primary/10 border border-primary/20 transition-all duration-200 group backdrop-blur-sm shadow-lg"
                          onClick={() => setIsMenuOpen(false)}
                        >
                          <Star className="h-5 w-5 text-primary transition-colors drop-shadow-sm" />
                          <span className="font-semibold drop-shadow-sm">
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
                          className="flex items-center space-x-3 px-4 py-3 rounded-xl text-foreground hover:bg-accent/80 hover:text-accent-foreground transition-all duration-200 group backdrop-blur-sm hover:shadow-md"
                          onClick={() => setIsMenuOpen(false)}
                        >
                          <BookOpen className="h-5 w-5 text-muted-foreground group-hover:text-primary transition-colors drop-shadow-sm" />
                          <span className="font-medium drop-shadow-sm">
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
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-24 xl:py-32">
          <div className="text-center">
            <div className="relative inline-block mb-4">
              <Badge variant="secondary" className="relative z-10">
                <span className="flex items-center gap-2">
                  <span className="w-2 h-2 bg-primary-foreground rounded-full animate-pulse"></span>
                  BETA PROGRAM
                </span>
                <span className="hidden sm:inline">
                  {" "}
                  Technical Excellence • 100% Revenue Share
                </span>
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
            <h1 className="text-2xl sm:text-3xl lg:text-4xl xl:text-5xl font-bold mb-4 sm:mb-6">
              Built for Performance,
              <span className="text-primary-foreground/80">
                {" "}
                Designed for Publishers
              </span>
            </h1>
            <p className="text-base sm:text-lg lg:text-xl text-primary-foreground/90 mb-6 sm:mb-8 max-w-3xl mx-auto px-4">
              Discover the powerful features that make PayPerCrawl the leading
              AI bot monetization platform for WordPress publishers
            </p>
          </div>
        </div>
      </section>

      {/* Core Features */}
      <section className="py-12 sm:py-16 lg:py-24">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-8 sm:mb-12 lg:mb-16">
            <h2 className="text-2xl sm:text-3xl lg:text-4xl font-bold text-foreground mb-4">
              Core Features
            </h2>
            <p className="text-base sm:text-lg lg:text-xl text-muted-foreground max-w-3xl mx-auto">
              Everything you need to monetize AI bot traffic on your WordPress
              site
            </p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
            <Card className="shadow-lg hover:shadow-xl transition-shadow dark:bg-card">
              <CardHeader>
                <Zap className="h-12 w-12 text-primary mb-4" />
                <CardTitle className="flex items-center gap-2">
                  Real-time Detection
                  <Badge variant="secondary">95% Accuracy</Badge>
                </CardTitle>
                <CardDescription>
                  Advanced AI bot identification using machine learning
                  algorithms running at the edge
                </CardDescription>
              </CardHeader>
              <CardContent>
                <ul className="space-y-2 text-sm text-muted-foreground">
                  <li className="flex items-center gap-2">
                    <CheckCircle className="h-4 w-4 text-primary" />
                    Instant bot identification
                  </li>
                  <li className="flex items-center gap-2">
                    <CheckCircle className="h-4 w-4 text-primary" />
                    Machine learning powered
                  </li>
                  <li className="flex items-center gap-2">
                    <CheckCircle className="h-4 w-4 text-primary" />
                    Continuous model updates
                  </li>
                </ul>
              </CardContent>
            </Card>

            <Card className="shadow-lg hover:shadow-xl transition-shadow dark:bg-card">
              <CardHeader>
                <DollarSign className="h-12 w-12 text-primary mb-4" />
                <CardTitle className="flex items-center gap-2">
                  Micro-monetization
                  <Badge variant="secondary">HTTP 402</Badge>
                </CardTitle>
                <CardDescription>
                  Revolutionary revenue model using industry-standard payment
                  protocols
                </CardDescription>
              </CardHeader>
              <CardContent>
                <ul className="space-y-2 text-sm text-muted-foreground">
                  <li className="flex items-center gap-2">
                    <CheckCircle className="h-4 w-4 text-primary" />
                    Per-request billing
                  </li>
                  <li className="flex items-center gap-2">
                    <CheckCircle className="h-4 w-4 text-primary" />
                    100% revenue share
                  </li>
                  <li className="flex items-center gap-2">
                    <CheckCircle className="h-4 w-4 text-primary" />
                    Instant payouts
                  </li>
                </ul>
              </CardContent>
            </Card>

            <Card className="shadow-lg hover:shadow-xl transition-shadow dark:bg-card">
              <CardHeader>
                <Globe className="h-12 w-12 text-primary mb-4" />
                <CardTitle className="flex items-center gap-2">
                  WordPress Integration
                  <Badge variant="secondary">5-Min Setup</Badge>
                </CardTitle>
                <CardDescription>
                  Seamless compatibility with the entire WordPress ecosystem
                </CardDescription>
              </CardHeader>
              <CardContent>
                <ul className="space-y-2 text-sm text-muted-foreground">
                  <li className="flex items-center gap-2">
                    <CheckCircle className="h-4 w-4 text-primary" />
                    Elementor compatible
                  </li>
                  <li className="flex items-center gap-2">
                    <CheckCircle className="h-4 w-4 text-primary" />
                    WooCommerce support
                  </li>
                  <li className="flex items-center gap-2">
                    <CheckCircle className="h-4 w-4 text-primary" />
                    Caching plugin friendly
                  </li>
                </ul>
              </CardContent>
            </Card>

            <Card className="shadow-lg hover:shadow-xl transition-shadow dark:bg-card">
              <CardHeader>
                <Shield className="h-12 w-12 text-primary mb-4" />
                <CardTitle className="flex items-center gap-2">
                  Edge Computing
                  <Badge variant="secondary">Cloudflare</Badge>
                </CardTitle>
                <CardDescription>
                  Global performance optimization using Cloudflare Workers
                  infrastructure
                </CardDescription>
              </CardHeader>
              <CardContent>
                <ul className="space-y-2 text-sm text-muted-foreground">
                  <li className="flex items-center gap-2">
                    <CheckCircle className="h-4 w-4 text-primary" />
                    99% cost advantage
                  </li>
                  <li className="flex items-center gap-2">
                    <CheckCircle className="h-4 w-4 text-primary" />
                    Global CDN network
                  </li>
                  <li className="flex items-center gap-2">
                    <CheckCircle className="h-4 w-4 text-primary" />
                    Sub-100ms responses
                  </li>
                </ul>
              </CardContent>
            </Card>

            <Card className="shadow-lg hover:shadow-xl transition-shadow dark:bg-card">
              <CardHeader>
                <TrendingUp className="h-12 w-12 text-primary mb-4" />
                <CardTitle className="flex items-center gap-2">
                  Scalable Architecture
                  <Badge variant="secondary">10M+ Requests</Badge>
                </CardTitle>
                <CardDescription>
                  Enterprise-ready infrastructure built for massive scale
                </CardDescription>
              </CardHeader>
              <CardContent>
                <ul className="space-y-2 text-sm text-muted-foreground">
                  <li className="flex items-center gap-2">
                    <CheckCircle className="h-4 w-4 text-primary" />
                    Auto-scaling workers
                  </li>
                  <li className="flex items-center gap-2">
                    <CheckCircle className="h-4 w-4 text-primary" />
                    99.9% uptime SLA
                  </li>
                  <li className="flex items-center gap-2">
                    <CheckCircle className="h-4 w-4 text-primary" />
                    Real-time analytics
                  </li>
                </ul>
              </CardContent>
            </Card>

            <Card className="shadow-lg hover:shadow-xl transition-shadow dark:bg-card">
              <CardHeader>
                <Users className="h-12 w-12 text-primary mb-4" />
                <CardTitle className="flex items-center gap-2">
                  AI Marketplace
                  <Badge variant="secondary">Coming Soon</Badge>
                </CardTitle>
                <CardDescription>
                  Connect with AI companies looking for licensed training data
                </CardDescription>
              </CardHeader>
              <CardContent>
                <ul className="space-y-2 text-sm text-muted-foreground">
                  <li className="flex items-center gap-2">
                    <CheckCircle className="h-4 w-4 text-primary" />
                    Premium AI partnerships
                  </li>
                  <li className="flex items-center gap-2">
                    <CheckCircle className="h-4 w-4 text-primary" />
                    Data licensing deals
                  </li>
                  <li className="flex items-center gap-2">
                    <CheckCircle className="h-4 w-4 text-primary" />
                    Revenue optimization
                  </li>
                </ul>
              </CardContent>
            </Card>
          </div>
        </div>
      </section>

      {/* Technical Specifications */}
      <section className="py-24 bg-secondary">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl lg:text-4xl font-bold text-foreground mb-4">
              Technical Specifications
            </h2>
            <p className="text-xl text-muted-foreground max-w-3xl mx-auto">
              PayPerCrawl is built on a modern, scalable, and secure technology
              stack
            </p>
          </div>

          <div className="bg-card rounded-lg shadow-lg p-8">
            <div className="grid md:grid-cols-2 gap-8">
              <div>
                <h3 className="text-2xl font-bold text-foreground mb-4">
                  Key Technologies
                </h3>
                <ul className="space-y-4">
                  <li className="flex items-start">
                    <div className="p-2 bg-primary/10 rounded-full mr-4">
                      <Cloud className="h-6 w-6 text-primary" />
                    </div>
                    <div>
                      <h4 className="font-semibold text-foreground">
                        Cloudflare Workers
                      </h4>
                      <p className="text-muted-foreground">
                        Serverless execution environment for high-performance,
                        low-latency bot detection.
                      </p>
                    </div>
                  </li>
                  <li className="flex items-start">
                    <div className="p-2 bg-primary/10 rounded-full mr-4">
                      <BrainCircuit className="h-6 w-6 text-primary" />
                    </div>
                    <div>
                      <h4 className="font-semibold text-foreground">
                        Machine Learning
                      </h4>
                      <p className="text-muted-foreground">
                        Proprietary models for bot detection, continuously
                        trained on new data.
                      </p>
                    </div>
                  </li>
                  <li className="flex items-start">
                    <div className="p-2 bg-primary/10 rounded-full mr-4">
                      <Lock className="h-6 w-6 text-primary" />
                    </div>
                    <div>
                      <h4 className="font-semibold text-foreground">
                        HTTP 402 Protocol
                      </h4>
                      <p className="text-muted-foreground">
                        Native browser payment requests for a seamless user
                        experience.
                      </p>
                    </div>
                  </li>
                </ul>
              </div>
              <div>
                <h3 className="text-2xl font-bold text-foreground mb-4">
                  Performance Metrics
                </h3>
                <div className="space-y-4">
                  <div>
                    <div className="flex justify-between mb-1">
                      <span className="text-sm font-medium text-muted-foreground">
                        Bot Detection Latency
                      </span>
                      <span className="text-sm font-medium text-foreground">
                        &lt;100ms
                      </span>
                    </div>
                    <Progress value={95} className="w-full" />
                  </div>
                  <div>
                    <div className="flex justify-between mb-1">
                      <span className="text-sm font-medium text-muted-foreground">
                        Uptime SLA
                      </span>
                      <span className="text-sm font-medium text-foreground">
                        99.9%
                      </span>
                    </div>
                    <Progress value={99.9} className="w-full" />
                  </div>
                  <div>
                    <div className="flex justify-between mb-1">
                      <span className="text-sm font-medium text-muted-foreground">
                        Accuracy
                      </span>
                      <span className="text-sm font-medium text-foreground">
                        &gt;95%
                      </span>
                    </div>
                    <Progress value={95} className="w-full" />
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className="py-24">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h2 className="text-3xl lg:text-4xl font-bold text-foreground mb-4">
            Ready to Stop Unpaid AI Traffic?
          </h2>
          <p className="text-xl text-muted-foreground mb-8">
            Join our waitlist to get early access to PayPerCrawl and start
            monetizing your content.
          </p>
          <Button size="lg" className="text-lg">
            Request Early Access
            <ArrowRight className="ml-2 h-5 w-5" />
          </Button>
        </div>
      </section>

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
