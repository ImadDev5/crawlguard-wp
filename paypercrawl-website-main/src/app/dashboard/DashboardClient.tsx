"use client";

import { useState, useEffect, Suspense } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { useAuth } from "@/contexts/AuthContext";
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
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Alert, AlertDescription } from "@/components/ui/alert";
import {
  ArrowRight,
  Shield,
  Key,
  Download,
  Copy,
  RefreshCw,
  Home as HomeIcon,
  Star,
  Info,
  BookOpen,
  Briefcase,
  Mail,
  Menu,
  LayoutDashboard,
  CheckCircle,
  Eye,
  EyeOff,
  Loader2,
} from "lucide-react";
import Link from "next/link";
import { ModeToggle } from "@/components/mode-toggle";
import { useToast } from "@/hooks/use-toast";

interface User {
  email: string;
  name: string;
  website?: string;
}

export default function DashboardClient() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { toast } = useToast();
  const { user, isAuthenticated, isLoading, logout } = useAuth();

  const [isMenuOpen, setIsMenuOpen] = useState(false);
  const [apiKey, setApiKey] = useState("");
  const [isApiKeyVisible, setIsApiKeyVisible] = useState(false);
  const [isGenerating, setIsGenerating] = useState(false);
  const [isCopied, setIsCopied] = useState(false);

  // Handle authentication state
  useEffect(() => {
    if (!isLoading && !isAuthenticated) {
      router.push("/waitlist");
      return;
    }

    // Show welcome message if coming from invite link and user is authenticated
    if (isAuthenticated && user && searchParams.get("token")) {
      toast({
        title: "Welcome to PayPerCrawl!",
        description: `Welcome ${user.name || user.email}, you now have access to the beta dashboard.`,
      });
    }
  }, [isAuthenticated, isLoading, user, searchParams, router, toast]);

  // Show loading screen while validating
  if (isLoading) {
    return (
      <div className="min-h-screen bg-background flex items-center justify-center">
        <Card className="w-[400px]">
          <CardHeader className="text-center">
            <div className="flex items-center justify-center space-x-2 mb-4">
              <Shield className="h-8 w-8 text-primary" />
              <span className="text-2xl font-bold">PayPerCrawl</span>
            </div>
            <CardTitle>Verifying Access</CardTitle>
            <CardDescription>
              Please wait while we verify your beta invitation...
            </CardDescription>
          </CardHeader>
          <CardContent className="flex justify-center">
            <Loader2 className="h-8 w-8 animate-spin text-primary" />
          </CardContent>
        </Card>
      </div>
    );
  }

  // If not authenticated, this shouldn't happen due to middleware redirect
  if (!isAuthenticated || !user) {
    return null;
  }

  const generateApiKey = async () => {
    setIsGenerating(true);
    try {
      const response = await fetch("/api/apikeys/generate", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
      });

      const data = await response.json();

      if (data.success) {
        setApiKey(data.apiKey);
      } else {
        console.error("Failed to generate API key:", data.error);
        // You could add error handling UI here
      }
    } catch (error) {
      console.error("Error generating API key:", error);
    } finally {
      setIsGenerating(false);
    }
  };

  const copyApiKey = async () => {
    if (apiKey) {
      await navigator.clipboard.writeText(apiKey);
      setIsCopied(true);
      setTimeout(() => setIsCopied(false), 2000);
    }
  };

  const downloadPlugin = () => {
    // Download the actual plugin from the API
    const element = document.createElement("a");
    element.href = "/api/plugin/download";
    element.download = "crawlguard-wp-paypercrawl.zip";
    document.body.appendChild(element);
    element.click();
    document.body.removeChild(element);
  };

  return (
    <div className="min-h-screen bg-background">
      {/* Navigation */}
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
              <Link
                href="/dashboard"
                className="text-primary font-semibold hover:text-primary/80 transition-colors"
              >
                Dashboard
              </Link>

              {/* User Info */}
              <div className="flex items-center space-x-2 text-sm text-muted-foreground">
                <span>Welcome, {user?.name}</span>
              </div>

              <Button
                variant="outline"
                size="sm"
                onClick={async () => {
                  await logout();
                  router.push("/");
                }}
              >
                Logout
              </Button>
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
                          className="flex items-center space-x-3 px-4 py-3 rounded-xl text-foreground bg-primary/10 border border-primary/20 transition-all duration-200 group hover:bg-primary/15 hover:border-primary/30 backdrop-blur-sm shadow-lg"
                          onClick={() => setIsMenuOpen(false)}
                        >
                          <LayoutDashboard className="h-5 w-5 text-primary transition-colors drop-shadow-sm" />
                          <span className="font-semibold text-foreground drop-shadow-sm">
                            Dashboard +{" "}
                          </span>
                        </Link>
                      </div>
                    </nav>

                    {/* User Info Section */}
                    <div className="p-6 border-t border-border/40 bg-accent/30 backdrop-blur-lg">
                      <div className="space-y-3">
                        <p className="text-sm font-medium text-foreground drop-shadow-sm">
                          Welcome back, {user?.name}
                        </p>
                        <p className="text-xs text-muted-foreground">
                          {user?.email}
                        </p>
                        <Button
                          variant="outline"
                          size="sm"
                          className="w-full"
                          onClick={async () => {
                            await logout();
                            router.push("/");
                            setIsMenuOpen(false);
                          }}
                        >
                          Logout
                        </Button>
                      </div>
                    </div>
                  </div>
                </SheetContent>
              </Sheet>
            </div>
          </div>
        </div>
      </nav>

      {/* Main Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="mb-8">
          <div className="flex items-center space-x-3 mb-4">
            <LayoutDashboard className="h-8 w-8 text-primary" />
            <h1 className="text-3xl sm:text-4xl font-bold text-foreground">
              Dashboard
            </h1>
          </div>
          <p className="text-lg text-muted-foreground max-w-2xl">
            Manage your API keys and download the PayPerCrawl plugin to start
            monetizing AI bot traffic on your website.
          </p>
        </div>

        {/* Dashboard Grid */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
          {/* API Key Generator */}
          <Card className="bg-background/50 backdrop-blur-sm border-border/50 shadow-lg hover:shadow-xl transition-all duration-300 glass-card hover-glow">
            <CardHeader className="pb-4">
              <div className="flex items-center space-x-2">
                <Key className="h-6 w-6 text-primary pulse-glow" />
                <CardTitle className="text-xl">API Key Generator</CardTitle>
              </div>
              <CardDescription>
                Generate and manage your PayPerCrawl API keys for secure access
                to our services.
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              {/* API Key Display */}
              <div className="space-y-3">
                <Label htmlFor="api-key" className="text-sm font-medium">
                  Your API Key
                </Label>
                <div className="relative">
                  <Input
                    id="api-key"
                    value={apiKey}
                    type={isApiKeyVisible ? "text" : "password"}
                    placeholder="Generate an API key to get started"
                    readOnly
                    className="pr-20 bg-background/80 backdrop-blur-sm"
                  />
                  <div className="absolute right-2 top-1/2 -translate-y-1/2 flex space-x-1">
                    {apiKey && (
                      <Button
                        size="sm"
                        variant="ghost"
                        className="h-7 w-7 p-0"
                        onClick={() => setIsApiKeyVisible(!isApiKeyVisible)}
                      >
                        {isApiKeyVisible ? (
                          <EyeOff className="h-4 w-4" />
                        ) : (
                          <Eye className="h-4 w-4" />
                        )}
                      </Button>
                    )}
                    {apiKey && (
                      <Button
                        size="sm"
                        variant="ghost"
                        className="h-7 w-7 p-0"
                        onClick={copyApiKey}
                      >
                        {isCopied ? (
                          <CheckCircle className="h-4 w-4 text-green-500" />
                        ) : (
                          <Copy className="h-4 w-4" />
                        )}
                      </Button>
                    )}
                  </div>
                </div>
              </div>

              {/* Generate Button */}
              <Button
                onClick={generateApiKey}
                disabled={isGenerating}
                className="w-full bg-primary hover:bg-primary/90 text-primary-foreground font-semibold shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5 dim:shadow-2xl dim:hover:shadow-primary/20"
              >
                {isGenerating ? (
                  <>
                    <RefreshCw className="mr-2 h-4 w-4 animate-spin" />
                    Generating...
                  </>
                ) : (
                  <>
                    <Key className="mr-2 h-4 w-4" />
                    {apiKey ? "Regenerate API Key" : "Generate API Key"}
                  </>
                )}
              </Button>

              {isCopied && (
                <Alert className="bg-green-50/80 dark:bg-green-950/30 dim:bg-green-900/20 border-green-200 dark:border-green-800 dim:border-green-700/50 backdrop-blur-sm">
                  <CheckCircle className="h-4 w-4 text-green-600 dark:text-green-400 dim:text-green-300" />
                  <AlertDescription className="text-green-800 dark:text-green-200 dim:text-green-100">
                    API key copied to clipboard!
                  </AlertDescription>
                </Alert>
              )}

              {apiKey && (
                <Alert className="bg-yellow-50/80 dark:bg-yellow-950/30 dim:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800 dim:border-yellow-700/50 backdrop-blur-sm">
                  <Shield className="h-4 w-4 text-yellow-600 dark:text-yellow-400 dim:text-yellow-300" />
                  <AlertDescription className="text-yellow-800 dark:text-yellow-200 dim:text-yellow-100">
                    Keep your API key secure and never share it publicly. This
                    key provides access to your PayPerCrawl account.
                  </AlertDescription>
                </Alert>
              )}
            </CardContent>
          </Card>

          {/* Plugin Download */}
          <Card className="bg-background/50 backdrop-blur-sm border-border/50 shadow-lg hover:shadow-xl transition-all duration-300 glass-card hover-glow">
            <CardHeader className="pb-4">
              <div className="flex items-center space-x-2">
                <Download className="h-6 w-6 text-primary pulse-glow" />
                <CardTitle className="text-xl">WordPress Plugin</CardTitle>
              </div>
              <CardDescription>
                Download and install the PayPerCrawl WordPress plugin to start
                detecting and monetizing AI bot traffic.
              </CardDescription>
            </CardHeader>
            <CardContent className="space-y-6">
              {/* Plugin Features */}
              <div className="space-y-3">
                <h4 className="font-semibold text-foreground">
                  Plugin Features:
                </h4>
                <div className="space-y-2">
                  <div className="flex items-center space-x-2">
                    <CheckCircle className="h-4 w-4 text-green-500" />
                    <span className="text-sm text-muted-foreground">
                      Advanced AI bot detection
                    </span>
                  </div>
                  <div className="flex items-center space-x-2">
                    <CheckCircle className="h-4 w-4 text-green-500" />
                    <span className="text-sm text-muted-foreground">
                      Real-time traffic monetization
                    </span>
                  </div>
                  <div className="flex items-center space-x-2">
                    <CheckCircle className="h-4 w-4 text-green-500" />
                    <span className="text-sm text-muted-foreground">
                      Easy WordPress integration
                    </span>
                  </div>
                  <div className="flex items-center space-x-2">
                    <CheckCircle className="h-4 w-4 text-green-500" />
                    <span className="text-sm text-muted-foreground">
                      Detailed analytics dashboard
                    </span>
                  </div>
                </div>
              </div>

              {/* Plugin Info */}
              <div className="grid grid-cols-2 gap-4 p-4 bg-accent/20 rounded-lg glass-card">
                <div>
                  <p className="text-xs text-muted-foreground">Version</p>
                  <p className="font-semibold">2.0.0</p>
                </div>
                <div>
                  <p className="text-xs text-muted-foreground">Size</p>
                  <p className="font-semibold">2.3 MB</p>
                </div>
                <div>
                  <p className="text-xs text-muted-foreground">Compatible</p>
                  <p className="font-semibold">WordPress 5.0+</p>
                </div>
                <div>
                  <p className="text-xs text-muted-foreground">Last Updated</p>
                  <p className="font-semibold">Jan 2025</p>
                </div>
              </div>

              {/* Download Button */}
              <Button
                onClick={downloadPlugin}
                className="w-full bg-gradient-to-r from-primary to-primary/80 hover:from-primary/90 hover:to-primary/70 text-primary-foreground font-semibold shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5 dim:shadow-2xl dim:hover:shadow-primary/20"
              >
                <Download className="mr-2 h-4 w-4" />
                Download Plugin
              </Button>

              <Alert className="bg-blue-50/80 dark:bg-blue-950/30 dim:bg-blue-900/20 border-blue-200 dark:border-blue-800 dim:border-blue-700/50 backdrop-blur-sm">
                <Info className="h-4 w-4 text-slate-900 dark:text-blue-400 dim:text-blue-300" />
                <AlertDescription className="!text-slate-900 dark:!text-blue-200 dim:!text-blue-100">
                  You'll need an API key to configure the plugin. Generate one
                  using the API Key Generator above.
                </AlertDescription>
              </Alert>
            </CardContent>
          </Card>
        </div>

        {/* Additional Info Section */}
        <div className="mt-12">
          <Card className="bg-gradient-to-r from-primary/5 to-primary/10 border-primary/20 shadow-lg glass-card hover-glow">
            <CardContent className="p-8">
              <div className="text-center space-y-4">
                <div className="flex justify-center">
                  <Badge
                    variant="outline"
                    className="text-primary border-primary/30 badge-enhanced"
                  >
                    Getting Started
                  </Badge>
                </div>
                <h3 className="text-2xl font-bold text-foreground">
                  Ready to Start Monetizing?
                </h3>
                <p className="text-muted-foreground max-w-2xl mx-auto">
                  Follow these simple steps: Generate your API key, download the
                  WordPress plugin, install it on your site, and configure it
                  with your API key. Start earning from AI bot traffic
                  immediately!
                </p>
                <div className="flex flex-col sm:flex-row gap-4 justify-center mt-6">
                  <Link href="/features">
                    <Button
                      variant="outline"
                      className="border-primary/30 hover:bg-primary/10 hover-glow transition-all duration-300"
                    >
                      <Star className="mr-2 h-4 w-4" />
                      View Features
                    </Button>
                  </Link>
                  <Link href="/contact">
                    <Button
                      variant="outline"
                      className="border-primary/30 hover:bg-primary/10 hover-glow transition-all duration-300"
                    >
                      <Mail className="mr-2 h-4 w-4" />
                      Get Support
                    </Button>
                  </Link>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
