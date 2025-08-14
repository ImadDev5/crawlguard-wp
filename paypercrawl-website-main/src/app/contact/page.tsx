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
import { Input } from "@/components/ui/input";
import { Textarea } from "@/components/ui/textarea";
import { Label } from "@/components/ui/label";
import { Sheet, SheetContent, SheetTrigger } from "@/components/ui/sheet";
import {
  Shield,
  Mail,
  Phone,
  MapPin,
  Clock,
  Send,
  ArrowRight,
  CheckCircle,
  Menu,
  X,
  Home as HomeIcon,
  Star,
  Info,
  BookOpen,
  Briefcase,
  LayoutDashboard,
} from "lucide-react";
import Link from "next/link";
import { ModeToggle } from "@/components/mode-toggle";

export default function ContactPage() {
  const [isMenuOpen, setIsMenuOpen] = useState(false);

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
            <Link
              href="/careers"
              className="text-muted-foreground transition-colors hover:text-foreground"
            >
              Careers
            </Link>
            <Link href="/contact" className="text-foreground">
              Contact
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
                          className="flex items-center space-x-3 px-4 py-3 rounded-xl text-primary bg-primary/10 border border-primary/20 transition-all duration-200 group backdrop-blur-sm shadow-lg"
                          onClick={() => setIsMenuOpen(false)}
                        >
                          <Mail className="h-5 w-5 text-primary transition-colors drop-shadow-sm" />
                          <span className="font-semibold drop-shadow-sm">
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
                          Get in touch with us
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

      {/* Hero Section */}
      <section className="relative overflow-hidden bg-primary text-primary-foreground">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 lg:py-32">
          <div className="text-center">
            <Badge
              variant="secondary"
              className="mb-4 bg-primary-foreground/20 text-primary-foreground border-primary-foreground/30"
            >
              <span className="flex items-center gap-2">
                <span className="w-2 h-2 bg-primary rounded-full animate-pulse"></span>
                BETA PROGRAM
              </span>
              Contact Us • 100% Revenue Share
            </Badge>
            <h1 className="text-4xl lg:text-5xl font-bold mb-6">
              Apply for Exclusive
              <span className="text-primary-foreground/80"> Beta Access</span>
            </h1>
            <p className="text-xl text-primary-foreground/90 mb-8 max-w-3xl mx-auto">
              Join our exclusive invite-only program and get 100% revenue share.
              Limited spots available for selected WordPress publishers.
            </p>
          </div>
        </div>
      </section>

      {/* Contact Information */}
      <section className="py-24">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid lg:grid-cols-3 gap-8 mb-16">
            <Card className="bg-card text-card-foreground">
              <CardHeader>
                <Mail className="h-12 w-12 text-primary mb-4 mx-auto" />
                <CardTitle>Email Us</CardTitle>
                <CardDescription>Get in touch with our team</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="space-y-2">
                  <p className="font-medium">support@paypercrawl.com</p>
                  <p className="text-sm text-muted-foreground">
                    Technical support & plugin help
                  </p>
                </div>
                <div className="space-y-2 mt-4">
                  <p className="font-medium">sales@paypercrawl.com</p>
                  <p className="text-sm text-muted-foreground">
                    Sales inquiries & partnerships
                  </p>
                </div>
              </CardContent>
            </Card>

            <Card className="bg-card text-card-foreground">
              <CardHeader>
                <Phone className="h-12 w-12 text-primary mb-4 mx-auto" />
                <CardTitle>Call Us</CardTitle>
                <CardDescription>Speak with our team directly</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="space-y-2">
                  <p className="font-medium">+1 (555) 123-4567</p>
                  <p className="text-sm text-muted-foreground">
                    Business & Enterprise customers
                  </p>
                </div>
                <div className="space-y-2 mt-4">
                  <p className="font-medium">+1 (555) 987-6543</p>
                  <p className="text-sm text-muted-foreground">
                    Technical support line
                  </p>
                </div>
              </CardContent>
            </Card>

            <Card className="bg-card text-card-foreground">
              <CardHeader>
                <MapPin className="h-12 w-12 text-primary mb-4 mx-auto" />
                <CardTitle>Our Office</CardTitle>
                <CardDescription>Visit our headquarters</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="space-y-2">
                  <p className="font-medium">123 Innovation Drive</p>
                  <p className="text-sm text-muted-foreground">
                    Suite 456, Tech City, 78910
                  </p>
                </div>
                <div className="flex items-center justify-center space-x-2 mt-4 text-sm text-muted-foreground">
                  <Clock className="h-4 w-4" />
                  <span>Mon-Fri, 9am - 5pm</span>
                </div>
              </CardContent>
            </Card>
          </div>

          {/* Contact Form */}
          <Card className="max-w-4xl mx-auto bg-card text-card-foreground">
            <CardHeader className="text-center">
              <CardTitle className="text-3xl">Send us a message</CardTitle>
              <CardDescription>
                We'll get back to you within 24 hours.
              </CardDescription>
            </CardHeader>
            <CardContent>
              <form className="space-y-6">
                <div className="grid sm:grid-cols-2 gap-6">
                  <div className="space-y-2">
                    <Label htmlFor="first-name">First Name</Label>
                    <Input id="first-name" placeholder="John" />
                  </div>
                  <div className="space-y-2">
                    <Label htmlFor="last-name">Last Name</Label>
                    <Input id="last-name" placeholder="Doe" />
                  </div>
                </div>
                <div className="space-y-2">
                  <Label htmlFor="email">Email</Label>
                  <Input
                    id="email"
                    type="email"
                    placeholder="john.doe@example.com"
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="subject">Subject</Label>
                  <Input
                    id="subject"
                    placeholder="e.g., Beta Program Inquiry"
                  />
                </div>
                <div className="space-y-2">
                  <Label htmlFor="message">Message</Label>
                  <Textarea
                    id="message"
                    placeholder="Your message..."
                    className="min-h-[150px]"
                  />
                </div>
                <Button type="submit" size="lg" className="w-full">
                  <Send className="mr-2 h-5 w-5" />
                  Send Message
                </Button>
              </form>
            </CardContent>
          </Card>
        </div>
      </section>

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
