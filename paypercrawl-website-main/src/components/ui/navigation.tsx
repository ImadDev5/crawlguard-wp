"use client";

import { useState } from "react";
import Link from "next/link";
import { useAuth } from "@/contexts/AuthContext";
import { Button } from "@/components/ui/button";
import { Sheet, SheetContent, SheetTrigger } from "@/components/ui/sheet";
import { ModeToggle } from "@/components/mode-toggle";
import { SignInModal } from "@/components/ui/sign-in-modal";
import {
  Shield,
  Menu,
  Home as HomeIcon,
  Star,
  Info,
  BookOpen,
  Briefcase,
  Mail,
  LayoutDashboard,
  ArrowRight,
  LogOut,
  User,
  LogIn,
} from "lucide-react";

export function Navigation() {
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const { user, isAuthenticated, logout, isLoading } = useAuth();

  const handleLogout = async () => {
    await logout();
    setIsMenuOpen(false);
  };

  return (
    <nav className="sticky top-0 z-50 bg-background/80 supports-[backdrop-filter]:backdrop-blur-xl border-b border-border/60 backdrop-saturate-150">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-16 gap-4">
          <div className="flex items-center space-x-2">
            <Shield className="h-6 w-6 sm:h-8 sm:w-8 text-primary" />
            <span className="text-lg sm:text-xl font-bold text-foreground">
              PayPerCrawl
            </span>
          </div>

          {/* Desktop Navigation */}
          <div className="hidden md:flex items-center space-x-5 lg:space-x-7 xl:space-x-9">
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

            {isAuthenticated ? (
              <>
                <Link
                  href="/dashboard"
                  className="text-primary font-semibold hover:text-primary/80 transition-colors"
                >
                  Dashboard
                </Link>
                <div className="flex items-center space-x-2 text-sm text-muted-foreground">
                  <User className="h-4 w-4" />
                  <span>Welcome, {user?.name || user?.email}</span>
                </div>
                <Button variant="outline" size="sm" onClick={handleLogout}>
                  <LogOut className="h-4 w-4 mr-2" />
                  Logout
                </Button>
              </>
            ) : (
              <>
                <Link
                  href="/dashboard"
                  className="text-muted-foreground hover:text-foreground transition-colors"
                >
                  Dashboard
                </Link>
                <SignInModal>
                  <Button
                    variant="outline"
                    size="sm"
                    className="shadow-sm hover:shadow-md"
                  >
                    <LogIn className="h-4 w-4 mr-2" />
                    Sign In
                  </Button>
                </SignInModal>
                <Link href="/waitlist">
                  <Button
                    size="sm"
                    elevation="md"
                    className="shadow-sm hover:shadow-md"
                  >
                    Join Beta
                  </Button>
                </Link>
              </>
            )}
            <ModeToggle />
          </div>

          {/* Mobile Navigation */}
          <div className="flex md:hidden items-center space-x-2">
            <ModeToggle />
            <Sheet open={isMenuOpen} onOpenChange={setIsMenuOpen}>
              <SheetTrigger asChild>
                <Button
                  variant="outline"
                  size="sm"
                  className="p-2 h-9 w-9"
                  elevation="none"
                >
                  <Menu className="h-5 w-5" />
                  <span className="sr-only">Toggle menu</span>
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
                        <span className="font-medium drop-shadow-sm">Home</span>
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
                        <span className="font-medium drop-shadow-sm">Blog</span>
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

                  {/* User Section / CTA Section */}
                  <div className="p-6 border-t border-border/40 bg-accent/30 backdrop-blur-lg">
                    {isAuthenticated && user ? (
                      <div className="space-y-3">
                        <div className="flex items-center space-x-2 text-sm">
                          <User className="h-4 w-4 text-muted-foreground" />
                          <span className="font-medium text-foreground">
                            {user.name || user.email}
                          </span>
                        </div>
                        <Button
                          variant="outline"
                          size="sm"
                          className="w-full"
                          onClick={handleLogout}
                        >
                          <LogOut className="h-4 w-4 mr-2" />
                          Logout
                        </Button>
                      </div>
                    ) : (
                      <div className="space-y-3">
                        <p className="text-sm font-medium text-foreground drop-shadow-sm">
                          Ready to monetize your content?
                        </p>
                        <SignInModal>
                          <Button
                            variant="outline"
                            size="sm"
                            className="w-full mb-2"
                            onClick={() => setIsMenuOpen(false)}
                          >
                            <LogIn className="h-4 w-4 mr-2" />
                            Sign In
                          </Button>
                        </SignInModal>
                        <Link
                          href="/waitlist"
                          onClick={() => setIsMenuOpen(false)}
                        >
                          <Button
                            elevation="md"
                            className="w-full bg-primary/90 hover:bg-primary text-primary-foreground font-semibold shadow-md hover:shadow-lg transition-all duration-200 backdrop-blur-sm border border-primary/25"
                          >
                            Join Beta Program
                            <ArrowRight className="ml-2 h-4 w-4" />
                          </Button>
                        </Link>
                      </div>
                    )}
                  </div>
                </div>
              </SheetContent>
            </Sheet>
          </div>
        </div>
      </div>
    </nav>
  );
}
