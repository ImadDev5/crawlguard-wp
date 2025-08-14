"use client";

import { useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { Button } from "@/components/ui/button";
import { Sheet, SheetContent, SheetTrigger } from "@/components/ui/sheet";
import {
  Shield,
  Menu,
  Home as HomeIcon,
  Star,
  Info,
  BookOpen,
  Briefcase,
  Mail as MailIcon,
  LayoutDashboard,
  User,
} from "lucide-react";
import { ModeToggle } from "@/components/mode-toggle";
import { useAuth } from "@/contexts/AuthContext";

interface NavigationProps {
  currentPage?: string;
}

export function Navigation({ currentPage }: NavigationProps) {
  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const { user, isAuthenticated, logout } = useAuth();
  const router = useRouter();

  const handleLogout = () => {
    logout();
    router.push("/");
    setIsMenuOpen(false);
  };

  return (
    <nav className="sticky top-0 z-50 bg-background/80 backdrop-blur-md border-b nav-blur">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center h-16">
          <div className="flex items-center space-x-2">
            <Shield className="h-6 w-6 sm:h-8 sm:w-8 text-primary" />
            <span className="text-lg sm:text-xl font-bold text-foreground">
              PayPerCrawl
            </span>
          </div>

          {/* Desktop Navigation */}
          <div className="hidden md:flex items-center space-x-6 lg:space-x-8">
            <Link
              href="/"
              className={`transition-colors ${
                currentPage === "home"
                  ? "text-primary font-semibold"
                  : "text-muted-foreground hover:text-foreground"
              }`}
            >
              Home
            </Link>
            <Link
              href="/features"
              className={`transition-colors ${
                currentPage === "features"
                  ? "text-primary font-semibold"
                  : "text-muted-foreground hover:text-foreground"
              }`}
            >
              Features
            </Link>
            <Link
              href="/about"
              className={`transition-colors ${
                currentPage === "about"
                  ? "text-primary font-semibold"
                  : "text-muted-foreground hover:text-foreground"
              }`}
            >
              About
            </Link>
            <Link
              href="/blog"
              className={`transition-colors ${
                currentPage === "blog"
                  ? "text-primary font-semibold"
                  : "text-muted-foreground hover:text-foreground"
              }`}
            >
              Blog
            </Link>
            <Link
              href="/careers"
              className={`transition-colors ${
                currentPage === "careers"
                  ? "text-primary font-semibold"
                  : "text-muted-foreground hover:text-foreground"
              }`}
            >
              Careers
            </Link>

            {/* Authentication-based navigation */}
            {isAuthenticated ? (
              <>
                <Link
                  href="/dashboard"
                  className={`transition-colors ${
                    currentPage === "dashboard"
                      ? "text-primary font-semibold"
                      : "text-muted-foreground hover:text-foreground"
                  }`}
                >
                  Dashboard
                </Link>

                {/* User Info */}
                <div className="flex items-center space-x-2 text-sm text-muted-foreground">
                  <User className="h-4 w-4" />
                  <span>Welcome, {user?.name}</span>
                </div>

                <Button variant="outline" size="sm" onClick={handleLogout}>
                  Logout
                </Button>
              </>
            ) : (
              <Link href="/waitlist">
                <Button size="sm">Join Beta</Button>
              </Link>
            )}

            <ModeToggle />
          </div>

          {/* Mobile Navigation */}
          <div className="flex md:hidden items-center space-x-2">
            <ModeToggle />
            <Sheet open={isMenuOpen} onOpenChange={setIsMenuOpen}>
              <SheetTrigger asChild>
                <Button variant="ghost" size="sm" className="p-2">
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
                        className={`flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 group backdrop-blur-sm hover:shadow-md ${
                          currentPage === "home"
                            ? "bg-primary/10 border border-primary/20 text-primary"
                            : "text-foreground hover:bg-accent/80 hover:text-accent-foreground"
                        }`}
                        onClick={() => setIsMenuOpen(false)}
                      >
                        <HomeIcon className="h-5 w-5 text-muted-foreground group-hover:text-primary transition-colors drop-shadow-sm" />
                        <span className="font-medium drop-shadow-sm">Home</span>
                      </Link>
                      <Link
                        href="/features"
                        className={`flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 group backdrop-blur-sm hover:shadow-md ${
                          currentPage === "features"
                            ? "bg-primary/10 border border-primary/20 text-primary"
                            : "text-foreground hover:bg-accent/80 hover:text-accent-foreground"
                        }`}
                        onClick={() => setIsMenuOpen(false)}
                      >
                        <Star className="h-5 w-5 text-muted-foreground group-hover:text-primary transition-colors drop-shadow-sm" />
                        <span className="font-medium drop-shadow-sm">
                          Features
                        </span>
                      </Link>
                      <Link
                        href="/about"
                        className={`flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 group backdrop-blur-sm hover:shadow-md ${
                          currentPage === "about"
                            ? "bg-primary/10 border border-primary/20 text-primary"
                            : "text-foreground hover:bg-accent/80 hover:text-accent-foreground"
                        }`}
                        onClick={() => setIsMenuOpen(false)}
                      >
                        <Info className="h-5 w-5 text-muted-foreground group-hover:text-primary transition-colors drop-shadow-sm" />
                        <span className="font-medium drop-shadow-sm">
                          About
                        </span>
                      </Link>
                      <Link
                        href="/blog"
                        className={`flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 group backdrop-blur-sm hover:shadow-md ${
                          currentPage === "blog"
                            ? "bg-primary/10 border border-primary/20 text-primary"
                            : "text-foreground hover:bg-accent/80 hover:text-accent-foreground"
                        }`}
                        onClick={() => setIsMenuOpen(false)}
                      >
                        <BookOpen className="h-5 w-5 text-muted-foreground group-hover:text-primary transition-colors drop-shadow-sm" />
                        <span className="font-medium drop-shadow-sm">Blog</span>
                      </Link>
                      <Link
                        href="/careers"
                        className={`flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 group backdrop-blur-sm hover:shadow-md ${
                          currentPage === "careers"
                            ? "bg-primary/10 border border-primary/20 text-primary"
                            : "text-foreground hover:bg-accent/80 hover:text-accent-foreground"
                        }`}
                        onClick={() => setIsMenuOpen(false)}
                      >
                        <Briefcase className="h-5 w-5 text-muted-foreground group-hover:text-primary transition-colors drop-shadow-sm" />
                        <span className="font-medium drop-shadow-sm">
                          Careers
                        </span>
                      </Link>
                      <Link
                        href="/contact"
                        className={`flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 group backdrop-blur-sm hover:shadow-md ${
                          currentPage === "contact"
                            ? "bg-primary/10 border border-primary/20 text-primary"
                            : "text-foreground hover:bg-accent/80 hover:text-accent-foreground"
                        }`}
                        onClick={() => setIsMenuOpen(false)}
                      >
                        <MailIcon className="h-5 w-5 text-muted-foreground group-hover:text-primary transition-colors drop-shadow-sm" />
                        <span className="font-medium drop-shadow-sm">
                          Contact
                        </span>
                      </Link>

                      {/* Authentication-based mobile navigation */}
                      {isAuthenticated && (
                        <Link
                          href="/dashboard"
                          className={`flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 group backdrop-blur-sm hover:shadow-md ${
                            currentPage === "dashboard"
                              ? "bg-primary/10 border border-primary/20 text-primary"
                              : "text-foreground hover:bg-accent/80 hover:text-accent-foreground"
                          }`}
                          onClick={() => setIsMenuOpen(false)}
                        >
                          <LayoutDashboard className="h-5 w-5 text-muted-foreground group-hover:text-primary transition-colors drop-shadow-sm" />
                          <span className="font-medium drop-shadow-sm">
                            Dashboard
                          </span>
                        </Link>
                      )}
                    </div>
                  </nav>

                  {/* Footer Section */}
                  <div className="p-6 border-t border-border/40 bg-accent/30 backdrop-blur-lg">
                    {isAuthenticated ? (
                      <div className="space-y-3">
                        <div className="flex items-center space-x-2">
                          <User className="h-4 w-4 text-muted-foreground" />
                          <span className="text-sm font-medium text-foreground drop-shadow-sm">
                            Welcome, {user?.name}
                          </span>
                        </div>
                        <p className="text-xs text-muted-foreground">
                          {user?.email}
                        </p>
                        <Button
                          variant="outline"
                          size="sm"
                          className="w-full"
                          onClick={handleLogout}
                        >
                          Logout
                        </Button>
                      </div>
                    ) : (
                      <div className="space-y-3">
                        <p className="text-sm font-medium text-foreground drop-shadow-sm">
                          Join our exclusive beta program
                        </p>
                        <Link
                          href="/waitlist"
                          onClick={() => setIsMenuOpen(false)}
                        >
                          <Button className="w-full">Join Beta</Button>
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
