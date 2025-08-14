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
import { Button } from "@/components/ui/button";
import { Sheet, SheetContent, SheetTrigger } from "@/components/ui/sheet";
import {
  Mail,
  Users,
  Target,
  TrendingUp,
  Award,
  Globe,
  ArrowRight,
  Star,
  Building,
  Code,
  Megaphone,
  DollarSign,
  Shield,
  Menu,
  X,
  Home as HomeIcon,
  Info,
  BookOpen,
  Briefcase,
  LayoutDashboard,
} from "lucide-react";
import Link from "next/link";
import { ModeToggle } from "@/components/mode-toggle";

export default function CareersPage() {
  const [isMenuOpen, setIsMenuOpen] = useState(false);

  const positions = [
    {
      category: "Engineering",
      icon: Code,
      color: "blue",
      roles: [
        {
          title: "Senior WordPress Developer",
          type: "Full-time",
          location: "Remote",
          description:
            "Lead WordPress plugin development with deep expertise in the WordPress ecosystem.",
        },
        {
          title: "Cloudflare Workers Specialist",
          type: "Full-time",
          location: "Remote",
          description:
            "Build and optimize edge computing infrastructure using Cloudflare Workers platform.",
        },
        {
          title: "Full Stack Developer",
          type: "Full-time",
          location: "Remote",
          description:
            "Work across the stack from frontend interfaces to backend API development.",
        },
        {
          title: "DevOps Engineer",
          type: "Full-time",
          location: "Remote",
          description:
            "Manage deployment pipelines and ensure system reliability at scale.",
        },
      ],
    },
    {
      category: "Business & Sales",
      icon: DollarSign,
      color: "green",
      roles: [
        {
          title: "Business Development Manager",
          type: "Full-time",
          location: "Remote",
          description:
            "Drive partnerships with AI companies and enterprise clients.",
        },
        {
          title: "Sales Representative",
          type: "Full-time",
          location: "Remote",
          description:
            "Convert WordPress publishers to paid plans and drive revenue growth.",
        },
        {
          title: "Partnership Manager",
          type: "Full-time",
          location: "Remote",
          description:
            "Build and maintain relationships with WordPress ecosystem partners.",
        },
      ],
    },
    {
      category: "Marketing & Growth",
      icon: Megaphone,
      color: "purple",
      roles: [
        {
          title: "Content Marketing Manager",
          type: "Full-time",
          location: "Remote",
          description:
            "Create compelling content targeting WordPress users and AI companies.",
        },
        {
          title: "Growth Marketer",
          type: "Full-time",
          location: "Remote",
          description:
            "Drive user acquisition through WordPress community engagement and SEO.",
        },
        {
          title: "Community Manager",
          type: "Full-time",
          location: "Remote",
          description:
            "Build and nurture our WordPress developer and publisher community.",
        },
      ],
    },
    {
      category: "Operations",
      icon: Building,
      color: "orange",
      roles: [
        {
          title: "Product Manager",
          type: "Full-time",
          location: "Remote",
          description:
            "Shape product strategy and prioritize features based on user feedback.",
        },
        {
          title: "Customer Success Manager",
          type: "Full-time",
          location: "Remote",
          description:
            "Ensure customer satisfaction and drive platform adoption.",
        },
      ],
    },
  ];

  return (
    <div className="min-h-screen bg-background text-foreground">
      <header className="sticky top-0 z-50 w-full border-b border-border/40 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
        <div className="container flex h-14 items-center">
          <Link href="/" className="mr-6 flex items-center space-x-2">
            <Shield className="h-6 w-6" />
            <span className="font-bold">PayperCrawl</span>
          </Link>

          {/* Desktop Navigation */}
          <nav className="hidden md:flex items-center space-x-6 text-sm font-medium">
            <Link
              href="/"
              className="text-muted-foreground transition-colors hover:text-foreground"
            >
              Home
            </Link>
            <Link
              href="/features"
              className="text-muted-foreground transition-colors hover:text-foreground"
            >
              Features
            </Link>
            <Link
              href="/about"
              className="text-muted-foreground transition-colors hover:text-foreground"
            >
              About
            </Link>
            <Link
              href="/blog"
              className="text-muted-foreground transition-colors hover:text-foreground"
            >
              Blog
            </Link>
            <Link href="/careers" className="text-foreground">
              Careers
            </Link>
            <Link
              href="/dashboard"
              className="text-muted-foreground transition-colors hover:text-foreground"
            >
              Dashboard
            </Link>
          </nav>

          <div className="flex flex-1 items-center justify-end space-x-4">
            <div className="hidden md:flex items-center space-x-4">
              <ModeToggle />
              <Button asChild>
                <Link href="/waitlist">Join Waitlist</Link>
              </Button>
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
                          className="flex items-center space-x-3 px-4 py-3 rounded-xl text-primary bg-primary/10 border border-primary/20 transition-all duration-200 group backdrop-blur-sm shadow-lg"
                          onClick={() => setIsMenuOpen(false)}
                        >
                          <Briefcase className="h-5 w-5 text-primary transition-colors drop-shadow-sm" />
                          <span className="font-semibold drop-shadow-sm">
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
                          Join our growing team
                        </p>
                        <Link
                          href="/waitlist"
                          onClick={() => setIsMenuOpen(false)}
                        >
                          <Button className="w-full bg-primary/90 hover:bg-primary text-primary-foreground font-semibold shadow-xl hover:shadow-2xl transition-all duration-200 backdrop-blur-sm border border-primary/20">
                            Join Waitlist
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
      </header>

      <main className="container mx-auto px-4 py-12 md:px-6">
        <div className="text-center mb-12">
          <h1 className="text-4xl md:text-5xl font-bold tracking-tighter">
            Join Our Mission
          </h1>
          <p className="mt-4 text-lg text-muted-foreground">
            We're building the future of decentralized web data. Be part of the
            revolution.
          </p>
        </div>

        <div className="grid gap-12">
          {positions.map((positionCategory) => (
            <div key={positionCategory.category}>
              <h2 className="text-3xl font-bold mb-6 flex items-center">
                <positionCategory.icon
                  className={`h-8 w-8 mr-4 text-${positionCategory.color}-500`}
                />
                {positionCategory.category}
              </h2>
              <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                {positionCategory.roles.map((role) => (
                  <Card
                    key={role.title}
                    className="bg-card text-card-foreground"
                  >
                    <CardHeader>
                      <CardTitle>{role.title}</CardTitle>
                      <div className="flex items-center gap-2 mt-2">
                        <Badge variant="secondary">{role.type}</Badge>
                        <Badge variant="outline">{role.location}</Badge>
                      </div>
                    </CardHeader>
                    <CardContent>
                      <p className="text-muted-foreground">
                        {role.description}
                      </p>
                      <Button variant="link" className="p-0 mt-4">
                        Apply Now <ArrowRight className="ml-2 h-4 w-4" />
                      </Button>
                    </CardContent>
                  </Card>
                ))}
              </div>
            </div>
          ))}
        </div>

        <Card className="mt-20 text-center p-8 bg-card">
          <CardTitle className="text-3xl font-bold">
            Don't See Your Role?
          </CardTitle>
          <CardDescription className="mt-2 text-lg text-muted-foreground">
            We're always looking for talented people. If you're passionate about
            our mission, we'd love to hear from you.
          </CardDescription>
          <Button size="lg" className="mt-6">
            <Mail className="mr-2 h-5 w-5" /> Get in Touch
          </Button>
        </Card>
      </main>

      <footer className="border-t border-border/40">
        <div className="container mx-auto py-6 px-4 md:px-6 flex flex-col md:flex-row justify-between items-center text-sm text-muted-foreground">
          <p>
            &copy; {new Date().getFullYear()} PayperCrawl. All rights reserved.
          </p>
          <div className="flex items-center gap-4 mt-4 md:mt-0">
            <Link href="/contact" className="hover:text-foreground">
              Contact
            </Link>
            <Link href="/admin" className="hover:text-foreground">
              Admin
            </Link>
          </div>
        </div>
      </footer>
    </div>
  );
}
