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
  Shield,
  Users,
  Target,
  TrendingUp,
  Award,
  Globe,
  ArrowRight,
  Star,
  CheckCircle,
  Menu,
  X,
  Home as HomeIcon,
  Info,
  BookOpen,
  Briefcase,
  Mail,
  LayoutDashboard,
} from "lucide-react";
import Link from "next/link";
import { ModeToggle } from "@/components/mode-toggle";

export default function AboutPage() {
  const [isMenuOpen, setIsMenuOpen] = useState(false);

  const team = [
    {
      name: "Executive Leadership",
      role: "C-Suite",
      members: [
        {
          title: "Chief Executive Officer",
          description:
            "Strategic visionary driving toward billion-dollar AI content licensing marketplace",
        },
        {
          title: "Chief Technology Officer",
          description:
            "Technical architect focused on scalable infrastructure using Cloudflare's cost arbitrage advantage",
        },
        {
          title: "VP of Engineering",
          description:
            "Engineering execution leader translating CTO vision into working systems",
        },
        {
          title: "VP of Product",
          description:
            "Product strategist focused on WordPress-first market domination",
        },
        {
          title: "VP of Sales & Marketing",
          description:
            "Revenue growth driver targeting WordPress publishers and AI companies",
        },
        {
          title: "CFO / VP of Finance",
          description:
            "Financial strategist ensuring capital efficiency while scaling toward marketplace model",
        },
      ],
    },
    {
      name: "Management Tier",
      role: "Department Heads",
      members: [
        {
          title: "Engineering Manager",
          description:
            "People-focused leader ensuring engineering teams deliver quality code on schedule",
        },
        {
          title: "Product Manager",
          description:
            "User advocate ensuring features solve real publisher and AI company problems",
        },
        {
          title: "Sales Manager",
          description:
            "Revenue generator focused on converting WordPress publishers to paid plans",
        },
        {
          title: "Marketing Manager",
          description:
            "Growth hacker focused on WordPress community engagement and AI company outreach",
        },
        {
          title: "Finance Manager",
          description:
            "Operational finance leader ensuring accurate reporting and efficient cash management",
        },
        {
          title: "Head of HR",
          description:
            "Culture builder and talent strategist supporting rapid team scaling",
        },
      ],
    },
    {
      name: "Technical Team",
      role: "Individual Contributors",
      members: [
        {
          title: "Senior Software Engineer",
          description:
            "Technical expert leading complex features and mentoring junior developers",
        },
        {
          title: "Mid-Level Software Engineer",
          description:
            "Productive contributor handling substantial features with growing independence",
        },
        {
          title: "Junior Software Engineer",
          description:
            "Learning-focused contributor handling smaller tasks with guidance and support",
        },
        {
          title: "Sales Representative",
          description:
            "Revenue generator focused on converting WordPress users to paid subscriptions",
        },
      ],
    },
  ];

  const phases = [
    {
      phase: "Phase 1",
      title: "Operation WordPress Domination",
      timeline: "Months 1-6",
      goal: "Achieve #1 plugin status in WordPress ecosystem",
      metric: "Weekly Active Publishers",
      description:
        "Undivided focus on becoming the undisputed AI monetization solution within WordPress",
    },
    {
      phase: "Phase 2",
      title: "Igniting the Marketplace",
      timeline: "Months 7-12",
      goal: "Launch self-service AI Company Portal",
      metric: "Monthly Recurring Revenue from AI Companies",
      description:
        "Systematically building the demand side with first paying AI companies",
    },
    {
      phase: "Phase 3",
      title: "The Great Expansion",
      timeline: "Months 13-24",
      goal: "Expand to non-WordPress sites and enterprise clients",
      metric: "Annual Contract Value from Licensing Deals",
      description:
        "Addressing wider web with JavaScript snippet and premium managed services",
    },
  ];

  return (
    <div className="min-h-screen">
      {/* Navigation */}
      <nav className="sticky top-0 z-50 bg-background/80 backdrop-blur-md border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between items-center h-16">
            <Link href="/" className="flex items-center space-x-2">
              <Shield className="h-8 w-8 text-primary" />
              <span className="text-xl font-bold">PayPerCrawl</span>
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
              <Link href="/about" className="text-primary font-medium">
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
                          className="flex items-center space-x-3 px-4 py-3 rounded-xl text-primary bg-primary/10 border border-primary/20 transition-all duration-200 group backdrop-blur-sm shadow-lg"
                          onClick={() => setIsMenuOpen(false)}
                        >
                          <Info className="h-5 w-5 text-primary transition-colors drop-shadow-sm" />
                          <span className="font-semibold drop-shadow-sm">
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
              <Badge
                variant="secondary"
                className="bg-primary-foreground/20 text-primary-foreground border-primary-foreground/30 relative z-10"
              >
                <span className="flex items-center gap-2">
                  <span className="w-2 h-2 bg-primary rounded-full animate-pulse"></span>
                  BETA PROGRAM
                </span>
                <span className="hidden sm:inline">
                  {" "}
                  About PayPerCrawl • 100% Revenue Share
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
            <h1 className="text-4xl lg:text-5xl font-bold mb-6">
              Building the Future of
              <span className="text-primary-foreground/80">
                {" "}
                Content Monetization
              </span>
            </h1>
            <p className="text-xl text-primary-foreground/90 mb-8 max-w-3xl mx-auto">
              We're creating an exclusive invite-only marketplace connecting
              selected WordPress publishers with AI companies, offering 100%
              revenue share during our beta phase.
            </p>
            <div className="flex items-center justify-center gap-8 text-primary-foreground/80">
              <div className="flex items-center gap-2">
                <Target className="h-5 w-5" />
                <span>$1.8B WordPress Opportunity</span>
              </div>
              <div className="flex items-center gap-2">
                <Users className="h-5 w-5" />
                <span>75M+ WordPress Sites</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Mission Section */}
      <section className="py-24">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid lg:grid-cols-2 gap-12 items-center">
            <div>
              <h2 className="text-3xl lg:text-4xl font-bold text-foreground mb-6">
                Our Mission
              </h2>
              <p className="text-lg text-muted-foreground mb-4">
                PayPerCrawl's true purpose is not to sell a simple bot-blocking
                tool. We are establishing a two-sided marketplace that functions
                as a collective bargaining union for the internet's long-tail
                publishers.
              </p>
              <p className="text-lg text-muted-foreground mb-6">
                A single blogger holds no leverage against data-hungry AI
                behemoths. However, a network of hundreds of thousands of
                publishers, represented by a single entity, possesses immense
                negotiating power. This transforms the business from a
                transactional service into a strategic data brokerage and
                licensing authority.
              </p>
              <div className="flex items-center gap-2 text-primary">
                <Star className="h-5 w-5" />
                <span className="font-medium">
                  Building the essential fabric connecting content creators with
                  AI
                </span>
              </div>
            </div>
            <div className="grid grid-cols-2 gap-6">
              <Card className="shadow-lg dark:bg-card">
                <CardHeader>
                  <Award className="h-12 w-12 text-primary mb-4" />
                  <CardTitle className="text-lg">
                    First-Mover Advantage
                  </CardTitle>
                </CardHeader>
                <CardContent>
                  <p className="text-muted-foreground">
                    Pioneer in AI bot monetization with validated market demand
                  </p>
                </CardContent>
              </Card>
              <Card className="shadow-lg dark:bg-card">
                <CardHeader>
                  <TrendingUp className="h-12 w-12 text-primary mb-4" />
                  <CardTitle className="text-lg">Scalable Model</CardTitle>
                </CardHeader>
                <CardContent>
                  <p className="text-muted-foreground">
                    Built to handle millions of sites and AI company
                    partnerships
                  </p>
                </CardContent>
              </Card>
              <Card className="shadow-lg dark:bg-card">
                <CardHeader>
                  <Globe className="h-12 w-12 text-primary mb-4" />
                  <CardTitle className="text-lg">Global Reach</CardTitle>
                </CardHeader>
                <CardContent>
                  <p className="text-muted-foreground">
                    WordPress-first strategy with 43.5% market share leverage
                  </p>
                </CardContent>
              </Card>
              <Card className="shadow-lg dark:bg-card">
                <CardHeader>
                  <Shield className="h-12 w-12 text-primary mb-4" />
                  <CardTitle className="text-lg">99% Cost Advantage</CardTitle>
                </CardHeader>
                <CardContent>
                  <p className="text-muted-foreground">
                    Infrastructure arbitrage through Cloudflare Workers
                  </p>
                </CardContent>
              </Card>
            </div>
          </div>
        </div>
      </section>

      {/* Our Approach */}
      <section className="bg-muted/40 py-24">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center mb-16">
            <h2 className="text-3xl lg:text-4xl font-bold mb-4">
              Our Approach
            </h2>
            <p className="text-xl text-muted-foreground">
              Building sustainable solutions for the content economy
            </p>
          </div>

          <div className="grid md:grid-cols-3 gap-8">
            <Card className="border-0 shadow-lg">
              <CardHeader className="text-center">
                <Shield className="h-12 w-12 text-primary mb-4 mx-auto" />
                <CardTitle className="text-xl">Reliable Technology</CardTitle>
                <CardDescription>
                  Built on modern infrastructure for performance and scalability
                </CardDescription>
              </CardHeader>
              <CardContent>
                <p className="text-sm text-muted-foreground text-center">
                  Leveraging cutting-edge technology to deliver dependable
                  solutions that grow with your needs.
                </p>
              </CardContent>
            </Card>

            <Card className="border-0 shadow-lg">
              <CardHeader className="text-center">
                <Users className="h-12 w-12 text-primary mb-4 mx-auto" />
                <CardTitle className="text-xl">Publisher First</CardTitle>
                <CardDescription>
                  Designed specifically for content creators and publishers
                </CardDescription>
              </CardHeader>
              <CardContent>
                <p className="text-sm text-muted-foreground text-center">
                  Every decision is made with the success and needs of WordPress
                  publishers at the forefront.
                </p>
              </CardContent>
            </Card>

            <Card className="border-0 shadow-lg">
              <CardHeader className="text-center">
                <TrendingUp className="h-12 w-12 text-primary mb-4 mx-auto" />
                <CardTitle className="text-xl">Future Ready</CardTitle>
                <CardDescription>
                  Preparing for the evolving landscape of content consumption
                </CardDescription>
              </CardHeader>
              <CardContent>
                <p className="text-sm text-muted-foreground text-center">
                  Adapting to new technologies and trends to ensure long-term
                  value for our partners.
                </p>
              </CardContent>
            </Card>
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="py-24">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <h2 className="text-3xl lg:text-4xl font-bold mb-4">
            Join Our Mission
          </h2>
          <p className="text-xl text-muted-foreground mb-8">
            We're hiring multiple positioning experts and professionals to build
            the future of content monetization
          </p>
          <div className="flex flex-col sm:flex-row gap-4 justify-center">
            <Link href="/careers">
              <Button size="lg" className="text-lg px-8 py-3">
                View Open Positions
                <ArrowRight className="ml-2 h-5 w-5" />
              </Button>
            </Link>
            <Button variant="outline" size="lg" className="text-lg px-8 py-3">
              Partner With Us
            </Button>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-foreground text-background py-12">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex flex-col md:flex-row justify-between items-center">
            <div className="flex items-center space-x-2 mb-4 md:mb-0">
              <Shield className="h-8 w-8 text-primary" />
              <span className="text-xl font-bold">PayPerCrawl</span>
            </div>
            <div className="text-muted-foreground text-center md:text-right">
              <p>&copy; 2025 PayPerCrawl. All rights reserved.</p>
              <p className="text-sm mt-1">The Cloudflare for WordPress</p>
            </div>
          </div>
        </div>
      </footer>
    </div>
  );
}
